{{--@extends('admin.layouts.app', [--}}
{{--    'activeMenu' => 'users',--}}
{{--    'activeSubMenu' => null--}}
{{--])--}}
{{--@section('title', 'Gestion des utilisteurs')--}}

{{--@section('content')--}}
{{--    <div class="page-body">--}}

{{--        <x-breadcrumbs--}}
{{--                title="Utilisateur"--}}
{{--                :items="[--}}
{{--                        'Utilisateur' => route('admin.users.index'),--}}
{{--                        'Liste Utilisateur' => null,--}}
{{--                    ]"--}}
{{--        />--}}
{{--        @php--}}
{{--            $users = $user;--}}

{{--            $columns = [--}}
{{--                ['key' => 'name', 'label' => 'Nom'],--}}
{{--                ['key' => 'email', 'label' => 'Email'],--}}
{{--                ['key' => 'role', 'label' => 'Rôle'],--}}
{{--                [--}}
{{--                    'key' => 'statut',--}}
{{--                    'label' => 'Statut',--}}
{{--                    'render' => function($value) {--}}
{{--                        $badge = $value === 'actif' ? 'success' : 'warning';--}}
{{--                        return '<span class="badge bg-' . $badge . '">' . $value . '</span>';--}}
{{--                    }--}}
{{--                ],--}}
{{--                ['key' => 'created_at', 'label' => 'Date d\'inscription'],--}}
{{--            ];--}}
{{--        @endphp--}}


{{--        --}}{{-- Définir les données statiques simplifiées --}}
{{--        @php--}}
{{--            $items = $user;--}}
{{--        @endphp--}}

{{--        --}}{{-- Utiliser le composant --}}
{{--        <x-data-table-add--}}
{{--                :items="$users"--}}
{{--                title="Liste des utilisateurs"--}}
{{--                --}}
{{--                :columns="['Civilité', 'Nom', 'Rôle', 'Email', 'Téléphone', 'Actions']"--}}
{{--                :fields="['genre.name', 'name', 'role.name', 'email', 'telephone_principal']"--}}
{{--                :tabs="['All']"--}}
{{--                tab-filter-field="type_proprietaire"--}}
{{--                :show-tabs="false"--}}
{{--                :show-export="true"--}}
{{--                :show-back="false"--}}
{{--                :show-add="true"--}}
{{--                add-route="{{ route('admin.users.create') }}"--}}
{{--                export-route="#"--}}
{{--                :actionRoutes="[--}}
{{--            'view' => '#',--}}
{{--            'edit' => route('admin.users.edit', ['id_user' => ':id_user']),--}}

{{--        ]"--}}
{{--                :routeParams="['id_user' => 'id_user']"--}}
{{--                :column-types="['Statut' => 'badge']"--}}
{{--                :status-classes="['Statut' => ['active' => 'success', 'inactive' => 'warning']]"--}}
{{--                id-field="id_user"--}}
{{--        />--}}





{{--    </div>--}}
{{--@endsection--}}

{{--@push('scripts')--}}
{{--    <script src="{{ asset('files/assets/pages/dashboard/custom-dashboard.js') }}"></script>--}}
{{--@endpush--}}



@extends('admin.layouts.app', [
    'activeMenu'    => 'users',
    'activeSubMenu' => null
])

@section('title', 'Gestion des utilisateurs')

@section('content')
    <div class="page-body">

        <x-breadcrumbs
                title="Utilisateurs"
                :items="[
                'Utilisateur'       => route('admin.users.index'),
                'Liste utilisateurs' => null,
            ]"
        />

        {{-- Alertes flash --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <x-data-table-add
                :items="$user"
                title="Liste des utilisateurs"
                card-layout="users"
                :columns="['Civilité', 'Nom', 'Rôle', 'Email', 'Téléphone', 'Actions']"
                :fields="['genre.name', 'name', 'role.name', 'email', 'telephone_principal']"
                :tabs="['All']"
                tab-filter-field="type_proprietaire"
                :show-tabs="false"
                :show-export="true"
                :show-back="false"
                :show-add="true"
                add-route="{{ route('admin.users.create') }}"
                export-route="#"
                :actionRoutes="[
                'view' => route('admin.users.show', ['id_user' => ':id_user']),
                'edit' => route('admin.users.edit', ['id_user' => ':id_user']),
                'status' => route('admin.users.status', ['id_user' => ':id_user']),
            ]"
                :routeParams="['id_user' => 'id_user']"
                :column-types="['Statut' => 'badge']"
                :status-classes="['Statut' => ['active' => 'success', 'inactive' => 'warning']]"
                id-field="id_user"
        >
            {{-- Bouton suppression dans le slot actions si votre composant le supporte --}}
        </x-data-table-add>

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('files/assets/pages/dashboard/custom-dashboard.js') }}"></script>

    {{-- Confirmation de suppression --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-delete-user]').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const url = this.getAttribute('data-delete-user');
                    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;
                        form.innerHTML = `@csrf @method('DELETE')`;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
