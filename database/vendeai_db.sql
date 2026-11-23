-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 24/11/2026 às 00:53
-- Versão do servidor: 10.11.16-MariaDB
-- Versão do PHP: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `vendeai_db`
--
CREATE DATABASE IF NOT EXISTS `vendeai_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vendeai_db`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(80) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `descricao`) VALUES
(1, 'Eletrônicos', 'Celulares, acessórios e tecnologia'),
(2, 'Moda', 'Roupas e acessórios'),
(3, 'Casa', 'Produtos para casa e decoração'),
(4, 'Papelaria', 'Materiais de estudo e escritório');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(10) UNSIGNED NOT NULL,
  `categoria_id` int(10) UNSIGNED DEFAULT NULL,
  `vendedor_id` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `estoque` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp()
) ;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `categoria_id`, `vendedor_id`, `nome`, `descricao`, `preco`, `imagem`, `estoque`, `ativo`, `data_cadastro`) VALUES
(1, 1, NULL, 'Fone de ouvido Bluetooth', 'Fone sem fio com microfone e até 20 horas de bateria.', 249.90, 'fone.jpg', 25, 1, '2026-11-18 23:46:57'),
(2, 1, NULL, 'Carregador portátil 10.000 mAh', 'Power bank compacto com duas saídas USB.', 149.90, 'carregador.jpg', 40, 1, '2026-11-18 23:46:57'),
(3, 2, NULL, 'Moletom azul unissex', 'Moletom confortável com capuz e bolso canguru.', 159.00, 'moletom.jpg', 15, 1, '2026-11-18 23:46:57'),
(4, 2, NULL, 'Boné preto ajustável', 'Boné de algodão com fecho ajustável.', 119.90, 'bone.jpg', 30, 1, '2026-11-18 23:46:57'),
(5, 3, NULL, 'Caneca térmica 450 ml', 'Mantém a bebida quente ou gelada por horas.', 219.90, 'caneca.jpg', 50, 1, '2026-11-18 23:46:57'),
(6, 3, NULL, 'Luminária de mesa LED', 'Luminária com três níveis de brilho.', 79.90, 'luminaria.jpg', 20, 1, '2026-11-18 23:46:57'),
(7, 4, NULL, 'Caderno inteligente', 'Capa dura, 160 folhas pautadas (10 matérias)', 149.90, 'caderno.jpg', 60, 1, '2026-11-18 23:46:57'),
(8, 4, NULL, 'Kit de canetas marca-texto', 'Pacote com 6 cores pastel.', 64.90, 'canetas.jpg', 80, 1, '2026-11-18 23:46:57');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `data_cadastro`) VALUES
(1, 'BRUNA GOMES DA SILVA', 'brunaagommesz@gmail.com', '$2y$12$/otYU.1DGZ4UTZuXc6vk/O0OpU97IesLedvEIDTXn7h3x3EmErz9a', '2026-11-18 23:48:53');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_produtos_categorias` (`categoria_id`),
  ADD KEY `fk_produtos_vendedor` (`vendedor_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `fk_produtos_categorias` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_produtos_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
