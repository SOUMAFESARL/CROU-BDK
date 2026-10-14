<a href="{{ isset($link) ? $link : '#' }}" class="fonctionnalites-box">
    @if(isset($icon))
        <div class="icone-container">
            <i class="{{ $icon }}"></i>
        </div>
    @endif
    <div class="titre">
        {{ $slot }}
    </div>
</a>