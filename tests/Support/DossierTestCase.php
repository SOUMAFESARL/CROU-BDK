<?php

namespace Tests\Support;

use App\Http\Services\AllocationChambreService;
use App\Http\Services\AttributionRules;
use App\Models\AssignationChambre;
use App\Models\Campagne;
use App\Models\Chambre;
use App\Models\Etudiant;
use App\Repositories\AssignationChambreRepository;
use App\Repositories\Interfaces\ChambreRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

abstract class DossierTestCase extends TestCase
{
    protected AllocationChambreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('config_app', fn (Blueprint $t) => $t->string('config_id')->primary());
        (require database_path('migrations/2026_08_10_000001_create_sanctions_tables.php'))->up();
        (require database_path('migrations/2026_08_10_000002_add_scope_to_sanctions_table.php'))->up();
        foreach (['cite' => 'id_cite', 'batiment' => 'id_batiment', 'chambre' => 'id_chambre',
            'etudiants' => 'id_etudiant', 'campagne' => 'id_campagne', 'type_cite' => 'id_type_cite'] as $name => $key) {
            Schema::create($name, function (Blueprint $t) use ($key) {
                $t->string($key)->primary();
                foreach (['statut', 'etat', 'cite_id', 'batiment_id', 'type_cite_id', 'type_occupation_id',
                    'numero_chambre', 'type_chambre_id', 'name', 'updated_by', 'statut_inscription'] as $column) {
                    $t->string($column)->nullable();
                }
                $t->integer('genre_id')->nullable();
                $t->integer('capacite_max')->default(1);
                $t->integer('occupants_actuels')->default(0);
                $t->boolean('pmr')->default(false);
                $t->boolean('pmr_confirme')->default(false);
                $t->string('pmr_confirme_by')->nullable();
                $t->timestamp('pmr_confirme_at')->nullable();
                $t->softDeletes();
                $t->timestamps();
            });
        }
        Schema::create('genre', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('residence_campagne', function (Blueprint $t) {
            $t->id(); $t->string('campagne_id'); $t->string('cite_id');
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary();
            foreach (['etudiant_id', 'chambre_id', 'campagne_id', 'statut', 'type_assignation', 'created_by', 'commentaires'] as $column) {
                $t->string($column)->nullable();
            }
            $t->timestamp('date_assignation')->nullable();
            $t->timestamp('date_fin')->nullable();
            $t->timestamps();
        });
        (require database_path('migrations/2026_09_22_000001_create_lits.php'))->up();
        $demandeTable = (new \App\Models\DemandeEtudiant())->getTable();
        Schema::create($demandeTable, function (Blueprint $t) {
            $t->string('id_demande')->primary(); $t->string('etudiant_id'); $t->string('campagne_id');
            $t->string('annee_academique_actuel')->nullable();
            $t->string('niveau_etude_actuel_code')->nullable();
            $t->softDeletes(); $t->timestamps();
        });
        DB::table('genre')->insert([['id' => 1, 'name' => 'Masculin'], ['id' => 2, 'name' => 'Féminin']]);
        foreach (['mixte', 'hommes', 'femmes'] as $type) {
            DB::table('type_cite')->insert(['id_type_cite' => $type, 'name' => $type]);
        }
        DB::table('cite')->insert(['id_cite' => 'cite', 'statut' => 'actif', 'type_cite_id' => 'mixte']);
        DB::table('batiment')->insert(['id_batiment' => 'building', 'cite_id' => 'cite', 'statut' => 'actif', 'type_occupation_id' => 'mixte']);
        DB::table('campagne')->insert(['id_campagne' => 'campaign']);
        DB::table('residence_campagne')->insert(['campagne_id' => 'campaign', 'cite_id' => 'cite']);
        foreach (['student', 'other'] as $id) {
            DB::table('etudiants')->insert(['id_etudiant' => $id, 'genre_id' => 1]);
        }
        $this->room('room');
        $this->initializeWorkflow();
        $this->service = new AllocationChambreService(Mockery::mock(ChambreRepositoryInterface::class), new AssignationChambreRepository());
    }

    protected function room(string $id, array $data = []): void
    {
        DB::table('chambre')->insert(array_merge([
            'id_chambre' => $id, 'cite_id' => 'cite', 'batiment_id' => 'building', 'numero_chambre' => $id,
            'statut' => 'actif', 'etat' => 'disponible', 'type_occupation_id' => 'mixte', 'capacite_max' => 1,
        ], $data));
    }

    /** Attribution définitive ; un dossier encore « Éligible » est d'abord confirmé par l'étudiant. */
    protected function allocate(string $student = 'student', ?string $room = 'room'): ?AssignationChambre
    {
        if (\App\Models\DemandeEtudiant::where('etudiant_id', $student)->where('campagne_id', 'campaign')
            ->latest('created_at')->orderByDesc('id_demande')->value('etat_dossier') === 'eligible') {
            $this->confirm($student);
        }
        return $this->service->allocateChambre(Etudiant::findOrFail($student), Campagne::findOrFail('campaign'),
            $room ? Chambre::findOrFail($room) : null);
    }

    /** L'étudiant éligible confirme qu'il souhaite toujours être logé. */
    protected function confirm(string $student = 'student'): \App\Models\DemandeEtudiant
    {
        return $this->workflow->transition($this->workflow->current($student, 'campaign')->getKey(),
            'confirme', 'etudiant', $student, 'Demande confirmée.');
    }

    /**
     * Proposition en attente de l'ancien parcours (pré-attribution à accepter par l'étudiant),
     * telle qu'elle subsiste pour les dossiers antérieurs.
     */
    protected function reserver(string $student = 'student', string $room = 'room', ?int $lit = null): AssignationChambre
    {
        return DB::transaction(function () use ($student, $room, $lit) {
            $chambre = Chambre::findOrFail($room);
            $assignment = AssignationChambre::create([
                'etudiant_id' => $student, 'chambre_id' => $room, 'campagne_id' => 'campaign',
                'lit_id' => app(\App\Http\Services\LitService::class)->choisir($chambre, $lit)->getKey(),
                'statut' => 'en_attente', 'type_assignation' => 'manuelle',
                'date_assignation' => now(), 'date_fin' => now()->addHours(72),
            ]);
            $dossier = $this->workflow->current($student, 'campaign');
            DB::table($dossier->getTable())->where('id_demande', $dossier->getKey())
                ->update(['etat_dossier' => 'pre_attribue', 'assignation_id' => $assignment->getKey(), 'statut_demande' => 'En_verification']);
            $chambre->actualiserOccupation();
            return $assignment;
        });
    }


    protected array $evaluation = ['eligible'=>true,'score_global'=>100,'total_regles'=>1,'regles'=>[['label'=>'Critères','ok'=>true],['label'=>'Documents validés','ok'=>true]]];
    protected \App\Http\Services\DossierWorkflow $workflow;

    protected function initializeWorkflow(): void
    {
        Schema::table('demande', function (Blueprint $t) {
            $t->string('statut_demande')->default('En_attente'); $t->string('numero_demande')->nullable();
            $t->string('created_by')->nullable(); $t->string('updated_by')->nullable();
            $t->text('commentaires')->nullable(); $t->timestamp('date_demande')->nullable();
        });
        Schema::create('niveau_etude', function (Blueprint $t) { $t->string('id_niveau_etude')->primary(); $t->string('name')->nullable(); $t->unsignedInteger('ordre')->default(0); $t->softDeletes(); });
        Schema::create('admission_derogations', function (Blueprint $t) {
            $t->string('id')->primary(); $t->string('demande_id'); $t->string('status');
            $t->json('eligibility')->nullable(); $t->timestamp('decided_at')->nullable(); $t->string('decided_by')->nullable();
        });
        (require database_path('migrations/2026_09_23_000006_create_dossier_workflow.php'))->up();
        DB::table('campagne')->update(['statut'=>'active']);
        $actor = Mockery::mock(\App\Models\User::class)->makePartial();
        $actor->fill(['id_user'=>'agent','name'=>'Agent']);
        $actor->shouldReceive('hasPermission')->andReturn(true);
        $this->actingAs($actor,'admin');
        $this->mock(\App\Repositories\Interfaces\EtudiantRepositoryInterface::class)
            ->shouldReceive('findWithRelations')->andReturnUsing(fn($id)=>Etudiant::findOrFail($id))
            ->shouldReceive('verifierEligibilite')->andReturnUsing(fn()=> $this->evaluation);
        $this->workflow=app(\App\Http\Services\DossierWorkflow::class);
        $this->withoutMiddleware([\App\Http\Middleware\LogUserActivity::class,\App\Http\Middleware\EnsureTokenIsValid::class,\App\Http\Middleware\EnforceSystemSecurity::class]);
    }
    protected function dossier(string $student='student', bool $draft=false): \App\Models\DemandeEtudiant
    {
        // Saisie faite depuis l'espace admin (c'est la route, pas la seule session admin, qui en fait une saisie admin).
        $requete=$this->app['request'];
        $this->app->instance('request',\Illuminate\Http\Request::create('/admin/etudiants','POST'));
        try {
            $dossier=new \App\Models\DemandeEtudiant(['etudiant_id'=>$student,'campagne_id'=>'campaign','statut_demande'=>'En_attente']);
            $dossier->workflowDraft=$draft;
            $dossier->save();
        } finally {
            $this->app->instance('request',$requete);
        }
        return $dossier->fresh();
    }
    protected function eligibleRequest(): \App\Models\DemandeEtudiant
    {
        $dossier=$this->dossier();
        return $this->workflow->admin($dossier->getKey(),'eligible','Critères vérifiés.');
    }
    protected function accept(\App\Models\DemandeEtudiant $dossier, AssignationChambre $assignment): void
    {
        $this->workflow->transition($dossier->getKey(),'accepte','etudiant','student','Accord de l’étudiant.',
            ['assignation_id'=>$assignment->getKey()]);
    }

}
