<script>
    const DIV = document.getElementById('user-actions');
    DIV.innerHTML = 'AA';
    <?php 
    
    if(!isset($_SESSION['loggedin'])){
        echo 'DIV.innerHTML = "<a href=\"login.php\" class=\"user-actions-links\"><ins>Entre</ins></a> ou<br><a href=\"register.php\" class=\"user-actions-links\"><ins>Cadastre-se</a></ins><a href=\"index.html\"><img src=\"images/icon-fav.png\" alt=\"Lista de Favoritos\" width=\"30\" height=\"30\"></a>";';
    }else{
        echo 'DIV.innerHTML = "a";'; 
    }

    ?>
</script>