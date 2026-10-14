<!-- about area start -->
<div class="about-area pd-top-120 pd-bottom-120 bg-relative">
    <div class="container">
        <div class="about-area-inner">
            <div class="row">
                <!-- Images / Illustration -->
                <div class="col-lg-6 col-md-10 order-lg-12 text-lg-right">
                    <div class="about-thumb-wrap about-right-thumb">
{{--                        <img class="img-1" src="{{ asset('files/assets/img_web/hero.jpg') }}" alt="img">--}}
{{--                        <img class="img-2" src="{{ asset('files/assets/img_web/hero.jpg') }}" alt="img">--}}
{{--                        <img class="img-3" src="{{ asset('files/assets/img_web/hero.jpg') }}" alt="img">--}}
{{--                        <div class="about-quote">--}}
{{--                            <img src="{{ asset('files/assets/img_web/hero.jpg') }}" alt="img">--}}
{{--                            Plus de 1100 étudiants accompagnés chaque année--}}
{{--                        </div>--}}
                    </div>
                </div>

                <!-- Texte -->
                <div class="col-lg-6 order-lg-1 align-self-center">
                    <div class="about-inner-wrap pt-4 pt-lg-0">
                        <div class="section-title mb-0">
                            <h6 class="sub-title left-line">À propos</h6>
                            <h2 class="title">
                                {{ systemConfig('app_name', config('app.name')) }}, au service des étudiants
                            </h2>

                            <div class="media single-list-inner mt-4">
{{--                                <div class="media-left mr-3">--}}
{{--                                    <img src="{{ asset('files/assets/img_web/hero.jpg') }}" alt="img">--}}
{{--                                </div>--}}
                                <div class="media-body">
                                    <h4>Hébergement et restauration</h4>
                                    <p class="mb-0">
                                        Nous offrons des services de qualité pour le confort et le bien-être des étudiants.
                                    </p>
                                </div>
                            </div>

                            <div class="media single-list-inner mt-4">
{{--                                <div class="media-left mr-3">--}}
{{--                                    <img src="{{ asset('files/assets/img_web/hero.jpg') }}" alt="img">--}}
{{--                                </div>--}}
                                <div class="media-body">
                                    <h4>Accompagnement social</h4>
                                    <p class="mb-0">
                                        Nos équipes accompagnent les étudiants pour faciliter leur insertion et leur réussite académique.
                                    </p>
                                </div>
                            </div>

                            <a class="btn btn-base mt-4" href="{{ route('contact') }}">
                                Nous contacter
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- about area end -->
