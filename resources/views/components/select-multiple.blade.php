@php
    // Valeurs par défaut pour toutes les variables
    $colWidth = $colWidth ?? 'col-sm-12 col-xl-4 m-b-30';
    $label = $label ?? '';
    $id = $id ?? 'select-' . uniqid();
    $name = $name ?? $id . '[]';
    $options = $options ?? [];
    $selected = $selected ?? [];
    $placeholder = $placeholder ?? 'Sélectionnez des options';
    $required = $required ?? false;
    $disabled = $disabled ?? false;
    $selectClasses = $selectClasses ?? 'form-control rounded-5';
    $helpText = $helpText ?? null;
@endphp

<div class="{{ $colWidth }}">
    @if($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}</label>
        @if($required)
            <span class="text-danger">*</span>
        @endif
    @endif

    @if($helpText)
        <div class="form-text mb-2">
            <small class="text-muted">{{ $helpText }}</small>
        </div>
    @endif

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        class="{{ $selectClasses }} {{ $multiple ? 'js-example-responsive ' : '' }}"

          {{ $multiple ? 'multiple' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
    >
        @if(empty($options))
            <!-- Options par défaut -->
            <option value="cheese">Cheese</option>
            <option value="h">Hanry Die</option>
            <option value="c">Come Leo</option>
            <option value="h2">Hampri Catlin</option>
        @else
            @foreach($options as $key => $value)
                @if(is_array($value))
                    <option value="{{ $value['value'] ?? $key }}"
                        @if(in_array($value['value'] ?? $key, $selected)) selected @endif
                    >
                        {{ $value['text'] ?? $value['value'] ?? $key }}
                    </option>
                @else
                    <option value="{{ $key }}"
                        @if(in_array($key, $selected)) selected @endif
                    >
                        {{ $value }}
                    </option>
                @endif
            @endforeach
        @endif
    </select>

    @error(str_replace('[]', '', $name))
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror
</div>
