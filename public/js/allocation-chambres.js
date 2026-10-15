/*
 * Attribution des chambres (/admin/allocation-chambres/*) : onglets et recherche du suivi
 * de campagne, fenêtres de confirmation, formulaire d'attribution et pré-contrôle.
 * Le pré-contrôle n'est qu'indicatif : AttributionRules (serveur) reste la référence.
 * Les fenêtres <dialog> s'ouvrent via public/js/campagnes-admission.js (data-ca-open).
 */
(function () {
    'use strict';

    function normaliser(texte) {
        return String(texte || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    }

    // --- Envoi unique : évite les doubles soumissions -----------------------
    document.querySelectorAll('form[data-al-once]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            // Après les autres écouteurs : un envoi bloqué par la validation laisse le bouton actif.
            setTimeout(function () {
                if (event.defaultPrevented) return;
                form.querySelectorAll('button[type="submit"]').forEach(function (bouton) { bouton.disabled = true; });
            }, 0);
        });
    });

    // --- Liste des campagnes : affichage liste / cartes, mémorisé par navigateur ---
    var vues = document.querySelector('[data-al-views]');
    if (vues) {
        var boutonsVue = Array.prototype.slice.call(vues.querySelectorAll('[data-al-view]'));
        var afficherVue = function (vue) {
            boutonsVue.forEach(function (bouton) {
                var actif = bouton.getAttribute('data-al-view') === vue;
                bouton.classList.toggle('is-active', actif);
                bouton.setAttribute('aria-pressed', actif ? 'true' : 'false');
            });
            document.querySelectorAll('[data-al-view-panel]').forEach(function (panneau) {
                panneau.hidden = panneau.getAttribute('data-al-view-panel') !== vue;
            });
        };
        var vueEnregistree = null;
        try { vueEnregistree = window.localStorage.getItem('al-campagnes-vue'); } catch (e) { /* stockage indisponible */ }
        if (vueEnregistree === 'cartes' || vueEnregistree === 'liste') afficherVue(vueEnregistree);
        boutonsVue.forEach(function (bouton) {
            bouton.addEventListener('click', function () {
                var vue = bouton.getAttribute('data-al-view');
                afficherVue(vue);
                try { window.localStorage.setItem('al-campagnes-vue', vue); } catch (e) { /* stockage indisponible */ }
            });
        });
    }

    // --- Onglets (suivi de campagne), onglet courant dans ?onglet= ---------
    var onglets = document.querySelector('[data-al-tabs]');
    if (onglets) {
        var boutons = Array.prototype.slice.call(onglets.querySelectorAll('[data-al-tab]'));
        var activer = function (cle, focus) {
            boutons.forEach(function (bouton) {
                var actif = bouton.getAttribute('data-al-tab') === cle;
                bouton.setAttribute('aria-selected', actif ? 'true' : 'false');
                bouton.tabIndex = actif ? 0 : -1;
                if (actif && focus) bouton.focus();
            });
            onglets.querySelectorAll('[data-al-panel]').forEach(function (panneau) {
                panneau.hidden = panneau.getAttribute('data-al-panel') !== cle;
            });
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('onglet', cle);
                window.history.replaceState(null, '', url);
            } catch (e) { /* navigateur ancien : l'onglet n'est simplement pas mémorisé */ }
        };
        boutons.forEach(function (bouton, index) {
            bouton.addEventListener('click', function () { activer(bouton.getAttribute('data-al-tab')); });
            bouton.addEventListener('keydown', function (event) {
                var cible = null;
                if (event.key === 'ArrowRight') cible = boutons[(index + 1) % boutons.length];
                if (event.key === 'ArrowLeft') cible = boutons[(index - 1 + boutons.length) % boutons.length];
                if (event.key === 'Home') cible = boutons[0];
                if (event.key === 'End') cible = boutons[boutons.length - 1];
                if (cible) {
                    event.preventDefault();
                    activer(cible.getAttribute('data-al-tab'), true);
                }
            });
        });
        var demande = new URLSearchParams(window.location.search).get('onglet');
        if (demande && boutons.some(function (b) { return b.getAttribute('data-al-tab') === demande; })) activer(demande);
    }

    // --- Recherche locale dans un onglet -----------------------------------
    document.querySelectorAll('[data-al-filter]').forEach(function (champ) {
        var cle = champ.getAttribute('data-al-filter');
        var conteneur = document.querySelector('[data-al-rows="' + cle + '"]');
        var compteur = document.querySelector('[data-al-count="' + cle + '"]');
        var vide = document.querySelector('[data-al-empty="' + cle + '"]');
        if (!conteneur) return;
        var lignes = Array.prototype.slice.call(conteneur.querySelectorAll('[data-al-text]'));
        var table = conteneur.closest('.ca-table-wrap');
        var filtrer = function () {
            var terme = normaliser(champ.value.trim());
            var visibles = 0;
            lignes.forEach(function (ligne) {
                var ok = !terme || normaliser(ligne.getAttribute('data-al-text')).indexOf(terme) !== -1;
                ligne.hidden = !ok;
                if (ok) visibles += 1;
            });
            if (compteur) compteur.textContent = terme ? visibles + ' résultat(s) sur ' + lignes.length : lignes.length + ' élément(s)';
            if (vide) vide.hidden = visibles > 0;
            (table || conteneur).hidden = visibles === 0;
        };
        champ.addEventListener('input', filtrer);
        filtrer();
    });

    // --- Fenêtres de confirmation : report des données de la ligne --------
    document.addEventListener('click', function (event) {
        var bouton = event.target.closest('[data-al-assignation][data-ca-open]');
        if (!bouton) return;
        var dialog = document.getElementById(bouton.getAttribute('data-ca-open'));
        if (!dialog) return;
        dialog.querySelectorAll('[data-al-field]').forEach(function (cible) {
            var valeur = bouton.getAttribute('data-al-' + cible.getAttribute('data-al-field')) || '';
            if (cible.tagName === 'INPUT') cible.value = valeur; else cible.textContent = valeur;
        });
        var motif = dialog.querySelector('textarea[name="motif"]');
        if (motif) motif.value = '';
        dialog.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = false; });
    }, true);

    // --- Formulaire d'attribution -------------------------------------------
    var form = document.getElementById('alAllocateForm');
    if (!form) return;

    var selectEtudiant = document.getElementById('al-etudiant');
    var selectChambre = document.getElementById('al-chambre');
    var selectLit = document.getElementById('al-lit');
    var sectionChambre = form.querySelector('[data-al-chambre-section]');
    var infoEtudiant = form.querySelector('[data-al-etudiant-info]');
    var infoChambre = form.querySelector('[data-al-chambre-info]');
    var selectPmr = document.getElementById('al-pmr-etudiant');
    var resume = function (cle) { return document.querySelector('[data-al-summary="' + cle + '"]'); };
    var info = function (racine, cle, valeur) {
        var cible = racine.querySelector('[data-al-info="' + cle + '"]');
        if (cible) cible.textContent = valeur || '—';
    };
    var choisie = function (select) {
        return select && select.selectedIndex > 0 ? select.options[select.selectedIndex] : null;
    };
    var rafraichir = function (select) { if (select) select.dispatchEvent(new Event('ca-select:refresh')); };
    var mode = function () {
        var coche = form.querySelector('input[name="allocation_type"]:checked');
        return coche ? coche.value : 'automatique';
    };
    var ICONES = {
        ok: '<path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/>',
        ko: '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
        warn: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        idle: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>'
    };
    var SEXES = { m: 'hommes', f: 'femmes' };

    function remplirLits() {
        var option = choisie(selectChambre);
        var initial = selectLit.getAttribute('data-al-lit-initial') || '';
        selectLit.setAttribute('data-al-lit-initial', '');
        while (selectLit.options.length > 1) selectLit.remove(1);
        var lits = [];
        try { lits = JSON.parse(option ? option.getAttribute('data-lits') || '[]' : '[]'); } catch (e) { lits = []; }
        lits.forEach(function (numero) {
            selectLit.add(new Option('Lit ' + numero, numero, false, String(numero) === initial));
        });
        rafraichir(selectLit);
    }

    function controles() {
        var etudiant = choisie(selectEtudiant);
        var chambre = mode() === 'manuelle' ? choisie(selectChambre) : null;
        var lignes = [];
        if (!etudiant) return [['idle', 'Sélectionnez un étudiant.']];

        if (mode() === 'automatique') {
            lignes.push(['ok', 'Recherche automatique parmi les chambres ' + (etudiant.getAttribute('data-priorites') || 'compatibles') + '.']);
            if (etudiant.getAttribute('data-pmr') === '1') lignes.push(['ok', 'Statut PMR confirmé : les chambres adaptées sont aussi proposées.']);
        } else if (!chambre) {
            return [['idle', 'Choisissez une chambre.']];
        } else {
            var pmrChambre = chambre.getAttribute('data-pmr') === '1';
            var pmrEtudiant = etudiant.getAttribute('data-pmr') === '1';
            if (pmrChambre && !pmrEtudiant) lignes.push(['ko', 'Chambre adaptée PMR : le statut PMR de l’étudiant n’est pas confirmé.']);
            else lignes.push(['ok', pmrChambre ? 'Chambre PMR et statut PMR confirmé.' : 'Pas de restriction PMR.']);

            var requis = chambre.getAttribute('data-sexe');
            var sexe = etudiant.getAttribute('data-sexe');
            if (requis === 'incomplet') lignes.push(['ko', 'Type d’occupation non renseigné pour la chambre, le bâtiment ou la cité.']);
            else if (requis === 'conflit') lignes.push(['ko', 'Types d’occupation contradictoires entre la chambre, le bâtiment et la cité.']);
            else if (requis === 'mixte') lignes.push(['ok', 'Occupation mixte.']);
            else if (!sexe) lignes.push(['warn', 'Genre de l’étudiant non renseigné : chambre réservée aux ' + SEXES[requis] + '.']);
            else if (sexe !== requis) lignes.push(['ko', 'Chambre réservée aux ' + SEXES[requis] + '.']);
            else lignes.push(['ok', 'Chambre réservée aux ' + SEXES[requis] + ' : compatible.']);

            var libres = parseInt(chambre.getAttribute('data-libres'), 10) || 0;
            lignes.push(libres > 0 ? ['ok', libres + ' place(s) libre(s).'] : ['ko', 'Plus aucune place libre.']);
        }
        lignes.push(['idle', 'Sanctions, campagne et places sont revérifiées à l’enregistrement.']);
        return lignes;
    }

    function majResume() {
        var etudiant = choisie(selectEtudiant);
        var nom = resume('etudiant');
        nom.textContent = etudiant ? etudiant.getAttribute('data-nom') : 'Non sélectionné';
        nom.classList.toggle('is-empty', !etudiant);
        resume('niveau').textContent = etudiant ? etudiant.getAttribute('data-matricule') + ' · ' + etudiant.getAttribute('data-niveau') : '';

        var chambre = mode() === 'manuelle' ? choisie(selectChambre) : null;
        var libelle = resume('chambre');
        if (mode() === 'automatique') {
            libelle.textContent = 'Choix automatique';
            resume('chambre-sub').textContent = 'Première chambre compatible';
        } else {
            var lit = choisie(selectLit);
            libelle.textContent = chambre ? 'Chambre ' + chambre.getAttribute('data-numero') : 'Non sélectionnée';
            resume('chambre-sub').textContent = chambre
                ? chambre.getAttribute('data-type') + (lit ? ' · ' + lit.textContent : ' · premier lit disponible')
                : '';
        }
        libelle.classList.toggle('is-empty', mode() === 'manuelle' && !chambre);

        var liste = document.querySelector('[data-al-checks]');
        liste.innerHTML = '';
        controles().forEach(function (ligne) {
            var item = document.createElement('li');
            item.className = 'is-' + ligne[0];
            item.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + ICONES[ligne[0]] + '</svg>';
            var texte = document.createElement('span');
            texte.textContent = ligne[1];
            item.appendChild(texte);
            liste.appendChild(item);
        });
    }

    function majEtudiant() {
        var etudiant = choisie(selectEtudiant);
        infoEtudiant.hidden = !etudiant;
        if (etudiant) {
            info(infoEtudiant, 'matricule', etudiant.getAttribute('data-matricule'));
            info(infoEtudiant, 'niveau', etudiant.getAttribute('data-niveau'));
            info(infoEtudiant, 'email', etudiant.getAttribute('data-email'));
            info(infoEtudiant, 'pmr', etudiant.getAttribute('data-pmr') === '1' ? 'Confirmé' : 'Non confirmé');
            form.querySelector('[data-al-error="etudiant"]').hidden = true;
            // La vérification PMR porte par défaut sur l'étudiant en cours.
            if (selectPmr && !selectPmr.value) {
                selectPmr.value = etudiant.value;
                rafraichir(selectPmr);
            }
        }
        majResume();
    }

    function majChambre() {
        var chambre = choisie(selectChambre);
        infoChambre.hidden = !chambre;
        if (chambre) {
            var capacite = parseInt(chambre.getAttribute('data-capacite'), 10) || 0;
            var occupes = parseInt(chambre.getAttribute('data-occupes'), 10) || 0;
            info(infoChambre, 'type', chambre.getAttribute('data-type'));
            info(infoChambre, 'lieu', chambre.getAttribute('data-lieu'));
            info(infoChambre, 'libres', chambre.getAttribute('data-libres') + ' / ' + capacite);
            info(infoChambre, 'prix', chambre.getAttribute('data-prix') + ' FCFA');
            infoChambre.querySelector('[data-al-info="occupation"]').style.width = (capacite ? Math.min(100, occupes / capacite * 100) : 0) + '%';
            form.querySelector('[data-al-error="chambre"]').hidden = true;
        }
        remplirLits();
        majResume();
    }

    function majMode() {
        var manuel = mode() === 'manuelle';
        sectionChambre.hidden = !manuel;
        selectChambre.disabled = !manuel;
        selectLit.disabled = !manuel;
        if (!manuel) {
            selectChambre.value = '';
            rafraichir(selectChambre);
            majChambre();
        }
        majResume();
    }

    selectEtudiant.addEventListener('change', majEtudiant);
    selectChambre.addEventListener('change', majChambre);
    selectLit.addEventListener('change', majResume);
    form.querySelectorAll('[data-al-mode]').forEach(function (radio) { radio.addEventListener('change', majMode); });

    form.addEventListener('submit', function (event) {
        var erreurs = [];
        if (!selectEtudiant.value) erreurs.push(['etudiant', selectEtudiant]);
        if (mode() === 'manuelle' && !selectChambre.value) erreurs.push(['chambre', selectChambre]);
        erreurs.forEach(function (erreur) {
            form.querySelector('[data-al-error="' + erreur[0] + '"]').hidden = false;
            erreur[1].classList.add('is-invalid');
        });
        if (erreurs.length) {
            event.preventDefault();
            erreurs[0][1].focus();
        }
    });

    // État initial (retour du serveur avec la saisie, ou ?etudiant_id= depuis le suivi).
    majEtudiant();
    majChambre();
    majMode();
})();
