<?php
session_start();
include_once __DIR__ . '/../connections/conectarBD.php';

if (empty($_SESSION['id']) || empty($_SESSION['cargo']) || (int) $_SESSION['cargo'] !== 1) {
    header('Location: ../login.php');
    exit;
}

// counts
try {
    $usersCount = (int) $conexao->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
} catch (Exception $e) {
    $usersCount = 0;
}
try {
    $productsCount = (int) $conexao->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
} catch (Exception $e) {
    $productsCount = 0;
}

?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Administração - Dashboard</title>
    <link href="../styles/styles.css" rel="stylesheet">
    <?php $pageTitle = 'Administração - Dashboard';
    include __DIR__ . '/../includes/head.php'; ?>
</head>

<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="container mt-4 admin-panel">
        <h2>Administração</h2>
        <p class="lead">Painel de administração — controle total do sistema.</p>

        <div style="display:flex;gap:18px;flex-wrap:wrap;margin-top:18px;">
            <div style="background:#fff;border:1px solid #eee;padding:18px;border-radius:8px;min-width:220px;">
                <h3><?php echo $usersCount; ?></h3>
                <div>Usuários</div>
                <div style="margin-top:8px;"><a class="btn btn-outline-secondary" href="users.php">Gerenciar
                        Usuários</a></div>
            </div>
            <div style="background:#fff;border:1px solid #eee;padding:18px;border-radius:8px;min-width:220px;">
                <h3><?php echo $productsCount; ?></h3>
                <div>Produtos</div>
                <div style="margin-top:8px;"><a class="btn btn-outline-secondary" href="products.php">Gerenciar
                        Produtos</a></div>
            </div>
        </div>
    </main>
</body>

</html>