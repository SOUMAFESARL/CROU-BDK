<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sanctions', function (Blueprint $table) {
            if (! Schema::hasColumn('sanctions', 'scope_type')) {
                $table->string('scope_type', 20)->default('etudiant')->after('numero_sanction');
            }
            if (! Schema::hasColumn('sanctions', 'batiment_id')) {
                $table->string('batiment_id', 150)->nullable()->after('cite_id')->index();
            }
            if (! Schema::hasColumn('sanctions', 'chambre_id')) {
                $table->string('chambre_id', 150)->nullable()->after('batiment_id')->index();
            }
            $table->string('etudiant_id', 150)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sanctions', function (Blueprint $table) {
            $table->dropIndex(['batiment_id']);
            $table->dropIndex(['chambre_id']);
            $table->dropColumn(['scope_type', 'batiment_id', 'chambre_id']);
            $table->string('etudiant_id', 150)->nullable(false)->change();
        });
    }
};
