<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Connexion | {{ systemConfig('company_name') ?: systemConfig('app_name', config('app.name')) }}</title>
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
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid #dcdcdc;
            font-size: var(--app-font-min, 15px);
        }

        .forgot {
            text-align: right;
            font-size: var(--app-font-min, 15px);
            margin-top: -6px;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 14px 0 18px;
            font-size: var(--app-font-min, 15px);
        }

        .remember-forgot .remember {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 400;
        }

        .remember-forgot .forgot-link {
            color: #f4a100;
            text-decoration: none;
            font-weight: 500;
        }


        .forgot a {
            color: #f4a100;
            text-decoration: none;
        }

        .options {
            margin: 18px 0;
            font-size: var(--app-font-min, 15px);
        }

        .options label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 400;
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
            input[type="email"],
            input[type="password"] {
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

            .remember-forgot {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .forgot-link {
                text-align: left;
            }

            .options label {
                font-size: var(--app-font-min, 15px);
            }
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
</style>
    <link rel="stylesheet" href="{{ asset('files/assets/css/system-theme.css') }}">
</head>
<body class="{{ systemConfig('force_dark_mode', false) ? 'app-dark-mode' : '' }}">

<section class="left">
    <img src="{{ systemLogo() }}" alt="{{ systemConfig('company_name') ?: systemConfig('app_name', config('app.name')) }}" class="logo">

    <h1>{{ systemConfig('company_name') ?: systemConfig('app_name', config('app.name')) }}</h1>
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
        <h2>Connexion</h2>
        <p class="subtitle">Accédez à votre espace administratif.</p>

        <form id="loginForm" method="POST" action="{{route('admin.post.login')}}" >
            @csrf
            <div class="form-group">
                <label class="required"> Email</label>
                <input type="email" name="email" id="email" placeholder=" admin@crou.ci" required>
            </div>

            <div class="form-group">
                <label class="required">Mot de passe</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required>
                    <span id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                    <i class="fas fa-eye"></i>
                </span>
                </div>
            </div>

            <div class="remember-forgot">
                <label class="remember">
                    <input type="checkbox">
                    Se souvenir de moi
                </label>

                <a href="#" class="forgot-link">Mot de passe oublié ?</a>
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

            <button type="submit" class="btn">Se connecter</button>

{{--            <div class="activate">--}}
{{--                <p>Vous êtes étudiant et c'est votre première visite ?</p>--}}
{{--                <button>Activer mon compte étudiant</button>--}}
{{--            </div>--}}
        </form>

        <footer>
            © {{ now()->year }} {{ systemConfig('company_name') ?: systemConfig('app_name', config('app.name')) }}
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
    const submitBtn = document.querySelector('.btn'); // Using the class selector since there's no ID

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

    document.getElementById('loginForm').addEventListener('submit', function(e){
        if(captchaInput.value !== currentCaptcha) {
            e.preventDefault();
            captchaMessage.textContent = 'Veuillez résoudre le CAPTCHA avant de vous connecter.';
            captchaMessage.classList.remove('text-success');
            captchaMessage.classList.add('text-danger');
        } else {
            // If captcha is correct, allow form submission
            // You can add your login logic here
            // e.g., AJAX request to authenticate user
            console.log('Form submitted with username:', document.getElementById('username').value);
        }
    });

    // ======= Voir / Masquer mot de passe =======
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const icon = togglePassword.querySelector('i');

    togglePassword.addEventListener('click', () => {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
</script>
</body>
</html>
