<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('config_app', 'address')) {
            Schema::table('config_app', function (Blueprint $table) {
                $table->string('address', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('config_app', fn (Blueprint $table) => $table->dropColumn('address'));
    }
};