<?php 
    session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="styles/styles.css" rel="stylesheet">
    <!-- Using only custom styles and scripts (no Materialize) -->
    
    <title>ShowTime</title>
    <link rel="icon" href="images/favicon.ico">
    <style>
       
        .search-bar input {
            width: 300px;
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .nav-categories button,
        .nav-categories select {
            margin-left: 30px;
            padding: 6px 12px;
            border: none;
            background: #c1121f;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }

        nav.nav-categories {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            background: #fc606c;
        }

        .carousel {
            position: relative;
            overflow: hidden;
            max-width: 1200px;
            margin: 20px auto;
        }

        .carousel ul {
            display: flex;
            margin: 0;
            padding: 0;
            transition: transform 0.5s ease;
        }

        .carousel li {
            list-style: none;
            min-width: 100%;
        }

        .carousel img {
            width: 100%;
            display: block;
        }

        .recommendations {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .recommendations h2 {
            margin-bottom: 15px;
        }

        .product-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        }

        .product-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
        }

        .product-card img {
            width: 100%;
            height: auto;
            margin-bottom: 10px;
        }

        .product-card del {
            color: #888;
        }

        .product-card strong {
            color: #d32f2f;
        }

        .product-card button {
            margin-top: 10px;
            padding: 8px 14px;
            background: #c1121f;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .carousel {
            width: 100%;
            max-height: 250px;
            overflow: hidden;
            position: relative;
            background: #eee;
        }

        .carousel img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }

        .carousel button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.4);
            border: none;
            color: #fff;
            font-size: 1.5rem;
            padding: 8px;
            cursor: pointer;
        }

        .carousel .prev {
            left: 10px;
        }

        .carousel .next {
            right: 10px;
        }

        section.recomendacoes {
            padding: 30px 20px;
        }

        section.recomendacoes h2 {
            font-size: 1.4rem;
            margin-bottom: 20px;
        }

        .produtos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 20px;
        }

        .produto-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 15px;
            text-align: center;
            transition: transform 0.2s;
        }

        .produto-card:hover {
            transform: translateY(-5px);
        }

        .produto-card img {
            max-width: 100%;
            margin-bottom: 10px;
        }

        .produto-card .titulo {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .produto-card .preco-antigo {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9rem;
        }

        .produto-card .preco {
            color: #e63946;
            font-weight: bold;
            font-size: 1.2rem;
            margin: 5px 0;
        }

        .produto-card .desconto {
            background: #e63946;
            color: #fff;
            font-size: 0.8rem;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .produto-card .avaliacao {
            margin-top: 8px;
            font-size: 0.9rem;
            color: #f4a261;
            /* cor das estrelas */
        }

        .produto-card .reviews {
            color: #666;
            font-size: 0.8rem;
        }

        /* Ocultar o pop-up por padrão */
        .popup {
        display: none; /* Oculto por padrão */
        position: fixed; /* Posicionamento fixo */
        z-index: 1; /* Fica por cima de outros elementos */
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto; /* Permite rolagem se o conteúdo for grande */
        background-color: rgba(0,0,0,0.5); /* Cor de fundo semitransparente */
        }

        .popup-content {
        background-color: #fefefe;
        margin: 15% auto; /* Centraliza o pop-up */
        padding: 20px;
        border: 1px solid #888;
        width: 80%; /* Largura do conteúdo */
        max-width: 500px; /* Largura máxima */
        position: relative;
        }

        .close-btn {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        }

        .close-btn:hover,
        .close-btn:focus {
        color: black;
        text-decoration: none;
        }

        /* Estilos para quando o pop-up é ativado */
        .popup.active {
        display: block; /* Torna o pop-up visível */
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>
    <!-- <?php //include 'includes/chat.inc'; ?> -->

    <!-- Navegação de categorias e botões -->
    <nav class="nav-categories">
        <select onchange="filtrarCategoria(this.value)">
            <option value="">Categorias</option>
            <option value="hardware">Hardware</option>
            <option value="software">Software</option>
            <option value="perifericos">Periféricos</option>
        </select>
        <button onclick="location.href='/ofertas'">Ofertas</button>
        <button onclick="location.href='/mais-vendidos'">Mais Vendidos</button>
        <button onclick="location.href='/lancamentos'">Lançamentos</button>
    </nav>

    <!-- Carrossel de banners clicáveis -->
    <section class="carousel">
        <ul id="carousel-slides">
            <li><a href="/promo1"><img src="images/banner1.jpg" alt="Promoção 1" /></a></li>
            <li><a href="/promo2"><img src="images/banner2.jpg" alt="Promoção 2" /></a></li>
            <li><a href="/promo3"><img src="images/banner3.jpg" alt="Promoção 3" /></a></li>
        </ul>
    </section>

    <!-- Recomendações de produtos -->
    <section class="recommendations">
        <h2>Recomendações de produtos para você:</h2>
        <div class="product-grid">
            <div class="product-card">
                <img src="images/produto1.jpg" alt="Desktop Completo" />
                <h3>Produto Amostra</h3>
                <p><del>R$ 2.000,00</del> <strong>R$ 1.000,00</strong> (-50%)</p>
                <p>5.0 ★★★★★ (1.527)</p>
                <button onclick="location.href='/produto/amostra'">Ver Detalhes</button>
            </div>
            <div class="product-card">
                <img src="images/produto1.jpg" alt="Desktop Completo" />
                <h3>Produto Amostra</h3>
                <p><del>R$ 2.000,00</del> <strong>R$ 1.000,00</strong> (-50%)</p>
                <p>5.0 ★★★★★ (1.527)</p>
                <button onclick="location.href='/produto/amostra'">Ver Detalhes</button>
            </div>
            <div class="product-card">
                <img src="images/produto1.jpg" alt="Desktop Completo" />
                <h3>Produto Amostra</h3>
                <p><del>R$ 2.000,00</del> <strong>R$ 1.000,00</strong> (-50%)</p>
                <p>5.0 ★★★★★ (1.527)</p>
                <button onclick="location.href='/produto/amostra'">Ver Detalhes</button>
            </div>
            <div class="product-card">
                <img src="images/produto1.jpg" alt="Desktop Completo" />
                <h3>Produto Amostra</h3>
                <p><del>R$ 2.000,00</del> <strong>R$ 1.000,00</strong> (-50%)</p>
                <p>5.0 ★★★★★ (1.527)</p>
                <button onclick="location.href='/produto/amostra'">Ver Detalhes</button>
            </div>
            <div class="product-card">
                <img src="images/produto1.jpg" alt="Desktop Completo" />
                <h3>Produto Amostra</h3>
                <p><del>R$ 2.000,00</del> <strong>R$ 1.000,00</strong> (-50%)</p>
                <p>5.0 ★★★★★ (1.527)</p>
                <button onclick="location.href='/produto/amostra'">Ver Detalhes</button>
            </div>
            <div class="product-card">
                <img src="images/produto1.jpg" alt="Desktop Completo" />
                <h3>Produto Amostra</h3>
                <p><del>R$ 2.000,00</del> <strong>R$ 1.000,00</strong> (-50%)</p>
                <p>5.0 ★★★★★ (1.527)</p>
                <button onclick="location.href='/produto/amostra'">Ver Detalhes</button>
            </div>
            <div class="product-card">
                <img src="images/produto1.jpg" alt="Desktop Completo" />
                <h3>Produto Amostra</h3>
                <p><del>R$ 2.000,00</del> <strong>R$ 1.000,00</strong> (-50%)</p>
                <p>5.0 ★★★★★ (1.527)</p>
                <button onclick="location.href='/produto/amostra'">Ver Detalhes</button>
            </div>
            <div class="product-card">
                <img src="images/produto1.jpg" alt="Desktop Completo" />
                <h3>Produto Amostra</h3>
                <p><del>R$ 2.000,00</del> <strong>R$ 1.000,00</strong> (-50%)</p>
                <p>5.0 ★★★★★ (1.527)</p>
                <button onclick="location.href='/produto/amostra'">Ver Detalhes</button>
            </div>
            <!-- Duplicar .product-card conforme necessário para novos itens -->
        </div>
    </section>    

    <script>
        // Função de filtro de categorias (lookup)
        function filtrarCategoria(categoria) {
            console.log('Lookup categoria:', categoria);
            // Aqui você pode disparar busca AJAX ou filtrar localmente
        }

        // Lógica simples de carrossel automático
        let idx = 0;
        const slides = document.getElementById('carousel-slides');
        function showNextSlide() {
            idx = (idx + 1) % slides.children.length;
            slides.style.transform = `translateX(${-100 * idx}%)`;
        }
        setInterval(showNextSlide, 4000);
    </script>

    <!-- User area is included by includes/header.php -->
        <!-- No Materialize: using a small custom dropdown script in includes/header.php -->
</body>

</html>