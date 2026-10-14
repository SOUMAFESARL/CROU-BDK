<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('cite', 'plan')) {
            Schema::table('cite', function (Blueprint $table) {
                $table->string('plan', 500)->nullable()->after('email2');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cite', 'plan')) {
            Schema::table('cite', function (Blueprint $table) {
                $table->dropColumn('plan');
            });
        }
    }
};
