<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (['Equipement','Equipement_mouvement'] as $table) { echo $table.': '.implode(', ', Illuminate\Support\Facades\Schema::getColumnListing($table))."\n"; }
