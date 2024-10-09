<?php

use Core\Request;
use Core\Response;


global $router;
global $model;

$router->post('/solicitar', function (Request $req, Response $res) {
    try {
        $data = json_decode($req->getBody(), true);
        $usuarioId = $data['usuarioId'] ?? null;
        $valorSolicitado = $data['valorSolicitado'] ?? null;
        $descricaoSolicitante = $data['descricaoSolicitante'] ?? null;
        $matrizId = $data['matrizId'] ?? null;

        if (!$usuarioId || !$valorSolicitado) {
            return $res->json(['error' => 'Dados de solicitação inválidos'], 400);
        }

        $usuario = $this->prisma->usuarios->findUnique([
            'where' => ['idUsuario' => $usuarioId],
        ]);

        if (!$usuario) {
            return $res->json(['error' => 'Usuário não encontrado'], 404);
        }

        $solicitacaoCredito = $this->prisma->solicitacaoCredito->create([
            'data' => [
                'valorSolicitado' => $valorSolicitado,
                'matrizId' => $matrizId ?: null, // Define matrizId como null se não fornecido
                'status' => 'Pendente',
                'descricaoSolicitante' => $descricaoSolicitante,
                'usuarioSolicitanteId' => $usuarioId,
                'usuarioCriadorId' => $usuario->usuarioCriadorId ?: 0,
            ],
            'include' => [
                'usuarioCriador' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
                'matriz' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                    ],
                ],
                'usuarioSolicitante' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
            ],
        ]);

        return $res->json([
            'message' => 'Solicitação de crédito enviada com sucesso',
            'solicitacaoCredito' => $solicitacaoCredito,
        ], 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro interno no servidor'], 500);
    }
});


$router->put('/editar/{solicitacaoId}', function ($req, $res, $args) {
    try {
        $solicitacaoId = (int) $args['solicitacaoId'];
        $data = json_decode($req->getBody(), true);
        $valorSolicitado = $data['valorSolicitado'] ?? null;
        $descricaoSolicitante = $data['descricaoSolicitante'] ?? null;

        if (!$valorSolicitado) {
            return $res->json(['error' => 'Dados de edição inválidos'], 400);
        }

        $solicitacaoCredito = $this->prisma->solicitacaoCredito->findUnique([
            'where' => ['idSolicitacaoCredito' => $solicitacaoId],
        ]);

        if (!$solicitacaoCredito) {
            return $res->json(['error' => 'Solicitação de crédito não encontrada'], 404);
        }

        $solicitacaoAtualizada = $this->prisma->solicitacaoCredito->update([
            'where' => ['idSolicitacaoCredito' => $solicitacaoId],
            'data' => [
                'valorSolicitado' => $valorSolicitado,
                'descricaoSolicitante' => $descricaoSolicitante,
            ],
        ]);

        return $res->json([
            'message' => 'Solicitação de crédito atualizada com sucesso',
            'solicitacaoAtualizada' => $solicitacaoAtualizada,
        ], 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro interno no servidor'], 500);
    }
});

$router->get('/listar/{usuarioId}', function ($req, $res, $args) {
    try {
        $usuarioId = (int) $args['usuarioId'];

        $usuario = $this->prisma->usuarios->findUnique([
            'where' => ['idUsuario' => $usuarioId],
        ]);

        if (!$usuario) {
            return $res->json(['error' => 'Usuário não encontrado'], 404);
        }

        $solicitacoesCredito = $this->prisma->solicitacaoCredito->findMany([
            'where' => ['usuarioSolicitanteId' => $usuarioId],
            'include' => [
                'usuarioCriador' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
                'matriz' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
                'usuarioSolicitante' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
            ],
        ]);

        return $res->json(['solicitacoesCredito' => $solicitacoesCredito], 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro interno no servidor'], 500);
    }
});

$router->get('/listar-todos', function (Request $req, Response $res) {
    try {
        $todasSolicitacoes = $this->prisma->solicitacaoCredito->findMany([
            'include' => [
                'usuarioCriador' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
                'matriz' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
                'usuarioSolicitante' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
            ],
        ]);

        return $res->json(['todasSolicitacoes' => $todasSolicitacoes], 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro interno no servidor'], 500);
    }
});

$router->get('/listar-filhos/{usuarioCriadorId}', function ($req, $res, $args) {
    try {
        $usuarioCriadorId = (int) $args['usuarioCriadorId'];

        $usuarioCriador = $this->prisma->usuarios->findUnique([
            'where' => ['idUsuario' => $usuarioCriadorId],
        ]);

        if (!$usuarioCriador) {
            return $res->json(['error' => 'Usuário criador não encontrado'], 404);
        }

        $usuariosFilhos = $this->prisma->usuarios->findMany([
            'where' => ['usuarioCriadorId' => $usuarioCriadorId],
        ]);

        $idsUsuariosFilhos = array_map(function ($usuario) {
            return $usuario['idUsuario'];
        }, $usuariosFilhos);

        $solicitacoesDosFilhos = $this->prisma->solicitacaoCredito->findMany([
            'where' => ['usuarioSolicitanteId' => ['in' => $idsUsuariosFilhos]],
            'include' => [
                'usuarioCriador' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
                'matriz' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
                'usuarioSolicitante' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
            ],
        ]);

        return $res->json(['solicitacoesDosFilhos' => $solicitacoesDosFilhos], 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro interno no servidor'], 500);
    }
});

$router->put('/encaminhar/{solicitacaoId}', function ($req, $res, $args) {
    try {
        $solicitacaoId = (int) $args['solicitacaoId'];
        $data = $req->getAllParameters();
        $status = $data['status'];
        $comentarioAgencia = $data['comentarioAgencia'];
        $matrizId = $data['matrizId'] ?? null;

        $solicitacaoCredito = $this->prisma->solicitacaoCredito->findUnique([
            'where' => ['idSolicitacaoCredito' => $solicitacaoId],
            'include' => ['usuarioCriador' => true],
        ]);

        if (!$solicitacaoCredito) {
            return $res->json(['error' => 'Solicitação de crédito não encontrada'], 404);
        }

        if ($status !== 'Encaminhado para a matriz' && $status !== 'Negado') {
            return $res->json(['error' => 'Status inválido'], 400);
        }

        $providedMatrizId = $matrizId ?: $solicitacaoCredito['usuarioCriador']['usuarioCriadorId'];

        if (!$providedMatrizId) {
            return $res->json(['error' => 'matrizId não fornecido ou não disponível'], 400);
        }

        $solicitacaoAtualizada = $this->prisma->solicitacaoCredito->update([
            'where' => ['idSolicitacaoCredito' => $solicitacaoId],
            'data' => [
                'status' => $status,
                'matrizId' => $providedMatrizId,
                'comentarioAgencia' => $status === 'Encaminhado para a matriz' ? $comentarioAgencia : null,
            ],
            'include' => [
                'usuarioCriador' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                    ],
                ],
                'matriz' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                    ],
                ],
                'usuarioSolicitante' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                    ],
                ],
            ],
        ]);

        return $res->json([
            'message' => "Solicitação $solicitacaoId " . strtolower($status) . " com sucesso",
            'solicitacaoAtualizada' => $solicitacaoAtualizada,
        ], 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro interno no servidor'], 500);
    }
});

$router->get('/matriz/analisar', function (Request $req, Response $res) {
    try {
        $solicitacoesEmAnalise = $this->prisma->solicitacaoCredito->findMany([
            'where' => [
                'status' => [
                    'in' => ['Encaminhado para a matriz', 'Pendente']
                ]
            ],
            'include' => [
                'usuarioCriador' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
                'matriz' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
                'usuarioSolicitante' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => true,
                    ],
                ],
            ],
        ]);

        return $res->json([
            'message' => 'Lista de créditos enviados para análise da matriz',
            'solicitacoesEmAnalise' => $solicitacoesEmAnalise,
        ], 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro interno no servidor'], 500);
    }
});

$router->put('/finalizar-analise/{solicitacaoId}', function ($req, $res, $args) {
    try {
        $solicitacaoId = (int) $args['solicitacaoId'];
        $body = json_decode($req->getBody(), true);
        $status = $body['status'] ?? null;
        $comentarioMatriz = $body['comentarioMatriz'] ?? null;

        $solicitacaoCredito = $this->prisma->solicitacaoCredito->findUnique([
            'where' => ['idSolicitacaoCredito' => $solicitacaoId],
            'include' => [
                'usuarioSolicitante' => ['include' => ['conta' => true]],
                'matriz' => true,
            ],
        ]);

        if (!$solicitacaoCredito) {
            return $res->json(['error' => 'Solicitação de crédito não encontrada'], 404);
        }

        $limiteCreditoAntes = $solicitacaoCredito['usuarioSolicitante']['conta']['limiteCredito'] ?? 0;

        if ($status !== 'Aprovado' && $status !== 'Negado') {
            return $res->json(['error' => 'Status inválido'], 400);
        }

        $solicitacaoAtualizada = $this->prisma->solicitacaoCredito->update([
            'where' => ['idSolicitacaoCredito' => $solicitacaoId],
            'data' => [
                'status' => $status,
                'matrizAprovacao' => $status === 'Aprovado',
                'comentarioMatriz' => $status === 'Aprovado' ? $comentarioMatriz : null,
            ],
            'include' => [
                'usuarioCriador' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                        'conta' => ['select' => ['limiteCredito' => true]],
                    ],
                ],
                'matriz' => true,
                'usuarioSolicitante' => [
                    'select' => [
                        'idUsuario' => true,
                        'nome' => true,
                        'email' => true,
                        'telefone' => true,
                        'cpf' => true,
                        'cidade' => true,
                        'bairro' => true,
                        'numero' => true,
                        'complemento' => true,
                    ],
                ],
            ],
        ]);

        if ($status === 'Aprovado') {
            $novoLimiteCredito = $limiteCreditoAntes + $solicitacaoCredito['valorSolicitado'];

            // Atualize o limiteCredito na base de dados
            if ($solicitacaoCredito['usuarioSolicitante']['conta']) {
                $this->prisma->conta->update([
                    'where' => ['idConta' => $solicitacaoCredito['usuarioSolicitante']['conta']['idConta']],
                    'data' => ['limiteCredito' => $novoLimiteCredito],
                ]);

                // Registre o valor no FundoPermuta
                $fundoPermutaData = [
                    'valor' => $solicitacaoCredito['valorSolicitado'],
                    'usuarioId' => $solicitacaoCredito['usuarioSolicitante']['idUsuario'],
                ];

                $this->prisma->fundoPermuta->create(['data' => $fundoPermutaData]);
            } else {
                error_log("Conta não encontrada para a solicitação de crédito.");
                // Trate conforme necessário (lançar exceção, retornar erro, etc.)
            }

            $limiteCreditoDepois = $novoLimiteCredito;

            return $res->json([
                'message' => "Solicitação {$solicitacaoId} analisada pela matriz",
                'limiteCreditoAntes' => $limiteCreditoAntes,
                'limiteCreditoDepois' => $limiteCreditoDepois,
                'solicitacaoAtualizada' => $solicitacaoAtualizada,
            ], 200);
        } else {
            return $res->json([
                'message' => "Solicitação {$solicitacaoId} analisada pela matriz",
                'limiteCreditoAntes' => $limiteCreditoAntes,
                'solicitacaoAtualizada' => $solicitacaoAtualizada,
            ], 200);
        }
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro interno no servidor'], 500);
    }
});

$router->delete("/apagar/{solicitacaoId}", function(Request $req, Response $res) use ($model) {
    try {
        $solicitacaoId = (int)$req->get('solicitacaoId');

        $solicitacaoCredito = $model->select('solicitacaoCredito', 'idSolicitacaoCredito = :id', ['id' => $solicitacaoId]);

        if (empty($solicitacaoCredito)) {
            $res->status(404)->body(['error' => 'Solicitação de crédito não encontrada']);
            return;
        }

        $model->query('DELETE FROM solicitacaoCredito WHERE idSolicitacaoCredito = :id', ['id' => $solicitacaoId]);

        $res->status(200)->body([
            'message' => 'Solicitação de crédito apagada com sucesso',
            'solicitacaoCredito' => $solicitacaoCredito
        ]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->body(['error' => 'Erro interno no servidor']);
    }
});


