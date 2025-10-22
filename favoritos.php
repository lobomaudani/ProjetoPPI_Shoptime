<?php
// Endpoint para toggle de favoritos (POST)
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once 'connections/conectarBD.php';

$action = $_POST['action'] ?? '';
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
if ($action !== 'toggle_fav' || $productId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid_request']);
    exit;
}

if (empty($_SESSION['id'])) {
    echo json_encode(['ok' => false, 'error' => 'not_logged']);
    exit;
}

$userId = (int) $_SESSION['id'];

try {
    // criar tabela favorites se necessário
    $conexao->exec("CREATE TABLE IF NOT EXISTS favoritos (
        Usuarios_idUsuarios INT NOT NULL,
        Produtos_idProdutos INT NOT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (Usuarios_idUsuarios, Produtos_idProdutos),
        INDEX fk_Fav_Usuarios (Usuarios_idUsuarios),
        INDEX fk_Fav_Produtos (Produtos_idProdutos)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // adicionar coluna contador em produtos (não falha se já existir em MySQL 8+)
    try {
        $conexao->exec("ALTER TABLE produtos ADD COLUMN IF NOT EXISTS FavoritosCount INT NOT NULL DEFAULT 0");
    } catch (Exception $e) {
        // ok, continue
    }

    // toggle
    $check = $conexao->prepare('SELECT 1 FROM favoritos WHERE Usuarios_idUsuarios = :uid AND Produtos_idProdutos = :pid');
    $check->execute([':uid' => $userId, ':pid' => $productId]);
    if ($check->fetchColumn()) {
        $del = $conexao->prepare('DELETE FROM favoritos WHERE Usuarios_idUsuarios = :uid AND Produtos_idProdutos = :pid');
        $del->execute([':uid' => $userId, ':pid' => $productId]);
        echo json_encode(['ok' => true, 'favorited' => false]);
        exit;
    } else {
        $ins = $conexao->prepare('INSERT INTO favoritos (Usuarios_idUsuarios, Produtos_idProdutos) VALUES (:uid, :pid)');
        $ins->execute([':uid' => $userId, ':pid' => $productId]);
        echo json_encode(['ok' => true, 'favorited' => true]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
}

