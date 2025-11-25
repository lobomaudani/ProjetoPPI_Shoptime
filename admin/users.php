<?php
// Admin users list + delete
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
if (empty($_SESSION['id']) || empty($_SESSION['cargo']) || (int) $_SESSION['cargo'] !== 1) {
    header('Location: ../index.php');
    exit;
}
include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/user_helpers.php';
include_once __DIR__ . '/../connections/conectarBD.php';

$message = '';
// handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'delete') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $message = 'Token CSRF inválido.';
    } elseif ($id <= 0) {
        $message = 'ID inválido.';
    } else {
        // prevent deleting administrator accounts via this simple UI
        try {
            $r = $conexao->prepare('SELECT Cargos_idCargos FROM usuarios WHERE idUsuarios = :id LIMIT 1');
            $r->execute([':id' => $id]);
            $role = $r->fetchColumn();
            if ($role == 1) {
                $message = 'Remoção de administradores não permitida por aqui.';
            } else {
                $stmt = $conexao->prepare('DELETE FROM usuarios WHERE idUsuarios = :id');
                $stmt->execute([':id' => $id]);
                $message = 'Usuário apagado.';
            }
        } catch (Exception $e) {
            $message = 'Falha ao apagar: ' . $e->getMessage();
        }
    }
}

// fetch users
try {
    $stmt = $conexao->prepare('SELECT u.idUsuarios, u.Nome, u.Email, u.Cargos_idCargos, c.Nome as CargoNome FROM usuarios u LEFT JOIN cargos c ON u.Cargos_idCargos = c.idCargos ORDER BY u.idUsuarios DESC LIMIT 200');
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $users = [];
}
?>
<main style="padding:18px;">
    <h1>Gerenciar Usuários</h1>
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div style="margin-bottom:12px;">
        <a class="btn btn-light" href="index.php">Voltar</a>
    </div>

    <table class="table" style="width:100%;border-collapse:collapse;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Cargo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo (int) $u['idUsuarios']; ?></td>
                    <td><?php echo htmlspecialchars($u['Nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($u['Email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($u['CargoNome'] ?? $u['Cargos_idCargos']); ?></td>
                    <td>
                        <a class="btn btn-sm btn-outline-primary"
                            href="../usuario.php?id=<?php echo (int) $u['idUsuarios']; ?>">Ver / Editar</a>
                        <?php if ((int) $u['Cargos_idCargos'] !== 1): ?>
                            <form method="POST" action="" style="display:inline;margin-left:6px;"
                                onsubmit="return confirm('Apagar este usuário?');">
                                <?php echo csrf_input(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) $u['idUsuarios']; ?>">
                                <button class="btn btn-sm btn-danger" type="submit">Apagar</button>
                            </form>
                        <?php else: ?>
                            <span style="color:#666;margin-left:6px;">(Administrador)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>
<?php
session_start();
include_once __DIR__ . '/../connections/conectarBD.php';
include_once __DIR__ . '/../includes/user_helpers.php';

if (empty($_SESSION['id']) || empty($_SESSION['cargo']) || (int) $_SESSION['cargo'] !== 1) {
    header('Location: ../login.php');
    exit;
}

$action = $_POST['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg = 'Token inválido.';
    } else {
        if ($action === 'delete' && !empty($_POST['user_id'])) {
            $uid = (int) $_POST['user_id'];
            try {
                // Gather files to remove
                $files = [];
                $pstmt = $conexao->prepare('SELECT e.ImagemUrl FROM enderecoimagem e JOIN produtos p ON p.idProdutos = e.Produtos_idProdutos WHERE p.Usuarios_idUsuarios = :uid');
                $pstmt->execute([':uid' => $uid]);
                while ($r = $pstmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!empty($r['ImagemUrl']) && strpos($r['ImagemUrl'], 'uploads/') === 0) {
                        $files[] = __DIR__ . '/../' . $r['ImagemUrl'];
                    }
                }

                $conexao->beginTransaction();
                // delete product images, produtos, favoritos, mensagens, enderecos
                $delImgs = $conexao->prepare('DELETE e FROM enderecoimagem e JOIN produtos p ON p.idProdutos = e.Produtos_idProdutos WHERE p.Usuarios_idUsuarios = :uid');
                $delImgs->execute([':uid' => $uid]);
                $delProd = $conexao->prepare('DELETE FROM produtos WHERE Usuarios_idUsuarios = :uid');
                $delProd->execute([':uid' => $uid]);
                $delFav = $conexao->prepare('DELETE FROM favoritos WHERE Usuarios_idUsuarios = :uid');
                $delFav->execute([':uid' => $uid]);
                $delAddr = $conexao->prepare('DELETE FROM enderecos WHERE Usuarios_idUsuarios = :uid');
                $delAddr->execute([':uid' => $uid]);
                $delMsgs1 = $conexao->prepare('DELETE FROM mensagens WHERE DeUsuarios_idUsuarios = :uid OR ParaUsuarios_idUsuarios = :uid');
                $delMsgs1->execute([':uid' => $uid]);
                $delUser = $conexao->prepare('DELETE FROM usuarios WHERE idUsuarios = :uid');
                $delUser->execute([':uid' => $uid]);
                $conexao->commit();
                foreach (array_unique($files) as $f)
                    if (is_file($f))
                        @unlink($f);
                $msg = 'Usuário removido.';
            } catch (Exception $e) {
                if ($conexao->inTransaction())
                    $conexao->rollBack();
                $msg = 'Erro ao remover: ' . $e->getMessage();
            }
        } elseif ($action === 'update' && !empty($_POST['user_id'])) {
            $uid = (int) $_POST['user_id'];
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $cargo = (int) ($_POST['cargo'] ?? 3);
            $senha = $_POST['senha'] ?? '';
            try {
                if ($senha !== '') {
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $up = $conexao->prepare('UPDATE usuarios SET nome = :nome, email = :email, Cargos_idCargos = :cargo, senha = :senha WHERE idUsuarios = :id');
                    $up->execute([':nome' => $nome, ':email' => $email, ':cargo' => $cargo, ':senha' => $hash, ':id' => $uid]);
                } else {
                    $up = $conexao->prepare('UPDATE usuarios SET nome = :nome, email = :email, Cargos_idCargos = :cargo WHERE idUsuarios = :id');
                    $up->execute([':nome' => $nome, ':email' => $email, ':cargo' => $cargo, ':id' => $uid]);
                }
                $msg = 'Usuário atualizado.';
            } catch (Exception $e) {
                $msg = 'Erro ao atualizar: ' . $e->getMessage();
            }
        }
    }
}

// fetch users
$users = [];
try {
    $stmt = $conexao->query('SELECT idUsuarios, nome, email, Cargos_idCargos, DataNascimento FROM usuarios ORDER BY idUsuarios DESC');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $users = [];
}

function e($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin - Usuários</title>
    <link href="../styles/styles.css" rel="stylesheet">
    <?php $pageTitle = 'Admin - Usuários';
    include __DIR__ . '/../includes/head.php'; ?>
</head>

<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="container mt-4 admin-panel">
        <h2>Gerenciar Usuários</h2>
        <?php if (!empty($msg)): ?>
            <div class="alert alert-info"><?php echo e($msg); ?></div><?php endif; ?>

        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Cargo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo (int) $u['idUsuarios']; ?></td>
                        <td><?php echo e($u['nome']); ?></td>
                        <td><?php echo e($u['email']); ?></td>
                        <td><?php echo (int) $u['Cargos_idCargos']; ?></td>
                        <td>
                            <a class="btn btn-sm btn-secondary"
                                href="users.php?edit=<?php echo (int) $u['idUsuarios']; ?>">Editar</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Remover usuário?');">
                                <?php echo csrf_input(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?php echo (int) $u['idUsuarios']; ?>">
                                <button class="btn btn-sm btn-danger" type="submit">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty($_GET['edit'])):
            $editId = (int) $_GET['edit'];
            $found = null;
            foreach ($users as $uu)
                if ((int) $uu['idUsuarios'] === $editId) {
                    $found = $uu;
                    break;
                }
            if ($found): ?>
                <hr>
                <h3>Editar usuário #<?php echo $editId; ?></h3>
                <form method="POST">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" value="<?php echo $editId; ?>">
                    <div class="mb-3"><label class="form-label">Nome</label><input class="form-control" name="nome"
                            value="<?php echo e($found['nome']); ?>"></div>
                    <div class="mb-3"><label class="form-label">Email</label><input class="form-control" name="email"
                            value="<?php echo e($found['email']); ?>"></div>
                    <div class="mb-3"><label class="form-label">Cargo (1=Admin,2=Mod,3=User,4=Vendedor)</label><input
                            class="form-control" name="cargo" value="<?php echo (int) $found['Cargos_idCargos']; ?>"></div>
                    <div class="mb-3"><label class="form-label">Nova senha (deixe em branco para não alterar)</label><input
                            class="form-control" name="senha" type="password"></div>
                    <div class="text-end"><button class="btn btn-primary" type="submit">Salvar</button></div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">Usuário não encontrado.</div>
            <?php endif; endif; ?>

    </main>
</body>

</html>