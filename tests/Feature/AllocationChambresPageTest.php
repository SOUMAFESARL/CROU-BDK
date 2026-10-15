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
            $this->workflow->admin($dossier->getKey(), 'en_etude');
            $this->workflow->admin($dossier->getKey(), 'eligible');
        }
    }

    private function controller(): AllocationChambreController
    {
        $this->app->instance('request', Request::create('/admin/allocation-chambres'));

        return app(AllocationChambreController::class);
    }

    public function test_liste_des_campagnes_et_indicateurs(): void
    {
        $data = $this->controller()->index()->getData();

        $this->assertSame(['total' => 2, 'active' => 1, 'planifiee' => 0, 'terminee' => 1], $data['stats']);
        $this->assertSame(['campaign', 'old'], collect($data['campagnes']->items())->pluck('id_campagne')->all());
        $this->assertSame(2, $data['campagnes']->first()->dossiers_eligibles_count);

        $this->allocate();
        $campagne = $this->controller()->index()->getData()['campagnes']->first();
        $this->assertSame(1, $campagne->dossiers_eligibles_count);
        $this->assertSame(1, $campagne->propositions_count);
        $this->assertSame(0, $campagne->confirmees_count);
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

        $vue = $this->controller()->statistics('campaign');

        $this->assertInstanceOf(\Illuminate\View\View::class, $vue);
        $this->assertSame('admin.hebergement.resident_contrat.allocation_statistics', $vue->name());
        // L'attribution en attente est rattachée au type de sa chambre.
        $parType = $vue->getData()['stats']['par_type']['Double'];
        $this->assertSame(1, $parType['total']);
        $this->assertSame(1, $parType['occupees']);
    }

    public function test_les_vues_compilent(): void
    {
        foreach (['allocation_index', 'allocation_show', 'allocation_form', 'allocation_statistics', 'ranking-summary'] as $vue) {
            $compiled = app('blade.compiler')->compileString(file_get_contents(resource_path("views/admin/hebergement/resident_contrat/{$vue}.blade.php")));
            $this->assertNotEmpty(token_get_all($compiled, TOKEN_PARSE), $vue);
        }
    }
}
