<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** La date de naissance n'est plus demandée dans le formulaire de réclamation (conservée pour les réclamations antérieures). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('reclamations', 'date_naissance')) {
            Schema::table('reclamations', fn (Blueprint $table) => $table->date('date_naissance')->nullable()->change());
        }
    }

    /** La colonne reste facultative : les réclamations déposées entre-temps n'ont pas de date de naissance. */
    public function down(): void {}
};
