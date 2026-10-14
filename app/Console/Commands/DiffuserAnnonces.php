<?php
namespace App\Console\Commands;

use App\Models\Annonce;
use App\Services\AnnonceDiffusionService;
use Illuminate\Console\Command;

class DiffuserAnnonces extends Command
{
    protected $signature = 'annonces:diffuser';
    protected $description = 'Publier les annonces arrivées à échéance et transmettre les envois en attente.';

    public function handle(AnnonceDiffusionService $service): int
    {
        Annonce::where('statut', 'planifie')->whereNotNull('date_planification')
            ->where('date_planification', '<=', now())->each(fn ($annonce) => $annonce->publier());
        Annonce::actif()->whereNull('diffusion_preparee_at')
            ->each(fn ($annonce) => $service->prepare($annonce->id_annonce));
        $service->sendPending(null, 100);
        $this->info('Traitement des annonces terminé.');
        return self::SUCCESS;
    }
}
