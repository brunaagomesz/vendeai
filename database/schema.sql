-- VendeAí • Esquema inicial
-- Execute no phpMyAdmin (aba SQL) com Apache e MySQL ligados no XAMPP.

CREATE DATABASE IF NOT EXISTS vendeai_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE vendeai_db;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL UNIQUE,
    descricao VARCHAR(255) NULL
);

-- Acréscimo ao modelo do PDF: vendedor_id, para que cada usuário
-- (empreendedor) cadastre e gerencie os seus próprios produtos.
CREATE TABLE IF NOT EXISTS produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT UNSIGNED NULL,
    vendedor_id INT UNSIGNED NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    preco DECIMAL(10,2) NOT NULL,
    imagem VARCHAR(255) NULL,
    estoque INT UNSIGNED NOT NULL DEFAULT 0,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_produtos_categorias
        FOREIGN KEY (categoria_id)
        REFERENCES categorias(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_produtos_vendedor
        FOREIGN KEY (vendedor_id)
        REFERENCES usuarios(id)
        ON DELETE SET NULL,
    CONSTRAINT chk_produtos_preco CHECK (preco >= 0)
);

INSERT IGNORE INTO categorias (nome, descricao) VALUES
('Eletrônicos', 'Celulares, acessórios e tecnologia'),
('Moda', 'Roupas e acessórios'),
('Casa', 'Produtos para casa e decoração'),
('Papelaria', 'Materiais de estudo e escritório');
