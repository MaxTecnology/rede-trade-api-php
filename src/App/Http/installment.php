<?php

use Core\Request;
use Core\Response;


global $router;
global $model;

$router->post("/criar-parcelamento/{transacaoId}", function(Request $req, Response $res) use ($model) {
    try {
        $transacaoId = (int)$req->get('transacaoId');
        $numeroParcela = $req->get('numeroParcela');
        $valorParcela = $req->get('valorParcela');
        $comissaoParcela = $req->get('comissaoParcela');

        $transacaoExistente = $model->select('transacao', 'idTransacao = :id', ['id' => $transacaoId]);

        if (empty($transacaoExistente)) {
            $res->status(404)->body(['error' => 'Transação não encontrada.']);
            return;
        }

        $novoParcelamento = $model->insert('parcelamento', [
            'numeroParcela' => $numeroParcela,
            'valorParcela' => $valorParcela,
            'comissaoParcela' => $comissaoParcela,
            'transacaoId' => $transacaoId
        ]);

        if ($novoParcelamento) {
            $res->status(201)->body($model->select('parcelamento', 'transacaoId = :id', ['id' => $transacaoId]));
        } else {
            throw new \Exception('Erro ao criar parcelamento.');
        }
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->body(['error' => 'Erro ao criar parcelamento.']);
    }
});

$router->get("/listar-parcelamentos", function(Request $req, Response $res) use ($model) {
    try {
        $pagina = (int)$req->get('pagina', 1);
        $itensPorPagina = (int)$req->get('itensPorPagina', 10);
        $offset = ($pagina - 1) * $itensPorPagina;

        $parcelamentos = $model->query("
            SELECT * FROM parcelamento 
            LIMIT :limit OFFSET :offset", 
            ['limit' => $itensPorPagina, 'offset' => $offset]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $totalParcelamentos = $model->query("SELECT COUNT(*) as total FROM parcelamento")->fetch(\PDO::FETCH_ASSOC)['total'];

        $meta = [
            'pagina' => $pagina,
            'itensPorPagina' => $itensPorPagina,
            'totalItens' => (int)$totalParcelamentos,
            'totalPaginas' => ceil($totalParcelamentos / $itensPorPagina),
        ];

        $res->status(200)->body(['parcelamentos' => $parcelamentos, 'meta' => $meta]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->body(['error' => 'Erro ao listar parcelamentos.']);
    }
});

$router->put("/editar-parcelamento/{id}", function(Request $req, Response $res) use ($model) {
    try {
        $id = (int)$req->get('id');
        $numeroParcela = $req->get('numeroParcela');
        $valorParcela = $req->get('valorParcela');
        $comissaoParcela = $req->get('comissaoParcela');

        $parcelamentoAtualizado = $model->update('parcelamento', [
            'numeroParcela' => $numeroParcela,
            'valorParcela' => $valorParcela,
            'comissaoParcela' => $comissaoParcela
        ], 'idParcelamento = :id', ['id' => $id]);

        if ($parcelamentoAtualizado) {
            $parcelamento = $model->select('parcelamento', 'idParcelamento = :id', ['id' => $id])[0];
            $transacao = $model->select('transacao', 'idTransacao = :transacaoId', ['transacaoId' => $parcelamento['transacaoId']])[0];
            $parcelamento['transacao'] = $transacao;

            $res->status(200)->body($parcelamento);
        } else {
            $res->status(404)->body(['error' => 'Parcelamento não encontrado.']);
        }
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->body(['error' => 'Erro ao editar parcelamento.']);
    }
});

$router->delete("/deletar-parcelamento/{id}", function(Request $req, Response $res) use ($model) {
    try {
        $id = (int)$req->get('id');

        $model->delete('parcelamento', 'idParcelamento = :id', ['id' => $id]);

        $res->status(204)->body([]);
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->body(['error' => 'Erro ao deletar parcelamento.']);
    }
});
