<?php

use Core\Request;
use Core\Response;


global $router;
global $model;

$router->post('/criar-plano', function(Request $req, Response $res) use ($model) {
    try {
        $data = $req->getAllParameters();
        $nomePlano = $data['nomePlano'];
        $tipoDoPlano = $data['tipoDoPlano'];
        $taxaInscricao = $data['taxaInscricao'];
        $taxaComissao = $data['taxaComissao'];
        $taxaManutencaoAnual = $data['taxaManutencaoAnual'];

        $planoExistente = $model->select('plano', 'nomePlano = ?', [$nomePlano]);

        if (!empty($planoExistente)) {
            return $res->status(400)->json(['error' => 'Já existe um plano com o mesmo nome.']);
        }

        $novoPlano = $model->insert('plano', [
            'nomePlano' => $nomePlano,
            'tipoDoPlano' => $tipoDoPlano,
            'taxaInscricao' => $taxaInscricao,
            'taxaComissao' => $taxaComissao,
            'taxaManutencaoAnual' => $taxaManutencaoAnual,
        ]);

        return $res->status(201)->json($novoPlano);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        return $res->status(500)->json(['error' => 'Erro interno do servidor.']);
    }
});

$router->get('/listar-planos', function(Request $req, Response $res) use ($model) {
    try {
        $page = (int)$req->get('page', 1);
        $pageSize = (int)$req->get('pageSize', 10);
        $offset = ($page - 1) * $pageSize;

        $totalItems = $model->query("SELECT COUNT(*) as total FROM plano")->fetch(\PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalItems / $pageSize);

        $planos = $model->query("
            SELECT * FROM plano 
            LIMIT :limit OFFSET :offset", 
            ['limit' => $pageSize, 'offset' => $offset]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $metadata = [
            'page' => $page,
            'pageSize' => $pageSize,
            'totalItems' => (int)$totalItems,
            'totalPages' => (int)$totalPages,
        ];

        return $res->status(200)->body(['planos' => $planos, 'metadata' => $metadata]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        return $res->status(500)->body(['error' => 'Erro interno do servidor.']);
    }
});

$router->post("/atribuir-plano/{idConta}/{idPlano}", function(Request $req, Response $res) use ($model) {
    try {
        $idConta = (int)$req->get('idConta');
        $idPlano = (int)$req->get('idPlano');

        $conta = $model->select('conta', 'idConta = ?', [$idConta]);
        $plano = $model->select('plano', 'idPlano = ?', [$idPlano]);

        if (empty($conta) || empty($plano)) {
            return $res->status(404)->body(['error' => 'Conta ou plano não encontrados.']);
        }

        $contaAtualizada = $model->update('conta', ['planoId' => $idPlano], 'idConta = ?', [$idConta]);

        if ($contaAtualizada) {
            $contaAtualizada = $model->select('conta', 'idConta = ?', [$idConta]); // Obter conta atualizada com detalhes
            return $res->status(200)->body($contaAtualizada);
        } else {
            return $res->status(500)->body(['error' => 'Erro ao atualizar a conta.']);
        }
    } catch (\Exception $e) {
        error_log($e->getMessage());
        return $res->status(500)->body(['error' => 'Erro interno do servidor.']);
    }
});

$router->post("/remover-plano/{idConta}", function(Request $req, Response $res) use ($model) {
    try {
        $idConta = (int)$req->get('idConta');

        $conta = $model->select('conta', 'idConta = ?', [$idConta]);
        if (empty($conta)) {
            return $res->status(404)->body(['error' => 'Conta não encontrada.']);
        }

        $contaAtualizada = $model->update('conta', ['planoId' => null], 'idConta = ?', [$idConta]);

        $usuario = $model->select('usuario', 'idUsuario = ?', [$conta[0]['usuarioId']]);
        
        $contaAtualizada['usuario'] = $usuario;

        return $res->status(200)->body($contaAtualizada);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        return $res->status(500)->body(['error' => 'Erro interno do servidor.']);
    }
});

$router->put("/atualizar-plano/{id}", function(Request $req, Response $res) use ($model) {
    try {
        $id = (int)$req->get('id');
        $dadosAtualizados = json_decode($req->getBody(), true);

        $planoAtualizado = $model->update('plano', $dadosAtualizados, 'idPlano = :id', ['id' => $id]);

        if ($planoAtualizado) {
            $res->status(200)->body($planoAtualizado);
        } else {
            $res->status(404)->body(['error' => 'Plano não encontrado.']);
        }
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->body(['error' => 'Erro interno do servidor.']);
    }
});

