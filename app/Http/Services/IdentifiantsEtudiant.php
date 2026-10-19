<?php
namespace App\Http\Services;

use App\Models\Etudiant;
use App\Notifications\EtudiantIdentifiantsNotification;
use Illuminate\Support\Facades\{Hash,Notification};
use Illuminate\Support\Str;

/**
 * Création et envoi du mot de passe de l'espace étudiant à l'e-mail saisi dans la demande (`email1`).
 * Un compte qui possède déjà un mot de passe n'est jamais modifié (réadmission, second passage en « Éligible »).
 */
class IdentifiantsEtudiant
{
    public function aEnvoyer(Etudiant $etudiant): bool
    {
        return blank($etudiant->getRawOriginal('password')) && filled($etudiant->email1);
    }

    /** Envoie les identifiants ; le mot de passe n'est enregistré que si l'e-mail est parti. */
    public function envoyer(Etudiant $etudiant, ?string $introduction = null): bool
    {
        if (!$this->aEnvoyer($etudiant)) return false;
        $motDePasse = Str::password(10, symbols: false);
        try {
            Notification::route('mail', $etudiant->email1)
                ->notify(new EtudiantIdentifiantsNotification($etudiant->email1, $motDePasse, route('etudiant.login'), ...array_filter([$introduction])));
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
        $etudiant->forceFill(['password' => Hash::make($motDePasse)])->save();
        return true;
    }
}
