<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use App\Models\AssignationChambre;
use App\Observers\AssignationChambreObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    // ...

    public function boot()
    {
        AssignationChambre::observe(AssignationChambreObserver::class);
    }
}