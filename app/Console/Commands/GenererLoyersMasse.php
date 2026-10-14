<?php
// app/Console/Commands/GenererLoyersMasse.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\LoyerService;

class GenererLoyersMasse extends Command
{
    protected $signature = 'loyers:generer 
                            {--cite= : ID de la cité spécifique}
                            {--annee= : Année universitaire}';
    
    protected $description = 'Génère automatiquement les loyers pour tous les étudiants assignés';

    public function handle(LoyerService $loyerService)
    {
        $this->info('Début de la génération des loyers...');
        
        $resultats = $loyerService->genererLoyersMasse(
            $this->option('cite'),
            $this->option('annee')
        );

        $this->table(
            ['Statut', 'Nombre'],
            [
                ['✅ Succès', $resultats['succes']],
                ['❌ Échecs', $resultats['echecs']],
            ]
        );

        if ($resultats['echecs'] > 0) {
            $this->error('Détails des échecs :');
            foreach ($resultats['details'] as $detail) {
                $this->line("Assignation {$detail['assignation']} : {$detail['erreur']}");
            }
        }

        $this->info('Génération terminée !');
    }
}