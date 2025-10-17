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
$stmt = $conexao->prepare('SELECT idUsuarios, nome, email, DataNascimento, ImagemUrl FROM usuarios WHERE idUsuarios = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$addresses = [];
$adStmt = $conexao->prepare('SELECT * FROM Endereco WHERE Usuarios_idUsuarios = :id');
$adStmt->execute([':id' => $userId]);
$addresses = $adStmt->fetchAll(PDO::FETCH_ASSOC);

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
            // foto opcional
            $imagemUrl = $user['ImagemUrl'];
            if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $saved = save_profile_photo($_FILES['profile_photo'], 2 * 1024 * 1024);
                    $imagemUrl = $saved['url'];
                } catch (Exception $ex) {
                    $mensagem = $ex->getMessage();
                    $tipo_mensagem = 'error';
                }
            }

            // atualizar usuário
            $up = $conexao->prepare('UPDATE usuarios SET nome = :nome, DataNascimento = :nasc, ImagemUrl = :img WHERE idUsuarios = :id');
            $up->execute([':nome' => $nome, ':nasc' => $dataNascimento, ':img' => $imagemUrl, ':id' => $userId]);

            // endereços: simplificar - apagar os atuais e inserir os enviados (até 3)
            $del = $conexao->prepare('DELETE FROM Endereco WHERE Usuarios_idUsuarios = :id');
            $del->execute([':id' => $userId]);
            if (!empty($_POST['endereco']) && is_array($_POST['endereco'])) {
                $insAddr = $conexao->prepare("INSERT INTO Endereco (Rua, Numero, Bairro, Cidade, Estado, CEP, Usuarios_idUsuarios) VALUES (:rua, :numero, :bairro, :cidade, :estado, :cep, :uid)");
                $count = 0;
                foreach ($_POST['endereco'] as $addr) {
                    if ($count >= 3) break;
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
            // recarregar
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
<header><?php include 'includes/logo.inc'; ?></header>
<main class="container mt-4">
<div class="row justify-content-center">
<div class="col-12 col-md-9 col-lg-7">
<div class="card shadow-sm p-3">
<h4>Meu Perfil</h4>
<?php if ($mensagem): ?>
<div class="alert alert-<?php echo htmlspecialchars($tipo_mensagem); ?>"><?php echo htmlspecialchars($mensagem); ?></div>
<?php endif; ?>
<form action="" method="post" enctype="multipart/form-data">
<div class="mb-3">
<label class="form-label">Nome</label>
<input class="form-control" name="username" value="<?php echo htmlspecialchars($user['nome']); ?>">
</div>
<div class="mb-3">
<label class="form-label">Data de Nascimento</label>
<input class="form-control" type="date" name="data_nascimento" value="<?php echo htmlspecialchars($user['DataNascimento']); ?>">
</div>
<div class="mb-3">
<label class="form-label">Foto de Perfil</label>
<?php if (!empty($user['ImagemUrl'])): ?>
<div class="mb-2"><img src="<?php echo htmlspecialchars($user['ImagemUrl']); ?>" alt="profile" width="120"></div>
<?php endif; ?>
<input type="file" name="profile_photo" accept="image/*" class="form-control">
</div>
<div class="mb-3">
<label class="form-label">Endereços (até 3)</label>
<div class="accordion" id="addressesAccordion">
<?php for ($i=0;$i<3;$i++): $addr = $addresses[$i] ?? []; ?>
    <div class="accordion-item">
        <h2 class="accordion-header" id="head<?php echo $i; ?>">
            <button class="accordion-button <?php echo empty($addr) ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#addr<?php echo $i; ?>" aria-expanded="<?php echo empty($addr) ? 'false' : 'true'; ?>" aria-controls="addr<?php echo $i; ?>">
                Endereço <?php echo $i+1; ?>
            </button>
        </h2>
        <div id="addr<?php echo $i; ?>" class="accordion-collapse collapse <?php echo empty($addr) ? '' : 'show'; ?>" aria-labelledby="head<?php echo $i; ?>" data-bs-parent="#addressesAccordion">
            <div class="accordion-body">
                <input class="form-control mb-1" name="endereco[<?php echo $i; ?>][rua]" placeholder="Rua" value="<?php echo htmlspecialchars($addr['Rua'] ?? ''); ?>">
                <input class="form-control mb-1" name="endereco[<?php echo $i; ?>][numero]" placeholder="Número" value="<?php echo htmlspecialchars($addr['Numero'] ?? ''); ?>">
                <input class="form-control mb-1" name="endereco[<?php echo $i; ?>][bairro]" placeholder="Bairro" value="<?php echo htmlspecialchars($addr['Bairro'] ?? ''); ?>">
                <input class="form-control mb-1" name="endereco[<?php echo $i; ?>][cidade]" placeholder="Cidade" value="<?php echo htmlspecialchars($addr['Cidade'] ?? ''); ?>">
                <input class="form-control mb-1" name="endereco[<?php echo $i; ?>][estado]" placeholder="Estado" value="<?php echo htmlspecialchars($addr['Estado'] ?? ''); ?>">
                <input class="form-control" name="endereco[<?php echo $i; ?>][cep]" placeholder="CEP" value="<?php echo htmlspecialchars($addr['CEP'] ?? ''); ?>">
            </div>
        </div>
    </div>
<?php endfor; ?>
</div>
</div>
<div class="text-end">
<button class="btn btn-primary" type="submit">Salvar</button>
</div>
</form>
</div>
</div>
</div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>