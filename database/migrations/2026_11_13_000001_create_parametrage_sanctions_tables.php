<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Paramétrage des sanctions (docs/parametrage-sanctions.md) :
 * - `types_sanction` : sanctions utilisées dans tout le système (décisions d'incidents, sanctions directes),
 *   avec le nombre de points retirés à la note de conduite et leur effet (aucun, suspension, expulsion) ;
 * - `parametres_conduite` : note de conduite initiale de chaque étudiant (ligne unique) ;
 * - `sanctions.points_retires` : points retirés au moment de la sanction (un barème modifié ensuite ne réécrit pas l'historique).
 * Les types existants (avertissement, blâme, suspension, expulsion) sont repris avec leurs codes.
 */
return new class extends Migration
{
    private const TYPES = [
        ['code' => 'avertissement', 'libelle' => 'Avertissement', 'points_retires' => 2, 'effet' => 'aucun'],
        ['code' => 'blame', 'libelle' => 'Blâme', 'points_retires' => 5, 'effet' => 'aucun'],
        ['code' => 'suspension', 'libelle' => 'Suspension', 'points_retires' => 10, 'effet' => 'suspension'],
        ['code' => 'expulsion', 'libelle' => 'Expulsion', 'points_retires' => 20, 'effet' => 'expulsion'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('types_sanction')) {
            Schema::create('types_sanction', function (Blueprint $table) {
                $table->uuid('id_type_sanction')->primary();
                $table->string('code', 30)->unique();
                $table->string('libelle', 100);
                $table->decimal('points_retires', 5, 2)->default(0);
                $table->string('effet', 20)->default('aucun');
                $table->text('description')->nullable();
                $table->boolean('actif')->default(true);
                $table->string('created_by', 150)->nullable();
                $table->string('updated_by', 150)->nullable();
                $table->timestamps();
            });
        }

        foreach (self::TYPES as $type) {
            if (! DB::table('types_sanction')->where('code', $type['code'])->exists()) {
                DB::table('types_sanction')->insert($type + [
                    'id_type_sanction' => (string) Str::uuid(),
                    'actif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (! Schema::hasTable('parametres_conduite')) {
            Schema::create('parametres_conduite', function (Blueprint $table) {
                $table->id();
                $table->decimal('note_initiale', 5, 2)->default(20);
                $table->string('updated_by', 150)->nullable();
                $table->timestamps();
            });
        }
        if (! DB::table('parametres_conduite')->exists()) {
            DB::table('parametres_conduite')->insert(['id' => 1, 'note_initiale' => 20, 'created_at' => now(), 'updated_at' => now()]);
        }

        if (Schema::hasTable('sanctions') && ! Schema::hasColumn('sanctions', 'points_retires')) {
            Schema::table('sanctions', function (Blueprint $table) {
                $table->decimal('points_retires', 5, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sanctions', 'points_retires')) {
            Schema::table('sanctions', fn (Blueprint $table) => $table->dropColumn('points_retires'));
        }
        Schema::dropIfExists('parametres_conduite');
        Schema::dropIfExists('types_sanction');
    }
};
