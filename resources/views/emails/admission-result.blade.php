@php
    // Charte : #FFFFFF, #006400 (boutons), #86BB46, #F8F8F8, #EBF8DE, #54595F — DM Sans / Segoe UI — coins de 8px.
    $police = "'DM Sans', 'Segoe UI', sans-serif";
    $bouton = "display:inline-block;background:#006400;color:#FFFFFF;padding:11px 20px;border-radius:8px;text-decoration:none;font-family:$police;font-size:14px;font-weight:500";
    $coordonnees = array_filter([systemConfig('address'), systemConfig('phone'), systemConfig('email')]);
    $logoSrc = $logo ? $message->embedData($logo['data'], $logo['nom'], $logo['mime']) : null;
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $subjectLine }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&display=swap');
    </style>
    <style>
        .corps p { margin: 0 0 12px; }
        .corps ul, .corps ol { margin: 0 0 12px; padding-left: 22px; }
        .corps strong, .corps b { font-weight: 500; color: #006400; }
        .corps a { color: #006400; word-break: break-all; }
        @media (max-width: 480px) { .marge { padding-left: 20px !important; padding-right: 20px !important; } }
    </style>
</head>
<body style="margin:0;padding:0;background:#F8F8F8;font-family:{{ $police }};color:#54595F">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#F8F8F8" style="background:#F8F8F8">
    <tr>
        <td align="center" style="padding:24px 12px">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#FFFFFF" style="max-width:600px;background:#FFFFFF;border:1px solid #E6E9E2;border-top:4px solid #006400;border-radius:8px">
                <tr>
                    <td class="marge" align="center" style="padding:24px 32px 18px;border-bottom:2px solid #86BB46;font-family:{{ $police }}">
                        @if($logoSrc)
                            <img src="{{ $logoSrc }}" alt="{{ systemAppName() }}" width="84" style="display:block;margin:0 auto 10px;width:84px;height:auto;border:0">
                        @endif
                        <div style="font-size:18px;font-weight:500;color:#006400">{{ systemAppName() }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="marge" style="padding:26px 32px 14px;font-family:{{ $police }};font-size:15px;line-height:1.6;color:#54595F">
                        <h1 style="margin:0 0 16px;font-size:18px;line-height:1.4;font-weight:500;color:#006400">{{ $subjectLine }}</h1>
                        <div class="corps">{!! $contenu !!}</div>
                    </td>
                </tr>

                @if($access)
                    <tr>
                        <td class="marge" style="padding:0 32px 22px;font-family:{{ $police }};font-size:14px;line-height:1.6;color:#54595F">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#EBF8DE" style="background:#EBF8DE;border-left:4px solid #86BB46;border-radius:8px">
                                <tr>
                                    <td style="padding:16px 18px;font-family:{{ $police }};font-size:14px;line-height:1.6;color:#54595F">
                                        <h2 style="margin:0 0 8px;font-size:15px;font-weight:500;color:#006400">Accès à votre espace étudiant</h2>
                                        @if($access['identifier'])
                                            <p style="margin:0 0 12px">Identifiant : <span style="color:#006400">{{ $access['identifier'] }}</span></p>
                                        @endif
                                        <p style="margin:0 0 12px"><a href="{{ $access['loginUrl'] }}" style="{{ $bouton }}">Se connecter à l’espace étudiant</a></p>
                                        @if($access['activation'])
                                            <p style="margin:0 0 8px"><a href="{{ $access['passwordUrl'] }}" style="color:#006400">Définir mon mot de passe</a></p>
                                            <p style="margin:0">Ce lien est à usage unique et reste valable {{ $access['expire'] }} minutes.
                                                Passé ce délai, utilisez <a href="{{ $access['forgotUrl'] }}" style="color:#006400">Mot de passe oublié</a>.</p>
                                        @else
                                            <p style="margin:0"><a href="{{ $access['forgotUrl'] }}" style="color:#006400">Mot de passe oublié</a></p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endif

                @if($reclamation)
                    <tr>
                        <td class="marge" style="padding:0 32px 22px">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#EBF8DE" style="background:#EBF8DE;border-left:4px solid #86BB46;border-radius:8px">
                                <tr>
                                    <td style="padding:16px 18px;font-family:{{ $police }};font-size:14px;line-height:1.6;color:#54595F">
                                        <h2 style="margin:0 0 8px;font-size:15px;font-weight:500;color:#006400">Réclamation</h2>
                                        <p style="margin:0 0 12px">Pour demander un réexamen de votre dossier, précisez le motif de votre réclamation.</p>
                                        <p style="margin:0 0 12px"><a href="{{ $reclamation['url'] }}" style="{{ $bouton }}">Faire une réclamation</a></p>
                                        <p style="margin:0">Vous pouvez aussi répondre directement à cet e-mail.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endif

                <tr>
                    <td class="marge" align="center" bgcolor="#F8F8F8" style="padding:16px 32px;background:#F8F8F8;border-top:1px solid #E6E9E2;border-radius:0 0 8px 8px;font-family:{{ $police }};font-size:12px;line-height:1.6;color:#54595F">
                        <div style="color:#006400">{{ systemFullName() }}</div>
                        @if($coordonnees)
                            <div>{{ implode(' · ', $coordonnees) }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
