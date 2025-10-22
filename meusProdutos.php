<?php
require_once 'connections/conectarBD.php';
session_start();

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['id'];

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Handle deletion (must run before any output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_id'])) {
    $delId = (int) $_POST['delete_id'];
    // Verify ownership
    $check = $conexao->prepare('SELECT idProdutos FROM produtos WHERE idProdutos = :id AND Usuarios_idUsuarios = :uid');
    $check->execute([':id' => $delId, ':uid' => $userId]);
    if ($check->fetchColumn()) {
        // Delete uploaded files and enderecoimagem rows
        try {
            $imgs = $conexao->prepare('SELECT ImagemUrl FROM enderecoimagem WHERE Produtos_idProdutos = :pid');
            $imgs->execute([':pid' => $delId]);
            $rows = $imgs->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $path = $r['ImagemUrl'];
                // if stored as a filesystem path (starts with uploads/), unlink
                if (is_string($path) && strpos($path, 'uploads/') === 0) {
                    $full = __DIR__ . '/' . $path;
                    if (is_file($full))
                        @unlink($full);
                }
            }
            // remove enderecoimagem rows
            $delImgs = $conexao->prepare('DELETE FROM enderecoimagem WHERE Produtos_idProdutos = :pid');
            $delImgs->execute([':pid' => $delId]);
        } catch (Exception $e) {
            // ignore image deletion errors but continue with product deletion
        }

        // delete product
        $stmt = $conexao->prepare('DELETE FROM produtos WHERE idProdutos = :id AND Usuarios_idUsuarios = :uid');
        $stmt->execute([':id' => $delId, ':uid' => $userId]);
    }
    header('Location: meusProdutos.php');
    exit;
}

// Count products for pagination
$countStmt = $conexao->prepare('SELECT COUNT(*) FROM produtos WHERE Usuarios_idUsuarios = :uid');
$countStmt->execute([':uid' => $userId]);
$total = (int) $countStmt->fetchColumn();

$stmt = $conexao->prepare('SELECT p.idProdutos, p.Nome, p.Preco, p.Quantidade, e.idEnderecoimagem AS imagem_id, e.ImagemUrl
     FROM produtos p
     LEFT JOIN (
          SELECT idEnderecoimagem, Produtos_idProdutos, ImagemUrl
          FROM enderecoimagem e1
          WHERE e1.idEnderecoimagem = (
               SELECT MIN(e2.idEnderecoimagem)
               FROM enderecoimagem e2
               WHERE e2.Produtos_idProdutos = e1.Produtos_idProdutos
          )
     ) e ON e.Produtos_idProdutos = p.idProdutos
     WHERE p.Usuarios_idUsuarios = :uid
     ORDER BY p.idProdutos DESC
     LIMIT :lim OFFSET :off');
$stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: mostrar os dados dos produtos
error_log("Produtos encontrados: " . print_r($produtos, true));

function e($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Render full page head so styles and Bootstrap are loaded
?>
<!doctype html>
<html lang="pt-br">

<head>
    <?php $pageTitle = 'Meus Produtos - ShowTime';
    include __DIR__ . '/includes/head.php'; ?>
    <style>
        /* page-specific small adjustments (keep site look consistent) */
        .card-img-top {
            max-height: 160px;
            object-fit: cover;
        }
    </style>
</head>

<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h2>Meus Produtos</h2>
        <p>Você tem <?php echo $total; ?> produto(s).</p>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>Seu painel de produtos</div>
            <div>
                <a href="cadastroProdutos.php" class="btn btn-primary">+ Novo Produto</a>
            </div>
        </div>

        <div class="row">
            <?php foreach ($produtos as $p): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($p['imagem_id'])): ?>
                            <img src="serve_imagem.php?id=<?php echo (int) $p['imagem_id']; ?>" class="card-img-top"
                                style="height:160px;object-fit:cover;" alt="">
                        <?php else: ?>
                            <div
                                style="height:160px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;color:#999">
                                Sem imagem</div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title small mb-1"><?php echo e($p['Nome']); ?></h5>
                            <p class="mb-1 text-muted">R$ <?php echo number_format($p['Preco'], 2, ',', '.'); ?></p>
                            <p class="mb-2 small">Estoque: <?php echo (int) $p['Quantidade']; ?></p>
                            <div class="mt-auto d-flex gap-2">
                                <a href="produto.php?id=<?php echo $p['idProdutos']; ?>"
                                    class="btn btn-sm btn-outline-primary">Ver</a>
                                <a href="editarProduto.php?id=<?php echo $p['idProdutos']; ?>"
                                    class="btn btn-sm btn-secondary">Editar</a>
                                <button class="btn btn-sm btn-danger btn-delete-prod"
                                    data-prod-id="<?php echo (int) $p['idProdutos']; ?>">Excluir</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total > $perPage): ?>
            <nav>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= ceil($total / $perPage); $i++): ?>
                        <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Modal de confirmação Bootstrap -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Deseja realmente excluir este produto? Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" id="deleteProductForm" style="display:inline">
                        <input type="hidden" name="delete_id" id="delete_id_input" value="">
                        <button class="btn btn-danger" type="submit">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // abrir modal e colocar id (com fallback se bootstrap não estiver presente)
        (function () {
            var deleteButtons = document.querySelectorAll('.btn-delete-prod');
            deleteButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = this.dataset.prodId;
                    var input = document.getElementById('delete_id_input');
                    if (input) input.value = id;
                    // se Bootstrap Modal estiver disponível, usar modal; caso contrário, confirmar e submeter
                    if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
                        var modalEl = document.getElementById('confirmDeleteModal');
                        var myModal = new bootstrap.Modal(modalEl);
                        myModal.show();
                    } else {
                        if (confirm('Deseja realmente excluir este produto?')) {
                            // submeter formulário diretamente
                            var form = document.getElementById('deleteProductForm');
                            if (form) form.submit();
                            else {
                                // fallback: criar form e submeter
                                var f = document.createElement('form');
                                f.method = 'POST'; f.action = '';
                                var h = document.createElement('input'); h.type = 'hidden'; h.name = 'delete_id'; h.value = id; f.appendChild(h);
                                document.body.appendChild(f);
                                f.submit();
                            }
                        }
                    }
                });
            });
        })();
    </script>

    <?php
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>