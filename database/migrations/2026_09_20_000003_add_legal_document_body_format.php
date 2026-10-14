<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('legal_documents', fn (Blueprint $t) => $t->string('body_format', 10)->default('text')); }
    public function down(): void { Schema::table('legal_documents', fn (Blueprint $t) => $t->dropColumn('body_format')); }
};
