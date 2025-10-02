<script>
    const DIV = document.getElementById('user-actions');
    DIV.innerHTML = 'AA';
    <?php 
    
    if(!isset($_SESSION['loggedin'])){    
        echo 'DIV.innerHTML = "a";';        
    }else{
        echo "DIV.innerHTML = \"b\";";
        //echo "DIV.innerHTML = \"<div class=\"user-verify\"><a href=\"login.php\" class=\"user-actions-links\"><ins>Entre</ins></a></button> ou<br><a href=\"register.php\" class=\"user-actions-links\"><ins>Cadastre-se</a></ins></button><a href=\"index.html\"><img src=\"images/icon-fav.png\" alt=\"Lista de Favoritos\" width=\"30\" height=\"30\" /></a></div>\";";
    }
    ?>
</script>