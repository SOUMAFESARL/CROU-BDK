@extends('admin.layouts.app', [
    'activeMenu' => 'Etudiant',
    'activeSubMenu' => null
])

@section('title', 'Gestion Etudiant')

@section('content')
    <x-breadcrumbs
            title="Gestion Etudiant"
            :items="[]"
    />

    <div class="pt-5 fonctionnalite-container">
        @adminroute('admin.etudiant.nouvelle_demande')
        <x-fonctionnalite-card
                link="{{ route('admin.etudiant.nouvelle_demande') }}"
                icon="fa-solid fa-building"
        >
            Nouvelle demande
        </x-fonctionnalite-card>
        @endadminroute

        <x-fonctionnalite-card
                link="#"
                icon="fa-solid fa-toolbox"
        >
            Demande en traitement
        </x-fonctionnalite-card>

        <x-fonctionnalite-card
                link="#"
                icon="fa-solid fa-bullhorn"
        >
           Etudiant en cités
        </x-fonctionnalite-card>







    </div>
@endsection

@push('scripts')
    <script src="{{ asset('files/assets/pages/dashboard/custom-dashboard.js') }}"></script>
@endpush
