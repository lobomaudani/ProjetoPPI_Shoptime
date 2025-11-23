-- Migration script for existing installations
-- WARNING: Backup your database before running any migration.
-- Run these statements on your MySQL server (MySQL 8+ recommended).
-- This script adds discount-related columns, CreatedAt, FavoritosCount index and the visitas table.

-- 1) Backup (example):
-- mysqldump -u youruser -p yourdb > backup_before_migration.sql

-- 2) Add columns if missing
ALTER TABLE produtos ADD COLUMN IF NOT EXISTS `TemDesconto` BOOLEAN DEFAULT FALSE;
ALTER TABLE produtos ADD COLUMN IF NOT EXISTS `Desconto` DECIMAL(5,2) DEFAULT NULL;
ALTER TABLE produtos ADD COLUMN IF NOT EXISTS `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE produtos ADD COLUMN IF NOT EXISTS `FavoritosCount` INT NOT NULL DEFAULT 0;

-- 3) Add indexes (skip if already exist)
CREATE INDEX IF NOT EXISTS `idx_produtos_favoritos` ON produtos (FavoritosCount);
CREATE INDEX IF NOT EXISTS `idx_produtos_createdat` ON produtos (CreatedAt);
CREATE INDEX IF NOT EXISTS `idx_nome_categoria` ON produtos (Nome(100), Categorias_idCategorias);

-- 4) Create visitas table
CREATE TABLE IF NOT EXISTS `visitas` (
  `idVisita` int NOT NULL AUTO_INCREMENT,
  `Usuarios_idUsuarios` int DEFAULT NULL,
  `Produtos_idProdutos` int NOT NULL,
  `Data` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idVisita`),
  KEY `idx_visitas_usuario` (`Usuarios_idUsuarios`),
  KEY `idx_visitas_produto` (`Produtos_idProdutos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Foreign keys (optional)
ALTER TABLE visitas ADD CONSTRAINT IF NOT EXISTS fk_Visitas_Usuarios FOREIGN KEY (Usuarios_idUsuarios) REFERENCES usuarios (idUsuarios) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE visitas ADD CONSTRAINT IF NOT EXISTS fk_Visitas_Produtos FOREIGN KEY (Produtos_idProdutos) REFERENCES produtos (idProdutos) ON DELETE CASCADE ON UPDATE CASCADE;

-- 6) Triggers to maintain FavoritosCount (if not present)
-- If your DB already has triggers for favoritos, skip these. Creating triggers may require DELIMITER support in your client.

-- Example triggers (run inside a DELIMITER block):
-- DELIMITER $$
-- CREATE TRIGGER trg_favoritos_after_insert
-- AFTER INSERT ON favoritos
-- FOR EACH ROW
-- BEGIN
--   UPDATE produtos SET FavoritosCount = FavoritosCount + 1 WHERE idProdutos = NEW.Produtos_idProdutos;
-- END$$
-- CREATE TRIGGER trg_favoritos_after_delete
-- AFTER DELETE ON favoritos
-- FOR EACH ROW
-- BEGIN
--   UPDATE produtos SET FavoritosCount = GREATEST(0, FavoritosCount - 1) WHERE idProdutos = OLD.Produtos_idProdutos;
-- END$$
-- DELIMITER ;

-- Migration notes:
-- - Test on a staging copy first.
-- - If your MySQL version is older than 8.0 and does not support "IF NOT EXISTS" on ADD COLUMN or CREATE INDEX,
--   remove the IF NOT EXISTS and run checks against information_schema before applying.
-- - After migration, consider running a script that recalculates FavoritosCount for existing products:
--     UPDATE produtos p SET FavoritosCount = (SELECT COUNT(*) FROM favoritos f WHERE f.Produtos_idProdutos = p.idProdutos);

-- End of migration script
