@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => null
])

@section('title', 'Gestion Hébergement')

@php
    // Modules = sous-menu « Gestion Hébergement » de config/admin_menu.php (déjà filtré par droits),
    // pour que la page d'accueil et la barre latérale restent identiques.
    $modules = app(\App\Services\AdminMenu::class)->childrenOf(auth('admin')->user(), 'admin.hebergement.index', request());
@endphp

@section('content')
<div class="as-refonte">
    <x-breadcrumbs
            title="Gestion Hébergement"
            :items="[]"
    />

    <div class="pt-4 module-strip">
        @foreach($modules as $module)
            <a href="{{ $module['href'] }}" class="module-card">
                <span class="module-icon {{ $module['color'] ?? 'green' }}"><x-admin-icon :name="$module['icon']" /></span>
                <div>
                    <small>{{ $module['description'] }}</small>
                    <strong>{{ $module['label'] }}</strong>
                </div>
                <x-admin-icon name="arrow-up-right" class="module-arrow" />
            </a>
        @endforeach
    </div>
</div>
@endsection

@include('admin.partials.module-cards-styles')
