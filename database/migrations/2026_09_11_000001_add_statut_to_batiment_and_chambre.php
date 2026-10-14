<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('batiment', 'statut')) {
            Schema::table('batiment', function (Blueprint $table) {
                $table->string('statut', 20)->default('actif')->after('cite_id');
            });
        }

        if (!Schema::hasColumn('chambre', 'statut')) {
            Schema::table('chambre', function (Blueprint $table) {
                $table->string('statut', 20)->default('actif')->after('batiment_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('batiment', 'statut')) {
            Schema::table('batiment', function (Blueprint $table) {
                $table->dropColumn('statut');
            });
        }

        if (Schema::hasColumn('chambre', 'statut')) {
            Schema::table('chambre', function (Blueprint $table) {
                $table->dropColumn('statut');
            });
        }
    }
};
