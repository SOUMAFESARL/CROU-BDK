<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_result_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('campagne_id', 36);
            $table->string('demande_id', 36);
            $table->string('resultat', 24);
            $table->string('channel', 8);
            $table->string('destination', 254)->nullable();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('status', 20);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['demande_id', 'resultat', 'channel'], 'admission_result_unique');
            $table->index(['status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_result_deliveries');
    }
};
