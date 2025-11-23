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

        /* overlay nav buttons for carousel */
        .carousel .img-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.45);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            z-index: 20;
        }

        .carousel .carousel-prev {
            left: 10px;
        }

        .carousel .carousel-next {
            right: 10px;
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
            display: none;
            /* Oculto por padrão */
            position: fixed;
            /* Posicionamento fixo */
            z-index: 1;
            /* Fica por cima de outros elementos */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            /* Permite rolagem se o conteúdo for grande */
            background-color: rgba(0, 0, 0, 0.5);
            /* Cor de fundo semitransparente */
        }

        .popup-content {
            background-color: #fefefe;
            margin: 15% auto;
            /* Centraliza o pop-up */
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            /* Largura do conteúdo */
            max-width: 500px;
            /* Largura máxima */
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
            display: block;
            /* Torna o pop-up visível */
        }
    </style>
</head>

<body>

    <?php include_once __DIR__ . '/includes/header.php'; ?>
    <!-- <?php //include 'includes/chat.inc'; ?> -->

    <!-- Navegação de categorias e botões -->
    <!-- <nav class="nav-categories">
        <select onchange="filtrarCategoria(this.value)">
            <option value="">Categorias</option>
            <option value="hardware">Hardware</option>
            <option value="software">Software</option>
            <option value="perifericos">Periféricos</option>
        </select>
        <button onclick="location.href='/ofertas'">Ofertas</button>
        <button onclick="location.href='/mais-vendidos'">Mais Vendidos</button>
        <button onclick="location.href='/lancamentos'">Lançamentos</button>
    </nav> -->

    <!-- Carrossel de banners clicáveis -->
    <section class="carousel">
        <ul id="carousel-slides">
            <li><a href="/promo1"><img src="images/banner1.jpg" alt="Promoção 1" /></a></li>
            <li><a href="/promo2"><img src="images/banner2.jpg" alt="Promoção 2" /></a></li>
            <li><a href="/promo3"><img src="images/banner3.jpg" alt="Promoção 3" /></a></li>
        </ul>
        <button class="img-nav-btn img-nav-prev carousel-prev" aria-label="Anterior banner">&#10094;</button>
        <button class="img-nav-btn img-nav-next carousel-next" aria-label="Próximo banner">&#10095;</button>
    </section>

    <!-- Recomendações de produtos -->
    <section class="recommendations">
        <h2>Recomendações de produtos para você:</h2>
        <div class="product-grid">
            <?php
            include_once __DIR__ . '/connections/conectarBD.php';
            include_once __DIR__ . '/includes/recommender.php';
            $uid = !empty($_SESSION['id']) ? (int) $_SESSION['id'] : null;
            // limit homepage recommendations to maximum 20 items
            $recs = get_recommendations($conexao, $uid, 20, null);
            if (empty($recs)) {
                echo '<p>Nenhuma recomendação no momento.</p>';
            } else {
                foreach ($recs as $p) {
                    $thumb = empty($p['ImagemUrl']) ? ($p['thumb'] ?? 'images/no-image.png') : ('serve_imagem.php?pid=' . (int) $p['idProdutos']);
                    $orig = number_format((float) $p['Preco'], 2, ',', '.');
                    // compute discount if available
                    $disc = null;
                    $hasDisc = false;
                    if (array_key_exists('Desconto', $p) && $p['Desconto'] !== null && $p['Desconto'] !== '')
                        $disc = (float) $p['Desconto'];
                    if (array_key_exists('desconto', $p) && $p['desconto'] !== null && $p['desconto'] !== '')
                        $disc = $disc ?? (float) $p['desconto'];
                    if (array_key_exists('TemDesconto', $p) && $p['TemDesconto'])
                        $hasDisc = true;
                    if (array_key_exists('tem_desconto', $p) && $p['tem_desconto'])
                        $hasDisc = true;
                    if ($disc !== null && $disc > 0)
                        $hasDisc = true;
                    $discounted = null;
                    if ($hasDisc && $disc !== null) {
                        $discounted = number_format(round((float) $p['Preco'] * (1 - ($disc / 100)), 2), 2, ',', '.');
                    }
                    echo '<div class="product-card">';
                    echo '<img src="' . htmlspecialchars($thumb) . '" alt="' . htmlspecialchars($p['Nome']) . '" />';
                    echo '<h3>' . htmlspecialchars($p['Nome']) . '</h3>';
                    if ($hasDisc && $discounted !== null) {
                        echo '<p><del>R$ ' . $orig . '</del> <strong>R$ ' . $discounted . '</strong> (' . htmlspecialchars(number_format($disc, 0)) . '%)</p>';
                    } else {
                        echo '<p><strong>R$ ' . $orig . '</strong></p>';
                    }
                    echo '<button onclick="location.href=\'produto.php?id=' . (int) $p['idProdutos'] . '\'">Ver Detalhes</button>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </section>

    <script>
        // Função de filtro de categorias (lookup)
        function filtrarCategoria(categoria) {
            console.log('Lookup categoria:', categoria);
            // Aqui você pode disparar busca AJAX ou filtrar localmente
        }

        // Carrossel com controles e pausa no hover
        let idx = 0;
        const slides = document.getElementById('carousel-slides');
        const carousel = document.querySelector('.carousel');
        const prevBtn = document.querySelector('.carousel-prev');
        const nextBtn = document.querySelector('.carousel-next');
        const SLIDE_INTERVAL = 8000; // 8 segundos por slide
        let slideTimer = null;

        function goToSlide(n) {
            if (!slides || slides.children.length === 0) return;
            idx = (n + slides.children.length) % slides.children.length;
            slides.style.transform = `translateX(${-100 * idx}%)`;
        }

        function showNextSlide() { goToSlide(idx + 1); }
        function showPrevSlide() { goToSlide(idx - 1); }

        function startTimer() {
            if (slideTimer) clearInterval(slideTimer);
            slideTimer = setInterval(showNextSlide, SLIDE_INTERVAL);
        }
        function stopTimer() { if (slideTimer) { clearInterval(slideTimer); slideTimer = null; } }

        // wire buttons
        if (nextBtn) nextBtn.addEventListener('click', function (e) { showNextSlide(); startTimer(); });
        if (prevBtn) prevBtn.addEventListener('click', function (e) { showPrevSlide(); startTimer(); });

        // pause on hover/focus for easier reading
        if (carousel) {
            carousel.addEventListener('mouseenter', stopTimer);
            carousel.addEventListener('mouseleave', startTimer);
            carousel.addEventListener('focusin', stopTimer);
            carousel.addEventListener('focusout', startTimer);
        }

        // initialize
        goToSlide(0);
        startTimer();
    </script>

    <!-- User area is included by includes/header.php -->
    <!-- No Materialize: using a small custom dropdown script in includes/header.php -->
</body>

</html>