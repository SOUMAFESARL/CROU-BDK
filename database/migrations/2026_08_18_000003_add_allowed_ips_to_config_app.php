<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { if (!Schema::hasColumn('config_app', 'allowed_ips')) Schema::table('config_app', fn (Blueprint $table) => $table->text('allowed_ips')->nullable()->after('ip_restriction')); }
    public function down(): void { if (Schema::hasColumn('config_app', 'allowed_ips')) Schema::table('config_app', fn (Blueprint $table) => $table->dropColumn('allowed_ips')); }
};
