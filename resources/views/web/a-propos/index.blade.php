@extends('web.layouts.app')

@section('title', 'À propos de nous')

@section('content')



    <div class="breadcrumb-area" style="background-image:url('{{ asset('assets/img/bg/1.png') }}')">
        <div class="container">
            <div class="breadcrumb-inner">
                <div class="section-title mb-0">
                    <h2 class="page-title">À propos de nous</h2>
                    <ul class="page-list">
                        <li><a href="{{ route('accueil') }}">Accueil</a></li>
                        <li>À propos de nous</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- About Section -->
    @include('web.sections.about')

    <!-- Counter Section -->
    @include('web.sections.counter')

    <!-- About Smart Section -->
    @include('web.sections.about-smart')

    <!-- Team Section -->
    @include('web.sections.team')
@endsection
