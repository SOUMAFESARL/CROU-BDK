@extends('web.layouts.app')

@section('title', 'Connexion')

@section('content')


    <!-- Sign In Area Start -->
    <div class="signin-area pd-top-120 mt-3 pd-bottom-120">
        <div class="container mt-3">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-7">
                    <div class="signin-wrap">
                        <div class="section-title text-center">
                            <h2 class="title">Binevenue!</h2>
                            <p>Connectez vous á votre compte</p>
                        </div>
                        @if(isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <form class="contact-form" action="{{ route('login.etudiant') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                               placeholder="Email Address" value="{{ old('email') }}" required>
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" required>
                                        @error('password')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="options col-12">
                                    <div class="mb-3">
                                        <label class="required">Captcha</label>
                                        <div class="captcha-wrapper">
                                            <div id="captchaContainer">
                                                ABC123
                                                <button type="button" id="reloadCaptcha">
                                                    <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                                                </button>

                                            </div>
                                        </div>
                                        <input type="text" id="captchaInput" class="form-control" placeholder="Entrez le texte" required>
                                        <div id="captchaMessage" class="mt-1"></div>
                                    </div>

                                </div>
                                <div class="col-12">
                                    <div class="form-group d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="remember">
                                                Se Souvenir
                                            </label>
                                        </div>
                                        <a href="#">Mot de passe oublié?</a>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-base w-100">Connexion</button>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Sign In Area End -->
@endsection

@push('styles')
    <style>
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
@endpush

@push('scripts')
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
@endpush
