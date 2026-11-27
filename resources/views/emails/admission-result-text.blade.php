{!! $messageText !!}

@if($access)
Accès à votre espace étudiant
@if($access['identifier'])
Identifiant : {!! $access['identifier'] !!}
@endif
Connexion : {!! $access['loginUrl'] !!}
@if(!empty($access['temporaryPassword']))
Mot de passe temporaire : {!! $access['temporaryPassword'] !!}
@elseif($access['activation'])
Définir mon mot de passe : {!! $access['passwordUrl'] !!}
Ce lien est à usage unique et reste valable {{ $access['expire'] }} minutes.
@endif
Mot de passe oublié : {!! $access['forgotUrl'] !!}

@endif
@if($reclamation)
Pour faire une réclamation, répondez à ce message ou écrivez à {!! $reclamation['email'] !!}.
Indiquez la référence de votre demande ({!! $reclamation['reference'] !!}) et le motif de votre demande de réexamen.

@endif
{{ systemAppName() }}
