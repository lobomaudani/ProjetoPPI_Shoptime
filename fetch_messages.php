<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/connections/conectarBD.php';

if (empty($_SESSION['id'])) {
    echo json_encode(['ok' => false, 'error' => 'not_logged']);
    exit;
}
$me = (int) $_SESSION['id'];
$produto = isset($_GET['produto_id']) ? (int) $_GET['produto_id'] : null;
$other = isset($_GET['other_user_id']) ? (int) $_GET['other_user_id'] : null;
$since_id = isset($_GET['since_id']) ? (int) $_GET['since_id'] : 0;
$limit = isset($_GET['limit']) ? min(200, (int) $_GET['limit']) : 200;

if (!$other && !$produto) {
    echo json_encode(['ok' => false, 'error' => 'missing_params']);
    exit;
}

try {
    // fetch messages between me and other for product (if produto provided)
    $sql = 'SELECT m.*, u.Nome AS deNome FROM mensagens m LEFT JOIN usuarios u ON u.idUsuarios = m.DeUsuarios_idUsuarios WHERE (' .
        ' (m.DeUsuarios_idUsuarios = :me AND m.ParaUsuarios_idUsuarios = :other) OR (m.DeUsuarios_idUsuarios = :other AND m.ParaUsuarios_idUsuarios = :me) )';
    $params = [':me' => $me, ':other' => $other];
    if ($produto) {
        $sql .= ' AND (m.Produtos_idProdutos = :pid)';
        $params[':pid'] = $produto;
    }
    if ($since_id > 0) {
        $sql .= ' AND m.id > :since_id';
        $params[':since_id'] = $since_id;
    }
    $sql .= ' ORDER BY m.id ASC LIMIT ' . intval($limit);
    $stmt = $conexao->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'messages' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

?>