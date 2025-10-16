<?php
// Renderiza a área do usuário diretamente no servidor (mais simples e confiável que injetar via JS)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// Saída HTML para a área do usuário
if (empty($_SESSION['loggedin'])) {
    ?>
    <div class="user-actions-generic">
        <div class="user-actions-line"><a href="login.php">Entre</a> ou</div>
        <div class="user-actions-line"><a href="register.php" class="user-actions-links">Cadastre-se</a></div>
    </div>
    <?php
} else {
    $nome = isset($_SESSION['nome']) ? htmlspecialchars($_SESSION['nome'], ENT_QUOTES, 'UTF-8') : 'Usuário';
    ?>
    <div class="user-area">
        <details class="user-dropdown">
            <summary><span class="user-name"><?php echo $nome; ?></span> ▾</summary>
            <ul class="user-menu">
                <li><a href="usuario.php">Editar Conta</a></li>
                <li><a href="compras.php">Compras</a></li>
                <li><a href="logout.php">Sair</a></li>
            </ul>
        </details>

        <a href="favoritos.php" class="fav-link" title="Favoritos">
            <img src="images/icon-fav.png" alt="Lista de Favoritos" width="28" height="28">
        </a>
    </div>
    <?php
}

?>
