<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cautions', function (Blueprint $table) {
            $table->string('paiement_encaissement_id', 150)->nullable()->change();
        });
    }

    public function down(): void
    {
        $references = DB::table('cautions')->whereNotNull('paiement_encaissement_id');

        foreach ($references->select('paiement_encaissement_id')->cursor() as $caution) {
            if (!ctype_digit((string) $caution->paiement_encaissement_id)) {
                throw new RuntimeException('Une reference UUID empeche le retour au type numerique.');
            }
        }

        Schema::table('cautions', function (Blueprint $table) {
            $table->unsignedBigInteger('paiement_encaissement_id')->nullable()->change();
        });
    }
};
