<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            if (! Schema::hasColumn('etudiants', 'pmr_confirme')) {
                $table->boolean('pmr_confirme')->default(false);
            }
            if (! Schema::hasColumn('etudiants', 'pmr_confirme_by')) {
                $table->string('pmr_confirme_by')->nullable();
            }
            if (! Schema::hasColumn('etudiants', 'pmr_confirme_at')) {
                $table->timestamp('pmr_confirme_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('etudiants', fn (Blueprint $table) => $table->dropColumn([
            'pmr_confirme', 'pmr_confirme_by', 'pmr_confirme_at',
        ]));
    }
};
