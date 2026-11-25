<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Réclamations : la décision « Ouverte » est retirée, les réclamations concernées redeviennent « En attente ». Idempotente. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reclamations')) {
            DB::table('reclamations')->where('statut', 'ouverte')->update(['statut' => 'en_attente']);
        }
    }

    public function down(): void
    {
        // Le statut « Ouverte » n'existe plus.
    }
};
