<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><title>{{ $subjectLine }}</title></head>
<body style="font-family: sans-serif; color: #172b28; line-height: 1.6">
    <div>{!! $messageHtml ?? nl2br(e($messageText)) !!}</div>

    @if($access)
        <h2 style="font-size: 18px">Accès à votre espace étudiant</h2>
        @if($access['identifier'])
            <p>Identifiant : <strong>{{ $access['identifier'] }}</strong></p>
        @endif
        <p><a href="{{ $access['loginUrl'] }}">Se connecter à l’espace étudiant</a></p>
        @if($access['activation'])
            <p><a href="{{ $access['passwordUrl'] }}">Définir mon mot de passe</a></p>
            <p>Ce lien est à usage unique et reste valable {{ $access['expire'] }} minutes.
                Passé ce délai, utilisez <a href="{{ $access['forgotUrl'] }}">Mot de passe oublié</a>.</p>
        @else
            <p><a href="{{ $access['forgotUrl'] }}">Mot de passe oublié</a></p>
        @endif
    @endif

    @if($reclamation)
        <h2 style="font-size: 18px">Réclamation</h2>
        <p>Pour demander un réexamen de votre dossier, précisez le motif de votre réclamation.</p>
        <p><a href="{{ $reclamation['url'] }}" style="display:inline-block;background:#175449;color:white;padding:9px 16px;border-radius:6px;text-decoration:none">Faire une réclamation</a></p>
        <p>Vous pouvez aussi répondre directement à cet e-mail.</p>
    @endif
    <p>Le CROU</p>
</body>
</html>
