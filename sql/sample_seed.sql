-- Sample seed data for demo/presentation
-- Adds 100 users, 100 products, and 1 thumbnail per product (enderecoimagem)
-- Run this on the database where the schema from sql/schema_v02.sql is already applied.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Insert users (omit idUsuarios; table uses AUTO_INCREMENT)
-- Split into smaller INSERTs to reduce parser issues on some MySQL clients
INSERT INTO usuarios (Nome, Email, DataNascimento, CPF, Senha, Cargos_idCargos) VALUES
('Demo User 1', 'demo1@example.test', '1990-01-01', '00000000001', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 2', 'demo2@example.test', '1990-01-01', '00000000002', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 3', 'demo3@example.test', '1990-01-01', '00000000003', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 4', 'demo4@example.test', '1990-01-01', '00000000004', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 5', 'demo5@example.test', '1990-01-01', '00000000005', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 6', 'demo6@example.test', '1990-01-01', '00000000006', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 7', 'demo7@example.test', '1990-01-01', '00000000007', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 8', 'demo8@example.test', '1990-01-01', '00000000008', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 9', 'demo9@example.test', '1990-01-01', '00000000009', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 10', 'demo10@example.test', '1990-01-01', '00000000010', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4)
;

INSERT INTO usuarios (Nome, Email, DataNascimento, CPF, Senha, Cargos_idCargos) VALUES
('Demo User 11', 'demo11@example.test', '1990-01-01', '00000000011', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 12', 'demo12@example.test', '1990-01-01', '00000000012', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 13', 'demo13@example.test', '1990-01-01', '00000000013', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 14', 'demo14@example.test', '1990-01-01', '00000000014', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 15', 'demo15@example.test', '1990-01-01', '00000000015', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 16', 'demo16@example.test', '1990-01-01', '00000000016', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 17', 'demo17@example.test', '1990-01-01', '00000000017', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 18', 'demo18@example.test', '1990-01-01', '00000000018', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 19', 'demo19@example.test', '1990-01-01', '00000000019', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4),
('Demo User 20', 'demo20@example.test', '1990-01-01', '00000000020', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 4)
;

INSERT INTO usuarios (Nome, Email, DataNascimento, CPF, Senha, Cargos_idCargos) VALUES
('Demo User 21', 'demo21@example.test', '1990-01-01', '00000000021', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 22', 'demo22@example.test', '1990-01-01', '00000000022', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 23', 'demo23@example.test', '1990-01-01', '00000000023', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 24', 'demo24@example.test', '1990-01-01', '00000000024', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 25', 'demo25@example.test', '1990-01-01', '00000000025', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 26', 'demo26@example.test', '1990-01-01', '00000000026', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 27', 'demo27@example.test', '1990-01-01', '00000000027', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 28', 'demo28@example.test', '1990-01-01', '00000000028', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 29', 'demo29@example.test', '1990-01-01', '00000000029', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 30', 'demo30@example.test', '1990-01-01', '00000000030', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3)
;

INSERT INTO usuarios (Nome, Email, DataNascimento, CPF, Senha, Cargos_idCargos) VALUES
('Demo User 31', 'demo31@example.test', '1990-01-01', '00000000031', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 32', 'demo32@example.test', '1990-01-01', '00000000032', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 33', 'demo33@example.test', '1990-01-01', '00000000033', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 34', 'demo34@example.test', '1990-01-01', '00000000034', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 35', 'demo35@example.test', '1990-01-01', '00000000035', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 36', 'demo36@example.test', '1990-01-01', '00000000036', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 37', 'demo37@example.test', '1990-01-01', '00000000037', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 38', 'demo38@example.test', '1990-01-01', '00000000038', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 39', 'demo39@example.test', '1990-01-01', '00000000039', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 40', 'demo40@example.test', '1990-01-01', '00000000040', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3)
;

INSERT INTO usuarios (Nome, Email, DataNascimento, CPF, Senha, Cargos_idCargos) VALUES
('Demo User 41', 'demo41@example.test', '1990-01-01', '00000000041', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 42', 'demo42@example.test', '1990-01-01', '00000000042', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 43', 'demo43@example.test', '1990-01-01', '00000000043', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 44', 'demo44@example.test', '1990-01-01', '00000000044', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 45', 'demo45@example.test', '1990-01-01', '00000000045', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 46', 'demo46@example.test', '1990-01-01', '00000000046', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 47', 'demo47@example.test', '1990-01-01', '00000000047', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 48', 'demo48@example.test', '1990-01-01', '00000000048', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 49', 'demo49@example.test', '1990-01-01', '00000000049', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 50', 'demo50@example.test', '1990-01-01', '00000000050', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3)
;

INSERT INTO usuarios (Nome, Email, DataNascimento, CPF, Senha, Cargos_idCargos) VALUES
('Demo User 51', 'demo51@example.test', '1990-01-01', '00000000051', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 52', 'demo52@example.test', '1990-01-01', '00000000052', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 53', 'demo53@example.test', '1990-01-01', '00000000053', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 54', 'demo54@example.test', '1990-01-01', '00000000054', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 55', 'demo55@example.test', '1990-01-01', '00000000055', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 56', 'demo56@example.test', '1990-01-01', '00000000056', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 57', 'demo57@example.test', '1990-01-01', '00000000057', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 58', 'demo58@example.test', '1990-01-01', '00000000058', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 59', 'demo59@example.test', '1990-01-01', '00000000059', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 60', 'demo60@example.test', '1990-01-01', '00000000060', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3)
;

INSERT INTO usuarios (Nome, Email, DataNascimento, CPF, Senha, Cargos_idCargos) VALUES
('Demo User 61', 'demo61@example.test', '1990-01-01', '00000000061', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 62', 'demo62@example.test', '1990-01-01', '00000000062', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 63', 'demo63@example.test', '1990-01-01', '00000000063', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 64', 'demo64@example.test', '1990-01-01', '00000000064', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 65', 'demo65@example.test', '1990-01-01', '00000000065', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 66', 'demo66@example.test', '1990-01-01', '00000000066', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 67', 'demo67@example.test', '1990-01-01', '00000000067', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 68', 'demo68@example.test', '1990-01-01', '00000000068', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 69', 'demo69@example.test', '1990-01-01', '00000000069', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 70', 'demo70@example.test', '1990-01-01', '00000000070', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3)
;

INSERT INTO usuarios (Nome, Email, DataNascimento, CPF, Senha, Cargos_idCargos) VALUES
('Demo User 71', 'demo71@example.test', '1990-01-01', '00000000071', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 72', 'demo72@example.test', '1990-01-01', '00000000072', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 73', 'demo73@example.test', '1990-01-01', '00000000073', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 74', 'demo74@example.test', '1990-01-01', '00000000074', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 75', 'demo75@example.test', '1990-01-01', '00000000075', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 76', 'demo76@example.test', '1990-01-01', '00000000076', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 77', 'demo77@example.test', '1990-01-01', '00000000077', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 78', 'demo78@example.test', '1990-01-01', '00000000078', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 79', 'demo79@example.test', '1990-01-01', '00000000079', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 80', 'demo80@example.test', '1990-01-01', '00000000080', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3)
;

INSERT INTO usuarios (Nome, Email, DataNascimento, CPF, Senha, Cargos_idCargos) VALUES
('Demo User 81', 'demo81@example.test', '1990-01-01', '00000000081', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 82', 'demo82@example.test', '1990-01-01', '00000000082', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 83', 'demo83@example.test', '1990-01-01', '00000000083', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 84', 'demo84@example.test', '1990-01-01', '00000000084', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 85', 'demo85@example.test', '1990-01-01', '00000000085', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 86', 'demo86@example.test', '1990-01-01', '00000000086', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 87', 'demo87@example.test', '1990-01-01', '00000000087', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 88', 'demo88@example.test', '1990-01-01', '00000000088', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 89', 'demo89@example.test', '1990-01-01', '00000000089', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 90', 'demo90@example.test', '1990-01-01', '00000000090', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3)
;

INSERT INTO usuarios (Nome, Email, DataNascimento, CPF, Senha, Cargos_idCargos) VALUES
('Demo User 91', 'demo91@example.test', '1990-01-01', '00000000091', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 92', 'demo92@example.test', '1990-01-01', '00000000092', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 93', 'demo93@example.test', '1990-01-01', '00000000093', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 94', 'demo94@example.test', '1990-01-01', '00000000094', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 95', 'demo95@example.test', '1990-01-01', '00000000095', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 96', 'demo96@example.test', '1990-01-01', '00000000096', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 97', 'demo97@example.test', '1990-01-01', '00000000097', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 98', 'demo98@example.test', '1990-01-01', '00000000098', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 99', 'demo99@example.test', '1990-01-01', '00000000099', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3),
('Demo User 100', 'demo100@example.test', '1990-01-01', '00000000100', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNO123456789012', 3)
;
INSERT INTO produtos (Usuarios_idUsuarios, Nome, Descricao, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca, FavoritosCount, TemDesconto, Desconto, CreatedAt) VALUES
(1, 'Apple iPhone 13', 'Smartphone Apple com câmera dupla e bom desempenho.', 19.90, 10, 4.5, 1, 'Apple', 0, 0, NULL, '2025-11-01 10:00:00'),
(2, 'Samsung Galaxy A53', 'Smartphone Samsung médio com tela AMOLED.', 29.90, 5, 4.0, 2, 'Samsung', 0, 1, 10.00, '2025-11-02 10:00:00'),
(3, 'Google Pixel 6a', 'Smartphone Google com câmera computacional avançada.', 39.90, 8, 3.8, 3, 'Google', 0, 0, NULL, '2025-11-03 10:00:00'),
(4, 'Motorola Moto G Power', 'Smartphone com bateria de longa duração.', 49.90, 12, 4.7, 4, 'Motorola', 0, 0, NULL, '2025-11-04 10:00:00'),
(5, 'Xiaomi Redmi Note 11', 'Smartphone com bom custo-benefício.', 59.90, 3, 4.2, 5, 'Xiaomi', 0, 1, 5.00, '2025-11-05 10:00:00'),
(6, 'OnePlus Nord CE', 'Smartphone OnePlus com desempenho equilibrado.', 15.00, 20, 4.1, 6, 'OnePlus', 0, 0, NULL, '2025-11-06 10:00:00'),
(7, 'Sony WH-1000XM4', 'Fone de ouvido com cancelamento de ruído.', 25.00, 7, 3.9, 7, 'Sony', 0, 0, NULL, '2025-11-07 10:00:00'),
(8, 'Bose QuietComfort 35 II', 'Fone de ouvido confortável e equilibrado.', 35.00, 15, 4.3, 8, 'Bose', 0, 0, NULL, '2025-11-08 10:00:00'),
(9, 'JBL Flip 5', 'Caixa de som portátil resistente à água.', 45.00, 6, 4.6, 9, 'JBL', 0, 1, 12.00, '2025-11-09 10:00:00'),
(10, 'Logitech MX Master 3', 'Mouse ergonômico para produtividade.', 55.00, 11, 4.8, 10, 'Logitech', 0, 0, NULL, '2025-11-10 10:00:00')
;

INSERT INTO produtos (Usuarios_idUsuarios, Nome, Descricao, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca, FavoritosCount, TemDesconto, Desconto, CreatedAt) VALUES
(11, 'Apple MacBook Air M1', 'Notebook Apple com chip M1 e ótima bateria.', 65.00, 9, 4.0, 11, 'Apple', 0, 0, NULL, '2025-11-11 10:00:00'),
(12, 'Dell XPS 13', 'Ultrabook compacto e potente.', 75.00, 4, 3.7, 12, 'Dell', 0, 0, NULL, '2025-11-12 10:00:00'),
(13, 'HP Pavilion 15', 'Notebook de uso geral com bom custo.', 85.00, 2, 4.9, 13, 'HP', 0, 1, 20.00, '2025-11-13 10:00:00'),
(14, 'Lenovo ThinkPad E14', 'Notebook empresarial resistente.', 95.00, 14, 4.4, 14, 'Lenovo', 0, 0, NULL, '2025-11-14 10:00:00'),
(15, 'ASUS ROG Strix G15', 'Notebook gamer com GPU dedicada.', 105.00, 1, 3.5, 15, 'ASUS', 0, 0, NULL, '2025-11-15 10:00:00'),
(16, 'Acer Swift 3', 'Notebook fino e leve para produtividade.', 115.00, 18, 4.1, 16, 'Acer', 0, 0, NULL, '2025-11-16 10:00:00'),
(17, 'Samsung 55" QLED TV', 'Televisor QLED 55 polegadas.', 125.00, 13, 4.2, 17, 'Samsung', 0, 0, NULL, '2025-11-17 10:00:00'),
(18, 'LG OLED C1 48"', 'TV OLED 48" com excelente contraste.', 135.00, 17, 4.6, 18, 'LG', 0, 1, 7.50, '2025-11-18 10:00:00'),
(19, 'Sony Bravia X90J 55"', 'TV LED 55" com boa qualidade de imagem.', 145.00, 16, 4.7, 19, 'Sony', 0, 0, NULL, '2025-11-19 10:00:00'),
(20, 'Amazon Fire TV Stick 4K', 'Player de streaming 4K.', 155.00, 19, 4.9, 20, 'Amazon', 0, 0, NULL, '2025-11-20 10:00:00')
;

INSERT INTO produtos (Usuarios_idUsuarios, Nome, Descricao, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca, FavoritosCount, TemDesconto, Desconto, CreatedAt) VALUES
(1, 'Nintendo Switch', 'Console híbrido para jogos.', 21.00, 10, 3.6, 1, 'Nintendo', 0, 0, NULL, '2025-11-21 10:00:00'),
(2, 'Sony PlayStation 5', 'Console de próxima geração Sony.', 22.00, 11, 4.1, 2, 'Sony', 0, 0, NULL, '2025-11-22 10:00:00'),
(3, 'Microsoft Xbox Series S', 'Console da Microsoft versão compacta.', 23.00, 12, 3.9, 3, 'Microsoft', 0, 1, 9.00, '2025-11-23 10:00:00'),
(4, 'Canon EOS R6', 'Câmera mirrorless full-frame.', 24.00, 13, 4.3, 4, 'Canon', 0, 0, NULL, '2025-11-24 10:00:00'),
(5, 'Nikon D3500', 'DSLR de entrada para fotografia.', 25.00, 14, 4.0, 5, 'Nikon', 0, 0, NULL, '2025-11-25 10:00:00'),
(6, 'GoPro HERO9 Black', 'Action cam resistente e 5K.', 26.00, 15, 4.2, 6, 'GoPro', 0, 0, NULL, '2025-11-26 10:00:00'),
(7, 'DJI Mini 2', 'Drone compacto com boa estabilização.', 27.00, 16, 4.4, 7, 'DJI', 0, 0, NULL, '2025-11-27 10:00:00'),
(8, 'Fitbit Versa 3', 'Smartwatch focado em fitness.', 28.00, 17, 4.5, 8, 'Fitbit', 0, 1, 15.00, '2025-11-28 10:00:00'),
(9, 'Apple Watch SE', 'Smartwatch Apple com recursos básicos.', 29.00, 18, 3.8, 9, 'Apple', 0, 0, NULL, '2025-11-29 10:00:00'),
(10, 'Samsung Galaxy Watch4', 'Smartwatch com Wear OS.', 30.00, 19, 4.6, 10, 'Samsung', 0, 0, NULL, '2025-11-30 10:00:00')
;

INSERT INTO produtos (Usuarios_idUsuarios, Nome, Descricao, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca, FavoritosCount, TemDesconto, Desconto, CreatedAt) VALUES
(11, 'Anker PowerCore 10000', 'Power bank portátil 10000mAh.', 31.00, 20, 4.7, 11, 'Anker', 0, 0, NULL, '2025-12-01 10:00:00'),
(12, 'SanDisk Extreme Portable SSD 1TB', 'SSD externo rápido e resistente.', 32.00, 21, 4.8, 12, 'SanDisk', 0, 0, NULL, '2025-12-02 10:00:00'),
(13, 'Samsung 970 EVO Plus 500GB', 'SSD NVMe interno de alto desempenho.', 33.00, 22, 4.1, 13, 'Samsung', 0, 0, NULL, '2025-12-03 10:00:00'),
(14, 'WD Blue 2TB HDD', 'HD interno para armazenamento.', 34.00, 23, 3.9, 14, 'Western Digital', 0, 1, 6.50, '2025-12-04 10:00:00'),
(15, 'Seagate Portable 4TB', 'HD externo para backup.', 35.00, 24, 4.0, 15, 'Seagate', 0, 0, NULL, '2025-12-05 10:00:00'),
(16, 'Kingston DataTraveler 64GB', 'Pen drive USB 3.0.', 36.00, 25, 4.2, 16, 'Kingston', 0, 0, NULL, '2025-12-06 10:00:00'),
(17, 'Razer BlackWidow', 'Teclado mecânico para jogos.', 37.00, 26, 4.3, 17, 'Razer', 0, 0, NULL, '2025-12-07 10:00:00'),
(18, 'Corsair K95 RGB Platinum', 'Teclado mecânico premium.', 38.00, 27, 4.4, 18, 'Corsair', 0, 1, 11.00, '2025-12-08 10:00:00'),
(19, 'HyperX Cloud II', 'Headset para jogos com microfone.', 39.00, 28, 3.6, 19, 'HyperX', 0, 0, NULL, '2025-12-09 10:00:00'),
(20, 'Philips Hue Starter Kit', 'Kit inicial de lâmpadas inteligentes.', 40.00, 29, 4.6, 20, 'Philips', 0, 0, NULL, '2025-12-10 10:00:00')
;

INSERT INTO produtos (Usuarios_idUsuarios, Nome, Descricao, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca, FavoritosCount, TemDesconto, Desconto, CreatedAt) VALUES
(1, 'Ring Video Doorbell 3', 'Campainha com câmera e notificações.', 41.00, 30, 4.7, 1, 'Ring', 0, 0, NULL, '2025-12-11 10:00:00'),
(2, 'TP-Link Archer AX50', 'Roteador Wi-Fi 6 de alto desempenho.', 42.00, 31, 4.8, 2, 'TP-Link', 0, 0, NULL, '2025-12-12 10:00:00'),
(3, 'Netgear Nighthawk R7000', 'Roteador com bom alcance e configurações.', 43.00, 32, 3.7, 3, 'Netgear', 0, 1, 5.00, '2025-12-13 10:00:00'),
(4, 'Belkin SurgePlus', 'Protetor de surto para eletrônicos.', 44.00, 33, 4.0, 4, 'Belkin', 0, 0, NULL, '2025-12-14 10:00:00'),
(5, 'Microsoft Surface Pro 7', 'Tablet 2-em-1 com performance híbrida.', 45.00, 34, 4.2, 5, 'Microsoft', 0, 0, NULL, '2025-12-15 10:00:00'),
(6, 'Google Nest Mini', 'Alto-falante inteligente compacto.', 46.00, 35, 4.3, 6, 'Google', 0, 0, NULL, '2025-12-16 10:00:00'),
(7, 'Amazon Echo Dot', 'Assistente de voz compacto Alexa.', 47.00, 36, 4.4, 7, 'Amazon', 0, 1, 8.00, '2025-12-17 10:00:00'),
(8, 'Bose SoundLink Mini II', 'Caixa de som Bluetooth portátil.', 48.00, 37, 4.5, 8, 'Bose', 0, 0, NULL, '2025-12-18 10:00:00'),
(9, 'Sonos One', 'Caixa de som inteligente com som rico.', 49.00, 38, 3.8, 9, 'Sonos', 0, 0, NULL, '2025-12-19 10:00:00'),
(10, 'Canon PIXMA TS5320', 'Impressora jato de tinta doméstica.', 50.00, 39, 4.6, 10, 'Canon', 0, 0, NULL, '2025-12-20 10:00:00')
;

INSERT INTO produtos (Usuarios_idUsuarios, Nome, Descricao, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca, FavoritosCount, TemDesconto, Desconto, CreatedAt) VALUES
(11, 'Brother HL-L2350DW', 'Impressora mono a laser compacta.', 51.00, 40, 4.7, 11, 'Brother', 0, 1, 14.00, '2025-12-21 10:00:00'),
(12, 'Epson EcoTank L3150', 'Impressora tanque de tinta econômica.', 52.00, 41, 4.8, 12, 'Epson', 0, 0, NULL, '2025-12-22 10:00:00'),
(13, 'Logitech C920', 'Webcam Full HD para streaming.', 53.00, 42, 4.1, 13, 'Logitech', 0, 0, NULL, '2025-12-23 10:00:00'),
(14, 'Blue Yeti', 'Microfone USB para gravação e streaming.', 54.00, 43, 3.9, 14, 'Blue', 0, 0, NULL, '2025-12-24 10:00:00'),
(15, 'Sennheiser HD 599', 'Fone aberto com ótima qualidade sonora.', 55.00, 44, 4.0, 15, 'Sennheiser', 0, 1, 3.50, '2025-12-25 10:00:00'),
(16, 'Beats Solo3', 'Fone on-ear com bateria duradoura.', 56.00, 45, 4.2, 16, 'Beats', 0, 0, NULL, '2025-12-26 10:00:00'),
(17, 'Xiaomi Mi Band 6', 'Pulseira inteligente com monitoramento.', 57.00, 46, 4.3, 17, 'Xiaomi', 0, 0, NULL, '2025-12-27 10:00:00'),
(18, 'Huawei P30 Lite', 'Smartphone com boa câmera para fotos.', 58.00, 47, 4.4, 18, 'Huawei', 0, 0, NULL, '2025-12-28 10:00:00'),
(19, 'Oppo Find X3 Neo', 'Smartphone com design premium.', 59.00, 48, 3.6, 19, 'Oppo', 0, 1, 4.00, '2025-12-29 10:00:00'),
(20, 'Realme 8 Pro', 'Smartphone com câmera de alta resolução.', 60.00, 49, 4.6, 20, 'Realme', 0, 0, NULL, '2025-12-30 10:00:00')
;

INSERT INTO produtos (Usuarios_idUsuarios, Nome, Descricao, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca, FavoritosCount, TemDesconto, Desconto, CreatedAt) VALUES
(1, 'TCL 6-Series Roku TV', 'TV com boa relação custo/benefício.', 61.00, 50, 4.7, 1, 'TCL', 0, 0, NULL, '2025-12-31 10:00:00'),
(2, 'Hisense U8G', 'TV com desempenho de alto nível.', 62.00, 51, 4.8, 2, 'Hisense', 0, 0, NULL, '2026-01-01 10:00:00'),
(3, 'JBL Charge 4', 'Caixa de som potente com bateria.', 63.00, 52, 3.7, 3, 'JBL', 0, 1, 6.00, '2026-01-02 10:00:00'),
(4, 'Skullcandy Crusher', 'Fones com graves reforçados.', 64.00, 53, 4.4, 4, 'Skullcandy', 0, 0, NULL, '2026-01-03 10:00:00'),
(5, 'Dyson V11', 'Aspirador sem fio com boa sucção.', 65.00, 54, 4.5, 5, 'Dyson', 0, 0, NULL, '2026-01-04 10:00:00'),
(6, 'iRobot Roomba 675', 'Robô aspirador básico e eficiente.', 66.00, 55, 4.2, 6, 'iRobot', 0, 0, NULL, '2026-01-05 10:00:00'),
(7, 'Philips Airfryer', 'Airfryer para cozinhar com menos óleo.', 67.00, 56, 4.3, 7, 'Philips', 0, 0, NULL, '2026-01-06 10:00:00'),
(8, 'Ninja Foodi', 'Panela multifunção com várias funções.', 68.00, 57, 4.4, 8, 'Ninja', 0, 0, NULL, '2026-01-07 10:00:00'),
(9, 'Instant Pot Duo', 'Panela elétrica multifuncional 7 em 1.', 69.00, 58, 4.6, 9, 'Instant Pot', 0, 1, 2.50, '2026-01-08 10:00:00'),
(10, 'KitchenAid Stand Mixer', 'Batedeira profissional para cozinha.', 70.00, 59, 4.0, 10, 'KitchenAid', 0, 0, NULL, '2026-01-09 10:00:00')
;

INSERT INTO produtos (Usuarios_idUsuarios, Nome, Descricao, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca, FavoritosCount, TemDesconto, Desconto, CreatedAt) VALUES
(11, 'Bosch Series 6 Dishwasher', 'Máquina de lavar louças eficiente.', 71.00, 60, 4.7, 11, 'Bosch', 0, 0, NULL, '2026-01-10 10:00:00'),
(12, 'Garmin Forerunner 245', 'Relógio GPS para corrida e fitness.', 72.00, 61, 4.8, 12, 'Garmin', 0, 1, 18.00, '2026-01-11 10:00:00'),
(13, 'Suunto 9 Peak', 'Relógio esportivo com longa bateria.', 73.00, 62, 4.1, 13, 'Suunto', 0, 0, NULL, '2026-01-12 10:00:00'),
(14, 'Acer Predator Helios 300', 'Notebook gamer com GPU potente.', 74.00, 63, 3.7, 14, 'Acer', 0, 0, NULL, '2026-01-13 10:00:00'),
(15, 'MSI GF63 Thin', 'Notebook gamer acessível.', 75.00, 64, 4.5, 15, 'MSI', 0, 0, NULL, '2026-01-14 10:00:00'),
(16, 'ASUS TUF Gaming A15', 'Notebook gamer robusto e durável.', 76.00, 65, 4.2, 16, 'ASUS', 0, 0, NULL, '2026-01-15 10:00:00'),
(17, 'BenQ 27" Monitor', 'Monitor 27" para designers e gamers.', 77.00, 66, 4.3, 17, 'BenQ', 0, 1, 22.00, '2026-01-16 10:00:00'),
(18, 'LG UltraGear 27GN950', 'Monitor gaming 4K 27".', 78.00, 67, 4.4, 18, 'LG', 0, 0, NULL, '2026-01-17 10:00:00'),
(19, 'ViewSonic 24" IPS Monitor', 'Monitor IPS 24 polegadas.', 79.00, 68, 3.6, 19, 'ViewSonic', 0, 0, NULL, '2026-01-18 10:00:00'),
(20, 'TP-Link Deco Mesh', 'Sistema Wi-Fi mesh para cobertura ampla.', 80.00, 69, 4.0, 20, 'TP-Link', 0, 0, NULL, '2026-01-19 10:00:00')
;

INSERT INTO produtos (Usuarios_idUsuarios, Nome, Descricao, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca, FavoritosCount, TemDesconto, Desconto, CreatedAt) VALUES
(1, 'Ubiquiti UniFi AP AC Lite', 'Ponto de acesso profissional para redes.', 81.00, 70, 4.1, 1, 'Ubiquiti', 0, 0, NULL, '2026-01-20 10:00:00'),
(2, 'Netatmo Weather Station', 'Estação meteorológica doméstica inteligente.', 82.00, 71, 4.2, 2, 'Netatmo', 0, 0, NULL, '2026-01-21 10:00:00'),
(3, 'Ring Alarm Kit', 'Kit de segurança doméstica completo.', 83.00, 72, 4.3, 3, 'Ring', 0, 1, 6.00, '2026-01-22 10:00:00'),
(4, 'August Smart Lock Pro', 'Fechadura inteligente com integração.', 84.00, 73, 4.4, 4, 'August', 0, 0, NULL, '2026-01-23 10:00:00'),
(5, 'Wyze Cam v3', 'Câmera de segurança acessível e versátil.', 85.00, 74, 4.5, 5, 'Wyze', 0, 0, NULL, '2026-01-24 10:00:00'),
(6, 'Eufy Security 2K', 'Câmera de segurança 2K com bateria.', 86.00, 75, 3.8, 6, 'Eufy', 0, 0, NULL, '2026-01-25 10:00:00'),
(7, 'TP-Link Kasa Smart Plug', 'Tomada inteligente controlada por app.', 87.00, 76, 4.6, 7, 'TP-Link', 0, 0, NULL, '2026-01-26 10:00:00'),
(8, 'Philips OneBlade', 'Aparador híbrido para barba e cabelo.', 88.00, 77, 4.7, 8, 'Philips', 0, 0, NULL, '2026-01-27 10:00:00'),
(9, 'Braun Series 7', 'Aparelho de barbear elétrico premium.', 89.00, 78, 4.8, 9, 'Braun', 0, 1, 9.50, '2026-01-28 10:00:00'),
(10, 'Oral-B Pro 2 2000N', 'Escova elétrica recarregável.', 90.00, 79, 4.9, 10, 'Oral-B', 0, 0, NULL, '2026-01-29 10:00:00')
;

INSERT INTO produtos (Usuarios_idUsuarios, Nome, Descricao, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca, FavoritosCount, TemDesconto, Desconto, CreatedAt) VALUES
(11, 'Xiaomi Mi Electric Scooter', 'Patinete elétrico para deslocamento urbano.', 91.00, 80, 4.2, 11, 'Xiaomi', 0, 0, NULL, '2026-01-30 10:00:00'),
(12, 'Segway Ninebot ES2', 'Patinete elétrico com boa autonomia.', 92.00, 81, 3.9, 12, 'Segway', 0, 0, NULL, '2026-01-31 10:00:00'),
(13, 'Fitbit Charge 5', 'Pulseira inteligente com sensores avançados.', 93.00, 82, 4.0, 13, 'Fitbit', 0, 1, 2.00, '2026-02-01 10:00:00'),
(14, 'Samsung Galaxy Buds Pro', 'Fones TWS com cancelamento de ruído.', 94.00, 83, 4.1, 14, 'Samsung', 0, 0, NULL, '2026-02-02 10:00:00'),
(15, 'Apple AirPods Pro', 'Fones True Wireless com ANC.', 95.00, 84, 4.3, 15, 'Apple', 0, 0, NULL, '2026-02-03 10:00:00'),
(16, 'Xbox Wireless Controller', 'Controle sem fio para Xbox e PC.', 96.00, 85, 4.4, 16, 'Microsoft', 0, 0, NULL, '2026-02-04 10:00:00'),
(17, 'Sony DualSense Controller', 'Controle com feedback háptico para PS5.', 97.00, 86, 3.5, 17, 'Sony', 0, 1, 7.00, '2026-02-05 10:00:00'),
(18, 'Logitech G502 HERO', 'Mouse gamer com sensor de alta precisão.', 98.00, 87, 4.0, 18, 'Logitech', 0, 0, NULL, '2026-02-06 10:00:00'),
(19, 'TP-Link USB WiFi Adapter', 'Adaptador USB para conexão sem fio.', 99.00, 88, 4.2, 19, 'TP-Link', 0, 0, NULL, '2026-02-07 10:00:00'),
(20, 'JBL Bar 5.1', 'Soundbar com som surround 5.1 integrado.', 100.00, 90, 4.6, 20, 'JBL', 0, 0, NULL, '2026-02-08 10:00:00')
;
INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES
('https://picsum.photos/seed/product_1/800/600', 1),
('https://picsum.photos/seed/product_2/800/600', 2),
('https://picsum.photos/seed/product_3/800/600', 3),
('https://picsum.photos/seed/product_4/800/600', 4),
('https://picsum.photos/seed/product_5/800/600', 5),
('https://picsum.photos/seed/product_6/800/600', 6),
('https://picsum.photos/seed/product_7/800/600', 7),
('https://picsum.photos/seed/product_8/800/600', 8),
('https://picsum.photos/seed/product_9/800/600', 9),
('https://picsum.photos/seed/product_10/800/600', 10)
;

INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES
('https://picsum.photos/seed/product_11/800/600', 11),
('https://picsum.photos/seed/product_12/800/600', 12),
('https://picsum.photos/seed/product_13/800/600', 13),
('https://picsum.photos/seed/product_14/800/600', 14),
('https://picsum.photos/seed/product_15/800/600', 15),
('https://picsum.photos/seed/product_16/800/600', 16),
('https://picsum.photos/seed/product_17/800/600', 17),
('https://picsum.photos/seed/product_18/800/600', 18),
('https://picsum.photos/seed/product_19/800/600', 19),
('https://picsum.photos/seed/product_20/800/600', 20)
;

INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES
('https://picsum.photos/seed/product_21/800/600', 21),
('https://picsum.photos/seed/product_22/800/600', 22),
('https://picsum.photos/seed/product_23/800/600', 23),
('https://picsum.photos/seed/product_24/800/600', 24),
('https://picsum.photos/seed/product_25/800/600', 25),
('https://picsum.photos/seed/product_26/800/600', 26),
('https://picsum.photos/seed/product_27/800/600', 27),
('https://picsum.photos/seed/product_28/800/600', 28),
('https://picsum.photos/seed/product_29/800/600', 29),
('https://picsum.photos/seed/product_30/800/600', 30)
;

INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES
('https://picsum.photos/seed/product_31/800/600', 31),
('https://picsum.photos/seed/product_32/800/600', 32),
('https://picsum.photos/seed/product_33/800/600', 33),
('https://picsum.photos/seed/product_34/800/600', 34),
('https://picsum.photos/seed/product_35/800/600', 35),
('https://picsum.photos/seed/product_36/800/600', 36),
('https://picsum.photos/seed/product_37/800/600', 37),
('https://picsum.photos/seed/product_38/800/600', 38),
('https://picsum.photos/seed/product_39/800/600', 39),
('https://picsum.photos/seed/product_40/800/600', 40)
;

INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES
('https://picsum.photos/seed/product_41/800/600', 41),
('https://picsum.photos/seed/product_42/800/600', 42),
('https://picsum.photos/seed/product_43/800/600', 43),
('https://picsum.photos/seed/product_44/800/600', 44),
('https://picsum.photos/seed/product_45/800/600', 45),
('https://picsum.photos/seed/product_46/800/600', 46),
('https://picsum.photos/seed/product_47/800/600', 47),
('https://picsum.photos/seed/product_48/800/600', 48),
('https://picsum.photos/seed/product_49/800/600', 49),
('https://picsum.photos/seed/product_50/800/600', 50)
;

INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES
('https://picsum.photos/seed/product_51/800/600', 51),
('https://picsum.photos/seed/product_52/800/600', 52),
('https://picsum.photos/seed/product_53/800/600', 53),
('https://picsum.photos/seed/product_54/800/600', 54),
('https://picsum.photos/seed/product_55/800/600', 55),
('https://picsum.photos/seed/product_56/800/600', 56),
('https://picsum.photos/seed/product_57/800/600', 57),
('https://picsum.photos/seed/product_58/800/600', 58),
('https://picsum.photos/seed/product_59/800/600', 59),
('https://picsum.photos/seed/product_60/800/600', 60)
;

INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES
('https://picsum.photos/seed/product_61/800/600', 61),
('https://picsum.photos/seed/product_62/800/600', 62),
('https://picsum.photos/seed/product_63/800/600', 63),
('https://picsum.photos/seed/product_64/800/600', 64),
('https://picsum.photos/seed/product_65/800/600', 65),
('https://picsum.photos/seed/product_66/800/600', 66),
('https://picsum.photos/seed/product_67/800/600', 67),
('https://picsum.photos/seed/product_68/800/600', 68),
('https://picsum.photos/seed/product_69/800/600', 69),
('https://picsum.photos/seed/product_70/800/600', 70)
;

INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES
('https://picsum.photos/seed/product_71/800/600', 71),
('https://picsum.photos/seed/product_72/800/600', 72),
('https://picsum.photos/seed/product_73/800/600', 73),
('https://picsum.photos/seed/product_74/800/600', 74),
('https://picsum.photos/seed/product_75/800/600', 75),
('https://picsum.photos/seed/product_76/800/600', 76),
('https://picsum.photos/seed/product_77/800/600', 77),
('https://picsum.photos/seed/product_78/800/600', 78),
('https://picsum.photos/seed/product_79/800/600', 79),
('https://picsum.photos/seed/product_80/800/600', 80)
;

INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES
('https://picsum.photos/seed/product_81/800/600', 81),
('https://picsum.photos/seed/product_82/800/600', 82),
('https://picsum.photos/seed/product_83/800/600', 83),
('https://picsum.photos/seed/product_84/800/600', 84),
('https://picsum.photos/seed/product_85/800/600', 85),
('https://picsum.photos/seed/product_86/800/600', 86),
('https://picsum.photos/seed/product_87/800/600', 87),
('https://picsum.photos/seed/product_88/800/600', 88),
('https://picsum.photos/seed/product_89/800/600', 89),
('https://picsum.photos/seed/product_90/800/600', 90)
;

INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES
('https://picsum.photos/seed/product_91/800/600', 91),
('https://picsum.photos/seed/product_92/800/600', 92),
('https://picsum.photos/seed/product_93/800/600', 93),
('https://picsum.photos/seed/product_94/800/600', 94),
('https://picsum.photos/seed/product_95/800/600', 95),
('https://picsum.photos/seed/product_96/800/600', 96),
('https://picsum.photos/seed/product_97/800/600', 97),
('https://picsum.photos/seed/product_98/800/600', 98),
('https://picsum.photos/seed/product_99/800/600', 99),
('https://picsum.photos/seed/product_100/800/600', 100)
;

SET FOREIGN_KEY_CHECKS = 1;
