<?php
// Endpoint to handle AJAX product edits (currently: image removal)
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/connections/conectarBD.php';

$action = $_GET['action'] ?? null;
try {
    if ($action !== 'remover_imagem') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'action_not_supported']);
        exit;
    }

    if (empty($_SESSION['id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'not_authenticated']);
        exit;
    }

    // read POST body (form-encoded)
    $id = isset($_POST['idEnderecoImagem']) ? (int) $_POST['idEnderecoImagem'] : 0;
    if ($id < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'invalid_id']);
        exit;
    }

    // find the product id for this image
    $q = $conexao->prepare('SELECT Produtos_idProdutos, ImagemUrl FROM enderecoimagem WHERE idEnderecoImagem = :id LIMIT 1');
    $q->execute([':id' => $id]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'not_found']);
        exit;
    }
    $prodId = (int) $row['Produtos_idProdutos'];

    // verify ownership
    $p = $conexao->prepare('SELECT Usuarios_idUsuarios FROM produtos WHERE idProdutos = :pid LIMIT 1');
    $p->execute([':pid' => $prodId]);
    $pr = $p->fetch(PDO::FETCH_ASSOC);
    if (!$pr) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'product_not_found']);
        exit;
    }
    $ownerId = (int) $pr['Usuarios_idUsuarios'];
    if ($ownerId !== (int) $_SESSION['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'not_owner']);
        exit;
    }

    // delete the image row, and attempt to unlink file if it's a path under uploads/
    try {
        $conexao->beginTransaction();
        $del = $conexao->prepare('DELETE FROM enderecoimagem WHERE idEnderecoImagem = :id');
        $del->execute([':id' => $id]);
        $conexao->commit();
    } catch (Exception $ex) {
        if ($conexao->inTransaction())
            $conexao->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'db_error', 'message' => $ex->getMessage()]);
        exit;
    }

    // attempt to remove file if ImagemUrl looks like a path
    if (!empty($row['ImagemUrl']) && is_string($row['ImagemUrl'])) {
        $trim = trim($row['ImagemUrl']);
        if (strpos($trim, 'uploads/') === 0) {
            $full = __DIR__ . '/' . $trim;
            if (is_file($full))
                @unlink($full);
            // try thumbnail variant
            $thumb = preg_replace('/(\.[^.]+)$/', '_thumb$1', $full);
            if (is_file($thumb))
                @unlink($thumb);
        }
    }

    echo json_encode(['success' => true]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'exception', 'message' => $e->getMessage()]);
    exit;
}

?>