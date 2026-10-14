{{-- resources/views/components/detail-item.blade.php --}}
<div class="detail-item">
    <div class="detail-label">{{ $label }}</div>
    <div class="detail-value">{{ $value ?: '—' }}</div>
</div>