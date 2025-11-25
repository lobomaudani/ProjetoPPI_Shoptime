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
// Batch-fetch last messages, products, users and thumbs to avoid N+1 queries
$threads = [];
$lastIds = array_map(function ($c) {
    return (int) $c['last_id'];
}, $convs);
$lastIds = array_values(array_unique($lastIds));
if (count($lastIds) > 0) {
    // fetch all last messages in one query
    $in = implode(',', array_fill(0, count($lastIds), '?'));
    $stmt = $conexao->prepare('SELECT m.*, u.Nome AS deNome FROM mensagens m LEFT JOIN usuarios u ON u.idUsuarios = m.DeUsuarios_idUsuarios WHERE m.id IN (' . $in . ')');
    $stmt->execute($lastIds);
    $lastRows = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lastRows[(int) $r['id']] = $r;
    }

    // collect product ids and user ids
    $productIds = [];
    $userIds = [];
    foreach ($convs as $c) {
        if ($c['produto_id'])
            $productIds[] = (int) $c['produto_id'];
        $lid = (int) $c['last_id'];
        if (isset($lastRows[$lid])) {
            $m = $lastRows[$lid];
            $otherId = ((int) $m['DeUsuarios_idUsuarios'] === $me) ? (int) $m['ParaUsuarios_idUsuarios'] : (int) $m['DeUsuarios_idUsuarios'];
            $userIds[] = $otherId;
        }
    }
    $productIds = array_values(array_unique($productIds));
    $userIds = array_values(array_unique($userIds));

    // fetch products
    $products = [];
    if (count($productIds) > 0) {
        $in = implode(',', array_fill(0, count($productIds), '?'));
        $pstmt = $conexao->prepare('SELECT idProdutos, Nome FROM produtos WHERE idProdutos IN (' . $in . ')');
        $pstmt->execute($productIds);
        while ($r = $pstmt->fetch(PDO::FETCH_ASSOC)) {
            $products[(int) $r['idProdutos']] = $r;
        }

        // fetch one image per product (may return multiple; we'll pick first)
        $imgStmt = $conexao->prepare('SELECT Produtos_idProdutos, ImagemUrl FROM enderecoimagem WHERE Produtos_idProdutos IN (' . $in . ')');
        $imgStmt->execute($productIds);
        $thumbs = [];
        while ($ir = $imgStmt->fetch(PDO::FETCH_ASSOC)) {
            $pid = (int) $ir['Produtos_idProdutos'];
            if (!isset($thumbs[$pid]))
                $thumbs[$pid] = $ir['ImagemUrl'];
        }
    } else {
        $thumbs = [];
    }

    // fetch users
    $users = [];
    if (count($userIds) > 0) {
        $in = implode(',', array_fill(0, count($userIds), '?'));
        $ustmt = $conexao->prepare('SELECT idUsuarios, Nome FROM usuarios WHERE idUsuarios IN (' . $in . ')');
        $ustmt->execute($userIds);
        while ($ur = $ustmt->fetch(PDO::FETCH_ASSOC)) {
            $users[(int) $ur['idUsuarios']] = $ur;
        }
    }

    // build threads preserving order from $convs
    foreach ($convs as $c) {
        $lid = (int) $c['last_id'];
        $last = $lastRows[$lid] ?? null;
        $produto = null;
        $thumb = 'images/no-image.png';
        if ($c['produto_id']) {
            $pid = (int) $c['produto_id'];
            $produto = $products[$pid] ?? null;
            if (isset($thumbs[$pid]) && $thumbs[$pid]) {
                $d = $thumbs[$pid];
                if (is_string($d) && (strpos($d, 'data:') === 0 || strpos($d, 'uploads/') === 0 || strpos($d, 'images/') === 0 || strpos($d, 'http') === 0)) {
                    $thumb = $d;
                } else {
                    $thumb = 'data:image/jpeg;base64,' . base64_encode($d);
                }
            }
        }
        $otherUser = null;
        if ($last) {
            $otherId = ((int) $last['DeUsuarios_idUsuarios'] === $me) ? (int) $last['ParaUsuarios_idUsuarios'] : (int) $last['DeUsuarios_idUsuarios'];
            $otherUser = $users[$otherId] ?? null;
        }
        $threads[] = ['produto' => $produto, 'other' => $otherUser, 'last' => $last, 'thumb' => $thumb];
    }
}

?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="styles/styles.css" rel="stylesheet">
    <title>Conversas - ShowTime</title>
    <style>
        .chat-shell {
            display: flex;
            gap: 18px;
        }

        .chat-sidebar {
            width: 340px;
            flex: 0 0 340px;
            background: #fff;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
            padding: 12px;
        }

        .chat-main {
            flex: 1 1 auto;
            min-height: 60vh;
            background: #fafafa;
            border-radius: 8px;
            padding: 18px;
            border: 1px solid #eee;
        }

        .conv-search {
            margin-bottom: 10px;
        }

        .conv-list {
            list-style: none;
            margin: 0;
            padding: 0;
            max-height: 72vh;
            overflow: auto;
        }

        .conv-item {
            display: flex;
            gap: 12px;
            padding: 10px;
            align-items: center;
            border-radius: 8px;
            cursor: pointer;
        }

        .conv-item:hover {
            background: #fbfbfb;
        }

        .conv-thumb {
            width: 56px;
            height: 56px;
            border-radius: 6px;
            object-fit: cover;
            flex: 0 0 56px;
            border: 1px solid #e9e9e9
        }

        .conv-meta {
            flex: 1 1 auto;
            min-width: 0
        }

        .conv-name {
            font-weight: 600;
            margin-bottom: 4px
        }

        .conv-product {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 6px
        }

        .conv-last {
            color: #777;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .conv-time {
            font-size: 0.8rem;
            color: #999;
            margin-left: 8px
        }

        .no-convs {
            color: #666
        }

        /* scrollbar small */
        .conv-list::-webkit-scrollbar {
            width: 10px;
        }

        .conv-list::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.06);
            border-radius: 6px
        }
    </style>
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
                                        <div class="conv-product"><?php echo htmlspecialchars($t['produto']['Nome']); ?></div>
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