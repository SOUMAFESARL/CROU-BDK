<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mot de passe oublié | {{ systemConfig('app_name', config('app.name')) }}</title>
    <link rel="icon" href="{{ asset('admin/assets/images/logos/crou_bdk.jpeg') }}?v=20261015" type="image/jpeg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* LEFT PANEL */
        .left {
            flex: 1;
            background: linear-gradient(rgba(22, 122, 64, 0.85), rgba(22, 122, 64, 0.85)),
            url("{{ asset('files/assets/images/auth/image_crou.jpg') }}") center/cover no-repeat;

            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
        }

        .logo {
            width: 120px;
            margin-bottom: 20px;
        }

        .left h1 {
            margin: 10px 0;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .left p {
            max-width: 420px;
            font-size: 15px;
            opacity: 0.95;
        }

        .services {
            display: flex;
            gap: 40px;
            margin-top: 40px;
        }

        .service {
            text-align: center;
            font-size: var(--app-font-min, 15px);
        }

        .service span {
            display: inline-flex;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255,255,255,0.25);
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            font-size: 22px;
        }

        /* RIGHT PANEL */
        .right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .card h2 {
            margin: 0 0 8px;
            text-align: center;
            font-size: 24px;
        }

        .card .subtitle {
            text-align: center;
            font-size: var(--app-font-min, 15px);
            color: #666;
            margin-bottom: 28px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            display: block;
            margin-bottom: 6px;
        }

        .required:after {
            content: " *";
            color: red;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid #dcdcdc;
            font-size: var(--app-font-min, 15px);
        }

        .back-to-login {
            text-align: center;
            font-size: var(--app-font-min, 15px);
            margin-top: 18px;
        }

        .back-to-login a {
            color: #f4a100;
            text-decoration: none;
            font-weight: 500;
        }

        .btn {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: none;
            background: #1e7f43;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn:hover {
            background: #196a38;
        }

        .btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }

        .activate {
            margin-top: 28px;
            text-align: center;
            font-size: var(--app-font-min, 15px);
        }

        .activate button {
            margin-top: 2px;
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            background: #fff;
            border: 2px solid #f4a100;
            color: #f4a100;
            font-weight: 600;
            cursor: pointer;
        }

        footer {
            margin-top: 20px;
            font-size: var(--app-font-min, 15px);
            text-align: center;
            color: #999;
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
            text-align: center;
            font-size: var(--app-font-min, 15px);
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* ======= CAPTCHA ======= */
        .captcha-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
        }

        #captchaContainer {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 8px 12px;
            font-weight: bold;
            letter-spacing: 3px;
            font-size: 18px;
            background: #f0f0f0;
            border-radius: 4px;
            position: relative;
            user-select: none;
        }

        #reloadCaptcha {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: #1e7f43;
            color: white;
            border: none;
            padding: 6px 8px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }

        #reloadCaptcha:hover {
            background: #1e8d52;
        }

        #reloadCaptcha i {
            font-size: 16px;
        }

        #captchaInput {
            margin-top: 8px;
        }

        .text-success {
            color: #28a745;
        }

        .text-danger {
            color: #dc3545;
        }

        @media (max-width: 1024px) {
            .left {
                padding: 20px;
            }

            .left h1 {
                font-size: 24px;
            }

            .left p {
                font-size: var(--app-font-min, 15px);
            }

            .services {
                gap: 20px;
                margin-top: 20px;
            }

            .service {
                font-size: var(--app-font-min, 15px);
            }

            .service span {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .left {
                display: none;
            }

            .right {
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

            input[type="text"],
            input[type="email"] {
                padding: 10px 12px;
                font-size: var(--app-font-min, 15px);
            }

            .btn {
                padding: 12px;
                font-size: var(--app-font-min, 15px);
            }

            .activate button {
                padding: 12px;
                font-size: var(--app-font-min, 15px);
            }
        }

        @media (max-width: 480px) {
            .left {
                padding: 20px 15px;
            }

            .logo {
                width: 80px;
            }

            .left h1 {
                font-size: 22px;
            }

            .left p {
                font-size: var(--app-font-min, 15px);
            }

            .services {
                gap: 10px;
            }

            .service {
                font-size: var(--app-font-min, 15px);
            }

            .service span {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }

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

            .back-to-login {
                font-size: var(--app-font-min, 15px);
            }

            .options label {
                font-size: var(--app-font-min, 15px);
            }
        }
</style>
    <link rel="stylesheet" href="{{ asset('files/assets/css/system-theme.css') }}">
</head>
<body class="{{ systemConfig('force_dark_mode', false) ? 'app-dark-mode' : '' }}">

<section class="left">
    <img src="{{ systemLogo('files/assets/images/auth/logo_crou.png') }}" alt="{{ systemConfig('app_name', config('app.name')) }}" class="logo">

    <h1>{{ systemConfig('app_name', config('app.name')) }}</h1>
    <p>Plateforme intégrée de gestion des services universitaires.</p>

    <div class="services">
        <div class="service">
            <span><i class="fas fa-home"></i></span>
            Logement
        </div>
        <div class="service">
            <span><i class="fas fa-utensils"></i></span>
            Restaurant
        </div>
        <div class="service">
            <span><i class="fas fa-heartbeat"></i></span>
            Santé
        </div>
    </div>
</section>

<section class="right">
    <div class="card">
        <h2>Mot de passe oublié</h2>
        <p class="subtitle">Entrez votre adresse e-mail pour réinitialiser votre mot de passe.</p>

        <form id="forgotPasswordForm">
            <div id="messageContainer"></div>

            <div class="form-group">
                <label class="required">Adresse e-mail</label>
                <input type="email" id="email" placeholder="Votre adresse e-mail" required>
            </div>

            <div class="options">
                <div class="mb-3">
                    <label class="required">Captcha</label>
                    <div class="captcha-wrapper">
                        <div id="captchaContainer">
                            ABC123
                            <button type="button" id="reloadCaptcha">
                                <i class="fa fa-refresh" aria-hidden="true"></i>
                            </button>

                        </div>
                    </div>
                    <input type="text" id="captchaInput" class="form-control" placeholder="Entrez le texte" required>
                    <div id="captchaMessage" class="mt-1"></div>
                </div>

            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn" disabled>Réinitialiser le mot de passe</button>
            </div>
        </form>

        <div class="back-to-login">
            <a href="{{ route('admin.login') }}"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
        </div>



        <footer>
            © {{ now()->year }} {{ systemConfig('app_name', config('app.name')) }}
        </footer>
    </div>
</section>

<script>
    // ======= CAPTCHA =======
    function generateCaptcha(length = 6) {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijklmnopqrstvwxyz0123456789';
        let captcha = '';
        for(let i = 0; i < length; i++) {
            captcha += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return captcha;
    }

    let currentCaptcha = generateCaptcha();
    const captchaContainer = document.getElementById('captchaContainer');
    const captchaInput = document.getElementById('captchaInput');
    const captchaMessage = document.getElementById('captchaMessage');
    const submitBtn = document.querySelector('.btn');

    captchaContainer.childNodes[0].textContent = currentCaptcha;

    const reloadBtn = document.getElementById('reloadCaptcha');
    reloadBtn.addEventListener('click', () => {
        currentCaptcha = generateCaptcha();
        captchaContainer.childNodes[0].textContent = currentCaptcha;
        captchaInput.value = '';
        captchaMessage.textContent = '';
        submitBtn.disabled = true;
    });

    captchaInput.addEventListener('input', () => {
        if(captchaInput.value === currentCaptcha) {
            captchaMessage.textContent = 'CAPTCHA CORRECT';
            captchaMessage.classList.remove('text-danger');
            captchaMessage.classList.add('text-success');
            submitBtn.disabled = false;
        } else {
            captchaMessage.textContent = 'CAPTCHA INCORRECT';
            captchaMessage.classList.remove('text-success');
            captchaMessage.classList.add('text-danger');
            submitBtn.disabled = true;
        }
    });

    document.getElementById('forgotPasswordForm').addEventListener('submit', function(e){
        e.preventDefault();

        const email = document.getElementById('email').value;
        const messageContainer = document.getElementById('messageContainer');

        // Simple email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            messageContainer.innerHTML = '<div class="message error">Veuillez entrer une adresse e-mail valide.</div>';
            return;
        }

        if(captchaInput.value !== currentCaptcha) {
            messageContainer.innerHTML = '<div class="message error">Veuillez résoudre le CAPTCHA avant de soumettre.</div>';
            return;
        }

        // Simulate API call to reset password
        // In a real application, this would be an actual API call
        setTimeout(() => {
            messageContainer.innerHTML = '<div class="message success">Un e-mail de réinitialisation de mot de passe a été envoyé à votre adresse. Veuillez vérifier votre boîte de réception.</div>';

            // Clear the form
            document.getElementById('email').value = '';
            captchaInput.value = '';
            currentCaptcha = generateCaptcha();
            captchaContainer.childNodes[0].textContent = currentCaptcha;
            captchaMessage.textContent = '';
            submitBtn.disabled = true;
        }, 1000);
    });
</script>
</body>
</html>
