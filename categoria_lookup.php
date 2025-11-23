<?php
// Simple JSON endpoint that returns categories for the header lookup
header('Content-Type: application/json; charset=utf-8');
try {
    include_once __DIR__ . '/connections/conectarBD.php';
    $stmt = $conexao->query('SELECT idCategorias AS id, Nome AS nome FROM categorias ORDER BY Nome ASC');
    $cats = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    echo json_encode(['ok' => true, 'categories' => $cats]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
