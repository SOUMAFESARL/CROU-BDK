@extends('web.layouts.app')

@section('title', 'Accueil')

@section('content')
    <!-- Hero Section -->
    @include('web.sections.hero')

{{--    <!-- Features Section -->--}}
{{--    @include('web.sections.features')--}}

{{--    <!-- About Section -->--}}
{{--    @include('web.sections.about')--}}

{{--    <!-- Courses Section -->--}}
{{--    @include('web.sections.courses')--}}

{{--    <!-- Counter Section -->--}}
{{--    @include('web.sections.counter')--}}

{{--    <!-- About Smart Section -->--}}
{{--    @include('web.sections.about-smart')--}}

{{--    <!-- Testimonials Section -->--}}
{{--    @include('web.sections.testimonials')--}}

{{--    <!-- Team Section -->--}}
{{--    @include('web.sections.team')--}}

    <!-- CTA Section -->
    @include('web.sections.cta')

{{--    <!-- Blog Section -->--}}
{{--    @include('web.sections.blog')--}}
@endsection
