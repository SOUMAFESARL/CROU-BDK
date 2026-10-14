@php
    // Valeurs par défaut
    $colWidth = $colWidth ?? 'col-sm-6 col-xl-3 mt-4';
    $label = $label ?? 'Recherche';
    $type = $type ?? 'submit';
    $btnClasses = $btnClasses ?? 'btn btn-primary btn-round';
    $icon = $icon ?? null;
    $id = $id ?? 'search-btn-' . uniqid();
    $name = $name ?? null;
    $disabled = $disabled ?? false;
    $onclick = $onclick ?? null;
    $form = $form ?? null;
    $tooltip = $tooltip ?? null;
@endphp

<div class="{{ $colWidth }}">
    <button
        id="{{ $id }}"
        type="{{ $type }}"
        class="{{ $btnClasses }}"
        @if($name) name="{{ $name }}" @endif
        @if($disabled) disabled @endif
        @if($onclick) onclick="{{ $onclick }}" @endif
        @if($form) form="{{ $form }}" @endif
        @if($tooltip)
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="{{ $tooltip }}"
        @endif
    >
        @if($icon)
            <i class="{{ $icon }} me-2"></i>
        @endif
        {{ $label }}
    </button>
</div>

@if($tooltip)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
@endif
