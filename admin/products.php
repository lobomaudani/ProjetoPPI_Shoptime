<?php
session_start();
// suppress the site header when in admin pages
if (!defined('NO_HEADER'))
    define('NO_HEADER', true);
include_once __DIR__ . '/../connections/conectarBD.php';
include_once __DIR__ . '/../includes/user_helpers.php';

if (empty($_SESSION['id']) || empty($_SESSION['cargo']) || (int) $_SESSION['cargo'] !== 1) {
    header('Location: ../login.php');
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg = 'Token inválido.';
    } else {
        if (!empty($_POST['delete_id'])) {
            $pid = (int) $_POST['delete_id'];
            try {
                // remove images and files if stored locally
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

// fetch products with owner name
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
    <main class="product-page-wrapper" style="padding:18px;margin-top:20px;">
        <div class="card" style="background:#fff;padding:16px;border-radius:10px;">
            <h1 class="card-title">Gerenciar Produtos</h1>
            <?php if (!empty($msg)): ?>
                <div class="alert alert-info"><?php echo e($msg); ?></div><?php endif; ?>

            <div style="margin-bottom:12px;">
                <a class="btn btn-light" href="index.php">Voltar</a>
                <a class="btn btn-primary" href="../meusProdutos.php">Criar / Editar (meus produtos)</a>
            </div>

            <div style="overflow-x:auto;">
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
                                    <form method="POST" style="display:inline"
                                        onsubmit="return confirm('Remover este produto?');">
                                        <?php echo csrf_input(); ?>
                                        <input type="hidden" name="delete_id" value="<?php echo (int) $p['idProdutos']; ?>">
                                        <button class="btn btn-sm btn-danger" type="submit">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>