<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Résultats figés à la publication des listes définitives (`ResultatsAdmission`), consultés par les étudiants sur
// `/resultats` : un résultat par demande (`admis` | `recevable_non_admis` | `non_recevable`).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('resultats_admission')) {
            Schema::create('resultats_admission', function (Blueprint $t) {
                $t->string('id', 36)->primary();
                $t->string('campagne_id', 36)->index();
                $t->string('demande_id', 36)->unique();
                $t->string('etudiant_id', 36)->index();
                $t->string('volet', 16);
                $t->string('resultat', 32);
                $t->dateTime('publie_at')->index();
                $t->timestamps();
            });
        }

        // Aucun identifiant n'est plus envoyé à la validation d'une demande : les envois encore en file sont retirés.
        if (Schema::hasTable('jobs')) {
            DB::table('jobs')->where('payload', 'like', '%EnvoyerIdentifiantsEtudiant%')->delete();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resultats_admission');
    }
};
