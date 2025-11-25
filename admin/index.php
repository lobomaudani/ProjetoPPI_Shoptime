<?php
// Admin dashboard
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
// only administrators (cargo id 1)
if (empty($_SESSION['id']) || empty($_SESSION['cargo']) || (int) $_SESSION['cargo'] !== 1) {
    header('Location: ../index.php');
    exit;
}
// do not render global header in admin area
if (!defined('NO_HEADER'))
    define('NO_HEADER', true);
include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../connections/conectarBD.php';

// fetch some counts
try {
    $totUsers = (int) $conexao->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    $totProducts = (int) $conexao->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
    $totCategories = (int) $conexao->query('SELECT COUNT(*) FROM categorias')->fetchColumn();
} catch (Exception $e) {
    $totUsers = $totProducts = $totCategories = 0;
}
?>
<main class="product-page-wrapper" style="padding:18px;margin-top:20px;">
    <div class="card" style="background:#fff;padding:18px;border-radius:10px;">
        <h1 class="card-title">Área Admin</h1>
        <p>Bem-vindo à área administrativa. Use os links abaixo para gerenciar o site.</p>

        <div style="display:flex;gap:18px;margin-top:12px;flex-wrap:wrap;">
            <a class="btn btn-primary" href="products.php">Gerenciar Produtos (<?php echo $totProducts; ?>)</a>
            <a class="btn btn-secondary" href="users.php">Gerenciar Usuários (<?php echo $totUsers; ?>)</a>
            <a class="btn btn-light" href="../index.php">Voltar ao Site</a>
        </div>

        <section style="margin-top:24px;">
            <h2>Visão Geral</h2>
            <ul>
                <li>Total de usuários: <?php echo $totUsers; ?></li>
                <li>Total de produtos: <?php echo $totProducts; ?></li>
                <li>Total de categorias: <?php echo $totCategories; ?></li>
            </ul>
        </section>
    </div>
</main>