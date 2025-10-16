<header>
    <?php include 'logo.inc'; ?>

    <div class="search-bar" style="margin: 10px 10px;">
        <form action="pesquisaProdutos.php" method="GET" class="d-flex" role="search">
            <input class="form-control me-2" type="search" name="q" 
                   placeholder="Buscar produtos..." 
                   aria-label="Buscar produtos" 
                   required
                   style="width: 300px; padding: 6px 10px; border-radius: 4px; border: 1px solid #ccc;">
            <button class="btn btn-outline-success" type="submit" style="padding: 6px 12px; border-radius: 4px;">Pesquisar</button>
        </form>
    </div>

    <div class="user-actions" id="user-actions">    
        <?php include __DIR__ . '/chamarHeader.php'; ?>
    </div>
    
</header>
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
        const rect = userArea.getBoundingClientRect();
        const menuHeight = menu.offsetHeight || 150; // fallback
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

    // Close when clicking outside
    document.addEventListener('click', function (e) {
        if (!userArea.classList.contains('open')) return;
        if (!userArea.contains(e.target)) closeMenu();
    });

    // Prevent clicks inside the menu from bubbling to document
    menu.addEventListener('click', function (e) { e.stopPropagation(); });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMenu();
    });
});
</script>