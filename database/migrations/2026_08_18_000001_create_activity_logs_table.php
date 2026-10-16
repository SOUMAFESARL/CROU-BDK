<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('actor_type', 30)->nullable()->index();
                $table->string('actor_id', 150)->nullable()->index();
                $table->string('actor_name', 255)->nullable();
                $table->string('action', 20);
                $table->string('method', 10);
                $table->string('route_name', 255)->nullable()->index();
                $table->string('controller_action', 500)->nullable();
                $table->text('url');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->unsignedSmallInteger('status_code')->nullable();
                $table->json('context')->nullable();
                $table->timestamps();

                $table->index(['created_at', 'action']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
