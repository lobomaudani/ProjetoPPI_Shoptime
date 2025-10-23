<?php
session_start();

// Avoid printing deprecation/notice messages into the HTML (they were showing up inside form fields)
// You can remove or adjust this in development if you want to see notices again.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

include_once "connections/conectarBD.php";
$mensagem_status = '';
$tipo_mensagem = '';
$imagensSalvas = [];

// Edit mode: if id provided, load product and images for editing
$editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editingProduct = null;
$existingImages = [];
if ($editId > 0) {
    // must be logged in and owner
    if (empty($_SESSION['id'])) {
        header('Location: login.php');
        exit;
    }
    try {
        $pstmt = $conexao->prepare('SELECT * FROM produtos WHERE idProdutos = :id');
        $pstmt->execute([':id' => $editId]);
        $editingProduct = $pstmt->fetch(PDO::FETCH_ASSOC);
        if (!$editingProduct || (int) $editingProduct['Usuarios_idUsuarios'] !== (int) $_SESSION['id']) {
            // not found or not owner
            header('Location: meusProdutos.php');
            exit;
        }
        $imgq = $conexao->prepare('SELECT idEnderecoImagem, ImagemUrl FROM enderecoimagem WHERE Produtos_idProdutos = :pid');
        $imgq->execute([':pid' => $editId]);
        $existingImages = $imgq->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // ignore and treat as create
        $editingProduct = null;
        $existingImages = [];
        $editId = 0;
    }
}

// Buscar categorias
$categorias = [];
try {
    $stmtCats = $conexao->query("SELECT idCategorias AS id, Nome AS nome FROM categorias ORDER BY Nome ASC");
    if ($stmtCats)
        $categorias = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categorias = [];
}

// Processamento do POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
    $descricao = htmlspecialchars(trim($_POST['descricao'] ?? ''));
    $preco = htmlspecialchars(trim($_POST['preco'] ?? ''));
    $unidades = htmlspecialchars(trim($_POST['unidades'] ?? ''));
    $categoria = htmlspecialchars(trim($_POST['categoria'] ?? ''));
    $marca = htmlspecialchars(trim($_POST['marca'] ?? ''));

    if (!$nome || !$descricao || $preco === '' || $unidades === '') {
        $mensagem_status = 'Por favor, preencha os campos obrigatórios corretamente.';
        $tipo_mensagem = 'danger';
    } elseif (!$categoria) {
        $mensagem_status = 'Por favor, selecione uma categoria para o produto.';
        $tipo_mensagem = 'danger';
    } else {
        // uploads (apenas imagens) - não gravar arquivos no disco, armazenar LOB
        if (isset($_FILES['imagens'])) {
            $files = $_FILES['imagens'];
            $countFiles = 0;
            foreach ($files['name'] as $n)
                if ($n !== '')
                    $countFiles++;
            if ($countFiles > 10) {
                $mensagem_status = 'Você pode enviar no máximo 10 arquivos.';
                $tipo_mensagem = 'danger';
            } else {
                $maxSizePerFile = 5 * 1024 * 1024;
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $exts = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['name'][$i] === '')
                        continue;
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                        $mensagem_status = 'Erro no upload de uma das imagens.';
                        $tipo_mensagem = 'danger';
                        break;
                    }
                    if ($files['size'][$i] > $maxSizePerFile) {
                        $mensagem_status = 'Uma das imagens excede o limite de 5MB.';
                        $tipo_mensagem = 'danger';
                        break;
                    }
                    $tmp = $files['tmp_name'][$i];
                    $mime = $finfo->file($tmp) ?: '';
                    if (!array_key_exists($mime, $exts)) {
                        $mensagem_status = 'Tipo de arquivo não suportado (apenas imagens).';
                        $tipo_mensagem = 'danger';
                        break;
                    }
                    // ler conteúdo binário e guardar para inserir como LOB
                    $data = file_get_contents($tmp);
                    if ($data === false) {
                        $mensagem_status = 'Erro ao ler arquivo de imagem.';
                        $tipo_mensagem = 'danger';
                        break;
                    }
                    $imagensSalvas[] = ['data' => $data, 'mime' => $mime];
                }
            }
        }

        if ($tipo_mensagem !== 'danger') {
            if (empty($_SESSION['id'])) {
                $mensagem_status = 'Você precisa estar logado para cadastrar produtos.';
                $tipo_mensagem = 'danger';
            } else {
                try {
                    $conexao->beginTransaction();
                    $userId = (int) $_SESSION['id'];
                    if ($editId > 0) {
                        // update existing product (only owner allowed; already checked when loading)
                        $upd = $conexao->prepare('UPDATE produtos SET Nome = :nome, Descricao = :desc, Preco = :preco, Quantidade = :quant, Categorias_idCategorias = :cat, Marca = :marca WHERE idProdutos = :id AND Usuarios_idUsuarios = :uid');
                        $upd->execute([':nome' => $nome, ':desc' => $descricao, ':preco' => ($preco === '' ? null : $preco), ':quant' => ($unidades === '' ? null : (int) $unidades), ':cat' => (int) $categoria, ':marca' => ($marca === '' ? null : $marca), ':id' => $editId, ':uid' => $userId]);
                        $prodId = $editId;
                    } else {
                        $sql = "INSERT INTO produtos (Usuarios_idUsuarios, Nome, Descricao, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca) VALUES (:uid, :nome, :preco, :quantidade, :avaliacao, :categoria, :marca)";
                        $ins = $conexao->prepare($sql);
                        $ins->execute([':uid' => $userId, ':nome' => $nome, ':desc' => $descricao, ':preco' => ($preco === '' ? null : $preco), ':quantidade' => ($unidades === '' ? null : (int) $unidades), ':avaliacao' => null, ':categoria' => (int) $categoria, ':marca' => ($marca === '' ? null : $marca)]);
                        $prodId = $conexao->lastInsertId();
                    }

                    // insert new images if present
                    if (!empty($imagensSalvas)) {
                        $insImg = $conexao->prepare("INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES (:img, :pid)");
                        foreach ($imagensSalvas as $f) {
                            $insImg->bindValue(':img', $f['data'], PDO::PARAM_LOB);
                            $insImg->bindValue(':pid', $prodId, PDO::PARAM_INT);
                            $insImg->execute();
                        }
                    }

                    // require at least one image (existing + new)
                    $countStmt = $conexao->prepare('SELECT COUNT(*) FROM enderecoimagem WHERE Produtos_idProdutos = :pid');
                    $countStmt->execute([':pid' => $prodId]);
                    $totalImgs = (int) $countStmt->fetchColumn();
                    if ($totalImgs < 1) {
                        throw new Exception('Envie ao menos uma imagem para o produto.');
                    }

                    $conexao->commit();
                    if ($editId > 0) {
                        header('Location: meusProdutos.php');
                        exit;
                    }
                    $mensagem_status = 'Produto cadastrado com sucesso!';
                    $tipo_mensagem = 'success';
                    $nome = $descricao = $preco = $unidades = $categoria = $marca = '';
                    $imagensSalvas = [];
                } catch (Exception $e) {
                    if ($conexao->inTransaction())
                        $conexao->rollBack();
                    $mensagem_status = 'Erro ao salvar produto: ' . $e->getMessage();
                    $tipo_mensagem = 'danger';
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <?php $pageTitle = 'ShowTime - Cadastro de Produtos';
    include __DIR__ . '/includes/head.php'; ?>
    <style>
        .card {
            border-radius: 10px
        }

        .thumb {
            max-width: 180px;
            max-height: 140px;
            object-fit: contain;
            border: 1px solid #eaeaea;
            padding: 6px;
            background: #fff;
            margin: 6px
        }

        .preview-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: flex-start
        }

        .preview-item {
            width: 180px;
            text-align: center;
            font-size: 12px;
            color: #666
        }

        video.thumb {
            background: #000
        }
    </style>
</head>

<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    <main class="container">
        <div style="height:12px;"></div> <!-- pequeno afastamento entre categorias e form -->
        <div class="row justify-content-center">
            <div class="col-12 col-md-9 col-lg-7">
                <?php if ($tipo_mensagem === 'success'): ?>
                    <div class="card shadow-sm p-3">
                        <div class="d-flex justify-content-center">
                            <div class="w-100" style="max-width:720px;">
                                <div class="card border-0 shadow-sm p-4 text-center">
                                    <div class="alert alert-success mb-3"><?php echo htmlspecialchars($mensagem_status); ?>
                                    </div>
                                    <?php if (!empty($prodId)): ?>
                                        <a href="produto.php?id=<?php echo urlencode($prodId); ?>" class="btn btn-primary">Ver
                                            Página do Produto</a>
                                        <a href="editarProduto.php?id=<?php echo urlencode($prodId); ?>"
                                            class="btn btn-outline-secondary">Editar Produto</a>
                                    <?php else: ?>
                                        <a href="index.php" class="btn btn-primary">Ver Lista de Produtos</a>
                                    <?php endif; ?>
                                    <a href="usuario.php" class="btn btn-outline-secondary">Voltar ao Perfil</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm p-3">
                        <h4 class="mb-3">Cadastro de Produto</h4>
                        <?php if ($mensagem_status && $tipo_mensagem !== 'success'): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($tipo_mensagem); ?>">
                                <?php echo htmlspecialchars($mensagem_status); ?>
                            </div>
                        <?php endif; ?>
                        <form action="" method="POST" enctype="multipart/form-data" id="produtoForm" novalidate>
                            <?php $isEdit = ($editId > 0); ?>
                            <div class="mb-3"><label class="form-label">Nome do Produto <span
                                        class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="nome" required
                                    placeholder="Digite o nome do produto"
                                    value="<?php echo $isEdit ? htmlspecialchars($editingProduct['Nome'] ?? '') : ''; ?>">
                            </div>
                            <div class="mb-3"><label class="form-label">Descrição <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="descricao" rows="4" required
                                    placeholder="Esse produto contém..."><?php echo $isEdit ? htmlspecialchars($editingProduct['Descricao'] ?? '') : ''; ?></textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Preço <span
                                            class="text-danger">*</span></label>
                                    <input class="form-control" type="number" step="0.01" name="preco" required
                                        placeholder="Digite o preço do produto"
                                        value="<?php echo $isEdit ? htmlspecialchars($editingProduct['Preco'] ?? '') : ''; ?>">
                                </div>
                                <div class="col-md-6"><label class="form-label">Unidades em estoque</label>
                                    <input class="form-control" type="number" name="unidades"
                                        value="<?php echo $isEdit ? htmlspecialchars($editingProduct['Quantidade'] ?? 1) : '1'; ?>"
                                        min="1" required placeholder="Digite a quantidade no estoque">
                                </div>
                            </div>
                            <div class="row g-3 mt-3">
                                <div class="col-md-6"><label class="form-label">Categoria <span
                                            class="text-danger">*</span></label>
                                    <select name="categoria" id="categoria" class="form-select" required>
                                        <?php if (empty($categorias)): ?>
                                            <option value="">Sem categorias</option>
                                        <?php else:
                                            foreach ($categorias as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo ($isEdit && $editingProduct['Categorias_idCategorias'] == $cat['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                                </option>
                                            <?php endforeach; endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6"><label class="form-label">Marca</label>
                                    <input class="form-control" type="text" name="marca" id="marca"
                                        placeholder="Marca (opcional)"
                                        value="<?php echo $isEdit ? htmlspecialchars($editingProduct['Marca'] ?? '') : ''; ?>">
                                </div>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-12">
                                    <label class="form-label">Imagens (até 10 arquivos)</label>
                                    <input class="form-control" type="file" name="imagens[]" id="imagensInput"
                                        accept="image/*" multiple>
                                    <div class="form-text">PNG, JPG, GIF, WEBP — até 5MB por imagem
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3" id="previewArea">
                                <div class="preview-grid" id="previewGrid"></div>
                            </div>
                            <?php if ($isEdit && !empty($existingImages)): ?>
                                <div class="mb-3">
                                    <label class="form-label">Imagens atuais</label>
                                    <div class="d-flex gap-2 flex-wrap" id="existingImages">
                                        <?php foreach ($existingImages as $img):
                                            $iid = (int) $img['idEnderecoImagem'];
                                            $src = (is_string($img['ImagemUrl']) && (strpos($img['ImagemUrl'], 'uploads/') === 0 || preg_match('#^https?://#i', $img['ImagemUrl']))) ? $img['ImagemUrl'] : (rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/serve_imagem.php?id=' . $iid);
                                            ?>
                                            <div class="existing-item" data-id="<?php echo $iid; ?>"
                                                style="width:120px;text-align:center;margin-bottom:6px;position:relative;">
                                                <img src="<?php echo htmlspecialchars($src); ?>"
                                                    style="width:120px;height:80px;object-fit:cover;border:1px solid #ddd;padding:2px;display:block;margin-bottom:4px;">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-existing"
                                                    style="position:absolute;right:6px;top:6px;">✕</button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="text-end mt-3"><a href="index.php"
                                    class="btn btn-outline-secondary me-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary"
                                    id="btnSalvar"><?php echo $isEdit ? 'Salvar alterações' : 'Cadastrar'; ?></button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const input = document.getElementById('imagensInput');
            const previewGrid = document.getElementById('previewGrid');
            const form = document.getElementById('produtoForm');
            const MAX_FILES = 10; const MAX_SIZE = 5 * 1024 * 1024;
            // DataTransfer-based incremental selection
            const dt = new DataTransfer();

            function renderPreviews() {
                previewGrid.innerHTML = '';
                for (let i = 0; i < dt.files.length; i++) {
                    const file = dt.files[i];
                    const item = document.createElement('div'); item.className = 'preview-item';
                    const img = document.createElement('img'); img.className = 'thumb'; img.src = URL.createObjectURL(file);
                    item.appendChild(img);
                    const btn = document.createElement('button'); btn.type = 'button'; btn.className = 'btn btn-sm btn-outline-danger'; btn.textContent = '✕';
                    btn.style.display = 'block'; btn.style.margin = '6px auto 0';
                    btn.addEventListener('click', function () { removeFileAt(i); });
                    item.appendChild(btn);
                    previewGrid.appendChild(item);
                }
                // update real input files
                input.files = dt.files;
            }

            function removeFileAt(index) {
                const tmp = new DataTransfer();
                for (let i = 0; i < dt.files.length; i++) if (i !== index) tmp.items.add(dt.files[i]);
                while (dt.files.length > 0) dt.items.remove(0);
                for (let i = 0; i < tmp.files.length; i++) dt.items.add(tmp.files[i]);
                renderPreviews();
            }

            // Attach to input change
            if (input) {
                input.addEventListener('change', function (e) {
                    const files = Array.from(e.target.files || []);
                    if (dt.files.length + files.length > MAX_FILES) { alert('Selecione no máximo ' + MAX_FILES + ' imagens.'); input.value = ''; return; }
                    for (const f of files) {
                        if (!f.type.startsWith('image/')) { alert('Apenas imagens são permitidas: ' + f.name); continue; }
                        if (f.size > MAX_SIZE) { alert('Imagem muito grande (max 5MB): ' + f.name); continue; }
                        dt.items.add(f);
                    }
                    renderPreviews();
                });
            }

            // Handle removal of existing images via AJAX
            function initExistingRemovalButtons() {
                document.querySelectorAll('.btn-remove-existing').forEach(function (btn) {
                    // avoid double-binding
                    if (btn._bound) return; btn._bound = true;
                    btn.addEventListener('click', function (e) {
                        var wrapper = e.target.closest('.existing-item');
                        var id = wrapper.getAttribute('data-id');
                        if (!confirm('Remover esta imagem? Esta ação não pode ser desfeita.')) return;
                        fetch('editarProduto.php?action=remover_imagem', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'idEnderecoImagem=' + encodeURIComponent(id)
                        }).then(function (resp) { return resp.json(); }).then(function (json) {
                            if (json && json.success) {
                                wrapper.parentNode.removeChild(wrapper);
                            } else {
                                alert('Erro ao remover imagem: ' + (json && json.error ? json.error : 'erro desconhecido'));
                            }
                        }).catch(function (err) {
                            console.error(err);
                            alert('Falha na requisição. Veja o console.');
                        });
                    });
                });
            }

            initExistingRemovalButtons();

            if (form) {
                form.addEventListener('submit', function (e) {
                    const nome = form.nome.value.trim(); const descricao = form.descricao.value.trim(); const preco = form.preco.value; const unidades = form.unidades.value; const categoria = (form.categoria ? form.categoria.value : '');
                    if (!nome || !descricao || preco === '' || unidades === '') { e.preventDefault(); alert('Preencha os campos obrigatórios antes de enviar.'); return; }
                    if (!categoria) { e.preventDefault(); alert('Por favor selecione uma categoria para o produto.'); return; }
                    const existingCount = document.querySelectorAll('.existing-item').length;
                    if (existingCount + dt.files.length === 0) { e.preventDefault(); alert('Envie ao menos uma imagem para o produto.'); return; }
                    // attach dt.files to input already performed in renderPreviews
                });
            }
        })();
    </script>
</body>

</html>