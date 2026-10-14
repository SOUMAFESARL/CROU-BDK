<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Activation de compte | {{ systemConfig('app_name', config('app.name')) }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            margin: 0;
            height: 100vh;
            display: flex;
            background: #f4f6f5;
        }

        /* ===== LEFT FORM ===== */
        .left {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 18px;
            padding: 36px 32px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
        }

        .card h2 {
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 700;
        }

        .card .subtitle {
            font-size: var(--app-font-min, 15px);
            color: #666;
            margin-bottom: 28px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
        }

        .required::after {
            content: " *";
            color: red;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: var(--app-font-min, 15px);
        }

        input::placeholder {
            color: #aaa;
        }

        .btn {
            margin-top: 12px;
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            border: none;
            background: #f0b429;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        /* ===== RIGHT IMAGE ===== */
        .right {
            flex: 1;
            position: relative;
            background:
                    linear-gradient(rgba(22,122,64,0.85), rgba(22,122,64,0.85)),
                    url("{{ asset('files/assets/images/auth/etudiants.jpg') }}") center/cover no-repeat;
            color: #fff;
            display: flex;
            align-items: center;
            padding: 60px;
        }

        .overlay-content {
            max-width: 100%;
        }

        .overlay-content img {
            width: 120px;
            margin-bottom: 24px;
        }

        .overlay-content h2 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .overlay-content ul {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 16px;
            line-height: 1.6;
        }

        .overlay-content li {
            margin-bottom: 10px;
            padding-left: 18px;
            position: relative;
        }

        .overlay-content li::before {
            content: "•";
            position: absolute;
            left: 0;
            font-size: 22px;
            line-height: 1;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .right {
                padding: 20px;
            }

            .overlay-content {
                max-width: 100%;
            }

            .overlay-content h2 {
                font-size: 24px;
            }

            .overlay-content ul {
                font-size: var(--app-font-min, 15px);
            }

            .card {
                padding: 28px 24px;
            }

            .card h2 {
                font-size: 22px;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .right {
                display: none;
            }

            .left {
                padding: 20px 0;
            }

            .card {
                margin: 0 20px;
                padding: 24px 20px;
                max-width: 100%;
            }

            .card h2 {
                font-size: 22px;
            }

            .form-group {
                margin-bottom: 16px;
            }

            input {
                padding: 10px 12px;
                font-size: var(--app-font-min, 15px);
            }

            .btn {
                padding: 12px;
                font-size: var(--app-font-min, 15px);
            }
        }

        @media (max-width: 480px) {
            .card {
                margin: 0 15px;
                padding: 20px 16px;
            }

            .card h2 {
                font-size: 20px;
            }

            .subtitle {
                font-size: var(--app-font-min, 15px);
            }

            label {
                font-size: var(--app-font-min, 15px);
            }

            .overlay-content h2 {
                font-size: 22px;
            }

            .overlay-content ul {
                font-size: var(--app-font-min, 15px);
            }

            .overlay-content li {
                margin-bottom: 8px;
            }
        }
</style>
    <link rel="stylesheet" href="{{ asset('files/assets/css/system-theme.css') }}">
</head>
<body class="{{ systemConfig('force_dark_mode', false) ? 'app-dark-mode' : '' }}">

<!-- LEFT -->
<section class="left">
    <div class="card">
        <h2>Activation de compte</h2>
        <p class="subtitle">Accédez à votre espace étudiant ou administratif.</p>

        <div class="form-group">
            <label class="required">Matricule</label>
            <input type="text" placeholder="2145899">
        </div>

        <div class="form-group">
            <label class="required">Date de naissance</label>
            <input type="text" placeholder="jj/mm/aaaa">
        </div>

        <div class="form-group">
            <label class="required">Email</label>
            <input type="email" placeholder="exemple@gmail.com">
        </div>

        <div class="form-group">
            <label class="required">Créer un mot de passe</label>
            <input type="password" placeholder="Au moins 6 caractères">
        </div>

        <div class="form-group">
            <label class="required">Confirmer le mot de passe</label>
            <input type="password" placeholder="Répétez le même mdp">
        </div>

        <button class="btn">Activer mon compte étudiant</button>
    </div>
</section>

<!-- RIGHT -->
<section class="right">
    <div class="overlay-content">
        <img src="{{ systemLogo('files/assets/images/auth/logo_crou.png') }}" alt="{{ systemConfig('app_name', config('app.name')) }}">

        <h2>Pourquoi activer votre compte ?</h2>

        <ul>
            <li>Faire vos demandes de logement en ligne.</li>
            <li>Payer vos loyers via Mobile Money.</li>
            <li>Signaler des incidents techniques.</li>
            <li>Prendre rendez-vous à l'infirmerie.</li>
        </ul>
    </div>
</section>

</body>
</html>
