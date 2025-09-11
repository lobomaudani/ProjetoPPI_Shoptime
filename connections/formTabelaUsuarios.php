<?php

require_once "conectarBD.php";

$mensagem_status = '';
$tipo_mensagem = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    if (empty($nome) || empty($email) || empty($senha)) {
        $tipo_mensagem = 'error';
        $mensagem_status = "Todos os campos são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $tipo_mensagem = 'error';
        $mensagem_status = "Formato de e-mail inválido.";
    } elseif (strlen($senha) < 6) {
        $tipo_mensagem = 'error';
        $mensagem_status = "A senha deve ter no mínimo 6 caracteres.";
    } else {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT); // <-- LINHA CORRIGIDA
        try {
            $stmt = $conexao->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $tipo_mensagem = 'error';
                $mensagem_status = "Este e-mail já está cadastrado.";
            } else {
                $stmt = $conexao->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)");
                $stmt->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':senha' => $senhaHash
                ]);
                $tipo_mensagem = 'success';
                $mensagem_status = "Cadastro realizado com sucesso!";
            }
        } catch (PDOException $e) {
            $tipo_mensagem = 'error';
            $mensagem_status = "Erro no banco de dados: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Styles/estilos.css">
    <title>Document</title>
</head>
<body>
    <div class="container"></div>
</body>
</html>
<?php
if (!empty($mensagem_status)) {
    echo '<div class="message ' . $tipo_mensagem . '">' . htmlspecialchars($mensagem_status) . '</div>';
}
?>