-- Inserindo tipos de contas iniciais
INSERT INTO TipoConta (tipoDaConta, prefixoConta, descricao)
VALUES 
    ('Administrador', 'ADM', 'Conta de administrador do sistema'),
    ('Usuário Regular', 'USR', 'Conta de usuário padrão');

-- Inserindo planos iniciais
INSERT INTO Plano (nomePlano, tipoDoPlano, taxaInscricao, taxaComissao, taxaManutencaoAnual)
VALUES 
    ('Plano Básico', 'Mensal', 100.00, 5.00, 50.00),
    ('Plano Premium', 'Anual', 500.00, 3.00, 100.00);

-- Inserindo usuários iniciais
INSERT INTO Usuarios (usuarioCriadorId, matrizId, nome, cpf, email, senha, statusConta, reputacao, razaoSocial, nomeFantasia, cnpj, mostrarNoSite, tipo, tipoDeMoeda, status, aceitaOrcamento, aceitaVoucher, tipoOperacao, permissoesDoUsuario)
VALUES 
    (NULL, NULL, 'Admin User', '123.456.789-00', 'admin@example.com', 'admin123', true, 5.0, 'Empresa Admin', 'Fantasia Admin', '12.345.678/0001-00', true, 'Administrador', 'BRL', true, true, true, 1, '[\"ALL\"]'),
    (1, NULL, 'Regular User', '987.654.321-00', 'user@example.com', 'user123', true, 3.0, 'Empresa Regular', 'Fantasia Regular', '98.765.432/0001-00', true, 'Usuário Regular', 'BRL', false, true, false, 2, '[\"READ\"]');

-- Inserindo contas iniciais associadas aos usuários
INSERT INTO Conta (taxaRepasseMatriz, limiteCredito, limiteUtilizado, saldoPermuta, limiteVendaMensal, limiteVendaTotal, limiteVendaEmpresa, diaFechamentoFatura, dataVencimentoFatura, numeroConta, nomeFranquia, tipoContaId, usuarioId, planoId, gerenteContaId)
VALUES 
    (10, 10000.00, 0.00, 5000.00, 3000.00, 20000.00, 15000.00, 30, 5, '0001-ADM-001', 'Franquia Admin', 1, 1, 1, NULL),
    (5, 5000.00, 0.00, 2000.00, 1500.00, 10000.00, 7000.00, 30, 10, '0002-USR-001', 'Franquia Regular', 2, 2, 2, 1);

-- Inserindo categorias e subcategorias para produtos ou serviços
INSERT INTO Categoria (nomeCategoria, tipoCategoria)
VALUES 
    ('Eletrônicos', 'Produto'),
    ('Serviços Gerais', 'Serviço');

INSERT INTO Subcategoria (nomeSubcategoria, categoriaId)
VALUES 
    ('Smartphones', 1),
    ('Consultoria', 2);

-- Inserindo uma oferta inicial
INSERT INTO Oferta (idFranquia, nomeFranquia, titulo, tipo, status, descricao, quantidade, valor, limiteCompra, vencimento, cidade, estado, retirada, obs, usuarioId, nomeUsuario, categoriaId, subcategoriaId, subcontaId)
VALUES 
    (1, 'Franquia Admin', 'Oferta de Teste', 'Produto', true, 'Oferta inicial para teste do sistema', 10, 999.99, 5, '2024-12-31 23:59:59', 'São Paulo', 'SP', 'Retirada em loja', 'Oferta promocional', 1, 'Admin User', 1, 1, NULL);
