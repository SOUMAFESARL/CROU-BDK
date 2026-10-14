<footer class="footer-area bg-overlay" style="background-image: url('{{ asset('assets/img/bg/3.png') }}');">
    <div class="container">
        <!-- Newsletter / Infos -->
        <div class="subscribe-area-inner style-white pl-0 pr-0 pt-0">
            <div class="row justify-content-center text-center">
                <div class="col-xl-8 col-lg-10 align-self-center mb-4 mb-lg-0">
                    <h3>Restez informé</h3>
                    <p>Abonnez-vous à notre newsletter pour recevoir les dernières informations de {{ systemConfig('app_name', config('app.name')) }}.</p>
                    <div class="single-subscribe-inner style-two mt-3">
                        <input type="email" placeholder="Votre e-mail">
                        <button class="btn btn-base">S’abonner</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widgets -->
        <div class="row mt-5">
            <!-- À propos -->
            <div class="col-lg-4 col-md-6">
                <div class="widget widget_about pr-xl-4">
                    <div class="thumb mb-3">
                        <a href="{{ route('accueil') }}"><img src="{{ systemLogo() }}" width="70" height="70" alt="{{ systemConfig('app_name', config('app.name')) }}" style="object-fit: contain"></a>

                    </div>
                    <div class="details">
                        <p>
                            {{ systemConfig('app_name', config('app.name')) }} offre des services essentiels
                            aux étudiants : hébergement, restauration et accompagnement social.
                        </p>
                        <ul class="social-media">
                            <li><a class="btn-base-m" href="#"><i class="fa-brands fa-facebook"></i></a></li>
                            <li><a class="btn-base-m" href="#"><i class="fa-brands fa-twitter"></i></a></li>
                            <li><a class="btn-base-m" href="#"><i class="fa-brands fa-instagram"></i></a></li>
                            <li><a class="btn-base-m" href="#"><i class="fa-brands fa-linkedin"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Services -->
            <div class="col-lg-2 col-md-6">
                <div class="widget widget_nav_menu">
                    <h4 class="widget-title">Services</h4>
                    <ul>
                        <li><a href="#">Hébergement</a></li>
                        <li><a href="#">Restauration</a></li>
                        <li><a href="#">Accompagnement social</a></li>
                        <li><a href="#">Bibliothèque</a></li>
                        <li><a href="#">Activités étudiantes</a></li>
                    </ul>
                </div>
            </div>

            <!-- Liens rapides -->
            <div class="col-lg-2 col-md-6">
                <div class="widget widget_nav_menu">
                    <h4 class="widget-title">Liens rapides</h4>
                    <ul>
                        <li><a href="{{ route('accueil') }}">Accueil</a></li>
                        <li><a href="{{ route('a-propos') }}">À propos</a></li>
                        <li><a href="#">Actualités</a></li>
                        <li><a href="#">Services</a></li>
                        <li><a href="{{ route('contact') }}">Contact</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contact -->
            <div class="col-lg-4 col-md-6">
                <div class="widget widget_contact pl-lg-3">
                    <h4 class="widget-title">Contact</h4>
                    <ul class="details">
                        @if(systemConfig('phone'))<li><i class="fa-solid fa-phone"></i> {{ systemConfig('phone') }}</li>@endif
                        @if(systemConfig('email'))<li><i class="fa-solid fa-envelope"></i> {{ systemConfig('email') }}</li>@endif
                        @if(systemConfig('address') || systemConfig('country'))<li><i class="fa-solid fa-location-dot"></i> {{ collect([systemConfig('address'), systemConfig('country')])->filter()->join(', ') }}</li>@endif
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer bottom -->
    <div class="footer-bottom text-center mt-4">
        <div class="container">
            <div class="row">
                <div class="col-md-12 align-self-center">
                    <p>© {{ date('Y') }} {{ systemConfig('app_name', config('app.name')) }}. {{ __('ui.all_rights') }}</p>
                </div>
            </div>
        </div>
    </div>
</footer>
