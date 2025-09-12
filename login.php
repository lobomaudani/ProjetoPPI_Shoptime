<?php
session_start();

include_once "connections/conectarBD.php";
$mensagem_status = '';
$tipo_mensagem = '';
$usuarios = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {

        $stmt = $conexao->prepare("SELECT id, nome, senha FROM usuarios WHERE nome = ?");
        $stmt->execute([$username]);
        $usuario = $stmt->fetch();

    } catch (PDOException $e) {
        $tipo_mensagem = 'error';
        $mensagem_status = "Erro: " . $e->getMessage();
    }

    if ($username === $usuario['nome'] && $password === $usuario['senha']) {
        // Autenticação bem-sucedida
        session_regenerate_id(true); //utilizado para protecao Session Fixation
        // 1. Armazena o nome de usuário na sessão
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;

        // 2. Define um cookie para "lembrar-me" por 7 dias
        if (isset($_POST['rememberme'])) {
            $cookie_name = 'user_login';
            $cookie_value = $username;
            $cookie_expire = time() + (60 * 60 * 24 * 7);
            setcookie($cookie_name, $cookie_value, $cookie_expire, '/');
        }
        // Redireciona para a página de dashboard
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Usuário ou senha inválidos.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <title>ShowTime - Login</title>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .login-container {
            max-width: 400px;
            margin-top: 100px;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        header {
            background: #ff4553;
            /* Vermelho principal */
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
        }

        header .logo {
            font-size: 1.6rem;
            font-weight: bold;
        }

        .btn-primary {
            background-color: #e63946;
            border-color: #bf2c37;
        }

        .link-register {
            color: #e32c29;
        }
    </style>
</head>

<body>

    <header>
        <a href="index.html"><img src="images/showtime-logo.png" alt="Promoção 3" width="160" height="40" /></a>
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

                <form action="register.php" method="POST">
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
                        <label class="form-check-label" for="rememberme">Lembrar-me</label>
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