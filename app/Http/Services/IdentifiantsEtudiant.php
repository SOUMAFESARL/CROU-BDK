<?php
namespace App\Http\Services;

use App\Models\Etudiant;
use App\Notifications\EtudiantIdentifiantsNotification;
use App\Services\AnnonceDiffusionService;
use Illuminate\Support\Facades\{Hash,Notification};
use Illuminate\Support\Str;

/**
 * Création et envoi du mot de passe de l'espace étudiant à l'e-mail saisi dans la demande (`email1`).
 * Plus d'envoi à la validation ni à la publication (25/11/2026) : il est prévu après le paiement de la caution et de
 * l'avance d'une nouvelle demande d'admission. Seul un brouillon reçoit encore ses identifiants à l'inscription.
 * Un compte qui possède déjà un mot de passe n'est jamais modifié (réadmission).
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
        $motDePasse = $this->generer();
        try {
            // Même transport que les autres e-mails : SMTP du paramétrage, et pas seulement celui du .env.
            app(AnnonceDiffusionService::class)->configureMail();
            Notification::route('mail', $etudiant->email1)
                ->notify(new EtudiantIdentifiantsNotification($etudiant->email1, $motDePasse, route('etudiant.login'), ...array_filter([$introduction])));
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
        $this->enregistrer($etudiant, $motDePasse);
        return true;
    }

    public function generer(): string
    {
        return Str::password(10, symbols: false);
    }

    /** À appeler une fois le mot de passe transmis à l'étudiant. */
    public function enregistrer(Etudiant $etudiant, string $motDePasse): void
    {
        $etudiant->forceFill(['password' => Hash::make($motDePasse)])->save();
    }
}
