<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\immobilier\residents_contrat\AllocationChambreController;
use App\Http\Services\AttributionRules;
use App\Models\Chambre;
use App\Models\Etudiant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AllocationChambresPageTest extends \Tests\Support\DossierTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::table('campagne', function (Blueprint $t) {
            $t->string('annee_campagne')->nullable();
            $t->string('description')->nullable();
            $t->date('periode_debut')->nullable();
            $t->date('periode_fin')->nullable();
        });
        Schema::create('type_chambre', function (Blueprint $t) {
            $t->string('id_type_chambre')->primary();
            $t->string('name');
            $t->softDeletes();
            $t->timestamps();
        });
        DB::table('type_chambre')->insert(['id_type_chambre' => 'double', 'name' => 'Double']);
        DB::table('campagne')->update(['annee_campagne' => '2026-2027', 'periode_debut' => '2026-09-01']);
        DB::table('campagne')->insert(['id_campagne' => 'old', 'statut' => 'terminee', 'annee_campagne' => '2025-2026', 'periode_debut' => '2025-09-01']);
        DB::table('campagne')->insert(['id_campagne' => 'cancelled', 'statut' => 'annulee', 'annee_campagne' => '2024-2025']);

        foreach (['student', 'other'] as $student) {
            $dossier = $this->dossier($student);
            $this->workflow->admin($dossier->getKey(), 'eligible');
            $this->confirm($student);
        }
    }

    /** Page Inertia rendue par une action d'affichage du contrôleur (composant + props). */
    private function page(string $action, array $parametres = [], array $query = []): array
    {
        $request = Request::create('/admin/allocation-chambres', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $request->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $request);

        return app()->call([app(AllocationChambreController::class), $action], ['request' => $request, ...$parametres])
            ->toResponse($request)->getData(true);
    }

    public function test_liste_des_campagnes_et_indicateurs(): void
    {
        $page = $this->page('index');
        $this->assertSame('Admin/Hebergement/Allocations/Index', $page['component']);
        $data = $page['props'];

        $this->assertSame(['total' => 2, 'active' => 1, 'planifiee' => 0, 'terminee' => 1], $data['stats']);
        $this->assertSame(['campaign', 'old'], array_column($data['campagnes']['data'], 'id'));
        $this->assertSame(2, $data['campagnes']['data'][0]['a_loger']);
        $this->assertNull($data['classement']);

        $this->allocate();
        $campagne = $this->page('index')['props']['campagnes']['data'][0];
        $this->assertSame(1, $campagne['a_loger']);
        $this->assertSame(0, $campagne['propositions']);
        $this->assertSame(1, $campagne['confirmees']);
    }

    public function test_precontrole_du_sexe_de_la_chambre(): void
    {
        $regles = app(AttributionRules::class);
        $chambre = fn () => Chambre::with(['typeOccupation', 'batiment.typeOccupation', 'cite.typeCite'])->findOrFail('room');

        $this->assertSame('m', $regles->sexeEtudiant(Etudiant::findOrFail('student')));
        $this->assertSame('mixte', $regles->sexeRequis($chambre()));

        DB::table('chambre')->update(['type_occupation_id' => 'hommes']);
        $this->assertSame('m', $regles->sexeRequis($chambre()));

        DB::table('cite')->update(['type_cite_id' => 'femmes']);
        $this->assertSame('conflit', $regles->sexeRequis($chambre()));

        DB::table('cite')->update(['type_cite_id' => null]);
        $this->assertSame('incomplet', $regles->sexeRequis($chambre()));
    }

    public function test_page_statistiques_accessible(): void
    {
        DB::table('chambre')->update(['type_chambre_id' => 'double']);
        $this->allocate();

        $page = $this->page('statistics', ['campagneId' => 'campaign']);

        $this->assertSame('Admin/Hebergement/Allocations/Statistics', $page['component']);
        // L'attribution est rattachée au type de sa chambre.
        $parType = collect($page['props']['stats']['par_type'])->firstWhere('nom', 'Double');
        $this->assertSame(1, $parType['total']);
        $this->assertSame(1, $parType['occupees']);
    }

    /** Tables lues par les listes d'éligibles et de chambres (documents, niveaux). */
    private function tablesDuSuivi(): void
    {
        Schema::create('document_etudiant', function (Blueprint $t) {
            $t->string('id_document_etudiant')->primary();
            $t->string('etudiant_id')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
    }

    public function test_suivi_de_la_campagne(): void
    {
        $this->tablesDuSuivi();
        $this->allocate();
        session()->flash('allocation_result', ['allocated' => [['rang' => 1, 'name' => 'Étudiant', 'score' => 80.5, 'chambre' => '101']], 'not_allocated' => []]);

        $page = $this->page('show', ['campagneId' => 'campaign']);

        $this->assertSame('Admin/Hebergement/Allocations/Show', $page['component']);
        $props = $page['props'];
        $this->assertCount(1, $props['assignations']);
        // Attribution définitive : elle ne s'annule plus qu'au travers de la procédure de sortie.
        $this->assertFalse($props['assignations'][0]['annulable']);
        $this->assertFalse($props['assignations'][0]['confirmable']);
        $this->assertCount(1, $props['etudiants']);
        $this->assertSame(1, $props['classement']['reservations']);
        $this->assertSame('101', $props['classement']['lignes'][0]['chambre']);
    }

    public function test_formulaire_d_attribution_et_precontrole(): void
    {
        $this->tablesDuSuivi();
        $page = $this->page('allocateForm', ['campagneId' => 'campaign'], ['etudiant_id' => 'student']);

        $this->assertSame('Admin/Hebergement/Allocations/Allocate', $page['component']);
        $props = $page['props'];
        $this->assertSame('student', $props['etudiantInitial']);
        $this->assertSame(['other', 'student'], collect($props['etudiants'])->pluck('id')->sort()->values()->all());
        $this->assertSame('m', collect($props['etudiants'])->firstWhere('id', 'student')['sexe']);
        $chambre = collect($props['chambres'])->firstWhere('id', 'room');
        $this->assertSame('mixte', $chambre['sexe']);
        $this->assertNotEmpty($chambre['lits']);
    }
}
