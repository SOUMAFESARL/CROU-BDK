


        <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrat de location - {{ $contrat->reference ?? 'Chambre' }}</title>
    <style>
        /* ============================================================
           STYLES POUR LE PDF - DomPDF compatible
           ============================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #1a1a1a;
            padding: 30px 40px;
            background: #ffffff;
        }

        .page {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* ============================================================
           EN-TÊTE
           ============================================================ */
        .header {
            text-align: center;
            border-bottom: 3px double #1a237e;
            padding-bottom: 20px;
            margin-top: -100px;
            position: relative;

        }

        .header .logo-text {
            font-size: 22px;
            font-weight: 800;
            color: #1a237e;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .header .logo-text small {
            font-weight: 300;
            font-size: 12px;
            color: #555;
            letter-spacing: 1px;
            display: block;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 700;
            color: #1a237e;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 8px 0 4px 0;
        }

        .header .subtitle {
            font-size: 12px;
            color: #666;
            font-style: italic;
        }

        .header .ref-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #1a237e;
            color: white;
            padding: 4px 14px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* ============================================================
           TITRES DE SECTIONS
           ============================================================ */
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #1a237e;
            margin: 20px 0 12px 0;
            padding-bottom: 6px;
            border-bottom: 2px solid #e8eaf6;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title .badge {
            background: #1a237e;
            color: white;
            font-size: 10px;
            padding: 1px 10px;
            border-radius: 10px;
            font-weight: 500;
        }

        /* ============================================================
           TABLEAU RÉCAPITULATIF
           ============================================================ */
        .summary-grid {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            border-left: 4px solid #1a237e;
            margin-bottom: 20px;
        }

        .summary-row {
            display: table;
            width: 100%;
            padding: 4px 0;
            border-bottom: 1px dashed #e0e0e0;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-row .label {
            display: table-cell;
            width: 40%;
            font-weight: 500;
            color: #555;
            font-size: 11px;
            padding: 3px 0;
        }

        .summary-row .value {
            display: table-cell;
            width: 60%;
            font-weight: 600;
            color: #1a1a1a;
            font-size: 11px;
            padding: 3px 0;
        }

        .summary-row .value.highlight {
            color: #1a237e;
        }

        /* ============================================================
           ARTICLES
           ============================================================ */
        .article {
            margin-bottom: 16px;
        }

        .article-header {
            margin-bottom: 4px;
        }

        .article-number {
            display: inline-block;
            background: #1a237e;
            color: white;
            font-weight: 700;
            font-size: 10px;
            padding: 1px 10px;
            border-radius: 3px;
            margin-right: 6px;
        }

        .article-title {
            font-weight: 700;
            font-size: 13px;
            color: #1a237e;
        }

        .article-content {
            padding-left: 28px;
            font-size: 11.5px;
            line-height: 1.7;
            color: #1a1a1a;
        }

        .article-content ul,
        .article-content ol {
            padding-left: 20px;
            margin: 5px 0;
        }

        .article-content ul li,
        .article-content ol li {
            margin-bottom: 3px;
        }

        .article-content .highlight {
            background: #fff3cd;
            padding: 0 4px;
            border-radius: 2px;
            font-weight: 600;
        }

        /* ============================================================
           RÈGLEMENT INTÉRIEUR
           ============================================================ */
        .rules-grid {
            display: table;
            width: 100%;
            margin-top: 8px;
            border-collapse: collapse;
        }

        .rules-grid .rule-row {
            display: table-row;
        }

        .rules-grid .rule-cell {
            display: table-cell;
            padding: 6px 12px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }

        .rules-grid .rule-label {
            font-weight: 600;
            font-size: 11px;
            color: #1a237e;
            width: 30%;
        }

        .rules-grid .rule-value {
            font-size: 11px;
            color: #1a1a1a;
            width: 70%;
        }

        .rule-subsection {
            margin-top: 10px;
            padding-left: 10px;
            border-left: 2px solid #e8eaf6;
        }

        .rule-subsection-title {
            font-weight: 600;
            font-size: 12px;
            color: #1a237e;
            margin-bottom: 4px;
        }

        .rule-list {
            padding-left: 20px;
            margin: 4px 0 8px 0;
        }

        .rule-list li {
            margin-bottom: 3px;
            font-size: 11px;
        }

        /* ============================================================
           SIGNATURES
           ============================================================ */
        .signature-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e8eaf6;
        }

        .signature-grid {
            display: table;
            width: 100%;
            margin-top: 20px;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            padding: 0 20px;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-box .label {
            font-weight: 700;
            font-size: 12px;
            color: #1a237e;
            margin-bottom: 5px;
        }

        .signature-box .line {
            border-bottom: 1px solid #1a1a1a;
            width: 80%;
            margin: 10px auto 5px auto;
            height: 35px;
        }

        .signature-box .note {
            font-size: 9px;
            color: #777;
            margin-top: 4px;
        }

        .signature-box .date-line {
            margin-top: 15px;
            font-size: 10px;
            color: #555;
        }

        /* ============================================================
           PIED DE PAGE
           ============================================================ */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #888;
        }

        /* ============================================================
           UTILITAIRES
           ============================================================ */
        .text-center { text-align: center; }
        .mt-10 { margin-top: 10px; }
        .mt-15 { margin-top: 15px; }
        .mt-20 { margin-top: 20px; }
        .mb-10 { margin-bottom: 10px; }
        .mb-15 { margin-bottom: 15px; }
        .page-break { page-break-before: always; }

        .field-placeholder {
            color: #1a237e;
            background: #f0f4ff;
            padding: 0 4px;
            border-radius: 2px;
            font-weight: 600;
        }

        .empty-field {
            color: #c62828;
            font-style: italic;
            font-size: 10px;
        }

        /* ============================================================
           IMPRESSION
           ============================================================ */
        @media print {
            body {
                padding: 20px 30px;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
        }

        /* ============================================================
           RESPONSIVE POUR L'ÉCRAN (optionnel)
           ============================================================ */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            .summary-grid {
                padding: 10px 15px;
            }
            .signature-box {
                display: block;
                width: 100%;
                margin-bottom: 25px;
            }
            .signature-grid {
                display: block;
            }
            .rules-grid .rule-cell {
                display: block;
                width: 100%;
            }
            .rules-grid .rule-row {
                display: block;
                margin-bottom: 8px;
            }
            .header-left {
                flex-direction: column;
                justify-content: center;
            }
        }
        .header-left {
            flex: 1;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo-img {
            max-height: 70px;
            max-width: 120px;
            height: auto;
            width: auto;
            display: block;
        }
    </style>
</head>
<body>
<div class="page">

    <!-- ============================================================
    EN-TÊTE
    ============================================================ -->
    <div class="header-left">
        @if(systemConfig('logo'))
            <img src="{{ systemLogoDocument() }}" alt="Logo" class="logo-img">
        @endif

    </div>
    <div class="header">
        <div class="logo-text">
            {{ systemConfig('app_name', config('app.name', 'CROU')) }}
            <small>Services Universitaires</small>
        </div>
        <h1>Contrat de location de chambre</h1>
        <div class="subtitle">Modèle standard — À compléter avant signature</div>
{{--        <div class="ref-badge">RÉF: {{ $contrat->reference ?? 'CONTRAT-' . date('Ymd') }}</div>--}}
    </div>

    <!-- ============================================================
    PRÉAMBULE
    ============================================================ -->
    <p style="margin-bottom: 1px;">
        Entre les soussignés :
    </p>

    <p style="margin-bottom: 1px;">
        <strong>Le Bailleur</strong>,
        <span class="field-placeholder">{{ $bailleur_nom ?? $config->app_name ?? '[NOM DU BAILLEUR]' }}</span>,
        représenté par <span class="field-placeholder">{{ $bailleur_representant ?? '[REPRÉSENTANT]' }}</span>,
        demeurant dans la région de
        <span class="field-placeholder">{{ $config->region->name ?? '[RÉGION]' }}</span>,
        précisément dans la ville de
        <span class="field-placeholder">{{ $config->ville->name ?? '[VILLE]' }}</span>,
        ci-après dénommé(e) <strong>« le Bailleur »</strong>,
    </p>

    <p style="margin-bottom: 10px;">
        <strong>Et</strong>
    </p>

    <p style="margin-bottom: 15px;">
        <strong>L'etudiant</strong>,
        <span class="field-placeholder">{{ $locataire_nom ?? '[NOM ET PRÉNOM DU LOCATAIRE]' }}</span>,
        né(e) le <span class="field-placeholder">{{ $locataire_naissance ?? '[DATE DE NAISSANCE]' }}</span>,
        matricule <span class="field-placeholder">{{ $locataire_matricule ?? '[MATRICULE]' }}</span>,
        demeurant actuellement a la <span class="field-placeholder">{{ $locataire_adresse_actuelle ?? '[ADRESSE ACTUELLE]' }}</span>
        dans le batiment <span class="field-placeholder">{{ $batiment_designation ?? '[BATIMENT ACTUELLE]' }}</span>,
        ci-après dénommé(e) <strong>« le Locataire »</strong>,
    </p>

    <p style="margin-top: 15px; font-weight: 600;">
        Il a été convenu ce qui suit :
    </p>

    <!-- ============================================================
    RÉCAPITULATIF DES CONDITIONS
    ============================================================ -->
    <div class="section-title">
        Récapitulatif des conditions
        <span class="badge">RÉSUMÉ</span>
    </div>

    <div class="summary-grid">
        <div class="summary-row">
            <span class="label">Locataire</span>
            <span class="value highlight">{{ $locataire_nom ?? '[NOM ET PRÉNOM DU LOCATAIRE]' }}</span>
        </div>
        <div class="summary-row">
            <span class="label">Chambre louée</span>
            <span class="value highlight">{{ $chambre_designation ?? '[NUMÉRO / DÉSIGNATION]' }}</span>
        </div>
        <div class="summary-row">
            <span class="label">Surface</span>
            <span class="value">{{ $surface ?? '[SURFACE EN M²]' }}</span>
        </div>
        <div class="summary-row">
            <span class="label">Adresse du logement</span>
            <span class="value">{{ $adresse_logement ?? '[ADRESSE COMPLÈTE]' }}</span>
        </div>
        <div class="summary-row">
            <span class="label">Date d'entrée</span>
            <span class="value">{{ $date_entree ?? '[DATE D\'ENTRÉE]' }}</span>
        </div>
        <div class="summary-row">
            <span class="label">Durée du contrat</span>
            <span class="value">{{ $duree ?? '[DURÉE]' }}</span>
        </div>
        <div class="summary-row">
            <span class="label">Loyer mensuel</span>
            <span class="value highlight">{{ $loyer_mensuel ?? '[MONTANT]' }}</span>
        </div>
        <div class="summary-row">
            <span class="label">Charges comprises</span>
            <span class="value">{{ $charges_comprises ?? '[OUI / NON - DÉTAIL]' }}</span>
        </div>
        <div class="summary-row">
            <span class="label">Dépôt de garantie</span>
            <span class="value highlight">{{ $depot_garantie ?? '[MONTANT]' }}</span>
        </div>
        <div class="summary-row">
            <span class="label">Jour de paiement</span>
            <span class="value">{{ $jour_paiement ?? '[EX. LE 5 DE CHAQUE MOIS]' }}</span>
        </div>
    </div>

    <!-- ============================================================
    ARTICLE 1
    ============================================================ -->
    <div class="article">
        <div class="article-header">
            <span class="article-number">1</span>
            <span class="article-title">Objet du contrat</span>
        </div>
        <div class="article-content">
            <p>
                Le Bailleur loue au Locataire, qui accepte, une chambre
                <span class="highlight">{{ $chambre_type ?? 'meublée' }}</span>
                désignée <span class="highlight">{{ $chambre_designation ?? '[NUMÉRO / DÉSIGNATION]' }}</span>,
                d'une surface d'environ <span class="highlight">{{ $surface ?? '[SURFACE]' }}</span> m²,
                située à l'adresse <span class="highlight">{{ $adresse_logement ?? '[ADRESSE COMPLÈTE]' }}</span>,
                ainsi que l'usage des parties communes (cuisine, salle de bain, salon) partagées avec les autres occupants.
            </p>
        </div>
    </div>

    <!-- ============================================================
    ARTICLE 2
    ============================================================ -->
    <div class="article">
        <div class="article-header">
            <span class="article-number">2</span>
            <span class="article-title">Durée du contrat</span>
        </div>
        <div class="article-content">
            <p>
                Le présent contrat est conclu pour une durée de
                <span class="highlight">{{ $duree ?? '[DURÉE]' }}</span>,
                à compter du <span class="highlight">{{ $date_entree ?? '[DATE D\'ENTRÉE]' }}</span>.
                Il pourra être renouvelé par accord exprès des deux parties ou résilié dans les conditions prévues à l'article 8.
            </p>
        </div>
    </div>

    <!-- ============================================================
    ARTICLE 3
    ============================================================ -->
    <div class="article">
        <div class="article-header">
            <span class="article-number">3</span>
            <span class="article-title">Loyer et charges</span>
        </div>
        <div class="article-content">
            <ol>
                <li>
                    Le Locataire s'engage à verser un loyer mensuel de
                    <span class="highlight">{{ $loyer_mensuel ?? '[MONTANT]' }}</span>,
                    payable d'avance le <span class="highlight">{{ $jour_paiement ?? '[JOUR]' }}</span>
                    de chaque mois, par <span class="highlight">{{ $mode_paiement ?? '[MODE DE PAIEMENT]' }}</span>.
                </li>
                <li>
                    Les charges suivantes sont <span class="highlight">{{ $charges_incluses ?? 'incluses' }}</span>
                    dans le loyer : <span class="highlight">{{ $detail_charges ?? '[DÉTAIL DES CHARGES]' }}</span>.
                </li>
                <li>
                    Tout retard de paiement supérieur à <span class="highlight">{{ $delai_retard ?? '15' }}</span> jours
                    pourra entraîner l'application de pénalités ou la résiliation du contrat.
                </li>
            </ol>
        </div>
    </div>

    <!-- ============================================================
    ARTICLE 4
    ============================================================ -->
    <div class="article">
        <div class="article-header">
            <span class="article-number">4</span>
            <span class="article-title">Dépôt de garantie</span>
        </div>
        <div class="article-content">
            <p>
                À la signature du présent contrat, le Locataire verse un dépôt de garantie d'un montant de
                <span class="highlight">{{ $depot_garantie ?? '[MONTANT]' }}</span>,
                destiné à couvrir d'éventuels manquements aux obligations locatives.
                Ce dépôt sera restitué dans un délai de <span class="highlight">{{ $delai_restituion ?? '30' }}</span> jours
                après la restitution des lieux, déduction faite des sommes dues le cas échéant.
            </p>
        </div>
    </div>

    <!-- ============================================================
    ARTICLE 5
    ============================================================ -->
    <div class="article">
        <div class="article-header">
            <span class="article-number">5</span>
            <span class="article-title">Obligations du Locataire</span>
        </div>
        <div class="article-content">
            <ul>
                <li>Occuper les lieux paisiblement et y habiter personnellement ;</li>
                <li>Payer le loyer et les charges aux échéances convenues ;</li>
                <li>Entretenir la chambre et signaler sans délai toute dégradation ou panne ;</li>
                <li>Respecter le règlement intérieur annexé au présent contrat (Article 7) ;</li>
                <li>Ne pas sous-louer ni céder le contrat sans accord écrit du Bailleur.</li>
            </ul>
        </div>
    </div>

    <!-- ============================================================
    ARTICLE 6
    ============================================================ -->
    <div class="article">
        <div class="article-header">
            <span class="article-number">6</span>
            <span class="article-title">Obligations du Bailleur</span>
        </div>
        <div class="article-content">
            <ul>
                <li>Délivrer une chambre décente, en bon état d'usage et de réparation ;</li>
                <li>Assurer la jouissance paisible des lieux loués ;</li>
                <li>Effectuer les réparations autres que locatives ;</li>
                <li>Restituer le dépôt de garantie dans les conditions prévues à l'article 4.</li>
            </ul>
        </div>
    </div>

    <!-- ============================================================
    ARTICLE 7 - RÈGLEMENT INTÉRIEUR
    ============================================================ -->
    <div class="article">
        <div class="article-header">
            <span class="article-number">7</span>
            <span class="article-title">Règlement intérieur (annexe)</span>
        </div>
        <div class="article-content">
            <p style="margin-bottom: 8px;">
                Le présent règlement fait partie intégrante du contrat. Le non-respect de ses dispositions peut constituer un motif de résiliation.
            </p>

            <!-- 7.1 Vie collective -->
            <div class="rule-subsection">
                <div class="rule-subsection-title">7.1 Vie collective et nuisances</div>
                <ul class="rule-list">
                    <li>Respecter le calme entre <span class="highlight">{{ $heure_silence_debut ?? '22h' }}</span>
                        et <span class="highlight">{{ $heure_silence_fin ?? '7h' }}</span> ;</li>
                    <li>Limiter le bruit (musique, appareils, visites) en toutes circonstances ;</li>
                    <li>Maintenir la propreté des parties communes après chaque usage.</li>
                </ul>
            </div>

            <!-- 7.2 Visites et invités -->
            <div class="rule-subsection">
                <div class="rule-subsection-title">7.2 Visites et invités</div>
                <ul class="rule-list">
                    <li>Les visites sont autorisées de <span class="highlight">{{ $plage_visites ?? '8h à 22h' }}</span>
                        sous réserve d'en informer le Bailleur ;</li>
                    <li>Les nuitées d'invités sont limitées à <span class="highlight">{{ $nb_nuitees ?? '3' }}</span>
                        par mois, sauf accord contraire.</li>
                </ul>
            </div>

            <!-- 7.3 Usage des équipements -->
            <div class="rule-subsection">
                <div class="rule-subsection-title">7.3 Usage des équipements</div>
                <ul class="rule-list">
                    <li>Usage de la cuisine, du réfrigérateur et des équipements communs :
                        <span class="highlight">{{ $modalites_equipements ?? '[MODALITÉS]' }}</span> ;</li>
                    <li>Connexion internet/Wi-Fi :
                        <span class="highlight">{{ $conditions_internet ?? '[CONDITIONS]' }}</span> ;</li>
                    <li>Interdiction de fumer dans les lieux :
                        <span class="highlight">{{ $fumeur_autorise ?? 'NON, sauf zones autorisées' }}</span>.</li>
                </ul>
            </div>

            <!-- 7.4 Sécurité -->
            <div class="rule-subsection">
                <div class="rule-subsection-title">7.4 Sécurité</div>
                <ul class="rule-list">
                    <li>Ne pas dupliquer les clés sans autorisation du Bailleur ;</li>
                    <li>Signaler immédiatement toute anomalie (fuite, panne électrique, intrusion) ;</li>
                    <li>Respecter les consignes de sécurité incendie affichées dans le logement.</li>
                </ul>
            </div>

            <!-- 7.5 Animaux -->
            <div class="rule-subsection">
                <div class="rule-subsection-title">7.5 Animaux</div>
                <p style="font-size: 11px;">
                    Les animaux domestiques sont <span class="highlight">{{ $animaux_autorises ?? 'NON AUTORISÉS' }}</span>,
                    sous réserve, le cas échéant, des conditions suivantes :
                    <span class="highlight">{{ $conditions_animaux ?? '[CONDITIONS PARTICULIÈRES]' }}</span>.
                </p>
            </div>

            <!-- 7.6 Dispositions diverses -->
            <div class="rule-subsection">
                <div class="rule-subsection-title">7.6 Dispositions diverses</div>
                <p style="font-size: 11px;">
                    <span class="highlight">{{ $dispositions_diverses ?? '[RÈGLES SPÉCIFIQUES : PARKING, BUANDERIE, POUBELLES, ETC.]' }}</span>
                </p>
            </div>
        </div>
    </div>

    <!-- ============================================================
    ARTICLE 8
    ============================================================ -->
    <div class="article">
        <div class="article-header">
            <span class="article-number">8</span>
            <span class="article-title">Résiliation</span>
        </div>
        <div class="article-content">
            <ol>
                <li>
                    Le Locataire peut résilier le contrat à tout moment moyennant un préavis de
                    <span class="highlight">{{ $preavis_locataire ?? '1 mois' }}</span>,
                    notifié par <span class="highlight">{{ $mode_notification ?? 'lettre recommandée' }}</span>.
                </li>
                <li>
                    Le Bailleur peut résilier le contrat en cas de manquement grave (impayés, troubles de voisinage,
                    non-respect du règlement intérieur) après mise en demeure restée sans effet pendant
                    <span class="highlight">{{ $delai_mise_demeure ?? '15 jours' }}</span>.
                </li>
            </ol>
        </div>
    </div>

    <!-- ============================================================
    ARTICLE 9
    ============================================================ -->
    <div class="article">
        <div class="article-header">
            <span class="article-number">9</span>
            <span class="article-title">Litiges</span>
        </div>
        <div class="article-content">
            <p>
                En cas de différend, les parties s'efforceront de trouver une solution amiable.
                À défaut, le litige sera porté devant
                <span class="highlight">{{ $juridiction ?? 'le tribunal compétent de [VILLE]' }}</span>.
            </p>
        </div>
    </div>

    <!-- ============================================================
    ARTICLE 10
    ============================================================ -->
    <div class="article">
        <div class="article-header">
            <span class="article-number">10</span>
            <span class="article-title">Dispositions complémentaires</span>
        </div>
        <div class="article-content">
            <p>
                <span class="highlight">{{ $dispositions_complementaires ?? '[CLAUSES SUPPLÉMENTAIRES NÉGOCIÉES ENTRE LES PARTIES]' }}</span>
            </p>
        </div>
    </div>

    <!-- ============================================================
    SIGNATURES
    ============================================================ -->
    <div class="signature-section">
        <p style="text-align: center; margin-bottom: 15px;">
            Fait à <span class="highlight">{{ $lieu_signature ?? '[LIEU]' }}</span>,
            le <span class="highlight">{{ $date_signature ?? date('d/m/Y') }}</span>,
            en deux exemplaires originaux.
        </p>

        <div class="signature-grid">
            <div class="signature-box">
                <div class="label">Le Crou</div>
                <div class="line">&nbsp;</div>
                <div class="note">(nom, signature et cachet)</div>
                <div class="date-line">Lu et approuvé</div>
            </div>
            <div class="signature-box">
                <div class="label">L'étudiant</div>
                <div class="line">&nbsp;</div>
                <div class="note">(nom et signature)</div>
                <div class="date-line">Lu et approuvé, précédé de la mention « Bon pour accord »</div>
            </div>
        </div>
    </div>

    <!-- ============================================================
    PIED DE PAGE
    ============================================================ -->
    <div class="footer">
        Contrat de location de chambre — {{ systemConfig('app_name', config('app.name', 'CROU')) }} —
        Généré le {{ $date_signature }}
        <br>
        <span style="font-size: 8px; color: #aaa;">
            Document confidentiel — Ne pas diffuser
        </span>
    </div>

</div>
</body>
</html>
