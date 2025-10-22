<?php
require_once 'connections/conectarBD.php';
session_start();

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['id'];

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Count
$stmt = $conexao->prepare('SELECT COUNT(*) FROM produtos WHERE Usuarios_idUsuarios = :uid');
$stmt->execute([':uid' => $userId]);
$total = (int) $stmt->fetchColumn();

$stmt = $conexao->prepare('SELECT p.idProdutos, p.Nome, p.Preco, p.Quantidade, p.FavoritosCount, e.ImagemUrl
    FROM produtos p
    LEFT JOIN enderecoimagem e ON e.Produtos_idProdutos = p.idProdutos
    WHERE p.Usuarios_idUsuarios = :uid
    GROUP BY p.idProdutos
    ORDER BY p.idProdutos DESC
    LIMIT :lim OFFSET :off');
$stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <h2>Meus Produtos</h2>
    <p>Você tem <?php echo $total; ?> produto(s).</p>

    <div class="row">
        <?php foreach ($produtos as $p): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <?php if (!empty($p['ImagemUrl'])): ?>
                        <img src="<?php echo e($p['ImagemUrl']); ?>" class="card-img-top" style="height:160px;object-fit:cover;"
                            alt="">
                    <?php else: ?>
                        <div
                            style="height:160px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;color:#999">
                            Sem imagem</div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title small mb-1"><?php echo e($p['Nome']); ?></h5>
                        <p class="mb-1 text-muted">R$ <?php echo number_format($p['Preco'], 2, ',', '.'); ?></p>
                        <p class="mb-2 small">Estoque: <?php echo (int) $p['Quantidade']; ?></p>
                        <div class="mt-auto d-flex gap-2">
                            <a href="produto.php?id=<?php echo $p['idProdutos']; ?>"
                                class="btn btn-sm btn-outline-primary">Ver</a>
                            <a href="editarProduto.php?id=<?php echo $p['idProdutos']; ?>"
                                class="btn btn-sm btn-secondary">Editar</a>
                            <form method="POST" action="meusProdutos.php"
                                onsubmit="return confirm('Deseja excluir este produto?');" style="display:inline">
                                <input type="hidden" name="delete_id" value="<?php echo $p['idProdutos']; ?>">
                                <button class="btn btn-sm btn-danger">Excluir</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total > $perPage): ?>
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= ceil($total / $perPage); $i++): ?>
                    <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php
// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_id'])) {
    $delId = (int) $_POST['delete_id'];
    // Only owner can delete
    $stmt = $conexao->prepare('DELETE FROM produtos WHERE idProdutos = :id AND Usuarios_idUsuarios = :uid');
    $stmt->execute([':id' => $delId, ':uid' => $userId]);
    header('Location: meusProdutos.php');
    exit;
}

?>