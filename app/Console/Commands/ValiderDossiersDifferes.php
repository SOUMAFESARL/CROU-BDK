<?php
namespace App\Console\Commands;

use App\Http\Services\DecisionAutomatiqueDossier;
use Illuminate\Console\Command;

class ValiderDossiersDifferes extends Command
{
    protected $signature = 'dossiers:validation-auto';
    protected $description = 'Décider (barème) les dossiers soumis des campagnes dont la date de validation automatique est atteinte.';

    public function handle(DecisionAutomatiqueDossier $decision): int
    {
        $this->info($decision->traiterValidationsDifferees().' dossier(s) décidé(s) automatiquement.');
        return self::SUCCESS;
    }
}
