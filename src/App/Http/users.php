<?php

use Core\Jwt;
use Core\Request;
use Core\Response;

global $router;
global $model;

$router->post("/criar-usuario", function(Request $req, Response $res) use ($model) {
    try {
        $nome = $req->get('nome');
        $email = $req->get('email');
        $senha = password_hash($req->get('senha'), PASSWORD_DEFAULT);

        $usuarioExistente = $model->select('usuario', 'email = :email', ['email' => $email]);

        if (!empty($usuarioExistente)) {
            $res->status(400)->body(['error' => 'Usuário já existe.']);
            return;
        }

        $novoUsuario = $model->insert('usuario', [
            'nome' => $nome,
            'email' => $email,
            'senha' => $senha
        ]);

        if ($novoUsuario) {
            $res->status(201)->body(['message' => 'Usuário criado com sucesso.']);
        } else {
            throw new \Exception('Erro ao criar usuário.');
        }
    } catch (\Exception $e) {
        error_log($e->getMessage());
        $res->status(500)->body(['error' => 'Erro ao criar usuário.']);
    }
});

$router->get('/listar-usuarios', function (Request $req, Response $res) use ($model) {
    try {
        $page = $req->get('page') ?? 1;
        $pageSize = $req->get('pageSize') ?? 60;
        $pageNumber = (int) $page;
        $pageSizeNumber = (int) $pageSize;

        $skip = ($pageNumber - 1) * $pageSizeNumber;

        $usuarios = $model->paginate('usuarios', $pageNumber, $pageSizeNumber);

        $usuariosSemSenha = array_map(function ($usuario) {
            unset($usuario['senha']);
            return $usuario;
        }, $usuarios);

        return $res->status(200)->body([
            'data' => $usuariosSemSenha,
            'meta' => [
                'page' => $pageNumber,
                'pageSize' => $pageSizeNumber,
                'total' => count($usuarios), 
            ],
        ]);
    } catch (\Exception $error) {
        error_log($error);
        return $res->status(500)->body(['error' => 'Erro interno do servidor.']);
    }
});

$router->get('/buscar-usuario/{id}', function (Request $req, Response $res) use ($model) {
    try {
        $id = $req->get('id');

        $usuario = $model->select('usuarios', 'idUsuario = :id', ['id' => (int)$id]);

        if (empty($usuario)) {
            $res->status(404);
            return $res->body(['error' => 'Usuário não encontrado.']);
        }

        $usuario = $usuario[0]; 
        unset($usuario['senha']);

        $res->status(200);
        return $res->body($usuario);
    } catch (Exception $error) {
        error_log($error);
        $res->status(500);
        return $res->body(['error' => 'Erro interno do servidor.']);
    }
});

$router->put('/atualizar-usuario/{id}', 'verifyToken', function (Request $req, Response $res) use ($model) {
    try {
        $id = $req->get('id');
        $dadosAtualizados = json_decode($req->getBody(), true);

        $usuarioAtualizado = $model->update('usuarios', $dadosAtualizados, 'idUsuario = ?', [$id]);

        if ($usuarioAtualizado) {
            $usuarioSemSenha = $dadosAtualizados;
            unset($usuarioSemSenha['senha']);

            return $res->status(200)->body($usuarioSemSenha);
        } else {
            return $res->status(404)->body(['error' => 'Usuário não encontrado.']);
        }
    } catch (Exception $error) {
        error_log($error);
        return $res->status(500)->body(['error' => 'Erro interno do servidor.']);
    }
});

$router->delete('/deletar-usuario/{id}', 'verifyToken', 'checkBlocked', function (Request $req, Response $res) use ($model){
    try {
        $id = $req->get('id');

        $usuarioExistente = $model->select('usuarios', 'idUsuario = ?', [$id]);

        if (empty($usuarioExistente)) {
            $res->status(404);
            $res->body(['error' => 'Usuário não encontrado.']);
            return;
        }

        $model->query('DELETE FROM usuarios WHERE idUsuario = ?', [$id]);

        $res->status(204);
        $res->body([]);
    } catch (Exception $error) {
        error_log($error->getMessage());
        $res->status(500);
        $res->body(['error' => 'Erro interno do servidor.']);
    }
});

$router->post('/adicionar-permissao/{idUsuario}', 'verifyToken', 'checkBlocked', function (Request $req, Response $res) use ($model) {
    try {
        $idUsuario = $req->get('idUsuario');
        $permissoes = json_decode($req->getBody(), true)['permissoes'];

        $usuarioExists = $model->select('usuarios', 'idUsuario = ?', [$idUsuario]);

        if (empty($usuarioExists)) {
            return $res->status(404)->body(['error' => 'Usuário não encontrado.']);
        }

        $usuario = $model->update('usuarios', [
            'permissoesDoUsuario' => json_encode($permissoes),
        ], 'idUsuario = ?', [$idUsuario]);

        if ($usuario) {
            return $res->status(200)->body($usuario);
        } else {
            return $res->status(500)->body(['error' => 'Erro ao adicionar permissões ao Usuário.']);
        }
    } catch (Exception $error) {
        error_log($error);
        return $res->status(500)->body(['error' => 'Erro ao adicionar permissões ao Usuário.']);
    }
});

$router->delete("/remover-permissao/{idUsuario}", 'verifyToken', 'checkBlocked', function(Request $req, Response $res) use ($model) {
    try {
        $idUsuario = $req->get('idUsuario');
        $permissoes = json_decode($req->getBody(), true)['permissoes'];

        $usuarioExists = $model->select('usuarios', 'idUsuario = ?', [$idUsuario]);

        if (empty($usuarioExists)) {
            return $res->status(404)->body(['error' => "Usuário não encontrado."]);
        }

        $usuario = $model->select('usuarios', 'idUsuario = ?', [$idUsuario]);

        if (empty($usuario)) {
            return $res->status(404)->body(['error' => "Usuário não encontrado."]);
        }

        $novasPermissoes = array_filter(json_decode($usuario[0]['permissoesDoUsuario'], true) ?? [], function($permissao) use ($permissoes) {
            return !in_array($permissao, $permissoes);
        });

        $model->update('usuarios', ['permissoesDoUsuario' => json_encode(array_values($novasPermissoes))], 'idUsuario = ?', [$idUsuario]);

        $usuarioSemSenha = $usuario[0];
        unset($usuarioSemSenha['senha']);

        return $res->status(200)->body(['message' => "Permissões removidas com sucesso.", 'usuarioSemSenha' => $usuarioSemSenha]);
    } catch (Exception $error) {
        error_log($error);
        return $res->status(500)->body(['error' => "Erro ao remover permissões do Usuário."]);
    }
});

$router->get('/listar-permissoes/{idUsuario}', function (Request $req, Response $res) use ($model) {
    try {
        $idUsuario = $req->get('idUsuario');

        $usuarioExists = $model->select('usuarios', 'idUsuario = ?', [$idUsuario]);

        if (empty($usuarioExists)) {
            return $res->status(404)->body(['error' => "Usuário não encontrado."]);
        }

        $usuario = $usuarioExists[0];

        $permissoes = json_decode($usuario['permissoesDoUsuario'] ?? '[]', true);

        return $res->status(200)->body(['permissoes' => $permissoes]);
    } catch (Exception $error) {
        error_log($error);
        return $res->status(500)->body(['error' => "Erro ao obter as permissões do Usuário."]);
    }
});

$router->post("/solicitar-redefinicao-senha-usuario", function (Request $req, Response $res) use ($model) {
    try {
        $body = json_decode($req->getBody(), true);
        $email = $body['email'] ?? null;
        $cpf = $body['cpf'] ?? null;

        if ($email) {
            $usuario = $model->select('usuarios', 'email = :email', ['email' => $email]);
        } elseif ($cpf) {
            $usuario = $model->select('usuarios', 'cpf = :cpf', ['cpf' => $cpf]);
        } else {
            $res->status(400);
            return $res->body(['error' => "É necessário fornecer um email ou CPF."]);
        }

        if (empty($usuario)) {
            $res->status(404);
            return $res->body(['error' => "Usuário não encontrado."]);
        }

        $tokenResetSenha = gerarToken(); // Assumindo que a função gerarToken está definida

        $usuarioAtualizado = $model->update('usuarios', [
            'tokenResetSenha' => $tokenResetSenha !== null ? $tokenResetSenha : "",
        ], 'idUsuario = :id', ['id' => $usuario[0]['idUsuario']]);

        $resetLink = "https://app.redetrade.com.br/resetPassword?id={$usuario[0]['idUsuario']}&token={$tokenResetSenha}";

        $emailDestinatario = $usuario[0]['email'];
        $assuntoEmail = "Redefinição de Senha - REDE TRADE";
        $corpoEmail = "Olá,\n\nVocê solicitou a redefinição de senha para sua conta na REDE TRADE. Por favor, clique no link a seguir para redefinir sua senha:\n\n{$resetLink}\n\nSe você não solicitou essa redefinição, ignore este e-mail.\n\nAtenciosamente,\nREDE TRADE";
        enviarEmail($emailDestinatario, $assuntoEmail, $corpoEmail); // Assumindo que a função enviarEmail está definida

        return $res->status(200)->body(['message' => "Um link para redefinição de senha foi enviado para o seu email."]);
    } catch (Exception $error) {
        error_log($error);
        $res->status(500);
        return $res->body(['error' => "Erro interno do servidor."]);
    }
});

$router->post("/redefinir-senha-usuario/{idUsuario}", function (Request $req, Response $res) use ($model) {
    try {
        $idUsuario = $req->get('idUsuario');
        $body = json_decode($req->getBody(), true);
        $novaSenha = $body['novaSenha'];
        $token = $body['token'];
        
        $usuario = $model->select('usuarios', 'idUsuario = :id AND tokenResetSenha = :token', [
            ':id' => intval($idUsuario),
            ':token' => $token,
        ]);

        if (empty($usuario)) {
            return $res->status(400)->body(['error' => "Token de redefinição de senha inválido."]);
        }

        $senhaCriptografada = password_hash($novaSenha, PASSWORD_BCRYPT);

        $model->update('usuarios', [
            'senha' => $senhaCriptografada,
            'tokenResetSenha' => null,
        ], 'idUsuario = :id', [':id' => intval($idUsuario)]);

        unset($usuario[0]['senha']);

        return $res->status(200)->body(['message' => "Senha atualizada com sucesso", 'usuarioSemSenha' => $usuario[0]]);
    } catch (Exception $error) {
        error_log($error);
        return $res->status(500)->body(['error' => "Erro interno do servidor."]);
    }
});

$router->post("/login", function (Request $req, Response $res) use ($model) {
    try {
        $body = json_decode($req->getBody(), true);
        $login = $body['login'];
        $senha = $body['senha'];

        $isEmail = strpos($login, '@') !== false;

        
        $usuario = $isEmail
            ? $model->select('usuarios', 'email = :email', ['email' => $login])
            : $model->select('usuarios', 'cpf = :cpf', ['cpf' => $login]);

        $subconta = $isEmail
            ? $model->select('subContas', 'email = :email', ['email' => $login])
            : $model->select('subContas', 'cpf = :cpf', ['cpf' => $login]);

        $user = !empty($usuario) ? $usuario[0] : (!empty($subconta) ? $subconta[0] : null);
        $userId = $user['idUsuario'] ?? $user['idSubContas'] ?? null;

        if (!$user) {
            return $res->status(404)->body(['error' => "Usuário não encontrado."]);
        }

        if (!password_verify($senha, $user['senha'])) {
            return $res->status(401)->body(['error' => "Credenciais inválidas."]);
        }

        $secret = getenv('SECRET') ?: '';
        $jwt =  new Jwt($secret);
        $token = $jwt->createToken(['userId' => $userId], $secret, 'HS256', 3600);

        unset($user['senha'], $user['tokenResetSenha']);

        return $res->status(200)->body(['token' => $token, 'user' => $user]);
    } catch (Exception $error) {
        error_log($error);
        return $res->status(401)->body(['error' => "Erro ao fazer login."]);
    }
});

$router->get('/user-info', 'verifyToken', function (Request $req, Response $res) use ($model) {
    try {
        $userId = $req->getHeader('userId'); // Assume que o ID do usuário é passado no header

        $user = $model->select('usuarios', 'idUsuario = :idUsuario', ['idUsuario' => $userId]);

        if (empty($user)) {
            return $res->status(404)->body(['error' => 'Usuário não encontrado.']);
        }

        $user = $user[0]; 

        unset($user['senha'], $user['tokenResetSenha']);

        $res->status(200)->body($user);
    } catch (Exception $error) {
        error_log($error);
        $res->status(500)->body(['error' => 'Erro ao obter informações do usuário.']);
    }
});


$router->post("/listar-tipo-usuarios", /*verifyToken, checkBlocked,*/ function (Request $req, Response $res) use ($model) {
    try {
        $page = $req->get('page') ?? 1;
        $pageSize = $req->get('pageSize') ?? 100;
        $pageNumber = (int) $page;
        $pageSizeNumber = (int) $pageSize;

        $tipoConta = $req->getBody()['tipoConta'] ?? null;

        if (!$tipoConta || !is_array($tipoConta) || count($tipoConta) === 0) {
            return $res->status(400)->body([
                'error' => 'O tipo de conta é obrigatório e deve ser um array não vazio no corpo da solicitação.'
            ]);
        }

        $skip = ($pageNumber - 1) * $pageSizeNumber;

        $usuarios = $model->select('usuarios', 'conta.tipoDaConta IN (:tipoConta)', [':tipoConta' => $tipoConta], $skip, $pageSizeNumber);

        $usuariosSemSenha = array_map(function ($usuario) {
            unset($usuario['senha']);
            return $usuario;
        }, $usuarios);

        return $res->status(200)->body([
            'data' => $usuariosSemSenha,
            'meta' => [
                'page' => $pageNumber,
                'pageSize' => $pageSizeNumber,
                'total' => count($usuarios), // Total de usuários sem a paginação
            ],
        ]);
    } catch (Exception $error) {
        error_log($error);
        return $res->status(500)->body(['error' => 'Erro interno do servidor.']);
    }
});

$router->get("/listar-ofertas/{idUsuario}", function(Request $req, Response $res) use ($model) {
    try {
        $idUsuario = $req->get('idUsuario');

        if (!filter_var($idUsuario, FILTER_VALIDATE_INT)) {
            $res->status(400);
            return $res->body(["error" => "ID do usuário inválido."]);
        }

        $usuario = $model->select('usuarios', 'idUsuario = ?', [$idUsuario]);

        if (empty($usuario)) {
            $res->status(404);
            return $res->body(["error" => "Usuário não encontrado."]);
        }

        return $res->status(200)->body(["data" => $usuario[0]['ofertas']]);
    } catch (Exception $error) {
        error_log($error);
        $res->status(500);
        return $res->body(["error" => "Erro interno do servidor."]);
    }
});

$router->get('/buscar-tipo-de-conta/{userId}', function (Request $req, Response $res) use ($model) {
   
        try {
            $userId = intval($req->get('userId')); // Certifique-se de usar o parâmetro correto
    
            $usuarioComTipoDaConta = $model->select('usuarios', 'idUsuario = :id', ['id' => $userId]);
    
            if (empty($usuarioComTipoDaConta)) {
                return $res->status(404)->body(['error' => 'Usuário não encontrado']);
            }
    
            $tipoDeConta = $usuarioComTipoDaConta[0]['conta']['tipoDaConta']['tipoDaConta'] ?? null;
    
            if (!$tipoDeConta) {
                return $res->status(404)->body(['error' => 'Tipo de conta não encontrado para este usuário']);
            }
    
            $res->body(['tipoDeConta' => $tipoDeConta]);
        } catch (Exception $error) {
            error_log("Erro ao buscar tipo de conta do usuário: " . $error->getMessage());
            $res->status(500)->body(['error' => 'Erro interno do servidor']);
        }
    
    
});

$router->get('/buscar-franquias/{matrizId}', function (Request $req, Response $res) use ($model) {

    try {
        $matrizId = $req->get('matrizId', 'ID da matriz não fornecido.');
        
        $franquias = $model->select('usuarios', 'usuarioCriadorId = :usuarioCriadorId AND conta.tipoDaConta.tipoDaConta IN ("Franquia", "Franquia Master")', [
            'usuarioCriadorId' => (int)$matrizId
        ]);

        return $res->body($franquias);
    } catch (Exception $error) {
        error_log($error);
        return $res->status(500)->body(['error' => 'Erro interno do servidor.']);
    }


});

$router->get('/usuarios-criados/{usuarioCriadorId}', function (Request $req, Response $res) use ($model) {
    
        try {
            $usuarioCriadorId = $req->get('usuarioCriadorId');
    
            $usuariosAssociados = $model->select('usuarios', 'usuarioCriadorId = :usuarioCriadorId AND conta.tipoDaConta.tipoDaConta = :tipoDaConta', [
                'usuarioCriadorId' => (int)$usuarioCriadorId,
                'tipoDaConta' => 'Associado',
            ]);
    
            if (empty($usuariosAssociados)) {
                $res->status(404);
                return $res->body(['error' => 'Não foi possível encontrar os associados.']);
            }
    
       
            $usuariosAssociadosSemSenha = array_map(function($usuario) {
                unset($usuario['senha'], $usuario['tokenResetSenha']);
                return $usuario;
            }, $usuariosAssociados);
    
            $res->status(200);
            return $res->body($usuariosAssociadosSemSenha);
        } catch (Exception $error) {
            error_log($error);
            $res->status(500);
            return $res->body(['error' => 'Erro interno do servidor.']);
        }
    
    
});

$router->get('/buscar-usuario-params', function (Request $req, Response $res) use ($model) {

    try {
        $queryParams = $req->getAllParameters();
        $filter = [];
        $page = intval($queryParams['page'] ?? 1);
        $pageSize = intval($queryParams['pageSize'] ?? 10);
        $skip = ($page - 1) * $pageSize;

        if (isset($queryParams['nome'])) {
            $filter['nome'] = $queryParams['nome'];
        }
        if (isset($queryParams['nomeFantasia'])) {
            $filter['nomeFantasia'] = $queryParams['nomeFantasia'];
        }
        if (isset($queryParams['razaoSocial'])) {
            $filter['razaoSocial'] = $queryParams['razaoSocial'];
        }
        if (isset($queryParams['nomeContato'])) {
            $filter['nomeContato'] = $queryParams['nomeContato'];
        }
        if (isset($queryParams['estado'])) {
            $filter['estado'] = $queryParams['estado'];
        }
        if (isset($queryParams['cidade'])) {
            $filter['cidade'] = $queryParams['cidade'];
        }
        if (isset($queryParams['usuarioCriadorId'])) {
            $filter['usuarioCriadorId'] = intval($queryParams['usuarioCriadorId']);
        }

        if (isset($queryParams['tipoDaConta'])) {
            $tipoConta = $model->select('tipo_conta', 'tipoDaConta = ?', [$queryParams['tipoDaConta']]);
            if (!empty($tipoConta)) {
                $filter['conta'] = ['tipoDaConta' => $tipoConta[0]['tipoDaConta']];
            }
        } else {
            $tipoContaAssociado = $model->select('tipo_conta', 'tipoDaConta = ?', ['Associado']);
            if (!empty($tipoContaAssociado)) {
                $filter['conta'] = ['tipoDaConta' => $tipoContaAssociado[0]['tipoDaConta']];
            }
        }

        $users = $model->paginate('usuarios', $page, $pageSize);
        $totalUsers = $model->query('SELECT COUNT(*) as total FROM usuarios WHERE ?', [$filter])->fetchColumn();
        
        $totalPages = ceil($totalUsers / $pageSize);
        $nextPage = null;

        if ($page < $totalPages) {
            $nextPageNumber = $page + 1;
            $nextPage = sprintf("%s://%s%s?page=%d&pageSize=%d", 
                $_SERVER['REQUEST_SCHEME'], 
                $_SERVER['HTTP_HOST'], 
                $_SERVER['REQUEST_URI'], 
                $nextPageNumber, 
                $pageSize);
        }

        $res->body([
            'data' => $users,
            'meta' => [
                'totalResults' => $totalUsers,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'pageSize' => $pageSize,
                'nextPage' => $nextPage,
            ],
        ]);
    } catch (Exception $error) {
        error_log("Erro ao pesquisar usuários: " . $error->getMessage());
        $res->status(500);
        $res->body(['error' => 'Erro ao pesquisar usuários']);
    }


});
