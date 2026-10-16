<?php
namespace App\Support;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class UserFacingErrors
{
    public static function status(Throwable $error): int
    {
        return match (true) {
            $error instanceof ValidationException => 422,
            $error instanceof AuthenticationException => 401,
            $error instanceof AuthorizationException => $error->status() ?? 403,
            $error instanceof ModelNotFoundException => 404,
            $error instanceof \App\Exceptions\OccupationIncompatibleException => 422,
            $error instanceof HttpExceptionInterface => $error->getStatusCode(),
            default => 500,
        };
    }

    public static function message(Throwable $error): string
    {
        if ($error instanceof ValidationException) return 'Vérifiez les champs indiqués et réessayez.';
        if ($error instanceof \App\Exceptions\OccupationIncompatibleException) return 'Le type d’occupation choisi ne correspond pas à celui du bâtiment ou de la cité.';
        if ($error instanceof QueryException) {
            return match ((int) ($error->errorInfo[1] ?? 0)) {
                1062 => 'Cette information est déjà enregistrée. Vérifiez les données saisies.',
                1451 => 'Cet élément est encore utilisé. Vous ne pouvez pas le supprimer pour le moment.',
                1452 => 'Un élément sélectionné n’est plus disponible. Actualisez la page et réessayez.',
                default => self::messageForStatus(503),
            };
        }
        return self::messageForStatus(self::status($error));
    }

    public static function messageForStatus(int $status): string
    {
        return match ($status) {
            400 => 'La demande ne peut pas être traitée. Vérifiez les informations envoyées.',
            401 => 'Connectez-vous pour continuer. Votre session a peut-être expiré.',
            403 => 'Vous n’avez pas l’autorisation d’effectuer cette action.',
            404, 410 => 'La page ou le document demandé est introuvable ou n’est plus disponible.',
            405 => 'Cette action n’est pas disponible depuis cette page.',
            408, 504 => 'La réponse prend trop de temps. Veuillez réessayer dans quelques instants.',
            409 => 'Les données ont changé ou cette action a déjà été effectuée. Actualisez la page.',
            413 => 'Le fichier envoyé est trop volumineux. Choisissez un fichier plus petit.',
            415 => 'Ce format de fichier ou de demande n’est pas accepté.',
            419 => 'Votre session a expiré. Actualisez la page puis reconnectez-vous si nécessaire.',
            422 => 'Vérifiez les informations saisies et réessayez.',
            429 => 'Vous avez effectué trop de tentatives. Patientez un moment avant de réessayer.',
            502, 503 => 'Le service est temporairement indisponible. Veuillez réessayer plus tard.',
            default => 'Nous n’avons pas pu terminer cette action. Veuillez réessayer dans quelques instants.',
        };
    }

    public static function caught(Throwable $error): string
    {
        report($error);
        if ($error instanceof ValidationException) return collect($error->errors())->flatten()->first() ?: self::message($error);
        return self::message($error);
    }

    public static function render(Throwable $error, Request $request)
    {
        if ($error instanceof \Illuminate\Http\Exceptions\HttpResponseException) return $error->getResponse();
        $json = $request->expectsJson() || $request->is('api', 'api/*');
        // Preserve field errors, old input and normal login redirects.
        if (!$json && ($error instanceof ValidationException || $error instanceof AuthenticationException)) return null;
        $status = self::status($error);
        $message = self::message($error);
        $headers = $error instanceof HttpExceptionInterface ? $error->getHeaders() : [];
        $headers['Cache-Control'] = 'private, no-store';
        // Action envoyée par une page React (Inertia) : retour à la page avec le message en toast (flash `error`).
        if (!$json && $request->header('X-Inertia') && !$request->isMethod('GET')) {
            return back(303)->with('error', $message);
        }
        if ($json) {
            $payload = ['success'=>false, 'message'=>$message];
            if ($error instanceof ValidationException) $payload['errors'] = $error->errors();
            return response()->json($payload, $status, $headers);
        }
        try {
            return response()->view('errors.friendly', compact('status','message'), $status, $headers);
        } catch (Throwable $renderError) {
            report($renderError);
            return response('<!doctype html><html lang="fr"><meta charset="utf-8"><title>Action interrompue</title><h1>Action interrompue</h1><p>'.e($message).'</p><a href="/">Retour à l’accueil</a></html>', $status, $headers);
        }
    }
}
