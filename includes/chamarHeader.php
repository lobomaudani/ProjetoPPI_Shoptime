<script>
    const DIV = document.getElementById('user-actions');
    <?php 
    
    if(!isset($_SESSION['loggedin'])){
        echo 'DIV.innerHTML = "<div class=\"user-actions-generic\"><div class=\"user-actions-line\"><a href=\"login.php\">Entre</a> ou<br></div><div class=\"user-actions-line\"><a href=\"register.php\" class=\"user-actions-links\">Cadastre-se</a></div></div>";';

    }else{

        echo 'DIV.innerHTML = "<a href=\"usuario.php\" class=\"user-actions-links\"><ins>'. $_SESSION['nome'] .'</ins></a>
        <div class="dropdown me-1">
            <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-offset="10,20">
            Offset
            </button>
            <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Action</a></li>
            <li><a class="dropdown-item" href="#">Another action</a></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
            </ul>
        </div>
        <img src=\"images/icon-fav.png\" alt=\"Lista de Favoritos\" width=\"30\" height=\"30\"></a>";';

    }

    ?>
</script>