@extends('admin.layouts.app', [
    'activeMenu' => 'parametres',
    'activeSubMenu' => null
])
@section('title', 'Gestion Paramétrage')

@php
    // Modules = sous-menu « Paramètres » de config/admin_menu.php (déjà filtré par droits),
    // pour que la page d'accueil et la barre latérale restent identiques.
    $modules = app(\App\Services\AdminMenu::class)->childrenOf(auth('admin')->user(), 'admin.parametrage.index', request());
@endphp

@section('content')
<div class="as-refonte">
    <x-breadcrumbs
            title="Gestion Paramétrage"
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
