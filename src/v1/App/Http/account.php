<?php

use Core\Request;
use Core\Response;

global $router;
global $model;

$router->post('/criar-tipo-de-conta', function (Request $req, Response $res) use ($model) {
    try {
        $tipoDaConta = $req->get('tipoDaConta', 'tipoDaConta é obrigatório.');
        $prefixoConta = $req->get('prefixoConta', 'prefixoConta é obrigatório.');
        $descricao = $req->get('descricao');
        $permissoes = $req->get('permissoes');

        $data = [
            'tipoDaConta' => $tipoDaConta,
            'prefixoConta' => $prefixoConta,
            'descricao' => $descricao,
            'permissoes' => json_encode($permissoes),
        ];
        
        $model->insert('tipo_conta', $data);
        $res->json(201, $data);
    } catch (\Exception $error) {
        error_log($error->getMessage());
        $res->error(500, 'Erro interno do servidor.');
    }
});

$router->get('/listar-tipos-de-conta', function (Request $req, Response $res) use ($model) {
    try {
        $tiposDeConta = $model->select('tipo_conta', '', [], 'ORDER BY idTipoConta ASC');
        $res->json(200, $tiposDeConta);
    } catch (\Exception $error) {
        error_log($error->getMessage());
        $res->error(500, 'Erro interno do servidor.');
    }
});

$router->put('/atualizar-tipo-de-conta/{id}', function (Request $req, Response $res) use ($model) {

    try {
        $tipoContaId = (int) $req->get('id');
        $tipoDaConta = $req->get('tipoDaConta');
        $prefixoConta = $req->get('prefixoConta');
        $descricao = $req->get('descricao');
        $permissoes = $req->get('permissoes');

        $data = array_filter([
            'tipoDaConta' => $tipoDaConta,
            'prefixoConta' => $prefixoConta,
            'descricao' => $descricao,
            'permissoes' => json_encode($permissoes),
        ]);

        $model->update('tipo_conta', $data, 'idTipoConta = :idTipoConta', ['idTipoConta' => $tipoContaId]);
        $res->json(200, ['success' => true, 'data' => $data]);
    } catch (\Exception $error) {
        error_log($error->getMessage());
        $res->error(500, 'Erro interno do servidor.');
    }
});


$router->delete('/deletar-tipo-de-conta/{id}', function (Request $req, Response $res) use ($model) {

    try {
        $tipoContaId = (int) $req->get('id');

        $tipoContaExistente = $model->select('tipo_conta', 'idTipoConta = :idTipoConta', ['idTipoConta' => $tipoContaId]);

        if (empty($tipoContaExistente)) {
            return $res->error(404, 'Tipo de conta não encontrado.');
        }

        $contasAssociadas = $model->select('conta', 'tipoContaId = :tipoContaId', ['tipoContaId' => $tipoContaId]);

        if (!empty($contasAssociadas)) {
            return $res->error(400, 'Existem contas associadas a este tipo de conta. Não é possível excluir.');
        }

        $tipoContaDeletado = $model->query('DELETE FROM tipo_conta WHERE idTipoConta = :idTipoConta', ['idTipoConta' => $tipoContaId]);

        $res->json(200, ['Deletado tipo de conta' => $tipoContaExistente[0]['tipoDaConta']]);
    } catch (\Exception $error) {
        error_log($error->getMessage());
        $res->error(500, 'Erro interno do servidor.');
    }
});

$router->post('/criar-conta-para-usuario/{id}', function (Request $req, Response $res) use ($model) {

        try {
            $data = $req->getBody();
            $tipoDaConta = $data['tipoDaConta'];
            $nomeFranquia = $data['nomeFranquia'];
            $diaFechamentoFatura = $data['diaFechamentoFatura'];
            $dataVencimentoFatura = $data['dataVencimentoFatura'];
            $saldoPermuta = $data['saldoPermuta'];
            $saldoDinheiro = $data['saldoDinheiro'];
            $limiteCredito = $data['limiteCredito'];
            $limiteVendaEmpresa = $data['limiteVendaEmpresa'];
            $limiteVendaMensal = $data['limiteVendaMensal'];
            $limiteVendaTotal = $data['limiteVendaTotal'];
            $valorVendaMensalAtual = $data['valorVendaMensalAtual'];
            $valorVendaTotalAtual = $data['valorVendaTotalAtual'];
            $taxaRepasseMatriz = $data['taxaRepasseMatriz'];
            $planoId = $data['planoId'];
            $permissoesEspecificas = $data['permissoesEspecificas'];
            $idUsuario = (int)$req->get('id');
    
            $usuarioExistente = $this->prisma->usuarios->findUnique(['where' => ['idUsuario' => $idUsuario]]);
    
            if (!$usuarioExistente) {
                return $res->status(404)->body(['error' => 'Usuário não encontrado.']);
            }
    
            $usuarioContaExistente = $this->prisma->conta->findFirst([
                'where' => ['usuarioId' => $idUsuario],
                'include' => ['usuario' => true],
            ]);
    
            if ($usuarioContaExistente) {
                return $res->status(400)->body(['error' => 'Este usuário já possui uma conta.']);
            }
    
            $usuarioCriadorId = $usuarioExistente->usuarioCriadorId;
    
            if (!$usuarioCriadorId) {
                return $res->status(500)->body(['error' => 'Erro ao buscar informações do usuário criador.']);
            }
    
            $usuarioCriador = $this->prisma->usuarios->findUnique([
                'where' => ['idUsuario' => $usuarioCriadorId],
                'include' => ['conta' => ['include' => ['tipoDaConta' => true]]],
            ]);
    
            $tipoConta = $this->prisma->tipoConta->findFirst(['where' => ['tipoDaConta' => $tipoDaConta]]);
    
            if (!$tipoConta) {
                return $res->status(404)->body(['error' => 'Tipo de conta não encontrado.']);
            }
    
            $numeroConta = '';
    
            if ($usuarioCriador->conta->tipoDaConta->tipoDaConta === 'Matriz' && $tipoConta->tipoDaConta === 'Franquia Comum') {
                $numeroMatriz = explode('-', $usuarioCriador->conta->numeroConta)[0];
    
                // Encontrar o último número de conta cadastrado com o tipo de conta "Franquia Comum"
                $ultimaContaFranquia = $this->prisma->conta->findFirst([
                    'where' => ['tipoContaId' => $tipoConta->idTipoConta],
                    'orderBy' => ['idConta' => 'desc'],
                ]);
    
                $prefixoFranquia = $tipoConta->prefixoConta ?: '';
    
                $proximoNumeroContaFranquia = $ultimaContaFranquia ? $ultimaContaFranquia->idConta + 1 : 1;
    
                $numeroConta = "{$numeroMatriz}/{$prefixoFranquia}{$proximoNumeroContaFranquia}";
            } elseif ($usuarioCriador->conta->tipoDaConta->tipoDaConta === 'Franquia Comum' && $tipoConta->tipoDaConta === 'Associado') {
                $usuarioCriadorAssociadoId = $usuarioExistente->usuarioCriadorId;
    
                if (!$usuarioCriadorAssociadoId) {
                    return $res->status(500)->body(['error' => 'Erro ao buscar informações do usuário criador.']);
                }
    
                $usuarioCriadorAssociado = $this->prisma->usuarios->findUnique([
                    'where' => ['idUsuario' => $usuarioCriadorAssociadoId],
                    'include' => ['conta' => ['include' => ['tipoDaConta' => true]]],
                ]);
    
                if (!$usuarioCriadorAssociado) {
                    return $res->status(500)->body(['error' => 'Erro ao buscar informações do usuário criador.']);
                }
    
                $ultimaContaAssociado = $this->prisma->conta->findFirst([
                    'where' => ['tipoContaId' => $tipoConta->idTipoConta],
                    'orderBy' => ['idConta' => 'desc'],
                ]);
    
                $prefixoAssociado = $tipoConta->prefixoConta ?: '400';
                $proximoNumeroContaAssociado = $ultimaContaAssociado ? $ultimaContaAssociado->idConta + 1 : 1;
                $contaFranquiaPai = explode('/', $usuarioCriadorAssociado->conta->numeroConta)[1] ?: '';
                $numeroConta = "{$contaFranquiaPai}/{$prefixoAssociado}{$proximoNumeroContaAssociado}";
            } elseif ($usuarioCriador->conta->tipoDaConta->tipoDaConta === 'Matriz' && $tipoConta->tipoDaConta === 'Associado') {
                $ultimaConta = $this->prisma->conta->findFirst([
                    'where' => ['tipoContaId' => $tipoConta->idTipoConta],
                    'orderBy' => ['idConta' => 'desc'],
                ]);
                $numeroMatriz = explode('-', $usuarioCriador->conta->numeroConta)[0];
    
                $prefixoAssociado = $tipoConta->prefixoConta ?: '400';
    
                $proximoNumeroConta = $ultimaConta ? $ultimaConta->idConta + 1 : 1;
    
                $numeroConta = "{$numeroMatriz}/{$prefixoAssociado}{$proximoNumeroConta}";
            } else {
                $ultimaConta = $this->prisma->conta->findFirst([
                    'where' => ['tipoContaId' => $tipoConta->idTipoConta],
                    'orderBy' => ['idConta' => 'desc'],
                ]);
    
                $prefixoConta = $tipoConta->prefixoConta ?: '';
                $proximoNumeroConta = $ultimaConta ? $ultimaConta->idConta + 1 : 1;
    
                $numeroConta = "{$prefixoConta}{$proximoNumeroConta}";
            }
    
            $novaConta = $this->prisma->conta->create([
                'data' => [
                    'tipoContaId' => $tipoConta->idTipoConta,
                    'usuarioId' => $idUsuario,
                    'numeroConta' => $numeroConta,
                    'limiteCredito' => $limiteCredito ?: 0,
                    'saldoPermuta' => $saldoPermuta ?: 0,
                    'saldoDinheiro' => $saldoDinheiro ?: 0,
                    'diaFechamentoFatura' => $diaFechamentoFatura,
                    'dataVencimentoFatura' => $dataVencimentoFatura,
                    'nomeFranquia' => $nomeFranquia,
                    'limiteVendaEmpresa' => $limiteVendaEmpresa,
                    'limiteVendaMensal' => $limiteVendaMensal,
                    'limiteVendaTotal' => $limiteVendaTotal,
                    'valorVendaMensalAtual' => $valorVendaMensalAtual,
                    'valorVendaTotalAtual' => $valorVendaTotalAtual,
                    'taxaRepasseMatriz' => $taxaRepasseMatriz,
                    'permissoesEspecificas' => $permissoesEspecificas,
                    'planoId' => $planoId,
                ],
                'include' => ['plano' => true],
            ]);
    
            $fundoPermutaData = [
                'valor' => $limiteCredito ?: 0,
                'usuarioId' => $idUsuario,
            ];
    
            $this->prisma->fundoPermuta->create(['data' => $fundoPermutaData]);
    
            return $res->status(201)->body($novaConta);
        } catch (\Exception $error) {
            error_log($error);
            return $res->status(500)->body(['error' => 'Erro interno do servidor.']);
        }
    
    
});

$router->get('/listar-contas', function (Request $req, Response $res) use ($model) {
    try {
        $page = (int) $req->get('page', 1);
        $pageSize = (int) $req->get('pageSize', 10); // Padrão: 10 contas por página

        $startIndex = ($page - 1) * $pageSize;

        $contas = $model->query("SELECT * FROM conta LIMIT :limit OFFSET :offset", [
            'limit' => $pageSize,
            'offset' => $startIndex,
        ]);

        $totalItems = $model->query("SELECT COUNT(*) as count FROM conta")->fetchColumn();
        $totalPages = ceil($totalItems / $pageSize);

        $metadata = [
            'page' => $page,
            'pageSize' => $pageSize,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
        ];

        $contasSerialized = array_map(function ($conta) {
            unset($conta['usuario']['senha']);
            return $conta;
        }, $contas);

        return $res->json(200, ['contas' => $contasSerialized, 'metadata' => $metadata]);
    } catch (\Exception $error) {
        error_log($error->getMessage());
        return $res->error(500, 'Erro interno do servidor.');
    }
});

$router->get('/buscar-conta-por-id/{id}', function (Request $req, Response $res) use ($model) {
    try {
        $id = (int) $req->get('id');

        $conta = $model->query("SELECT * FROM conta WHERE idConta = :idConta", ['idConta' => $id]);

        if (empty($conta)) {
            return $res->error(404, 'Conta não encontrada.');
        }

        $usuario = $model->select('usuario', 'idUsuario = :idUsuario', ['idUsuario' => $conta['usuarioId']]);
        $plano = $model->select('plano', 'idPlano = :idPlano', ['idPlano' => $conta['planoId']]);
        $tipoDaConta = $model->select('tipo_conta', 'idTipoConta = :idTipoConta', ['idTipoConta' => $conta['tipoContaId']]);
        $gerenteConta = $model->select('gerente_conta', 'idGerenteConta = :idGerenteConta', ['idGerenteConta' => $conta['gerenteContaId']]);
        $cobrancas = $model->select('cobranca', 'idConta = :idConta', ['idConta' => $id]);
        $subContas = $model->select('sub_contas', 'contaId = :contaId', ['contaId' => $id]);

        $contaSerialized = [
            ...$conta,
            'usuario' => [
                ...$usuario,
                'senha' => null,
            ],
            'plano' => $plano,
            'tipoDaConta' => $tipoDaConta,
            'cobrancas' => $cobrancas,
            'gerenteConta' => $gerenteConta,
            'subContas' => $subContas,
        ];

        return $res->json(200, $contaSerialized);
    } catch (\Exception $error) {
        error_log($error->getMessage());
        return $res->error(500, 'Erro interno do servidor.');
    }
});

$router->get('/buscar-conta-por-numero/{numeroConta}', function (Request $req, Response $res) use ($model) {
    try {
        $numeroConta = $req->get('numeroConta');

        $conta = $model->query("SELECT * FROM conta WHERE numeroConta = :numeroConta", ['numeroConta' => $numeroConta]);

        if (empty($conta)) {
            return $res->error(404, 'Conta não encontrada.');
        }

        $usuario = $model->select('usuario', 'idUsuario = :idUsuario', ['idUsuario' => $conta['usuarioId']]);
        $plano = $model->select('plano', 'idPlano = :idPlano', ['idPlano' => $conta['planoId']]);
        $tipoDaConta = $model->select('tipo_conta', 'idTipoConta = :idTipoConta', ['idTipoConta' => $conta['tipoContaId']]);
        $gerenteConta = $model->select('gerente_conta', 'idGerenteConta = :idGerenteConta', ['idGerenteConta' => $conta['gerenteContaId']]);
        $cobrancas = $model->select('cobranca', 'idConta = :idConta', ['idConta' => $conta['idConta']]);
        $subContas = $model->select('sub_contas', 'contaId = :contaId', ['contaId' => $conta['idConta']]);

        $contaSerialized = [
            ...$conta,
            'usuario' => [
                ...$usuario,
                'senha' => null,
            ],
            'plano' => $plano,
            'tipoDaConta' => $tipoDaConta,
            'cobrancas' => $cobrancas,
            'gerenteConta' => $gerenteConta,
            'subContas' => $subContas,
        ];

        return $res->json(200, $contaSerialized);
    } catch (\Exception $error) {
        error_log($error->getMessage());
        return $res->error(500, 'Erro interno do servidor.');
    } finally {
        $model->disconnect();
    }
});

$router->put('/atualizar-conta/{id}', function (Request $req, Response $res) use ($model) {
    try {
        $id = $req->get('id');
        $data = $req->getBody();

        $contaExistente = $model->select('conta', 'idConta = :id', ['id' => (int)$id]);

        if (empty($contaExistente)) {
            return $res->error(404, 'Conta não encontrada.');
        }

        $saldoPermuta = isset($data['saldoPermuta']) ? $data['saldoPermuta'] : 0;

        $model->update('conta', [
            'nomeFranquia' => $data['nomeFranquia'],
            'dataDeAfiliacao' => $data['dataDeAfiliacao'],
            'valorVendaTotalAtual' => $data['valorVendaTotalAtual'],
            'tipoContaId' => $data['tipoContaId'],
            'gerenteContaId' => $data['gerenteContaId'],
            'limiteCredito' => $data['limiteCredito'],
            'limiteUtilizado' => $data['limiteUtilizado'],
            'limiteDisponivel' => $data['limiteDisponivel'],
            'saldoPermuta' => $saldoPermuta,
            'dataVencimentoFatura' => $data['dataVencimentoFatura'],
            'diaFechamentoFatura' => $data['diaFechamentoFatura'],
            'limiteVendaEmpresa' => $data['limiteVendaEmpresa'],
            'limiteVendaMensal' => $data['limiteVendaMensal'],
            'limiteVendaTotal' => $data['limiteVendaTotal'],
            'valorVendaMensalAtual' => $data['valorVendaMensalAtual'],
            'planoId' => $data['planoId'],
            'taxaRepasseMatriz' => $data['taxaRepasseMatriz'],
            'permissoesEspecificas' => $data['permissoesEspecificas'],
        ], 'idConta = :id', ['id' => (int)$id]);

        return $res->json(200, ['message' => 'Conta atualizada com sucesso.', 'conta' => $data]);
    } catch (\Exception $error) {
        error_log($error->getMessage());
        return $res->error(500, 'Erro interno do servidor.');
    }
});

$router->delete('/deletar-conta/{id}', function (Request $req, Response $res) use ($model) {
    try {
        $id = $req->get('id');

        $contaExistente = $model->select('conta', 'idConta = :id', ['id' => (int)$id]);

        if (empty($contaExistente)) {
            return $res->error(404, 'Conta não encontrada.');
        }

        $model->delete('conta', 'idConta = :id', ['id' => (int)$id]);

        return $res->status(204)->body([]);
    } catch (\Exception $error) {
        error_log($error->getMessage());
        return $res->error(500, 'Erro interno do servidor.');
    }
});

$router->delete('/deletar-conta/{id}', function (Request $req, Response $res) use ($model) {
    try {
        $id = $req->get('id');

        $contaExistente = $model->select('conta', 'idConta = :id', ['id' => (int)$id]);

        if (empty($contaExistente)) {
            return $res->error(404, 'Conta não encontrada.');
        }

        $model->delete('conta', 'idConta = :id', ['id' => (int)$id]);

        return $res->status(204)->body([]);
    } catch (\Exception $error) {
        error_log($error->getMessage());
        return $res->error(500, 'Erro interno do servidor.');
    }
});

$router->post('/adicionar-gerente/{idConta}/{idGerente}', function (Request $req, Response $res) use ($model) {
    try {
        $idConta = $req->get('idConta');
        $idGerente = $req->get('idGerente');

        $contaExistente = $model->select('conta', 'idConta = :id', ['id' => (int)$idConta]);
        $gerenteExistente = $model->select('usuarios', 'idUsuario = :id', ['id' => (int)$idGerente]);

        if (empty($contaExistente) || empty($gerenteExistente)) {
            return $res->error(404, 'Conta ou gerente não encontrado.');
        }

        $contaAtualizada = $model->update('conta', ['gerenteContaId' => (int)$idGerente], 'idConta = :id', ['id' => (int)$idConta]);

        $contaAtualizada['gerenteConta'] = $gerenteExistente;

        return $res->status(200)->body($contaAtualizada);
    } catch (\Exception $error) {
        error_log($error->getMessage());
        return $res->error(500, 'Erro interno do servidor.');
    }
});

$router->put('/remover-gerente/{idConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idConta = $req->get('idConta');

        $contaAtualizada = $model->update('conta', ['gerenteContaId' => null], 'idConta = :id', ['id' => (int)$idConta]);

        return $res->status(200)->body($contaAtualizada);
    } catch (\Exception $error) {
        error_log($error->getMessage());
        return $res->error(500, 'Erro interno do servidor.');
    }
});

$router->get('/contas-gerenciadas/{idUsuario}', function (Request $req, Response $res) use ($model) {
    try {
        $idUsuario = $req->get('idUsuario');

        $usuarioExistente = $model->select('usuarios', 'idUsuario = :id', ['id' => (int)$idUsuario]);

        if (empty($usuarioExistente)) {
            return $res->status(404)->body(['error' => 'Usuário não encontrado.']);
        }

        $contasGerenciadas = $model->select('conta', 'gerenteContaId = :id', ['id' => (int)$idUsuario]);

        return $res->status(200)->body($contasGerenciadas);
    } catch (\Exception $error) {
        error_log($error->getMessage());
        return $res->status(500)->body(['error' => 'Erro interno do servidor.']);
    }
});

$router->post('/criar-subconta/{idContaPai}', function (Request $req, Response $res) use ($model) {
    try {
        $idContaPai = $req->get('idContaPai');
        $data = $req->getParsedBody();

        $nome = $data['nome'];
        $email = $data['email'];
        $cpf = $data['cpf'];
        $senha = $data['senha'];
        $imagem = $data['imagem'];
        $statusConta = $data['statusConta'];
        $reputacao = $data['reputacao'];
        $telefone = $data['telefone'];
        $celular = $data['celular'];
        $emailContato = $data['emailContato'];
        $logradouro = $data['logradouro'];
        $numero = $data['numero'];
        $cep = $data['cep'];
        $complemento = $data['complemento'];
        $bairro = $data['bairro'];
        $cidade = $data['cidade'];
        $estado = $data['estado'];

        $existingEmail = $model->find('usuarios', ['email' => $email]);
        $existingSubContaEmail = $model->find('subContas', ['email' => $email]);

        $existingCPF = $model->find('usuarios', ['cpf' => $cpf]);
        $existingSubContaCPF = $model->find('subContas', ['cpf' => $cpf]);

        if ($existingEmail || $existingCPF) {
            return $res->status(400)->json(['error' => 'Email e/ou CPF já cadastrado para um usuário.']);
        }

        if ($existingSubContaEmail || $existingSubContaCPF) {
            return $res->status(400)->json(['error' => 'Email e/ou CPF já cadastrado para uma subconta.']);
        }

        $numeroSubContas = $model->count('subContas', ['contaPaiId' => (int)$idContaPai]);
        if ($numeroSubContas >= 4) {
            return $res->status(400)->json(['error' => 'Não é possível adicionar mais subcontas a esta conta.']);
        }

        $contaPai = $model->find('conta', ['idConta' => (int)$idContaPai], ['numeroConta']);
        if (!$contaPai) {
            return $res->status(404)->json(['error' => 'Conta pai não encontrada.']);
        }

        $ultimaSubConta = $model->findLast('subContas', ['contaPaiId' => (int)$idContaPai]);
        $proximoNumeroSubConta = $ultimaSubConta ? (explode("-", $ultimaSubConta['numeroSubConta'])[1] + 1) : 1;

        $numeroSubConta = "{$contaPai['numeroConta']}-{$proximoNumeroSubConta}";
        $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

        $novaSubConta = $model->create('subContas', [
            'nome' => $nome,
            'email' => $email,
            'cpf' => $cpf,
            'imagem' => $imagem,
            'statusConta' => $statusConta,
            'reputacao' => $reputacao,
            'emailContato' => $emailContato,
            'senha' => $senhaCriptografada,
            'numeroSubConta' => $numeroSubConta,
            'contaPaiId' => (int)$idContaPai,
            'telefone' => $telefone,
            'celular' => $celular,
            'logradouro' => $logradouro,
            'numero' => $numero,
            'cep' => $cep,
            'complemento' => $complemento,
            'bairro' => $bairro,
            'cidade' => $cidade,
            'estado' => $estado,
        ]);

        unset($novaSubConta['senha']);
        return $res->status(201)->json($novaSubConta);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->status(500)->json(['error' => 'Erro interno do servidor.']);
    }
});

$router->delete('/deletar-subconta/{idSubConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idSubConta = $req->get('idSubConta');

        $subConta = $model->find('subContas', ['idSubContas' => (int)$idSubConta]);

        if (!$subConta) {
            return $res->status(404)->json(['error' => 'Subconta não encontrada.']);
        }

        $model->delete('subContas', ['idSubContas' => (int)$idSubConta]);

        return $res->status(200)->json(['message' => 'Subconta deletada com sucesso.']);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->status(500)->json(['error' => 'Erro interno do servidor.']);
    }
});

$router->get('/listar-subcontas/{idContaPai}', function (Request $req, Response $res) use ($model) {
    try {
        $idContaPai = $req->get('idContaPai');
        $page = $req->query('page', 1);
        $pageSize = $req->query('pageSize', 10);

        $paginaAtual = (int)$page;
        $itensPorPagina = (int)$pageSize;

        $indiceInicio = ($paginaAtual - 1) * $itensPorPagina;

        $subcontas = $model->findMany('subContas', [
            'where' => ['contaPaiId' => (int)$idContaPai],
            'include' => ['contaPai' => ['select' => [
                'idConta', 'dataVencimentoFatura', 'diaFechamentoFatura', 
                'limiteCredito', 'permissoesEspecificas', 'numeroConta', 
                'saldoPermuta', 'limiteVendaEmpresa', 'limiteVendaMensal', 
                'nomeFranquia', 'taxaRepasseMatriz', 'valorVendaMensalAtual', 
                'valorVendaTotalAtual', 'cobrancas'
            ]]],
            'skip' => $indiceInicio,
            'take' => $itensPorPagina,
        ]);

        $totalSubcontas = $model->count('subContas', ['where' => ['contaPaiId' => (int)$idContaPai]]);

        $totalPages = ceil($totalSubcontas / $itensPorPagina);

        $subcontasSemSenha = array_map(function ($subconta) {
            unset($subconta['senha']);
            return $subconta;
        }, $subcontas);

        $paginationMeta = [
            'currentPage' => $paginaAtual,
            'pageSize' => $itensPorPagina,
            'totalItems' => $totalSubcontas,
            'totalPages' => $totalPages,
        ];

        return $res->status(200)->json(['subcontas' => $subcontasSemSenha, 'meta' => $paginationMeta]);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->status(500)->json(['error' => 'Erro interno do servidor.']);
    }
});

$router->get('/buscar-subconta/{idSubConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idSubConta = $req->get('idSubConta');

        $subconta = $model->findUnique('subContas', [
            'where' => ['idSubContas' => (int)$idSubConta],
            'include' => ['contaPai' => ['select' => [
                'idConta', 'dataVencimentoFatura', 'diaFechamentoFatura', 
                'limiteCredito', 'permissoesEspecificas', 'numeroConta', 
                'saldoPermuta', 'limiteVendaEmpresa', 'limiteVendaMensal', 
                'nomeFranquia', 'taxaRepasseMatriz', 'valorVendaMensalAtual', 
                'valorVendaTotalAtual', 'cobrancas'
            ]]],
        ]);

        if (!$subconta) {
            return $res->status(404)->json(['error' => 'Subconta não encontrada.']);
        }

        unset($subconta['senha']);

        return $res->status(200)->json($subconta);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->status(500)->json(['error' => 'Erro interno do servidor.']);
    }
});

$router->put('/atualizar-subconta/{idSubConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idSubConta = $req->get('idSubConta');
        $data = $req->getParsedBody();

        $subConta = $model->findUnique('subContas', [
            'where' => ['idSubContas' => (int)$idSubConta],
        ]);

        if (!$subConta) {
            return $res->withStatus(404)->withJson(['error' => 'Subconta não encontrada.']);
        }

        $subContaAtualizada = $model->update('subContas', [
            'where' => ['idSubContas' => (int)$idSubConta],
            'data' => array_filter([
                'nome' => $data['nome'] ?? null,
                'email' => $data['email'] ?? null,
                'cpf' => $data['cpf'] ?? null,
                'imagem' => $data['imagem'] ?? null,
                'telefone' => $data['telefone'] ?? null,
                'celular' => $data['celular'] ?? null,
                'logradouro' => $data['logradouro'] ?? null,
                'numero' => $data['numero'] ?? null,
                'cep' => $data['cep'] ?? null,
                'complemento' => $data['complemento'] ?? null,
                'bairro' => $data['bairro'] ?? null,
                'cidade' => $data['cidade'] ?? null,
                'estado' => $data['estado'] ?? null,
                'reputacao' => $data['reputacao'] ?? null,
                // Adicione outros campos que você deseja permitir a atualização
            ]),
        ]);

        unset($subContaAtualizada['senha']);

        return $res->withStatus(200)->withJson($subContaAtualizada);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->withStatus(500)->withJson(['error' => 'Erro interno do servidor.']);
    }
});

$router->post('/solicitar-redefinicao-senha/{idSubConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idSubConta = $req->get('idSubConta');

        $subConta = $model->findUnique('subContas', [
            'where' => ['idSubContas' => (int)$idSubConta],
        ]);

        if (!$subConta) {
            return $res->withStatus(404)->withJson(['error' => 'Subconta não encontrada.']);
        }

        $tokenResetSenha = gerarToken();

        $subContaAtualizada = $model->update('subContas', [
            'where' => ['idSubContas' => (int)$idSubConta],
            'data' => [
                'tokenResetSenha' => $tokenResetSenha ?: "",
            ],
        ]);

        $emailDestinatario = $subContaAtualizada['email'];
        $assuntoEmail = "Redefinição de Senha - REDE TRADE";
        $corpoEmail = "Seu token de redefinição de senha é: $tokenResetSenha";
        enviarEmail($emailDestinatario, $assuntoEmail, $corpoEmail);

        return $res->withStatus(200)->withJson(['message' => 'Token enviado com sucesso.']);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->withStatus(500)->withJson(['error' => 'Erro interno do servidor.']);
    }
});

$router->post('/redefinir-senha/{idSubConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idSubConta = $req->get('idSubConta');
        $body = $req->getParsedBody();
        $novaSenha = $body['novaSenha'];
        $token = $body['token'];

        $subConta = $model->findUnique('subContas', [
            'where' => [
                'idSubContas' => (int)$idSubConta,
                'tokenResetSenha' => $token,
            ],
        ]);

        if (!$subConta) {
            return $res->withStatus(400)->withJson(['error' => 'Token de redefinição de senha inválido.']);
        }


        $senhaCriptografada = password_hash($novaSenha, PASSWORD_BCRYPT);

        $subContaAtualizada = $model->update('subContas', [
            'where' => ['idSubContas' => (int)$idSubConta],
            'data' => [
                'senha' => $senhaCriptografada,
                'tokenResetSenha' => null,
            ],
        ]);

        unset($subContaAtualizada['senha']);

        return $res->withStatus(200)->withJson(['message' => 'Senha atualizada com sucesso', 'subConta' => $subContaAtualizada]);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->withStatus(500)->withJson(['error' => 'Erro interno do servidor.']);
    }
});

$router->post('/subcontas/adicionar-permissao/{idSubConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idSubConta = $req->get('idSubConta');
        $body = $req->getParsedBody();
        $permissoes = $body['permissoes'];

        $subcontaExists = $model->findUnique('subContas', [
            'where' => ['idSubContas' => (int)$idSubConta],
        ]);

        if (!$subcontaExists) {
            return $res->withStatus(404)->withJson(['error' => 'Subconta não encontrada.']);
        }

        $subconta = $model->update('subContas', [
            'where' => ['idSubContas' => (int)$idSubConta],
            'data' => [
                'permissoes' => json_encode($permissoes),
            ],
        ]);

        unset($subconta['senha']);

        return $res->withStatus(200)->withJson($subconta);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->withStatus(500)->withJson(['error' => 'Erro ao adicionar permissões à subconta.']);
    }
});

$router->delete('/subcontas/remover-permissoes/{idSubConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idSubConta = $req->get('idSubConta');
        $body = $req->getParsedBody();
        $permissoes = $body['permissoes'];

        $subcontaExists = $model->findUnique('subContas', [
            'where' => ['idSubContas' => (int)$idSubConta],
        ]);

        if (!$subcontaExists) {
            return $res->withStatus(404)->withJson(['error' => 'Subconta não encontrada.']);
        }

        $currentPermissoes = json_decode($subcontaExists['permissoes'], true);

        $permissoesArray = is_array($permissoes) ? $permissoes : [$permissoes];

        $updatedPermissoes = array_filter($currentPermissoes, function ($p) use ($permissoesArray) {
            return !in_array($p, $permissoesArray);
        });

        $updatedSubconta = $model->update('subContas', [
            'where' => ['idSubContas' => (int)$idSubConta],
            'data' => [
                'permissoes' => json_encode(array_values($updatedPermissoes)),
            ],
        ]);

        unset($updatedSubconta['senha']);

        return $res->withStatus(200)->withJson($updatedSubconta);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->withStatus(500)->withJson(['error' => 'Erro ao remover permissões da subconta.']);
    }
});

$router->get('/subcontas/permissoes/{idSubConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idSubConta = $req->get('idSubConta');

        $subconta = $model->findUnique('subContas', [
            'where' => ['idSubContas' => (int)$idSubConta],
        ]);

        if (!$subconta) {
            return $res->withStatus(404)->withJson(['error' => 'Subconta não encontrada.']);
        }

        $permissoes = json_decode($subconta['permissoes'], true);

        return $res->withStatus(200)->withJson(['permissoes' => $permissoes]);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->withStatus(500)->withJson(['error' => 'Erro ao obter permissões da subconta.']);
    }
});

$router->put('/subcontas/atualizar-permissoes/{idSubConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idSubConta = $req->get('idSubConta');
        $permissoes = $req->getParsedBody()['permissoes'];

        $subconta = $model->findUnique('subContas', [
            'where' => ['idSubContas' => (int)$idSubConta],
        ]);

        if (!$subconta) {
            return $res->withStatus(404)->withJson(['error' => 'Subconta não encontrada.']);
        }

        $model->update('subContas', [
            'permissoes' => json_encode($permissoes),
        ], [
            'idSubContas' => (int)$idSubConta,
        ]);

        $updatedSubconta = $model->findUnique('subContas', [
            'where' => ['idSubContas' => (int)$idSubConta],
        ]);

        if ($updatedSubconta) {
            unset($updatedSubconta['senha']);
        }

        return $res->withStatus(200)->withJson($updatedSubconta);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->withStatus(500)->withJson(['error' => 'Erro ao atualizar permissões da subconta.']);
    }
});

$router->post('/tipocontas/adicionar-permissao/{idTipoConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idTipoConta = $req->get('idTipoConta');
        $permissoes = $req->getParsedBody()['permissoes'];

        $tipoContaExists = $model->findUnique('tipoConta', [
            'where' => ['idTipoConta' => (int)$idTipoConta],
        ]);

        if (!$tipoContaExists) {
            return $res->withStatus(404)->withJson(['error' => 'TipoConta não encontrada.']);
        }

        $tipoConta = $model->update('tipoConta', [
            'permissoes' => json_encode($permissoes),
        ], [
            'idTipoConta' => (int)$idTipoConta,
        ]);

        return $res->withStatus(200)->withJson($tipoConta);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->withStatus(500)->withJson(['error' => 'Erro ao adicionar permissões ao TipoConta.']);
    }
});

$router->delete('/tipocontas/remover-permissoes/{idTipoConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idTipoConta = $req->get('idTipoConta');
        $permissoes = $req->getParsedBody()['permissoes'];

        $tipoContaExists = $model->findUnique('tipoConta', [
            'where' => ['idTipoConta' => (int)$idTipoConta],
        ]);

        if (!$tipoContaExists) {
            return $res->withStatus(404)->withJson(['error' => 'TipoConta não encontrada.']);
        }

        $currentPermissoes = json_decode($tipoContaExists['permissoes'], true);
        $novasPermissoes = array_filter($currentPermissoes, function($permissao) use ($permissoes) {
            return !in_array($permissao, $permissoes);
        });

        $updatedTipoConta = $model->update('tipoConta', [
            'permissoes' => json_encode($novasPermissoes),
        ], [
            'idTipoConta' => (int)$idTipoConta,
        ]);

        return $res->withStatus(200)->withJson([
            'message' => 'Permissões removidas com sucesso.',
            'updatedTipoConta' => $updatedTipoConta,
        ]);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->withStatus(500)->withJson(['error' => 'Erro ao remover permissões do TipoConta.']);
    }
});

$router->get('/tipocontas/permissoes/{idTipoConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idTipoConta = $req->get('idTipoConta');

        $tipoConta = $model->findUnique('tipoConta', [
            'where' => ['idTipoConta' => (int)$idTipoConta],
        ]);

        if (!$tipoConta) {
            return $res->withStatus(404)->withJson(['error' => 'TipoConta não encontrada.']);
        }

        $permissoes = json_decode($tipoConta['permissoes'], true);

        return $res->withStatus(200)->withJson(['permissoes' => $permissoes]);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->withStatus(500)->withJson(['error' => 'Erro ao obter permissões do TipoConta.']);
    }
});

$router->put('/tipocontas/atualizar-permissoes/{idTipoConta}', function (Request $req, Response $res) use ($model) {
    try {
        $idTipoConta = $req->get('idTipoConta');
        $permissoes = $req->getParsedBody()['permissoes'];

        $tipoConta = $model->findUnique('tipoConta', [
            'where' => ['idTipoConta' => (int)$idTipoConta],
        ]);

        if (!$tipoConta) {
            return $res->withStatus(404)->withJson(['error' => 'TipoConta não encontrada.']);
        }

        $model->update('tipoConta', [
            'where' => ['idTipoConta' => (int)$idTipoConta],
            'data' => [
                'permissoes' => json_encode($permissoes),
            ],
        ]);

        $updatedTipoConta = $model->findUnique('tipoConta', [
            'where' => ['idTipoConta' => (int)$idTipoConta],
        ]);

        return $res->withStatus(200)->withJson($updatedTipoConta);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->withStatus(500)->withJson(['error' => 'Erro ao atualizar permissões do TipoConta.']);
    }
});

$router->post('/pagamento-do-plano/{idUsuario}', function (Request $req, Response $res) use ($model) {
    try {
        $idUsuario = (int) $req->get('idUsuario');
        $body = $req->getParsedBody();
        $formaPagamento = $body['formaPagamento'];
        $idPlano = $body['idPlano'];

        $usuarioExistente = $model->findUnique('usuarios', [
            'where' => ['idUsuario' => $idUsuario],
            'include' => ['conta'],
        ]);

        if (!$usuarioExistente) {
            return $res->withStatus(404)->withJson(['error' => 'Usuário não encontrado.']);
        }

        $conta = $usuarioExistente['conta'];

        if (!$conta) {
            return $res->withStatus(404)->withJson(['error' => 'Conta não encontrada.']);
        }

        $valorCreditoUtilizado = 0;

        if ($formaPagamento === "100" || $formaPagamento === "50") {
            $plano = $model->findUnique('plano', [
                'where' => ['idPlano' => $idPlano],
            ]);

            if (!$plano) {
                return $res->withStatus(404)->withJson(['error' => 'Plano não encontrado.']);
            }

            $valorCreditoUtilizado = ($formaPagamento === "100" ? 1 : 0.5) * $plano['taxaInscricao'];

            if ($conta['limiteCredito'] == 0) {
                $model->update('conta', [
                    'where' => ['idConta' => $conta['idConta']],
                    'data' => [
                        'limiteCredito' => $valorCreditoUtilizado,
                        'limiteUtilizado' => $valorCreditoUtilizado,
                        'saldoPermuta' => ($conta['saldoPermuta'] ?? 0) - $valorCreditoUtilizado,
                    ],
                ]);
            } elseif (isset($conta['limiteCredito']) && $conta['limiteCredito'] >= $valorCreditoUtilizado) {
                $model->update('conta', [
                    'where' => ['idConta' => $conta['idConta']],
                    'data' => [
                        'limiteUtilizado' => ($conta['limiteUtilizado'] ?? 0) + $valorCreditoUtilizado,
                        'saldoPermuta' => ($conta['saldoPermuta'] ?? 0) - $valorCreditoUtilizado,
                    ],
                ]);
            }

            $model->create('fundoPermuta', [
                'data' => [
                    'valor' => $valorCreditoUtilizado,
                    'usuarioId' => $idUsuario,
                ],
            ]);
        }

        return $res->withStatus(200)->withJson([
            'message' => "Pagamento da taxa de inscrição do plano {$formaPagamento}% lançado como fundo permuta!",
        ]);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->withStatus(500)->withJson([
            'error' => 'Erro interno do servidor ao processar o pagamento do plano.',
        ]);
    }
});
