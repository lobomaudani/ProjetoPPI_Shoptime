-- --------------------------------------------------------
-- Servidor:                     127.0.0.1
-- Versão do servidor:           8.0.30 - MySQL Community Server - GPL
-- OS do Servidor:               Win64
-- HeidiSQL Versão:              12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Copiando estrutura do banco de dados para ppi_v01_showtime
CREATE DATABASE IF NOT EXISTS `ppi_v01_showtime` /*!40100 DEFAULT CHARACTER SET utf8mb3 */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `ppi_v01_showtime`;

-- Copiando estrutura para tabela ppi_v01_showtime.cargos
CREATE TABLE IF NOT EXISTS `cargos` (
  `idCargos` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(45) NOT NULL,
  PRIMARY KEY (`idCargos`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

-- Copiando dados para a tabela ppi_v01_showtime.cargos: ~3 rows (aproximadamente)
INSERT INTO `cargos` (`idCargos`, `Nome`) VALUES
	(1, 'Cliente'),
	(2, 'Admin'),
	(3, 'Mod');

-- Copiando estrutura para tabela ppi_v01_showtime.categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `idCategorias` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(45) NOT NULL,
  PRIMARY KEY (`idCategorias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Copiando dados para a tabela ppi_v01_showtime.categorias: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela ppi_v01_showtime.compras
CREATE TABLE IF NOT EXISTS `compras` (
  `idCompras` int NOT NULL AUTO_INCREMENT,
  `Data` datetime NOT NULL,
  `Usuarios_idUsuarios` int NOT NULL,
  PRIMARY KEY (`idCompras`),
  KEY `fk_Compras_Usuários1_idx` (`Usuarios_idUsuarios`),
  CONSTRAINT `fk_Compras_Usuários1` FOREIGN KEY (`Usuarios_idUsuarios`) REFERENCES `usuarios` (`idUsuarios`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Copiando dados para a tabela ppi_v01_showtime.compras: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela ppi_v01_showtime.enderecoimagem
CREATE TABLE IF NOT EXISTS `enderecoimagem` (
  `idEnderecoImagem` int NOT NULL AUTO_INCREMENT,
  `ImagemUrl` longblob NOT NULL,
  `Produtos_idProdutos` int NOT NULL,
  `Produtos_Categorias_idCategorias` int NOT NULL,
  `Produtos_Marcas_idMarcas` int NOT NULL,
  PRIMARY KEY (`idEnderecoImagem`),
  KEY `fk_EnderecoImagem_Produtos1_idx` (`Produtos_idProdutos`,`Produtos_Categorias_idCategorias`,`Produtos_Marcas_idMarcas`),
  CONSTRAINT `fk_EnderecoImagem_Produtos1` FOREIGN KEY (`Produtos_idProdutos`, `Produtos_Categorias_idCategorias`, `Produtos_Marcas_idMarcas`) REFERENCES `produtos` (`idProdutos`, `Categorias_idCategorias`, `Marcas_idMarcas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Copiando dados para a tabela ppi_v01_showtime.enderecoimagem: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela ppi_v01_showtime.enderecos
CREATE TABLE IF NOT EXISTS `enderecos` (
  `IdEndereco` int NOT NULL AUTO_INCREMENT,
  `Rua` varchar(45) NOT NULL,
  `Numero` varchar(45) DEFAULT NULL,
  `Bairro` varchar(45) NOT NULL,
  `Cidade` varchar(45) NOT NULL,
  `Estado` varchar(45) NOT NULL,
  `CEP` varchar(45) NOT NULL,
  `Usuarios_idUsuarios` int NOT NULL,
  PRIMARY KEY (`IdEndereco`,`Usuarios_idUsuarios`),
  KEY `fk_Enderecos_Usuários1_idx` (`Usuarios_idUsuarios`),
  CONSTRAINT `fk_Enderecos_Usuários1` FOREIGN KEY (`Usuarios_idUsuarios`) REFERENCES `usuarios` (`idUsuarios`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Copiando dados para a tabela ppi_v01_showtime.enderecos: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela ppi_v01_showtime.itenscompras
CREATE TABLE IF NOT EXISTS `itenscompras` (
  `idCompras` int NOT NULL AUTO_INCREMENT,
  `QuantidadeComprada` int NOT NULL,
  `Produtos_idProdutos` int NOT NULL,
  `Compras_idCompras` int NOT NULL,
  PRIMARY KEY (`idCompras`,`Produtos_idProdutos`,`Compras_idCompras`),
  KEY `fk_ItensCompras_Produtos1_idx` (`Produtos_idProdutos`),
  KEY `fk_ItensCompras_Compras1_idx` (`Compras_idCompras`),
  CONSTRAINT `fk_ItensCompras_Compras1` FOREIGN KEY (`Compras_idCompras`) REFERENCES `compras` (`idCompras`),
  CONSTRAINT `fk_ItensCompras_Produtos1` FOREIGN KEY (`Produtos_idProdutos`) REFERENCES `produtos` (`idProdutos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Copiando dados para a tabela ppi_v01_showtime.itenscompras: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela ppi_v01_showtime.marcas
CREATE TABLE IF NOT EXISTS `marcas` (
  `idMarcas` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(45) NOT NULL,
  PRIMARY KEY (`idMarcas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Copiando dados para a tabela ppi_v01_showtime.marcas: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela ppi_v01_showtime.produtos
CREATE TABLE IF NOT EXISTS `produtos` (
  `idProdutos` int NOT NULL AUTO_INCREMENT,
  `Usuarios_idUsuarios` int NOT NULL,
  `Nome` varchar(45) NOT NULL,
  `Preco` varchar(45) NOT NULL,
  `Quantidade` varchar(45) DEFAULT NULL,
  `Avaliacao` decimal(10,0) DEFAULT NULL,
  `Categorias_idCategorias` int NOT NULL,
  `Marcas_idMarcas` int NOT NULL,
  PRIMARY KEY (`idProdutos`,`Categorias_idCategorias`,`Marcas_idMarcas`),
  KEY `fk_Produtos_Usuários1_idx` (`Usuarios_idUsuarios`),
  KEY `fk_Produtos_Categorias1_idx` (`Categorias_idCategorias`),
  KEY `fk_Produtos_Marcas1_idx` (`Marcas_idMarcas`),
  CONSTRAINT `fk_Produtos_Categorias1` FOREIGN KEY (`Categorias_idCategorias`) REFERENCES `categorias` (`idCategorias`),
  CONSTRAINT `fk_Produtos_Marcas1` FOREIGN KEY (`Marcas_idMarcas`) REFERENCES `marcas` (`idMarcas`),
  CONSTRAINT `fk_Produtos_Usuários1` FOREIGN KEY (`Usuarios_idUsuarios`) REFERENCES `usuarios` (`idUsuarios`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Copiando dados para a tabela ppi_v01_showtime.produtos: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela ppi_v01_showtime.telefones
CREATE TABLE IF NOT EXISTS `telefones` (
  `idTelefone` int NOT NULL AUTO_INCREMENT,
  `Numero` varchar(45) NOT NULL,
  `Usuarios_idUsuarios` int NOT NULL,
  PRIMARY KEY (`idTelefone`,`Usuarios_idUsuarios`),
  KEY `fk_Telefones_Usuários1_idx` (`Usuarios_idUsuarios`),
  CONSTRAINT `fk_Telefones_Usuários1` FOREIGN KEY (`Usuarios_idUsuarios`) REFERENCES `usuarios` (`idUsuarios`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Copiando dados para a tabela ppi_v01_showtime.telefones: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela ppi_v01_showtime.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `idUsuarios` int NOT NULL AUTO_INCREMENT,
  `Nome` varchar(45) NOT NULL,
  `Email` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `DataNascimento` date DEFAULT NULL,
  `CPF` varchar(14) NOT NULL,
  `Senha` varchar(255) NOT NULL,
  `Cargos_idCargos` int NOT NULL,
  `ImagemUrl` longblob,
  PRIMARY KEY (`idUsuarios`,`Cargos_idCargos`),
  UNIQUE KEY `CPF_UNIQUE` (`CPF`),
  UNIQUE KEY `E-Mail_UNIQUE` (`Email`) USING BTREE,
  KEY `fk_Usuários_Cargos1_idx` (`Cargos_idCargos`),
  CONSTRAINT `fk_Usuários_Cargos1` FOREIGN KEY (`Cargos_idCargos`) REFERENCES `cargos` (`idCargos`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3;

-- Copiando dados para a tabela ppi_v01_showtime.usuarios: ~1 rows (aproximadamente)
INSERT INTO `usuarios` (`idUsuarios`, `Nome`, `Email`, `DataNascimento`, `CPF`, `Senha`, `Cargos_idCargos`, `ImagemUrl`) VALUES
	(19, 'Daniel', 'daniel@email.com', NULL, '1', '$2y$10$FIh/9/4u/O4PdjpUzSF1pu2RpKQyI1wlvD56l/OqKWFHFRoPlLNkG', 1, NULL);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
