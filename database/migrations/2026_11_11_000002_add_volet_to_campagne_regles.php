<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Critères d'éligibilité : un jeu de règles par volet (admission / réadmission), versionné séparément.
 * Les versions enregistrées avant ce découpage (un jeu commun aux deux volets) deviennent la version
 * de réadmission et sont dupliquées pour l'admission ; chaque ligne de places ne garde que les places de son volet.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campagne_regles') || Schema::hasColumn('campagne_regles', 'volet')) {
            return;
        }

        Schema::table('campagne_regles', function (Blueprint $t) {
            $t->string('volet', 20)->default('readmission')->after('campagne_id');
        });
        Schema::table('campagne_regles', function (Blueprint $t) {
            $t->dropUnique(['campagne_id', 'version']);
            $t->dropIndex(['campagne_id', 'statut']);
        });
        Schema::table('campagne_regles', function (Blueprint $t) {
            $t->unique(['campagne_id', 'volet', 'version']);
            $t->index(['campagne_id', 'volet', 'statut']);
        });

        $places = function (?string $json, string $volet): ?string {
            if ($json === null) {
                return null;
            }

            return json_encode(collect(json_decode($json, true) ?: [])->map(fn (array $ligne) => [
                'cite_id' => $ligne['cite_id'] ?? null,
                'cite' => $ligne['cite'] ?? null,
                'public' => $ligne['public'] ?? null,
                'capacite' => $ligne['capacite'] ?? 0,
                'places' => $ligne[$volet] ?? null,
            ] + ($volet === 'readmission' ? ['quotas' => $ligne['quotas'] ?? []] : []))->values()->all());
        };

        foreach (DB::table('campagne_regles')->get() as $ligne) {
            DB::table('campagne_regles')->where('id', $ligne->id)->update(['places' => $places($ligne->places, 'readmission')]);
            $copie = (array) $ligne;
            $copie['id'] = (string) Str::uuid();
            $copie['volet'] = 'admission';
            $copie['places'] = $places($ligne->places, 'admission');
            DB::table('campagne_regles')->insert($copie);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('campagne_regles') || ! Schema::hasColumn('campagne_regles', 'volet')) {
            return;
        }
        DB::table('campagne_regles')->where('volet', 'admission')->delete();
        Schema::table('campagne_regles', function (Blueprint $t) {
            $t->dropUnique(['campagne_id', 'volet', 'version']);
            $t->dropIndex(['campagne_id', 'volet', 'statut']);
        });
        Schema::table('campagne_regles', function (Blueprint $t) {
            $t->dropColumn('volet');
            $t->unique(['campagne_id', 'version']);
            $t->index(['campagne_id', 'statut']);
        });
    }
};
