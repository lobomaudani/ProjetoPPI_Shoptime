<?php
require_once 'conectarBD.php';

try {
    // Criar a tabela usuarios se não existir
    $sql = "CREATE TABLE IF NOT EXISTS usuarios (
        idUsuarios INT PRIMARY KEY AUTO_INCREMENT,
        Nome VARCHAR(255) NOT NULL,
        Email VARCHAR(255) NOT NULL UNIQUE,
        Senha VARCHAR(255) NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        alterado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    $conexao->exec($sql);
    echo "Tabela usuarios criada/verificada com sucesso\n";

} catch (PDOException $e) {
    die("Erro ao criar tabela usuarios: " . $e->getMessage());
}