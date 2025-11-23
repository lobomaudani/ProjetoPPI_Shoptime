<?php
session_start();
include_once 'connections/conectarBD.php';
include_once 'includes/user_helpers.php';

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$mensagem = null;
$tipo_mensagem = null;
$userId = $_SESSION['id'];

// carregar usuário e endereços
$stmt = $conexao->prepare('SELECT idUsuarios, nome, email, DataNascimento FROM usuarios WHERE idUsuarios = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$addresses = [];
$adStmt = $conexao->prepare('SELECT * FROM enderecos WHERE Usuarios_idUsuarios = :id');
$adStmt->execute([':id' => $userId]);
$addresses = $adStmt->fetchAll(PDO::FETCH_ASSOC);

// counts for user stats
$prodCountStmt = $conexao->prepare('SELECT COUNT(*) FROM produtos WHERE Usuarios_idUsuarios = :id');
$prodCountStmt->execute([':id' => $userId]);
$prodCount = (int) $prodCountStmt->fetchColumn();

$favCountStmt = $conexao->prepare('SELECT COUNT(*) FROM favoritos WHERE Usuarios_idUsuarios = :id');
$favCountStmt->execute([':id' => $userId]);
$favCount = (int) $favCountStmt->fetchColumn();

// Handle account deletion (confirmed via POST delete_account)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    // CSRF check
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $mensagem = 'Token inválido. Tente novamente.';
        $tipo_mensagem = 'error';
    } else {
        // require current password for security
        $provided = $_POST['confirm_password'] ?? '';
        $pwStmt = $conexao->prepare('SELECT Senha FROM usuarios WHERE idUsuarios = :id');
        $pwStmt->execute([':id' => $userId]);
        $row = $pwStmt->fetch(PDO::FETCH_ASSOC);
        $hash = $row['Senha'] ?? '';
        if (!password_verify($provided, $hash)) {
            $mensagem = 'Senha incorreta. A conta não foi removida.';
            $tipo_mensagem = 'error';
        } else {
            // proceed with deletion
            // collect file paths to remove after DB deletion
            $filesToRemove = [];

            // collect product image paths
            try {
                $q = $conexao->prepare("SELECT e.ImagemUrl FROM enderecoimagem e JOIN produtos p ON p.idProdutos = e.Produtos_idProdutos WHERE p.Usuarios_idUsuarios = :uid");
                $q->execute([':uid' => $userId]);
                $rows = $q->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    if (!empty($r['ImagemUrl']) && is_string($r['ImagemUrl']) && strpos($r['ImagemUrl'], 'uploads/') === 0) {
                        $filesToRemove[] = __DIR__ . '/' . $r['ImagemUrl'];
                        $filesToRemove[] = preg_replace('/(\.[^.]+)$/', '_thumb$1', __DIR__ . '/' . $r['ImagemUrl']);
                    }
                }
            } catch (Exception $_) {
                // ignore
            }

            // delete user and cascade (FKs should remove related rows)
            try {
                $conexao->beginTransaction();
                $del = $conexao->prepare('DELETE FROM usuarios WHERE idUsuarios = :id');
                $del->execute([':id' => $userId]);
                $conexao->commit();

                // remove files
                foreach (array_unique($filesToRemove) as $f) {
                    if (is_file($f))
                        @unlink($f);
                }

                // destroy session and redirect
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(
                        session_name(),
                        '',
                        time() - 42000,
                        $params['path'],
                        $params['domain'],
                        $params['secure'],
                        $params['httponly']
                    );
                }
                session_destroy();
                header('Location: index.php');
                exit;
            } catch (Exception $e) {
                if ($conexao->inTransaction())
                    $conexao->rollBack();
                $mensagem = 'Falha ao deletar conta: ' . $e->getMessage();
                $tipo_mensagem = 'error';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // atualizar nome, data de nascimento, foto e endereços
    $nome = trim($_POST['username'] ?? $user['nome']);
    $dataNascimento = trim($_POST['data_nascimento'] ?? $user['DataNascimento']);

    // validar idade
    $dob = DateTime::createFromFormat('Y-m-d', $dataNascimento);
    if (!$dob) {
        $mensagem = 'Data inválida';
        $tipo_mensagem = 'error';
    } else {
        $age = (new DateTime())->diff($dob)->y;
        if ($age < 16) {
            $mensagem = 'Você deve ter ao menos 16 anos.';
            $tipo_mensagem = 'error';
        } else {
            // atualizar usuário (sem foto de perfil)
            $up = $conexao->prepare('UPDATE usuarios SET nome = :nome, DataNascimento = :nasc WHERE idUsuarios = :id');
            $up->execute([':nome' => $nome, ':nasc' => $dataNascimento, ':id' => $userId]);

            // endereços: simplificar - apagar os atuais e inserir os enviados (até 3)
            $del = $conexao->prepare('DELETE FROM enderecos WHERE Usuarios_idUsuarios = :id');
            $del->execute([':id' => $userId]);
            if (!empty($_POST['endereco']) && is_array($_POST['endereco'])) {
                $insAddr = $conexao->prepare("INSERT INTO enderecos (Rua, Numero, Bairro, Cidade, Estado, CEP, Usuarios_idUsuarios) VALUES (:rua, :numero, :bairro, :cidade, :estado, :cep, :uid)");
                $count = 0;
                foreach ($_POST['endereco'] as $addr) {
                    if ($count >= 3)
                        break;
                    $rua = trim($addr['rua'] ?? '');
                    $numero = trim($addr['numero'] ?? '');
                    $bairro = trim($addr['bairro'] ?? '');
                    $cidade = trim($addr['cidade'] ?? '');
                    $estado = trim($addr['estado'] ?? '');
                    $cep = trim($addr['cep'] ?? '');
                    if ($rua !== '' || $cep !== '') {
                        $insAddr->execute([':rua' => $rua, ':numero' => $numero, ':bairro' => $bairro, ':cidade' => $cidade, ':estado' => $estado, ':cep' => $cep, ':uid' => $userId]);
                        $count++;
                    }
                }
            }

            $mensagem = 'Perfil atualizado com sucesso.';
            $tipo_mensagem = 'success';
            // recarregar (redirect so we show fresh data)
            header('Location: usuario.php');
            exit;
        }
    }
}

?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles/styles.css" rel="stylesheet">
    <title>Meu Perfil</title>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-9 col-lg-7">
                <div class="card shadow-sm p-3">
                    <div class="d-flex align-items-center mb-3">
                        <div style="flex:1">
                            <h4 class="mb-0">Meu Perfil</h4>
                            <small class="text-muted"><?php echo htmlspecialchars($user['Email'] ?? ''); ?></small>
                        </div>
                        <div class="text-end">
                            <div><strong><?php echo $prodCount; ?></strong> produtos</div>
                            <div class="text-muted"><strong><?php echo $favCount; ?></strong> favoritos</div>
                        </div>
                    </div>
                    <?php if ($mensagem): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($tipo_mensagem); ?>">
                            <?php echo htmlspecialchars($mensagem); ?>
                        </div>
                    <?php endif; ?>
                    <form action="" method="post" enctype="multipart/form-data">
                        <?php echo csrf_input(); ?>
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input class="form-control" name="username"
                                value="<?php echo htmlspecialchars($user['nome']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data de Nascimento</label>
                            <input class="form-control" type="date" name="data_nascimento"
                                value="<?php echo htmlspecialchars($user['DataNascimento']); ?>">
                        </div>
                        <!-- Foto de perfil removida do formulário -->
                        <div class="mb-3">
                            <label class="form-label">Endereços (até 3)</label>
                            <div class="accordion" id="addressesAccordion">
                                <?php for ($i = 0; $i < 3; $i++):
                                    $addr = $addresses[$i] ?? []; ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="head<?php echo $i; ?>">
                                            <button class="accordion-button <?php echo empty($addr) ? 'collapsed' : ''; ?>"
                                                type="button" data-bs-toggle="collapse"
                                                data-bs-target="#addr<?php echo $i; ?>"
                                                aria-expanded="<?php echo empty($addr) ? 'false' : 'true'; ?>"
                                                aria-controls="addr<?php echo $i; ?>">
                                                Endereço <?php echo $i + 1; ?>
                                            </button>
                                        </h2>
                                        <div id="addr<?php echo $i; ?>"
                                            class="accordion-collapse collapse <?php echo empty($addr) ? '' : 'show'; ?>"
                                            aria-labelledby="head<?php echo $i; ?>" data-bs-parent="#addressesAccordion">
                                            <div class="accordion-body">
                                                <input class="form-control mb-1" name="endereco[<?php echo $i; ?>][rua]"
                                                    placeholder="Rua"
                                                    value="<?php echo htmlspecialchars($addr['Rua'] ?? ''); ?>">
                                                <input class="form-control mb-1" name="endereco[<?php echo $i; ?>][numero]"
                                                    placeholder="Número"
                                                    value="<?php echo htmlspecialchars($addr['Numero'] ?? ''); ?>">
                                                <input class="form-control mb-1" name="endereco[<?php echo $i; ?>][bairro]"
                                                    placeholder="Bairro"
                                                    value="<?php echo htmlspecialchars($addr['Bairro'] ?? ''); ?>">
                                                <input class="form-control mb-1" name="endereco[<?php echo $i; ?>][cidade]"
                                                    placeholder="Cidade"
                                                    value="<?php echo htmlspecialchars($addr['Cidade'] ?? ''); ?>">
                                                <input class="form-control mb-1" name="endereco[<?php echo $i; ?>][estado]"
                                                    placeholder="Estado"
                                                    value="<?php echo htmlspecialchars($addr['Estado'] ?? ''); ?>">
                                                <input class="form-control" name="endereco[<?php echo $i; ?>][cep]"
                                                    placeholder="CEP"
                                                    value="<?php echo htmlspecialchars($addr['CEP'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="change_password.php" class="btn btn-outline-secondary me-2">Mudar senha</a>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                                    data-bs-target="#confirmDeleteAccountModal">Deletar minha conta</button>
                            </div>
                            <div class="text-end">
                                <button class="btn btn-primary" type="submit">Salvar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal confirmar exclusão da conta -->
    <div class="modal fade" id="confirmDeleteAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Deletar conta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja deletar sua conta? Esta ação é irreversível e removerá seus produtos,
                        favoritos e dados.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display:inline">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="delete_account" value="1">
                        <div class="mb-2">
                            <label class="form-label">Confirme sua senha para deletar</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-danger">Deletar minha conta</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>