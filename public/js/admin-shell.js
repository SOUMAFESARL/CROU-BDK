/*
 * Comportement de <x-admin-shell> (pages admin Blade), calqué sur la Sidebar
 * shadcn du layout React : repli en mode icônes (bouton ou Ctrl+B, état dans le
 * cookie `sidebar_state`), tiroir sur mobile, info-bulles en mode replié,
 * sous-menus (dépliables ; menu flottant en mode replié) et menu utilisateur.
 */
(() => {
    const shell = document.querySelector('[data-admin-shell]');
    if (!shell) return;

    const MOBILE = window.matchMedia('(max-width: 767.98px)');
    const COOKIE_MAX_AGE = 60 * 60 * 24 * 7;
    const tooltip = document.querySelector('.as-tooltip');

    const setCollapsed = collapsed => {
        shell.dataset.state = collapsed ? 'collapsed' : 'expanded';
        document.cookie = `sidebar_state=${!collapsed}; path=/; max-age=${COOKIE_MAX_AGE}`;
    };
    const setMobileOpen = open => {
        shell.dataset.mobileOpen = open ? 'true' : 'false';
    };
    const toggle = () => {
        if (MOBILE.matches) {
            setMobileOpen(shell.dataset.mobileOpen !== 'true');
        } else {
            setCollapsed(shell.dataset.state !== 'collapsed');
        }
    };

    shell.querySelectorAll('[data-shell-toggle]').forEach(button => button.addEventListener('click', toggle));
    shell.querySelector('[data-shell-close]')?.addEventListener('click', () => setMobileOpen(false));
    MOBILE.addEventListener?.('change', () => setMobileOpen(false));

    document.addEventListener('keydown', event => {
        if (event.key === 'b' && (event.ctrlKey || event.metaKey)) {
            event.preventDefault();
            toggle();
        }
        if (event.key === 'Escape') {
            setMobileOpen(false);
            closeUserMenu();
            closeFlyouts();
        }
    });

    // Info-bulles : uniquement en mode replié sur bureau.
    const showTooltip = link => {
        if (!tooltip || MOBILE.matches || shell.dataset.state !== 'collapsed') return;
        const rect = link.getBoundingClientRect();
        tooltip.textContent = link.dataset.tooltip;
        tooltip.hidden = false;
        tooltip.style.left = `${rect.right + 8}px`;
        tooltip.style.top = `${rect.top + rect.height / 2 - tooltip.offsetHeight / 2}px`;
    };
    const hideTooltip = () => {
        if (tooltip) tooltip.hidden = true;
    };
    // Les rubriques à sous-menu affichent un menu flottant (avec titre) à la place de l'info-bulle.
    shell.querySelectorAll('.as-menu-item:not([data-has-sub]) > .as-menu-button[data-tooltip]').forEach(link => {
        link.addEventListener('mouseenter', () => showTooltip(link));
        link.addEventListener('focus', () => showTooltip(link));
        link.addEventListener('mouseleave', hideTooltip);
        link.addEventListener('blur', hideTooltip);
    });

    // Sous-menus : bouton chevron pour déplier/replier dans la barre latérale.
    const subItems = [...shell.querySelectorAll('.as-menu-item[data-has-sub]')];
    subItems.forEach(item => {
        const button = item.querySelector('[data-sub-toggle]');
        button?.addEventListener('click', () => {
            const open = item.dataset.open !== 'true';
            item.dataset.open = open ? 'true' : 'false';
            button.setAttribute('aria-expanded', String(open));
        });
    });

    // Mode replié (bureau) : menu flottant à droite de l'icône, au survol ou au focus clavier.
    const isCollapsedDesktop = () => !MOBILE.matches && shell.dataset.state === 'collapsed';
    let flyoutTimer = null;

    function closeFlyouts(except = null) {
        subItems.forEach(item => {
            if (item !== except) delete item.dataset.flyout;
        });
    }

    const openFlyout = item => {
        clearTimeout(flyoutTimer);
        if (!isCollapsedDesktop()) return;
        hideTooltip();
        closeFlyouts(item);
        item.dataset.flyout = 'true';
        const sub = item.querySelector('.as-sub');
        const top = item.getBoundingClientRect().top;
        sub.style.top = `${Math.max(8, Math.min(top, window.innerHeight - sub.offsetHeight - 8))}px`;
    };
    // Petit délai : laisse le temps de traverser l'espace entre l'icône et le menu flottant.
    const scheduleClose = () => {
        clearTimeout(flyoutTimer);
        flyoutTimer = setTimeout(() => closeFlyouts(), 180);
    };

    subItems.forEach(item => {
        item.addEventListener('mouseenter', () => openFlyout(item));
        item.addEventListener('mouseleave', scheduleClose);
        item.addEventListener('focusin', () => openFlyout(item));
        item.addEventListener('focusout', event => {
            if (!item.contains(event.relatedTarget)) scheduleClose();
        });
    });
    shell.querySelectorAll('[data-shell-toggle]').forEach(button => button.addEventListener('click', () => closeFlyouts()));

    // Menu utilisateur (barre du haut).
    const userMenu = shell.querySelector('[data-shell-user]');
    const userToggle = userMenu?.querySelector('[data-shell-user-toggle]');
    const dropdown = userMenu?.querySelector('.as-dropdown');

    function closeUserMenu() {
        if (!dropdown || dropdown.hidden) return;
        dropdown.hidden = true;
        userToggle.setAttribute('aria-expanded', 'false');
    }

    userToggle?.addEventListener('click', () => {
        const open = dropdown.hidden;
        dropdown.hidden = !open;
        userToggle.setAttribute('aria-expanded', String(open));
        if (open) dropdown.querySelector('[role="menuitem"]')?.focus();
    });
    document.addEventListener('click', event => {
        if (userMenu && !userMenu.contains(event.target)) closeUserMenu();
    });
})();
