<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_api_tokens')) {
            Schema::create('student_api_tokens', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('etudiant_id', 150)->index();
                $table->string('name')->default('mobile');
                $table->string('token_hash', 64)->unique();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_api_tokens');
    }
};
