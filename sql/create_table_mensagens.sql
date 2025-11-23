-- Migration: create mensagens table for product chats
-- Run on your DB to add chat support

CREATE TABLE IF NOT EXISTS `mensagens` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `Produtos_idProdutos` INT NULL,
  `DeUsuarios_idUsuarios` INT NOT NULL,
  `ParaUsuarios_idUsuarios` INT NOT NULL,
  `Mensagem` TEXT NOT NULL,
  `Lida` TINYINT(1) NOT NULL DEFAULT 0,
  `CriadoEm` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_produto` (`Produtos_idProdutos`),
  INDEX `idx_de` (`DeUsuarios_idUsuarios`),
  INDEX `idx_para` (`ParaUsuarios_idUsuarios`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
