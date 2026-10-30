<?php

namespace App\Jobs;

use App\Http\Services\IdentifiantsEtudiant;
use App\Models\Etudiant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Envoi des identifiants de l'espace étudiant hors de la requête : un serveur SMTP lent ne doit pas bloquer
 * la soumission de la demande (dépassement des 30 s de PHP, sans réponse ni message de confirmation).
 */
class EnvoyerIdentifiantsEtudiant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(public string $etudiantId) {}

    public function handle(IdentifiantsEtudiant $identifiants): void
    {
        $etudiant = Etudiant::find($this->etudiantId);
        if (!$etudiant || !$identifiants->aEnvoyer($etudiant)) return;
        // Échec d'envoi (SMTP indisponible) : nouvel essai plus tard, le mot de passe n'ayant pas été enregistré.
        if (!$identifiants->envoyer($etudiant) && $this->attempts() < $this->tries) $this->release(120);
    }
}
