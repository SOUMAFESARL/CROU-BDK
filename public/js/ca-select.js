/*
 * Liste déroulante personnalisée pour les <select data-ca-select> des pages « ca-page ».
 * Le <select> natif reste la source de vérité (valeur envoyée, options masquées via `hidden`,
 * état `disabled`, classe `is-invalid`) : les changements d'attributs et d'options sont suivis
 * automatiquement ; après un `select.value = …` par script, déclencher « ca-select:refresh ».
 * Le menu est en position fixe pour ne pas être rogné par les fenêtres <dialog> défilantes.
 * Avec l'attribut `data-ca-select-search`, un champ de recherche (sans accents) filtre les options.
 */
(function () {
    'use strict';

    var CHEVRON = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
    var compteur = 0;

    function normaliser(texte) {
        return String(texte || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    }

    // Place un panneau flottant sous (ou au-dessus de) son déclencheur, dans la fenêtre visible.
    function placer(trigger, panneau, largeurDuDeclencheur) {
        var rect = trigger.getBoundingClientRect();
        var marge = 8;
        if (largeurDuDeclencheur) panneau.style.width = rect.width + 'px';
        panneau.style.maxHeight = '';
        var hauteur = panneau.offsetHeight;
        var dessous = window.innerHeight - rect.bottom - marge;
        var dessus = rect.top - marge;
        var versLeHaut = hauteur > dessous && dessus > dessous;
        if (hauteur > Math.max(dessous, dessus)) {
            panneau.style.maxHeight = Math.max(dessous, dessus) - 6 + 'px';
            hauteur = panneau.offsetHeight;
        }
        var top = versLeHaut ? rect.top - hauteur - 6 : rect.bottom + 6;
        var left = Math.min(Math.max(rect.left, marge), window.innerWidth - panneau.offsetWidth - marge);
        panneau.style.top = top + 'px';
        panneau.style.left = left + 'px';
        // Un ancêtre transformé décale le repère des éléments fixes : on corrige l'écart mesuré.
        var reel = panneau.getBoundingClientRect();
        if (Math.abs(reel.top - top) > 1 || Math.abs(reel.left - left) > 1) {
            panneau.style.top = top * 2 - reel.top + 'px';
            panneau.style.left = left * 2 - reel.left + 'px';
        }
        panneau.classList.toggle('is-up', versLeHaut);
    }

    function enhance(select) {
        if (select.dataset.caSelectReady) return;
        select.dataset.caSelectReady = '1';
        compteur += 1;

        var wrapper = document.createElement('div');
        wrapper.className = 'ca-select';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        select.classList.add('ca-select-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'ca-input ca-select-trigger';
        trigger.id = select.id ? select.id + '_trigger' : 'ca_select_' + compteur;
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.innerHTML = '<span></span>' + CHEVRON;
        wrapper.appendChild(trigger);

        // Le label du <select> pointe désormais vers le bouton.
        if (select.id) {
            document.querySelectorAll('label[for="' + select.id + '"]').forEach(function (label) {
                label.setAttribute('for', trigger.id);
            });
        }

        var menu = document.createElement('ul');
        menu.className = 'ca-select-menu';
        menu.id = trigger.id + '_menu';
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;
        trigger.setAttribute('aria-controls', menu.id);
        wrapper.appendChild(menu);

        var actif = -1;
        var recherche = null;
        if (select.hasAttribute('data-ca-select-search')) {
            var zone = document.createElement('li');
            zone.className = 'ca-select-search';
            zone.setAttribute('role', 'presentation');
            recherche = document.createElement('input');
            recherche.type = 'search';
            recherche.autocomplete = 'off';
            recherche.placeholder = select.getAttribute('data-ca-select-search') || 'Rechercher…';
            recherche.setAttribute('aria-label', recherche.placeholder);
            recherche.setAttribute('aria-controls', menu.id);
            zone.appendChild(recherche);
        }

        function options() {
            var filtre = recherche ? normaliser(recherche.value.trim()) : '';
            return Array.prototype.filter.call(select.options, function (option) {
                return option.value !== '' && !option.hidden && !option.disabled
                    && (!filtre || normaliser(option.textContent).indexOf(filtre) !== -1);
            });
        }

        function refresh() {
            var selected = select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
            var placeholder = !selected || selected.value === '';
            trigger.firstChild.textContent = selected ? selected.textContent.trim() : '';
            trigger.classList.toggle('is-placeholder', placeholder);
            trigger.classList.toggle('is-invalid', select.classList.contains('is-invalid'));
            trigger.disabled = select.disabled;
            if (select.disabled) close();
        }

        function render() {
            // Le champ de recherche reste en place (le retirer du DOM lui ferait perdre le focus).
            Array.prototype.slice.call(menu.children).forEach(function (enfant) {
                if (!recherche || enfant !== recherche.parentNode) menu.removeChild(enfant);
            });
            if (recherche && !recherche.parentNode.parentNode) menu.appendChild(recherche.parentNode);
            actif = -1;
            var liste = options();
            if (!liste.length) {
                var vide = document.createElement('li');
                vide.className = 'ca-select-empty';
                vide.textContent = recherche && recherche.value.trim() ? 'Aucun résultat' : 'Aucune option disponible';
                menu.appendChild(vide);
                return;
            }
            liste.forEach(function (option, index) {
                var item = document.createElement('li');
                var choisi = option.value === select.value;
                item.className = 'ca-select-option';
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', choisi ? 'true' : 'false');
                item.dataset.value = option.value;
                item.dataset.index = index;
                item.textContent = option.textContent.trim();
                if (choisi) actif = index;
                menu.appendChild(item);
            });
        }

        function surligner(index, defiler) {
            var items = menu.querySelectorAll('.ca-select-option');
            if (!items.length) return;
            actif = (index + items.length) % items.length;
            items.forEach(function (item, i) { item.classList.toggle('is-active', i === actif); });
            if (defiler !== false) items[actif].scrollIntoView({ block: 'nearest' });
        }

        function repositionner() { placer(trigger, menu, true); }

        function open() {
            if (select.disabled || !menu.hidden) return;
            document.querySelectorAll('.ca-select.is-open, .ca-date.is-open').forEach(function (autre) {
                autre.dispatchEvent(new CustomEvent('ca-select:close'));
            });
            if (recherche) recherche.value = '';
            render();
            menu.hidden = false;
            wrapper.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            repositionner();
            surligner(Math.max(actif, 0));
            if (recherche) recherche.focus({ preventScroll: true });
            window.addEventListener('scroll', repositionner, true);
            window.addEventListener('resize', repositionner);
        }

        function close() {
            if (menu.hidden) return;
            menu.hidden = true;
            wrapper.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            window.removeEventListener('scroll', repositionner, true);
            window.removeEventListener('resize', repositionner);
        }

        function choisir(valeur) {
            close();
            trigger.focus();
            if (select.value === valeur) return;
            select.value = valeur;
            select.classList.remove('is-invalid');
            select.dispatchEvent(new Event('change', { bubbles: true }));
            refresh();
        }

        trigger.addEventListener('click', function () { menu.hidden ? open() : close(); });
        function clavier(event) {
            var items = menu.querySelectorAll('.ca-select-option');
            switch (event.key) {
                case 'ArrowDown':
                case 'ArrowUp':
                    event.preventDefault();
                    if (menu.hidden) { open(); return; }
                    surligner(actif + (event.key === 'ArrowDown' ? 1 : -1));
                    break;
                case 'Home':
                case 'End':
                    if (menu.hidden) return;
                    event.preventDefault();
                    surligner(event.key === 'Home' ? 0 : items.length - 1);
                    break;
                case 'Enter':
                case ' ':
                    // L'espace reste un caractère dans le champ de recherche.
                    if (event.key === ' ' && event.target === recherche) return;
                    event.preventDefault();
                    if (menu.hidden) { open(); return; }
                    if (items[actif]) choisir(items[actif].dataset.value);
                    break;
                case 'Escape':
                    if (!menu.hidden) { event.preventDefault(); event.stopPropagation(); close(); }
                    break;
                case 'Tab':
                    close();
                    break;
            }
        }
        trigger.addEventListener('keydown', clavier);
        if (recherche) {
            recherche.addEventListener('keydown', function (event) {
                // Échap ou Tab depuis la recherche : retour au bouton.
                if (event.key === 'Escape' || event.key === 'Tab') trigger.focus();
                clavier(event);
            });
            recherche.addEventListener('input', function () {
                render();
                surligner(0, false);
                repositionner();
            });
        }
        menu.addEventListener('mousedown', function (event) {
            if (event.target !== recherche) event.preventDefault();
        });
        menu.addEventListener('mousemove', function (event) {
            var item = event.target.closest('.ca-select-option');
            if (item && +item.dataset.index !== actif) surligner(+item.dataset.index, false);
        });
        menu.addEventListener('click', function (event) {
            var item = event.target.closest('.ca-select-option');
            if (item) choisir(item.dataset.value);
        });
        document.addEventListener('click', function (event) {
            if (!wrapper.contains(event.target) && !menu.contains(event.target)) close();
        });
        wrapper.addEventListener('ca-select:close', close);
        // Un focus programmatique sur le <select> masqué (validation) est renvoyé au bouton.
        select.addEventListener('focus', function () { trigger.focus(); });
        select.addEventListener('change', refresh);
        select.addEventListener('ca-select:refresh', refresh);
        if (select.form) {
            select.form.addEventListener('reset', function () { setTimeout(refresh, 0); });
        }
        new MutationObserver(refresh).observe(select, {
            attributes: true,
            attributeFilter: ['class', 'disabled'],
            childList: true,
            subtree: true,
        });

        refresh();
    }

    function init(root) {
        (root || document).querySelectorAll('select[data-ca-select]').forEach(enhance);
    }

    window.caSelect = { init: init, placer: placer };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(); });
    } else {
        init();
    }
})();
