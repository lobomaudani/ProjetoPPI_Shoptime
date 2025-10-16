<header>
    <?php include 'logo.inc'; ?>

    <div class="search-bar" style="margin: 10px 10px;">
        <form action="pesquisaProdutos.php" method="GET" class="d-flex" role="search">
            <input class="form-control me-2" type="search" name="q" 
                   placeholder="Buscar produtos..." 
                   aria-label="Buscar produtos" 
                   required
                   style="width: 300px; padding: 6px 10px; border-radius: 4px; border: 1px solid #ccc;">
            <button class="btn btn-outline-success" type="submit" style="padding: 6px 12px; border-radius: 4px;">Pesquisar</button>
        </form>
    </div>

    <div class="user-actions" id="user-actions">    
        <?php include __DIR__ . '/chamarHeader.php'; ?>
    </div>
    
</header>
<script>
// Fecha o <details> do usuário quando clicar fora de forma robusta
document.addEventListener('click', function (e) {
    const dropdown = document.querySelector('.user-dropdown');
    if (!dropdown) return;

    // Se o dropdown estiver aberto e o clique ocorrer fora dele, fecha
    if (dropdown.open && !dropdown.contains(e.target)) {
        // Pequeno timeout para evitar conflito com o próprio clique no summary que alterna o estado
        setTimeout(() => { dropdown.open = false; }, 0);
    }
});
</script>