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
            max-width: 420px;
            width: 100%;
            border: 1px solid #eaeaea;
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
            <div class="col-left">
                <div class="d-flex gap-3">
                    <div class="thumb-list">
                        <?php foreach ($imgs as $i): ?>
                            <?php if ($i['type'] === 'path' && preg_match('/^uploads\//', $i['src'])): ?>
                                <img src="<?php echo e($i['src']); ?>" data-src="<?php echo e($i['src']); ?>"
                                    class="thumb-img fav-thumb">
                            <?php else: ?>
                                <img src="<?php echo e($i['src']); ?>" data-src="<?php echo e($i['src']); ?>"
                                    class="thumb-img fav-thumb">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="gallery-main">
                        <?php if (!empty($imgs)): ?>
                            <?php $first = $imgs[0]; ?>
                            <?php if (strpos($first['src'], 'data:') === 0): ?>
                                <img src="<?php echo e($first['src']); ?>" id="mainMedia">
                            <?php else: ?>
                                <img src="<?php echo e($first['src']); ?>" id="mainMedia">
                            <?php endif; ?>
                        <?php else: ?>
                            <img src="images/no-image.png" id="mainMedia">
                        <?php endif; ?>
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
                    <h4 class="text-danger">R$ <?php echo number_format((float) $produto['Preco'], 2, ',', '.'); ?></h4>
                    <p><?php echo nl2br(e($produto['Quantidade'] ? "Estoque: " . $produto['Quantidade'] : 'Sem informação de estoque')); ?>
                    </p>

                    <p class="mb-1"><strong>Categoria:</strong> <?php echo e($produto['categoriaNome'] ?? '—'); ?></p>
                    <p class="mb-3"><strong>Marca:</strong> <?php echo e($produto['Marca'] ?? '—'); ?></p>

                    <div class="d-flex align-items-center gap-2 mb-3">
                        <button id="buyBtn" class="btn btn-primary">Comprar agora</button>
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

    <script>
        document.querySelectorAll('.thumb-img').forEach(function (el) {
            el.addEventListener('click', function () {
                document.getElementById('mainMedia').src = this.dataset.src;
            });
        });

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
            fetch('', {
                method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=add_item'
            }).then(r => r.json()).then(j => { if (j.ok) alert('Item adicionado (teste)'); else alert('Erro: ' + (j.error || '')); });
        });
    </script>
</body>

</html>