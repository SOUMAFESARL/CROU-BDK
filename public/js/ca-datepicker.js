/*
 * Sélecteur de date (style shadcn/ui « Date Picker ») pour les <input type="date" class="ca-input">
 * des pages « ca-page ». À charger après ca-select.js (positionnement partagé).
 * Le champ natif reste la source de vérité : valeur AAAA-MM-JJ envoyée au serveur, attributs
 * min / max / disabled / required et classe `is-invalid` suivis automatiquement ;
 * après un `input.value = …` par script, déclencher « ca-date:refresh ».
 */
(function () {
    'use strict';

    var ICONE_CALENDRIER = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>';
    var ICONE_PRECEDENT = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>';
    var ICONE_SUIVANT = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';
    var JOURS = ['lu', 'ma', 'me', 'je', 've', 'sa', 'di'];
    var JOURS_LONGS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
    var MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    var compteur = 0;

    function lire(valeur) {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(valeur || '');
        if (!m) return null;
        var date = new Date(+m[1], +m[2] - 1, +m[3]);
        return date.getMonth() === +m[2] - 1 ? date : null;
    }

    function iso(date) {
        var mois = String(date.getMonth() + 1).padStart(2, '0');
        var jour = String(date.getDate()).padStart(2, '0');
        return date.getFullYear() + '-' + mois + '-' + jour;
    }

    function memeJour(a, b) {
        return !!a && !!b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }

    function ajouterJours(date, n) {
        return new Date(date.getFullYear(), date.getMonth(), date.getDate() + n);
    }

    // Même jour du mois décalé de n mois (ramené au dernier jour si besoin : 31 janv. → 28 févr.).
    function ajouterMois(date, n) {
        var dernier = new Date(date.getFullYear(), date.getMonth() + n + 1, 0).getDate();
        return new Date(date.getFullYear(), date.getMonth() + n, Math.min(date.getDate(), dernier));
    }

    function libelle(date) {
        return date.getDate() + ' ' + MOIS[date.getMonth()] + ' ' + date.getFullYear();
    }

    function enhance(input) {
        if (input.dataset.caDateReady) return;
        input.dataset.caDateReady = '1';
        compteur += 1;

        var placeholder = input.getAttribute('placeholder') || 'Choisir une date';

        var wrapper = document.createElement('div');
        wrapper.className = 'ca-date';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        input.classList.add('ca-select-native');
        input.tabIndex = -1;
        input.setAttribute('aria-hidden', 'true');

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'ca-input ca-date-trigger';
        trigger.id = input.id ? input.id + '_trigger' : 'ca_date_' + compteur;
        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.innerHTML = ICONE_CALENDRIER + '<span></span>';
        wrapper.appendChild(trigger);

        if (input.id) {
            document.querySelectorAll('label[for="' + input.id + '"]').forEach(function (label) {
                label.setAttribute('for', trigger.id);
            });
        }

        var popover = document.createElement('div');
        popover.className = 'ca-date-popover';
        popover.setAttribute('role', 'dialog');
        popover.setAttribute('aria-label', 'Choisir une date');
        popover.hidden = true;
        popover.innerHTML =
            '<div class="ca-cal-head">' +
                '<button type="button" class="ca-cal-nav" data-nav="-1" aria-label="Mois précédent">' + ICONE_PRECEDENT + '</button>' +
                '<div class="ca-cal-caption" aria-live="polite"></div>' +
                '<button type="button" class="ca-cal-nav" data-nav="1" aria-label="Mois suivant">' + ICONE_SUIVANT + '</button>' +
            '</div>' +
            '<table class="ca-cal-grid" role="grid"><thead><tr>' +
                JOURS.map(function (jour, i) { return '<th scope="col" abbr="' + JOURS_LONGS[i] + '">' + jour + '</th>'; }).join('') +
            '</tr></thead><tbody></tbody></table>' +
            '<div class="ca-cal-foot">' +
                '<button type="button" class="ca-cal-link" data-action="today">Aujourd’hui</button>' +
                '<button type="button" class="ca-cal-link" data-action="clear">Effacer</button>' +
            '</div>';
        wrapper.appendChild(popover);

        var caption = popover.querySelector('.ca-cal-caption');
        var corps = popover.querySelector('tbody');
        var boutonAujourdhui = popover.querySelector('[data-action="today"]');
        var boutonEffacer = popover.querySelector('[data-action="clear"]');
        var mois = null;   // 1er jour du mois affiché
        var focus = null;  // jour portant le focus clavier

        function bornes() {
            return { min: lire(input.min), max: lire(input.max) };
        }

        function horsBornes(date, b) {
            return (b.min && date < b.min) || (b.max && date > b.max);
        }

        function borner(date, b) {
            if (b.min && date < b.min) return b.min;
            if (b.max && date > b.max) return b.max;
            return date;
        }

        function refresh() {
            var date = lire(input.value);
            trigger.lastChild.textContent = date ? libelle(date) : placeholder;
            trigger.classList.toggle('is-placeholder', !date);
            trigger.classList.toggle('is-invalid', input.classList.contains('is-invalid'));
            trigger.disabled = input.disabled;
            if (input.disabled) close();
        }

        function render() {
            var b = bornes();
            var selection = lire(input.value);
            var aujourdhui = new Date();
            caption.textContent = MOIS[mois.getMonth()] + ' ' + mois.getFullYear();

            // La grille commence le lundi de la semaine contenant le 1er du mois.
            var debut = ajouterJours(mois, -((mois.getDay() + 6) % 7));
            var fin = new Date(mois.getFullYear(), mois.getMonth() + 1, 0);
            corps.innerHTML = '';
            for (var jour = debut; jour <= fin || (jour.getDay() + 6) % 7 !== 0; jour = ajouterJours(jour, 1)) {
                if ((jour.getDay() + 6) % 7 === 0) corps.appendChild(document.createElement('tr'));
                var cellule = document.createElement('td');
                var bouton = document.createElement('button');
                bouton.type = 'button';
                bouton.className = 'ca-cal-day';
                bouton.textContent = jour.getDate();
                bouton.dataset.date = iso(jour);
                bouton.tabIndex = memeJour(jour, focus) ? 0 : -1;
                bouton.setAttribute('aria-label', JOURS_LONGS[(jour.getDay() + 6) % 7] + ' ' + libelle(jour));
                if (jour.getMonth() !== mois.getMonth()) bouton.classList.add('is-outside');
                if (memeJour(jour, aujourdhui)) {
                    bouton.classList.add('is-today');
                    bouton.setAttribute('aria-current', 'date');
                }
                if (memeJour(jour, selection)) {
                    bouton.classList.add('is-selected');
                    bouton.setAttribute('aria-pressed', 'true');
                }
                if (horsBornes(jour, b)) bouton.disabled = true;
                cellule.appendChild(bouton);
                corps.lastChild.appendChild(cellule);
            }

            var premier = new Date(mois.getFullYear(), mois.getMonth(), 1);
            var dernier = new Date(mois.getFullYear(), mois.getMonth() + 1, 0);
            popover.querySelector('[data-nav="-1"]').disabled = !!(b.min && premier <= b.min);
            popover.querySelector('[data-nav="1"]').disabled = !!(b.max && dernier >= b.max);
            boutonAujourdhui.disabled = !!horsBornes(new Date(aujourdhui.getFullYear(), aujourdhui.getMonth(), aujourdhui.getDate()), b);
            boutonEffacer.hidden = input.required || !selection;
        }

        function afficher(date, donnerFocus) {
            focus = borner(date, bornes());
            mois = new Date(focus.getFullYear(), focus.getMonth(), 1);
            render();
            if (donnerFocus) {
                var cible = corps.querySelector('[data-date="' + iso(focus) + '"]');
                if (cible) cible.focus();
            }
        }

        function repositionner() {
            if (window.caSelect && window.caSelect.placer) window.caSelect.placer(trigger, popover, false);
        }

        function open() {
            if (input.disabled || !popover.hidden) return;
            document.querySelectorAll('.ca-select.is-open, .ca-date.is-open').forEach(function (autre) {
                autre.dispatchEvent(new CustomEvent('ca-select:close'));
            });
            popover.hidden = false;
            wrapper.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            afficher(lire(input.value) || new Date(), false);
            repositionner();
            var cible = corps.querySelector('[tabindex="0"]');
            if (cible) cible.focus({ preventScroll: true });
            window.addEventListener('scroll', repositionner, true);
            window.addEventListener('resize', repositionner);
        }

        function close(rendreFocus) {
            if (popover.hidden) return;
            popover.hidden = true;
            wrapper.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            window.removeEventListener('scroll', repositionner, true);
            window.removeEventListener('resize', repositionner);
            if (rendreFocus) trigger.focus();
        }

        function choisir(valeur) {
            close(true);
            if (input.value === valeur) return;
            input.value = valeur;
            input.classList.remove('is-invalid');
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            refresh();
        }

        trigger.addEventListener('click', function () { popover.hidden ? open() : close(); });
        trigger.addEventListener('keydown', function (event) {
            if ((event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') && popover.hidden) {
                event.preventDefault();
                open();
            }
        });

        popover.addEventListener('click', function (event) {
            var nav = event.target.closest('[data-nav]');
            if (nav) {
                var cible = ajouterMois(focus, +nav.dataset.nav);
                // Changement de mois à la souris : le focus reste sur le bouton de navigation.
                afficher(cible, false);
                return;
            }
            var jour = event.target.closest('.ca-cal-day');
            if (jour && !jour.disabled) {
                choisir(jour.dataset.date);
                return;
            }
            var action = event.target.closest('[data-action]');
            if (action && action.dataset.action === 'today') choisir(iso(new Date()));
            if (action && action.dataset.action === 'clear') choisir('');
        });

        popover.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                close(true);
                return;
            }
            if (!event.target.classList.contains('ca-cal-day')) return;
            var deplacements = {
                ArrowLeft: function (d) { return ajouterJours(d, -1); },
                ArrowRight: function (d) { return ajouterJours(d, 1); },
                ArrowUp: function (d) { return ajouterJours(d, -7); },
                ArrowDown: function (d) { return ajouterJours(d, 7); },
                Home: function (d) { return ajouterJours(d, -((d.getDay() + 6) % 7)); },
                End: function (d) { return ajouterJours(d, 6 - (d.getDay() + 6) % 7); },
                PageUp: function (d) { return ajouterMois(d, event.shiftKey ? -12 : -1); },
                PageDown: function (d) { return ajouterMois(d, event.shiftKey ? 12 : 1); },
            };
            if (!deplacements[event.key]) return;
            event.preventDefault();
            afficher(deplacements[event.key](focus), true);
        });

        document.addEventListener('mousedown', function (event) {
            if (!wrapper.contains(event.target)) close();
        });
        wrapper.addEventListener('focusout', function (event) {
            if (event.relatedTarget && !wrapper.contains(event.relatedTarget)) close();
        });
        wrapper.addEventListener('ca-select:close', function () { close(); });
        input.addEventListener('focus', function () { trigger.focus(); });
        input.addEventListener('change', refresh);
        input.addEventListener('ca-date:refresh', refresh);
        if (input.form) {
            input.form.addEventListener('reset', function () { setTimeout(refresh, 0); });
        }
        new MutationObserver(function () {
            refresh();
            if (!popover.hidden) render();
        }).observe(input, { attributes: true, attributeFilter: ['class', 'disabled', 'min', 'max', 'value', 'required'] });

        refresh();
    }

    function init(root) {
        (root || document).querySelectorAll('.ca-page input[type="date"].ca-input').forEach(enhance);
    }

    window.caDate = { init: init };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(); });
    } else {
        init();
    }
})();
