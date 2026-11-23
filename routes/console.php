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

\Illuminate\Support\Facades\Schedule::command('admissions:notifier')->everyMinute()->withoutOverlapping(30);

\Illuminate\Support\Facades\Schedule::command('hebergement:entrees')->everyMinute()->withoutOverlapping();

\Illuminate\Support\Facades\Schedule::command('dossiers:validation-auto')->everyMinute()->withoutOverlapping();
