<div class="form-row">
<div class="form-group">
        <label>Civilité <span class="required">*</span></label>
        <select name="genre_id" class="form-select" id="genre_id" required>
            <option value="">Sélectionnez une civilité</option>
            @foreach($genre as $item)
                <option value="{{ $item->id }}"
                        {{ old('genre_id', $user->genre_id ?? '') == $item->id ? 'selected' : '' }}>
                    {{ $item->name }}
                </option>
            @endforeach
        </select>
        @error('genre_id') <div class="error-message text-danger mt-1" style="font-size:var(--app-font-min, 15px)">{{ $message }}</div> @enderror
    </div>

<div class="form-group">
        <label>Nom <span class="required">*</span></label>
        <input type="text" name="name" placeholder="Ex: John Doe"
               value="{{ old('name', $user->name ?? '') }}" required>
        @error('name') <div class="error-message text-danger mt-1" style="font-size:var(--app-font-min, 15px)">{{ $message }}</div> @enderror
    </div>

<div class="form-group">
        <label>Téléphone <span class="required">*</span></label>
        <input type="tel" name="telephone_principal" id="telephone1"
               placeholder="Ex: 0700000000" inputmode="numeric" minlength="10" maxlength="10"
               pattern="[0-9]{10}" title="Le téléphone doit contenir exactement 10 chiffres." required
               value="{{ old('telephone_principal', $user->telephone_principal ?? '') }}">
        @error('telephone_principal') <div class="error-message text-danger mt-1" style="font-size:var(--app-font-min, 15px)">{{ $message }}</div> @enderror
    </div>

<div class="form-group">
        <label>Email <span class="required">*</span></label>
        <input type="email" name="email" placeholder="Ex: johndoe@yahoo.fr" required
               value="{{ old('email', $user->email ?? '') }}">
        @error('email') <div class="error-message text-danger mt-1" style="font-size:var(--app-font-min, 15px)">{{ $message }}</div> @enderror
    </div>

<div class="form-group">
        <label>Rôle <span class="required">*</span></label>
        @php($canAssignRole = auth('admin')->user()->hasPermission('parametrage.roles.assign'))
        @unless($canAssignRole)
            <input type="hidden" name="role_id" value="{{ $user->role_id ?? '' }}">
        @endunless
        <select name="role_id" class="form-select" id="role_id" required @disabled(!$canAssignRole)>
            <option value="">Sélectionnez un rôle</option>
            @foreach($role as $item)
                @continue((int) $item->id === 1 && (int) auth('admin')->user()->role_id !== 1 && (int) ($user->role_id ?? 0) !== 1)
                <option value="{{ $item->id }}"
                        {{ old('role_id', $user->role_id ?? '') == $item->id ? 'selected' : '' }}>
                    {{ $item->name }}
                </option>
            @endforeach
        </select>
        @error('role_id') <div class="error-message text-danger mt-1" style="font-size:var(--app-font-min, 15px)">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label>Mot de passe @if(!isset($user)) <span class="required">*</span> @else <small class="text-muted">(laisser vide pour conserver le mot de passe)</small> @endif</label>
        <input type="password" name="password" autocomplete="new-password" @required(!isset($user))>
        @error('password') <div class="error-message text-danger mt-1">{{ $message }}</div> @enderror
    </div>
</div>
