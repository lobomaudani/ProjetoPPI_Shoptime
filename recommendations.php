<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/connections/conectarBD.php';
include_once __DIR__ . '/includes/recommender.php';

$limit = isset($_GET['limit']) ? max(1, min(50, (int) $_GET['limit'])) : 8;
$pid = isset($_GET['pid']) ? (int) $_GET['pid'] : null;
$uid = !empty($_SESSION['id']) ? (int) $_SESSION['id'] : null;

try {
    $recs = get_recommendations($conexao, $uid, $limit, $pid);

    // fallback: if recommender returned fewer than requested, add random products
    if (count($recs) < $limit) {
        $needed = $limit - count($recs);
        $excludeIds = array_map(function ($r) {
            return (int) $r['idProdutos'];
        }, $recs);
        if ($excludeIds) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $sql = "SELECT p.*, (SELECT enderecoimagem.ImagemUrl FROM enderecoimagem WHERE enderecoimagem.Produtos_idProdutos = p.idProdutos LIMIT 1) AS thumb
                FROM produtos p WHERE p.idProdutos NOT IN ($placeholders) ORDER BY RAND() LIMIT ?";
            $stmt = $conexao->prepare($sql);
            $i = 1;
            foreach ($excludeIds as $id) {
                $stmt->bindValue($i++, $id, PDO::PARAM_INT);
            }
            $stmt->bindValue($i, $needed, PDO::PARAM_INT);
        } else {
            $sql = "SELECT p.*, (SELECT enderecoimagem.ImagemUrl FROM enderecoimagem WHERE enderecoimagem.Produtos_idProdutos = p.idProdutos LIMIT 1) AS thumb
                FROM produtos p WHERE p.idProdutos NOT IN (0) ORDER BY RAND() LIMIT ?";
            $stmt = $conexao->prepare($sql);
            $stmt->bindValue(1, $needed, PDO::PARAM_INT);
        }
        $stmt->execute();
        $more = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($more as $m) {
            $recs[] = $m;
        }
    }

    $out = [];
    foreach ($recs as $p) {
        $thumb = empty($p['ImagemUrl']) ? ($p['thumb'] ?? 'images/no-image.png') : ('serve_imagem.php?pid=' . (int) $p['idProdutos']);
        $orig = isset($p['Preco']) ? (float) $p['Preco'] : null;
        $disc = null;
        $hasDisc = false;
        if (array_key_exists('Desconto', $p) && $p['Desconto'] !== null && $p['Desconto'] !== '')
            $disc = (float) $p['Desconto'];
        if (array_key_exists('desconto', $p) && $p['desconto'] !== null && $p['desconto'] !== '')
            $disc = $disc ?? (float) $p['desconto'];
        if (array_key_exists('TemDesconto', $p) && $p['TemDesconto'])
            $hasDisc = true;
        if (array_key_exists('tem_desconto', $p) && $p['tem_desconto'])
            $hasDisc = true;
        if ($disc !== null && $disc > 0)
            $hasDisc = true;
        $discounted = null;
        if ($hasDisc && $orig !== null && $disc !== null)
            $discounted = round($orig * (1 - ($disc / 100)), 2);

        $out[] = [
            'id' => (int) $p['idProdutos'],
            'nome' => $p['Nome'],
            'preco' => $orig,
            'preco_descontado' => $discounted,
            'desconto' => $disc,
            'thumb' => $thumb
        ];
    }
    echo json_encode(['ok' => true, 'results' => $out], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
