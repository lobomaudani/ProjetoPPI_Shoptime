<?php
session_start();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // echo $password = $_POST["password"];
    // echo $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    // var_dump (password_verify($password, $passwordHash));
    // exit;

    if ($_POST["password"] != $_POST["password-confirm"]) {
        $error = "As senhas não estão validando entre si!";
    } else {
        include 'connections/conectarBD.php';
        include_once 'includes/user_helpers.php';

        $autorizadoInsercao = true;

        try {
            $stmtEmail = $conexao->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $stmtEmail->execute([':email' => $_POST["email"]]);

            $stmtCpf = $conexao->prepare("SELECT COUNT(*) FROM usuarios WHERE cpf = :cpf");
            $stmtCpf->execute([':cpf' => $_POST["cpf"]]);

            if ($stmtEmail->fetchColumn() > 0) {
                $tipo_mensagem = 'error';
                $mensagem_status = "Este e-mail já está cadastrado.";
                $autorizadoInsercao = false;
            } else if ($stmtCpf->fetchColumn() > 0) {
                $tipo_mensagem = 'error';
                $mensagem_status = "Este CPF já está cadastrado.";
                $autorizadoInsercao = false;
            }

            // valida CPF (formato)
            if ($autorizadoInsercao) {
                $cpfRaw = preg_replace('/[^0-9]/', '', $_POST['cpf']);
                if (!validate_cpf($cpfRaw)) {
                    $autorizadoInsercao = false;
                    $tipo_mensagem = 'error';
                    $error = 'CPF inválido.';
                }
            }

            $idCargo = 1;

            if ($autorizadoInsercao) {


                // Preparar valores vindos do POST
                $nomeUsuario = trim($_POST['username']);
                $email = trim($_POST['email']);
                $cpf = trim($_POST['cpf']);
                $password = $_POST['password'];
                $dataNascimento = trim($_POST['data_nascimento'] ?? '');

                // validar data de nascimento e idade mínima 16
                if ($dataNascimento) {
                    $dob = DateTime::createFromFormat('Y-m-d', $dataNascimento);
                    if (!$dob) {
                        throw new Exception('Data de nascimento inválida.');
                    }
                    $today = new DateTime();
                    $age = $today->diff($dob)->y;
                    if ($age < 16) {
                        $tipo_mensagem = 'error';
                        $error = 'É necessário ter 16 anos ou mais para criar uma conta.';
                        $autorizadoInsercao = false;
                    }
                } else {
                    // exigir data de nascimento
                    $tipo_mensagem = 'error';
                    $error = 'Por favor informe sua data de nascimento.';
                    $autorizadoInsercao = false;
                }

                // Profile photo removed: no longer handled or stored

                // Hash da senha (recomendado) antes de salvar
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // Inserir usuário (DataNascimento) — sem foto de perfil
                $stmt = $conexao->prepare("INSERT INTO usuarios (nome, cpf, email, senha, DataNascimento, Cargos_idCargos)
                                                    VALUES (:nome, :cpf, :email, :senha, :nasc, :cargo)");
                $stmt->execute([
                    ':nome' => $nomeUsuario,
                    ':cpf' => $cpf,
                    ':email' => $email,
                    ':senha' => $passwordHash,
                    ':nasc' => $dataNascimento,
                    ':cargo' => $idCargo
                ]);

                $tipo_mensagem = 'success';
                $error = "Cadastro realizado com sucesso!";

                // Registrar sessão com o id do usuário recém-criado
                $newUserId = $conexao->lastInsertId();
                session_regenerate_id(true);
                $_SESSION['loggedin'] = true;
                $_SESSION['email'] = $email;
                $_SESSION['id'] = $newUserId;

                // Inserir até 3 endereços se foram preenchidos
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
                        // inserir apenas se ao menos rua ou cep foi preenchido
                        if ($rua !== '' || $cep !== '') {
                            $insAddr->execute([
                                ':rua' => $rua,
                                ':numero' => $numero,
                                ':bairro' => $bairro,
                                ':cidade' => $cidade,
                                ':estado' => $estado,
                                ':cep' => $cep,
                                ':uid' => $newUserId
                            ]);
                            $count++;
                        }
                    }
                }

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
    <script>
        // validação simples de idade mínima 16 anos no cliente
        (function () {
            const form = document.querySelector('form');
            if (!form) return;
            form.addEventListener('submit', function (e) {
                const dob = document.getElementById('data_nascimento');
                if (dob && dob.value) {
                    const birth = new Date(dob.value);
                    const today = new Date();
                    let age = today.getFullYear() - birth.getFullYear();
                    const m = today.getMonth() - birth.getMonth();
                    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
                    if (age < 16) {
                        e.preventDefault();
                        alert('Você deve ter pelo menos 16 anos para usar o site.');
                    }
                }
            });
        })();
    </script>
    <link rel="icon" href="images/favicon.ico">
    <title>ShowTime - Cadastrar-se</title>
</head>

<body>

    <header>
        <?php include 'includes/logo.inc'; ?>
    </header>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-9 col-lg-7">
                <div class="card shadow-sm p-3">
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
                            <label for="data_nascimento" class="form-label">Data de Nascimento:</label>
                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento"
                                required>
                        </div>

                        <!-- Endereços: accordion collapsible (até 3) -->
                        <div class="mb-3">
                            <label class="form-label">Endereço (até 3) — preencha ao menos Rua ou CEP para cadastrar
                                cada um</label>
                            <div class="accordion" id="addressesAccordion">
                                <?php for ($i = 0; $i < 3; $i++): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?= $i ?>">
                                            <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#collapse<?= $i ?>"
                                                aria-expanded="false" aria-controls="collapse<?= $i ?>">
                                                Endereço <?= $i + 1 ?>
                                            </button>
                                        </h2>
                                        <div id="collapse<?= $i ?>" class="accordion-collapse collapse"
                                            aria-labelledby="heading<?= $i ?>" data-bs-parent="#addressesAccordion">
                                            <div class="accordion-body">
                                                <input type="text" class="form-control mb-1" name="endereco[<?= $i ?>][rua]"
                                                    placeholder="Rua">
                                                <input type="text" class="form-control mb-1"
                                                    name="endereco[<?= $i ?>][numero]" placeholder="Número">
                                                <input type="text" class="form-control mb-1"
                                                    name="endereco[<?= $i ?>][bairro]" placeholder="Bairro">
                                                <input type="text" class="form-control mb-1"
                                                    name="endereco[<?= $i ?>][cidade]" placeholder="Cidade">
                                                <input type="text" class="form-control mb-1"
                                                    name="endereco[<?= $i ?>][estado]" placeholder="Estado">
                                                <input type="text" class="form-control" name="endereco[<?= $i ?>][cep]"
                                                    placeholder="CEP">
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
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
                        <div class="text-end mt-3">
                            <a href="index.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" id="submitBtn" class="btn btn-primary">Cadastrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <?php //} ?> -->
</body>

</html>