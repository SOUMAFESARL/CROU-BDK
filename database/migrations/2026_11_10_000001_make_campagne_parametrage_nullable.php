<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Public et critères ne sont plus saisis à la création de la campagne : NULL = non paramétré.
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('campagne')) {
            return;
        }

        Schema::table('campagne', function (Blueprint $t) {
            $t->tinyInteger('is_discipline')->nullable()->default(1)->change();
            $t->tinyInteger('is_participate')->nullable()->default(1)->change();
            $t->integer('pourcentage_validate')->nullable()->default(0)->change();
            foreach (['L3', 'M1', 'M2', 'D1', 'D2', 'D3'] as $niveau) {
                $t->integer("age_{$niveau}_max")->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Pas de retour en NOT NULL : des campagnes non paramétrées peuvent déjà exister.
    }
};
