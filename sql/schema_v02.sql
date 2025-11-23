-- Full schema for ppi_v02_showtime (optimized copy)
-- Run manually in your MySQL client if you want a full rebuild.

CREATE DATABASE IF NOT EXISTS `ppi_v02_showtime` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ppi_v02_showtime`;

-- cargos
CREATE TABLE IF NOT EXISTS `cargos` (
  `idCargos` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(45) NOT NULL,
  PRIMARY KEY (`idCargos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO cargos (idCargos, Nome) VALUES (1,'Administrador'),(2,'Moderador'),(3,'Usuário'),(4,'Vendedor');

-- categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `idCategorias` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) NOT NULL,
  PRIMARY KEY (`idCategorias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO categorias (idCategorias, Nome) VALUES
(1,'Celulares e Smartphones'),(2,'Tablets e E-readers'),(3,'Notebooks e Ultrabooks'),(4,'Desktops e All-in-One'),(5,'Monitores'),(6,'Televisores'),(7,'Áudio e Home Theater'),(8,'Fones de Ouvido e Headsets'),(9,'Câmeras e Acessórios'),(10,'Drones e Acessórios'),(11,'Smartwatches e Wearables'),(12,'Consoles e Videogames'),(13,'Peças e Componentes'),(14,'Redes e Roteadores'),(15,'Armazenamento (HD/SSD/Pendrive)'),(16,'Impressoras e Suprimentos'),(17,'Periféricos (Teclado/Mouse)'),(18,'Energia (No-break/Carregadores/Baterias)'),(19,'Acessórios (Capas/Cabos/Carregadores)'),(20,'Domótica e Smart Home');

-- usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `idUsuarios` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `DataNascimento` date DEFAULT NULL,
  `CPF` varchar(14) NOT NULL,
  `Senha` varchar(255) NOT NULL,
  `Cargos_idCargos` int NOT NULL,
  PRIMARY KEY (`idUsuarios`),
  UNIQUE KEY `CPF_UNIQUE` (`CPF`),
  UNIQUE KEY `Email_UNIQUE` (`Email`),
  KEY `fk_Usuarios_Cargos1_idx` (`Cargos_idCargos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- produtos
CREATE TABLE IF NOT EXISTS `produtos` (
  `idProdutos` int NOT NULL AUTO_INCREMENT,
  `Usuarios_idUsuarios` int NOT NULL,
  `Nome` varchar(255) NOT NULL,
  `Descricao` varchar(1000) DEFAULT '' COMMENT 'Descrição do produto',
  `Preco` decimal(10,2) NOT NULL,
  `Quantidade` int DEFAULT NULL,
  `Avaliacao` decimal(3,2) DEFAULT NULL,
  `Categorias_idCategorias` int NOT NULL,
  `Marca` varchar(100) DEFAULT NULL,
  `FavoritosCount` int NOT NULL DEFAULT 0,
  `tem_desconto` boolean default false,
  `quantidade_desc` int,
  PRIMARY KEY (`idProdutos`),
  KEY `fk_Produtos_Usuarios1_idx` (`Usuarios_idUsuarios`),
  KEY `fk_Produtos_Categorias1_idx` (`Categorias_idCategorias`),
  INDEX idx_nome_categoria (Nome(100), Categorias_idCategorias)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- enderecoimagem
CREATE TABLE IF NOT EXISTS `enderecoimagem` (
  `idEnderecoImagem` int NOT NULL AUTO_INCREMENT,
  `ImagemUrl` longblob NOT NULL,
  `Produtos_idProdutos` int NOT NULL,
  PRIMARY KEY (`idEnderecoImagem`),
  KEY `fk_EnderecoImagem_Produtos1_idx` (`Produtos_idProdutos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- compras / itenscompras
CREATE TABLE IF NOT EXISTS `compras` (
  `idCompras` int NOT NULL AUTO_INCREMENT,
  `Data` datetime NOT NULL,
  `Usuarios_idUsuarios` int NOT NULL,
  PRIMARY KEY (`idCompras`),
  KEY `fk_Compras_Usuarios1_idx` (`Usuarios_idUsuarios`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `itenscompras` (
  `QuantidadeComprada` int NOT NULL,
  `Produtos_idProdutos` int NOT NULL,
  `Compras_idCompras` int NOT NULL,
  PRIMARY KEY (`Produtos_idProdutos`,`Compras_idCompras`),
  KEY `fk_ItensCompras_Produtos1_idx` (`Produtos_idProdutos`),
  KEY `fk_ItensCompras_Compras1_idx` (`Compras_idCompras`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- telefones
CREATE TABLE IF NOT EXISTS `telefones` (
  `idTelefone` int NOT NULL AUTO_INCREMENT,
  `Numero` varchar(45) NOT NULL,
  `Usuarios_idUsuarios` int NOT NULL,
  PRIMARY KEY (`idTelefone`),
  KEY `fk_Telefones_Usuarios1_idx` (`Usuarios_idUsuarios`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- enderecos
CREATE TABLE IF NOT EXISTS `enderecos` (
  `IdEndereco` int NOT NULL AUTO_INCREMENT,
  `Rua` varchar(45) NOT NULL,
  `Numero` varchar(45) DEFAULT NULL,
  `Bairro` varchar(45) NOT NULL,
  `Cidade` varchar(45) NOT NULL,
  `Estado` varchar(45) NOT NULL,
  `CEP` varchar(45) NOT NULL,
  `Usuarios_idUsuarios` int NOT NULL,
  PRIMARY KEY (`IdEndereco`),
  KEY `fk_Enderecos_Usuarios1_idx` (`Usuarios_idUsuarios`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- favoritos
CREATE TABLE IF NOT EXISTS `favoritos` (
  `Usuarios_idUsuarios` int NOT NULL,
  `Produtos_idProdutos` int NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Usuarios_idUsuarios`,`Produtos_idProdutos`),
  KEY `fk_Favoritos_Usuarios_idx` (`Usuarios_idUsuarios`),
  KEY `fk_Favoritos_Produtos_idx` (`Produtos_idProdutos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- triggers (notes: create triggers manually if your client requires DELIMITER blocks)
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

CREATE TRIGGER trg_itenscompras_after_insert
AFTER INSERT ON itenscompras
FOR EACH ROW
BEGIN
  UPDATE produtos SET Quantidade = IFNULL(Quantidade,0) - NEW.QuantidadeComprada WHERE idProdutos = NEW.Produtos_idProdutos;
END$$

CREATE TRIGGER trg_itenscompras_after_delete
AFTER DELETE ON itenscompras
FOR EACH ROW
BEGIN
  UPDATE produtos SET Quantidade = IFNULL(Quantidade,0) + OLD.QuantidadeComprada WHERE idProdutos = OLD.Produtos_idProdutos;
END$$
DELIMITER ;

-- Foreign keys (add after tables created)
ALTER TABLE produtos ADD CONSTRAINT IF NOT EXISTS fk_Produtos_Usuarios1 FOREIGN KEY (Usuarios_idUsuarios) REFERENCES usuarios (idUsuarios) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE produtos ADD CONSTRAINT IF NOT EXISTS fk_Produtos_Categorias1 FOREIGN KEY (Categorias_idCategorias) REFERENCES categorias (idCategorias) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE enderecoimagem ADD CONSTRAINT IF NOT EXISTS fk_EnderecoImagem_Produtos1 FOREIGN KEY (Produtos_idProdutos) REFERENCES produtos (idProdutos) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE compras ADD CONSTRAINT IF NOT EXISTS fk_Compras_Usuarios1 FOREIGN KEY (Usuarios_idUsuarios) REFERENCES usuarios (idUsuarios) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE itenscompras ADD CONSTRAINT IF NOT EXISTS fk_ItensCompras_Compras1 FOREIGN KEY (Compras_idCompras) REFERENCES compras (idCompras) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE itenscompras ADD CONSTRAINT IF NOT EXISTS fk_ItensCompras_Produtos1 FOREIGN KEY (Produtos_idProdutos) REFERENCES produtos (idProdutos) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE favoritos ADD CONSTRAINT IF NOT EXISTS fk_Favoritos_Usuarios FOREIGN KEY (Usuarios_idUsuarios) REFERENCES usuarios (idUsuarios) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE favoritos ADD CONSTRAINT IF NOT EXISTS fk_Favoritos_Produtos FOREIGN KEY (Produtos_idProdutos) REFERENCES produtos (idProdutos) ON DELETE CASCADE ON UPDATE CASCADE;

-- End of schema
