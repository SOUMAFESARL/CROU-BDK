<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('payments:reconcile')->everyMinute()->withoutOverlapping();


\Illuminate\Support\Facades\Schedule::command('annonces:diffuser')->everyMinute()->withoutOverlapping();

Artisan::command('admissions:notifier {--watch : Traiter les résultats chaque minute}', function (\App\Services\AdmissionResultDiffusion $diffusion) {
    $diffusion->sendPending();
    while ($this->option('watch')) {
        sleep(60);
        $diffusion->sendPending();
    }
})->purpose('Transmettre les notifications des résultats publiés.');

// `admissions:notifier` n'est plus planifiée (25/11/2026) : la publication n'envoie plus de message, les étudiants
// consultent leur résultat sur la plateforme (`/resultats`).

Artisan::command('admissions:appliquer-publications', function (\App\Http\Services\Eligibilite\ResultatsAdmission $resultats) {
    $bilan = $resultats->appliquerPublications();
    $this->info($bilan['admis'].' étudiant(s) passé(s) dans « Admis à loger » ; résultats consultables sur /resultats.');
    foreach ($bilan['bloques'] as $motif) {
        $this->warn($motif);
    }
})->purpose('Appliquer les publications de listes définitives antérieures (résultats et « Admis à loger »).');

\Illuminate\Support\Facades\Schedule::command('hebergement:entrees')->everyMinute()->withoutOverlapping();

\Illuminate\Support\Facades\Schedule::command('dossiers:validation-auto')->everyMinute()->withoutOverlapping();
