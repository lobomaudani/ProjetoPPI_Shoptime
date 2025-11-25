<?php
// ensure session and CSRF token
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(16));
    }
}
// compute application base (strip /admin path when included from admin)
$appBase = preg_replace('#/admin(?:/.*)?$#', '', str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])));
if ($appBase === '')
    $appBase = '';
// expose to JS
echo "<script>window.APP_BASE = '" . addslashes($appBase) . "';</script>\n";
?>
<header>
    <div class="header-inner">
        <?php include 'logo.inc'; ?>

        <div class="search-bar" style="margin: 10px 10px;">
            <form action="<?php echo $appBase; ?>/pesquisaProdutos.php" method="GET" class="d-flex" role="search">
                <?php $searchTerm = isset($_GET['q']) ? $_GET['q'] : ''; ?>
                <input class="form-control me-2" type="search" name="q" placeholder="Buscar produtos..."
                    aria-label="Buscar produtos" required
                    value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>"
                    style="width: 300px; padding: 6px 10px; border-radius: 4px; border: 1px solid #ccc;">
                <button class="btn btn-outline-success" type="submit"
                    style="padding: 6px 12px; border-radius: 4px;">Pesquisar</button>
            </form>
        </div>

        <div class="user-actions" id="user-actions">
            <?php include __DIR__ . '/chamarHeader.php'; ?>
        </div>
    </div>

</header>

<!-- Navegação de categorias e botões -->
<nav class="nav-categories" style="display:flex;gap:12px;align-items:center;margin-left:10px;">
    <div class="categories-lookup" style="position:relative;">
        <button id="categoriesTrigger" class="btn btn-outline-light" aria-expanded="false">Categorias ▾</button>
        <div id="categoriesPanel" class="card shadow-sm"
            style="position:absolute;left:0;top:36px;display:none;z-index:999;width:320px;padding:8px;">
            <input id="catSearch" class="form-control" placeholder="Pesquisar categorias..."
                style="margin-bottom:8px;display:none;" aria-hidden="true">
            <div id="catResults" style="max-height:260px;overflow:auto;"></div>
        </div>
    </div>

    <button id="btnOfertas" class="btn btn-outline-light">Ofertas</button>
    <button onclick="location.href='<?php echo $appBase; ?>/pesquisaProdutos.php?filtro=mais_favoritados'"
        class="btn btn-outline-light">Mais
        Favoritados</button>
    <button onclick="location.href='<?php echo $appBase; ?>/pesquisaProdutos.php?filtro=lancamentos'"
        class="btn btn-outline-light">Lançamentos</button>
</nav>

<script>
    // Small custom dropdown (dropup) behavior: toggles .open on the .user-area
    document.addEventListener('DOMContentLoaded', function () {
        const trigger = document.getElementById('user-name-trigger');
        const userArea = trigger ? trigger.closest('.user-area') : null;
        const menu = document.getElementById('user-dropdown');

        if (!trigger || !userArea || !menu) return;

        function closeMenu() {
            userArea.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        function openMenu() {
            // decide whether to show above or below depending on viewport space
            menu.classList.remove('dropup', 'dropdown');

            // measure menu height while keeping it hidden but rendered
            menu.classList.add('measuring');
            const menuHeight = menu.offsetHeight || menu.scrollHeight || 150;
            menu.classList.remove('measuring');

            const rect = userArea.getBoundingClientRect();
            const spaceAbove = rect.top; // px above the trigger
            const spaceBelow = window.innerHeight - rect.bottom; // px below the trigger
            if (spaceAbove > menuHeight + 20) {
                menu.classList.add('dropup');
            } else {
                menu.classList.add('dropdown');
            }
            userArea.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
        }

        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (userArea.classList.contains('open')) closeMenu(); else openMenu();
        });

        // Close when clicking outside (capture phase to be robust)
        function onOutsideClick(e) {
            if (!userArea.classList.contains('open')) return;
            if (!userArea.contains(e.target)) closeMenu();
        }
        document.addEventListener('mousedown', onOutsideClick, true);
        document.addEventListener('touchstart', onOutsideClick, true);

        // Prevent clicks inside the menu from bubbling to document
        menu.addEventListener('click', function (e) { e.stopPropagation(); });

        // Close on window resize or scroll (menu position may be invalid)
        window.addEventListener('resize', function () { if (userArea.classList.contains('open')) closeMenu(); });
        window.addEventListener('scroll', function () { if (userArea.classList.contains('open')) closeMenu(); }, true);

        // Close on Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMenu();
        });
    });
    // Style adjustments for categories panel to ensure solid white background and rounded corners
    (function () {
        var css = '\n#categoriesPanel { background: #fff !important; border-radius: 8px !important; border: 1px solid rgba(0,0,0,0.08) !important; box-shadow: 0 10px 20px rgba(0,0,0,0.12) !important; color: #222 !important; }\n#categoriesPanel .cat-row:hover { background: rgba(0,0,0,0.03); }\n#categoriesPanel input#catSearch { background: #fff; }\n#categoriesPanel::-webkit-scrollbar { width: 10px; }\n#categoriesPanel::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.08); border-radius: 6px; }\n';
        var s = document.createElement('style'); s.type = 'text/css'; s.appendChild(document.createTextNode(css)); document.head.appendChild(s);
    })();
    // Category lookup behavior
    document.addEventListener('DOMContentLoaded', function () {
        const trigger = document.getElementById('categoriesTrigger');
        const panel = document.getElementById('categoriesPanel');
        const input = document.getElementById('catSearch');
        const results = document.getElementById('catResults');
        const ofertasBtn = document.getElementById('btnOfertas');
        let cached = null;

        // typing buffer for type-to-search (input hidden)
        let searchBuffer = '';
        let bufferTimer = null;
        const BUFFER_TIMEOUT = 1200; // ms
        function resetBuffer() { searchBuffer = ''; if (bufferTimer) { clearTimeout(bufferTimer); bufferTimer = null; } }
        function scheduleBufferReset() { if (bufferTimer) clearTimeout(bufferTimer); bufferTimer = setTimeout(resetBuffer, BUFFER_TIMEOUT); }
        function applyFilterFromBuffer() { const q = (searchBuffer || '').trim().toLowerCase(); if (!cached) return; const filtered = q === '' ? cached : cached.filter(c => (c.nome || '').toLowerCase().indexOf(q) !== -1); renderList(filtered); }

        function openPanel() {
            panel.style.display = 'block';
            trigger.setAttribute('aria-expanded', 'true');
            // reset typing buffer when opening
            resetBuffer();
            // focus hidden input for accessibility; panel will capture typing
            try { input && input.focus(); } catch (e) { }
            // start listening for typed characters
            document.addEventListener('keydown', onPanelKeydown);
            if (!cached) fetchCategories(); else applyFilterFromBuffer();
        }
        function closePanel() {
            panel.style.display = 'none';
            trigger.setAttribute('aria-expanded', 'false');
            resetBuffer();
            document.removeEventListener('keydown', onPanelKeydown);
        }

        trigger && trigger.addEventListener('click', function (e) {
            e.preventDefault();
            if (panel.style.display === 'block') closePanel(); else openPanel();
        });

        // close button removed; panel can be closed via Escape or clicking outside

        // click outside to close
        document.addEventListener('mousedown', function (ev) { if (panel && panel.style.display === 'block' && !panel.contains(ev.target) && ev.target !== trigger) closePanel(); });

        function fetchCategories() {
            fetch(window.APP_BASE + '/categoria_lookup.php').then(r => r.json()).then(j => {
                if (j && j.ok) {
                    cached = j.categories || [];
                    renderList(cached);
                } else {
                    results.innerHTML = '<div class="text-danger">Erro ao carregar categorias</div>';
                }
            }).catch(err => { results.innerHTML = '<div class="text-danger">Falha ao carregar</div>'; });
        }

        function renderList(list) {
            if (!list || list.length === 0) { results.innerHTML = '<div class="text-muted">Nenhuma categoria</div>'; return; }
            results.innerHTML = '';
            for (const c of list) {
                const row = document.createElement('div');
                row.className = 'cat-row';
                row.style.padding = '6px 8px';
                row.style.cursor = 'pointer';
                row.textContent = c.nome;
                row.dataset.id = c.id;
                row.addEventListener('click', function () {
                    // navigate to pesquisaProducts filtered by category id
                    window.location = window.APP_BASE + '/pesquisaProdutos.php?categoria=' + encodeURIComponent(this.dataset.id);
                });
                results.appendChild(row);
            }
        }

        // hide input is used for accessibility only; implement type-to-search via keydown
        function onPanelKeydown(e) {
            if (!panel || panel.style.display !== 'block') return;
            // allow close with Escape
            if (e.key === 'Escape') { closePanel(); trigger.focus(); return; }
            // backspace: remove last char
            if (e.key === 'Backspace') { searchBuffer = searchBuffer.slice(0, -1); applyFilterFromBuffer(); scheduleBufferReset(); return; }
            // ignore control/meta keys
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            // only process printable characters
            if (e.key && e.key.length === 1) {
                searchBuffer += e.key;
                applyFilterFromBuffer();
                scheduleBufferReset();
            }
        }

        // Offers button -> filter by desconto
        if (ofertasBtn) ofertasBtn.addEventListener('click', function () {
            window.location = window.APP_BASE + '/pesquisaProdutos.php?desconto=1';
        });
    });
</script>

<!-- Floating chat/unread button (persistent) -->
<style>
    #chatFloatingBtn {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 1200;
    }

    #chatFloatingBtn .fab {
        background: #c1121f;
        color: #fff;
        border-radius: 999px;
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
        cursor: pointer;
        border: none
    }

    #chatFloatingBtn .badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #ffdd57;
        color: #000;
        min-width: 20px;
        height: 20px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        padding: 0 6px;
        font-weight: 600
    }

    #chatFloatingBtn .fab:focus {
        outline: none;
    }
</style>

<div id="chatFloatingBtn" aria-hidden="false">
    <button id="chatFab" class="fab" title="Mensagens">
        💬
        <span id="chatUnreadBadge" class="badge" style="display:none">0</span>
    </button>
</div>

<script>
    (function () {
        const isLogged = <?php echo empty($_SESSION['id']) ? 'false' : 'true'; ?>;
        const chatFab = document.getElementById('chatFab');
        const badge = document.getElementById('chatUnreadBadge');

        function updateBadge(count) {
            if (!badge) return;
            if (!count || count <= 0) {
                badge.style.display = 'none';
            } else {
                badge.style.display = 'flex';
                badge.textContent = count > 99 ? '99+' : String(count);
            }
        }

        async function fetchUnread() {
            try {
                const res = await fetch(window.APP_BASE + '/get_unread_count.php', { cache: 'no-store' });
                const j = await res.json();
                if (j && j.ok) updateBadge(parseInt(j.count || 0));
            } catch (e) {
                // ignore network errors silently
                // console.error('Unread fetch', e);
            }
        }

        // click action: open chat list or go to login
        chatFab.addEventListener('click', function () {
            if (!isLogged) {
                window.location = window.APP_BASE + '/login.php';
                return;
            }
            // open chat list
            window.location = window.APP_BASE + '/chat.php';
        });

        // start polling only if logged in (cheap fallback: still hits endpoint which returns not_logged)
        if (isLogged) {
            fetchUnread();
            setInterval(fetchUnread, 6000);
        }
    })();
</script>