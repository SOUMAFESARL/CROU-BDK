<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('sanctions', function (Blueprint $t) {
            $t->string('type', 30)->change();
            if (! Schema::hasColumn('sanctions', 'rapport_id')) {
                $t->string('rapport_id', 150)->nullable()->unique();
            }
        });
        Schema::table('disciplinary_reports', function (Blueprint $t) {
            if (! Schema::hasColumn('disciplinary_reports', 'sanction_decidee')) {
                $t->string('sanction_decidee', 30)->nullable();
            }
        });
        Schema::table('departure_requests', function (Blueprint $t) {
            if (! Schema::hasColumn('departure_requests', 'sanction_id')) {
                $t->string('sanction_id', 150)->nullable()->index();
            }
        });
    }
    public function down(): void {
        Schema::table('departure_requests', fn (Blueprint $t) => $t->dropColumn('sanction_id'));
        Schema::table('disciplinary_reports', fn (Blueprint $t) => $t->dropColumn('sanction_decidee'));
        Schema::table('sanctions', function (Blueprint $t) {
            $t->dropUnique(['rapport_id']);
            $t->dropColumn('rapport_id');
        });
        // Conserver le type texte pour préserver les expulsions déjà enregistrées.
    }
};
