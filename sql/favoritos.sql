-- SQL DDL para favoritos (execute no seu cliente MySQL/HeidiSQL)

CREATE TABLE IF NOT EXISTS `favoritos` (
  `Usuarios_idUsuarios` INT NOT NULL,
  `Produtos_idProdutos` INT NOT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Usuarios_idUsuarios`,`Produtos_idProdutos`),
  KEY `fk_Fav_Usuarios` (`Usuarios_idUsuarios`),
  KEY `fk_Fav_Produtos` (`Produtos_idProdutos`),
  CONSTRAINT `fk_Favoritos_Usuarios` FOREIGN KEY (`Usuarios_idUsuarios`) REFERENCES `usuarios` (`idUsuarios`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_Favoritos_Produtos` FOREIGN KEY (`Produtos_idProdutos`) REFERENCES `produtos` (`idProdutos`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Adiciona coluna de contador em produtos (opcional, útil para exibir número de favoritos)
ALTER TABLE `produtos` ADD COLUMN IF NOT EXISTS `FavoritosCount` INT NOT NULL DEFAULT 0;

-- Triggers para manter o contador atualizado
DELIMITER $$
CREATE TRIGGER trg_favoritos_after_insert
AFTER INSERT ON favoritos
FOR EACH ROW
BEGIN
  UPDATE produtos SET FavoritosCount = FavoritosCount + 1 WHERE idProdutos = NEW.Produtos_idProdutos;
END$$

CREATE TRIGGER trg_favoritos_after_delete
AFTER DELETE ON favoritos
FOR EACH ROW
BEGIN
  UPDATE produtos SET FavoritosCount = GREATEST(0, FavoritosCount - 1) WHERE idProdutos = OLD.Produtos_idProdutos;
END$$
DELIMITER ;
