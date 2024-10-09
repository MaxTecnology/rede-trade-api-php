CREATE TABLE Usuarios (
    idUsuario INT AUTO_INCREMENT NOT NULL,
    usuarioCriadorId INT,
    matrizId INT,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    imagem VARCHAR(255),
    statusConta BOOLEAN DEFAULT true,
    reputacao DOUBLE DEFAULT 0.0,
    razaoSocial VARCHAR(255),
    nomeFantasia VARCHAR(255),
    cnpj VARCHAR(255),
    inscEstadual VARCHAR(255),
    inscMunicipal VARCHAR(255),
    mostrarNoSite BOOLEAN NOT NULL DEFAULT true,
    descricao VARCHAR(255),
    tipo VARCHAR(255),
    tipoDeMoeda VARCHAR(255),
    status BOOLEAN NOT NULL DEFAULT false,
    restricao VARCHAR(255),
    nomeContato VARCHAR(255),
    telefone VARCHAR(255),
    celular VARCHAR(255),
    emailContato VARCHAR(255),
    emailSecundario VARCHAR(255),
    site VARCHAR(255),
    logradouro VARCHAR(255),
    numero INT,
    cep VARCHAR(255),
    complemento VARCHAR(255),
    bairro VARCHAR(255),
    cidade VARCHAR(255),
    estado VARCHAR(255),
    regiao VARCHAR(255),
    aceitaOrcamento BOOLEAN NOT NULL,
    aceitaVoucher BOOLEAN NOT NULL,
    tipoOperacao INT NOT NULL,
    categoriaId INT,
    subcategoriaId INT,
    taxaComissaoGerente INT,
    permissoesDoUsuario VARCHAR(255) NOT NULL DEFAULT '[]',
    tokenResetSenha VARCHAR(255),
    bloqueado BOOLEAN DEFAULT false,
    PRIMARY KEY (idUsuario)
);

CREATE TABLE Conta (
    idConta INT AUTO_INCREMENT NOT NULL,
    taxaRepasseMatriz INT,
    limiteCredito DOUBLE NOT NULL DEFAULT 0.0,
    limiteUtilizado DOUBLE NOT NULL DEFAULT 0.0,
    saldoPermuta DOUBLE NOT NULL,
    limiteVendaMensal DOUBLE NOT NULL,
    limiteVendaTotal DOUBLE NOT NULL,
    limiteVendaEmpresa DOUBLE NOT NULL,
    valorVendaMensalAtual DOUBLE NOT NULL DEFAULT 0.0,
    valorVendaTotalAtual DOUBLE NOT NULL DEFAULT 0.0,
    diaFechamentoFatura INT NOT NULL,
    dataVencimentoFatura INT NOT NULL,
    numeroConta VARCHAR(255) NOT NULL,
    dataDeAfiliacao TIMESTAMP(3),
    nomeFranquia VARCHAR(255),
    tipoContaId INT,
    usuarioId INT,
    planoId INT,
    gerenteContaId INT,
    permissoesEspecificas VARCHAR(255) DEFAULT '[]',
    limiteDisponivel DOUBLE,
    saldoDinheiro DOUBLE DEFAULT 0.0,
    PRIMARY KEY (idConta)
);

CREATE TABLE TipoConta (
    idTipoConta INT AUTO_INCREMENT NOT NULL,
    tipoDaConta VARCHAR(255) NOT NULL,
    prefixoConta VARCHAR(255) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    permissoes VARCHAR(255) NOT NULL DEFAULT '[]',
    PRIMARY KEY (idTipoConta)
);

CREATE TABLE Plano (
    idPlano INT AUTO_INCREMENT NOT NULL,
    createdAt TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updatedAt TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    nomePlano VARCHAR(255) NOT NULL,
    tipoDoPlano VARCHAR(255),
    imagem VARCHAR(255),
    taxaInscricao DOUBLE NOT NULL,
    taxaComissao DOUBLE NOT NULL,
    taxaManutencaoAnual DOUBLE NOT NULL,
    PRIMARY KEY (idPlano)
);


CREATE TABLE SubContas (
    idSubContas INT AUTO_INCREMENT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    cpf VARCHAR(255) NOT NULL,
    numeroSubConta VARCHAR(255) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    imagem VARCHAR(255),
    statusConta BOOLEAN DEFAULT true,
    reputacao DOUBLE DEFAULT 0.0,
    telefone VARCHAR(255),
    celular VARCHAR(255),
    emailContato VARCHAR(255),
    logradouro VARCHAR(255),
    numero INT,
    cep VARCHAR(255),
    complemento VARCHAR(255),
    bairro VARCHAR(255),
    cidade VARCHAR(255),
    estado VARCHAR(255),
    contaPaiId INT NOT NULL,
    permissoes VARCHAR(255) NOT NULL DEFAULT '[]',
    tokenResetSenha VARCHAR(255),
    PRIMARY KEY (idSubContas)
);

CREATE TABLE Oferta (
    idOferta INT AUTO_INCREMENT NOT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    idFranquia INT,
    nomeFranquia VARCHAR(255),
    titulo VARCHAR(255) NOT NULL,
    tipo VARCHAR(255) NOT NULL,
    status BOOLEAN NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    quantidade INT NOT NULL,
    valor DOUBLE NOT NULL,
    limiteCompra DOUBLE NOT NULL,
    vencimento TIMESTAMP NOT NULL,
    cidade VARCHAR(255) NOT NULL,
    estado VARCHAR(255) NOT NULL,
    retirada VARCHAR(255) NOT NULL,
    obs VARCHAR(255) NOT NULL,
    imagens JSON,
    usuarioId INT,
    nomeUsuario VARCHAR(255) NOT NULL,
    categoriaId INT,
    subcategoriaId INT,
    subcontaId INT,
    PRIMARY KEY (idOferta)
);

CREATE TABLE Imagem (
    id INT AUTO_INCREMENT NOT NULL,
    public_id VARCHAR(255) NOT NULL,
    url VARCHAR(255) NOT NULL,
    ofertaId INT NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE Categoria (
    idCategoria INT AUTO_INCREMENT NOT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    nomeCategoria VARCHAR(255) NOT NULL,
    tipoCategoria VARCHAR(255),
    PRIMARY KEY (idCategoria)
);

CREATE TABLE Subcategoria (
    idSubcategoria INT AUTO_INCREMENT NOT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    nomeSubcategoria VARCHAR(255) NOT NULL,
    categoriaId INT NOT NULL,
    PRIMARY KEY (idSubcategoria)
);


CREATE TABLE Transacao (
    idTransacao INT AUTO_INCREMENT NOT NULL,
    codigo BINARY(16) NOT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dataDoEstorno TIMESTAMP,
    nomeComprador VARCHAR(255) NOT NULL,
    nomeVendedor VARCHAR(255) NOT NULL,
    compradorId INT,
    vendedorId INT,
    saldoUtilizado VARCHAR(255) NOT NULL,
    valorRt DOUBLE NOT NULL,
    valorAdicional DOUBLE NOT NULL,
    saldoAnteriorComprador DOUBLE NOT NULL,
    saldoAposComprador DOUBLE NOT NULL,
    saldoAnteriorVendedor DOUBLE NOT NULL,
    saldoAposVendedor DOUBLE NOT NULL,
    limiteCreditoAnteriorComprador DOUBLE,
    limiteCreditoAposComprador DOUBLE,
    numeroParcelas INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    notaAtendimento INT NOT NULL,
    observacaoNota VARCHAR(255) NOT NULL,
    status VARCHAR(255) NOT NULL,
    emiteVoucher BOOLEAN NOT NULL DEFAULT false,
    ofertaId INT,
    subContaCompradorId INT,
    subContaVendedorId INT,
    comissao DOUBLE NOT NULL,
    comissaoParcelada DOUBLE NOT NULL,
    PRIMARY KEY (idTransacao)
);


CREATE TABLE Parcelamento (
    idParcelamento INT AUTO_INCREMENT NOT NULL,
    numeroParcela INT NOT NULL,
    valorParcela DOUBLE NOT NULL,
    comissaoParcela DOUBLE NOT NULL,
    transacaoId INT NOT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idParcelamento)
);


CREATE TABLE Voucher (
    idVoucher INT AUTO_INCREMENT NOT NULL,
    codigo BINARY(16) NOT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    dataCancelamento TIMESTAMP,
    transacaoId INT NOT NULL,
    status VARCHAR(255) DEFAULT 'Ativo',
    PRIMARY KEY (idVoucher)
);


CREATE TABLE Cobranca (
    idCobranca INT AUTO_INCREMENT NOT NULL,
    valorFatura DOUBLE NOT NULL,
    referencia VARCHAR(255) NOT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(255),
    transacaoId INT,
    usuarioId INT,
    contaId INT,
    vencimentoFatura TIMESTAMP,
    subContaId INT,
    gerenteContaId INT,
    PRIMARY KEY (idCobranca)
);


CREATE TABLE SolicitacaoCredito (
    idSolicitacaoCredito INT AUTO_INCREMENT NOT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    valorSolicitado DOUBLE NOT NULL,
    status VARCHAR(255) NOT NULL,
    motivoRejeicao VARCHAR(255),
    usuarioSolicitanteId INT NOT NULL,
    descricaoSolicitante VARCHAR(255),
    comentarioAgencia VARCHAR(255),
    matrizAprovacao BOOLEAN,
    comentarioMatriz VARCHAR(255),
    usuarioCriadorId INT NOT NULL,
    matrizId INT,
    PRIMARY KEY (idSolicitacaoCredito)
);

CREATE TABLE FundoPermuta (
    idFundoPermuta INT AUTO_INCREMENT NOT NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    valor DOUBLE NOT NULL,
    usuarioId INT NOT NULL,
    PRIMARY KEY (idFundoPermuta)
);
