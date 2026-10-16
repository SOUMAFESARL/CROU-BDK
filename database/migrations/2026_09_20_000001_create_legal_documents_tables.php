<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('legal_signatories')) {
            Schema::create('legal_signatories', function (Blueprint $t) {
                $t->id();
                $t->string('role_code', 40)->unique();
                $t->string('full_name', 150)->nullable();
                $t->string('signature_path')->nullable();
                $t->boolean('is_active')->default(false);
                $t->string('updated_by', 150)->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('legal_documents')) {
            Schema::create('legal_documents', function (Blueprint $t) {
                $t->id();
                $t->string('type', 40);
                $t->string('title', 200);
                $t->string('reference', 100)->nullable();
                $t->string('version_label', 30)->default('1.0');
                $t->string('scope_label', 150)->nullable();
                $t->string('institution', 200);
                $t->string('academic_year', 30)->nullable();
                $t->string('location', 150)->nullable();
                $t->string('contact', 255)->nullable();
                $t->date('effective_date')->nullable();
                $t->longText('body');
                $t->json('schedule')->nullable();
                $t->json('signatory_roles');
                $t->string('status', 20)->default('draft');
                $t->unsignedInteger('lock_version')->default(1);
                $t->unsignedBigInteger('published_version_id')->nullable();
                $t->string('created_by', 150)->nullable();
                $t->string('updated_by', 150)->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('legal_document_versions')) {
            Schema::create('legal_document_versions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('legal_document_id')->constrained('legal_documents')->cascadeOnDelete();
                $t->unsignedInteger('revision');
                $t->longText('snapshot');
                $t->string('created_by', 150)->nullable();
                $t->timestamps();
                $t->unique(['legal_document_id', 'revision']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_versions');
        Schema::dropIfExists('legal_documents');
        Schema::dropIfExists('legal_signatories');
    }
};
