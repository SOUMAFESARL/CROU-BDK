<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('payment_providers')) {
            return;
        }

        if (! Schema::hasTable('payment_providers')) {
            Schema::create('payment_providers', function (Blueprint $table) {
                $table->id();
                $table->string('code', 30)->unique();
                $table->string('name', 60);
                $table->string('api_url', 2048)->nullable();
                $table->text('api_key')->nullable();
                $table->text('merchant_key')->nullable();
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
        }

        foreach (['moov' => 'Moov', 'orange' => 'Orange', 'mtn' => 'MTN', 'wave' => 'Wave', 'cinetpay' => 'CinetPay', 'tresorpay' => 'TrésorPay'] as $code => $name) {
            DB::table('payment_providers')->insert([
                'code' => $code, 'name' => $name, 'is_active' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_providers');
    }
};
