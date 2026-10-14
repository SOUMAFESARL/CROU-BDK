<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Services\CampagneService;
use App\Http\Services\LoyerService;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Vérification quotidienne des loyers en retard et application des pénalités
        $schedule->call(function () {
            app(LoyerService::class)->verifierEtMettreAJourStatuts();
        })->dailyAt('00:05');

        // Génération automatique des loyers au début de l'année universitaire
        $schedule->command('loyers:generer')->cron('0 0 1 9 *'); // 1er septembre à minuit

        // Rappel des échéances à venir (3 jours avant)
        $schedule->call(function () {
            app(LoyerService::class)->envoyerRappelsEcheances();
        })->dailyAt('08:00');

        // Nettoyage des loyers annulés (tous les mois)
        $schedule->command('loyers:cleanup')->monthlyOn(1, '03:00');

        // Rejet automatique des demandes non résidentes après échéance des contrats de campagne
        $schedule->call(function () {
            app(CampagneService::class)->rejeterDemandesCampagneExpiree();
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}