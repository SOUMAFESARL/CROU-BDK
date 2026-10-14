@extends('web.layouts.app')

@section('title', 'Nous Contacter')

@section('content')

    <div class="breadcrumb-area" style="background-image:url('{{ asset('assets/img/bg/1.png') }}')">
        <div class="container">
            <div class="breadcrumb-inner">
                <div class="section-title mb-0">
                    <h2 class="page-title">Nous Contacter</h2>
                    <ul class="page-list">
                        <li><a href="{{ route('accueil') }}">Accueil</a></li>
                        <li>Contact</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Area Start -->
    <div class="contact-area pd-top-120 pd-bottom-120">
        <div class="container">
            <div class="row">

                <!-- Informations -->
                <div class="col-lg-5">
                    <div class="contact-info-wrap">
                        <div class="section-title mb-4">

                            <h2 class="title">Nos coordonnées</h2>
                        </div>

                        <div class="single-contact-wrap">
                            <div class="thumb">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <div class="details">
                                <h5>Adresse</h5>
                                <p>{{ collect([systemConfig('address'), systemConfig('country')])->filter()->join(', ') ?: systemConfig('app_name', config('app.name')) }}</p>
                            </div>
                        </div>

                        <div class="single-contact-wrap">
                            <div class="thumb">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div class="details">
                                <h5>Téléphone</h5>
                                <p>{{ systemConfig('phone', 'Non renseigné') }}</p>
                            </div>
                        </div>

                        <div class="single-contact-wrap">
                            <div class="thumb">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <div class="details">
                                <h5>Email</h5>
                                <p>{{ systemConfig('email', 'Non renseigné') }}</p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Formulaire -->
                <div class="col-lg-7">
                    <div class="contact-form-wrap">
                        <div class="section-title mb-4">

                            <h2 class="title">Nous écrire</h2>
                        </div>

                        <form class="contact-form" action="{{ route('contact.send') }}" method="POST">
                            @csrf

                            <div class="row">

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <input type="text" name="name" class="form-control" placeholder="Nom complet" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <input type="email" name="email" class="form-control" placeholder="Adresse email" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <input type="text" name="phone" class="form-control" placeholder="Téléphone">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <input type="text" name="subject" class="form-control" placeholder="Sujet" required>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <textarea name="message" class="form-control" rows="5" placeholder="Votre message" required></textarea>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-base">
                                        Envoyer le message
                                    </button>
                                </div>

                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- Contact Area End -->

    <!-- Map Area Start -->
    <div class="map-area">
        <iframe
                src="https://maps.google.com/maps?q=Bondoukou%20cote%20d'ivoire&t=&z=13&ie=UTF8&iwloc=&output=embed"
                width="100%"
                height="400"
                style="border:0;"
                loading="lazy">
        </iframe>
    </div>
    <!-- Map Area End -->

@endsection
