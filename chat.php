<?php
session_start();
include_once __DIR__ . '/connections/conectarBD.php';

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}
$me = (int) $_SESSION['id'];

// list distinct conversations where user is sender or recipient, grouped by product and other user
$sql = "SELECT 
    GREATEST(m.DeUsuarios_idUsuarios, m.ParaUsuarios_idUsuarios) AS umax,
    LEAST(m.DeUsuarios_idUsuarios, m.ParaUsuarios_idUsuarios) AS umin,
    m.Produtos_idProdutos AS produto_id,
    MAX(m.id) AS last_id
    FROM mensagens m
    WHERE m.DeUsuarios_idUsuarios = :me OR m.ParaUsuarios_idUsuarios = :me
    GROUP BY umin, umax, m.Produtos_idProdutos
    ORDER BY last_id DESC
";
$stmt = $conexao->prepare($sql);
$stmt->execute([':me' => $me]);
$convs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// enrich with product and user names
$threads = [];
foreach ($convs as $c) {
    $lastId = (int) $c['last_id'];
    // fetch last message
    $lm = $conexao->prepare('SELECT m.*, u.Nome AS deNome FROM mensagens m LEFT JOIN usuarios u ON u.idUsuarios = m.DeUsuarios_idUsuarios WHERE m.id = :id LIMIT 1');
    $lm->execute([':id' => $lastId]);
    $last = $lm->fetch(PDO::FETCH_ASSOC);
    $produto = null;
    $otherUser = null;
    if ($c['produto_id']) {
        $p = $conexao->prepare('SELECT idProdutos, Nome FROM produtos WHERE idProdutos = :pid');
        $p->execute([':pid' => $c['produto_id']]);
        $produto = $p->fetch(PDO::FETCH_ASSOC);
    }
    $otherId = ($last['DeUsuarios_idUsuarios'] == $me) ? (int) $last['ParaUsuarios_idUsuarios'] : (int) $last['DeUsuarios_idUsuarios'];
    $ou = $conexao->prepare('SELECT idUsuarios, Nome FROM usuarios WHERE idUsuarios = :id');
    $ou->execute([':id' => $otherId]);
    $otherUser = $ou->fetch(PDO::FETCH_ASSOC);
    $threads[] = ['produto' => $produto, 'other' => $otherUser, 'last' => $last];
}

?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="styles/styles.css" rel="stylesheet">
    <title>Conversas - ShowTime</title>
</head>

<body>
    <?php include_once __DIR__ . '/includes/header.php'; ?>
    <main class="container my-4">
        <h3>Conversas</h3>
        <div class="conversations-list">
            <?php if (empty($threads)): ?>
                <p>Você ainda não tem conversas.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($threads as $t): ?>
                        <li style="margin-bottom:12px; list-style:none;">
                            <a
                                href="chat_thread.php?produto=<?php echo (int) ($t['produto']['idProdutos'] ?? 0); ?>&other=<?php echo (int) ($t['other']['idUsuarios'] ?? 0); ?>">
                                <strong><?php echo htmlspecialchars($t['other']['Nome'] ?? 'Vendedor'); ?></strong>
                                <?php if (!empty($t['produto'])): ?>
                                    <div style="font-size:0.9rem;">Produto: <?php echo htmlspecialchars($t['produto']['Nome']); ?>
                                    </div>
                                <?php endif; ?>
                                <div style="color:#666; font-size:0.9rem;">Última:
                                    <?php echo nl2br(htmlspecialchars(mb_substr($t['last']['Mensagem'], 0, 120))); ?></div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>