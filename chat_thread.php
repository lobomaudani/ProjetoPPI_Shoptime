<?php
session_start();
include_once __DIR__ . '/connections/conectarBD.php';

if (empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}
$me = (int) $_SESSION['id'];
$produto = isset($_GET['produto']) ? (int) $_GET['produto'] : null;
$other = isset($_GET['other']) ? (int) $_GET['other'] : null;

// If product provided, determine seller if other is empty
if ($produto && !$other) {
    $q = $conexao->prepare('SELECT Usuarios_idUsuarios FROM produtos WHERE idProdutos = :pid LIMIT 1');
    $q->execute([':pid' => $produto]);
    $s = $q->fetch(PDO::FETCH_ASSOC);
    $other = $s ? (int) $s['Usuarios_idUsuarios'] : null;
}

if (!$other) {
    echo "Usuário de conversa não especificado.";
    exit;
}
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="styles/styles.css" rel="stylesheet">
    <title>Chat - <?php echo htmlspecialchars($produtoRow['Nome'] ?? 'Conversa'); ?></title>
</head>

<body>
    <?php include_once __DIR__ . '/includes/header.php'; ?>
    <main class="container my-4 chat-container">
        <div class="chat-header">
            <?php
            // get product thumb
            $prodThumb = 'images/no-image.png';
            if ($produtoRow) {
                $qimg = $conexao->prepare('SELECT ImagemUrl FROM enderecoimagem WHERE Produtos_idProdutos = :pid LIMIT 1');
                $qimg->execute([':pid' => $produtoRow['idProdutos']]);
                $ir = $qimg->fetch(PDO::FETCH_ASSOC);
                if ($ir && !empty($ir['ImagemUrl'])) {
                    $d = $ir['ImagemUrl'];
                    if (is_string($d) && (strpos($d, 'data:') === 0 || strpos($d, 'uploads/') === 0 || strpos($d, 'images/') === 0 || strpos($d, 'http') === 0)) {
                        $prodThumb = $d;
                    } else {
                        $prodThumb = 'data:image/jpeg;base64,' . base64_encode($d);
                    }
                }
            }
            ?>
            <button id="backToChats" class="chat-back-btn" title="Voltar" aria-label="Voltar para conversas"
                onclick="location.href='chat.php'">&#8592;</button>
            <img src="<?php echo htmlspecialchars($prodThumb); ?>" alt="produto" class="chat-product-thumb">
            <div>
                <div class="chat-head-title" style="color:#000">
                    <?php echo htmlspecialchars($produtoRow['Nome'] ?? 'Conversa'); ?>
                </div>
                <div class="text-muted small">
                    <?php echo htmlspecialchars($t = ($produtoRow ? 'Discussões sobre este produto' : 'Conversa')); ?>
                </div>
            </div>
        </div>

        <div id="chatWindow" class="chat-window" aria-live="polite">
            <!-- messages appended here -->
        </div>

        <div class="send-area">
            <div class="send-box">
                <textarea id="msgInput" placeholder="Digite sua mensagem..."></textarea>
                <button id="sendBtn">Enviar</button>
            </div>
        </div>
    </main>

    <script>
        const meId = <?php echo $me; ?>;
        const otherId = <?php echo (int) $other; ?>;
        const produtoId = <?php echo (int) $produto; ?>;
        let lastId = 0;
        const chatWindow = document.getElementById('chatWindow');
        const msgInput = document.getElementById('msgInput');
        const sendBtn = document.getElementById('sendBtn');

        function renderMessage(m) {
            const div = document.createElement('div');
            div.className = 'msg ' + (parseInt(m.DeUsuarios_idUsuarios) === meId ? 'me' : 'them');
            const bubble = document.createElement('div'); bubble.className = 'bubble';
            // message text
            const text = document.createElement('div'); text.textContent = m.Mensagem;
            bubble.appendChild(text);
            // timestamp meta (if available)
            if (m.CriadoEm) {
                const meta = document.createElement('div'); meta.className = 'meta';
                try {
                    const d = new Date(m.CriadoEm);
                    meta.textContent = d.toLocaleString();
                } catch (e) { meta.textContent = m.CriadoEm; }
                bubble.appendChild(meta);
            }
            div.appendChild(bubble);
            return div;
        }

        function scrollToBottom() { chatWindow.scrollTop = chatWindow.scrollHeight; }

        function fetchMessages() {
            const url = 'fetch_messages.php?other_user_id=' + encodeURIComponent(otherId) + '&produto_id=' + encodeURIComponent(produtoId) + '&since_id=' + encodeURIComponent(lastId);
            fetch(url).then(r => r.json()).then(j => {
                if (!j.ok) return;
                const msgs = j.messages || [];
                if (msgs.length) {
                    // append and scroll
                    msgs.forEach(m => {
                        chatWindow.appendChild(renderMessage(m));
                        lastId = Math.max(lastId, parseInt(m.id));
                    });
                    scrollToBottom();
                    // mark messages as read for messages we just received
                    markRead();
                } else {
                    // if no messages and first load, show empty state
                    if (lastId === 0 && chatWindow.children.length === 0) {
                        chatWindow.innerHTML = '<div class="empty-state">Nenhuma mensagem ainda. Diga olá!</div>';
                    }
                }
            }).catch(e => console.error(e));
        }

        function markRead() {
            // mark messages directed to me from the other participant for this product
            try {
                const fd = new FormData();
                fd.append('produto_id', produtoId);
                fd.append('other_user_id', otherId);
                try { fd.append('csrf_token', window.__CSRF_TOKEN || ''); } catch (e) { }
                fetch('mark_read.php', { method: 'POST', body: fd }).then(r => r.json()).then(j => {
                    // request the current unread count and update the floating badge immediately
                    try {
                        fetch('get_unread_count.php', { cache: 'no-store' }).then(r2 => r2.json()).then(j2 => {
                            if (j2 && j2.ok) {
                                const b = document.getElementById('chatUnreadBadge');
                                if (b) {
                                    const count = parseInt(j2.count || 0, 10);
                                    if (!count || count <= 0) { b.style.display = 'none'; } else { b.style.display = 'flex'; b.textContent = count > 99 ? '99+' : String(count); }
                                }
                            }
                        }).catch(() => { });
                    } catch (e) { }
                }).catch(e => { /* ignore */ });
            } catch (e) { }
        }

        sendBtn.addEventListener('click', sendMessage);

        function sendMessage() {
            const text = msgInput.value.trim();
            if (!text) return;
            const fd = new FormData();
            fd.append('produto_id', produtoId);
            fd.append('to_user_id', otherId);
            fd.append('mensagem', text);
            try { fd.append('csrf_token', window.__CSRF_TOKEN || ''); } catch (e) { }
            fetch('send_message.php', { method: 'POST', body: fd }).then(r => r.json()).then(j => {
                if (j.ok && j.message) {
                    // if we had an empty-state, clear it
                    if (chatWindow.querySelector('.empty-state')) chatWindow.innerHTML = '';
                    chatWindow.appendChild(renderMessage(j.message));
                    lastId = Math.max(lastId, parseInt(j.message.id));
                    msgInput.value = '';
                    scrollToBottom();
                } else {
                    alert('Erro ao enviar: ' + (j.error || ''));
                }
            }).catch(e => { console.error(e); alert('Erro ao enviar.'); });
        }

        // polling
        setInterval(fetchMessages, 2000);
        // initial load: fetch messages then mark them read
        fetchMessages();
        // ensure we mark read any existing messages once after first render
        setTimeout(markRead, 500);

        // send on Enter (Shift+Enter -> newline)
        msgInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    </script>
</body>

</html>