<?php
session_start();

include_once "connections/conectarBD.php";
$mensagem_status    = '';
$tipo_mensagem      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailRaw   = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password   = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Validação básica do email
    $email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $error = 'Email inválido.';
    } elseif ($password === '') {
        $error = 'Senha não pode ser vazia.';
    } else {
        try {
            $stmt = $conexao->prepare("SELECT idUsuarios, email, nome, senha FROM usuarios WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $storedHash = $usuario['senha'];

                // Verifica hash padrão (password_hash) primeiro
                if (password_verify($password, $storedHash)) {
                    $passwordOk = true;
                } else {
                    // Se a senha no banco estiver em texto plano, permite autenticar e rehash
                    if ($storedHash === $password) {
                        $passwordOk = true;
                        // Re-hash da senha e atualização no banco
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $upd = $conexao->prepare("UPDATE usuarios SET senha = :senha WHERE idUsuarios = :id");
                        $upd->execute([':senha' => $newHash, ':id' => $usuario['idUsuarios']]);
                    } else {
                        $passwordOk = false;
                    }
                }

                if ($passwordOk) {
                    session_regenerate_id(true);
                    $_SESSION['loggedin'] = true;
                    $_SESSION['email'] = $email;
                    $_SESSION['nome'] = $usuario['nome'];

                    if (isset($_POST['rememberme'])) {
                        $cookie_name    = 'user_login';
                        $cookie_value   = $email;
                        $cookie_expire  = time() + (60 * 60 * 24 * 7);
                        $secure_flag    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                        // usa array de opções (PHP 7.3+)
                        setcookie($cookie_name, $cookie_value, [
                            'expires'  => $cookie_expire,
                            'path'     => '/',
                            'httponly' => true,
                            'secure'   => $secure_flag,
                            'samesite' => 'Lax'
                        ]);
                    }

                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Usuário ou senha inválidos.';
                }
            } else {
                $error = 'Usuário ou senha inválidos.';
            }

        } catch (PDOException $e) {
            $tipo_mensagem  = 'error';
            $error          = "Erro: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles/styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" href="images/favicon.ico">
    <title>ShowTime - Entrar</title>
</head>

<body>

    <header>
        <?php include 'includes/logo.inc'; ?>
    </header>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 login-container">
                <h2 class="text-center mb-4">Entre na sua conta:</h2>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form action="<?= $_SERVER['PHP_SELF']; ?>" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="text" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberme" name="rememberme">
                        <label class="form-check-label" for="rememberme">Lembrar-me por 7 dias</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Entrar</button>
                    </div>
                    <p2>Não possui conta? <a href="register.php"><ins class="link-register">Cadastre-se
                                aqui!</ins></a>
                    </p2>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>