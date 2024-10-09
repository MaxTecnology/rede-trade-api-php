<?php

use Core\Request;
use Core\Response;

global $router;
global $model;

$router->post('/criar-cobranca', function (Request $req, Response $res) use ($model) {
    try {
        $body = $req->getAllParameters();
        $valorFatura = $body['valorFatura'];
        $status = $body['status'];
        $transacaoId = $body['transacaoId'];
        $contaId = $body['contaId'];
        $vencimentoFatura = $body['vencimentoFatura'];
        $subContaId = $body['subContaId'];
        $usuarioId = $body['usuarioId'];
        $referencia = $body['referencia'];

        $novaCobranca = $model->create('cobranca', [
            'data' => [
                'valorFatura' => $valorFatura,
                'status' => $status,
                'transacaoId' => $transacaoId,
                'usuarioId' => $usuarioId,
                'contaId' => $contaId,
                'vencimentoFatura' => $vencimentoFatura,
                'subContaId' => $subContaId,
                'referencia' => $referencia,
            ],
            'include' => ['transacao', 'conta', 'subConta'],
        ]);

        return $res->status(201)->json($novaCobranca);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->status(500)->json(['error' => 'Erro ao adicionar cobrança.']);
    }
});

$router->get('/listar-proxima-fatura/{id}', function ($req, $res, $args) use ($model) {
    try {
        $id = $args['id'];

        $cobrancas = $model->findMany('cobranca', [
            'where' => ['usuarioId' => (int)$id],
            'include' => [
                'transacao' => [
                    'include' => ['voucher' => true],
                ],
                'usuario' => [
                    'select' => [
                        'nome' => true,
                        'nomeFantasia' => true,
                        'email' => true,
                        'telefone' => true,
                        'conta' => true,
                    ],
                ],
            ],
            'orderBy' => ['vencimentoFatura' => 'asc'],
        ]);

        if (empty($cobrancas)) {
            return $res->json(['error' => 'Nenhuma cobrança encontrada para o usuário.']);
        }

        $ultimaCobranca = end($cobrancas);
        $dataUltimaCobranca = $ultimaCobranca['vencimentoFatura'];

        $proximaCobranca = null;
        foreach ($cobrancas as $cobranca) {
            if (
                $cobranca['vencimentoFatura'] !== null &&
                $dataUltimaCobranca !== null &&
                $cobranca['vencimentoFatura'] >= $dataUltimaCobranca
            ) {
                $proximaCobranca = $cobranca;
                break;
            }
        }

        $cobrancasMesmoVencimento = array_filter($cobrancas, function ($cobranca) use ($dataUltimaCobranca) {
            return $cobranca['vencimentoFatura'] === $dataUltimaCobranca;
        });

        $resposta = [
            'proximaFatura' => $proximaCobranca ? $proximaCobranca['vencimentoFatura'] : null,
            'cobrancas' => array_values($cobrancasMesmoVencimento),
        ];

        return $res->json($resposta);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro ao listar cobranças.'], 500);
    }
});

$router->get('/listar-cobrancas/{id}', function ($req, $res, $args) use ($model) {
    try {
        $id = (int)$args['id'];

        $cobrancas = $model->findMany('cobranca', [
            'where' => [
                'usuarioId' => $id,
                'status' => ['not' => 'Quitado'],
            ],
            'include' => [
                'transacao' => [
                    'include' => ['voucher' => true],
                ],
                'usuario' => [
                    'select' => [
                        'nome' => true,
                        'nomeFantasia' => true,
                        'email' => true,
                        'telefone' => true,
                        'conta' => true,
                    ],
                ],
            ],
        ]);

        if (!empty($cobrancas)) {
            return $res->json($cobrancas, 200);
        }

        $subConta = $model->find('subContas', [
            'where' => ['idSubContas' => $id],
            'include' => ['cobrancas' => true],
        ]);

        if ($subConta) {
            return $res->json($subConta['cobrancas'], 200);
        }

        return $res->json(['error' => 'Usuário ou subconta não encontrado.'], 404);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro ao listar cobranças.'], 500);
    }
});

$router->get('/listar-todas-cobrancas', function (Request $req, Response $res) use ($model) {
    try {
        $todasCobrancas = $model->findMany('cobranca', [
            'include' => [
                'transacao' => true,
                'conta' => true,
                'subConta' => true,
            ],
        ]);

        return $res->json($todasCobrancas, 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro ao listar todas as cobranças.'], 500);
    }
});

$router->put('/atualizar-cobranca/{idCobranca}', function ($req, $res, $args) use ($model) {
    try {
        $idCobranca = intval($args['idCobranca']);

        $cobrancaExistente = $model->find('cobranca', [
            'where' => ['idCobranca' => $idCobranca],
        ]);

        if (!$cobrancaExistente) {
            return $res->json(['error' => 'Cobrança não encontrada.'], 404);
        }

        $dadosAtualizacao = $req->getAllParameters();
        $valorFatura = $dadosAtualizacao['valorFatura'] ?? null;
        $status = $dadosAtualizacao['status'] ?? null;
        $transacaoId = $dadosAtualizacao['transacaoId'] ?? null;
        $contaId = $dadosAtualizacao['contaId'] ?? null;
        $vencimentoFatura = $dadosAtualizacao['vencimentoFatura'] ?? null;
        $subContaId = $dadosAtualizacao['subContaId'] ?? null;

        $cobrancaAtualizada = $model->update('cobranca', [
            'valorFatura' => $valorFatura,
            'status' => $status,
            'transacaoId' => $transacaoId,
            'contaId' => $contaId,
            'vencimentoFatura' => $vencimentoFatura,
            'subContaId' => $subContaId,
        ], ['idCobranca' => $idCobranca]);

        return $res->json($cobrancaAtualizada, 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro ao atualizar cobrança.'], 500);
    }
});

$router->delete('/deletar_cobranca/{idCobranca}', function ($req, $res, $args) use ($model) {
    try {
        $idCobranca = intval($args['idCobranca']);

        $cobrancaExistente = $model->find('cobranca', [
            'where' => ['idCobranca' => $idCobranca],
        ]);

        if (!$cobrancaExistente) {
            return $res->json(['error' => 'Cobrança não encontrada.'], 404);
        }

        $model->delete('cobranca', ['idCobranca' => $idCobranca]);

        return $res->status(204);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro ao deletar cobrança.'], 500);
    }
});

