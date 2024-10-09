<?php

use Core\Request;
use Core\Response;

global $router;
global $model;

$router->post('/criar_categoria', function (Request $req, Response $res) use ($model) {
    try {
        $body = $req->getAllParameters();
        $nomeCategoria = $body['nomeCategoria'];
        $tipoCategoria = $body['tipoCategoria'];

        $categoriaExistente = $model->find('categoria', [
            'where' => ['nomeCategoria' => $nomeCategoria],
        ]);

        if ($categoriaExistente) {
            return $res->json(['error' => 'Já existe uma categoria com o mesmo nome.'], 400);
        }

        $novaCategoria = $model->insert('categoria', [
            'nomeCategoria' => $nomeCategoria,
            'tipoCategoria' => $tipoCategoria,
        ]);

        return $res->json($novaCategoria, 201);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro ao cadastrar categoria.'], 500);
    }
});

$router->post('/criar-subcategoria/{categoryId}', function ($req, $res, $args) {
    try {
        $body = $req->getAllParameters();
        $nomeSubcategoria = $body['nomeSubcategoria'];
        $categoriaId = (int)$args['categoryId'];

        $novaSubcategoria = $this->prisma->subcategoria->create([
            'data' => [
                'nomeSubcategoria' => $nomeSubcategoria,
                'categoriaId' => $categoriaId,
            ],
        ]);

        return $res->json($novaSubcategoria, 201);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro ao cadastrar subcategoria.'], 500);
    }
});

$router->get('/listar-categorias', function (Request $req, Response $res) {
    try {
        $page = $req->getAllParameters()['page'] ?? 1;
        $pageSize = $req->getAllParameters()['pageSize'] ?? 10;
        $pageInt = (int)$page;
        $pageSizeInt = (int)$pageSize;
        $skip = ($pageInt - 1) * $pageSizeInt;

        $categorias = $this->prisma->categoria->findMany([
            'take' => $pageSizeInt,
            'skip' => $skip,
            'include' => ['subcategorias' => true],
        ]);

        $total = $this->prisma->categoria->count();

        $totalPages = ceil($total / $pageSizeInt);

        $meta = [
            'total' => $total,
            'page' => $pageInt,
            'pageSize' => $pageSizeInt,
            'totalPages' => $totalPages,
        ];

        return $res->json(['categorias' => $categorias, 'meta' => $meta], 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro ao obter categorias.'], 500);
    }
});

$router->put('/atualizar-categoria/{categoryId}', function ($req, $res, $args) {
    try {
        $categoryId = (int)$args['categoryId'];
        $data = json_decode($req->getBody(), true);
        $nomeCategoria = $data['nomeCategoria'] ?? null;
        $tipoCategoria = $data['tipoCategoria'] ?? null;

        $categoriaExistente = $this->prisma->categoria->findUnique([
            'where' => ['idCategoria' => $categoryId],
        ]);

        if (!$categoriaExistente) {
            return $res->json(['error' => 'Categoria não encontrada.'], 404);
        }

        $categoriaAtualizada = $this->prisma->categoria->update([
            'where' => ['idCategoria' => $categoryId],
            'data' => [
                'nomeCategoria' => $nomeCategoria,
                'tipoCategoria' => $tipoCategoria,
            ],
        ]);

        return $res->json($categoriaAtualizada, 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro ao atualizar categoria.'], 500);
    }
});

$router->put('/editar-subcategoria/{subcategoryId}', function ($req, $res, $args) {
    try {
        $subcategoryId = (int)$args['subcategoryId'];
        $data = json_decode($req->getBody(), true);
        $nomeSubcategoria = $data['nomeSubcategoria'] ?? null;

        $subcategoriaExistente = $this->prisma->subcategoria->findUnique([
            'where' => ['idSubcategoria' => $subcategoryId],
        ]);

        if (!$subcategoriaExistente) {
            return $res->json(['error' => 'Subcategoria não encontrada.'], 404);
        }

        $subcategoriaAtualizada = $this->prisma->subcategoria->update([
            'where' => ['idSubcategoria' => $subcategoryId],
            'data' => [
                'nomeSubcategoria' => $nomeSubcategoria,
            ],
        ]);

        return $res->json($subcategoriaAtualizada, 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro ao editar subcategoria.'], 500);
    }
});

$router->delete('/deletar-subcategoria/{subcategoryId}', function ($req, $res, $args) {
    try {
        $subcategoryId = (int)$args['subcategoryId'];

        $subcategoriaExistente = $this->prisma->subcategoria->findUnique([
            'where' => ['idSubcategoria' => $subcategoryId],
        ]);

        if (!$subcategoriaExistente) {
            return $res->json(['error' => 'Subcategoria não encontrada.'], 404);
        }

        $subcategoriaDeletada = $this->prisma->subcategoria->delete([
            'where' => ['idSubcategoria' => $subcategoryId],
        ]);

        return $res->json([
            'message' => 'Subcategoria deletada com sucesso',
            'subcategoriaDeletada' => $subcategoriaDeletada,
        ], 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro ao deletar subcategoria.'], 500);
    }
});

$router->delete('/deletar-categoria/{categoryId}', function ($req, $res, $args) {
    try {
        $categoryId = (int)$args['categoryId'];

        $categoriaExistente = $this->prisma->categoria->findUnique([
            'where' => ['idCategoria' => $categoryId],
            'include' => ['subcategorias' => true],
        ]);

        if (!$categoriaExistente) {
            return $res->json(['error' => 'Categoria não encontrada.'], 404);
        }

        if (count($categoriaExistente->subcategorias) > 0) {
            return $res->json([
                'error' => 'Não é possível deletar a categoria pois existem subcategorias relacionadas.',
            ], 400);
        }

        $categoriaDeletada = $this->prisma->categoria->delete([
            'where' => ['idCategoria' => $categoryId],
        ]);

        return $res->json([
            'message' => 'Categoria deletada com sucesso.',
            'categoriaDeletada' => $categoriaDeletada,
        ], 200);
    } catch (Exception $error) {
        error_log($error->getMessage());
        return $res->json(['error' => 'Erro ao deletar categoria.'], 500);
    }
});
