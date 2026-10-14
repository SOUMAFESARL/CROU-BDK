@extends('admin.layouts.app', ['activeMenu' => null, 'activeSubMenu' => null])
@section('title', 'Mon profil')
@php
    // « 0700000000 » → « 07 00 00 00 00 » (Côte d'Ivoire, préfixe +225 affiché).
    $telephone = preg_replace('/\D/', '', (string) $user->telephone_principal);
    $telephone = $telephone !== '' ? '+225 ' . trim(chunk_split($telephone, 2, ' ')) : null;
    $infos = [
        ['icon' => 'user-round', 'label' => 'Civilité', 'value' => $user->genre?->name],
        ['icon' => 'user-round', 'label' => 'Nom complet', 'value' => $user->name],
        ['icon' => 'shield-check', 'label' => 'Rôle', 'value' => $user->role?->name],
        ['icon' => 'mail', 'label' => 'Adresse e-mail', 'value' => $user->email],
        ['icon' => 'phone', 'label' => 'Téléphone', 'value' => $telephone],
        ['icon' => 'calendar-clock', 'label' => 'Compte créé le', 'value' => $user->created_at?->translatedFormat('d F Y')],
    ];
@endphp
@section('content')
<div class="page-body as-refonte pf">
    <style>
        .pf { --pf-primary: #006400; --pf-primary-hover: #005000; --pf-soft: #ebf8de; --pf-muted: #54595f; --pf-border: #e6e9e2; }
        .pf-header { margin-bottom: 24px; }
        .pf-header .page-title { margin-bottom: 0; }
        .pf-subtitle { margin: 6px 0 0; font-size: 14px; color: var(--pf-muted); }
        .pf-wrap { max-width: 56rem; margin: 0 auto; display: flex; flex-direction: column; gap: 24px; }
        .pf-card { background: #fff; border: 1px solid var(--pf-border); border-radius: 8px; }
        .pf-card-head { display: flex; align-items: center; gap: 16px; padding: 24px; border-bottom: 1px solid var(--pf-border); }
        .pf-avatar { display: flex; align-items: center; justify-content: center; flex-shrink: 0; width: 56px; height: 56px; border-radius: 50%; background: var(--pf-soft); color: var(--pf-primary); font-size: 18px; font-weight: 500; }
        .pf-name { margin: 0; font-size: 18px; font-weight: 500; color: #1f2328; }
        .pf-role { display: inline-flex; align-items: center; gap: 6px; margin-top: 4px; font-size: 14px; color: var(--pf-muted); }
        .pf-body { padding: 24px; }
        .pf-section-title { display: flex; align-items: center; gap: 8px; margin: 0 0 16px; font-size: 15px; font-weight: 500; color: #1f2328; }
        .pf-section-title svg { color: var(--pf-primary); }
        .pf-section-text { margin: -8px 0 20px; font-size: 13px; color: var(--pf-muted); }
        .pf-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin: 0; }
        @media (min-width: 640px) { .pf-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1024px) { .pf-grid { grid-template-columns: repeat(3, 1fr); } }
        .pf-item { display: flex; align-items: flex-start; gap: 12px; padding: 16px; border: 1px solid var(--pf-border); border-radius: 8px; background: #fafbf9; }
        .pf-item-icon { display: flex; align-items: center; justify-content: center; flex-shrink: 0; width: 36px; height: 36px; border-radius: 8px; background: #fff; color: var(--pf-primary); border: 1px solid var(--pf-border); }
        .pf-item dt { margin: 0; font-size: 12px; font-weight: 400; color: var(--pf-muted); }
        .pf-item dd { margin: 2px 0 0; font-size: 14px; font-weight: 500; color: #1f2328; word-break: break-word; }
        .pf-empty { font-weight: 400; color: var(--pf-muted); }
        .pf-form { display: grid; grid-template-columns: 1fr; gap: 16px; }
        @media (min-width: 1024px) { .pf-form { grid-template-columns: repeat(2, 1fr); } }
        .pf-label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 500; color: #1f2328; }
        .pf-input { width: 100%; height: 44px; padding: 0 12px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; font-size: 14px; color: #1f2328; transition: border-color .15s, box-shadow .15s; }
        .pf-input:focus { outline: none; border-color: var(--pf-primary); box-shadow: 0 0 0 3px var(--pf-soft); }
        .pf-input.is-invalid { border-color: #dc2626; }
        .pf-input.is-invalid:focus { box-shadow: 0 0 0 3px #fee2e2; }
        .pf-error { margin-top: 6px; font-size: 13px; color: #dc2626; }
        .pf-actions { display: flex; justify-content: flex-end; padding: 16px 24px; border-top: 1px solid var(--pf-border); }
        .pf-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 40px; padding: 0 20px; border: 0; border-radius: 8px; background: var(--pf-primary); color: #fff; font-size: 14px; font-weight: 500; cursor: pointer; transition: background-color .15s; }
        .pf-btn:hover { background: var(--pf-primary-hover); color: #fff; }
        .pf-btn:focus-visible { outline: 2px solid #86bb46; outline-offset: 2px; }
        .pf-alert { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border: 1px solid #c6e4a8; border-radius: 8px; background: var(--pf-soft); color: var(--pf-primary); font-size: 14px; }
        @media (max-width: 639.98px) {
            .pf-card-head, .pf-body { padding: 16px; }
            .pf-actions { padding: 16px; }
            .pf-btn { width: 100%; }
        }
    </style>

    {{-- Fil d’Ariane déjà affiché dans la barre du haut : seul le titre est rendu ici. --}}
    <div class="pf-header">
        <x-breadcrumbs title="Mon profil" />
        <p class="pf-subtitle">Informations de votre compte d'accès à l'espace d'administration.</p>
    </div>

    <div class="pf-wrap">
        @if(session('success'))
            <div class="pf-alert" role="status">
                <x-admin-icon name="circle-check" />
                {{ session('success') }}
            </div>
        @endif

        <section class="pf-card">
            <div class="pf-card-head">
                <span class="pf-avatar" aria-hidden="true">{{ getInitials($user->name, 2) }}</span>
                <div style="min-width:0">
                    <p class="pf-name">{{ $user->name }}</p>
                    <span class="pf-role">
                        <x-admin-icon name="shield-check" size="16" />
                        {{ $user->role?->name ?? 'Rôle non renseigné' }}
                    </span>
                </div>
            </div>
            <div class="pf-body">
                <h2 class="pf-section-title">Informations du compte</h2>
                <dl class="pf-grid">
                    @foreach($infos as $info)
                        <div class="pf-item">
                            <span class="pf-item-icon"><x-admin-icon :name="$info['icon']" /></span>
                            <div style="min-width:0">
                                <dt>{{ $info['label'] }}</dt>
                                <dd>{!! filled($info['value']) ? e($info['value']) : '<span class="pf-empty">Non renseigné</span>' !!}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        <section class="pf-card">
            <form method="POST" action="{{ route('admin.profile.password') }}">
                @csrf
                @method('PUT')
                <div class="pf-body">
                    <h2 class="pf-section-title"><x-admin-icon name="key-round" /> Modifier mon mot de passe</h2>
                    <p class="pf-section-text">Vous resterez connecté sur cet appareil après la modification.</p>
                    <div class="pf-form">
                        <div>
                            <label for="password" class="pf-label">Nouveau mot de passe</label>
                            <input type="password" id="password" name="password" class="pf-input @error('password') is-invalid @enderror" autocomplete="new-password" required>
                            @error('password')<div class="pf-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="pf-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="pf-input" autocomplete="new-password" required>
                        </div>
                    </div>
                </div>
                <div class="pf-actions">
                    <button class="pf-btn" type="submit">
                        <x-admin-icon name="lock" size="16" />
                        Enregistrer le mot de passe
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
