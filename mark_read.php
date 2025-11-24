<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/connections/conectarBD.php';

if (empty($_SESSION['id'])) {
    echo json_encode(['ok' => false, 'error' => 'not_logged']);
    exit;
}
$me = (int) $_SESSION['id'];
$csrf = $_POST['csrf_token'] ?? '';
if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['ok' => false, 'error' => 'bad_csrf']);
    exit;
}
$produto = isset($_POST['produto_id']) ? (int) $_POST['produto_id'] : null;
$other = isset($_POST['other_user_id']) ? (int) $_POST['other_user_id'] : null;

if (!$other || !$produto) {
    echo json_encode(['ok' => false, 'error' => 'missing_params']);
    exit;
}

try {
    $stmt = $conexao->prepare('UPDATE mensagens SET Lida = 1 WHERE ParaUsuarios_idUsuarios = :me AND DeUsuarios_idUsuarios = :other AND Produtos_idProdutos = :pid AND Lida = 0');
    $stmt->execute([':me' => $me, ':other' => $other, ':pid' => $produto]);
    $updated = $stmt->rowCount();
    echo json_encode(['ok' => true, 'updated' => $updated]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

?>