<?php
session_start();
include_once 'connections/conectarBD.php';

// Helper para escapar saída
function e($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// receber termo de busca via GET
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
// registrar termo em session para recomendações (mantém até 10 termos únicos)
if ($q !== '') {
    if (!isset($_SESSION['recent_searches']) || !is_array($_SESSION['recent_searches']))
        $_SESSION['recent_searches'] = [];
    // evitar duplicatas: remove ocorrência anterior
    $existing = array_search($q, $_SESSION['recent_searches']);
    if ($existing !== false)
        unset($_SESSION['recent_searches'][$existing]);
    array_unshift($_SESSION['recent_searches'], $q);
    $_SESSION['recent_searches'] = array_slice($_SESSION['recent_searches'], 0, 10);
}
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 24;
$offset = ($page - 1) * $perPage;

$results = [];
$total = 0;
$hasError = false;

// filtros laterais
$filter_categoria = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int) $_GET['categoria'] : null;
$filter_min = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) $_GET['min_price'] : null;
$filter_max = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : null;
$filter_vendedor = isset($_GET['vendedor']) && $_GET['vendedor'] !== '' ? trim($_GET['vendedor']) : null; // agora texto
$filter_marca = isset($_GET['marca']) && $_GET['marca'] !== '' ? trim($_GET['marca']) : null; // texto
// filtro para ofertas/descontos
$filter_desconto = isset($_GET['desconto']) && ($_GET['desconto'] === '1' || $_GET['desconto'] === 'true') ? true : false;
// filtros especiais via header (filtro=mais_favoritados|lancamentos|mais_vendidos)
$filtro = isset($_GET['filtro']) ? trim($_GET['filtro']) : '';

try {
    // inspect produto columns once to know which discount fields exist
    $prodCols = [];
    try {
        $colRows = $conexao->query("DESCRIBE produtos")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($colRows as $cr)
            $prodCols[] = $cr['Field'];
    } catch (Exception $e) {
        $prodCols = [];
    }

    // carregar listas para filtros
    $categorias = $conexao->query('SELECT idCategorias, Nome FROM categorias ORDER BY Nome')->fetchAll(PDO::FETCH_ASSOC);
    // para os campos de texto não precisamos carregar todas as opções, mas carregamos marcas para sugestão se quiser
    $vendedores = $conexao->query('SELECT idUsuarios, Nome FROM usuarios ORDER BY Nome')->fetchAll(PDO::FETCH_ASSOC);
    $marcas = $conexao->query("SELECT DISTINCT Marca FROM produtos WHERE Marca IS NOT NULL AND Marca <> '' ORDER BY Marca")->fetchAll(PDO::FETCH_COLUMN);

    // montar condição dinamicamente
    $where = [];
    $params = [];
    if ($q !== '') {
        $like = '%' . str_replace('%', '\\%', $q) . '%';
        // incluir vendedor e marca na busca para que buscar por 'Ron' encontre produtos do vendedor
        $where[] = '(p.Nome LIKE :like OR p.Descricao LIKE :like OR u.Nome LIKE :like OR p.Marca LIKE :like)';
        $params[':like'] = $like;
    }
    if ($filter_categoria) {
        $where[] = 'p.Categorias_idCategorias = :cat';
        $params[':cat'] = $filter_categoria;
    }
    if ($filter_min !== null) {
        $where[] = 'p.Preco >= :minp';
        $params[':minp'] = $filter_min;
    }
    if ($filter_max !== null) {
        $where[] = 'p.Preco <= :maxp';
        $params[':maxp'] = $filter_max;
    }
    if ($filter_vendedor) {
        // buscar por vendedor pelo nome (JOIN com usuarios)
        $where[] = 'u.Nome LIKE :vnome';
        $params[':vnome'] = '%' . str_replace('%', '\\%', $filter_vendedor) . '%';
    }
    if ($filter_marca) {
        $where[] = 'p.Marca LIKE :marca';
        $params[':marca'] = '%' . str_replace('%', '\\%', $filter_marca) . '%';
    }

    // filtro de ofertas (produtos com desconto). Build condition safely depending on which columns exist
    if ($filter_desconto) {
        // inspect table columns to avoid SQL errors when a column doesn't exist
        $cols = [];
        try {
            $colRows = $conexao->query("DESCRIBE produtos")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($colRows as $cr)
                $cols[] = $cr['Field'];
        } catch (Exception $e) {
            $cols = [];
        }

        $discountConds = [];
        if (in_array('TemDesconto', $cols))
            $discountConds[] = 'p.TemDesconto = 1';
        if (in_array('tem_desconto', $cols))
            $discountConds[] = 'p.tem_desconto = 1';
        if (in_array('Desconto', $cols))
            $discountConds[] = 'p.Desconto > 0';
        if (in_array('desconto', $cols))
            $discountConds[] = 'p.desconto > 0';
        if (in_array('quantidade_desc', $cols))
            $discountConds[] = 'p.quantidade_desc > 0';

        if (!empty($discountConds)) {
            $where[] = '(' . implode(' OR ', $discountConds) . ')';
        }
    }

    $whereSql = '';
    if (count($where) > 0)
        $whereSql = 'WHERE ' . implode(' AND ', $where);

    // total (inclui JOIN com usuarios porque o WHERE pode usar u.Nome) - contar distinct produtos
    $countSql = "SELECT COUNT(DISTINCT p.idProdutos) FROM produtos p LEFT JOIN usuarios u ON u.idUsuarios = p.Usuarios_idUsuarios $whereSql";
    $countStmt = $conexao->prepare($countSql);
    foreach ($params as $k => $v)
        $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    // Determine ordering based on filtro parameter
    $orderBySql = 'ORDER BY p.Nome ASC';
    if ($filtro === 'mais_favoritados') {
        // prefer ordering by FavoritosCount when available
        $cols = [];
        try {
            $colRows = $conexao->query("DESCRIBE produtos")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($colRows as $cr)
                $cols[] = $cr['Field'];
        } catch (Exception $e) {
            $cols = [];
        }
        if (in_array('FavoritosCount', $cols)) {
            $orderBySql = 'ORDER BY p.FavoritosCount DESC, p.Nome ASC';
        } else {
            $orderBySql = 'ORDER BY p.Nome ASC';
        }
    } elseif ($filtro === 'lancamentos') {
        // order by creation date when available, fallback to id desc
        $cols = [];
        try {
            $colRows = $conexao->query("DESCRIBE produtos")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($colRows as $cr)
                $cols[] = $cr['Field'];
        } catch (Exception $e) {
            $cols = [];
        }
        if (in_array('CreatedAt', $cols)) {
            $orderBySql = 'ORDER BY p.CreatedAt DESC';
        } else {
            $orderBySql = 'ORDER BY p.idProdutos DESC';
        }
    }

    // prepare extra select columns for discount support if present
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

    // buscar produtos com primeira imagem (subquery) e nome do vendedor
    // usar DISTINCT para evitar duplicações caso algum JOIN extra provoque múltiplas linhas
    $sql = "SELECT p.idProdutos, p.Nome, p.Preco, p.Marca, p.Usuarios_idUsuarios $selectExtras,
        u.Nome AS vendedorNome,
        (SELECT ImagemUrl FROM enderecoimagem e WHERE e.Produtos_idProdutos = p.idProdutos LIMIT 1) AS ImagemUrl
        FROM produtos p
        LEFT JOIN usuarios u ON u.idUsuarios = p.Usuarios_idUsuarios
        $whereSql
        GROUP BY p.idProdutos
        $orderBySql
        LIMIT :lim OFFSET :off";

    $stmt = $conexao->prepare($sql);
    foreach ($params as $k => $v)
        $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', (int) $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':off', (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // processar imagens para cada resultado (pegar URL pronto)
    for ($i = 0; $i < count($results); $i++) {
        $r = $results[$i];
        $img = $r['ImagemUrl'] ?? null;
        if (!$img) {
            $results[$i]['thumb'] = 'images/no-image.png';
            continue;
        }
        // se há algum valor em ImagemUrl, vamos usar um endpoint para servir (handle path/blob/data)
        $results[$i]['thumb'] = 'serve_imagem.php?pid=' . (int) $r['idProdutos'];
    }

} catch (Exception $e) {
    $hasError = true;
    $errorMsg = $e->getMessage();
}

// Renderização HTML básica
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="styles/styles.css" rel="stylesheet">
    <title>Resultados da busca por "<?php echo e($q); ?>" - ShowTime</title>
    <style>
        .container-centered {
            max-width: 1100px;
            margin: 0 auto;
        }

        .page-wrapper {
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }

        aside.filters {
            width: 280px;
            background: #fff;
            border: 1px solid #e6e6e6;
            padding: 16px;
            border-radius: 6px;
        }

        .results {
            flex: 1;
        }

        .result-item {
            display: flex;
            gap: 16px;
            align-items: center;
            background: #fff;
            border: 1px solid #e9e9e9;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 12px;
        }

        .result-item .thumb img {
            width: 120px;
            height: 90px;
            object-fit: cover;
            border-radius: 4px;
            display: block;
        }

        .result-body {
            flex: 1;
        }

        .result-title {
            font-weight: 700;
            margin-bottom: 6px;
            color: #222;
            text-decoration: none;
        }

        .result-title:hover {
            text-decoration: none;
            color: #222;
        }

        .result-price {
            color: #c1121f;
            font-weight: 700;
        }

        .meta {
            color: #666;
            font-size: 0.9rem;
            margin-top: 6px;
        }

        .filters .form-row {
            margin-bottom: 10px;
        }

        .filters label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 6px;
        }

        .filters input[type="text"],
        .filters select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .filters button {
            margin-top: 8px;
            padding: 8px 12px;
        }

        /* centralizar conteúdo interno */
        .page-wrapper .results,
        .page-wrapper .filters {
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <main class="container my-4">
        <div style="height:40px;"></div> <!-- espaçamento maior abaixo do header -->
        <div class="container-centered">
            <h2 style="margin-top:0; text-align:center;">Resultados da busca</h2>
            <?php if ($q !== ''): ?>
                <p>Buscando por: "<?php echo e($q); ?>" — <?php echo $total; ?> resultado(s)</p>
            <?php else: ?>
                <p>Todos os produtos (use os filtros ou o campo de busca)</p>
            <?php endif; ?>

            <?php if ($hasError): ?>
                <div class="alert alert-danger">Erro ao buscar: <?php echo e($errorMsg); ?></div>
            <?php endif; ?>

        </div>

        <div class="page-wrapper container-centered" style="margin-top:18px;">
            <aside class="filters">
                <form method="get">
                    <input type="hidden" name="q" value="<?php echo e($q); ?>">
                    <div class="form-row">
                        <label for="categoria">Categoria</label>
                        <select name="categoria" id="categoria">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?php echo (int) $c['idCategorias']; ?>" <?php echo $filter_categoria == $c['idCategorias'] ? 'selected' : ''; ?>><?php echo e($c['Nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <label>Faixa de preço</label>
                        <div style="display:flex;gap:8px;"><input type="text" name="min_price" placeholder="R$ min"
                                value="<?php echo e($filter_min); ?>"><input type="text" name="max_price"
                                placeholder="R$ max" value="<?php echo e($filter_max); ?>"></div>
                    </div>

                    <div class="form-row">
                        <label for="vendedor">Vendedor (nome)</label>
                        <input type="text" name="vendedor" id="vendedor" placeholder="Digite o nome do vendedor"
                            value="<?php echo e($filter_vendedor); ?>">
                    </div>

                    <div class="form-row">
                        <label for="marca">Marca</label>
                        <input type="text" name="marca" id="marca" placeholder="Digite a marca"
                            value="<?php echo e($filter_marca); ?>">
                    </div>

                    <div class="form-row">
                        <button class="btn btn-primary" type="submit">Aplicar filtros</button>
                        <a class="btn btn-outline-secondary" href="pesquisaProdutos.php">Limpar</a>
                    </div>
                </form>
            </aside>

            <section class="results">
                <?php if (empty($results)): ?>
                    <p>Nenhum produto encontrado.</p>
                <?php else: ?>
                    <?php foreach ($results as $r): ?>
                        <div class="result-item">
                            <div class="thumb">
                                <a href="produto.php?id=<?php echo (int) $r['idProdutos']; ?>">
                                    <img src="<?php echo e($r['thumb']); ?>" alt="<?php echo e($r['Nome']); ?>">
                                </a>
                            </div>
                            <div class="result-body">
                                <a href="produto.php?id=<?php echo (int) $r['idProdutos']; ?>"
                                    class="result-title"><?php echo e($r['Nome']); ?></a>
                                <?php
                                // compute discount if available for this product
                                $origPrice = isset($r['Preco']) ? (float) $r['Preco'] : null;
                                $discPercent = null;
                                $hasDiscFlag = false;
                                if (isset($r['Desconto']) && $r['Desconto'] !== null && $r['Desconto'] !== '')
                                    $discPercent = (float) $r['Desconto'];
                                if (isset($r['desconto']) && $r['desconto'] !== null && $r['desconto'] !== '')
                                    $discPercent = $discPercent ?? (float) $r['desconto'];
                                if (isset($r['TemDesconto']) && $r['TemDesconto'])
                                    $hasDiscFlag = true;
                                if (isset($r['tem_desconto']) && $r['tem_desconto'])
                                    $hasDiscFlag = true;
                                if ($discPercent !== null && $discPercent > 0)
                                    $hasDiscFlag = true;
                                $discounted = null;
                                if ($hasDiscFlag && $origPrice !== null && $discPercent !== null) {
                                    $discounted = round($origPrice * (1 - ($discPercent / 100)), 2);
                                    if ($discounted < 0)
                                        $discounted = 0.0;
                                }
                                ?>

                                <?php if ($hasDiscFlag && $discounted !== null): ?>
                                    <div class="result-price">
                                        <span class="text-muted" style="text-decoration:line-through;">R$
                                            <?php echo number_format($origPrice, 2, ',', '.'); ?></span>
                                        &nbsp;<strong class="text-danger">R$
                                            <?php echo number_format($discounted, 2, ',', '.'); ?></strong>
                                        <div class="small text-success">Economize
                                            <?php echo htmlspecialchars(number_format($discPercent, 2, ',', '.')); ?>%
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="result-price">R$ <?php echo number_format((float) $r['Preco'], 2, ',', '.'); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="meta">Vendedor: <?php echo e($r['vendedorNome'] ?? '—'); ?> &middot; Marca:
                                    <?php echo e($r['Marca'] ?? '—'); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php // paginação simples
                        $pages = max(1, ceil($total / $perPage));
                        if ($pages > 1): ?>
                        <nav aria-label="Paginação">
                            <ul class="pagination">
                                <?php for ($p = 1; $p <= $pages; $p++): ?>
                                    <?php
                                    $qs = $_GET;
                                    $qs['page'] = $p;
                                    echo '<li class="page-item ' . ($p === $page ? 'active' : '') . '"><a class="page-link" href="?' . http_build_query($qs) . '">' . $p . '</a></li>';
                                    ?>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>

    </main>
</body>

</html>