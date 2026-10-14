@props([
    'title' => '',
    /**
     * Conservé pour compatibilité des appels existants (plus affiché sous le titre).
     * Format attendu :
     * [
     *   'Dashboard' => route('admin.dashboard.index'),
     *   'Utilisateurs' => route('admin.users.index'),
     *   'Ajouter' => null
     * ]
     */
    'items' => []
])

@php
    // Les attributs statiques (title="A & B") arrivent déjà échappés : on les décode
    // une fois pour éviter un double échappement ("&amp;") à l'affichage via {{ }}.
    $title = html_entity_decode((string) $title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
@endphp

<div class="mb-6">
    {{-- Titre de la page --}}
    <h1 class="page-title text-2xl font-medium text-gray-800">
        {{ $title }}
    </h1>

    {{-- Fil d’Ariane retiré sous les titres : il reste affiché dans la barre du haut. --}}
</div>
