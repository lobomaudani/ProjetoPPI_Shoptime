<header>
    <div class="header-inner">
        <?php include 'logo.inc'; ?>

        <div class="search-bar" style="margin: 10px 10px;">
            <form action="pesquisaProdutos.php" method="GET" class="d-flex" role="search">
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
<nav class="nav-categories">
    <select onchange="filtrarCategoria(this.value)">
        <option value="">Categorias</option>
        <option value="hardware">Hardware</option>
        <option value="software">Software</option>
        <option value="perifericos">Periféricos</option>
    </select>
    <button onclick="location.href='/ofertas'">Ofertas</button>
    <button onclick="location.href='/mais-vendidos'">Mais Vendidos</button>
    <button onclick="location.href='/lancamentos'">Lançamentos</button>
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
</script>