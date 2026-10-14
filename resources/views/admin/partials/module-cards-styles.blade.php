{{-- Styles des cartes de modules (.module-strip / .module-card) partagés par les pages d'accueil de module
     (Gestion Hébergement, Gestion Paramétrage). --}}
@push('styles')
    <style>
        .module-strip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:16px 0}
        .module-card{background:#fff;border:1px solid var(--shell-border,#e6e9e2);border-radius:8px;box-shadow:0 7px 24px rgba(30,45,75,.045);padding:13px 14px;display:flex;align-items:center;gap:11px;text-decoration:none;color:var(--shell-text,#1f2328);transition:.2s}
        .module-card:hover{color:var(--shell-text,#1f2328);text-decoration:none;transform:translateY(-2px);box-shadow:0 10px 25px rgba(30,45,75,.09)}
        .module-card>.module-arrow{flex:none;margin-left:auto;color:#a5adbc;transition:.2s}
        .module-card:hover>.module-arrow{color:var(--shell-primary,#006400)}
        .module-card>div{min-width:0}
        .module-icon{flex:none;width:35px;height:35px;border-radius:8px;display:grid;place-items:center}
        .module-icon.green{background:var(--shell-soft,#ebf8de);color:var(--shell-primary,#006400)}
        .module-icon.amber{background:#fff6e5;color:#dc9318}
        .module-icon.red{background:#fff0ef;color:#e45851}
        .module-icon.purple{background:#f1edff;color:#6d4fc8}
        .module-card small,.module-card strong{display:block}
        .module-card small{font-size:var(--app-font-min,15px);color:var(--shell-muted,#54595f);margin-bottom:2px}
        .module-card strong{font-size:var(--app-font-min,15px);font-weight:500}
        @media (max-width:1199px){.module-strip{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media (max-width:991px){.module-strip{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media (max-width:575px){.module-strip{grid-template-columns:1fr}}
    </style>
@endpush
