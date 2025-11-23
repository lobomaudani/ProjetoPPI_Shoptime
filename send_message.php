<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/connections/conectarBD.php';

// require login
if (empty($_SESSION['id'])) {
    echo json_encode(['ok' => false, 'error' => 'not_logged']);
    exit;
}
$de = (int) $_SESSION['id'];
$produto = isset($_POST['produto_id']) ? (int) $_POST['produto_id'] : null;
$para = isset($_POST['to_user_id']) ? (int) $_POST['to_user_id'] : null;
$mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';

if (!$para || !$mensagem) {
    echo json_encode(['ok' => false, 'error' => 'missing_fields']);
    exit;
}

try {
    $ins = $conexao->prepare('INSERT INTO mensagens (Produtos_idProdutos, DeUsuarios_idUsuarios, ParaUsuarios_idUsuarios, Mensagem) VALUES (:pid, :de, :para, :msg)');
    $ins->execute([':pid' => $produto, ':de' => $de, ':para' => $para, ':msg' => $mensagem]);
    $id = (int) $conexao->lastInsertId();
    // return inserted row
    $stmt = $conexao->prepare('SELECT m.*, u.Nome AS deNome FROM mensagens m LEFT JOIN usuarios u ON u.idUsuarios = m.DeUsuarios_idUsuarios WHERE m.id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'message' => $row]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

?>