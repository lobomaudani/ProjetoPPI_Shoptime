<header>
    <?php include 'includes/logo.inc'; ?>
    <div class="search-bar" style="margin: 10px 0;">
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
        <a href=\"login.php\" class=\"user-actions-links\"><ins>Entre</ins></a></button> ou<br>
        <a href=\"register.php\" class=\"user-actions-links\"><ins>Cadastre-se</a></ins></button>
        <a href=\"index.html\"><img src=\"images/icon-fav.png\" alt=\"Lista de Favoritos\" width=\"30\" height=\"30" /></a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const DIV = document.getElementById('user-actions');
            DIV.innerHTML = "<a href=\"login.php\" class=\"user-actions-links\"><ins>Entre</ins></a></button> ou<br> <a href=\"register.php\" class=\"user-actions-links\"><ins>Cadastre-se</a></ins></button> <a href=\"index.html\"><img src=\"images/icon-fav.png\" alt=\"Lista de Favoritos\" width=\"30\" height=\"30\" /></a>";
        });
    </script>
</header>