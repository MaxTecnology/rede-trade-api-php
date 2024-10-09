<?php

use Core\Request;
use Core\Response;


global $router;
global $model;

$router->post("/inserir-transacao", function(Request $req, Response $res) use ($model) {

        try {
            $contaComprador = null;
            $contaVendedor = null;
    
            $data = json_decode($req->getBody(), true);
            extract($data);
    
            $contaComprador = obterContaInfo($subContaCompradorId, $compradorId);
            $contaVendedor = obterContaInfo($subContaVendedorId, $vendedorId);
    
            if (!$contaComprador || !$contaVendedor) {
                return $res->json(['error' => 'Comprador ou vendedor não encontrado'], 400);
            }
    
            $transacoesVendedor = $this->prisma->transacao->findMany(['where' => ['vendedorId' => $vendedorId]]);
            $totalTransacoesVendedor = array_reduce($transacoesVendedor, function($total, $transacao) {
                return $total + $transacao['valorRt'];
            }, 0);
    
            if ($totalTransacoesVendedor + $valorRt > $contaVendedor['limiteVendaEmpresa']) {
                return $res->json(['error' => 'Vendedor atingiu o limite de venda da empresa.'], 400);
            }
    
            if ($totalTransacoesVendedor + $valorRt > $contaVendedor['limiteVendaTotal']) {
                return $res->json(['error' => 'Vendedor atingiu o limite total de venda.'], 400);
            }
    
            $saldoUtilizado = null;
            $limiteUtilizado = 0;
            $limiteDisponivel = null;
    
            $saldoCreditoDisponivel = $contaComprador['limiteCredito'] - $contaComprador['limiteUtilizado'];
            $saldoAnteriorComprador = $contaComprador['saldoPermuta'] ?? 0;
            $saldoAposComprador = 0;
            $saldoAnteriorVendedor = $contaVendedor['saldoPermuta'] ?? 0;
            $saldoAposVendedor = 0;
            $limiteCreditoDisponivelAnterior = $saldoCreditoDisponivel;
            $saldoTotalDisponivel = $saldoCreditoDisponivel + $saldoAnteriorComprador;
    
            if ($saldoTotalDisponivel < $valorRt) {
                return $res->json(['message' => 'O comprador não possuí limite de crédito disponível para esta transação.']);
            }
    
            if ($valorRt <= $saldoAnteriorComprador) {
                $saldoAposComprador = $saldoAnteriorComprador - $valorRt;
                $saldoUtilizado = "saldoPermuta - {$valorRt}";
                $limiteDisponivel = $contaComprador['limiteCredito'] - $contaComprador['limiteUtilizado'];
                $this->prisma->conta->update([
                    'where' => ['idConta' => $contaComprador['idConta']],
                    'data' => [
                        'saldoPermuta' => $saldoAposComprador,
                        'limiteDisponivel' => $limiteDisponivel,
                    ],
                ]);
            } else {
                $valorAbatidoSaldoPermuta = $saldoAnteriorComprador;
                $valorRestante = $valorRt - $valorAbatidoSaldoPermuta;
    
                $limiteUtilizado = $valorRestante;
                $saldoAposComprador = $saldoAnteriorComprador - $valorRt;
                $limiteDisponivel = $contaComprador['limiteCredito'] - ($limiteUtilizado ?? 0);
                $saldoUtilizado = "saldoPermuta - {$valorAbatidoSaldoPermuta} / limiteCredito - {$limiteUtilizado}";
                $this->prisma->conta->update([
                    'where' => ['idConta' => $contaComprador['idConta']],
                    'data' => [
                        'saldoPermuta' => $saldoAposComprador,
                        'limiteDisponivel' => $limiteDisponivel,
                        'limiteUtilizado' => $limiteUtilizado,
                    ],
                ]);
            }
    
            $limiteCreditoDisponivelAposComprador = $limiteDisponivel;
            $saldoAposVendedor = $saldoAnteriorVendedor + $valorRt;
    
            $comissao = 0;
            $comissaoParcelada = 0;
    
            if ($contaComprador && $contaComprador['planoId']) {
                $plano = $this->prisma->plano->findUnique(['where' => ['idPlano' => $contaComprador['planoId']]]);
                if ($plano) {
                    $comissao = ($plano['taxaComissao'] / 100) * $valorRt;
                    if ($numeroParcelas) {
                        $comissaoParcelada = $comissao / $numeroParcelas;
                    }
                }
            }
    
            $comprador = $this->prisma->usuarios->findUnique(['where' => ['idUsuario' => $compradorId]]);
            $vendedor = $this->prisma->usuarios->findUnique(['where' => ['idUsuario' => $vendedorId]]);
            $compradorNome = $comprador['nome'] ?? $nomeComprador;
            $vendedorNome = $vendedor['nome'] ?? $nomeVendedor;
    
            $novaTransacao = $this->prisma->transacao->create([
                'data' => [
                    'compradorId' => $compradorId,
                    'vendedorId' => $vendedorId,
                    'valorRt' => $valorRt,
                    'numeroParcelas' => $numeroParcelas,
                    'descricao' => $descricao,
                    'saldoAnteriorComprador' => $saldoAnteriorComprador,
                    'saldoAnteriorVendedor' => $saldoAnteriorVendedor,
                    'saldoAposComprador' => $saldoAposComprador,
                    'limiteCreditoAnteriorComprador' => $limiteCreditoDisponivelAnterior,
                    'limiteCreditoAposComprador' => $limiteCreditoDisponivelAposComprador,
                    'saldoAposVendedor' => $saldoAposVendedor,
                    'comissao' => $comissao,
                    'comissaoParcelada' => $comissaoParcelada,
                    'nomeComprador' => $compradorNome,
                    'nomeVendedor' => $vendedorNome,
                    'notaAtendimento' => $notaAtendimento,
                    'subContaCompradorId' => $subContaCompradorId ?? null,
                    'subContaVendedorId' => $subContaVendedorId ?? null,
                    'valorAdicional' => $valorAdicional,
                    'observacaoNota' => $observacaoNota,
                    'ofertaId' => $ofertaId,
                    'saldoUtilizado' => $saldoUtilizado ?? "",
                    'status' => 'Concluída',
                ],
            ]);
    
            $this->prisma->conta->update([
                'where' => ['idConta' => $contaVendedor['idConta']],
                'data' => [
                    'saldoPermuta' => $saldoAposVendedor,
                ],
            ]);
    
            $dataAtual = new \DateTime();
            $diaFechamentoFatura = $contaComprador['diaFechamentoFatura'];
            $dataVencimento = new \DateTimeImmutable(
                "{$dataAtual->format('Y')}-{$dataAtual->format('m')}-{$contaComprador['dataVencimentoFatura']}"
            );
    
            if ($dataAtual->format('d') >= $diaFechamentoFatura) {
                $dataVencimento = $dataVencimento->modify('+1 month');
            }
    
            $cobrancasParceladas = [];
            for ($i = 1; $i <= $numeroParcelas; $i++) {
                $novaCobrancaParcelada = $this->prisma->cobranca->create([
                    'data' => [
                        'valorFatura' => $comissaoParcelada,
                        'referencia' => "Transação #{$novaTransacao['idTransacao']} - Parcela {$i}",
                        'status' => 'Emitida',
                        'transacaoId' => $novaTransacao['idTransacao'],
                        'usuarioId' => $novaTransacao['compradorId'],
                        'contaId' => $contaComprador['idConta'],
                        'vencimentoFatura' => $dataVencimento,
                        'gerenteContaId' => $contaComprador['gerenteContaId'],
                    ],
                ]);
                $cobrancasParceladas[] = $novaCobrancaParcelada;
            }
    
            function formatarData($data) {
                return $data->format('d/m/Y H:i');
            }
    
            $dataFormatada = formatarData(new \DateTime());
    
            $corpoEmailComprador = "Olá {$comprador['nome']}, Obrigado por sua transação na plataforma RedeTrade. Abaixo estão os detalhes da transação:\n\n" .
                "Data da transação: {$dataFormatada}\n" .
                "Código da transação: {$novaTransacao['codigo']}\n" .
                "Valor da transação: R$ " . number_format($valorRt, 2, ',', '.') . "\n" .
                "Número de Parcelas: {$numeroParcelas}\n" .
                "Descrição: {$descricao}\n" .
                "Nome do Vendedor: {$nomeVendedor}\n" .
                "Nota de Atendimento: {$notaAtendimento}\n\n" .
                "Atenciosamente,\n" .
                "RedeTrade";
    
            $corpoEmailVendedor = "Olá {$vendedor['nome']}, Obrigado por sua transação na plataforma RedeTrade. Abaixo estão os detalhes da transação:\n\n" .
                "Data da transação: {$dataFormatada}\n" .
                "Código da transação: {$novaTransacao['codigo']}\n" .
                "Valor da transação: R$ " . number_format($valorRt, 2, ',', '.') . "\n" .
                "Número de Parcelas: {$numeroParcelas}\n" .
                "Descrição: {$descricao}\n" .
                "Nome do Comprador: {$nomeComprador}\n" .
                "Nota de Atendimento: {$notaAtendimento}\n\n" .
                "Atenciosamente,\n" .
                "RedeTrade";
    
            enviarEmail($comprador['email'], "Confirmação de Transação", $corpoEmailComprador);
            enviarEmail($vendedor['email'], "Confirmação de Transação", $corpoEmailVendedor);
    
            return $res->json(['message' => 'Transação criada com sucesso!', 'transacao' => $novaTransacao], 201);
        } catch (\Exception $e) {
            return $res->json(['error' => 'Erro ao criar a transação: ' . $e->getMessage()], 500);
        }
    
});

$router->post("/encaminhar-estorno/{idTransacao}", function(Request $req, Response $res) use ($model) {
    try {
        $idTransacao = $req->get('idTransacao');      
        $model->update('transacao', ['status' => 'Encaminhada para estorno'], 'idTransacao = ?', [$idTransacao]);
        $res->status(200);
        $res->body(['message' => 'Transação encaminhada para estorno com sucesso.']);
    } catch (Exception $error) {
        error_log($error);
        $res->status(500);
        $res->body(['error' => 'Erro ao encaminhar transação para estorno.']);
    }

});

$router->post("/listar-encaminhadas-estorno/{idTransacao}", function(Request $req, Response $res) use ($model) {

    try {
        $idFranquia = $req->get('idFranquia');
        $transacoes = $model->select('transacao', 'status = :status AND (comprador_id IN (SELECT id FROM usuario WHERE usuarioCriadorId = :idFranquia) OR vendedor_id IN (SELECT id FROM usuario WHERE usuarioCriadorId = :idFranquia))', [
            ':status' => 'Encaminhada para estorno',
            ':idFranquia' => (int)$idFranquia,
        ]);

        return $res->body(["Solicitações de estorno" => $transacoes]);
    } catch (\Exception $error) {
        error_log($error);
        return $res->body([
            "error" => "Erro ao visualizar transações encaminhadas para estorno."
        ]);
    }


});

$router->post("/encaminhar-estorno-matriz/{idTransacao}", function(Request $req, Response $res) use ($model) {

    try {
        $idTransacao = $req->get('idTransacao', 'ID da transação é obrigatório');
        $model->update('transacao', ['status' => 'Encaminhada solicitação de estorno para matriz'], 'idTransacao = ?', [$idTransacao]);
        $res->status(200);
        $res->body([
            'message' => 'Solicitação de estorno encaminhada para matriz com sucesso.',
        ]);
    } catch (Exception $error) {
        error_log($error);
        $res->status(500);
        $res->body([
            'error' => 'Erro ao encaminhar solicitação de estorno para matriz.',
        ]);
    }


});

$router->post("/visualizar-estornos-encaminhados-matriz/{idMatriz}", function(Request $req, Response $res) use ($model) {

        try {
            $idMatriz = $req->get('idMatriz');
    
         
            $transacoes = $model->select('transacao', 'status = :status AND (comprador_matrizId = :idMatriz OR vendedor_matrizId = :idMatriz)', [
                'status' => 'Encaminhada solicitação de estorno para matriz',
                'idMatriz' => (int)$idMatriz
            ]);
    
            return $res->body(['transacoes' => $transacoes]);
        } catch (\Exception $error) {
            error_log($error);
            $res->status(500);
            return $res->body([
                'error' => 'Erro ao visualizar transações encaminhadas para matriz.'
            ]);
        }
    
    
});

$router->post("/estornar-transacao/{idTransacao}", function(Request $req, Response $res) use ($model) {

    try {
        $idTransacao = $req->get('idTransacao');


        $transacao = $model->select('transacao', 'idTransacao = ?', [intval($idTransacao)]);

        if (empty($transacao)) {
            return $res->status(404)->body(['error' => 'Transação não encontrada.']);
        }
        
        $transacao = $transacao[0];
        $compradorId = $transacao['compradorId'];
        $vendedorId = $transacao['vendedorId'];
        $valorRt = $transacao['valorRt'];
        $saldoUtilizado = $transacao['saldoUtilizado'];

        if (empty($compradorId) || empty($vendedorId)) {
            return $res->status(400)->body(['error' => 'ID do comprador ou vendedor ausente']);
        }

        $usuarioComprador = $model->select('usuarios', 'idUsuario = ?', [intval($compradorId)], 'conta');

        if (empty($usuarioComprador) || empty($usuarioComprador[0]['conta']['idConta'])) {
            return $res->status(404)->body(['error' => 'Conta do comprador não encontrada']);
        }

        $contaComprador = $usuarioComprador[0]['conta'];

        if ($saldoUtilizado) {
            $saldoUtilizadoParts = explode("/", $saldoUtilizado);
            $novoSaldoPermuta = $contaComprador['saldoPermuta'] ?? 0;
            $novoLimiteUtilizado = $contaComprador['limiteUtilizado'] ?? 0;
            $saldoPermutaUtilizado = 0;
            $limiteUtilizado = 0;

            foreach ($saldoUtilizadoParts as $part) {
                list($tipoSaldo, $valorStr) = explode("-", trim($part));
                $valor = intval($valorStr);

                if (trim($tipoSaldo) === "saldoPermuta") {
                    $novoSaldoPermuta += $valor;
                    $saldoPermutaUtilizado = $valor;
                } elseif (trim($tipoSaldo) === "limiteCredito") {
                    $novoLimiteUtilizado -= $valor;
                    $limiteUtilizado = $valor;
                }
            }

            $novoLimiteDisponivel = $contaComprador['limiteCredito'] - $novoLimiteUtilizado;
            $saldoPermutaEstornar = $limiteUtilizado > 0 ? ($novoSaldoPermuta + $limiteUtilizado) : $novoSaldoPermuta;

            $model->update('conta', [
                'saldoPermuta' => $saldoPermutaEstornar,
                'limiteUtilizado' => $novoLimiteUtilizado,
                'limiteDisponivel' => $novoLimiteDisponivel,
            ], 'idConta = ?', [$contaComprador['idConta']]);
        }

        $usuarioVendedor = $model->select('usuarios', 'idUsuario = ?', [intval($vendedorId)], 'conta');

        if (empty($usuarioVendedor) || empty($usuarioVendedor[0]['conta']['idConta'])) {
            return $res->status(404)->body(['error' => 'Conta do vendedor não encontrada']);
        }

        $contaVendedor = $usuarioVendedor[0]['conta'];
        $model->update('conta', [
            'saldoPermuta' => ($contaVendedor['saldoPermuta'] ?? 0) - $valorRt,
        ], 'idConta = ?', [$contaVendedor['idConta']]);

        $model->query('DELETE FROM cobranca WHERE transacaoId = ?', [intval($idTransacao)]);

        $vouchers = $model->select('voucher', 'transacaoId = ?', [intval($idTransacao)]);

        foreach ($vouchers as $voucher) {
            $model->update('voucher', [
                'status' => 'Cancelado',
                'dataCancelamento' => date('Y-m-d H:i:s'),
            ], 'idVoucher = ?', [$voucher['idVoucher']]);
        }

        $model->update('transacao', [
            'status' => 'Estornada',
            'dataDoEstorno' => date('Y-m-d H:i:s'),
        ], 'idTransacao = ?', [intval($idTransacao)]);

        return $res->status(200)->body(['message' => 'Transação estornada com sucesso']);
    } catch (Exception $error) {
        error_log($error);
        return $res->status(500)->body(['error' => 'Erro ao estornar transação.']);
    }


});

$router->get("/listar-transacoes", function(Request $req, Response $res) use ($model) {
    try {
        $page = (int)$req->get('page', 1);
        $pageSize = (int)$req->get('pageSize', 10);
        $offset = ($page - 1) * $pageSize;

        $transacoes = $model->query("
            SELECT t.*, 
                   c.nome AS comprador_nome, c.email AS comprador_email,
                   v.nome AS vendedor_nome, v.email AS vendedor_email,
                   sc.nome AS subContaComprador_nome, sc.email AS subContaComprador_email,
                   sv.nome AS subContaVendedor_nome, sv.email AS subContaVendedor_email
            FROM transacao t
            LEFT JOIN comprador c ON t.compradorId = c.idUsuario
            LEFT JOIN vendedor v ON t.vendedorId = v.idUsuario
            LEFT JOIN sub_conta sc ON t.subContaCompradorId = sc.idSubContas
            LEFT JOIN sub_conta sv ON t.subContaVendedorId = sv.idSubContas
            LEFT JOIN parcelamento p ON t.idTransacao = p.transacaoId
            LEFT JOIN cobrancas co ON t.idTransacao = co.transacaoId
            LIMIT :limit OFFSET :offset", 
            ['limit' => $pageSize, 'offset' => $offset]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $totalItems = $model->query("SELECT COUNT(*) as total FROM transacao")->fetch(\PDO::FETCH_ASSOC)['total'];

        return $res->status(200)->body([
            'transacoes' => $transacoes,
            'meta' => [
                'totalItems' => (int)$totalItems,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => ceil($totalItems / $pageSize),
            ],
        ]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->body(['error' => 'Erro ao listar transações.']);
    }
});

$router->get("/buscar-transacao/{id}", function(Request $req, Response $res) use ($model) {
    try {
        $id = (int)$req->get('id');
        $transacao = $model->query("
            SELECT t.*, 
                   c.idUsuario AS idComprador, c.nome AS nomeComprador, c.email AS emailComprador,
                   v.idUsuario AS idVendedor, v.nome AS nomeVendedor, v.email AS emailVendedor,
                   sc.idSubContas AS idSubContaComprador, sc.nome AS nomeSubContaComprador, sc.email AS emailSubContaComprador,
                   sv.idSubContas AS idSubContaVendedor, sv.nome AS nomeSubContaVendedor, sv.email AS emailSubContaVendedor,
                   p.*, 
                   co.*, 
                   vch.*
            FROM transacao t
            LEFT JOIN comprador c ON t.compradorId = c.idUsuario
            LEFT JOIN vendedor v ON t.vendedorId = v.idUsuario
            LEFT JOIN sub_contas sc ON t.subContaCompradorId = sc.idSubContas
            LEFT JOIN sub_contas sv ON t.subContaVendedorId = sv.idSubContas
            LEFT JOIN parcelamento p ON t.idTransacao = p.transacaoId
            LEFT JOIN cobrancas co ON t.idTransacao = co.transacaoId
            LEFT JOIN voucher vch ON t.idTransacao = vch.transacaoId
            WHERE t.idTransacao = :id", 
            ['id' => $id]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!$transacao) {
            return $res->status(404)->body(['error' => 'Transação não encontrada.']);
        }

        $res->status(200)->body($transacao);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->body(['error' => 'Erro ao obter transação.']);
    }
});

$router->put("/atualizar-transacao/:id", function(Request $req, Response $res) use ($model) {
    try {
        $id = (int)$req->get('id');
        $data = $req->getAllParameters();

        $transacaoAtualizada = $model->update("transacao", $data, "idTransacao = :id", ['id' => $id]);

        if (!$transacaoAtualizada) {
            return $res->status(404)->json(['error' => 'Transação não encontrada.']);
        }

        $transacao = $model->select("transacao", "idTransacao = :id", ['id' => $id]);
        $res->status(200)->json($transacao);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->json(['error' => 'Erro ao atualizar transação.']);
    }
});

$router->delete("/deletar-transacao/{id}", function(Request $req, Response $res) use ($model) {
    try {
        $id = (int)$req->get('id');

        $parcelamentos = $model->select("parcelamento", "transacaoId = :transacaoId", ['transacaoId' => $id]);

        if (count($parcelamentos) > 0) {
            $res->status(400)->body([
                'error' => 'Não é possível excluir uma transação com parcelamentos vinculados.',
            ]);
            return;
        }

        $model->delete("transacao", "idTransacao = :id", ['id' => $id]);

        $res->status(204)->body([]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->body(['error' => 'Erro ao excluir transação.']);
    }
});

$router->delete("/excluir-voucher/{idVoucher}", function(Request $req, Response $res) use ($model) {
    try {
        $idVoucher = (int)$req->get('idVoucher');

        $voucher = $model->select("voucher", "idVoucher = :id", ['id' => $idVoucher]);

        if (empty($voucher)) {
            $res->status(404)->body(['error' => 'Voucher não encontrado']);
            return;
        }

        $model->delete("voucher", "idVoucher = :id", ['id' => $idVoucher]);

        $res->status(204)->body([]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->body(['error' => 'Erro ao excluir o voucher.']);
    }
});

$router->post("/criar-voucher/:idTransacao", function(Request $req, Response $res) use ($model) {
    try {
        $idTransacao = (int)$req->get('idTransacao');

        $transacao = $model->select('transacao', 'idTransacao = ?', [$idTransacao]);
        if (empty($transacao)) {
            return $res->status(404)->body(['error' => 'Transação não encontrada']);
        }

        $novoVoucher = $model->insert('voucher', ['transacaoId' => $idTransacao]);

        $conteudoQRCode = json_encode($novoVoucher);

        $qrcodesDir = __DIR__ . "/qrcodes";
        if (!is_dir($qrcodesDir)) {
            mkdir($qrcodesDir, 0777, true);
        }

        $qrCodeImagePath = "$qrcodesDir/qrcode_{$novoVoucher['codigo']}.png";

        QRcode::png($conteudoQRCode, $qrCodeImagePath);

        $corpoEmail = "Olá {$transacao['nomeComprador']},\n\n" .
                      "Obrigado por sua transação na plataforma RedeTrade. Abaixo estão os detalhes da transação e Voucher:\n" .
                      "Código do Voucher: {$novoVoucher['codigo']}\n" .
                      "Data de Criação: " . date('d/m/Y H:i:s', strtotime($novoVoucher['createdAt'])) . "\n" .
                      "Status: {$novoVoucher['status']}\n\n" .
                      "Código da transação: {$transacao['codigo']}\n" .
                      "Valor da transação: R$ " . number_format($transacao['valorRt'], 2, ',', '.') . "\n" .
                      "Número de Parcelas: {$transacao['numeroParcelas']}\n" .
                      "Descrição: {$transacao['descricao']}\n" .
                      "Nome do Vendedor: {$transacao['nomeVendedor']}\n" .
                      "Nota de Atendimento: {$transacao['notaAtendimento']}\n" .
                      "Observações: {$transacao['observacaoNota']}\n" .
                      "Status: {$transacao['status']}\n" .
                      "Agradecemos por usar a RedeTrade!\n\n" .
                      "Aqui está o QR Code do seu Voucher:\n";

        $comprador = $model->select('usuarios', 'idUsuario = ?', [$transacao['compradorId']]);
        if (empty($comprador)) {
            return $res->status(404)->body(['error' => 'Comprador não encontrado.']);
        }

        enviarEmail($comprador['email'], "Seu Voucher RedeTrade!", $corpoEmail, [
            [
                'filename' => "qrcode_{$novoVoucher['codigo']}.png",
                'content' => file_get_contents($qrCodeImagePath),
            ],
        ]);

        return $res->status(201)->body(['voucher' => $novoVoucher, 'qrCode' => $qrCodeImagePath]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return $res->status(500)->body(['error' => 'Erro ao criar o voucher.']);
    }
});

