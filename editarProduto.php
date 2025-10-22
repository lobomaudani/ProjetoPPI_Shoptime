<?php
require_once 'connections/conectarBD.php';
session_start();
require_once 'includes/user_helpers.php';

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['id'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    header('Location: meusProdutos.php');
    exit;
}

// Load product and ensure ownership
$stmt = $conexao->prepare('SELECT * FROM produtos WHERE idProdutos = :id');
$stmt->execute([':id' => $id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$produto || (int) $produto['Usuarios_idUsuarios'] !== $userId) {
    die('Produto não encontrado ou sem permissão.');
}

// Load images
$imgStmt = $conexao->prepare('SELECT idEnderecoImagem, ImagemUrl FROM enderecoimagem WHERE Produtos_idProdutos = :pid');
$imgStmt->execute([':pid' => $id]);
$imagens = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

// Load categories for select
$categorias = [];
try {
    $cstmt = $conexao->query("SELECT idCategorias AS id, Nome AS nome FROM categorias ORDER BY Nome ASC");
    if ($cstmt)
        $categorias = $cstmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $_) {
    $categorias = [];
}

$errors = [];
// Handle image removals sent via AJAX (remove single existing image)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_remove_image'])) {
    $remId = (int) ($_POST['ajax_remove_image'] ?? 0);
    if ($remId) {
        $q = $conexao->prepare('SELECT ImagemUrl FROM enderecoimagem WHERE idEnderecoImagem = :id AND Produtos_idProdutos = :pid');
        $q->execute([':id' => $remId, ':pid' => $id]);
        if ($r = $q->fetch(PDO::FETCH_ASSOC)) {
            // if path stored on disk, unlink; else it's blob and we just delete row
            if (is_string($r['ImagemUrl']) && strpos($r['ImagemUrl'], 'uploads/') === 0) {
                $path = __DIR__ . '/' . $r['ImagemUrl'];
                if (is_file($path))
                    @unlink($path);
            }
            $d = $conexao->prepare('DELETE FROM enderecoimagem WHERE idEnderecoImagem = :id');
            $d->execute([':id' => $remId]);
            echo json_encode(['ok' => true]);
            exit;
        }
    }
    echo json_encode(['ok' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $preco = $_POST['preco'] ?? '';
    $quant = $_POST['quantidade'] ?? null;
    $categoria = $_POST['categoria'] ?? $produto['Categorias_idCategorias'];
    $marca = trim($_POST['marca'] ?? '');

    if ($nome === '' || $descricao === '' || $preco === '') {
        $errors[] = 'Nome, descrição e preço são obrigatórios.';
    }

    if (empty($errors)) {
        try {
            $conexao->beginTransaction();
            $u = $conexao->prepare('UPDATE produtos SET Nome = :n, Descricao = :d, Preco = :p, Quantidade = :q, Categorias_idCategorias = :c, Marca = :m WHERE idProdutos = :id AND Usuarios_idUsuarios = :uid');
            $u->execute([':n' => $nome, ':d' => $descricao, ':p' => $preco, ':q' => $quant, ':c' => $categoria, ':m' => $marca, ':id' => $id, ':uid' => $userId]);
            // Handle new image uploads (store as LOB only, require at least one image total)
            $newImages = [];
            if (!empty($_FILES['images'])) {
                $uploads = $_FILES['images'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
                for ($i = 0; $i < count($uploads['name']); $i++) {
                    if ($uploads['error'][$i] === UPLOAD_ERR_NO_FILE)
                        continue;
                    if ($uploads['error'][$i] !== UPLOAD_ERR_OK)
                        continue;
                    $tmp = $uploads['tmp_name'][$i];
                    $mime = $finfo->file($tmp) ?: '';
                    if (!array_key_exists($mime, $allowed))
                        continue;
                    $data = file_get_contents($tmp);
                    if ($data === false)
                        continue;
                    $newImages[] = ['data' => $data, 'mime' => $mime];
                }
            }

            // count existing images (after possible removals handled earlier)
            $countStmt = $conexao->prepare('SELECT COUNT(*) FROM enderecoimagem WHERE Produtos_idProdutos = :pid');
            $countStmt->execute([':pid' => $id]);
            $existingCount = (int) $countStmt->fetchColumn();
            if ($existingCount + count($newImages) < 1) {
                throw new Exception('O produto precisa ter pelo menos uma imagem.');
            }

            if (!empty($newImages)) {
                $ins = $conexao->prepare('INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES (:img, :pid)');
                foreach ($newImages as $ni) {
                    $ins->bindValue(':img', $ni['data'], PDO::PARAM_LOB);
                    $ins->bindValue(':pid', $id, PDO::PARAM_INT);
                    $ins->execute();
                }
            }

            $conexao->commit();
            header('Location: meusProdutos.php');
            exit;
        } catch (Exception $e) {
            try {
                $conexao->rollBack();
            } catch (Exception $_) {
            }
            $errors[] = 'Falha ao atualizar: ' . $e->getMessage();
        }
    }
}

function e($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
include 'includes/header.php';
?>

<div class="container mt-4">
    <h2>Editar Produto</h2>
    <form method="POST" enctype="multipart/form-data" id="editarForm">

        <?php if (!empty($imagens)): ?>
            <div class="mb-3">
                <label class="form-label">Imagens atuais (marque para remover)</label>
                <div class="d-flex gap-2 flex-wrap">
                    <div id="existingImages" class="d-flex gap-2 flex-wrap">
                        <?php foreach ($imagens as $img): ?>
                            <div class="existing-item" data-id="<?php echo (int) $img['idEnderecoImagem']; ?>"
                                style="width:120px;text-align:center;margin-bottom:6px;position:relative;">
                                <img src="<?php echo e($img['ImagemUrl']); ?>"
                                    style="width:120px;height:80px;object-fit:cover;border:1px solid #ddd;padding:2px;display:block;margin-bottom:4px;">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-existing"
                                    style="position:absolute;right:6px;top:6px;">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label">Nome do Produto</label>
            <input name="nome" class="form-control" required value="<?php echo e($produto['Nome']); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Descrição</label>
            <textarea name="descricao" class="form-control" rows="4"><?php echo e($produto['Descricao']); ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Categoria</label>
            <select name="categoria" class="form-select">
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo (int) $cat['id']; ?>" <?php echo ($produto['Categorias_idCategorias'] == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['nome']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Preço</label>
            <input name="preco" class="form-control" required value="<?php echo e($produto['Preco']); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Quantidade</label>
            <input name="quantidade" class="form-control" value="<?php echo e($produto['Quantidade']); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Marca</label>
            <input name="marca" class="form-control" value="<?php echo e($produto['Marca']); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Imagens (adicionar)</label>
            <input type="file" name="images[]" id="imagesInput" multiple accept="image/*" class="form-control">
            <div id="previewNew" class="d-flex gap-2 flex-wrap mt-2"></div>
        </div>

        <div class="text-start mt-3">
            <button class="btn btn-primary" type="submit">Salvar</button>
            <a class="btn btn-secondary" href="meusProdutos.php">Cancelar</a>
        </div>
    </form>

</div>

<script>
    // incremental selection for new images and AJAX removal for existing images
    (() => {
        const input = document.getElementById('imagesInput');
        const preview = document.getElementById('previewNew');
        const dt = new DataTransfer();
        function render() {
            preview.innerHTML = '';
            for (let i = 0; i < dt.files.length; i++) {
                const f = dt.files[i];
                const div = document.createElement('div'); div.className = 'preview-item'; div.style.width = '120px'; div.style.textAlign = 'center';
                const img = document.createElement('img'); img.src = URL.createObjectURL(f); img.style.width = '120px'; img.style.height = '80px'; img.style.objectFit = 'cover'; img.style.border = '1px solid #ddd'; img.style.padding = '2px';
                div.appendChild(img);
                const btn = document.createElement('button'); btn.type = 'button'; btn.className = 'btn btn-sm btn-outline-danger'; btn.textContent = '✕'; btn.style.display = 'block'; btn.style.margin = '6px auto 0';
                btn.addEventListener('click', (() => { const idx = i; return () => { const tmp = new DataTransfer(); for (let j = 0; j < dt.files.length; j++) if (j !== idx) tmp.items.add(dt.files[j]); while (dt.files.length > 0) dt.items.remove(0); for (let j = 0; j < tmp.files.length; j++) dt.items.add(tmp.files[j]); render(); }; })());
                div.appendChild(btn); preview.appendChild(div);
            }
            input.files = dt.files;
        }
        if (input) input.addEventListener('change', function (e) { const files = Array.from(e.target.files || []); for (const f of files) dt.items.add(f); render(); });

        // AJAX removal for existing
        document.querySelectorAll('.btn-remove-existing').forEach(btn => {
            btn.addEventListener('click', function () {
                const wrapper = this.closest('.existing-item');
                const id = wrapper.dataset.id;
                if (!confirm('Remover imagem?')) return;
                fetch('editarProduto.php?id=<?php echo $id ?>', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'ajax_remove_image=' + encodeURIComponent(id) })
                    .then(r => r.json()).then(j => {
                        if (j.ok) wrapper.remove(); else alert('Erro ao remover');
                    }).catch(() => alert('Erro de rede'));
            });
        });
    })();
</script>

<?php
// Handle image removal (batch via remove_imgs[] or single remove_img fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Batch removal
    if (!empty($_POST['remove_imgs']) && is_array($_POST['remove_imgs'])) {
        $ids = array_map('intval', $_POST['remove_imgs']);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $q = $conexao->prepare("SELECT idEnderecoImagem, ImagemUrl FROM enderecoimagem WHERE idEnderecoImagem IN ($placeholders) AND Produtos_idProdutos = ?");
            $params = array_merge($ids, [$id]);
            $q->execute($params);
            $rows = $q->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                $del = $conexao->prepare('DELETE FROM enderecoimagem WHERE idEnderecoImagem = ?');
                foreach ($rows as $r) {
                    $path = __DIR__ . '/' . $r['ImagemUrl'];
                    if (file_exists($path))
                        @unlink($path);
                    $del->execute([(int) $r['idEnderecoImagem']]);
                }
            }
            header('Location: editarProduto.php?id=' . $id);
            exit;
        }
    }

    // Single removal fallback
    if (!empty($_POST['remove_img'])) {
        $rem = (int) $_POST['remove_img'];
        $q = $conexao->prepare('SELECT ImagemUrl FROM enderecoimagem WHERE idEnderecoImagem = :id AND Produtos_idProdutos = :pid');
        $q->execute([':id' => $rem, ':pid' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $path = __DIR__ . '/' . $row['ImagemUrl'];
            if (file_exists($path))
                @unlink($path);
            $d = $conexao->prepare('DELETE FROM enderecoimagem WHERE idEnderecoImagem = :id');
            $d->execute([':id' => $rem]);
            header('Location: editarProduto.php?id=' . $id);
            exit;
        }
    }
}

?>