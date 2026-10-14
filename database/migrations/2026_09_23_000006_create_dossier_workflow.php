<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
return new class extends Migration {
    public function up(): void {
        Schema::table('demande', function(Blueprint $t) {
            $t->string('etat_dossier',30)->default('soumis')->index();
            $t->string('assignation_id',150)->nullable()->index();
        });
        Schema::create('dossier_transitions', function(Blueprint $t) {
            $t->id(); $t->string('demande_id',150)->index();
            $t->string('ancien_etat',30)->nullable(); $t->string('nouvel_etat',30);
            $t->string('acteur_type',30); $t->string('acteur_id',150)->nullable();
            $t->text('motif')->nullable(); $t->json('details')->nullable(); $t->timestamp('created_at');
        });
        DB::table('demande')->orderBy('id_demande')->chunk(100, function($requests) {
            foreach($requests as $request) {
                $legacy = mb_strtolower((string)$request->statut_demande);
                $state = in_array($legacy,['refusée','refusee','rejetee']) ? 'refuse'
                    : (in_array($legacy,['annulee','annuler']) ? 'annule'
                    : (in_array($legacy,['en_verification','en_cours','validee','acceptée','acceptee']) ? 'en_etude' : 'soumis'));
                $latest = DB::table('demande')->where('etudiant_id',$request->etudiant_id)
                    ->where('campagne_id',$request->campagne_id)->orderByDesc('created_at')->orderByDesc('id_demande')->value('id_demande');
                $assignment = $latest === $request->id_demande ? DB::table('assignation_chambres')
                    ->where('etudiant_id',$request->etudiant_id)->where('campagne_id',$request->campagne_id)
                    ->whereIn('statut',['en_attente','confirmee'])
                    ->where(fn($q)=>$q->whereNull('date_fin')->orWhere('date_fin','>',now()))
                    ->orderByDesc('date_assignation')->first() : null;
                if ($assignment) $state = $assignment->statut === 'confirmee' ? 'attribue_definitif' : 'pre_attribue';
                DB::table('demande')->where('id_demande',$request->id_demande)->update([
                    'etat_dossier'=>$state, 'assignation_id'=>$assignment?->id_assignation,
                ]);
                DB::table('dossier_transitions')->insert([
                    'demande_id'=>$request->id_demande,'ancien_etat'=>null,'nouvel_etat'=>$state,
                    'acteur_type'=>'reprise','acteur_id'=>null,
                    'motif'=>'Reprise des données existantes ; aucune validation historique supplémentaire présumée.',
                    'details'=>json_encode(['ancien_statut'=>$request->statut_demande]),'created_at'=>now(),
                ]);
            }
        });
    }
    public function down(): void {
        Schema::dropIfExists('dossier_transitions');
        Schema::table('demande',function(Blueprint $t) { $t->dropIndex(['etat_dossier']); $t->dropIndex(['assignation_id']); });
        Schema::table('demande',fn(Blueprint $t)=>$t->dropColumn(['etat_dossier','assignation_id']));
    }
};
