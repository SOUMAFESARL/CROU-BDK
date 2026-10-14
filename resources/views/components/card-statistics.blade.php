<div class="stats-card {{ $color ?? 'primary' }} ">
    @if(isset($icon))
        <div class="icone-stats-card">
            <i class="{{ $icon }}"></i>
        </div>
    @endif
    <div class="stats-content ">
        <div class="titre-stats">
            {{ $titre ?? 'Titre' }}
        </div>
        <div class="nombre">
            {{ $nombre ?? '0' }}
        </div>
    </div>
</div>