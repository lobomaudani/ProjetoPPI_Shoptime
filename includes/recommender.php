<?php
// Recommender helper: simple, fast, DB-driven recommendations combining
// user favorites, recent searches (session), and user's visits. Also
// boosts discounted and popular items.

function describeProdutoCols($conexao)
{
    $cols = [];
    try {
        $rows = $conexao->query("DESCRIBE produtos")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r)
            $cols[] = $r['Field'];
    } catch (Exception $e) {
        // ignore
    }
    return $cols;
}

function fetchProductsByIds($conexao, $ids)
{
    if (empty($ids))
        return [];
    $prodCols = describeProdutoCols($conexao);
    $selectExtras = '';
    if (in_array('Desconto', $prodCols))
        $selectExtras .= ', p.Desconto';
    if (in_array('desconto', $prodCols))
        $selectExtras .= ', p.desconto';
    if (in_array('TemDesconto', $prodCols))
        $selectExtras .= ', p.TemDesconto';
    if (in_array('tem_desconto', $prodCols))
        $selectExtras .= ', p.tem_desconto';
    if (in_array('quantidade_desc', $prodCols))
        $selectExtras .= ', p.quantidade_desc';
    if (in_array('FavoritosCount', $prodCols))
        $selectExtras .= ', p.FavoritosCount';
    if (in_array('CreatedAt', $prodCols))
        $selectExtras .= ', p.CreatedAt';

    $in = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT p.idProdutos, p.Nome, p.Preco, p.Marca, p.Usuarios_idUsuarios $selectExtras,
        (SELECT ImagemUrl FROM enderecoimagem e WHERE e.Produtos_idProdutos = p.idProdutos LIMIT 1) AS ImagemUrl
        FROM produtos p WHERE p.idProdutos IN ($in) GROUP BY p.idProdutos";
    $stmt = $conexao->prepare($sql);
    foreach (array_values($ids) as $k => $v)
        $stmt->bindValue($k + 1, $v, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // map by id
    $map = [];
    foreach ($rows as $r) {
        $r['thumb'] = empty($r['ImagemUrl']) ? 'images/no-image.png' : 'serve_imagem.php?pid=' . (int) $r['idProdutos'];
        $map[$r['idProdutos']] = $r;
    }
    return $map;
}

function get_recommendations($conexao, $userId = null, $limit = 8, $contextProductId = null)
{
    // returns array of product rows suitable for rendering
    $candidates = []; // id => score
    $bySource = [];

    // If user is logged in, compute a simple interaction count (visits + favorites + recent searches)
    // If interactions are fewer than 10, return empty so the caller can show random/fallback products.
    if ($userId) {
        $visCount = 0;
        $favCount = 0;
        $searchCount = 0;
        try {
            $stmt = $conexao->prepare('SELECT COUNT(*) FROM visitas WHERE Usuarios_idUsuarios = :uid');
            $stmt->execute([':uid' => $userId]);
            $visCount = (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            $visCount = 0;
        }
        try {
            $stmt = $conexao->prepare('SELECT COUNT(*) FROM favoritos WHERE Usuarios_idUsuarios = :uid');
            $stmt->execute([':uid' => $userId]);
            $favCount = (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            $favCount = 0;
        }
        if (!empty($_SESSION['recent_searches']) && is_array($_SESSION['recent_searches']))
            $searchCount = count($_SESSION['recent_searches']);

        $interactionCount = $visCount + $favCount + $searchCount;
        if ($interactionCount < 10) {
            // Not enough signal for personalized recommendations; let the caller return random/fallback items
            return [];
        }
    }

    // 1) If user logged, get favorites categories/brands
    if ($userId) {
        try {
            $favStmt = $conexao->prepare('SELECT DISTINCT p.Categorias_idCategorias AS cid, p.Marca AS marca
                FROM produtos p JOIN favoritos f ON f.Produtos_idProdutos = p.idProdutos WHERE f.Usuarios_idUsuarios = :uid');
            $favStmt->execute([':uid' => $userId]);
            $cats = [];
            $marcas = [];
            while ($r = $favStmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($r['cid']))
                    $cats[] = (int) $r['cid'];
                if (!empty($r['marca']))
                    $marcas[] = $r['marca'];
            }
            if (!empty($cats) || !empty($marcas)) {
                $conds = [];
                $params = [];
                if (!empty($cats)) {
                    $conds[] = 'p.Categorias_idCategorias IN (' . implode(',', array_map('intval', $cats)) . ')';
                }
                if (!empty($marcas)) {
                    $placeholders = implode(',', array_fill(0, count($marcas), '?'));
                    $conds[] = 'p.Marca IN (' . $placeholders . ')';
                }
                $sql = 'SELECT p.idProdutos FROM produtos p WHERE (' . implode(' OR ', $conds) . ')';
                $stmt = $conexao->prepare($sql);
                $idx = 1;
                if (!empty($marcas)) {
                    foreach ($marcas as $m)
                        $stmt->bindValue($idx++, $m);
                }
                $stmt->execute();
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $pid = (int) $r['idProdutos'];
                    $bySource[$pid] = ($bySource[$pid] ?? 0) + 40; // increased weight for favorites
                }
            }
        } catch (Exception $e) { /* ignore */
        }
    }

    // 2) user visits (if table exists)
    if ($userId) {
        try {
            $vis = $conexao->prepare('SELECT Produtos_idProdutos AS pid, COUNT(*) AS cnt FROM visitas WHERE Usuarios_idUsuarios = :uid GROUP BY Produtos_idProdutos ORDER BY cnt DESC LIMIT 20');
            $vis->execute([':uid' => $userId]);
            while ($r = $vis->fetch(PDO::FETCH_ASSOC)) {
                $pid = (int) $r['pid'];
                $cnt = (int) $r['cnt'];
                $bySource[$pid] = ($bySource[$pid] ?? 0) + 80 + min($cnt, 100); // increased weight for visits
            }
        } catch (Exception $e) { /* table might not exist */
        }
    }

    // 3) recent searches from session
    $recentTerms = [];
    if (!empty($_SESSION['recent_searches']) && is_array($_SESSION['recent_searches'])) {
        $recentTerms = array_slice($_SESSION['recent_searches'], 0, 5);
    }
    if (!empty($recentTerms)) {
        $qparts = [];
        foreach ($recentTerms as $t)
            $qparts[] = "p.Nome LIKE '%" . str_replace("'", "\\'", $t) . "%'";
        $sql = 'SELECT p.idProdutos FROM produtos p WHERE (' . implode(' OR ', $qparts) . ') LIMIT 40';
        try {
            $stmt = $conexao->query($sql);
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $pid = (int) $r['idProdutos'];
                $bySource[$pid] = ($bySource[$pid] ?? 0) + 30; // increased weight for recent searches
            }
        } catch (Exception $e) { /* ignore */
        }
    }

    // 4) fallback popular items (FavoritosCount desc or visits global)
    try {
        $cols = describeProdutoCols($conexao);
        if (in_array('FavoritosCount', $cols)) {
            $pop = $conexao->query('SELECT idProdutos FROM produtos ORDER BY FavoritosCount DESC LIMIT 40');
        } else {
            $pop = $conexao->query('SELECT idProdutos FROM produtos ORDER BY idProdutos DESC LIMIT 40');
        }
        while ($r = $pop->fetch(PDO::FETCH_ASSOC)) {
            $pid = (int) $r['idProdutos'];
            $bySource[$pid] = ($bySource[$pid] ?? 0) + 12; // small increase for popular items
        }
    } catch (Exception $e) { /* ignore */
    }

    // Exclude current product and user's own products
    $exclude = [];
    if ($contextProductId)
        $exclude[] = (int) $contextProductId;
    if ($userId) {
        try {
            $own = $conexao->prepare('SELECT idProdutos FROM produtos WHERE Usuarios_idUsuarios = :uid');
            $own->execute([':uid' => $userId]);
            while ($r = $own->fetch(PDO::FETCH_ASSOC))
                $exclude[] = (int) $r['idProdutos'];
        } catch (Exception $e) {
        }
        // exclude favorites so we focus on discovery
        try {
            $favIds = $conexao->prepare('SELECT Produtos_idProdutos FROM favoritos WHERE Usuarios_idUsuarios = :uid');
            $favIds->execute([':uid' => $userId]);
            while ($r = $favIds->fetch(PDO::FETCH_ASSOC))
                $exclude[] = (int) $r['Produtos_idProdutos'];
        } catch (Exception $e) {
        }
    }

    // Build candidate list, filter excludes
    foreach ($bySource as $pid => $score) {
        if (in_array($pid, $exclude))
            continue;
        $candidates[$pid] = ($candidates[$pid] ?? 0) + $score;
    }

    if (empty($candidates))
        return [];

    // fetch product rows for candidates
    $ids = array_keys($candidates);
    $prodMap = fetchProductsByIds($conexao, $ids);

    // finalize scoring with bonuses
    $final = [];
    foreach ($candidates as $pid => $base) {
        if (!isset($prodMap[$pid]))
            continue;
        $p = $prodMap[$pid];
        $score = $base;
        // discount bonus
        $disc = null;
        $hasDiscFlag = false;
        if (array_key_exists('Desconto', $p) && $p['Desconto'] !== null && $p['Desconto'] !== '')
            $disc = (float) $p['Desconto'];
        if (array_key_exists('desconto', $p) && $p['desconto'] !== null && $p['desconto'] !== '')
            $disc = $disc ?? (float) $p['desconto'];
        if (array_key_exists('TemDesconto', $p) && $p['TemDesconto'])
            $hasDiscFlag = true;
        if (array_key_exists('tem_desconto', $p) && $p['tem_desconto'])
            $hasDiscFlag = true;
        if ($disc !== null && $disc > 0)
            $hasDiscFlag = true;
        if ($hasDiscFlag)
            $score += 40; // stronger bonus for discounted items
        // favoritos count bonus
        if (array_key_exists('FavoritosCount', $p) && is_numeric($p['FavoritosCount']))
            $score += min(50, (int) $p['FavoritosCount']); // amplified favoritos count bonus
        // recent created bonus
        if (array_key_exists('CreatedAt', $p) && !empty($p['CreatedAt'])) {
            $created = strtotime($p['CreatedAt']);
            if ($created !== false && (time() - $created) < 60 * 60 * 24 * 30)
                $score += 8;
        }
        $final[$pid] = ['score' => $score, 'product' => $p];
    }

    uasort($final, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    $out = [];
    foreach ($final as $pid => $row) {
        $out[] = $row['product'];
        if (count($out) >= $limit)
            break;
    }
    return $out;
}

?>