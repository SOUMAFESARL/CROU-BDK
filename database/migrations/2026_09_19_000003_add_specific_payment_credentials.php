<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_providers', function (Blueprint $table) {
            $table->text('api_user')->nullable();
            $table->text('subscription_key')->nullable();
            $table->text('request_signing_secret')->nullable();
            $table->string('environment', 30)->default('sandbox');
        });
    }

    public function down(): void
    {
        Schema::table('payment_providers', function (Blueprint $table) {
            $table->dropColumn(['api_user', 'subscription_key', 'request_signing_secret', 'environment']);
        });
    }
};
