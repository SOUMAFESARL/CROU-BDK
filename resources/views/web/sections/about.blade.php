<!-- about area start -->
<div class="about-area bg-relative pd-top-90 pd-bottom-120">
    <div class="container">
        <div class="about-area-inner">
            <div class="row">
                <!-- Images / Illustration -->
                <div class="col-lg-6 col-md-11">
                    <div class="about-thumb-wrap about-left-thumb">
{{--                        <img class="img-1" src="{{ asset('assets/img/about/1.png') }}" alt="img">--}}
                        <img class="img-2" src="{{ asset('files/assets/img_web/hero.jpg') }}" alt="img">
{{--                        <img class="img-3" src="{{ asset('assets/img/about/3.png') }}" alt="img">--}}
                    </div>
                </div>

                <!-- Texte -->
                <div class="col-lg-6 align-self-center">
                    <div class="about-inner-wrap mt-2 pt-4 pt-lg-0">
                        <div class="section-title mb-0">
                            <h6 class="sub-title left-line">À propos</h6>
                            <h2 class="title">{{ systemConfig('app_name', config('app.name')) }}, au service des étudiants</h2>
                            <p class="content">
                                {{ systemConfig('app_name', config('app.name')) }} accompagne les étudiants
                                dans leurs études et leur vie quotidienne grâce à des services de qualité :
                                hébergement, restauration, accompagnement social et activités culturelles.
                            </p>
                            <h4 class="small-title">
                                Nous facilitons la réussite et le bien-être <br> de chaque étudiant
                            </h4>
                            <h4 class="phone">
                                <i class="fa-solid fa-phone"></i> {{ systemConfig('phone', 'Contact non renseigné') }}
                            </h4>
                            <a class="btn btn-base" href="{{ route('contact') }}">Nous contacter</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- about area end -->
