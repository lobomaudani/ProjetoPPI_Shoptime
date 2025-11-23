<?php
session_start();
include_once 'connections/conectarBD.php';

// Helper para saída segura
function e($s)
{
    return htmlspecialchars($s ?? '');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// ações via POST (favoritar, addcart)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // toggle_fav now handled by favoritos.php endpoint; produto.php keeps add_item handling

    if ($action === 'add_item' && !empty($_SESSION['id'])) {
        // ação simples: inserir em itenscompras com QuantidadeComprada = 1
        $userId = (int) $_SESSION['id'];
        // criar compra temporária (aqui só um placeholder)
        $conexao->beginTransaction();
        try {
            $conexao->exec("INSERT INTO compras (Data, Usuarios_idUsuarios) VALUES (NOW(), $userId)");
            $compId = $conexao->lastInsertId();
            $ins = $conexao->prepare('INSERT INTO itenscompras (QuantidadeComprada, Produtos_idProdutos, Compras_idCompras) VALUES (:q, :pid, :cid)');
            $ins->execute([':q' => 1, ':pid' => $id, ':cid' => $compId]);
            $conexao->commit();
            echo json_encode(['ok' => true]);
            exit;
        } catch (Exception $e) {
            if ($conexao->inTransaction())
                $conexao->rollBack();
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// buscar produto
$stmt = $conexao->prepare('SELECT p.*, u.Nome AS vendedorNome, u.Email AS vendedorEmail, cg.Nome AS cargoNome, cat.Nome AS categoriaNome
    FROM produtos p
    JOIN usuarios u ON u.idUsuarios = p.Usuarios_idUsuarios
    LEFT JOIN cargos cg ON cg.idCargos = u.Cargos_idCargos
    LEFT JOIN categorias cat ON cat.idCategorias = p.Categorias_idCategorias
    WHERE p.idProdutos = :id');
$stmt->execute([':id' => $id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$produto) {
    header('Location: index.php');
    exit;
}

// registrar visita do usuário (se logado). Tenta inserir na tabela visitas se existir.
if (!empty($_SESSION['id'])) {
    try {
        $insv = $conexao->prepare('INSERT INTO visitas (Usuarios_idUsuarios, Produtos_idProdutos, Data) VALUES (:uid, :pid, NOW())');
        $insv->execute([':uid' => (int) $_SESSION['id'], ':pid' => $id]);
    } catch (Exception $e) {
        // tabela pode não existir em instalações antigas; ignorar sem quebrar a página
    }
}

// preparar descrição para exibição: converte sequências literais "\n" em quebras reais
$descricaoRaw = '';
if (!empty($produto['Descricao'])) {
    $descricaoRaw = $produto['Descricao'];
} elseif (!empty($produto['Descricao'] ?? null)) {
    $descricaoRaw = $produto['Descricao'];
}
// se veio com barras literais (\n), converte para nova linha
if ($descricaoRaw !== '') {
    $descricaoRaw = str_replace('\\n', "\n", $descricaoRaw);
}

// buscar imagens
$imgs = [];
$stmt = $conexao->prepare('SELECT idEnderecoImagem, ImagemUrl FROM enderecoimagem WHERE Produtos_idProdutos = :id');
$stmt->execute([':id' => $id]);
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // se ImagemUrl é blob (conteúdo bin) detectamos e salvamos como data-url
    $data = $r['ImagemUrl'];
    if ($data === null)
        continue;
    // detectar se é caminho (string começando com uploads/) ou conteúdo binário
    if (is_string($data) && strpos($data, 'uploads/') === 0) {
        $imgs[] = ['type' => 'path', 'src' => $data];
    } else {
        // tratar como blob - detectar mime via finfo se possível
        $mime = 'image/jpeg';
        if (is_string($data)) {
            // já binário em string
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($data) ?: 'application/octet-stream';
            $imgs[] = ['type' => 'blob', 'src' => 'data:' . $mime . ';base64,' . base64_encode($data)];
        }
    }
}

// verificar se usuário favoritou
$favorited = false;
if (!empty($_SESSION['id'])) {
    $check = $conexao->prepare('SELECT 1 FROM favoritos WHERE Usuarios_idUsuarios = :uid AND Produtos_idProdutos = :pid');
    $check->execute([':uid' => (int) $_SESSION['id'], ':pid' => $id]);
    $favorited = (bool) $check->fetchColumn();
}

// Determinar desconto (adapta a diferentes nomes de coluna possíveis)
$discountPercent = null;
$hasDiscountFlag = false;
if (array_key_exists('Desconto', $produto) && $produto['Desconto'] !== null && $produto['Desconto'] !== '') {
    $discountPercent = (float) $produto['Desconto'];
}
if ($discountPercent === null && array_key_exists('desconto', $produto) && $produto['desconto'] !== null && $produto['desconto'] !== '') {
    $discountPercent = (float) $produto['desconto'];
}
if ($discountPercent === null && array_key_exists('quantidade_desc', $produto) && $produto['quantidade_desc'] !== null && $produto['quantidade_desc'] !== '') {
    $discountPercent = (float) $produto['quantidade_desc'];
}
// flags that may indicate discount is active
if ((array_key_exists('TemDesconto', $produto) && $produto['TemDesconto']) || (array_key_exists('tem_desconto', $produto) && $produto['tem_desconto'])) {
    $hasDiscountFlag = true;
}
// if there's a numeric discount > 0, consider discount active as well
if ($discountPercent !== null && $discountPercent > 0) {
    $hasDiscountFlag = true;
}

// prepare price values
$originalPrice = (isset($produto['Preco']) && $produto['Preco'] !== null) ? (float) $produto['Preco'] : null;
$discountedPrice = null;
if ($hasDiscountFlag && $originalPrice !== null && $discountPercent !== null) {
    $discountedPrice = round($originalPrice * (1 - ($discountPercent / 100)), 2);
    if ($discountedPrice < 0)
        $discountedPrice = 0.0;
}

?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles/styles.css" rel="stylesheet">
    <title><?php echo e($produto['Nome']); ?> - ShowTime</title>
    <style>
        .gallery {
            display: flex;
            gap: 12px;
        }

        .gallery-main img,
        .gallery-main video {
            max-width: 540px;
            /* limit main image width */
            max-height: 420px;
            /* avoid extremely tall images */
            width: 100%;
            height: auto;
            object-fit: contain;
            border: 1px solid #eaeaea;
            display: block;
            background: #fff;
        }

        .thumb-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .thumb-list img,
        .thumb-list video {
            width: 64px;
            height: 64px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 4px;
        }

        .thumb-list img.active {
            border-color: #c1121f;
        }

        /* overlay navigation buttons on main image */
        .img-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
        }

        .img-nav-btn:hover {
            background: rgba(0, 0, 0, 0.65);
        }

        .img-nav-prev {
            left: 8px;
        }

        .img-nav-next {
            right: 8px;
        }

        .gallery-main-wrapper {
            position: relative;
            display: inline-block;
        }

        /* thumbnail strip (horizontal, Shopee-like) */
        .thumb-strip-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        .thumb-strip-btn {
            background: transparent;
            border: none;
            font-size: 20px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #555;
        }

        .thumb-strip {
            display: flex;
            gap: 8px;
            overflow: hidden;
            /* show up to 5 thumbnails (72px each) plus gaps: 5*72 + 4*8 = 392px */
            max-width: 392px;
            width: 100%;
        }

        .thumb-item {
            flex: 0 0 auto;
        }

        .thumb-item img {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .thumb-item img.active {
            border-color: #c1121f;
        }

        /* coração favorito */
        .btn-icon {
            background: transparent;
            border: none;
            padding: 6px;
            font-size: 18px;
            color: #777;
        }

        .btn-icon svg {
            width: 28px;
            height: 28px;
        }

        .btn-icon.favorited {
            color: #b30000;
        }

        .product-card {
            position: relative;
        }

        .fav-icon {
            position: absolute;
            right: 18px;
            top: 18px;
            width: 40px;
            height: 40px;
            cursor: pointer;
        }
    </style>
</head>

<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    <main class="container my-4 product-page-wrapper">
        <div class="product-columns">
            <div class="col-left" style="flex:1 1 60%;">
                <div class="d-flex flex-column gap-3">
                    <div class="gallery-main">
                        <div class="gallery-main-wrapper">
                            <?php if (!empty($imgs)): ?>
                                <?php $first = $imgs[0]; ?>
                                <img src="<?php echo e($first['src']); ?>" id="mainMedia">
                                <button class="img-nav-btn img-nav-prev" id="imgPrev"
                                    aria-label="Anterior">&#10094;</button>
                                <button class="img-nav-btn img-nav-next" id="imgNext" aria-label="Próximo">&#10095;</button>
                            <?php else: ?>
                                <img src="images/no-image.png" id="mainMedia">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="thumb-strip-wrapper">
                        <button class="thumb-strip-btn" id="thumbPrev"
                            aria-label="Anterior miniaturas">&#10094;</button>
                        <div class="thumb-strip" id="thumbStrip">
                            <?php foreach ($imgs as $i): ?>
                                <div class="thumb-item"><img src="<?php echo e($i['src']); ?>"
                                        data-src="<?php echo e($i['src']); ?>" class="thumb-img fav-thumb"></div>
                            <?php endforeach; ?>
                        </div>
                        <button class="thumb-strip-btn" id="thumbNext" aria-label="Próximo miniaturas">&#10095;</button>
                    </div>
                </div>
            </div>

            <div class="col-right">
                <div class="card p-3 product-card">
                    <img id="favIcon" class="fav-icon"
                        src="<?php echo $favorited ? 'images/icon-fav-produto-selecionado.png' : 'images/icon-fav-produto-nao-selecionado.png'; ?>"
                        alt="Favoritar" />
                    <h3><?php echo e($produto['Nome']); ?></h3>
                    <p class="text-muted mb-1">Vendido por: <strong><?php echo e($produto['vendedorNome']); ?></strong>
                    </p>
                    <?php if ($hasDiscountFlag && $discountedPrice !== null): ?>
                        <div class="mb-2">
                            <div class="d-flex align-items-baseline gap-3">
                                <div>
                                    <h6 class="text-muted mb-0 text-decoration-line-through">R$
                                        <?php echo number_format($originalPrice, 2, ',', '.'); ?>
                                    </h6>
                                    <div class="small text-success">Economize
                                        <?php echo htmlspecialchars(number_format($discountPercent, 2, ',', '.')); ?>%
                                    </div>
                                </div>

                                <!-- recommendations will load asynchronously -->
                                <div>
                                    <h4 class="text-danger">R$ <?php echo number_format($discountedPrice, 2, ',', '.'); ?>
                                    </h4>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <h4 class="text-danger">R$ <?php echo number_format((float) $produto['Preco'], 2, ',', '.'); ?></h4>
                    <?php endif; ?>
                    <p><?php echo nl2br(e($produto['Quantidade'] ? "Estoque: " . $produto['Quantidade'] : 'Sem informação de estoque')); ?>
                    </p>

                    <p class="mb-1"><strong>Categoria:</strong> <?php echo e($produto['categoriaNome'] ?? '—'); ?></p>
                    <p class="mb-3"><strong>Marca:</strong> <?php echo e($produto['Marca'] ?? '—'); ?></p>

                    <div class="d-flex align-items-center gap-2 mb-3">
                        <?php if (empty($_SESSION['id'])): ?>
                            <a href="login.php" class="btn btn-primary">Falar com o Vendedor</a>
                        <?php else: ?>
                            <a href="chat_thread.php?produto=<?php echo (int) $produto['idProdutos']; ?>"
                                class="btn btn-primary">Falar com o Vendedor</a>
                        <?php endif; ?>
                        <?php if (!empty($_SESSION['id']) && $_SESSION['id'] == $produto['Usuarios_idUsuarios']): ?>
                            <a href="cadastroProdutos.php?id=<?php echo $produto['idProdutos']; ?>"
                                class="btn btn-outline-secondary">Editar Produto</a>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4">
                        <h5>Descrição</h5>
                        <div><?php echo nl2br(e($descricaoRaw ?: ($produto['Descricao'] ?? $produto['Nome']))); ?></div>
                    </div>

                </div>
            </div>
        </div>
    </main>
    <!-- Recommendations placeholder (loaded via AJAX) -->
    <section id="recsContainer" class="recommendations container my-4" aria-live="polite">
        <h4>Recomendados para você</h4>
        <div id="recsGrid" class="product-grid">
            <p>Carregando recomendações...</p>
        </div>
    </section>

    <script>
        // build images array from thumbnails (thumb strip)
        const thumbStrip = document.getElementById('thumbStrip');
        const thumbEls = Array.from(document.querySelectorAll('#thumbStrip .thumb-img'));
        const mainMedia = document.getElementById('mainMedia');
        let currentIndex = 0;

        const images = thumbEls.map(function (el) { return el.dataset.src || el.src; });

        function setMainImage(idx) {
            if (!images.length) return;
            currentIndex = (idx + images.length) % images.length;
            mainMedia.src = images[currentIndex];
            thumbEls.forEach((t, i) => t.classList.toggle('active', i === currentIndex));
            // ensure active thumbnail is visible in strip
            const active = thumbEls[currentIndex];
            if (active && thumbStrip) {
                const rect = active.getBoundingClientRect();
                const parentRect = thumbStrip.getBoundingClientRect();
                if (rect.left < parentRect.left || rect.right > parentRect.right) {
                    // scroll so active is centered
                    const offset = (rect.left + rect.right) / 2 - (parentRect.left + parentRect.right) / 2;
                    thumbStrip.scrollBy({ left: offset, behavior: 'smooth' });
                }
            }
        }

        // click thumbnails
        thumbEls.forEach(function (el, i) { el.addEventListener('click', function () { setMainImage(i); }); });

        // nav buttons on main image
        const btnPrev = document.getElementById('imgPrev');
        const btnNext = document.getElementById('imgNext');
        if (btnPrev) btnPrev.addEventListener('click', function () { setMainImage(currentIndex - 1); });
        if (btnNext) btnNext.addEventListener('click', function () { setMainImage(currentIndex + 1); });

        // thumbnail strip nav (scrolls one thumbnail at a time)
        const thumbPrev = document.getElementById('thumbPrev');
        const thumbNext = document.getElementById('thumbNext');
        function scrollThumbs(delta) {
            if (!thumbStrip || thumbEls.length === 0) return;
            const thumbW = thumbEls[0].getBoundingClientRect().width + 8; // include gap
            thumbStrip.scrollBy({ left: delta * thumbW, behavior: 'smooth' });
        }
        if (thumbPrev) thumbPrev.addEventListener('click', function () { scrollThumbs(-1); });
        if (thumbNext) thumbNext.addEventListener('click', function () { scrollThumbs(1); });

        // keyboard navigation
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'ArrowLeft') setMainImage(currentIndex - 1);
            if (ev.key === 'ArrowRight') setMainImage(currentIndex + 1);
        });

        // initialize active state
        setMainImage(0);

        // Load recommendations via AJAX using the endpoint
        function loadRecommendations(limit = 6) {
            const pid = <?php echo (int) $produto['idProdutos']; ?>;
            fetch('recommendations.php?limit=' + encodeURIComponent(limit) + '&pid=' + encodeURIComponent(pid))
                .then(r => r.json())
                .then(data => {
                    const grid = document.getElementById('recsGrid');
                    if (!grid) return;
                    grid.innerHTML = '';
                    if (!data.ok || !data.results || data.results.length === 0) {
                        grid.innerHTML = '<p>Nenhuma recomendação no momento.</p>';
                        return;
                    }
                    data.results.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'product-card';
                        const img = document.createElement('img'); img.src = p.thumb; img.alt = p.nome;
                        const h3 = document.createElement('h3'); h3.textContent = p.nome;
                        const price = document.createElement('p');
                        if (p.preco_descontado !== null && p.preco_descontado !== undefined) {
                            price.innerHTML = '<del>R$ ' + Number(p.preco).toFixed(2).replace('.', ',') + '</del> <strong>R$ ' + Number(p.preco_descontado).toFixed(2).replace('.', ',') + '</strong> (' + (p.desconto ? Math.round(p.desconto) : 0) + '%)';
                        } else {
                            price.innerHTML = '<strong>R$ ' + Number(p.preco).toFixed(2).replace('.', ',') + '</strong>';
                        }
                        const btn = document.createElement('button'); btn.textContent = 'Ver Detalhes'; btn.onclick = function () { location.href = 'produto.php?id=' + p.id; };
                        div.appendChild(img); div.appendChild(h3); div.appendChild(price); div.appendChild(btn);
                        grid.appendChild(div);
                    });
                }).catch(err => {
                    const grid = document.getElementById('recsGrid'); if (grid) grid.innerHTML = '<p>Erro ao carregar recomendações.</p>';
                });
        }

        // call on load
        loadRecommendations(6);

        // Favoritar (imagem)
        const favIcon = document.getElementById('favIcon');
        if (favIcon) {
            favIcon.addEventListener('click', function () {
                // se não logado, redireciona para login
                <?php if (empty($_SESSION['id'])): ?>
                    window.location = 'login.php';
                <?php else: ?>
                    fetch('favoritos.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=toggle_fav&product_id=<?php echo $produto['idProdutos']; ?>'
                    }).then(r => r.json()).then(j => {
                        if (j.ok) {
                            favIcon.src = j.favorited ? 'images/icon-fav-produto-selecionado.png' : 'images/icon-fav-produto-nao-selecionado.png';
                        } else alert('Erro ao favoritar');
                    });
                <?php endif; ?>
            });
        }

        // Comprar (adiciona item em itenscompras como placeholder)
        const buyBtn = document.getElementById('buyBtn');
        buyBtn && buyBtn.addEventListener('click', function () {
            fetch('', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=add_item' })
                .then(r => r.json()).then(j => { if (j.ok) alert('Item adicionado (teste)'); else alert('Erro: ' + (j.error || '')); });
        });
    </script>
</body>

</html>