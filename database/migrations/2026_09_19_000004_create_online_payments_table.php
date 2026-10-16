<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_providers', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_providers', 'webhook_secret')) {
                $table->text('webhook_secret')->nullable();
            }
        });
        if (! Schema::hasTable('online_payments')) {
            Schema::create('online_payments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('reference', 40)->unique();
                $table->unsignedBigInteger('provider_id')->index();
                $table->string('provider_code', 30);
                $table->string('student_id', 150)->index();
                $table->string('loyer_id', 150)->index();
                $table->unsignedBigInteger('amount');
                $table->string('currency', 3)->default('XOF');
                $table->string('status', 30)->default('initializing')->index();
                $table->string('provider_reference', 100)->nullable();
                $table->text('checkout_url')->nullable();
                $table->text('credentials');
                $table->string('callback_token_hash', 64);
                $table->string('paiement_id', 150)->nullable()->unique();
                $table->string('last_error', 100)->nullable();
                $table->timestamp('next_check_at')->nullable()->index();
                $table->timestamp('notified_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                $table->unique(['provider_id', 'provider_reference']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('online_payments');
        Schema::table('payment_providers', fn (Blueprint $table) => $table->dropColumn('webhook_secret'));
    }
};
