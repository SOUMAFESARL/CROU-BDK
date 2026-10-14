<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            $table->boolean('pmr_confirme')->default(false);
            $table->string('pmr_confirme_by')->nullable();
            $table->timestamp('pmr_confirme_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('etudiants', fn (Blueprint $table) => $table->dropColumn([
            'pmr_confirme', 'pmr_confirme_by', 'pmr_confirme_at',
        ]));
    }
};
