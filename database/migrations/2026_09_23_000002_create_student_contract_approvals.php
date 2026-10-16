<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (! Schema::hasTable('student_contract_approvals')) {
            Schema::create('student_contract_approvals', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->string('assignment_id')->unique();
                $t->string('student_id')->index();
                $t->string('student_name');
                $t->longText('document_html');
                $t->string('document_hash', 64);
                $t->text('consent_text')->nullable();
                $t->timestamp('approved_at')->nullable();
                $t->timestamps();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('student_contract_approvals'); }
};
