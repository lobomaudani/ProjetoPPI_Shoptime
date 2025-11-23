<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/connections/conectarBD.php';

if (empty($_SESSION['id'])) {
    echo json_encode(['ok' => false, 'error' => 'not_logged', 'count' => 0]);
    exit;
}
$me = (int) $_SESSION['id'];
try {
    $stmt = $conexao->prepare('SELECT COUNT(1) AS cnt FROM mensagens WHERE ParaUsuarios_idUsuarios = :me AND Lida = 0');
    $stmt->execute([':me' => $me]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = $row ? (int) $row['cnt'] : 0;
    echo json_encode(['ok' => true, 'count' => $count]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'count' => 0]);
}

?>