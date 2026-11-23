<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Types de paiement paramétrables (Paramétrage hébergement › Types de paiement), proposés dans « Nouveau paiement ».
 * Le `code` est la valeur enregistrée dans `paiements.type_paiement` ; les quatre types historiques sont des types
 * « système » (utilisés par le code : loyers, cautions de départ, paiements en ligne) : ni supprimables ni désactivables.
 */
return new class extends Migration
{
    private const SYSTEME = [
        ['code' => 'loyer', 'name' => 'Loyer', 'description' => 'Loyer mensuel du logement.', 'ordre' => 1],
        ['code' => 'caution', 'name' => 'Caution', 'description' => 'Dépôt de garantie.', 'ordre' => 2],
        ['code' => 'penalite', 'name' => 'Pénalité', 'description' => 'Pénalité de retard ou sanction financière.', 'ordre' => 3],
        ['code' => 'autre', 'name' => 'Autre', 'description' => 'Paiement d’une autre nature.', 'ordre' => 99],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('types_paiement')) {
            Schema::create('types_paiement', function (Blueprint $table) {
                $table->string('id_type_paiement', 36)->primary();
                $table->string('code', 50)->unique();
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->boolean('actif')->default(true);
                $table->boolean('systeme')->default(false);
                $table->unsignedInteger('ordre')->default(50);
                $table->timestamps();
            });
        }

        foreach (self::SYSTEME as $type) {
            if (! DB::table('types_paiement')->where('code', $type['code'])->exists()) {
                DB::table('types_paiement')->insert($type + [
                    'id_type_paiement' => (string) Str::uuid(),
                    'actif' => true,
                    'systeme' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // L'énumération figée de la colonne devient un texte libre : elle reçoit le code des nouveaux types.
        if (Schema::hasTable('paiements') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE paiements MODIFY type_paiement VARCHAR(50) NOT NULL DEFAULT 'loyer' COMMENT 'Code du type de paiement (types_paiement.code)'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('paiements') && DB::getDriverName() === 'mysql') {
            DB::table('paiements')->whereNotIn('type_paiement', ['loyer', 'caution', 'penalite', 'autre'])->update(['type_paiement' => 'autre']);
            DB::statement("ALTER TABLE paiements MODIFY type_paiement ENUM('loyer','caution','penalite','autre') NOT NULL DEFAULT 'loyer' COMMENT 'Type de paiement'");
        }

        Schema::dropIfExists('types_paiement');
    }
};
