<div class="{{ $colWidth }}">
    @if($label)
        <label for="{{ $id }}">{{ $label }} 
            @if ($required)
                <strong class="text-danger">*</strong>
            @endif
            
        </label>
    @endif
    
    <input 
        id="{{ $id }}"
        name="{{ $name }}"
        class="{{ $classes }}"
        type="{{ $type }}" 
        placeholder="{{ $placeholder }}"
        value="{{ $value }}"
        {{ $required ? 'required' : '' }}
        maxlength="$maxlength"
    />
      @if($maxlength)
        <div class="form-text  float-end">
            <small class="character-count text-danger fw-bold ">
                <span class="current-count  float-end">0</span>/{{ $maxlength }} caractères
            </small>
        </div>
    @endif
    @error($name)
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>

{{-- ------------------


 <x-input-text 
    label="Nom complet" 
    id="dropper-dangercolor"
    name="full_name"
    type="text"
    value="test"
    placeholder="Selectionnez la date"
    required="true"
    classes="form-control form-control-lg"
    />

--}}
{{-- 
pour les date utilise id="dropper-dangercolor"  --}}

