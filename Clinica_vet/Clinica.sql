-- =============================================================
--  VetSys – Script de Criação do Banco de Dados
--  Versão  : 1.1.0
--  Descrição: Cria todas as tabelas, relacionamentos e dados
--             iniciais (seed) para o sistema VetSys.
-- =============================================================

CREATE DATABASE IF NOT EXISTS clinica
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE clinica;

-- ── 1. Tabela de Usuários (Autenticação e Perfis) ─────────────
CREATE TABLE usuario (
    id_usuario INT          AUTO_INCREMENT,
    email      VARCHAR(100) NOT NULL UNIQUE,
    senha      VARCHAR(255) NOT NULL,                          -- bcrypt via password_hash()
    perfil     ENUM('Gerente','Recepcionista','Veterinario','Cliente') NOT NULL,
    PRIMARY KEY (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 2. Tabela de Clientes (Tutores) ──────────────────────────
CREATE TABLE cliente (
    id_cliente   INT         AUTO_INCREMENT,
    id_usuario   INT         UNIQUE,                          -- ligação 1:1 opcional com login
    nome_cliente VARCHAR(100) NOT NULL,
    telefone     VARCHAR(20),
    cpf          VARCHAR(14) UNIQUE,
    PRIMARY KEY (id_cliente),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. Tabela de Veterinários ─────────────────────────────────
CREATE TABLE veterinario (
    id_veterinario INT         AUTO_INCREMENT,
    id_usuario     INT         UNIQUE,                        -- ligação 1:1 com login
    nome_vet       VARCHAR(100) NOT NULL,
    crmv           VARCHAR(20) NOT NULL UNIQUE,
    PRIMARY KEY (id_veterinario),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 4. Tabela de Pets (Pacientes) ─────────────────────────────
CREATE TABLE pet (
    id_pet     INT         AUTO_INCREMENT,
    id_cliente INT         NOT NULL,
    nome_pet   VARCHAR(50) NOT NULL,
    especie    VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_pet),
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 5. Tabela de Serviços / Catálogo da Clínica ───────────────
CREATE TABLE servico (
    id_servico   INT            AUTO_INCREMENT,
    descricao    VARCHAR(150)   NOT NULL,
    valor_padrao DECIMAL(10,2)  NOT NULL,
    PRIMARY KEY (id_servico)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 6. Tabela de Consultas / Agendamentos ────────────────────
CREATE TABLE consulta (
    id_consulta      INT      AUTO_INCREMENT,
    id_pet           INT      NOT NULL,
    id_veterinario   INT      NOT NULL,
    data_hora        DATETIME NOT NULL,
    historico_clinico TEXT,                                   -- notas do veterinário
    PRIMARY KEY (id_consulta),
    FOREIGN KEY (id_pet)         REFERENCES pet(id_pet)               ON DELETE RESTRICT,
    FOREIGN KEY (id_veterinario) REFERENCES veterinario(id_veterinario) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 7. Tabela Intermediária: Itens da Consulta ───────────────
--     Permite múltiplos serviços por consulta e guarda o valor
--     cobrado no momento (histórico de preço).
CREATE TABLE itens_consulta (
    id_consulta INT           NOT NULL,
    id_servico  INT           NOT NULL,
    valor_cobrado DECIMAL(10,2) NOT NULL,                    -- preço na data do atendimento
    PRIMARY KEY (id_consulta, id_servico),
    FOREIGN KEY (id_consulta) REFERENCES consulta(id_consulta) ON DELETE CASCADE,
    FOREIGN KEY (id_servico)  REFERENCES servico(id_servico)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
--  CARGA INICIAL (SEED)
-- =============================================================

-- Usuários: senhas em texto para referência (no sistema usa bcrypt)
-- Gerente  → senha: gerente123
-- Recep    → senha: recep123
-- Vet      → senha: vet123
INSERT INTO usuario (email, senha, perfil) VALUES
('gerente@vetsys.com',  '$2y$10$IKLoV9wBIq/8rafDN/L/T.WjVooMNpS5C.Vlb/GXuZ32dY.onuhwO', 'Gerente'),
('recepcao@vetsys.com', '$2y$10$Dk87XArfDJ7rzEhDX6FS5OcvlmYIGDAUHJU5LoTJZxLeF2QAL2MQO', 'Recepcionista'),
('drjose@vetsys.com',   '$2y$10$CyVLvGRRCg08VofHPJyFCuXo9sdP/1P9kPqNADafxNB/ApEsPPftO', 'Veterinario');

-- Veterinário vinculado ao usuário
INSERT INTO veterinario (id_usuario, nome_vet, crmv) VALUES
(3, 'Dr. José Gabriel', 'CRMV-12345');

-- Clientes de exemplo
INSERT INTO cliente (nome_cliente, telefone, cpf) VALUES
('Lucas Nascente',  '(61) 99999-0001', '111.222.333-01'),
('Ana Paula Silva', '(61) 99999-0002', '111.222.333-02');

-- Pets de exemplo
INSERT INTO pet (id_cliente, nome_pet, especie) VALUES
(1, 'Rex',    'Cachorro'),
(1, 'Mia',    'Gato'),
(2, 'Pichon', 'Ave');

-- Serviços
INSERT INTO servico (descricao, valor_padrao) VALUES
('Consulta Geral',      80.00),
('Vacinação',           60.00),
('Hemograma Completo', 120.00),
('Raio-X',             150.00),
('Banho e Tosa',        55.00);

-- Adiciona a coluna 'raca' na tabela 'pet'
-- Isso permite armazenar a raça específica do cachorro
ALTER TABLE pet 
ADD COLUMN raca VARCHAR(100) NULL 
AFTER especie;

-- Se quiser adicionar um comment na coluna (opcional)
ALTER TABLE pet 
MODIFY COLUMN raca VARCHAR(100) NULL 
COMMENT 'Raça específica do animal (usado para Cães com Dog API)';

-- Verifica se a coluna foi adicionada com sucesso
DESCRIBE pet;