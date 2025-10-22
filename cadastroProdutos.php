<?php
session_start();

include_once "connections/conectarBD.php";
$mensagem_status = '';
$tipo_mensagem = '';
$imagensSalvas = [];

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
        // uploads
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
                $uploadsDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadsDir))
                    mkdir($uploadsDir, 0777, true);
                $maxSizePerFile = 5 * 1024 * 1024;
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $exts = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/ogg' => 'ogv', 'video/quicktime' => 'mov'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['name'][$i] === '')
                        continue;
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                        $mensagem_status = 'Erro no upload.';
                        $tipo_mensagem = 'danger';
                        break;
                    }
                    if ($files['size'][$i] > $maxSizePerFile) {
                        $mensagem_status = 'Um dos arquivos excede o limite de 5MB.';
                        $tipo_mensagem = 'danger';
                        break;
                    }
                    $tmp = $files['tmp_name'][$i];
                    $mime = $finfo->file($tmp) ?: '';
                    if (!array_key_exists($mime, $exts)) {
                        $mensagem_status = 'Tipo de arquivo não suportado.';
                        $tipo_mensagem = 'danger';
                        break;
                    }
                    $base = bin2hex(random_bytes(8)) . '_' . time() . '_' . $i;
                    $filename = $base . '.' . $exts[$mime];
                    $dest = $uploadsDir . $filename;
                    if (!move_uploaded_file($tmp, $dest)) {
                        $mensagem_status = 'Erro ao mover arquivo.';
                        $tipo_mensagem = 'danger';
                        break;
                    }
                    $imagensSalvas[] = ['path' => 'uploads/' . $filename, 'mime' => $mime];
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
                    $sql = "INSERT INTO produtos (Usuarios_idUsuarios, Nome, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca) VALUES (:uid, :nome, :preco, :quantidade, :avaliacao, :categoria, :marca)";
                    $ins = $conexao->prepare($sql);
                    $ins->execute([':uid' => $userId, ':nome' => $nome, ':preco' => ($preco === '' ? null : $preco), ':quantidade' => ($unidades === '' ? null : (int) $unidades), ':avaliacao' => null, ':categoria' => (int) $categoria, ':marca' => ($marca === '' ? null : $marca)]);
                    $prodId = $conexao->lastInsertId();
                    if (!empty($imagensSalvas)) {
                        $insImg = $conexao->prepare("INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES (:img, :pid)");
                        foreach ($imagensSalvas as $f) {
                            $filePath = __DIR__ . '/' . $f['path'];
                            if (is_file($filePath)) {
                                $data = file_get_contents($filePath);
                                $insImg->bindValue(':img', $data, PDO::PARAM_LOB);
                                $insImg->bindValue(':pid', $prodId, PDO::PARAM_INT);
                                $insImg->execute();
                            }
                        }
                    }
                    $conexao->commit();
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
                            <div class="mb-3"><label class="form-label">Nome do Produto <span
                                        class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="nome" required
                                    placeholder="Digite o nome do produto">
                            </div>
                            <div class="mb-3"><label class="form-label">Descrição <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="descricao" rows="4" required
                                    placeholder="Esse produto contém..."></textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Preço <span
                                            class="text-danger">*</span></label>
                                    <input class="form-control" type="number" step="0.01" name="preco" required
                                        placeholder="Digite o preço do produto">
                                </div>
                                <div class="col-md-6"><label class="form-label">Unidades em estoque</label>
                                    <input class="form-control" type="number" name="unidades" value="1" min="1" required
                                        placeholder="Digite a quantidade no estoque">
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
                                                <option value="<?php echo htmlspecialchars($cat['id']); ?>">
                                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                                </option>
                                            <?php endforeach; endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6"><label class="form-label">Marca</label>
                                    <input class="form-control" type="text" name="marca" id="marca"
                                        placeholder="Marca (opcional)">
                                </div>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-12">
                                    <label class="form-label">Imagens / Vídeos (até 10 arquivos)</label>
                                    <input class="form-control" type="file" name="imagens[]" id="imagensInput"
                                        accept="image/*,video/*" multiple>
                                    <div class="form-text">PNG, JPG, GIF, WEBP, MP4, WebM, OGG, MOV — até 5MB por arquivo
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3" id="previewArea">
                                <div class="preview-grid" id="previewGrid"></div>
                            </div>
                            <div class="text-end mt-3"><a href="index.php"
                                    class="btn btn-outline-secondary me-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary" id="btnSalvar">Cadastrar</button>
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
            function clearPreview() { previewGrid.innerHTML = ''; }
            function addPreview(file, url) { const item = document.createElement('div'); item.className = 'preview-item'; const isImage = file.type.startsWith('image/'); if (isImage) { const img = document.createElement('img'); img.src = url; img.className = 'thumb'; item.appendChild(img); } else { const vid = document.createElement('video'); vid.src = url; vid.className = 'thumb'; vid.controls = true; vid.preload = 'metadata'; item.appendChild(vid); } const label = document.createElement('div'); label.textContent = file.name; item.appendChild(label); previewGrid.appendChild(item); }
            if (input) { input.addEventListener('change', function (e) { clearPreview(); const files = Array.from(e.target.files || []); if (files.length > MAX_FILES) { alert('Selecione no máximo ' + MAX_FILES + ' arquivos.'); input.value = ''; return; } for (const file of files) { if (!file.type.startsWith('image/') && !file.type.startsWith('video/')) { alert('Arquivo não suportado: ' + file.name); input.value = ''; clearPreview(); return; } if (file.size > MAX_SIZE) { alert('Arquivo muito grande (limite 5MB): ' + file.name); input.value = ''; clearPreview(); return; } const url = URL.createObjectURL(file); addPreview(file, url); } }); }
            if (form) { form.addEventListener('submit', function (e) { const nome = form.nome.value.trim(); const descricao = form.descricao.value.trim(); const preco = form.preco.value; const unidades = form.unidades.value; const categoria = (form.categoria ? form.categoria.value : ''); if (!nome || !descricao || preco === '' || unidades === '') { e.preventDefault(); alert('Preencha os campos obrigatórios antes de enviar.'); return; } if (!categoria) { e.preventDefault(); alert('Por favor selecione uma categoria para o produto.'); return; } const files = document.getElementById('imagensInput').files; if (files.length > MAX_FILES) { e.preventDefault(); alert('Selecione no máximo ' + MAX_FILES + ' arquivos.'); } }); }
        })();
    </script>
</body>

</html>