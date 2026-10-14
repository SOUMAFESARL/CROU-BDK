
<div id="page-etat-lieux" class="content">

    <link rel="stylesheet" href="{{ asset('css/edl-page.css') }}?v=20261010">
    <style>
        :root{
            --paper:#F6F2EA;
            --card:#FFFFFF;
            --ink:#23271F;
            --ink-soft:#5B5F54;
            --line:#D8D2C3;
            --stamp:#B5481F;
            --ok:#3E6B4F;
            --warn:#B07A1E;
            --bad:#A6362C;
            --radius:10px;
        }
        *{box-sizing:border-box;}
        /* Styles limités à la section : un `body { padding }` décollait la barre du haut de la coque. */
        #page-etat-lieux{
            color:var(--ink);
            font-family:'Inter',sans-serif;
        }
        .sheet{max-width:880px;margin:0 auto;}
        header.top{
            display:flex;justify-content:space-between;align-items:flex-end;
            border-bottom:2px solid var(--ink);
            padding-bottom:18px;margin-bottom:18px;
            flex-wrap:wrap;gap:10px;
        }
        header.top h1{font-family:'Fraunces',serif;font-weight:600;font-size:30px;margin:0 0 4px;}
        header.top p{margin:0;color:var(--ink-soft);font-size:var(--app-font-min, 15px);letter-spacing:.04em;text-transform:uppercase;}
        .ref-stamp{
            border:1.5px solid var(--stamp);color:var(--stamp);
            font-family:'JetBrains Mono',monospace;font-size:var(--app-font-min, 15px);letter-spacing:.06em;
            padding:6px 10px;border-radius:6px;text-align:right;white-space:nowrap;
        }
        .mode-tabs{display:flex;gap:8px;margin-bottom:20px;}
        .mode-tab{
            flex:1;text-align:center;padding:12px 10px;border-radius:9px;
            border:1.5px solid var(--line);background:var(--card);cursor:pointer;
            font-weight:600;font-size:var(--app-font-min, 15px);color:var(--ink-soft);transition:.15s;
        }
        .mode-tab.active{background:var(--ink);color:#fff;border-color:var(--ink);}
        .mode-tab .sub{display:block;font-size:var(--app-font-min, 15px);font-weight:400;margin-top:2px;opacity:.8;text-transform:none;letter-spacing:0;}
        section.block{
            background:var(--card);border:1px solid var(--line);border-radius:var(--radius);
            padding:20px 22px;margin-bottom:18px;
        }
        h2.block-title{
            font-family:'Fraunces',serif;font-weight:600;font-size:18px;margin:0 0 14px;
            display:flex;align-items:center;gap:10px;
        }
        h2.block-title .idx{
            font-family:'JetBrains Mono',monospace;font-size:var(--app-font-min, 15px);color:#fff;background:var(--ink);
            border-radius:5px;padding:2px 7px;
        }
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;}
        label{display:block;font-size:var(--app-font-min, 15px);color:var(--ink-soft);margin-bottom:4px;font-weight:500;}
        input[type=text],input[type=date],input[type=number],textarea,select{
            width:100%;border:1px solid var(--line);border-radius:7px;padding:9px 10px;
            font-family:'Inter',sans-serif;font-size:var(--app-font-min, 15px);background:#FCFAF6;color:var(--ink);
        }
        textarea{resize:vertical;min-height:60px;}
        input:focus,textarea:focus,select:focus{outline:2px solid var(--stamp);outline-offset:1px;}
        .copy-entry-bar{
            display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;
            background:#FBF1E4;border:1px solid #E9D8B6;border-radius:9px;
            padding:10px 14px;margin-bottom:18px;font-size:var(--app-font-min, 15px);color:#7A5A1E;
        }
        .copy-entry-bar button{
            background:var(--ink);color:#fff;border:none;border-radius:7px;
            padding:8px 12px;font-size:var(--app-font-min, 15px);font-weight:600;cursor:pointer;white-space:nowrap;
        }
        .copy-entry-bar button:hover{background:#000;}
        .room{border:1px solid var(--line);border-radius:8px;padding:16px;margin-bottom:14px;background:#FCFAF6;}
        .room-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:10px;}
        .room-head input{font-family:'Fraunces',serif;font-size:16px;font-weight:600;border:none;background:transparent;padding:2px 0;border-bottom:1px dashed var(--line);}
        .remove-room{background:none;border:1px solid var(--line);border-radius:6px;color:var(--bad);font-size:var(--app-font-min, 15px);padding:5px 9px;cursor:pointer;}
        .remove-room:hover{background:#FBE8E6;}
        table.elements{width:100%;border-collapse:collapse;font-size:var(--app-font-min, 15px);}
        table.elements th{text-align:left;color:var(--ink-soft);font-weight:500;font-size:var(--app-font-min, 15px);text-transform:uppercase;letter-spacing:.03em;padding:6px 6px;border-bottom:1px solid var(--line);}
        table.elements td{padding:6px 6px;border-bottom:1px solid #ECE7DA;vertical-align:top;}
        table.elements td input[type=text]{padding:6px 8px;font-size:var(--app-font-min, 15px);}
        table.elements td input.comment-required-error{
            border-color:var(--bad);background:#FBE8E6;outline:none;
            box-shadow:0 0 0 2px rgba(166,54,44,.10);
        }
        .comment-error-message{display:block;color:var(--bad);font-size:var(--app-font-min, 15px);margin-top:4px;font-weight:600;}
        .etat-select{display:flex;gap:4px;}
        .etat-btn{
            flex:1;border:1px solid var(--line);border-radius:6px;background:#fff;
            font-size:var(--app-font-min, 15px);padding:6px 0;cursor:pointer;color:var(--ink-soft);font-weight:500;
        }
        .etat-btn[data-v="bon"].active{background:var(--ok);color:#fff;border-color:var(--ok);}
        .etat-btn[data-v="moyen"].active{background:var(--warn);color:#fff;border-color:var(--warn);}
        .etat-btn[data-v="mauvais"].active{background:var(--bad);color:#fff;border-color:var(--bad);}
        .entry-ref{font-size:var(--app-font-min, 15px);color:var(--ink-soft);margin-top:3px;}
        .entry-ref b{color:var(--ink);}
        .add-row,.add-room{
            background:none;border:1px dashed var(--ink-soft);border-radius:7px;color:var(--ink-soft);
            font-size:var(--app-font-min, 15px);padding:9px 12px;cursor:pointer;width:100%;margin-top:6px;
        }
        .add-row:hover,.add-room:hover{border-color:var(--ink);color:var(--ink);}
        .photo-zone{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;}
        .photo-thumb{position:relative;width:96px;height:96px;border-radius:7px;overflow:hidden;border:1px solid var(--line);}
        .photo-thumb img{width:100%;height:100%;object-fit:cover;display:block;}
        .photo-thumb button{
            position:absolute;top:3px;right:3px;width:20px;height:20px;border-radius:50%;
            border:none;background:rgba(0,0,0,.6);color:#fff;font-size:var(--app-font-min, 15px);cursor:pointer;line-height:1;
        }
        .photo-add{
            width:96px;height:96px;border:1.5px dashed var(--line);border-radius:7px;
            display:flex;align-items:center;justify-content:center;color:var(--ink-soft);
            font-size:24px;cursor:pointer;background:#FCFAF6;
        }
        .photo-add:hover{border-color:var(--ink);color:var(--ink);}
        input[type=file]{display:none;}
        .meters{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;}
        .sig-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
        .sig-box{text-align:center;}
        canvas.sig-pad{
            display:block;width:100%;height:140px;border:1px solid var(--line);border-radius:7px;
            background:#FCFAF6;touch-action:none;cursor:crosshair;
        }
        /* Signature existante enregistrée */
        .sig-existing{
            margin-bottom:8px;padding:6px;background:#F0EAD8;border:1px solid var(--line);
            border-radius:7px;font-size:var(--app-font-min, 15px);color:var(--ink-soft);text-align:center;
        }
        .sig-existing img{max-height:60px;display:block;margin:4px auto 0;}
        .sig-label{font-size:var(--app-font-min, 15px);font-weight:600;margin:10px 0 6px;}
        .sig-actions{display:flex;justify-content:center;gap:8px;margin-top:6px;}
        .sig-actions button{
            border:1px solid var(--line);background:#fff;border-radius:6px;font-size:var(--app-font-min, 15px);
            padding:5px 10px;cursor:pointer;color:var(--ink-soft);
        }
        .sig-actions button:hover{color:var(--ink);border-color:var(--ink);}
        .toolbar{
            position:sticky;bottom:0;display:flex;justify-content:center;gap:10px;
            padding:14px 0;margin-top:10px;flex-wrap:wrap;
        }
        .toolbar button{
            font-family:'Inter',sans-serif;font-weight:600;font-size:var(--app-font-min, 15px);
            border-radius:8px;padding:11px 20px;cursor:pointer;border:1px solid var(--ink);
        }
        #saveBtn{background:var(--ok);color:#fff;border-color:var(--ok);}
        #saveBtn:hover{background:#2d5039;}
        #resetBtn{background:#fff;color:var(--ink);}
        #resetBtn:hover{background:#F0EAdb;}
        .hint{font-size:var(--app-font-min, 15px);color:var(--ink-soft);margin-top:6px;}
        @media print{
            body{background:#fff;}
            .toolbar,.add-row,.add-room,.remove-room,.photo-add,.sig-actions,.mode-tabs,.copy-entry-bar{display:none !important;}
            section.block{break-inside:avoid;border:1px solid #ccc;}
            .room{break-inside:avoid;}
            .mode-panel{display:block !important;}
        }
    </style>

    @php
        $entree   = $data['etudiant']->edl_entree ?? null;
        $sortie   = $data['etudiant']->edl_sortie ?? null;
        $entreeId = $entree?->etat_des_lieux_id  ?? null;
        $sortieId = $sortie?->etat_des_lieux_id ?? null;

        $chambre        = $data['derniereAssignation']->chambre ?? null;
        $cite           = $chambre?->cite;
        $batiment       = $chambre?->batiment;
        $etudiant       = $data['etudiant'];

        $sharedFields = [
            'id_cite'     => $cite?->id_cite      ?? '',
            'id_batiment' => $batiment?->id_batiment ?? '',
            'id_chambre'  => $chambre?->id_chambre  ?? '',
            'id_etudiant' => $etudiant->id_etudiant ?? '',
        ];

        $defaultPieces = [
            ['name' => 'Chambre',       'elements' => []],
            ['name' => 'Cuisine',       'elements' => []],
            ['name' => 'Salle de bain', 'elements' => []],
        ];

        // Données injectées en JS pour le pré-remplissage
        $jsEntree = $entree ? [
            'id'          => $entreeId,
            'date'        => $entree->date_etat,
            'cles'        => $entree->nbre_cle_recus,
            'obs'         => $entree->observation_generale,
            'pieces'      => $entree->pieces ?? $defaultPieces,
            'sig_crou'    => $entree->signature_crou    ? asset('storage/' . $entree->signature_crou)    : null,
            'sig_etudiant'=> $entree->signature_etudiant? asset('storage/' . $entree->signature_etudiant): null,
        ] : null;

        $jsSortie = $sortie ? [
            'id'          => $sortieId,
            'date'        => $sortie->date_etat,
            'cles'        => $sortie->nbre_cle_recus,
            'obs'         => $sortie->observation_generale,
            'pieces'      => $sortie->pieces ?? $defaultPieces,
            'sig_crou'    => $sortie->signature_crou    ? asset('storage/' . $sortie->signature_crou)    : null,
            'sig_etudiant'=> $sortie->signature_etudiant? asset('storage/' . $sortie->signature_etudiant): null,
        ] : null;
    @endphp

    {{-- Formulaire unique ; action et méthode changent dynamiquement via JS --}}
    <form id="mainForm" method="POST" action="{{ route('etudiants.store_eld') }}">
        @csrf


        {{-- Spoofing PUT pour les mises à jour --}}
        <input type="hidden" name="_method"              id="inp-method"       value="POST">
        {{-- Champs cachés remplis par JS juste avant la soumission --}}
        <input type="hidden" name="type_etat"            id="inp-type_etat"            value="">
        <input type="hidden" name="id_cite"              id="inp-id_cite"              value="{{ $sharedFields['id_cite'] }}">
        <input type="hidden" name="id_batiment"          id="inp-id_batiment"          value="{{ $sharedFields['id_batiment'] }}">
        <input type="hidden" name="id_chambre"           id="inp-id_chambre"           value="{{ $sharedFields['id_chambre'] }}">
        <input type="hidden" name="id_etudiant"          id="inp-id_etudiant"          value="{{ $sharedFields['id_etudiant'] }}">
        <input type="hidden" name="date_etat"            id="inp-date_etat"            value="">
        <input type="hidden" name="nbre_cle_recus"       id="inp-nbre_cle_recus"       value="">
        <input type="hidden" name="observation_generale" id="inp-observation_generale" value="">
        <input type="hidden" name="pieces_json"          id="inp-pieces_json"          value="">
        <input type="hidden" name="signature_crou"       id="inp-signature_crou"       value="">
        <input type="hidden" name="signature_etudiant"   id="inp-signature_etudiant"   value="">

        <div class="sheet">

            <header class="top">
                <div>
                    <h1 id="mainTitle">État des lieux</h1>
                    <p>Annexe au contrat de location</p>
                </div>
                <div class="ref-stamp" id="refStamp">REF — À COMPLÉTER</div>
            </header>

            <div class="mode-tabs">
                <div class="mode-tab active" data-mode="entree">
                    Entrée
                    <span class="sub">État des lieux d'entrée</span>
                </div>
                <div class="mode-tab" data-mode="sortie" {{ $sortie === null ? 'hidden' : '' }}>
                    Sortie
                    <span class="sub">État des lieux de sortie</span>
                </div>
            </div>

            <div id="panel-sortie-bar" class="copy-entry-bar" style="display:none;">
                <span>Vous êtes en mode <b>Sortie</b>. Vous pouvez reprendre les pièces saisies à l'entrée pour comparer facilement chaque élément.</span>
                <button id="copyEntryBtn" type="button">Copier les pièces de l'entrée</button>
            </div>

            {{-- ======================== PANEL ENTRÉE ======================== --}}
            <div id="panel-entree" class="mode-panel">
                <section class="block">
                    <h2 class="block-title"><span class="idx">01</span>Informations générales</h2>
                    <div class="grid">
                        <div><label>Cité</label>
                            <input type="text" class="f-cite" readonly value="{{ $cite?->name ?? '' }}" placeholder="Nom cité"></div>
                        <div><label>Bâtiment</label>
                            <input type="text" class="f-batiment" readonly value="{{ $batiment?->name ?? '' }}" placeholder="Bâtiment"></div>
                        <div><label>Chambre</label>
                            <input type="text" class="f-chambre" readonly value="{{ $chambre?->numero_chambre ?? '' }}" placeholder="Chambre"></div>
                        <div><label>Étudiant</label>
                            <input type="text" class="f-etudiant" readonly value="{{ $etudiant->name ?? '' }}" placeholder="Étudiant"></div>
                        <div><label>Nombre de clés remises</label>
                            <input type="number" min="0" class="f-cles" value="{{ $entree?->nombre_cles_remises ?? 0 }}" placeholder="0"></div>
                        <div><label>Date de l'état des lieux</label>
                            <input type="date" class="f-date" value="{{ $entree?->date_etat ?? '' }}"></div>
                    </div>
                </section>

                <section class="block">
                    <h2 class="block-title"><span class="idx">02</span>État des pièces</h2>
                    <div class="rooms" id="rooms-entree"></div>
                    <button class="add-room" data-target="entree" type="button">+ Ajouter une pièce</button>
                </section>

                <section class="block">
                    <h2 class="block-title"><span class="idx">03</span>Observations générales</h2>
                    <textarea class="f-obs" placeholder="Remarques complémentaires, réserves, accessoires fournis..."></textarea>
                </section>

                <section class="block">
                    <h2 class="block-title"><span class="idx">04</span>Signatures</h2>
                    <p class="hint">Signer directement dans les cadres ci-dessous (souris, doigt ou stylet).
                        @if($entree?->signature_crou || $entree?->signature_etudiant)
                            Dessiner une nouvelle signature remplacera celle enregistrée.
                        @endif
                    </p>
                    <div class="sig-grid">
                        <div class="sig-box">
                            <div class="sig-label">Le CROU</div>
                            @if($entree?->signature_crou)
                                <div class="sig-existing">
                                    Signature enregistrée
                                    <img src="{{ asset('storage/' . $entree->signature_crou) }}" alt="Signature CROU entrée">
                                </div>
                            @endif
                            @unless($entree?->signature_crou)
                                <div class="sig-existing">En attente de la signature du CROU</div>
                            @endunless
                        </div>
                        <div class="sig-box">
                            <div class="sig-label">Étudiant</div>
                            @if($entree?->signature_etudiant)
                                <div class="sig-existing">
                                    Signature enregistrée
                                    <img src="{{ asset('storage/' . $entree->signature_etudiant) }}" alt="Signature Étudiant entrée">
                                </div>
                            @endif
                            <canvas class="sig-pad" id="sig-entree-locataire"></canvas>
                            <div class="sig-actions"><button data-clear="sig-entree-locataire" type="button">Effacer</button></div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ======================== PANEL SORTIE ======================== --}}
            <div id="panel-sortie" class="mode-panel" style="display:none;">
                <section class="block">
                    <h2 class="block-title"><span class="idx">01</span>Informations générales</h2>
                    <div class="grid">
                        <div><label>Cité</label>
                            <input type="text" class="f-cite" readonly value="{{ $cite?->name ?? '' }}" placeholder="Nom cité"></div>
                        <div><label>Bâtiment</label>
                            <input type="text" class="f-batiment" readonly value="{{ $batiment?->name ?? '' }}" placeholder="Bâtiment"></div>
                        <div><label>Chambre</label>
                            <input type="text" class="f-chambre" readonly value="{{ $chambre?->numero_chambre ?? '' }}" placeholder="Chambre"></div>
                        <div><label>Étudiant</label>
                            <input type="text" class="f-etudiant" readonly value="{{ $etudiant->name ?? '' }}" placeholder="Étudiant"></div>
                        <div><label>Nombre de clés rendues</label>
                            <input type="number" min="0" class="f-cles" placeholder="0"></div>
                        <div><label>Date de l'état des lieux</label>
                            <input type="date" class="f-date"></div>
                    </div>
                </section>

                <section class="block">
                    <h2 class="block-title"><span class="idx">02</span>État des pièces</h2>
                    <div class="rooms" id="rooms-sortie"></div>
                    <button class="add-room" data-target="sortie" type="button">+ Ajouter une pièce</button>
                </section>

                <section class="block">
                    <h2 class="block-title"><span class="idx">03</span>Observations générales</h2>
                    <textarea class="f-obs" placeholder="Remarques complémentaires, réserves, retenues sur dépôt de garantie..."></textarea>
                </section>

                <section class="block">
                    <h2 class="block-title"><span class="idx">04</span>Signatures</h2>
                    <p class="hint">Signer directement dans les cadres ci-dessous (souris, doigt ou stylet).
                        @if($sortie?->signature_crou || $sortie?->signature_etudiant)
                            Dessiner une nouvelle signature remplacera celle enregistrée.
                        @endif
                    </p>
                    <div class="sig-grid">
                        <div class="sig-box">
                            <div class="sig-label">Le CROU</div>
                            @if($sortie?->signature_crou)
                                <div class="sig-existing">
                                    Signature enregistrée
                                    <img src="{{ asset('storage/' . $sortie->signature_crou) }}" alt="Signature CROU sortie">
                                </div>
                            @endif
                            @unless($sortie?->signature_crou)
                                <div class="sig-existing">En attente de la signature du CROU</div>
                            @endunless
                        </div>
                        <div class="sig-box">
                            <div class="sig-label">Étudiant</div>
                            @if($sortie?->signature_etudiant)
                                <div class="sig-existing">
                                    Signature enregistrée
                                    <img src="{{ asset('storage/' . $sortie->signature_etudiant) }}" alt="Signature Étudiant sortie">
                                </div>
                            @endif
                            <canvas class="sig-pad" id="sig-sortie-locataire"></canvas>
                            <div class="sig-actions"><button data-clear="sig-sortie-locataire" type="button">Effacer</button></div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="toolbar">
{{--                <button id="resetBtn" type="button">Réinitialiser le formulaire actif</button>--}}
                <button id="saveBtn" type="button">Enregistrer l'état</button>
            </div>

        </div>{{-- .sheet --}}
    </form>

    <script src="{{ asset('js/edl-photos.js') }}?v=20261010"></script>
    <script>
        // ── Données PHP injectées ────────────────────────────────────────────────
        const STORE_URL      = "{{ route('etudiants.store_eld') }}";
        const existingEntree = @json($jsEntree);   // null ou objet avec id, date, pieces …
        const existingSortie = @json($jsSortie);
        const UPDATE_URL_TEMPLATE = "{{ route('etudiants.update_eld', ['id' => 'PLACEHOLDER_ID']) }}";

        const defaultElements = ["Sol","Murs","Plafond","Fenêtres","Porte","Électricité","Équipements"];
        const counters = { entree: 0, sortie: 0 };
        let currentMode = "entree";

        function toggleSaveButton(hidden) {
            const btn = document.getElementById("saveBtn");
            if (!btn) return;
            btn.style.display = hidden ? "none" : "inline-block";
            btn.disabled = hidden;
        }

        function updateSaveState(mode) {
            const existing = mode === "entree" ? existingEntree : existingSortie;
            const locked = mode === 'entree'
                ? !!existing?.sig_crou
                : !existing?.id || !!existing?.sig_etudiant;
            const btn = document.getElementById("saveBtn");

            toggleSaveButton(locked);
            if (!btn || locked) return;

            btn.textContent = mode === 'sortie'
                ? "Signer l'état des lieux de sortie"
                : (existing?.id ? "Modifier l'état des lieux d'entrée" : "Enregistrer l'état des lieux d'entrée");
        }

        function lockExitContent() {
            const panel = document.getElementById('panel-sortie');
            if (!panel) return;

            panel.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(field => {
                field.readOnly = true;
                if (field.type === 'file') field.disabled = true;
            });
            panel.querySelectorAll('.etat-btn, .add-room, .add-row, .remove-room, .photo-add').forEach(control => {
                control.disabled = true;
                control.style.pointerEvents = 'none';
                control.style.opacity = '.65';
            });
        }

        // ── Helpers rooms ────────────────────────────────────────────────────────
        function makeEtatSelect(activeVal){
            const vals = ["bon","moyen","mauvais"];
            return `<div class="etat-select">${vals.map(v =>
                `<button type="button" class="etat-btn${activeVal===v?' active':''}" data-v="${v}">${v.charAt(0).toUpperCase()+v.slice(1)}</button>`
            ).join("")}</div>`;
        }

        function wireEtatButtons(scope){
            scope.querySelectorAll(".etat-btn").forEach(btn => {
                btn.onclick = () => {
                    btn.parentElement.querySelectorAll(".etat-btn").forEach(b => b.classList.remove("active"));
                    btn.classList.add("active");
                };
            });
        }

        /**
         * addRoom(mode, name, savedData, refData)
         *  savedData : données de la pièce déjà enregistrée en BDD (pré-remplissage)
         *  refData   : données de la pièce d'entrée (affiché comme référence en mode sortie)
         */
        function addRoom(mode, name, savedData, refData){
            counters[mode]++;
            const div = document.createElement("div");
            div.className = "room";
            div.id = mode + "-room" + counters[mode];
            const isSortie = mode === "sortie";

            // Détermine la liste des éléments à afficher
            let rows = "";
            if (savedData && savedData.elements && savedData.elements.length > 0) {
                // Pré-remplissage depuis les données enregistrées
                rows = savedData.elements.map((el, i) => {
                    const ref = refData ? (refData.elements[i] || null) : null;
                    const refHtml = isSortie
                        ? `<div class="entry-ref">État entrée : <b>${ref ? (ref.etat||'—').toUpperCase() : '—'}</b></div>`
                        : "";
                    return `<tr data-el-index="${i}">
                        <td>
                            <input type="text" value="${escHtml(el.name_element || '')}">
                            ${refHtml}
                        </td>
                        <td>${makeEtatSelect(el.etat || "")}</td>
                        <td><input type="text" value="${escHtml(el.observation || '')}" placeholder="Détail, défaut constaté..."></td>
                    </tr>`;
                }).join("");
            } else {
                // Pièce vierge avec les éléments par défaut
                rows = defaultElements.map((el, i) => {
                    const ref = refData ? (refData.elements[i] || null) : null;
                    const refHtml = isSortie
                        ? `<div class="entry-ref">État entrée : <b>${ref ? (ref.etat||'—').toUpperCase() : '—'}</b></div>`
                        : "";
                    return `<tr data-el-index="${i}">
                        <td>
                            <input type="text" value="${escHtml(el)}">
                            ${refHtml}
                        </td>
                        <td>${makeEtatSelect("")}</td>
                        <td><input type="text" placeholder="Détail, défaut constaté..."></td>
                    </tr>`;
                }).join("");
            }

            div.innerHTML = `
                <div class="room-head">
                    <input type="text" value="${escHtml(name || 'Nouvelle pièce')}">
                    <button class="remove-room" type="button">Supprimer</button>
                </div>
                <table class="elements">
                    <thead><tr>
                        <th style="width:22%">Élément</th>
                        <th style="width:30%">État${isSortie ? " (sortie)" : ""}</th>
                        <th>Observations</th><th>Photo facultative</th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                </table>
                <button class="add-row" type="button">+ Ajouter un élément</button>
                <div class="photo-zone">
                    <label class="photo-add">+<input type="file" accept="image/*" capture="environment" multiple></label>
                </div>`;

            document.getElementById("rooms-" + mode).appendChild(div);

            div.querySelector(".remove-room").onclick = () => div.remove();
            wireEtatButtons(div);

            div.querySelector(".add-row").onclick = () => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td><input type="text" placeholder="Élément"></td>
                    <td>${makeEtatSelect("")}</td>
                    <td><input type="text" placeholder="Détail, défaut constaté..."></td>`;
                div.querySelector("tbody").appendChild(tr);
                wireEtatButtons(tr);
                EdlPhotos.mountElement(tr);
            };

            div.querySelectorAll('tbody tr').forEach((tr, index) => {
                EdlPhotos.mountElement(tr, savedData?.elements?.[index] || {}, mode === 'sortie' || !!existingEntree?.sig_crou);
            });
            div.querySelector('.photo-zone').remove();
            EdlPhotos.mount(div, savedData?.images || [], mode === 'sortie' || !!existingEntree?.sig_crou);

            return div;
        }

        function escHtml(str){
            return String(str).replace(/&/g,"&amp;").replace(/"/g,"&quot;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
        }

        // ── Lecture des pièces depuis le DOM ─────────────────────────────────────
        // function readRoomsData(mode){
        //     const rooms = [];
        //     document.querySelectorAll("#rooms-" + mode + " .room").forEach(roomEl => {
        //         const roomName = roomEl.querySelector(".room-head input").value;
        //         const els = [];
        //         roomEl.querySelectorAll("tbody tr").forEach(tr => {
        //             const inputs = tr.querySelectorAll("td input[type=text]");
        //             const name = inputs[0] ? inputs[0].value : "";
        //             const obs  = inputs[inputs.length - 1] ? inputs[inputs.length - 1].value : "";
        //             const active = tr.querySelector(".etat-btn.active");
        //             els.push({ name, etat: active ? active.dataset.v : "", obs });
        //         });
        //         rooms.push({ name: roomName, elements: els });
        //     });
        //     return rooms;
        // }

        function readRoomsData(mode){
            const rooms = [];
            document.querySelectorAll("#rooms-" + mode + " .room").forEach((roomEl, roomIndex) => {
                const roomName = roomEl.querySelector(".room-head input").value;
                const els = [];
                roomEl.querySelectorAll("tbody tr").forEach((tr, elIndex) => {
                    const inputs  = tr.querySelectorAll("td input[type=text]");
                    const active  = tr.querySelector(".etat-btn.active");
                    els.push({
                        name_element : inputs[0] ? inputs[0].value : "",
                        etat         : active ? active.dataset.v : "bon",   // jamais vide
                        observation  : inputs[inputs.length - 1] ? inputs[inputs.length - 1].value : "",
                        photo        : EdlPhotos.readElement(tr),
                        ordre        : elIndex + 1,
                    });
                });
                rooms.push({
                    name_piece : roomName,
                    ordre      : roomIndex + 1,
                    elements   : els,
                    photos     : EdlPhotos.read(roomEl),
                });
            });
            return rooms;
        }

        function validateBadElementComments(mode){
            let firstInvalid = null;

            document.querySelectorAll("#rooms-" + mode + " .room").forEach(roomEl => {
                const roomName = roomEl.querySelector(".room-head input")?.value || "Pièce";

                roomEl.querySelectorAll("tbody tr").forEach(tr => {
                    const inputs = tr.querySelectorAll('td input[type="text"]');
                    const elementName = inputs[0]?.value || "Élément";
                    const observation = inputs[inputs.length - 1];
                    const isBad = tr.querySelector('.etat-btn.active')?.dataset.v === 'mauvais';
                    const oldMessage = observation?.parentElement.querySelector('.comment-error-message');

                    observation?.classList.remove('comment-required-error');
                    oldMessage?.remove();

                    if (isBad && !observation?.value.trim()) {
                        observation.classList.add('comment-required-error');
                        const message = document.createElement('span');
                        message.className = 'comment-error-message';
                        message.textContent = `Commentaire obligatoire pour ${elementName}`;
                        observation.insertAdjacentElement('afterend', message);
                        firstInvalid ??= {input: observation, roomName, elementName};
                    }
                });
            });

            if (firstInvalid) {
                firstInvalid.input.scrollIntoView({behavior:'smooth', block:'center'});
                firstInvalid.input.focus();
                alert(`Veuillez ajouter un commentaire pour « ${firstInvalid.elementName} » dans « ${firstInvalid.roomName} », car son état est mauvais.`);
                return false;
            }

            return true;
        }

        // ── Initialisation des panneaux depuis les données PHP ───────────────────
        function initPanel(mode, existing){
            const container = document.getElementById("rooms-" + mode);
            container.innerHTML = "";
            counters[mode] = 0;

            if (existing && existing.pieces && existing.pieces.length > 0) {
                // Pré-remplissage depuis la BDD
                existing.pieces.forEach(room => addRoom(mode, room.name_piece, room, null));
            } else {
                // Pièces vierges par défaut
                ["Chambre", "Cuisine", "Salle de bain"].forEach(n => addRoom(mode, n, null, null));
            }

            // Champs simples
            const panel = document.getElementById("panel-" + mode);
            if (existing) {
                if (existing.date) panel.querySelector(".f-date").value = existing.date.slice(0, 10);
                if (existing.cles != null) panel.querySelector(".f-cles").value = existing.cles;
                if (existing.obs)  panel.querySelector(".f-obs").value  = existing.obs;
            } else {
                panel.querySelector(".f-date").valueAsDate = new Date();
            }
        }

        // ── Copier les pièces d'entrée vers sortie ───────────────────────────────
        function copyEntryToSortie(){
            const entryRooms = readRoomsData("entree");
            if (entryRooms.length === 0){
                alert("Aucune pièce n'a encore été saisie dans l'onglet Entrée.");
                return;
            }
            document.getElementById("rooms-sortie").innerHTML = "";
            counters.sortie = 0;
            entryRooms.forEach(r => addRoom("sortie", r.name_piece, null, r));
        }

        document.getElementById("copyEntryBtn").onclick = copyEntryToSortie;

        // ── Démarrage ────────────────────────────────────────────────────────────
        initPanel("entree", existingEntree);
        initPanel("sortie", existingSortie);
        lockExitContent();
        if (!existingEntree) document.querySelector("#panel-entree .f-date").valueAsDate = new Date();
        if (!existingSortie) document.querySelector("#panel-sortie .f-date").valueAsDate = new Date();
        updateSaveState(currentMode);

        document.querySelectorAll(".add-room").forEach(btn => {
            btn.onclick = () => addRoom(btn.dataset.target, "Nouvelle pièce", null, null);
        });

        // ── Ref stamp & titre ────────────────────────────────────────────────────
        function updateStamp(){
            const datestr = new Date().toISOString().slice(0,10).replace(/-/g,"");
            document.getElementById("refStamp").textContent =
                (currentMode === "entree" ? "EDL-ENTREE-" : "EDL-SORTIE-") + datestr;
            document.getElementById("mainTitle").textContent =
                currentMode === "entree" ? "État des lieux d'entrée" : "État des lieux de sortie";
        }
        updateStamp();

        // ── Onglets Entrée / Sortie ───────────────────────────────────────────────
        document.querySelectorAll(".mode-tab").forEach(tab => {
            tab.onclick = () => {
                const mode = tab.dataset.mode;
                if (mode === currentMode) return;
                currentMode = mode;
                document.querySelectorAll(".mode-tab").forEach(t => t.classList.toggle("active", t.dataset.mode === mode));
                document.getElementById("panel-entree").style.display   = mode === "entree" ? "block" : "none";
                document.getElementById("panel-sortie").style.display   = mode === "sortie" ? "block" : "none";
                document.getElementById("panel-sortie-bar").style.display = mode === "sortie" ? "flex"  : "none";
                updateStamp();
                updateSaveState(mode);
                requestAnimationFrame(resizeVisibleSignaturePads);
            };
        });

        // ── Signature pads ────────────────────────────────────────────────────────
        const pads = {};

        function setupSignaturePad(canvas){
            const ctx = canvas.getContext("2d");
            let strokes = [];
            let hasDrawn = false;

            function resize(){
                const ratio  = window.devicePixelRatio || 1;
                const rect   = canvas.getBoundingClientRect();
                const cssW   = Math.max(rect.width,  1);
                const cssH   = Math.max(rect.height, canvas.offsetHeight || 140);
                canvas.width  = cssW * ratio;
                canvas.height = cssH * ratio;
                ctx.setTransform(1,0,0,1,0,0);
                ctx.scale(ratio, ratio);
                ctx.lineWidth   = 2;
                ctx.lineCap     = "round";
                ctx.lineJoin    = "round";
                ctx.strokeStyle = "#23271F";
                redraw();
            }

            function redraw(){
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                strokes.forEach(stroke => {
                    if (stroke.length < 2) return;
                    ctx.beginPath();
                    ctx.moveTo(stroke[0].x, stroke[0].y);
                    for (let i = 1; i < stroke.length; i++) ctx.lineTo(stroke[i].x, stroke[i].y);
                    ctx.stroke();
                });
            }

            let drawing = false, currentStroke = null;

            function pos(e){
                const rect = canvas.getBoundingClientRect();
                const t    = e.touches ? e.touches[0] : e;
                return { x: t.clientX - rect.left, y: t.clientY - rect.top };
            }
            function start(e){ drawing = true; currentStroke = []; strokes.push(currentStroke); const p = pos(e); currentStroke.push(p); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
            function move(e){ if (!drawing) return; const p = pos(e); currentStroke.push(p); ctx.lineTo(p.x, p.y); ctx.stroke(); hasDrawn = true; e.preventDefault(); }
            function end(){ drawing = false; currentStroke = null; }

            canvas.addEventListener("mousedown",  start);
            canvas.addEventListener("mousemove",  move);
            window.addEventListener("mouseup",    end);
            canvas.addEventListener("touchstart", start, { passive: false });
            canvas.addEventListener("touchmove",  move,  { passive: false });
            canvas.addEventListener("touchend",   end);

            resize();
            requestAnimationFrame(resize);

            return {
                resize,
                clear: () => { strokes = []; hasDrawn = false; ctx.clearRect(0, 0, canvas.width, canvas.height); },
                // Retourne le base64 uniquement si l'utilisateur a dessiné quelque chose
                exportIfDrawn: () => hasDrawn ? canvas.toDataURL("image/png") : null,
            };
        }

        document.querySelectorAll(".sig-pad").forEach(c => pads[c.id] = setupSignaturePad(c));

        function resizeVisibleSignaturePads(){
            document.querySelectorAll(".mode-panel").forEach(panel => {
                if (panel.style.display === "none") return;
                panel.querySelectorAll(".sig-pad").forEach(c => pads[c.id] && pads[c.id].resize());
            });
        }

        window.addEventListener("resize", resizeVisibleSignaturePads);
        requestAnimationFrame(resizeVisibleSignaturePads);

        document.querySelectorAll("[data-clear]").forEach(btn => {
            btn.onclick = () => pads[btn.dataset.clear].clear();
        });

        // ── Soumission du formulaire ──────────────────────────────────────────────
        document.getElementById("saveBtn").onclick = () => {
            if (!EdlPhotos.ready()) return;
            const mode   = currentMode;
            const panel  = document.getElementById("panel-" + mode);
            const form   = document.getElementById("mainForm");
            const exists = mode === "entree" ? existingEntree : existingSortie;

            if (mode === 'entree' && !validateBadElementComments(mode)) return;

            // Type et pièces
            document.getElementById("inp-type_etat").value            = mode;
            document.getElementById("inp-date_etat").value            = panel.querySelector(".f-date").value;
            document.getElementById("inp-nbre_cle_recus").value       = panel.querySelector(".f-cles").value;
            document.getElementById("inp-observation_generale").value = panel.querySelector(".f-obs").value;
            document.getElementById("inp-pieces_json").value          = mode === 'entree' ? JSON.stringify(readRoomsData(mode)) : "";

            // Signatures : n'envoyer que si un nouveau dessin a été fait
            const sigEtu  = pads["sig-" + mode + "-locataire"].exportIfDrawn();
            document.getElementById("inp-signature_crou").value      = "";
            document.getElementById("inp-signature_etudiant").value  = sigEtu   || "";

            // Action et méthode HTTP
            if (exists && exists.id) {
                // Mise à jour d'un état existant
                form.action = UPDATE_URL_TEMPLATE.replace('PLACEHOLDER_ID', exists.id);
                document.getElementById("inp-method").value = "PUT";
            } else {
                // Création
                form.action = STORE_URL;
                document.getElementById("inp-method").value = "POST";
            }

            form.submit();
        };

        // ── Réinitialisation ──────────────────────────────────────────────────────
        const resetBtn = document.getElementById("resetBtn");
        if (resetBtn) resetBtn.onclick = () => {
            const label = currentMode === "entree" ? "d'entrée" : "de sortie";
            if (!confirm(`Réinitialiser le formulaire ${label} ? Cette action est irréversible.`)) return;

            const existing = currentMode === "entree" ? existingEntree : existingSortie;
            initPanel(currentMode, existing);

            if (pads["sig-" + currentMode + "-bailleur"]) pads["sig-" + currentMode + "-bailleur"].clear();
            pads["sig-" + currentMode + "-locataire"].clear();
        };
    </script>

</div>
