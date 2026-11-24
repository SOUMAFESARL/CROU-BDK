<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Mise en forme du message (éditeur Tiptap) pour l'envoi par e-mail ; `body` reste la version texte, envoyée par SMS. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('admission_result_deliveries', 'body_html')) {
            Schema::table('admission_result_deliveries', fn (Blueprint $table) => $table->longText('body_html')->nullable()->after('body'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('admission_result_deliveries', 'body_html')) {
            Schema::table('admission_result_deliveries', fn (Blueprint $table) => $table->dropColumn('body_html'));
        }
    }
};
