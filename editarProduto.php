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

$errors = [];
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

            // Handle new image uploads (multiple)
            if (!empty($_FILES['images'])) {
                $uploads = $_FILES['images'];
                for ($i = 0; $i < count($uploads['name']); $i++) {
                    if ($uploads['error'][$i] === UPLOAD_ERR_NO_FILE)
                        continue;
                    if ($uploads['error'][$i] !== UPLOAD_ERR_OK)
                        continue;
                    $tmp = $uploads['tmp_name'][$i];
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($tmp) ?: '';
                    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
                    if (!array_key_exists($mime, $allowed))
                        continue;
                    $ext = $allowed[$mime];
                    $base = 'prod_' . bin2hex(random_bytes(6)) . '_' . time();
                    $filename = $base . '.' . $ext;
                    $dest = __DIR__ . '/uploads/' . $filename;
                    if (!is_dir(__DIR__ . '/uploads'))
                        mkdir(__DIR__ . '/uploads', 0777, true);
                    if (!move_uploaded_file($tmp, $dest))
                        continue;
                    // insert into enderecoimagem (store path)
                    $ins = $conexao->prepare('INSERT INTO enderecoimagem (ImagemUrl, Produtos_idProdutos) VALUES (:url, :pid)');
                    $ins->execute([':url' => 'uploads/' . $filename, ':pid' => $id]);
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

    <?php if (!empty($imagens)): ?>
        <div class="mb-3">
            <label class="form-label">Imagens atuais (marque para remover)</label>
            <div class="d-flex gap-2 flex-wrap">
                <form method="POST" id="removeImagesForm" onsubmit="return confirm('Remover imagens selecionadas?');">
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach ($imagens as $img): ?>
                            <div style="width:120px;text-align:center;margin-bottom:6px;">
                                <img src="<?php echo e($img['ImagemUrl']); ?>"
                                    style="width:120px;height:80px;object-fit:cover;border:1px solid #ddd;padding:2px;display:block;margin-bottom:4px;">
                                <label style="font-size:12px;display:block;"><input type="checkbox" name="remove_imgs[]"
                                        value="<?php echo (int) $img['idEnderecoImagem']; ?>"> Remover</label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Remover imagens selecionadas</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
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
</div>

<div class="mb-3">
    <label class="form-label">Imagens (adicionar)</label>
    <input type="file" name="images[]" multiple accept="image/*" class="form-control">
</div>

<?php if (!empty($imagens)): ?>
    <div class="mb-3">
        <label class="form-label">Imagens atuais</label>
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach ($imagens as $img): ?>
                <div style="width:120px">
                    <img src="<?php echo e($img['ImagemUrl']); ?>"
                        style="width:120px;height:80px;object-fit:cover;border:1px solid #ddd;padding:2px;">
                    <div class="mt-1">
                        <form method="POST" style="display:inline" onsubmit="return confirm('Remover imagem?');">
                            <input type="hidden" name="remove_img" value="<?php echo (int) $img['idEnderecoImagem']; ?>">
                            <button class="btn btn-sm btn-outline-danger">Remover</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<button class="btn btn-primary">Salvar</button>
<a class="btn btn-secondary" href="meusProdutos.php">Cancelar</a>
</form>
</div>

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