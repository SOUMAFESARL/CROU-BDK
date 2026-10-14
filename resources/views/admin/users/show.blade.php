@extends('admin.layouts.app', ['activeMenu' => 'users', 'activeSubMenu' => null])

@section('title', 'Utilisateurs - Détail')

@section('content')
    <div class="page-body user-detail-page">
        <x-breadcrumbs title="Détail utilisateur" :items="['Utilisateur' => route('admin.users.index'), 'Détail' => null]" />

        @if(session('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif

        <div class="user-profile mt-4">
            <div class="user-profile-header">
                <div class="user-profile-identity">
                    <div class="user-profile-avatar" aria-hidden="true">{{ getInitials($user->name, 2) }}</div>
                    <div class="user-profile-heading">
                        <p class="user-profile-caption">Fiche utilisateur</p>
                        <h1>{{ $user->name }}</h1>
                        <span class="user-profile-role"><i class="fas fa-shield-alt" aria-hidden="true"></i> {{ $user->role->name ?? 'Rôle non renseigné' }}</span>
                    </div>
                </div>
                <div class="user-profile-actions">
                    @adminroute('admin.users.status')
                    @if((string) auth('admin')->id() !== (string) $user->id_user)
                        <form method="POST" action="{{ route('admin.users.status', $user->id_user) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="statut" value="{{ $user->statut === 'active' ? 'inactive' : 'active' }}">
                            <button type="submit" class="btn btn-outline-secondary">{{ $user->statut === 'active' ? 'Désactiver' : 'Activer' }}</button>
                        </form>
                    @endif
                    @endadminroute
                    @adminroute('admin.users.index')
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Retour à la liste</a>
                    @endadminroute
                    @adminroute('admin.users.edit')
                    <a href="{{ route('admin.users.edit', $user->id_user) }}" class="btn btn-primary"><i class="fas fa-edit me-1" aria-hidden="true"></i> Modifier</a>
                    @endadminroute
                </div>
            </div>
            <div class="user-profile-body">
                <h2>Informations du compte</h2><p class="text-muted">Statut : <strong>{{ $user->statut === 'active' ? 'Actif' : 'Inactif' }}</strong></p>
                <div class="user-profile-grid">
                    @adminblock('users.personal')
                    <x-detail-item label="Civilité" :value="$user->genre->name ?? '—'" />
                    @endadminblock
                    <x-detail-item label="Nom" :value="$user->name" />
                    <x-detail-item label="Rôle" :value="$user->role->name ?? '—'" />
                    @adminblock('users.personal')
                    <x-detail-item label="Email" :value="$user->email ?? '—'" />
                    <x-detail-item label="Téléphone" :value="$user->telephone_principal ?? '—'" />
                    @endadminblock
                    <div class="detail-item user-profile-password">
                        <div class="detail-label">Mot de passe</div>
                        <div class="detail-value" aria-label="Mot de passe masqué">••••••••</div>
                        <small>Pour le changer, utilisez le bouton Modifier.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .user-detail-page .user-profile {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(15, 23, 42, .04);
        }
        .user-detail-page .user-profile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
            padding: 32px;
            background: linear-gradient(120deg, #f0f6ff, #fff);
            border-bottom: 1px solid #e2e8f0;
        }
        .user-detail-page .user-profile-identity { display: flex; align-items: center; gap: 20px; min-width: 0; }
        .user-detail-page .user-profile-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 26px;
            font-weight: 700;
        }
        .user-detail-page .user-profile-heading { min-width: 0; }
        .user-detail-page .user-profile-caption { margin: 0 0 6px; color: #64748b; font-size: var(--app-font-min, 15px); }
        .user-detail-page .user-profile-heading h1 { margin: 0 0 10px; color: #0f172a; font-size: 26px; font-weight: 700; overflow-wrap: anywhere; }
        .user-detail-page .user-profile-role { display: inline-flex; align-items: center; gap: 8px; color: #1e40af; font-weight: 500; }
        .user-detail-page .user-profile-actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .user-detail-page .user-profile-actions .btn { border-radius: 8px; padding: 10px 16px; }
        .user-detail-page .user-profile-body { padding: 32px; }
        .user-detail-page .user-profile-body h2 { margin: 0 0 22px; font-size: 18px; font-weight: 600; color: #0f172a; }
        .user-detail-page .user-profile-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
        .user-detail-page .detail-item { padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; }
        .user-detail-page .detail-label { margin-bottom: 10px; color: #64748b; font-size: var(--app-font-min, 15px); }
        .user-detail-page .detail-value { color: #0f172a; font-weight: 600; font-size: 17px; overflow-wrap: anywhere; }
        .user-detail-page .user-profile-password .detail-value { letter-spacing: 3px; }
        .user-detail-page .user-profile-password small { display: block; margin-top: 8px; color: #64748b; font-size: var(--app-font-min, 15px); }
        @media (max-width: 991px) {
            .user-detail-page .user-profile-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575px) {
            .user-detail-page .user-profile-header, .user-detail-page .user-profile-body { padding: 20px; }
            .user-detail-page .user-profile-identity { gap: 14px; }
            .user-detail-page .user-profile-heading h1 { font-size: 22px; }
            .user-detail-page .user-profile-grid { grid-template-columns: 1fr; gap: 12px; }
            .user-detail-page .user-profile-actions { width: 100%; }
            .user-detail-page .user-profile-actions .btn { flex: 1; }
        }
    </style>
@endpush
