<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\ChambreRepositoryInterface;
use App\Repositories\Interfaces\AssignationChambreRepositoryInterface;
use App\Repositories\ChambreRepository;
use App\Repositories\AssignationChambreRepository;
use App\Http\Services\AllocationChambreService;

class AllocationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Enregistrer les interfaces et leurs implémentations
        $this->app->bind(ChambreRepositoryInterface::class, ChambreRepository::class);
        $this->app->bind(AssignationChambreRepositoryInterface::class, AssignationChambreRepository::class);

        // Enregistrer le service avec ses dépendances
        $this->app->singleton(AllocationChambreService::class, function ($app) {
            return new AllocationChambreService(
                $app->make(ChambreRepositoryInterface::class),
                $app->make(AssignationChambreRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}