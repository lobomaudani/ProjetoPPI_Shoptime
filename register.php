<?php
session_start();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // echo $password = $_POST["password"];
    // echo $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    // var_dump (password_verify($password, $passwordHash));
    // exit;

    $password = htmlspecialchars(trim($_POST["password"]));
    $passwordConfirm = htmlspecialchars(trim($_POST["password-confirm"]));

    if ($password != $passwordConfirm) {
        $error = "As senhas não estão validando entre si!";
    } else {
        include 'connections/conectarBD.php';

        $autorizadoInsercao = true;
        $nome = $_POST["username"];
        $cpf = $_POST["cpf"];
        $email = $_POST["email"];        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmtEmail = $conexao->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $stmtEmail->execute([':email' => $email]);        

            $stmtCpf = $conexao->prepare("SELECT COUNT(*) FROM usuarios WHERE cpf = :cpf");
            $stmtCpf->execute([':cpf' => $cpf]);

            if ($stmtEmail->fetchColumn() > 0) {
                $tipo_mensagem = 'error';
                $mensagem_status = "Este e-mail já está cadastrado.";
                $autorizadoInsercao = false;
            } else if($stmtCpf->fetchColumn() > 0){
                $tipo_mensagem = 'error';
                $mensagem_status = "Este CPF já está cadastrado.";
                $autorizadoInsercao = false;
            }

            $idCargo = 1;

            if($autorizadoInsercao) {
                $stmt = $conexao->prepare("INSERT INTO usuarios (nome, cpf, email, senha, Cargos_idCargos)
                                                    VALUES (:nome, :cpf, :email, :senha, :cargo)");
                $stmt->execute([
                    ':nome' => $nome,
                    ':cpf' => $cpf,
                    ':email' => $email,
                    ':senha' => $passwordHash,
                    ':cargo' => $idCargo
                ]);

                $tipo_mensagem = 'success';
                $error = "Cadastro realizado com sucesso!";

                $stmt = $conexao->prepare("SELECT idUsuarios, email, nome, senha FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                $usuario = $stmt->fetch();
                session_regenerate_id(true); 
                $_SESSION['loggedin'] = true;
                $_SESSION['email'] = $email;
                $_SESSION['nome'] = $usuario['nome'];

                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $tipo_mensagem = 'error';
            $error = "Erro no banco de dados: " . $e->getMessage();
        }
    }
} else {

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
    <title>ShowTime - Cadastrar-se</title>

</head>

<body>

    <header>
        <?php include 'includes/logo.inc'; ?>
    </header>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 login-container">
                <h2 class="text-center mb-4">Registrar conta:</h2>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nome Completo:</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="cpf" class="form-label">CPF:</label>
                        <input type="text" class="form-control" id="cpf" name="cpf" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="password-confirm" class="form-label">Confirmar Senha:</label>
                        <input type="password" class="form-control" id="password-confirm" name="password-confirm"
                            required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" id="submitBtn" class="btn btn-primary">Entrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <?php //} ?> -->
</body>

</html>