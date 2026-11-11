<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le barème de score n'est plus paramétrable : les critères d'admission et de réadmission définissent à eux seuls
 * l'éligibilité et le classement (docs/notes/eligibilite-criteres-campagne.md). Les barèmes enregistrés sont désactivés :
 * plus aucun refus automatique par score ; sans barème actif, les dossiers soumis restent « Soumis » pour une décision
 * manuelle (DecisionAutomatiqueDossier) en attendant la liste provisoire. L'historique des barèmes est conservé.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campaign_scorings')) {
            DB::table('campaign_scorings')->where('active', true)->update(['active' => false]);
        }
    }

    public function down(): void
    {
        // Irréversible : les barèmes actifs avant la migration ne sont pas connus.
    }
};
