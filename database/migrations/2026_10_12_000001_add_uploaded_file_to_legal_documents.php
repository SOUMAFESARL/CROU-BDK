<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('legal_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('legal_documents', 'file_path')) {
                $table->string('file_path')->nullable();
            }
            if (! Schema::hasColumn('legal_documents', 'file_name')) {
                $table->string('file_name')->nullable();
            }
        });
    }
    public function down(): void {
        Schema::table('legal_documents', fn (Blueprint $table) => $table->dropColumn(['file_path', 'file_name']));
    }
};
