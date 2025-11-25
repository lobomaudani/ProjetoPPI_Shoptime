<?php
// Admin products list + delete
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
if (empty($_SESSION['id']) || empty($_SESSION['cargo']) || (int) $_SESSION['cargo'] !== 1) {
    header('Location: ../index.php');
    exit;
}
include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/user_helpers.php';
include_once __DIR__ . '/../connections/conectarBD.php';

$message = '';
// handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $message = 'Token CSRF inválido.';
    } elseif ($id <= 0) {
        $message = 'ID inválido.';
    } else {
        try {
            $stmt = $conexao->prepare('DELETE FROM produtos WHERE idProdutos = :id');
            $stmt->execute([':id' => $id]);
            $message = 'Produto apagado.';
        } catch (Exception $e) {
            $message = 'Falha ao apagar: ' . $e->getMessage();
        }
    }
}

// fetch products
try {
    $stmt = $conexao->prepare('SELECT p.idProdutos, p.Nome, p.Preco, p.FavoritosCount, p.Quantidade, u.Nome as Vendedor FROM produtos p LEFT JOIN usuarios u ON p.Usuarios_idUsuarios = u.idUsuarios ORDER BY p.idProdutos DESC LIMIT 200');
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $products = [];
}
?>
<main style="padding:18px;">
    <h1>Gerenciar Produtos</h1>
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div style="margin-bottom:12px;">
        <a class="btn btn-light" href="index.php">Voltar</a>
        <a class="btn btn-primary" href="../meusProdutos.php">Criar / Editar (meus produtos)</a>
    </div>

    <table class="table" style="width:100%;border-collapse:collapse;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Preço</th>
                <th>Qtd</th>
                <th>Favoritos</th>
                <th>Vendedor</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?php echo (int) $p['idProdutos']; ?></td>
                    <td><?php echo htmlspecialchars($p['Nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>R$ <?php echo number_format((float) $p['Preco'], 2, ',', '.'); ?></td>
                    <td><?php echo htmlspecialchars($p['Quantidade']); ?></td>
                    <td><?php echo (int) $p['FavoritosCount']; ?></td>
                    <td><?php echo htmlspecialchars($p['Vendedor'] ?? '—'); ?></td>
                    <td>
                        <a class="btn btn-sm btn-outline-primary"
                            href="../editarProduto.php?id=<?php echo (int) $p['idProdutos']; ?>">Editar</a>
                        <form method="POST" action="" style="display:inline;margin-left:6px;"
                            onsubmit="return confirm('Apagar este produto?');">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int) $p['idProdutos']; ?>">
                            <button class="btn btn-sm btn-danger" type="submit">Apagar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>
<?php
session_start();
include_once __DIR__ . '/../connections/conectarBD.php';
include_once __DIR__ . '/../includes/user_helpers.php';

if (empty($_SESSION['id']) || empty($_SESSION['cargo']) || (int) $_SESSION['cargo'] !== 1) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg = 'Token inválido.';
    } else {
        if (!empty($_POST['delete_id'])) {
            $pid = (int) $_POST['delete_id'];
            try {
                // remove images and files
                $imgs = $conexao->prepare('SELECT ImagemUrl FROM enderecoimagem WHERE Produtos_idProdutos = :pid');
                $imgs->execute([':pid' => $pid]);
                $files = [];
                while ($r = $imgs->fetch(PDO::FETCH_ASSOC)) {
                    if (!empty($r['ImagemUrl']) && strpos($r['ImagemUrl'], 'uploads/') === 0)
                        $files[] = __DIR__ . '/../' . $r['ImagemUrl'];
                }

                $conexao->beginTransaction();
                $delImgs = $conexao->prepare('DELETE FROM enderecoimagem WHERE Produtos_idProdutos = :pid');
                $delImgs->execute([':pid' => $pid]);
                $delProd = $conexao->prepare('DELETE FROM produtos WHERE idProdutos = :pid');
                $delProd->execute([':pid' => $pid]);
                $conexao->commit();
                foreach ($files as $f)
                    if (is_file($f))
                        @unlink($f);
                $msg = 'Produto removido.';
            } catch (Exception $e) {
                if ($conexao->inTransaction())
                    $conexao->rollBack();
                $msg = 'Erro ao remover produto: ' . $e->getMessage();
            }
        }
    }
}

// fetch products with owner name and one thumb
$products = [];
try {
    $stmt = $conexao->query('SELECT p.idProdutos, p.Nome, p.Preco, p.Usuarios_idUsuarios, u.Nome AS vendedor FROM produtos p LEFT JOIN usuarios u ON u.idUsuarios = p.Usuarios_idUsuarios ORDER BY p.idProdutos DESC');
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $products = [];
}

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
    <title>Admin - Produtos</title>
    <link href="../styles/styles.css" rel="stylesheet">
    <?php $pageTitle = 'Admin - Produtos';
    include __DIR__ . '/../includes/head.php'; ?>
</head>

<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="container mt-4 admin-panel">
        <h2>Gerenciar Produtos</h2>
        <?php if (!empty($msg)): ?>
            <div class="alert alert-info"><?php echo e($msg); ?></div><?php endif; ?>

        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Preço</th>
                    <th>Vendedor</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?php echo (int) $p['idProdutos']; ?></td>
                        <td><?php echo e($p['Nome']); ?></td>
                        <td>R$ <?php echo number_format((float) $p['Preco'], 2, ',', '.'); ?></td>
                        <td><?php echo e($p['vendedor'] ?? '—'); ?></td>
                        <td>
                            <a class="btn btn-sm btn-secondary"
                                href="../cadastroProdutos.php?id=<?php echo (int) $p['idProdutos']; ?>">Editar</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Remover este produto?');">
                                <?php echo csrf_input(); ?>
                                <input type="hidden" name="delete_id" value="<?php echo (int) $p['idProdutos']; ?>">
                                <button class="btn btn-sm btn-danger" type="submit">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>

</html>