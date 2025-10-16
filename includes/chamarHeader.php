<?php
// Renderiza a área do usuário diretamente no servidor (mais simples e confiável que injetar via JS)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// Saída HTML para a área do usuário
if (empty($_SESSION['loggedin'])) {
    ?>
    <div class="user-actions-generic">
        <div class="user-actions-line"><a href="screens/user/login.php">Entre</a> ou</div>
        <div class="user-actions-line"><a href="screens/user/register.php" class="user-actions-links">Cadastre-se</a></div>
    </div>
    <?php
} else {
    $nome = isset($_SESSION['nome']) ? htmlspecialchars($_SESSION['nome'], ENT_QUOTES, 'UTF-8') : 'Usuário';
    ?>
    <div class="user-area">
        <!-- User name trigger (custom, no Materialize dependency) -->
        <a class="user-name-link" href="#" aria-haspopup="true" aria-expanded="false" id="user-name-trigger">
            <?php echo $nome; ?> ▴
        </a>

        <!-- Menu (custom dropup) -->
        <ul id="user-dropdown" class="user-menu" role="menu" aria-labelledby="user-name-trigger">
            <li role="none"><a role="menuitem" href="screens/user/usuario.php">Conta</a></li>
            <li role="none"><a role="menuitem" href="screens/user/compras.php">Compras</a></li>
            <li role="separator" class="divider" aria-hidden="true"></li>
            <li role="none"><a role="menuitem" href="screens/user/logout.php">Sair</a></li>
        </ul>

        <a href="favoritos.php" class="fav-link" title="Favoritos">
            <img src="images/icon-fav.png" alt="Lista de Favoritos" width="28" height="28">
        </a>
    </div>
    <?php
}

?>
