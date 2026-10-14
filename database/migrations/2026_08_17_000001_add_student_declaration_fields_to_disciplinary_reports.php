<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('disciplinary_reports', function (Blueprint $table) {
            $table->string('declarant_id', 150)->nullable()->index()->after('etudiant_id');
            $table->string('source', 30)->default('administration')->after('declarant_id');
            $table->boolean('confidentiel')->default(false)->after('temoins');
        });
    }

    public function down(): void
    {
        Schema::table('disciplinary_reports', function (Blueprint $table) {
            $table->dropIndex(['declarant_id']);
            $table->dropColumn(['declarant_id', 'source', 'confidentiel']);
        });
    }
};
