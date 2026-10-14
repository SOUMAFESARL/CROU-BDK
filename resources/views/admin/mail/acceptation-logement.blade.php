<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Confirmation d'Éligibilité – Étudiant</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: #0b1220;
            font-family: 'DM Sans', sans-serif;
            padding: 40px 16px 60px;
        }

        .email-wrapper {
            max-width: 620px;
            margin: 0 auto;
        }

        /* ── HEADER BAND ── */
        .header {
            background: linear-gradient(135deg, #1a2f5e 0%, #0e1e42 100%);
            border-radius: 20px 20px 0 0;
            padding: 40px 48px 32px;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.08);
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -40px; left: -40px;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.05);
        }

        .logo-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 36px;
        }

        .logo-emblem {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, #d4af37, #f5d060);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            font-weight: 700;
            color: #0b1220;
            letter-spacing: -1px;
            flex-shrink: 0;
        }

        .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 17px;
            font-weight: 600;
            color: #ffffff;
            line-height: 1.2;
        }
        .logo-text span {
            display: block;
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            font-weight: 300;
            color: #8fa3c8;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(212, 175, 55, 0.12);
            border: 1px solid rgba(212, 175, 55, 0.35);
            border-radius: 100px;
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 500;
            color: #d4af37;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .badge-status::before {
            content: '';
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #d4af37;
        }

        .header-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 10px;
        }
        .header-title em {
            font-style: normal;
            color: #d4af37;
        }

        .header-sub {
            font-size: 14px;
            color: #7a95be;
            font-weight: 300;
            line-height: 1.5;
        }

        /* ── DECORATIVE DIVIDER ── */
        .divider-strip {
            height: 5px;
            background: linear-gradient(90deg, #d4af37 0%, #f5d060 50%, #c09a28 100%);
        }

        /* ── MAIN BODY ── */
        .body-card {
            background: #ffffff;
            padding: 48px 48px 36px;
        }

        .greeting {
            font-size: 16px;
            color: #1a2f5e;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .greeting strong {
            font-weight: 600;
        }

        .intro-text {
            font-size: 15px;
            color: #4a5568;
            line-height: 1.75;
            margin-bottom: 36px;
        }
        .intro-text strong {
            color: #1a2f5e;
            font-weight: 500;
        }

        /* ── INFO GRID ── */
        .info-section-label {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #9ba8bc;
            margin-bottom: 16px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 32px;
        }

        .info-cell {
            background: #f7f8fc;
            border: 1px solid #e8ecf4;
            border-radius: 10px;
            padding: 16px 18px;
        }
        .info-cell.full-width {
            grid-column: 1 / -1;
        }

        .info-cell-label {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #8fa3c8;
            margin-bottom: 5px;
        }

        .info-cell-value {
            font-size: 15px;
            font-weight: 500;
            color: #1a2f5e;
        }

        /* ── ELIGIBILITY RESULT BOX ── */
        .result-box {
            background: linear-gradient(135deg, #f0faf4 0%, #e6f7ed 100%);
            border: 1.5px solid #6dcf95;
            border-radius: 14px;
            padding: 24px 28px;
            display: flex;
            gap: 18px;
            align-items: flex-start;
            margin-bottom: 32px;
        }

        .result-icon {
            width: 46px; height: 46px;
            background: #25a85a;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 22px;
        }

        .result-title {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            color: #15703c;
            margin-bottom: 6px;
        }

        .result-desc {
            font-size: 14px;
            color: #2d7a4f;
            line-height: 1.65;
        }

        /* ── NEXT STEPS ── */
        .steps-label {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #9ba8bc;
            margin-bottom: 16px;
        }

        .steps-list {
            list-style: none;
            margin-bottom: 36px;
        }

        .steps-list li {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid #f0f2f8;
            font-size: 14px;
            color: #4a5568;
            line-height: 1.55;
        }
        .steps-list li:last-child { border-bottom: none; }

        .step-num {
            width: 26px; height: 26px;
            border-radius: 50%;
            background: #1a2f5e;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* ── CTA BUTTON ── */
        .cta-wrap {
            text-align: center;
            margin-bottom: 32px;
        }

        .cta-btn {
            display: inline-block;
            background: linear-gradient(135deg, #1a2f5e 0%, #0e1e42 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            letter-spacing: 0.02em;
            box-shadow: 0 8px 24px rgba(26, 47, 94, 0.28);
            transition: all 0.2s;
        }

        .note-box {
            background: #fffbec;
            border-left: 3px solid #d4af37;
            border-radius: 0 8px 8px 0;
            padding: 14px 18px;
            font-size: 13px;
            color: #6b5b20;
            line-height: 1.65;
            margin-bottom: 36px;
        }
        .note-box strong { color: #5a4a10; }

        .sign-off {
            font-size: 15px;
            color: #4a5568;
            line-height: 1.7;
        }
        .sign-off strong {
            display: block;
            margin-top: 16px;
            color: #1a2f5e;
            font-weight: 600;
            font-size: 15px;
        }
        .sign-off em {
            display: block;
            font-style: normal;
            font-size: 13px;
            color: #8fa3c8;
            margin-top: 2px;
        }

        /* ── FOOTER ── */
        .footer-band {
            background: #f3f5fb;
            border-radius: 0 0 20px 20px;
            padding: 28px 48px;
            border-top: 1px solid #e4e9f4;
        }

        .footer-meta {
            font-size: 12px;
            color: #9ba8bc;
            line-height: 1.7;
            text-align: center;
        }
        .footer-meta a { color: #6b85b8; text-decoration: none; }

        .footer-divider {
            width: 48px;
            height: 2px;
            background: linear-gradient(90deg, #d4af37, transparent);
            margin: 20px auto;
            border-radius: 2px;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 540px) {
            .header, .body-card, .footer-band { padding-left: 24px; padding-right: 24px; }
            .header-title { font-size: 24px; }
            .info-grid { grid-template-columns: 1fr; }
            .info-cell.full-width { grid-column: 1; }
        }
    </style>
</head>
<body>

@php

dd('dcgjxvhkzcvxmnvc');

    $etudiant = $contenu['etudiant'];
    $campagne = $contenu['campagne']['data']['campagnes']?->first();

@endphp

<div class="email-wrapper">

    <!-- HEADER -->
    <div class="header">
        <div class="logo-row">
            <div class="logo-emblem">UB</div>
            <div class="logo-text">
                Université de Bondoukou
                <span>Bureau de la gestion d'hebergement du {{ systemConfig('app_name', config('app.name')) }}</span>
            </div>
        </div>

        <div class="badge-status">Décision Officielle</div>

        <h1 class="header-title">
            Confirmation<br>d'<em>Éligibilité</em>
        </h1>
        <p class="header-sub">Année académique {{$campagne?->annee_campagne ?? 'N/D'}} · Programme d'admission et d'attribution</p>
    </div>

    <!-- GOLD STRIP -->
    <div class="divider-strip"></div>

    <!-- BODY -->
    <div class="body-card">

        <p class="greeting">
            <strong>{{$etudiant->civilite?->name=="Homme"? 'Cher Monsieur' :"Chère Mademoiselle" }} {{$etudiant->name}}</strong>,
        </p>

        <p class="intro-text">
            Après examen attentif de votre dossier de candidature et vérification de l'ensemble des critères définis par notre commission d'évaluation, nous avons le plaisir de vous informer que vous êtes <strong>déclarée éligible</strong> au programme sélectionnée ci-dessous.
        </p>

        <!-- INFO GRID -->
        <p class="info-section-label">Informations de l'Étudiant</p>
        <div class="info-grid">
            <div class="info-cell">
                <div class="info-cell-label">Matricule</div>
                <div class="info-cell-value">{{$etudiant->matricule}}</div>
            </div>

            <div class="info-cell">
                <div class="info-cell-label">Numéro Étudiant</div>
                <div class="info-cell-value">{{$etudiant->telephone1}}</div>
            </div>

            <div class="info-cell">
                <div class="info-cell-label">Niveau Precedent</div>
                <div class="info-cell-value">{{optional(optional($etudiant->derniereDemande)->niveauEtudiantPrecedent)->name ?? "N/D"}} </div>
            </div>

            <div class="info-cell">
                <div class="info-cell-label">Niveau Actuelle</div>
                <div class="info-cell-value">{{optional(optional($etudiant->derniereDemande)->niveauEtudiantActuel)->name ?? "N/D"}} </div>
            </div>
            <div class="info-cell">
                <div class="info-cell-label">Moyenne Générale Précédente</div>
                <div class="info-cell-value">{{optional($etudiant->derniereDemande)->moyenne}} / 20</div>
            </div>
            <div class="info-cell">
                <div class="info-cell-label">Credit de valider %</div>
                <div class="info-cell-value">{{optional($etudiant->derniereDemande)->credit_valider}} / 100</div>
            </div>
            {{--            <div class="info-cell full-width">--}}
            {{--                <div class="info-cell-label">Programme concerné</div>--}}
            {{--                <div class="info-cell-value">Bourse d'Excellence Nationale – Cycle Licence</div>--}}
            {{--            </div>--}}
        </div>

        <!-- RESULT BOX -->
        <div class="result-box">
            <div class="result-icon">✓</div>
            <div>
                <div class="result-title">Éligibilité Confirmée</div>
                <div class="result-desc">
                    Votre profil satisfait à l'ensemble des critères académiques et administratifs requis. Cette confirmation vous ouvre le droit à procéder à la phase de validation de votre dossier définitif.
                </div>
            </div>
        </div>

        <!-- NEXT STEPS -->
        <p class="steps-label">Prochaines Étapes à Suivre</p>
        <ul class="steps-list">
            <li>
                <span class="step-num">1</span>
                <span>Connectez-vous à votre <strong>espace étudiant (web ou mobile)</strong> et accédez à la section <em>« Mes Candidatures »</em> pour confirmer votre acceptation avant la date limite.</span>
            </li>

            <li>
                <span class="step-num">2</span>
                <span>Déposez l'ensemble des justificatifs demandés (Originaux) a l'inscription au <strong>Bureau du CROU </strong> pour une verification.</span>
            </li>
            <li>
                <span class="step-num">3</span>
                <span>Vous renseignemerai  <strong>la fiche de contrat et finaliserai votre paiement</strong> afin d'acceder a votre chambre.</span>
            </li>
        </ul>

        <!-- CTA -->
        <div class="cta-wrap">
            <a href="{{route('etudiant.login')}}" class="cta-btn">Accéder à mon Espace Étudiant en ligne →</a>
        </div>

        <div class="result-box" >

            <div class="result-icon" >✓</div>
            <div>
                <div class="result-title" >Parametre de connexion</div>
                <div class="result-desc" >
                    Email : <b>{{$etudiant->email1}}</b> <br>
                    Mot de passe : <b> password </b>
                </div>
            </div>


        </div>

        <!-- NOTE -->
        <div class="note-box">
            <strong>⚠ Important :</strong> Cette confirmation d'éligibilité ne constitue pas une attribution définitive de la chambre. La validation finale est conditionnée au dépôt complet et conforme de votre dossier avant le <strong>{{ optional($campagne?->date_echeance_demande)?->format('d-m-Y') ?? 'N/D' }}</strong>.
        </div>

        <!-- SIGN OFF -->
        {{--        <p class="sign-off">--}}
        {{--            Nous vous encourageons à finaliser votre dossier dans les meilleurs délais et restons disponibles pour toute question.--}}

        {{--            Veuillez agréer, {{$etudiant->civilite?->name=="Homme"? 'Chèr Monsieur' :"Chère Mademoiselle" }}, l'expression de nos sincères salutations.--}}

        {{--            <strong>Dr. Moussa Diallo</strong>--}}
        {{--            <em>Directeur du Bureau des Admissions &amp; Bourses</em>--}}
        {{--        </p>--}}

    </div>

    <!-- FOOTER -->
    <div class="footer-band">
        <div class="footer-divider"></div>
        <p class="footer-meta">
            Ce message est généré automatiquement par la plateforme de gestion académique.<br>
            Merci de ne pas répondre directement à cet e-mail.<br><br>
            <a href="#">Contacter le Support</a> · <a href="#">Politique de confidentialité</a> · <a href="#">Se désabonner</a><br><br>
            © {{$campagne?->annee_campagne ?? 'N/D'}} {{ systemConfig('app_name', config('app.name')) }}<br>

        </p>
    </div>



</div>
</body>
</html>
