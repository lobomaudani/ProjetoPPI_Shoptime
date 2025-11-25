<?php
require_once 'connections/conectarBD.php';
session_start();

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}
$userId = (int) $_SESSION['id'];

// handle unfavorite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['unfav_id'])) {
    $pid = (int) $_POST['unfav_id'];
    $d = $conexao->prepare('DELETE FROM favoritos WHERE Usuarios_idUsuarios = :uid AND Produtos_idProdutos = :pid');
    $d->execute([':uid' => $userId, ':pid' => $pid]);
    header('Location: meusFavoritos.php');
    exit;
}

// detect discount columns to include in select
$prodCols = [];
try {
    $colRows = $conexao->query("DESCRIBE produtos")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($colRows as $cr)
        $prodCols[] = $cr['Field'];
} catch (Exception $e) {
    $prodCols = [];
}

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

$stmt = $conexao->prepare('SELECT p.idProdutos, p.Nome, p.Preco' . $selectExtras . ', e.idEnderecoImagem AS imagem_id, u.Nome AS Vendedor
    FROM favoritos f
    JOIN produtos p ON p.idProdutos = f.Produtos_idProdutos
    LEFT JOIN (
        SELECT idEnderecoImagem, Produtos_idProdutos
        FROM enderecoimagem e1
        WHERE e1.idEnderecoImagem = (
            SELECT MIN(e2.idEnderecoImagem)
            FROM enderecoimagem e2
            WHERE e2.Produtos_idProdutos = e1.Produtos_idProdutos
        )
    ) e ON e.Produtos_idProdutos = p.idProdutos
    JOIN usuarios u ON u.idUsuarios = p.Usuarios_idUsuarios
    WHERE f.Usuarios_idUsuarios = :uid
    ORDER BY f.criado_em DESC');
$stmt->execute([':uid' => $userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles/styles.css" rel="stylesheet">
    <title>Meus Favoritos - ShowTime</title>
</head>

<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h2>Meus Favoritos</h2>
        <?php if (empty($items)): ?>
            <p>Nenhum favorito ainda.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($items as $it): ?>
                    <div class="list-group-item mb-2 position-relative p-3 d-flex align-items-start">
                        <a href="produto.php?id=<?php echo $it['idProdutos']; ?>"
                            class="d-flex align-items-center justify-content-start text-decoration-none text-dark">
                            <?php if (!empty($it['imagem_id'])): ?>
                                <?php $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
                                <img src="<?php echo $base; ?>/serve_imagem.php?id=<?php echo (int) $it['imagem_id']; ?>" alt=""
                                    style="width:120px;height:80px;object-fit:cover;border:1px solid #eaeaea;margin-right:12px;">
                            <?php else: ?>
                                <div
                                    style="width:120px;height:80px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;color:#999;margin-right:12px;border:1px solid #eaeaea;">
                                    Sem imagem</div>
                            <?php endif; ?>

                            <div>
                                <h5 class="mb-1 small"><?php echo e($it['Nome']); ?></h5>
                                <?php
                                $orig = isset($it['Preco']) ? (float) $it['Preco'] : null;
                                $disc = null;
                                $hasDisc = false;
                                if (isset($it['Desconto']) && $it['Desconto'] !== null && $it['Desconto'] !== '')
                                    $disc = (float) $it['Desconto'];
                                if (isset($it['desconto']) && $it['desconto'] !== null && $it['desconto'] !== '')
                                    $disc = $disc ?? (float) $it['desconto'];
                                if (isset($it['TemDesconto']) && $it['TemDesconto'])
                                    $hasDisc = true;
                                if (isset($it['tem_desconto']) && $it['tem_desconto'])
                                    $hasDisc = true;
                                if ($disc !== null && $disc > 0)
                                    $hasDisc = true;
                                $discountedPrice = null;
                                if ($hasDisc && $orig !== null && $disc !== null) {
                                    $discountedPrice = round($orig * (1 - ($disc / 100)), 2);
                                    if ($discountedPrice < 0)
                                        $discountedPrice = 0.0;
                                }
                                ?>
                                <?php if ($hasDisc && $discountedPrice !== null): ?>
                                    <p class="mb-1 text-muted"><span style="text-decoration:line-through;">R$
                                            <?php echo number_format($orig, 2, ',', '.'); ?></span>
                                        &nbsp;<strong class="text-danger">R$
                                            <?php echo number_format($discountedPrice, 2, ',', '.'); ?></strong>
                                    <div class="small text-success">Economize
                                        <?php echo htmlspecialchars(number_format($disc, 2, ',', '.')); ?>%
                                    </div>
                                    </p>
                                <?php else: ?>
                                    <p class="mb-1 text-muted">R$ <?php echo number_format($it['Preco'], 2, ',', '.'); ?></p>
                                <?php endif; ?>
                                <p class="mb-0 small text-muted">Vendedor: <?php echo e($it['Vendedor']); ?></p>
                            </div>
                        </a>

                        <!-- remove button (top-right) -->
                        <div style="position:absolute; right:12px; top:12px;">
                            <button class="btn btn-sm btn-outline-danger btn-unfav"
                                data-product-id="<?php echo (int) $it['idProdutos']; ?>" title="Remover dos favoritos">
                                &times;
                            </button>
                        </div>

                        <!-- fallback form (for non-JS) -->
                        <form method="POST" style="display:none">
                            <input type="hidden" name="unfav_id" value="<?php echo (int) $it['idProdutos']; ?>">
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Remover favorito via fetch para melhor UX
        document.querySelectorAll('.btn-unfav').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var pid = this.dataset.productId;
                if (!confirm('Remover este item dos favoritos?')) return;
                fetch('favoritos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=toggle_fav&product_id=' + encodeURIComponent(pid)
                }).then(function (r) { return r.json(); }).then(function (j) {
                    if (j && j.ok) {
                        // remover item da lista
                        var item = btn.closest('.list-group-item');
                        if (item) item.remove();
                    } else {
                        alert('Erro ao remover favorito');
                    }
                }).catch(function () { alert('Erro de rede ao tentar remover favorito.'); });
            });
        });
    </script>
    <?php
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>