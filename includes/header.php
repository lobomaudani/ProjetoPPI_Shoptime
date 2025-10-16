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
// Header specific scripts (kept minimal). Materialize dropdown is initialized in the page template.
</script>

</script>