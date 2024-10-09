<?php

use Core\Request;
use Core\Response;


global $router;
global $model;

$router->post("/criar-oferta", function(Request $req, Response $res) use ($model) {
    try {
        $data = $req->getAllParameters();

        $ofertaExistente = $model->select('oferta', 'titulo = :titulo AND valor = :valor', [
            'titulo' => $data['titulo'],
            'valor' => $data['valor']
        ]);

        if (!empty($ofertaExistente)) {
            return $res->status(400)->json(['error' => 'Já existe uma oferta com o mesmo nome e valor.']);
        }

        $novaOferta = $model->insert('oferta', $data);

        return $res->status(201)->json($novaOferta);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        return $res->status(500)->json(['error' => 'Erro ao cadastrar oferta.']);
    }
});

$router->get('/listar-ofertas', function(Request $req, Response $res) use ($model) {
    try {
        $queryParams = $req->getAllParameters();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 10;

        $ofertas = $model->paginate('oferta', $page, $limit);

        $totalOfertas = count($model->select('oferta'));

        $totalPages = ceil($totalOfertas / $limit);

        $meta = [
            'totalOfertas' => $totalOfertas,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ];

        return $res->status(200)->json(['ofertas' => $ofertas, 'meta' => $meta]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        return $res->status(500)->json(['error' => 'Erro ao listar ofertas.']);
    }
});

$router->put('/atualizar-oferta/{ofertaId}', function($req, $res, $args) use ($model) {
    try {
        $ofertaId = (int)$args['ofertaId'];
        $data = $req->getAllParameters();

        $ofertaAtualizada = $model->update('oferta', $data, 'idOferta = ?', [$ofertaId]);

        return $res->status(200)->json($ofertaAtualizada);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        return $res->status(500)->json(['error' => 'Erro ao atualizar oferta.']);
    }
});

$router->delete('/deletar-oferta/{ofertaId}', function($req, $res, $args) use ($model) {
    try {
        $ofertaId = (int)$args['ofertaId'];

        $transacoesRelacionadas = $model->select('transacao', 'ofertaId = ?', [$ofertaId]);

        if (count($transacoesRelacionadas) > 0) {
            return $res->status(400)->json(['error' => 'Não é possível excluir a oferta devido a transações relacionadas.']);
        }

        $ofertaDeletada = $model->delete('oferta', 'idOferta = ?', [$ofertaId]);

        return $res->status(200)->json(['message' => 'Oferta deletada!', 'ofertaDeletada' => $ofertaDeletada]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        return $res->status(500)->json(['error' => 'Erro ao deletar oferta.']);
    }
});

$router->get('/buscar-oferta/{ofertaId}', function($req, $res, $args) use ($model) {
    try {
        $ofertaId = (int)$args['ofertaId'];

        $oferta = $model->select('oferta', 'idOferta = ?', [$ofertaId], [
            'categoria',
            'usuario' => ['idUsuario', 'nome', 'email', 'telefone'],
            'subconta' => ['idSubContas', 'nome', 'email', 'telefone'],
            'transacoes'
        ]);

        if (empty($oferta)) {
            return $res->status(404)->json(['error' => 'Oferta não encontrada.']);
        }

        return $res->status(200)->json($oferta);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        return $res->status(500)->json(['error' => 'Erro ao buscar oferta.']);
    }
});

$router->get('/buscar-oferta/{ofertaId}', function($req, $res, $args) use ($model) {
    try {
        $ofertaId = (int)$args['ofertaId'];

        $oferta = $model->select('oferta', 'idOferta = ?', [$ofertaId], [
            'categoria',
            'usuario' => ['idUsuario', 'nome', 'email', 'telefone'],
            'subconta' => ['idSubContas', 'nome', 'email', 'telefone'],
            'transacoes'
        ]);

        if (empty($oferta)) {
            return $res->status(404)->json(['error' => 'Oferta não encontrada.']);
        }

        return $res->status(200)->json($oferta);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        return $res->status(500)->json(['error' => 'Erro ao buscar oferta.']);
    }
});
