<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departure_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('notice_days')->default(30);
            $table->decimal('late_notice_penalty', 12, 2)->default(0);
            $table->decimal('unpaid_blocking_threshold', 12, 2)->default(0);
            $table->json('damage_retention_grid')->nullable();
            $table->string('updated_by', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('departure_settings'); }
};
