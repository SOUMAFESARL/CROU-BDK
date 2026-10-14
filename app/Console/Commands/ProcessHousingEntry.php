<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Http\Services\HousingEntryJourney;
class ProcessHousingEntry extends Command {
    protected $signature='hebergement:entrees {--prepare-only : Préparer sans transmettre les e-mails}';
    protected $description='Préparer les annonces, propositions, contrats et rendez-vous d’entrée.';
    public function handle(HousingEntryJourney $journey): int {
        $journey->prepare();
        if (!$this->option('prepare-only')) $journey->sendPending();
        $this->info('Parcours d’entrée traité.');
        return self::SUCCESS;
    }
}
