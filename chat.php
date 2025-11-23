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
        // try to get a thumbnail for this product
        $img = $conexao->prepare('SELECT ImagemUrl FROM enderecoimagem WHERE Produtos_idProdutos = :pid LIMIT 1');
        $img->execute([':pid' => $c['produto_id']]);
        $imgRow = $img->fetch(PDO::FETCH_ASSOC);
        $thumb = 'images/no-image.png';
        if ($imgRow && !empty($imgRow['ImagemUrl'])) {
            $d = $imgRow['ImagemUrl'];
            if (is_string($d) && (strpos($d, 'data:') === 0 || strpos($d, 'uploads/') === 0 || strpos($d, 'images/') === 0 || strpos($d, 'http') === 0)) {
                $thumb = $d;
            } else {
                // assume binary blob
                $thumb = 'data:image/jpeg;base64,' . base64_encode($d);
            }
        }
    }
    $otherId = ($last['DeUsuarios_idUsuarios'] == $me) ? (int) $last['ParaUsuarios_idUsuarios'] : (int) $last['DeUsuarios_idUsuarios'];
    $ou = $conexao->prepare('SELECT idUsuarios, Nome FROM usuarios WHERE idUsuarios = :id');
    $ou->execute([':id' => $otherId]);
    $otherUser = $ou->fetch(PDO::FETCH_ASSOC);
    $threads[] = ['produto' => $produto, 'other' => $otherUser, 'last' => $last, 'thumb' => $thumb ?? 'images/no-image.png'];
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
        <div class="chat-shell">
            <aside class="chat-sidebar">
                <h4 style="margin-top:2px">Conversas</h4>
                <div class="conv-search">
                    <input id="convSearch" class="form-control" placeholder="Pesquisar conversa"
                        aria-label="Pesquisar conversas">
                </div>

                <?php if (empty($threads)): ?>
                    <div class="no-convs">Você ainda não tem conversas.</div>
                <?php else: ?>
                    <ul id="convList" class="conv-list">
                        <?php foreach ($threads as $t): ?>
                            <?php
                            $pid = (int) ($t['produto']['idProdutos'] ?? 0);
                            $otherId = (int) ($t['other']['idUsuarios'] ?? 0);
                            $link = "chat_thread.php?produto={$pid}&other={$otherId}";
                            $lastMsg = htmlspecialchars(mb_substr($t['last']['Mensagem'] ?? '', 0, 140));
                            $time = isset($t['last']['CriadoEm']) ? date('d/m/Y H:i', strtotime($t['last']['CriadoEm'])) : '';
                            ?>
                            <li class="conv-item"
                                data-search="<?php echo htmlspecialchars(strtolower(($t['other']['Nome'] ?? '') . ' ' . ($t['produto']['Nome'] ?? '') . ' ' . ($t['last']['Mensagem'] ?? ''))); ?>"
                                onclick="location.href='<?php echo $link; ?>'">
                                <img class="conv-thumb" src="<?php echo htmlspecialchars($t['thumb']); ?>" alt="thumb">
                                <div class="conv-meta">
                                    <div style="display:flex;align-items:center;justify-content:space-between">
                                        <div class="conv-name">
                                            <?php echo htmlspecialchars($t['other']['Nome'] ?? 'Vendedor'); ?>
                                        </div>
                                        <div class="conv-time"><?php echo $time; ?></div>
                                    </div>
                                    <?php if (!empty($t['produto'])): ?>
                                        <div class="conv-product"><?php echo htmlspecialchars($t['produto']['Nome'] ?? ''); ?></div>
                                    <?php endif; ?>
                                    <div class="conv-last"><?php echo $lastMsg; ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </aside>

            <section class="chat-main" id="chatMain">
                <div style="text-align:center;color:#666;margin-top:40px">
                    <p style="font-size:1.1rem;font-weight:600">Selecione uma conversa</p>
                    <p style="max-width:560px;margin:0 auto">Clique em uma conversa à esquerda para ver o histórico e
                        responder.</p>
                </div>
            </section>
        </div>
    </main>

    <script>
        // client-side search for conversations
        (function () {
            const input = document.getElementById('convSearch');
            const list = document.getElementById('convList');
            if (!input || !list) return;
            const items = Array.from(list.querySelectorAll('.conv-item'));
            input.addEventListener('input', function () {
                const q = (this.value || '').trim().toLowerCase();
                if (q === '') { items.forEach(i => i.style.display = 'flex'); return; }
                items.forEach(i => {
                    const s = i.getAttribute('data-search') || '';
                    i.style.display = s.indexOf(q) !== -1 ? 'flex' : 'none';
                });
            });
        })();
    </script>
</body>

</html>