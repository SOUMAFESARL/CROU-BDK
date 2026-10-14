<div class="{{ $colWidth }}">
    @if($label)
        <label for="{{ $id }}">{{ $label }}</label>
        @if($required)
            <span class="text-danger">*</span>
        @endif
    @endif

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        class="{{ $classes }}"
        rows="{{ $rows }}"
        maxlength="{{ $maxlength }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
    >{{ $value }}</textarea>

    @if($maxlength)
        <div class="form-text  float-end">
            <small class="character-count text-danger fw-bold ">
                <span class="current-count  float-end">0</span>/{{ $maxlength }} caractères
            </small>
        </div>
    @endif

    @if($helpText)
        <div class="form-text">
            <small>{{ $helpText }}</small>
        </div>
    @endif

    @error($name)
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>

@push('scripts')
@if($maxlength)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('{{ $id }}');
    const counter = textarea?.closest('.{{ str_replace(" ", ".", $colWidth) }}')?.querySelector('.current-count');

    if (textarea && counter) {
        // Initial count
        counter.textContent = textarea.value.length;

        // Update count on input
        textarea.addEventListener('input', function() {
            counter.textContent = this.value.length;

            // Optional: add warning class when approaching limit
            if (this.value.length > {{ $maxlength }} * 0.9) {
                counter.classList.add('text-warning');
            } else {
                counter.classList.remove('text-warning');
            }
        });
    }
});
</script>
@endif
@endpush




{{-- ---------
<x-textarea-field
    label="Description détaillée"
    id="detailed_description"
    name="detailed_description"

    placeholder="Entrez une description détaillée du produit..."
    required="true"
    rows="6"
    maxlength="500"
    col-width="col-sm-4 col-xl-4"
    classes="form-control max-textarea"

/>--------------- --}}
