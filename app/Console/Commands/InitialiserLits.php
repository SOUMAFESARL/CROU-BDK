<?php

namespace App\Console\Commands;

use App\Http\Services\LitService;
use App\Models\Chambre;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class InitialiserLits extends Command
{
    protected $signature = 'lits:initialiser';
    protected $description = 'Numéroter les lits et rattacher les attributions actives existantes';

    public function handle(LitService $service): int
    {
        $count = 0;
        $errors = 0;
        foreach (Chambre::whereNotNull('numero_chambre')->lazyById(100, 'id_chambre') as $room) {
            try {
                $service->synchroniser($room);
                $count++;
            } catch (ValidationException $e) {
                $errors++;
                $this->warn('Chambre '.$room->getKey().' : '.$e->getMessage());
            }
        }
        $this->info("Chambres initialisées : $count. Chambres à régulariser : $errors.");
        return $errors ? self::FAILURE : self::SUCCESS;
    }
}
