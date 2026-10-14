<div id="app-feedback" class="app-feedback" aria-label="Messages de la page">
    @foreach(['success' => 'Opération réussie', 'info' => 'Information', 'warning' => 'Attention', 'error' => 'Une erreur est survenue', 'status_error' => 'Une erreur est survenue', 'status' => 'Information'] as $key => $label)
        @if(is_string(session($key)) && session($key) !== '')
            <div class="app-notice app-notice-{{ $key === 'status_error' ? 'error' : $key }}" role="{{ in_array($key, ['error', 'status_error']) ? 'alert' : 'status' }}">
                <div><strong>{{ $label }}</strong><p>{{ session($key) }}</p></div>
                <button type="button" data-dismiss-notice aria-label="Fermer le message">&times;</button>
            </div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="app-notice app-notice-error" id="app-validation-errors" role="alert" tabindex="-1">
            <div><strong>Veuillez corriger les champs suivants avant de continuer.</strong>
                <ul>@foreach($errors->messages() as $field => $messages)
                    @foreach($messages as $message)<li><a href="#app-validation-errors" data-error-field="{{ $field }}">{{ $message }}</a></li>@endforeach
                @endforeach</ul>
            </div>
        </div>
    @endif
</div>
<div id="app-operation-status" class="app-operation-status" role="status" aria-live="polite" hidden></div>
<div id="app-network-status" class="app-notice app-notice-warning app-network-status" role="status" hidden>Connexion interrompue. Vérifiez votre connexion avant de poursuivre.</div>
