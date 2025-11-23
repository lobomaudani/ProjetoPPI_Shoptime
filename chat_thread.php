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

// load product details if any
$produtoRow = null;
if ($produto) {
    $pstmt = $conexao->prepare('SELECT idProdutos, Nome FROM produtos WHERE idProdutos = :pid');
    $pstmt->execute([':pid' => $produto]);
    $produtoRow = $pstmt->fetch(PDO::FETCH_ASSOC);
}

?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="styles/styles.css" rel="stylesheet">
    <title>Chat - <?php echo htmlspecialchars($produtoRow['Nome'] ?? 'Conversa'); ?></title>
    <style>
        .chat-window {
            border: 1px solid #ddd;
            height: 60vh;
            overflow: auto;
            padding: 12px;
            background: #fff;
        }

        .msg {
            margin-bottom: 8px;
        }

        .msg.me {
            text-align: right;
        }

        .msg .bubble {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 12px;
            max-width: 70%;
        }

        .msg.me .bubble {
            background: #d1ffd6;
        }

        .msg.them .bubble {
            background: #eee;
        }

        .send-box {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
    </style>
</head>

<body>
    <?php include_once __DIR__ . '/includes/header.php'; ?>
    <main class="container my-4">
        <h4><?php echo htmlspecialchars($produtoRow['Nome'] ?? 'Conversa'); ?></h4>
        <div id="chatWindow" class="chat-window" aria-live="polite">
            <p>Carregando mensagens...</p>
        </div>
        <div class="send-box">
            <input id="msgInput" type="text" class="form-control" placeholder="Digite sua mensagem..." />
            <button id="sendBtn" class="btn btn-primary">Enviar</button>
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
            bubble.textContent = m.Mensagem;
            div.appendChild(bubble);
            return div;
        }

        function scrollToBottom() { chatWindow.scrollTop = chatWindow.scrollHeight; }

        function fetchMessages() {
            const url = 'fetch_messages.php?other_user_id=' + encodeURIComponent(otherId) + '&produto_id=' + encodeURIComponent(produtoId) + '&since_id=' + encodeURIComponent(lastId);
            fetch(url).then(r => r.json()).then(j => {
                if (!j.ok) return;
                const msgs = j.messages || [];
                msgs.forEach(m => {
                    chatWindow.appendChild(renderMessage(m));
                    lastId = Math.max(lastId, parseInt(m.id));
                });
                if (msgs.length) {
                    scrollToBottom();
                    // mark messages as read for messages we just received
                    markRead();
                }
            }).catch(e => console.error(e));
        }

        function markRead() {
            // mark messages directed to me from the other participant for this product
            try {
                const fd = new FormData();
                fd.append('produto_id', produtoId);
                fd.append('other_user_id', otherId);
                fetch('mark_read.php', { method: 'POST', body: fd }).then(r => r.json()).then(j => {
                    // optional: update unread badge by reloading unread count via header polling
                }).catch(e => { /* ignore */ });
            } catch (e) { }
        }

        sendBtn.addEventListener('click', function () {
            const text = msgInput.value.trim();
            if (!text) return;
            const fd = new FormData();
            fd.append('produto_id', produtoId);
            fd.append('to_user_id', otherId);
            fd.append('mensagem', text);
            fetch('send_message.php', { method: 'POST', body: fd }).then(r => r.json()).then(j => {
                if (j.ok && j.message) {
                    chatWindow.appendChild(renderMessage(j.message));
                    lastId = Math.max(lastId, parseInt(j.message.id));
                    msgInput.value = '';
                    scrollToBottom();
                } else {
                    alert('Erro ao enviar: ' + (j.error || ''));
                }
            }).catch(e => { console.error(e); alert('Erro ao enviar.'); });
        });

        // polling
        setInterval(fetchMessages, 2000);
        // initial load: fetch messages then mark them read
        fetchMessages();
        // ensure we mark read any existing messages once after first render
        setTimeout(markRead, 500);

        // send on enter
        msgInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); sendBtn.click(); } });
    </script>
</body>

</html>