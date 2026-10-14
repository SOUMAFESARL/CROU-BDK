/*
 * Campagnes & admission : fenêtres <dialog>, résumé des résidences et contrôles
 * du formulaire de création de campagne (les règles serveur de CampagneRequest restent la référence).
 */
(function () {
    'use strict';

    // --- Fenêtres modales ---------------------------------------------------
    function openDialog(id) {
        var dialog = document.getElementById(id);
        if (dialog && typeof dialog.showModal === 'function' && !dialog.open) dialog.showModal();
    }

    document.addEventListener('click', function (event) {
        var opener = event.target.closest('[data-ca-open]');
        if (opener) {
            openDialog(opener.getAttribute('data-ca-open'));
            return;
        }
        var closer = event.target.closest('[data-ca-close]');
        if (closer) {
            var dialog = closer.closest('dialog');
            if (dialog) dialog.close();
            return;
        }
        // Clic sur le fond de la fenêtre : fermeture.
        if (event.target.tagName === 'DIALOG' && event.target.classList.contains('ca-dialog')) {
            var rect = event.target.getBoundingClientRect();
            var inside = event.clientX >= rect.left && event.clientX <= rect.right
                && event.clientY >= rect.top && event.clientY <= rect.bottom;
            if (!inside) event.target.close();
        }
    });

    document.querySelectorAll('dialog[data-ca-autoopen]').forEach(function (dialog) {
        openDialog(dialog.id);
    });

    // --- Formulaire de campagne --------------------------------------------
    var form = document.getElementById('campagneForm');
    if (!form) return;

    var errorBox = document.getElementById('campagneFormErrors');
    var field = function (name) { return form.querySelector('[name="' + name + '"]'); };

    function updateResidencesSummary() {
        var checked = form.querySelectorAll('.residence-checkbox:checked');
        var places = 0;
        checked.forEach(function (box) { places += parseInt(box.dataset.places, 10) || 0; });
        var count = document.getElementById('selectedCount');
        var total = document.getElementById('totalPlaces');
        if (count) count.textContent = checked.length;
        if (total) total.textContent = places;
    }

    form.addEventListener('change', function (event) {
        if (event.target.classList.contains('residence-checkbox')) updateResidencesSummary();
    });
    updateResidencesSummary();

    form.querySelectorAll('[data-ca-docs]').forEach(function (button) {
        button.addEventListener('click', function () {
            var value = button.getAttribute('data-ca-docs') === '1';
            form.querySelectorAll('.document-checkbox').forEach(function (box) { box.checked = value; });
        });
    });

    // Année universitaire : tiret ajouté automatiquement (2025 → 2025-).
    var annee = field('annee_campagne');
    if (annee) {
        annee.addEventListener('input', function (event) {
            if (event.inputType && event.inputType.indexOf('delete') === 0) return;
            var value = annee.value.replace(/[^\d-]/g, '');
            if (/^\d{4}$/.test(value)) value += '-';
            annee.value = value;
        });
    }

    // Dates : la date minimale de chaque champ suit la précédente.
    var dates = ['periode_debut', 'periode_fin', 'date_echeance_demande', 'date_echeance_contrat'];
    function syncDateMins() {
        for (var i = 1; i < dates.length; i++) {
            var previous = field(dates[i - 1]);
            var current = field(dates[i]);
            if (previous && current) current.min = previous.value || '';
        }
    }
    dates.forEach(function (name) {
        var input = field(name);
        if (input) input.addEventListener('change', syncDateMins);
    });
    syncDateMins();

    function collectErrors() {
        var errors = [];
        var firstInvalid = null;
        var flag = function (input, message) {
            errors.push(message);
            if (input) {
                input.classList.add('is-invalid');
                if (!firstInvalid) firstInvalid = input;
            }
        };
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });

        var anneeValue = annee ? annee.value.trim() : '';
        if (!/^\d{4}-\d{4}$/.test(anneeValue)) {
            flag(annee, 'L’année universitaire doit respecter le format AAAA-AAAA (ex. 2025-2026).');
        } else {
            var parts = anneeValue.split('-');
            if (parseInt(parts[1], 10) !== parseInt(parts[0], 10) + 1) {
                flag(annee, 'L’année universitaire doit couvrir deux années consécutives.');
            }
        }

        var description = field('description');
        if (description && description.value.trim().length < 10) {
            flag(description, 'La description doit contenir au moins 10 caractères.');
        }

        var labels = {
            periode_debut: 'la date d’ouverture', periode_fin: 'la date de clôture',
            date_echeance_demande: 'l’échéance des demandes', date_echeance_contrat: 'l’échéance des contrats'
        };
        dates.forEach(function (name, index) {
            var input = field(name);
            if (!input) return;
            if (!input.value) {
                flag(input, 'Renseignez ' + labels[name] + '.');
            } else if (index > 0) {
                var previous = field(dates[index - 1]);
                if (previous && previous.value && input.value <= previous.value) {
                    flag(input, labels[name].charAt(0).toUpperCase() + labels[name].slice(1) + ' doit être postérieure à ' + labels[dates[index - 1]] + '.');
                }
            }
        });

        ['moyenne_min', 'pourcentage_validate', 'distance_min'].forEach(function (name) {
            var input = field(name);
            if (input && input.value !== '' && !input.checkValidity()) {
                flag(input, 'Valeur invalide pour « ' + form.querySelector('label[for="' + name + '"]').textContent.trim() + ' ».');
            }
        });

        var previousAge = null;
        form.querySelectorAll('[data-ca-age]').forEach(function (input) {
            if (input.value === '') return;
            var age = parseInt(input.value, 10);
            var label = form.querySelector('label[for="' + input.id + '"]').textContent.trim();
            if (!input.checkValidity()) {
                flag(input, 'L’âge maximum ' + label + ' doit être compris entre 0 et 40 ans.');
            } else if (previousAge !== null && age <= previousAge) {
                flag(input, 'L’âge maximum ' + label + ' doit être supérieur à celui du niveau précédent.');
            }
            previousAge = age;
        });

        var admission = field('admission');
        var readmission = field('readmission');
        if (admission && readmission && !admission.checked && !readmission.checked) {
            flag(admission, 'Choisissez au moins un type : admission ou réadmission.');
        }

        if (!form.querySelector('.residence-checkbox:checked')) {
            flag(form.querySelector('.residence-checkbox'), 'Sélectionnez au moins une résidence.');
        }

        return { errors: errors, firstInvalid: firstInvalid };
    }

    form.addEventListener('submit', function (event) {
        var result = collectErrors();
        if (!result.errors.length) {
            if (errorBox) errorBox.hidden = true;
            return;
        }
        event.preventDefault();
        if (errorBox) {
            errorBox.innerHTML = '';
            var intro = document.createElement('div');
            intro.textContent = 'Veuillez corriger les points suivants :';
            var list = document.createElement('ul');
            result.errors.forEach(function (message) {
                var item = document.createElement('li');
                item.textContent = message;
                list.appendChild(item);
            });
            intro.appendChild(list);
            errorBox.appendChild(intro);
            errorBox.hidden = false;
            errorBox.scrollIntoView({ block: 'nearest' });
        }
        if (result.firstInvalid) result.firstInvalid.focus({ preventScroll: true });
    });
})();
