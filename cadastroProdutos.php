<?php

session_start();

include_once "connections/conectarBD.php";
$mensagem_status    = '';
$tipo_mensagem      = '';

// Recebe os dados do formulário
$nome      = $_POST['nome'];
$descricao = $_POST['descricao'];
$preco     = $_POST['preco'];
$unidades  = $_POST['unidades'];
$avaliacao = $_POST['avaliacao'];

// Upload da imagem (opcional)
$nomeImagem = null;
if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
    $pasta = "uploads/";
    if (!is_dir($pasta)) {
        mkdir($pasta, 0777, true);
    }
    $nomeImagem = $pasta . basename($_FILES['imagem']['name']);
    move_uploaded_file($_FILES['imagem']['tmp_name'], $nomeImagem);
}

// Função para transformar nota em estrelas
function exibirEstrelas($nota) {
    $estrelasCheias = floor($nota); // parte inteira
    $meiaEstrela    = ($nota - $estrelasCheias) >= 0.5 ? 1 : 0;
    $estrelasVazias = 5 - $estrelasCheias - $meiaEstrela;

    $html = str_repeat("★", $estrelasCheias); // cheias
    if ($meiaEstrela) {
        $html .= "☆"; // pode usar meia estrela com CSS depois
    }
}

// Aqui você conecta no banco e insere os dados
/*
$pdo = new PDO("mysql:host=localhost;dbname=sua_base", "usuario", "senha");
$sql = "INSERT INTO produtos (nome, descricao, preco, unidades, avaliacao, imagem) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$nome, $descricao, $preco, $unidades, $avaliacao, $nomeImagem]);
*/

echo "<h3>Produto cadastrado com sucesso!</h3>";
echo "<p><strong>Nome:</strong> $nome</p>";
echo "<p><strong>Descrição:</strong> $descricao</p>";
echo "<p><strong>Preço:</strong> R$ $preco</p>";
echo "<p><strong>Unidades:</strong> $unidades</p>";
echo "<p><strong>Avaliação:</strong> " . exibirEstrelas($avaliacao) . " ($avaliacao / 5)</p>";
if ($nomeImagem) {
    echo "<p><img src='$nomeImagem' width='150'></p>";
}
?>

<!DOCTYPE html>

<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link href="styles/styles.css" rel="stylesheet">    
    <link rel="icon" href="images/favicon.ico">
    <title>ShowTime - Cadastro de Produtos</title>
</head>

<body>

    <header>
        <?php include 'includes/logo.inc'; ?>
    </header>

    <h2>Cadastro de Produto</h2>
    <form action="salvar_produto.php" method="POST" enctype="multipart/form-data">
    
    <label>Nome do Produto:</label><br>
    <input type="text" name="nome" required><br><br>

    <label>Descrição:</label><br>
    <textarea name="descricao" required></textarea><br><br>

    <label>Preço:</label><br>
    <input type="number" step="0.01" name="preco" required><br><br>

    <label>Imagem:</label><br>
    <input type="file" name="imagem" accept="image/*"><br><br>

    <label>Unidades em estoque:</label><br>
    <input type="number" name="unidades" value="1" min="1" required><br><br>

    <label>Avaliação (0 a 5):</label><br>
    <input type="number" name="avaliacao" min="0" max="5" step="0.1"><br><br> 
    
    <label>Unidades em estoque:</label><br>
   
    <button type="submit">Cadastrar</button>
  
    </form>
</body>

</html>