<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('payments:reconcile')->everyMinute()->withoutOverlapping();


\Illuminate\Support\Facades\Schedule::command('annonces:diffuser')->everyMinute()->withoutOverlapping();

\Illuminate\Support\Facades\Schedule::command('hebergement:entrees')->everyMinute()->withoutOverlapping();

\Illuminate\Support\Facades\Schedule::command('dossiers:validation-auto')->everyMinute()->withoutOverlapping();
