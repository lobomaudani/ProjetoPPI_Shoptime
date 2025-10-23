<?php
session_start();
include_once 'connections/conectarBD.php';
include_once 'includes/user_helpers.php';

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}
$userId = (int) $_SESSION['id'];
$mensagem = null;
$tipo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $mensagem = 'Token inválido.';
        $tipo = 'danger';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($new === '' || $confirm === '' || $current === '') {
            $mensagem = 'Preencha todos os campos.';
            $tipo = 'danger';
        } elseif ($new !== $confirm) {
            $mensagem = 'Nova senha e confirmação não coincidem.';
            $tipo = 'danger';
        } else {
            $q = $conexao->prepare('SELECT Senha FROM usuarios WHERE idUsuarios = :id');
            $q->execute([':id' => $userId]);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            $hash = $row['Senha'] ?? '';
            if (!password_verify($current, $hash)) {
                $mensagem = 'Senha atual incorreta.';
                $tipo = 'danger';
            } else {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $u = $conexao->prepare('UPDATE usuarios SET Senha = :s WHERE idUsuarios = :id');
                $u->execute([':s' => $newHash, ':id' => $userId]);
                $mensagem = 'Senha alterada com sucesso.';
                $tipo = 'success';
            }
        }
    }
}

?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Alterar Senha</title>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <main class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6">
                <div class="card p-3">
                    <h4>Alterar Senha</h4>
                    <?php if ($mensagem): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($tipo); ?>">
                            <?php echo htmlspecialchars($mensagem); ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <?php echo csrf_input(); ?>
                        <div class="mb-3"><label class="form-label">Senha atual</label><input type="password"
                                name="current_password" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Nova senha</label><input type="password"
                                name="new_password" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Confirmar nova senha</label><input type="password"
                                name="confirm_password" class="form-control" required></div>
                        <div class="text-end"><a href="usuario.php"
                                class="btn btn-outline-secondary me-2">Cancelar</a><button class="btn btn-primary"
                                type="submit">Alterar</button></div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>