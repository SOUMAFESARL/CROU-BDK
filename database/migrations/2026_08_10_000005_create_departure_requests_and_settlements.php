<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('departure_requests', function(Blueprint $t){$t->uuid('id_demande_sortie')->primary();$t->string('etudiant_id',150)->index();$t->string('assignation_id',150)->index();$t->string('chambre_id',150)->index();$t->date('date_demande');$t->date('date_sortie_prevue');$t->string('motif',100);$t->text('observations')->nullable();$t->enum('statut',['en_attente','cloturee','annulee'])->default('en_attente');$t->string('created_by',150)->nullable();$t->timestamps();});
        Schema::create('departure_settlements', function(Blueprint $t){$t->uuid('id_decompte')->primary();$t->uuid('demande_sortie_id')->nullable()->index();$t->string('etat_des_lieux_id',150)->index();$t->string('etudiant_id',150)->index();$t->string('chambre_id',150)->index();$t->unsignedInteger('preavis_requis_jours')->default(0);$t->unsignedInteger('preavis_effectif_jours')->default(0);$t->decimal('penalite_preavis',12,2)->default(0);$t->json('retenues_degradations')->nullable();$t->decimal('total_degradations',12,2)->default(0);$t->decimal('total_retenues',12,2)->default(0);$t->decimal('impute_caution',12,2)->default(0);$t->decimal('solde_debiteur',12,2)->default(0);$t->string('created_by',150)->nullable();$t->timestamps();});
    }
    public function down(): void {Schema::dropIfExists('departure_settlements');Schema::dropIfExists('departure_requests');}
};
