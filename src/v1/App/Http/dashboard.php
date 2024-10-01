<?php

use Core\Request;
use Core\Response;


global $router;
global $model;

$router->get("/total-valor-rt", function (Request $req, Response $res)  use ($model){
    try {
        $includeTransacoes = $req->get('includeTransacoes');
        $totalValorRT = 0;
        $transacoes = null;
        
        $inicioMes = date('Y-m-01 00:00:00');
        $fimMes = date('Y-m-t 23:59:59');

        if ($includeTransacoes === "true") {
            $transacoes = $model->select('transacao', 
                'createdAt >= :inicioMes AND createdAt < :fimMes', 
                ['inicioMes' => $inicioMes, 'fimMes' => $fimMes]
            );

            $totalValorRT = array_reduce($transacoes, function($total, $transacao) {
                return $total + $transacao['valorRt'];
            }, 0);
        } else {
            $resultado = $model->query(
                'SELECT SUM(valorRt) as total FROM transacao WHERE createdAt >= :inicioMes AND createdAt < :fimMes', 
                ['inicioMes' => $inicioMes, 'fimMes' => $fimMes]
            )->fetch();

            $totalValorRT = $resultado['total'] ?? 0;
        }

        $res->status(200);
        $res->body(['totalValorRT' => $totalValorRT, 'transacoes' => $transacoes]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500);
        $res->body(['error' => 'Erro interno no servidor']);
    }
    
});

$router->get("/total-valor-rt-por-unidade/{usuarioCriadorId}", function (Request $req, Response $res) {
    
    try {
        $usuarioCriadorId = $req->get('usuarioCriadorId');
        $includeTransacoes = $req->get('includeTransacoes') === "true";

        if (!$usuarioCriadorId) {
            return $res->status(400)->body([
                'error' => 'ID do usuário criador não fornecido'
            ]);
        }

        $usuariosDaUnidade = $this->model->select('usuarios', 'usuarioCriadorId = ?', [
            (int)$usuarioCriadorId
        ]);

        $idsUsuariosDaUnidade = array_map(function ($usuario) {
            return $usuario['idUsuario'];
        }, $usuariosDaUnidade);

        $transacoes = $this->model->select('transacao', 
            'createdAt >= ? AND createdAt < ? AND (
                compradorId IN (?) OR vendedorId IN (?) OR compradorId = ? OR vendedorId = ?
            )', [
            date('Y-m-01'), 
            date('Y-m-01', strtotime('+1 month')), 
            implode(',', $idsUsuariosDaUnidade), 
            implode(',', $idsUsuariosDaUnidade),
            (int)$usuarioCriadorId, 
            (int)$usuarioCriadorId
        ]);

        $valorTotalTransacoes = array_reduce($transacoes, function ($total, $transacao) {
            return $total + $transacao['valorRt'];
        }, 0);

        $res->status(200)->body([
            'valorTotalTransacoes' => $valorTotalTransacoes,
            'transacoes' => $includeTransacoes ? $transacoes : null
        ]);

    } catch (\Exception $e) {
        $res->status(500)->body([
            'error' => 'Erro interno no servidor'
        ]);
    }

});

$router->get("/total-fundo-permuta-matriz/{matrizId}", function (Request $req, Response $res) use ($model) {
    try {
        $matrizId = (int) $req->get('matrizId');

        $fundoPermuta = $model->select('fundoPermuta', 'usuario.matrizId = :matrizId', ['matrizId' => $matrizId]);

        $total = array_reduce($fundoPermuta, function ($acc, $fundo) {
            return $acc + ($fundo['valor'] ?? 0);
        }, 0);

        $res->status(200);
        $res->body(['valorFundoPermutaTotal' => $total, 'fundoPermuta' => $fundoPermuta]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500);
        $res->body(['error' => 'Erro interno no servidor']);
    }
});

$router->get("/total-creditos-aprovados/", function (Request $req, Response $res) use ($model){
    try {
        
        $result = $model->query("
            SELECT SUM(valorSolicitado) AS valorTotalCreditosAprovados
            FROM solicitacaoCredito
            WHERE status = :status
        ", ['status' => 'Aprovado']);

        $valorTotalCreditosAprovados = $result->fetch(\PDO::FETCH_ASSOC)['valorTotalCreditosAprovados'] ?? 0;

        $res->status(200)->body([
            'valorTotalCreditosAprovados' => $valorTotalCreditosAprovados,
        ]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->body(['error' => 'Erro interno no servidor']);
    }
});

$router->get("/fundo-permuta-unidade/{idFranquia}", function (Request $req, Response $res) use ($model) {
    
    try {
        $idFranquia = (int) $req->get('idFranquia');

       
        $franquia = $model->select('usuarios', 'idUsuario = :idFranquia', ['idFranquia' => $idFranquia]);

        if (empty($franquia)) {
            $res->status(404)->body(['error' => 'Franquia não encontrada.']);
            return;
        }

        $associadosFranquia = $model->select('usuarios u JOIN conta c ON u.idUsuario = c.usuarioId JOIN tipo_da_conta tdc ON c.tipoContaId = tdc.id', 
            'u.usuarioCriadorId = :idFranquia AND tdc.tipoDaConta = "Associado"', 
            ['idFranquia' => $idFranquia]
        );

        if (empty($associadosFranquia)) {
            $res->status(200)->body(['valorFundoPermutaUnidade' => 0, 'fundoPermutaFranquia' => []]);
            return;
        }

        $associadosIds = array_column($associadosFranquia, 'idUsuario');
        $fundoPermutaFranquia = $model->select('fundoPermuta fp JOIN usuarios u ON fp.usuarioId = u.idUsuario', 
            'fp.usuarioId IN (' . implode(',', array_fill(0, count($associadosIds), '?')) . ')', 
            $associadosIds
        );

        $total = array_reduce($fundoPermutaFranquia, function ($acc, $fundo) {
            return $acc + ($fundo['valor'] ?? 0);
        }, 0);

        $res->status(200)->body(['valorFundoPermutaUnidade' => $total, 'fundoPermutaFranquia' => $fundoPermutaFranquia]);

    } catch (\Exception $error) {
        error_log($error->getMessage());
        $res->status(500)->body(['error' => 'Erro interno no servidor']);
    }




});

$router->get("/receita-matriz/{matrizId}", function (Request $req, Response $res) use ($model) {
    
    try {
        $matrizId = $req->get('matrizId');

        if (!$matrizId) {
            return $res->status(400)->body(['error' => 'ID da matriz não fornecido']);
        }     

        $agencias = $model->select('usuarios', 'usuarioCriadorId = ?', [intval($matrizId)]);

        $valorTotalReceberMatriz = 0;
        $detalhesReceitaMatriz = [];
        $valorTotalCobrancas = 0;
        $detalhamentoDasCobrancas = [];

        foreach ($agencias as $agencia) {
            $cobrancasAgenciasEAssociadosDaMatriz = $model->select('cobranca', 'usuarioId = ?', [$agencia['idUsuario']]);

            foreach ($cobrancasAgenciasEAssociadosDaMatriz as $cobranca) {
                $valorTotalCobrancas += $cobranca['valorFatura'];

                $detalhamentoDasCobrancas[] = [
                    'idCobranca' => $cobranca['idCobranca'],
                    'valorFatura' => $cobranca['valorFatura'],
                    'referencia' => $cobranca['referencia'],
                    'createdAt' => $cobranca['createdAt'],
                    'status' => $cobranca['status'],
                    'transacaoId' => $cobranca['transacaoId'],
                    'usuarioId' => $cobranca['usuarioId'],
                    'contaId' => $cobranca['contaId'],
                    'vencimentoFatura' => $cobranca['vencimentoFatura'],
                    'subContaId' => $cobranca['subContaId'],
                    'gerenteContaId' => $cobranca['gerenteContaId']
                ];
            }

            if (!in_array($agencia['conta']['tipoDaConta'], ['Franquia', 'Franquia Master'])) {
                continue;
            }

            if ($agencia['matrizId'] != intval($matrizId)) {
                continue;
            }

            $usuariosAssociadosDasAgencias = $model->select('usuarios', 'usuarioCriadorId = ? AND conta.tipoDaConta = ?', [$agencia['idUsuario'], 'Associado']);

            foreach ($usuariosAssociadosDasAgencias as $associado) {
                $cobrancasAssociado = $model->select('cobranca', 'usuarioId = ?', [$associado['idUsuario']]);

                foreach ($cobrancasAssociado as $cobranca) {
                    $valorRepassarMatriz = ($cobranca['valorFatura'] * ($agencia['conta']['taxaRepasseMatriz'] ?? 0)) / 100;

                    $detalhesReceitaMatriz[] = [
                        'agencia' => [
                            'id' => $agencia['idUsuario'],
                            'nome' => $agencia['nome'],
                        ],
                        'associado' => [
                            'id' => $associado['idUsuario'],
                            'nome' => $associado['nome'],
                        ],
                        'valorRepassarMatriz' => $valorRepassarMatriz,
                        'cobranca' => [
                            'id' => $cobranca['idCobranca'],
                            'valorFatura' => $cobranca['valorFatura'],
                            'referencia' => $cobranca['referencia'],
                            'createdAt' => $cobranca['createdAt'],
                            'status' => $cobranca['status'],
                            'transacaoId' => $cobranca['transacaoId'],
                            'usuarioId' => $cobranca['usuarioId'],
                            'contaId' => $cobranca['contaId'],
                            'vencimentoFatura' => $cobranca['vencimentoFatura'],
                            'subContaId' => $cobranca['subContaId'],
                            'gerenteContaId' => $cobranca['gerenteContaId']
                        ]
                    ];

                    $valorTotalReceberMatriz += $valorRepassarMatriz;
                }
            }
        }

        $aReceberRepasses = [
            'valorTotalReceberMatriz' => $valorTotalReceberMatriz,
            'detalhesReceitaMatriz' => $detalhesReceitaMatriz,
        ];

        $aReceberCobrancas = [
            'valorTotalCobrancas' => $valorTotalCobrancas,
            'detalhamentoDasCobrancas' => $detalhamentoDasCobrancas,
        ];

        $res->status(200)->body([
            'aReceberRepasses' => $aReceberRepasses,
            'aReceberCobrancas' => $aReceberCobrancas,
        ]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->body(['error' => 'Erro interno no servidor']);
    }


});

$router->get("/receita-agencia/{agenciaId}", function (Request $req, Response $res) use ($model) {
    try {
        $agenciaId = $req->get('agenciaId');

        if (!$agenciaId) {
            $res->status(400);
            $res->body(['error' => 'ID da agência não fornecido']);
            return;
        }

        
        $associados = $model->select('usuarios', 'usuarioCriadorId = :id AND conta.tipoDaConta.tipoDaConta = :tipo', [
            ':id' => (int)$agenciaId,
            ':tipo' => 'Associado',
        ]);

        $idsAssociados = array_column($associados, 'idUsuario');

        $cobrancasAssociados = $model->select('cobranca', 'usuarioId IN (' . implode(',', array_fill(0, count($idsAssociados), '?')) . ') AND status != :status', array_merge($idsAssociados, [':status' => 'Quitado']));

        $valorTotalReceber = array_reduce($cobrancasAssociados, function ($total, $cobranca) {
            return $total + $cobranca['valorFatura'];
        }, 0);

        $res->status(200);
        $res->body([
            'valorTotalReceber' => $valorTotalReceber,
            'cobrancasAssociados' => $cobrancasAssociados,
        ]);
    } catch (Exception $error) {
        error_log($error);
        $res->status(500);
        $res->body(['error' => 'Erro interno no servidor']);
    }
});

$router->get("/a-pagar-gerente/{idGerente}", function (Request $req, Response $res) use ($model) {
    try {
        $idGerente = $req->get('idGerente');

        if (!$idGerente) {
            return $res->status(400)->body(['error' => 'ID do gerente não fornecido']);
        }


        $dataInicio = new DateTime();
        $dataInicio->setDate($dataInicio->format('Y'), $dataInicio->format('n'), 1);
        $dataFim = new DateTime();
        $dataFim->setDate($dataFim->format('Y'), $dataFim->format('n') + 1, 1);

        $cobrancas = $model->select('cobranca', 'gerenteContaId = :idGerente AND createdAt >= :dataInicio AND createdAt < :dataFim', [
            'idGerente' => intval($idGerente),
            'dataInicio' => $dataInicio->format('Y-m-d H:i:s'),
            'dataFim' => $dataFim->format('Y-m-d H:i:s'),
        ]);

        $valorTotalPagamento = array_reduce($cobrancas, function($total, $cobranca) {
            $valorComissao = ($cobranca['valorFatura'] * ($cobranca['gerente']['taxaComissaoGerente'] ?? 0)) / 100;
            return $total + $valorComissao;
        }, 0);

        $res->status(200)->body(['valorTotalPagamento' => $valorTotalPagamento]);
    } catch (Exception $error) {
        error_log($error);
        $res->status(500)->body(['error' => 'Erro interno no servidor']);
    }
});

$router->get("/a-pagar-todos-gerentes/{idAgencia}", function (Request $req, Response $res) use ($model) {
    try {
        $idAgencia = $req->get('idAgencia');

        if (!$idAgencia) {
            return $res->status(400)->body(['error' => 'ID da agência não fornecido']);
        }

        $gerentes = $model->select('usuarios', 'usuarioCriadorId = ?', [intval($idAgencia)]);
        $gerentes = array_filter($gerentes, function ($gerente) use ($model) {
            $contasGerenciadas = $model->select('contas', 'gerenteId = ?', [$gerente['id']]);
            return count($contasGerenciadas) > 0;
        });

        $valorTotalPagamento = array_reduce($gerentes, function ($total, $gerente) use ($model) {
            $cobrancasGerenciadas = $model->select('cobrancas', 'gerenteId = ? AND createdAt >= ? AND createdAt < ?', [
                $gerente['id'],
                date('Y-m-01'),
                date('Y-m-01', strtotime('+1 month'))
            ]);

            $valorComissao = array_reduce($cobrancasGerenciadas, function ($subtotal, $cobranca) use ($gerente) {
                return $subtotal + ($cobranca['valorFatura'] * ($gerente['taxaComissaoGerente'] ?? 0)) / 100;
            }, 0);

            return $total + $valorComissao;
        }, 0);

        $res->status(200)->body(['valorTotalPagamento' => $valorTotalPagamento]);
    } catch (Exception $error) {
        error_log($error);
        $res->status(500)->body(['error' => 'Erro interno no servidor']);
    }
});
