<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
// Ordre d'affichage des niveaux d'étude (paramétrage, formulaires de demande, réadmission, API) : 1, 2, 3…
// Les niveaux existants sont numérotés dans l'ordre alphabétique, l'ancien tri.
return new class extends Migration {
    public function up(): void {
        if (Schema::hasColumn('niveau_etude', 'ordre')) return;
        Schema::table('niveau_etude', function (Blueprint $t) {
            $t->unsignedInteger('ordre')->default(0)->after('description');
        });
        DB::table('niveau_etude')->orderBy('name')->pluck('id_niveau_etude')
            ->each(fn ($id, $i) => DB::table('niveau_etude')->where('id_niveau_etude', $id)->update(['ordre' => $i + 1]));
    }
    public function down(): void {
        if (!Schema::hasColumn('niveau_etude', 'ordre')) return;
        Schema::table('niveau_etude', function (Blueprint $t) { $t->dropColumn('ordre'); });
    }
};
