<?php
session_start();

include_once "connections/conectarBD.php";
$mensagem_status = '';
$tipo_mensagem = '';
$imagensSalvas = []; // informações dos arquivos salvos

// Buscar categorias do banco (fallback para array vazio)
$categorias = [];
try {
    // usar os nomes reais das colunas: idCategorias e Nome
    $stmt = $conexao->query("SELECT idCategorias AS id, Nome AS nome FROM categorias ORDER BY Nome ASC");
    if ($stmt) {
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // tabela categorias pode não existir; seguir com array vazio
    $categorias = [];
}

// Processamento do POST (mesmo arquivo)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recebe os dados do formulário com sanitização básica
    $nome = htmlspecialchars(trim($_POST["nome"] ?? ''));
    $descricao = htmlspecialchars(trim($_POST["descricao"] ?? ''));
    $preco = htmlspecialchars(trim($_POST["preco"] ?? ''));
    $unidades = htmlspecialchars(trim($_POST["unidades"] ?? ''));
    $categoria = htmlspecialchars(trim($_POST["categoria"] ?? ''));
    $marca = htmlspecialchars(trim($_POST["marca"] ?? ''));

    // Validações simples
    if (!$nome || !$descricao || $preco === '' || $unidades === '') {
        $mensagem_status = 'Por favor, preencha os campos obrigatórios corretamente.';
        $tipo_mensagem = 'danger';
    } elseif (!$categoria) {
        // categoria é obrigatória
        $mensagem_status = 'Por favor, selecione uma categoria para o produto.';
        $tipo_mensagem = 'danger';
    } else {
        // Upload de múltiplos arquivos (até 10)
        if (isset($_FILES['imagens'])) {
            $files = $_FILES['imagens'];
            // contagem real de arquivos enviados (ignora inputs vazios)
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

                $maxSizePerFile = 5 * 1024 * 1024; // 5MB por arquivo
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                // tipos permitidos e extensões
                $exts = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'video/mp4' => 'mp4',
                    'video/webm' => 'webm',
                    'video/ogg' => 'ogv',
                    'video/quicktime' => 'mov',
                ];

                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['name'][$i] === '')
                        continue; // pular inputs vazios

                    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                        $mensagem_status = 'Erro no upload de um dos arquivos.';
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
                        $mensagem_status = 'Tipo de arquivo não suportado. Use imagens (JPG/PNG/WEBP) ou vídeos (MP4/WebM/OGG/MOV).';
                        $tipo_mensagem = 'danger';
                        break;
                    }

                    $baseName = bin2hex(random_bytes(8)) . '_' . time() . '_' . $i;
                    $filename = $baseName . '.' . $exts[$mime];
                    $dest = $uploadsDir . $filename;
                    if (!move_uploaded_file($tmp, $dest)) {
                        $mensagem_status = 'Erro ao mover arquivo para o servidor.';
                        $tipo_mensagem = 'danger';
                        break;
                    }

                    // armazenar info relativa para exibição e possível gravação no DB
                    $imagensSalvas[] = [
                        'path' => 'uploads/' . $filename,
                        'mime' => $mime
                    ];
                }
            }
        }

        // Se não houve erro até aqui, persistir usando a conexão $conexao
        if ($tipo_mensagem !== 'danger') {
            // requer usuário logado para saber quem está cadastrando
            if (empty($_SESSION['id'])) {
                $mensagem_status = 'Você precisa estar logado para cadastrar produtos.';
                $tipo_mensagem = 'danger';
            } else {
                try {
                    $conexao->beginTransaction();

                    $userId = (int) $_SESSION['id'];
                    $sql = "INSERT INTO produtos (Usuarios_idUsuarios, Nome, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca)";
                    $sql .= " VALUES (:uid, :nome, :preco, :quantidade, :avaliacao, :categoria, :marca)";
                    $ins = $conexao->prepare($sql);
                    $ins->execute([
                        ':uid' => $userId,
                        ':nome' => $nome,
                        ':preco' => ($preco === '' ? null : $preco),
                        ':quantidade' => ($unidades === '' ? null : (int) $unidades),
                        ':avaliacao' => null,
                        ':categoria' => (int) $categoria,
                        ':marca' => ($marca === '' ? null : $marca)
                    ]);

                    $prodId = $conexao->lastInsertId();

                    // gravar cada imagem/vídeo como LONGBLOB na tabela enderecoimagem
                    if (!empty($imagensSalvas)) {
                        $insImg = $conexao->prepare("INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES (:img, :pid)");
                        foreach ($imagensSalvas as $f) {
                            $filePath = __DIR__ . '/' . $f['path'];
                            if (is_file($filePath)) {
                                $data = file_get_contents($filePath);
                                // usar bindValue para LOB
                                $insImg->bindValue(':img', $data, PDO::PARAM_LOB);
                                $insImg->bindValue(':pid', $prodId, PDO::PARAM_INT);
                                $insImg->execute();
                            }
                        }
                    }

                    $conexao->commit();
                    $mensagem_status = 'Produto cadastrado com sucesso!';
                    $tipo_mensagem = 'success';

                    // limpar variáveis locais para evitar reenvio
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles/styles.css" rel="stylesheet">
    <link rel="icon" href="images/favicon.ico">
    <title>ShowTime - Cadastro de Produtos</title>
    <style>
        .card {
            border-radius: 10px;
        }

        .thumb {
            max-width: 180px;
            max-height: 140px;
            object-fit: contain;
            border: 1px solid #eaeaea;
            padding: 6px;
            background: #fff;
            margin: 6px;
        }

        .preview-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: flex-start;
        }

        .preview-item {
            width: 180px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        video.thumb {
            background: #000;
        }
    </style>
</head>

<body class="bg-light">
    <header class="mb-4">
        <?php include 'includes/logo.inc'; ?>
    </header>

    <main class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-9 col-lg-7">
                <?php if ($tipo_mensagem === 'success'): ?>
                    <div class="card shadow-sm p-3">
                        <div class="d-flex justify-content-center">
                            <div class="w-100" style="max-width:720px;">
                                <div class="card border-0 shadow-sm p-4 text-center">
                                    <div class="alert alert-success mb-3">Produto cadastrado com sucesso!</div>
                                    <?php if (!empty($nome)): ?>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($nome); ?></h5>
                                    <?php endif; ?>
                                    <?php if (!empty($descricao)): ?>
                                        <p class="text-muted small mb-2"><?php echo nl2br(htmlspecialchars($descricao)); ?></p>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-center gap-2 mt-3">
                                        <?php if (!empty($prodId)): ?>
                                            <a href="produto.php?id=<?php echo urlencode($prodId); ?>"
                                                class="btn btn-primary">Ver Página do Produto</a>
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
                            <div class="mb-3">
                                <label class="form-label">Nome do Produto <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="nome" required
                                    placeholder="Digite o nome do produto">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descrição <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="descricao" rows="4" required
                                    placeholder="Esse produto contém..."></textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Preço <span class="text-danger">*</span></label>
                                    <input class="form-control" type="number" step="0.01" name="preco" required
                                        placeholder="Digite o preço do produto">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Unidades em estoque</label>
                                    <input class="form-control" type="number" name="unidades" value="1" min="1" required
                                        placeholder="Digite a quantidade no estoque">
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Categoria <span class="text-danger">*</span></label>
                                    <select name="categoria" id="categoria" class="form-select" required>
                                        <?php if (empty($categorias)): ?>
                                            <option value="">Sem categorias</option>
                                        <?php else: ?>
                                            <?php foreach ($categorias as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat['id']); ?>">
                                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Marca</label>
                                    <input class="form-control" type="text" name="marca" id="marca"
                                        placeholder="Selecione Marca">
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
                                <div class="preview-grid" id="previewGrid">
                                    <!-- pré-visualizações geradas pelo JS -->
                                </div>
                            </div>

                            <div class="text-end mt-3">
                                <a href="index.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary" id="btnSalvar">Cadastrar</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const input = document.getElementById('imagensInput');
            const previewGrid = document.getElementById('previewGrid');
            const form = document.getElementById('produtoForm');
            const MAX_FILES = 10;
            const MAX_SIZE = 5 * 1024 * 1024; // 5MB

            function clearPreview() { previewGrid.innerHTML = ''; }

            function addPreview(file, url) {
                const item = document.createElement('div');
                item.className = 'preview-item';
                const isImage = file.type.startsWith('image/');
                if (isImage) {
                    const img = document.createElement('img');
                    img.src = url;
                    img.className = 'thumb';
                    item.appendChild(img);
                } else {
                    const vid = document.createElement('video');
                    vid.src = url;
                    vid.className = 'thumb';
                    vid.controls = true;
                    vid.preload = 'metadata';
                    item.appendChild(vid);
                }
                const label = document.createElement('div');
                label.textContent = file.name;
                item.appendChild(label);
                previewGrid.appendChild(item);
            }

            if (input) {
                input.addEventListener('change', function (e) {
                    clearPreview();
                    const files = Array.from(e.target.files || []);
                    if (files.length > MAX_FILES) {
                        alert('Selecione no máximo ' + MAX_FILES + ' arquivos.');
                        input.value = '';
                        return;
                    }
                    for (const file of files) {
                        if (!file.type.startsWith('image/') && !file.type.startsWith('video/')) {
                            alert('Arquivo não suportado: ' + file.name);
                            input.value = '';
                            clearPreview();
                            return;
                        }
                        if (file.size > MAX_SIZE) {
                            alert('Arquivo muito grande (limite 5MB): ' + file.name);
                            input.value = '';
                            clearPreview();
                            return;
                        }
                        const url = URL.createObjectURL(file);
                        addPreview(file, url);
                    }
                });
            }

            if (form) {
                form.addEventListener('submit', function (e) {
                    const nome = form.nome.value.trim();
                    const descricao = form.descricao.value.trim();
                    const preco = form.preco.value;
                    const unidades = form.unidades.value;
                    const categoria = (form.categoria ? form.categoria.value : '');
                    const marcaInput = (form.marca ? form.marca : null);
                    if (!nome || !descricao || preco === '' || unidades === '') {
                        e.preventDefault();
                        alert('Preencha os campos obrigatórios antes de enviar.');
                        return;
                    }

                    if (!categoria) {
                        e.preventDefault();
                        alert('Por favor selecione uma categoria para o produto.');
                        return;
                    }

                    // Se o campo marca estiver vazio, deixamos o placeholder visível — nada adicional a fazer.
                    const files = document.getElementById('imagensInput').files;
                    if (files.length > MAX_FILES) {
                        e.preventDefault();
                        alert('Selecione no máximo ' + MAX_FILES + ' arquivos.');
                    }
                });
            }
        })();
    </script>
</body>

</html>
}

// Se não houve erro até aqui, persistir usando a conexão $conexao
if ($tipo_mensagem !== 'danger') {
// requer usuário logado para saber quem está cadastrando
if (empty($_SESSION['id'])) {
$mensagem_status = 'Você precisa estar logado para cadastrar produtos.';
$tipo_mensagem = 'danger';
} else {
try {
$conexao->beginTransaction();

$userId = (int) $_SESSION['id'];
$sql = "INSERT INTO produtos (Usuarios_idUsuarios, Nome, Preco, Quantidade, Avaliacao, Categorias_idCategorias, Marca)";
$sql .= " VALUES (:uid, :nome, :preco, :quantidade, :avaliacao, :categoria, :marca)";
$ins = $conexao->prepare($sql);
$ins->execute([
':uid' => $userId,
':nome' => $nome,
':preco' => ($preco === '' ? null : $preco),
':quantidade' => ($unidades === '' ? null : (int)$unidades),
':avaliacao' => null,
':categoria' => (int)$categoria,
':marca' => ($marca === '' ? null : $marca)
]);

$prodId = $conexao->lastInsertId();

// gravar cada imagem/vídeo como LONGBLOB na tabela enderecoimagem
if (!empty($imagensSalvas)) {
$insImg = $conexao->prepare("INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES (:img, :pid)");
foreach ($imagensSalvas as $f) {
$filePath = __DIR__ . '/' . $f['path'];
if (is_file($filePath)) {
$data = file_get_contents($filePath);
// usar bindValue para LOB
$insImg->bindValue(':img', $data, PDO::PARAM_LOB);
$insImg->bindValue(':pid', $prodId, PDO::PARAM_INT);
$insImg->execute();
}
}
}

$conexao->commit();
$mensagem_status = 'Produto cadastrado com sucesso!';
$tipo_mensagem = 'success';

// limpar variáveis locais para evitar reenvio
$nome = $descricao = $preco = $unidades = $categoria = $marca = '';
$imagensSalvas = [];
} catch (Exception $e) {
if ($conexao->inTransaction()) $conexao->rollBack();
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles/styles.css" rel="stylesheet">
    <link rel="icon" href="images/favicon.ico">
    <title>ShowTime - Cadastro de Produtos</title>
    <style>
        .card {
            border-radius: 10px;
        }

        .thumb {
            max-width: 180px;
            max-height: 140px;
            object-fit: contain;
            border: 1px solid #eaeaea;
            padding: 6px;
            background: #fff;
            margin: 6px;
        }

        .preview-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: flex-start;
        }

        .preview-item {
            width: 180px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        video.thumb {
            background: #000;
        }
    </style>
</head>

<body class="bg-light">
    <header class="mb-4">
        <?php include 'includes/logo.inc'; ?>
    </header>

    <main class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-9 col-lg-7">
                <div class="card shadow-sm p-3">
                    <h4 class="mb-3">Cadastro de Produto</h4>

                    <?php if ($mensagem_status && $tipo_mensagem !== 'success'): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($tipo_mensagem); ?>">
                            <?php echo htmlspecialchars($mensagem_status); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($tipo_mensagem === 'success'): ?>
                        <div class="d-flex justify-content-center">
                            <div class="w-100" style="max-width:720px;">
                                <div class="card border-0 shadow-sm p-4 text-center">
                                    <div class="alert alert-success mb-3">Produto cadastrado com sucesso!</div>
                                    <?php if (!empty($nome)): ?>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($nome); ?></h5>
                                    <?php endif; ?>
                                    <?php if (!empty($descricao)): ?>
                                        <p class="text-muted small mb-2"><?php echo nl2br(htmlspecialchars($descricao)); ?></p>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-center gap-2 mt-3">
                                        <?php if (!empty($prodId)): ?>
                                            <a href="produto.php?id=<?php echo urlencode($prodId); ?>"
                                                class="btn btn-primary">Ver Página do Produto</a>
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
                    <?php endif; ?>

                    <?php if ($tipo_mensagem !== 'success'): ?>
                        <form action="" method="POST" enctype="multipart/form-data" id="produtoForm" novalidate>
                            <div class="mb-3">
                                <label class="form-label">Nome do Produto <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="nome" required
                                    placeholder="Digite o nome do produto">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descrição <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="descricao" rows="4" required
                                    placeholder="Esse produto contém..."></textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Preço <span class="text-danger">*</span></label>
                                    <input class="form-control" type="number" step="0.01" name="preco" required
                                        placeholder="Digite o preço do produto">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Unidades em estoque</label>
                                    <input class="form-control" type="number" name="unidades" value="1" min="1" required
                                        placeholder="Digite a quantidade no estoque">
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Categoria <span class="text-danger">*</span></label>
                                    <select name="categoria" id="categoria" class="form-select" required>
                                        <?php if (empty($categorias)): ?>
                                            <option value="">Sem categorias</option>
                                        <?php else: ?>
                                            <?php foreach ($categorias as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat['id']); ?>">
                                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Marca</label>
                                    <input class="form-control" type="text" name="marca" id="marca"
                                        placeholder="Selecione Marca">
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
                                <div class="preview-grid" id="previewGrid">
                                    <!-- pré-visualizações geradas pelo JS -->
                                </div>
                            </div>

                            <div class="text-end mt-3">
                                <a href="index.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary" id="btnSalvar">Cadastrar</button>
                            </div>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const input = document.getElementById('imagensInput');
            const previewGrid = document.getElementById('previewGrid');
            const form = document.getElementById('produtoForm');
            const MAX_FILES = 10;
            const MAX_SIZE = 5 * 1024 * 1024; // 5MB

            function clearPreview() { previewGrid.innerHTML = ''; }

            function addPreview(file, url) {
                const item = document.createElement('div');
                item.className = 'preview-item';
                const isImage = file.type.startsWith('image/');
                if (isImage) {
                    const img = document.createElement('img');
                    img.src = url;
                    img.className = 'thumb';
                    item.appendChild(img);
                } else {
                    const vid = document.createElement('video');
                    vid.src = url;
                    vid.className = 'thumb';
                    vid.controls = true;
                    vid.preload = 'metadata';
                    item.appendChild(vid);
                }
                const label = document.createElement('div');
                label.textContent = file.name;
                item.appendChild(label);
                previewGrid.appendChild(item);
            }

            if (input) {
                input.addEventListener('change', function (e) {
                    clearPreview();
                    const files = Array.from(e.target.files || []);
                    if (files.length > MAX_FILES) {
                        alert('Selecione no máximo ' + MAX_FILES + ' arquivos.');
                        input.value = '';
                        return;
                    }
                    for (const file of files) {
                        if (!file.type.startsWith('image/') && !file.type.startsWith('video/')) {
                            alert('Arquivo não suportado: ' + file.name);
                            input.value = '';
                            clearPreview();
                            return;
                        }
                        if (file.size > MAX_SIZE) {
                            alert('Arquivo muito grande (limite 5MB): ' + file.name);
                            input.value = '';
                            clearPreview();
                            return;
                        }
                        const url = URL.createObjectURL(file);
                        addPreview(file, url);
                    }
                });
            }

            if (form) {
                form.addEventListener('submit', function (e) {
                    const nome = form.nome.value.trim();
                    const descricao = form.descricao.value.trim();
                    const preco = form.preco.value;
                    const unidades = form.unidades.value;
                    const categoria = (form.categoria ? form.categoria.value : '');
                    const marcaInput = (form.marca ? form.marca : null);
                    if (!nome || !descricao || preco === '' || unidades === '') {
                        e.preventDefault();
                        alert('Preencha os campos obrigatórios antes de enviar.');
                        return;
                    }

                    if (!categoria) {
                        e.preventDefault();
                        alert('Por favor selecione uma categoria para o produto.');
                        return;
                    }

                    // Se o campo marca estiver vazio, deixamos o placeholder visível — nada adicional a fazer.
                    const files = document.getElementById('imagensInput').files;
                    if (files.length > MAX_FILES) {
                        e.preventDefault();
                        alert('Selecione no máximo ' + MAX_FILES + ' arquivos.');
                    }
                });
            }
        })();
    </script>
</body>

</html>