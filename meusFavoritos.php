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

$stmt = $conexao->prepare('SELECT p.idProdutos, p.Nome, p.Preco, p.FavoritosCount, e.ImagemUrl, u.Nome AS Vendedor
    FROM favoritos f
    JOIN produtos p ON p.idProdutos = f.Produtos_idProdutos
    LEFT JOIN enderecoimagem e ON e.Produtos_idProdutos = p.idProdutos
    JOIN usuarios u ON u.idUsuarios = p.Usuarios_idUsuarios
    WHERE f.Usuarios_idUsuarios = :uid
    GROUP BY p.idProdutos
    ORDER BY f.criado_em DESC');
$stmt->execute([':uid' => $userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
include 'includes/header.php';
?>
<div class="container mt-4">
    <h2>Meus Favoritos</h2>
    <?php if (empty($items)): ?>
        <p>Nenhum favorito ainda.</p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($items as $it): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($it['ImagemUrl'])): ?>
                            <img src="<?php echo e($it['ImagemUrl']); ?>" class="card-img-top"
                                style="height:160px;object-fit:cover;" alt="">
                        <?php else: ?>
                            <div
                                style="height:160px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;color:#999">
                                Sem imagem</div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title small mb-1"><?php echo e($it['Nome']); ?></h5>
                            <p class="mb-1 text-muted">R$ <?php echo number_format($it['Preco'], 2, ',', '.'); ?></p>
                            <p class="mb-2 small">Vendedor: <?php echo e($it['Vendedor']); ?></p>
                            <div class="mt-auto d-flex gap-2">
                                <a href="produto.php?id=<?php echo $it['idProdutos']; ?>"
                                    class="btn btn-sm btn-outline-primary">Ver</a>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Remover dos favoritos?');">
                                    <input type="hidden" name="unfav_id" value="<?php echo (int) $it['idProdutos']; ?>">
                                    <button class="btn btn-sm btn-danger">Remover</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
?>