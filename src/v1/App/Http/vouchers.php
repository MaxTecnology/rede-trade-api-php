<?php

use Core\Request;
use Core\Response;

global $router;
global $model;


$router->get("/vouchers-do-usuario/{idUsuario}", function (Request $req, Response $res) use ($model)  {
    try {
        $idUsuario = (int)$req->get('idUsuario');

        $transacoesDoUsuario = $model->select('transacao', 'compradorId = :idUsuario OR vendedorId = :idUsuario', [
            'idUsuario' => $idUsuario,
        ]);

        $transacoesDoUsuarioComVoucher = array_filter($transacoesDoUsuario, function ($transacao) {
            return !empty($transacao['voucher']);
        });

        $transacoesDoUsuarioComSelect = array_map(function ($transacao) use ($model) {
            return [
                'idTransacao' => $transacao['idTransacao'],
                'codigo' => $transacao['codigo'],
                'createdAt' => $transacao['createdAt'],
                'valorRt' => $transacao['valorRt'],
                'compradorId' => $transacao['compradorId'],
                'vendedorId' => $transacao['vendedorId'],
                'voucher' => $transacao['voucher'],
                'comprador' => $model->select('usuario', 'idUsuario = :id', ['id' => $transacao['compradorId']]),
                'vendedor' => $model->select('usuario', 'idUsuario = :id', ['id' => $transacao['vendedorId']]),
                'descricao' => $transacao['descricao'],
                'status' => $transacao['status'],
            ];
        }, $transacoesDoUsuarioComVoucher);

        $transacoesComprador = array_filter($transacoesDoUsuarioComSelect, function ($transacao) use ($idUsuario) {
            return $transacao['compradorId'] === $idUsuario;
        });
        $transacoesVendedor = array_filter($transacoesDoUsuarioComSelect, function ($transacao) use ($idUsuario) {
            return $transacao['vendedorId'] === $idUsuario;
        });

        $res->status(200)->body([
            'transacoesComprador' => array_map(function ($transacao) {
                return ['transacao' => $transacao];
            }, $transacoesComprador),
            'transacoesVendedor' => array_map(function ($transacao) {
                return ['transacao' => $transacao];
            }, $transacoesVendedor),
        ]);
    } catch (Exception $error) {
        error_log($error);
        $res->status(500)->body(['error' => 'Erro ao buscar os vouchers do usuário.']);
    }
});

$router->get(
    "/transacoes-com-voucher",
    function (Request $req, Response $res) use ($model)  {
        try {
            $transacoesComVoucher = $model->select('transacao', 'voucher IS NOT NULL');

            $transacoesComVoucherComSelect = array_map(function ($transacao) {
                return [
                    'idTransacao' => $transacao['idTransacao'],
                    'status' => $transacao['status'],
                    'codigo' => $transacao['codigo'],
                    'createdAt' => $transacao['createdAt'],
                    'valorRt' => $transacao['valorRt'],
                    'descricao' => $transacao['descricao'],
                    'voucher' => $transacao['voucher'],
                    'comprador' => $transacao['comprador'],
                    'vendedor' => $transacao['vendedor'],
                ];
            }, $transacoesComVoucher);

            $res->status(200);
            $res->body(['transacoesComVoucher' => $transacoesComVoucherComSelect]);
        } catch (Exception $error) {
            error_log($error);
            $res->status(500);
            $res->body(['error' => 'Erro ao buscar as transações com voucher.']);
        }
    }
);

$router->get(
    "/transacoes-com-voucher-por-unidade/{idFranquia}",
    function (Request $req, Response $res) use ($model)  {
        try {
            $idFranquia = (int)$req->get('idFranquia');

            $usuariosAssociados = $model->select('usuarios', 'usuarioCriadorId = :idFranquia AND conta_tipoDaConta_tipoDaConta = :tipo', [
                'idFranquia' => $idFranquia,
                'tipo' => 'Associado'
            ]);

            $idsUsuariosAssociados = array_column($usuariosAssociados, 'idUsuario');

            $transacoesPorUnidade = $model->select('transacao', 'compradorId IN (' . implode(',', array_fill(0, count($idsUsuariosAssociados), '?')) . ') OR vendedorId IN (' . implode(',', array_fill(0, count($idsUsuariosAssociados), '?')) . ') AND voucher IS NOT NULL', array_merge($idsUsuariosAssociados, $idsUsuariosAssociados));

            $transacoesCompradorComSelect = array_filter($transacoesPorUnidade, function ($transacao) use ($idsUsuariosAssociados) {
                return in_array($transacao['compradorId'], $idsUsuariosAssociados);
            });

            $transacoesCompradorComSelect = array_map(function ($transacao) {
                return [
                    'idTransacao' => $transacao['idTransacao'],
                    'status' => $transacao['status'],
                    'codigo' => $transacao['codigo'],
                    'createdAt' => $transacao['createdAt'],
                    'valorRt' => $transacao['valorRt'],
                    'descricao' => $transacao['descricao'],
                    'voucher' => $transacao['voucher'],
                    'comprador' => $transacao['comprador'],
                    'vendedor' => $transacao['vendedor'],
                ];
            }, $transacoesCompradorComSelect);

            $transacoesVendedorComSelect = array_filter($transacoesPorUnidade, function ($transacao) use ($idsUsuariosAssociados) {
                return in_array($transacao['vendedorId'], $idsUsuariosAssociados);
            });

            $transacoesVendedorComSelect = array_map(function ($transacao) {
                return [
                    'idTransacao' => $transacao['idTransacao'],
                    'codigo' => $transacao['codigo'],
                    'createdAt' => $transacao['createdAt'],
                    'valorRt' => $transacao['valorRt'],
                    'descricao' => $transacao['descricao'],
                    'voucher' => $transacao['voucher'],
                    'comprador' => $transacao['comprador'],
                    'vendedor' => $transacao['vendedor'],
                ];
            }, $transacoesVendedorComSelect);

            $res->status(200);
            $res->body([
                'transacoesComprador' => $transacoesCompradorComSelect,
                'transacoesVendedor' => $transacoesVendedorComSelect,
            ]);
        } catch (\Exception $error) {
            error_log($error);
            $res->status(500);
            $res->body([
                'error' => 'Erro ao buscar as transações com voucher por unidade.',
            ]);
        }
    }
);
