<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Request;

/**
 * Numéros de téléphone : exactement 10 chiffres (format ivoirien).
 * La saisie peut contenir des espaces, points, tirets ou l'indicatif +225 : ils sont retirés avant validation
 * et le numéro est enregistré sous la forme « 0700000000 ».
 */
final class Telephone
{
    public const MESSAGE = 'Le numéro de téléphone doit contenir exactement 10 chiffres.';

    /** « +225 07 00 00 00 00 » → « 0700000000 » ; une saisie vide devient null, une saisie non numérique est laissée telle quelle (refusée par la règle). */
    public static function normaliser(mixed $valeur): mixed
    {
        if (is_int($valeur)) {
            $valeur = (string) $valeur;
        }
        if (! is_string($valeur)) {
            return $valeur;
        }
        if (trim($valeur) === '') {
            return null;
        }
        if (! preg_match('/\A[0-9\s+().\-]+\z/', $valeur)) {
            return $valeur;
        }

        $chiffres = preg_replace('/\D+/', '', $valeur);

        return strlen($chiffres) === 13 && str_starts_with($chiffres, '225') ? substr($chiffres, 3) : $chiffres;
    }

    /** Normalise les champs téléphone présents dans la requête, avant `validate()`. */
    public static function normaliserRequete(Request $request, array $champs): void
    {
        $valeurs = [];
        foreach ($champs as $champ) {
            if ($request->exists($champ)) {
                $valeurs[$champ] = self::normaliser($request->input($champ));
            }
        }
        if ($valeurs) {
            $request->merge($valeurs);
        }
    }

    /** Règle de validation : 10 chiffres exactement (à combiner avec `required` ou `nullable`). */
    public static function regle(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (! is_string($value) || ! preg_match('/\A[0-9]{10}\z/', $value)) {
                $fail(self::MESSAGE);
            }
        };
    }
}
