<!-- banner start -->
<div class="banner-area banner-area-1" style="background-image: url('{{ asset('files/assets/img_web/hero.jpg') }}');">
    <div class="banner-bg"></div>
    <div class="container">
        <div class="row">
            <div class="col-xl-6 col-lg-7 align-self-center">
                <div class="banner-inner style-white text-center text-lg-left">

                    <h6 class="b-animate-1 sub-title">{{ systemConfig('app_name', config('app.name')) }}</h6>

                    <h1 class="b-animate-2 title">
                        Au service du bien-être des étudiants
                    </h1>

                    <p class="content b-animate-3">
                        Des services dédiés pour améliorer les conditions de vie et d’étude des étudiants.
                    </p>

                    <div class="btn-wrap">
                        <a class="btn btn-base-2 b-animate-4 mr-3" href="{{ route('contact') }}">
                            Nous contacter
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<!-- banner end -->
