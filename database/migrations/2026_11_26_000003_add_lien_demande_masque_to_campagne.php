<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// Masque le lien « Faire une demande d'hébergement » de la page de connexion étudiant pendant la période
// d'admission, sans toucher aux dates de la campagne (bouton du panneau « Détail de la campagne »).
return new class extends Migration {
    public function up(): void {
        Schema::table('campagne', function (Blueprint $t) {
            if (!Schema::hasColumn('campagne', 'lien_demande_masque')) $t->boolean('lien_demande_masque')->default(false);
        });
    }
    public function down(): void {
        Schema::table('campagne', function (Blueprint $t) {
            if (Schema::hasColumn('campagne', 'lien_demande_masque')) $t->dropColumn('lien_demande_masque');
        });
    }
};
