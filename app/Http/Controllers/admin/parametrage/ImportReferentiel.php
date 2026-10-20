<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Support\SimpleXlsx;
use App\Support\UserFacingErrors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Import en masse d'un référentiel du paramétrage (départements, filières) depuis un .xlsx ou un .csv. */
trait ImportReferentiel
{
    /** Nombre maximal de lignes de données par fichier. */
    private const IMPORT_MAX_LIGNES = 2000;

    /**
     * Lignes du fichier envoyé (`fichier`), aux en-têtes normalisés (« Abréviation » → `abreviation`),
     * accompagnées de leur numéro de ligne dans le fichier.
     *
     * @param  array<string, string>  $alias  en-tête normalisé → clé attendue
     * @param  array<string, string>  $obligatoires  clé exigée dans l'en-tête → libellé de la colonne du modèle
     * @return array<int, array<string, string|null>>
     */
    private function lignesImport(Request $request, array $alias, array $obligatoires): array
    {
        $request->validate([
            // `mimes` inclut zip : un .xlsx produit hors Excel est souvent détecté comme une archive ZIP.
            'fichier' => ['required', 'file', 'extensions:csv,txt,xlsx', 'mimes:csv,txt,xlsx,zip', 'max:5120'],
        ], [
            'fichier.required' => 'Choisissez un fichier .xlsx ou .csv.',
            'fichier.extensions' => 'Formats acceptés : .xlsx ou .csv.',
            'fichier.mimes' => 'Formats acceptés : .xlsx ou .csv.',
            'fichier.max' => 'Le fichier dépasse 5 Mo.',
        ]);

        try {
            $lignes = SimpleXlsx::read($request->file('fichier'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['fichier' => UserFacingErrors::caught($e)]);
        }

        if (! $lignes) {
            throw ValidationException::withMessages(['fichier' => 'Le fichier ne contient aucune ligne de données.']);
        }
        if (count($lignes) > self::IMPORT_MAX_LIGNES) {
            throw ValidationException::withMessages(['fichier' => 'Le fichier dépasse '.self::IMPORT_MAX_LIGNES.' lignes : découpez-le en plusieurs fichiers.']);
        }

        $normaliser = function (array $ligne) use ($alias): array {
            $resultat = [];
            foreach ($ligne as $entete => $valeur) {
                $cle = Str::slug(preg_replace('/^\xEF\xBB\xBF/', '', (string) $entete), '_');
                if ($cle === '') {
                    continue;
                }
                $valeur = trim((string) $valeur);
                $resultat[$alias[$cle] ?? $cle] = $valeur === '' ? null : $valeur;
            }

            return $resultat;
        };

        $manquantes = array_diff_key($obligatoires, $normaliser(array_fill_keys(array_keys($lignes[0]), '')));
        if ($manquantes) {
            throw ValidationException::withMessages(['fichier' => 'Colonne(s) manquante(s) : '.implode(', ', $manquantes).'. Téléchargez le modèle et conservez ses en-têtes.']);
        }

        $resultat = [];
        foreach ($lignes as $index => $ligne) {
            // Ligne 1 = en-têtes.
            $resultat[$index + 2] = $normaliser($ligne);
        }

        return $resultat;
    }

    /** Retour sur la liste : succès simple, ou rapport des lignes refusées affiché dans une fenêtre. */
    private function retourImport(int $crees, int $misAJour, int $inchangees, array $refusees): RedirectResponse
    {
        $message = $crees.' ligne(s) ajoutée(s), '.$misAJour.' mise(s) à jour, '.$inchangees.' déjà à jour.';
        if (! $refusees) {
            return back()->with('success', 'Import terminé : '.$message);
        }

        return back()->with('import_rapport', ['message' => $message, 'lignes' => $refusees]);
    }
}
