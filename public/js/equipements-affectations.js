/*
 * Affectations du matériel au parc (admin/gestion-hebergement/equipements/affectations).
 * - Assistant d'affectation en 3 étapes : chaque étape doit être valide avant la suivante
 *   (les règles serveur de EquipmentAllocationService restent la référence).
 * - Fenêtres « État du matériel » et « Retour au magasin » renseignées depuis la ligne cliquée.
 * L'ouverture / fermeture des <dialog> est gérée par campagnes-admission.js.
 */
(function () {
    'use strict';

    function setText(root, selector, value) {
        root.querySelectorAll(selector).forEach(function (el) { el.textContent = value; });
    }

    function openDialog(id) {
        var dialog = document.getElementById(id);
        if (dialog && typeof dialog.showModal === 'function' && !dialog.open) dialog.showModal();
        return dialog;
    }

    // --- Actions des lignes ------------------------------------------------
    document.addEventListener('click', function (event) {
        var etat = event.target.closest('[data-af-etat]');
        if (etat) {
            var etatDialog = document.getElementById('af-etat-form');
            var etatForm = etatDialog.querySelector('form');
            etatForm.action = etat.dataset.action;
            setText(etatDialog, '[data-af-name]', etat.dataset.name);
            setText(etatDialog, '[data-af-lieu]', etat.dataset.lieu);
            etatForm.querySelectorAll('input[name="etat"]').forEach(function (radio) {
                radio.checked = radio.value === etat.dataset.etat;
            });
            openDialog('af-etat-form');
            return;
        }
        var retour = event.target.closest('[data-af-retour]');
        if (retour) {
            var retourDialog = document.getElementById('af-retour-form');
            retourDialog.querySelector('form').action = retour.dataset.action;
            setText(retourDialog, '[data-af-name]', retour.dataset.name);
            setText(retourDialog, '[data-af-lieu]', retour.dataset.lieu);
            setText(retourDialog, '[data-af-quantite]', retour.dataset.quantite);
            openDialog('af-retour-form');
        }
    });

    // Empêche le double envoi des formulaires de confirmation.
    document.querySelectorAll('#af-etat-form form, #af-retour-form form').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('button[type="submit"]').forEach(function (button) { button.disabled = true; });
        });
    });

    // --- Recherche instantanée ----------------------------------------------
    // Recharge la liste et les compteurs sans bouton ni rechargement complet.
    var filters = document.querySelector('[data-af-filters]');
    if (filters) {
        var results = document.querySelector('[data-af-results]');
        var kpis = document.querySelector('[data-af-kpis]');
        var searchBox = filters.querySelector('[data-af-search]');
        var reset = filters.querySelector('[data-af-reset]');
        var timer = null;
        var controller = null;

        var refresh = function (url) {
            if (controller) controller.abort();
            controller = new AbortController();
            results.classList.add('is-loading');
            searchBox.classList.add('is-loading');
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
                .then(function (response) {
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    return response.text();
                })
                .then(function (html) {
                    var page = new DOMParser().parseFromString(html, 'text/html');
                    var newResults = page.querySelector('[data-af-results]');
                    var newKpis = page.querySelector('[data-af-kpis]');
                    var newReset = page.querySelector('[data-af-reset]');
                    if (newResults) results.innerHTML = newResults.innerHTML;
                    if (newKpis && kpis) kpis.innerHTML = newKpis.innerHTML;
                    if (newReset && reset) reset.hidden = newReset.hidden;
                    history.replaceState(null, '', url);
                })
                .catch(function (error) {
                    if (error.name !== 'AbortError') window.location.href = url;
                })
                .finally(function () {
                    results.classList.remove('is-loading');
                    searchBox.classList.remove('is-loading');
                });
        };

        var currentUrl = function () {
            var params = new URLSearchParams();
            new FormData(filters).forEach(function (value, key) {
                if (String(value).trim() !== '') params.append(key, String(value).trim());
            });
            var query = params.toString();
            return filters.action + (query ? '?' + query : '');
        };

        filters.addEventListener('input', function (event) {
            if (event.target.name !== 'q') return;
            clearTimeout(timer);
            timer = setTimeout(function () { refresh(currentUrl()); }, 300);
        });
        filters.addEventListener('change', function (event) {
            if (event.target.name !== 'cite_id') return;
            clearTimeout(timer);
            refresh(currentUrl());
        });
        filters.addEventListener('submit', function (event) {
            event.preventDefault();
            clearTimeout(timer);
            refresh(currentUrl());
        });
    }

    // --- Assistant d'affectation --------------------------------------------
    var form = document.getElementById('afStoreForm');
    if (!form) return;

    var field = function (name) { return form.elements[name]; };
    var equipement = field('equipement_id');
    var quantite = field('quantite');
    var cite = field('cite_id');
    var batiment = field('batiment_id');
    var chambre = field('chambre_id');
    var stockInfo = form.querySelector('[data-af-stock]');
    var errorBox = form.querySelector('[data-af-errors]');
    var steps = Array.prototype.slice.call(form.querySelectorAll('[data-af-step]'));
    var tabs = Array.prototype.slice.call(form.querySelectorAll('[data-af-step-tab]'));
    var prevButton = form.querySelector('[data-af-step-prev]');
    var nextButton = form.querySelector('[data-af-step-next]');
    var submitButton = form.querySelector('[data-af-step-submit]');
    var currentLabel = form.querySelector('[data-af-step-current]');
    var current = 0;
    var reached = 0;
    var nombre = new Intl.NumberFormat('fr-FR');

    function refreshSelect(select) {
        select.dispatchEvent(new Event('ca-select:refresh'));
    }

    function selectedOption(select) {
        return select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
    }

    function stockDisponible() {
        var option = selectedOption(equipement);
        return option && option.value ? parseInt(option.dataset.stock, 10) || 0 : null;
    }

    // Bâtiments de la cité choisie, chambres du bâtiment choisi.
    function filtrer(select, cle, valeur) {
        Array.prototype.forEach.call(select.options, function (option) {
            var masque = option.value !== '' && (!valeur || option.dataset[cle] !== valeur);
            option.hidden = masque;
            option.disabled = masque;
            if (option.selected && masque) select.value = '';
        });
        select.disabled = !valeur;
        refreshSelect(select);
    }

    function majEmplacement() {
        filtrer(batiment, 'cite', cite.value);
        filtrer(chambre, 'building', batiment.value);
    }

    function majStock() {
        var stock = stockDisponible();
        if (stock === null) {
            stockInfo.hidden = true;
            quantite.removeAttribute('max');
            return;
        }
        quantite.max = stock;
        stockInfo.hidden = false;
        stockInfo.lastElementChild.textContent = nombre.format(stock) + ' unité(s) disponible(s) en magasin.';
    }

    cite.addEventListener('change', majEmplacement);
    batiment.addEventListener('change', majEmplacement);
    equipement.addEventListener('change', majStock);

    function stepOf(element) {
        var panel = element.closest('[data-af-step]');
        return panel ? parseInt(panel.getAttribute('data-af-step'), 10) : 0;
    }

    function collectErrors() {
        var errors = [];
        function add(input, message) { errors.push({ input: input, message: message }); }

        if (!equipement.value) add(equipement, 'Sélectionnez l’équipement à affecter.');
        var qte = Number(quantite.value);
        var stock = stockDisponible();
        if (quantite.value === '' || !Number.isInteger(qte) || qte < 1) {
            add(quantite, 'Saisissez une quantité entière d’au moins 1.');
        } else if (stock !== null && qte > stock) {
            add(quantite, 'La quantité dépasse le stock disponible (' + nombre.format(stock) + ').');
        }
        if (!form.querySelector('input[name="etat"]:checked')) {
            add(form.querySelector('input[name="etat"]'), 'Indiquez l’état du matériel à la sortie.');
        }
        if (!cite.value) add(cite, 'Sélectionnez la cité qui reçoit le matériel.');
        if (chambre.value && !batiment.value) add(batiment, 'Choisissez le bâtiment de la chambre.');
        if (field('notes').value.length > 2000) add(field('notes'), 'Les observations ne doivent pas dépasser 2 000 caractères.');
        return errors;
    }

    function showErrors(errors) {
        if (!errors.length) {
            errorBox.hidden = true;
            errorBox.innerHTML = '';
            return;
        }
        var list = document.createElement('ul');
        errors.forEach(function (error) {
            var item = document.createElement('li');
            item.textContent = error.message;
            list.appendChild(item);
        });
        errorBox.innerHTML = '<div>Corrigez les points suivants :</div>';
        errorBox.firstChild.appendChild(list);
        errorBox.hidden = false;
    }

    function validateStep(index) {
        steps[index].querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        var errors = collectErrors().filter(function (error) { return stepOf(error.input) === index; });
        errors.forEach(function (error) {
            if (error.input.type === 'radio') {
                form.querySelectorAll('input[name="' + error.input.name + '"]').forEach(function (radio) { radio.classList.add('is-invalid'); });
            } else {
                error.input.classList.add('is-invalid');
            }
        });
        showErrors(errors);
        if (errors.length) errors[0].input.focus();
        return !errors.length;
    }

    function recap() {
        var option = selectedOption(equipement);
        var etat = form.querySelector('input[name="etat"]:checked');
        var lieu = [selectedOption(cite), selectedOption(batiment), selectedOption(chambre)]
            .filter(function (opt) { return opt && opt.value; })
            .map(function (opt) { return opt.textContent.trim(); });
        if (lieu.length === 1) lieu.push('commun à la cité');
        else if (lieu.length === 2) lieu.push('commun au bâtiment');
        var stock = stockDisponible() || 0;
        var qte = parseInt(quantite.value, 10) || 0;

        setText(form, '[data-af-recap="equipement"]', option && option.value ? option.textContent.split(' — ')[0].trim() : '—');
        setText(form, '[data-af-recap="quantite"]', nombre.format(qte));
        setText(form, '[data-af-recap="etat"]', etat ? etat.closest('label').querySelector('.ca-check-title').textContent : '—');
        setText(form, '[data-af-recap="lieu"]', lieu.join(' · ') || '—');
        setText(form, '[data-af-recap="reste"]', nombre.format(Math.max(0, stock - qte)) + ' unité(s)');
    }

    function showStep(index) {
        current = Math.max(0, Math.min(index, steps.length - 1));
        reached = Math.max(reached, current);
        steps.forEach(function (panel, i) { panel.hidden = i !== current; });
        tabs.forEach(function (tab, i) {
            tab.classList.toggle('is-active', i === current);
            tab.classList.toggle('is-done', i < current);
            tab.disabled = i > reached;
            if (i === current) tab.setAttribute('aria-current', 'step');
            else tab.removeAttribute('aria-current');
        });
        var last = current === steps.length - 1;
        if (last) recap();
        prevButton.hidden = current === 0;
        nextButton.hidden = last;
        submitButton.hidden = !last;
        currentLabel.textContent = current + 1;
    }

    nextButton.addEventListener('click', function () {
        if (validateStep(current)) showStep(current + 1);
    });
    prevButton.addEventListener('click', function () {
        showErrors([]);
        showStep(current - 1);
    });
    tabs.forEach(function (tab, i) {
        tab.addEventListener('click', function () {
            if (i > current && !validateStep(current)) return;
            showErrors([]);
            showStep(i);
        });
    });
    form.addEventListener('change', function (event) {
        if (event.target.classList.contains('is-invalid')) event.target.classList.remove('is-invalid');
        if (event.target.name === 'etat') {
            form.querySelectorAll('input[name="etat"]').forEach(function (radio) { radio.classList.remove('is-invalid'); });
        }
    });

    // « Entrée » dans un champ : passe à l'étape suivante au lieu d'envoyer le formulaire.
    form.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && event.target.tagName === 'INPUT' && current < steps.length - 1) {
            event.preventDefault();
            nextButton.click();
        }
    });

    form.addEventListener('submit', function (event) {
        var errors = collectErrors();
        if (!errors.length) {
            submitButton.disabled = true;
            return;
        }
        event.preventDefault();
        showStep(stepOf(errors[0].input));
        validateStep(current);
    });

    // Réouverture après une erreur serveur : on revient à l'étape du premier champ en erreur.
    majStock();
    majEmplacement();
    var serverError = form.querySelector('.ca-error');
    if (serverError) {
        reached = steps.length - 1;
        showStep(stepOf(serverError));
    } else {
        showStep(0);
    }

    // Nouvelle ouverture du bouton « Affecter » : formulaire remis à zéro.
    document.querySelectorAll('[data-ca-open="af-store-form"]').forEach(function (opener) {
        opener.addEventListener('click', function () {
            if (form.querySelector('.ca-error')) return;
            form.reset();
            setTimeout(function () {
                showErrors([]);
                form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
                majStock();
                majEmplacement();
                reached = 0;
                showStep(0);
            }, 0);
        });
    });
})();
