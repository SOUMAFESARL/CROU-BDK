<?php

namespace Tests\Feature;

use App\Models\AssignationChambre;
use App\Models\Batiment;
use App\Models\Campagne;
use App\Models\Chambre;
use App\Models\DemandeEtudiant;
use App\Models\Etudiant;
use App\Services\EtudiantDashboard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Tableau de bord React de l'espace étudiant (Etudiant/TableauDeBord) et réponse à la proposition de chambre. */
class StudentDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $settings = new \App\Models\ConfigApp(['app_name' => 'Residence test']);
        $settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(\App\Repositories\Interfaces\ConfigAppRepositoryInterface::class)
            ->shouldReceive('getConfig')->andReturn($settings);
        $this->withoutMiddleware([\App\Http\Middleware\EnforceSystemSecurity::class, \App\Http\Middleware\LogUserActivity::class]);
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary();
            $t->string('chambre_id')->nullable();
            $t->timestamp('date_fin')->nullable();
        });
    }

    private function etudiant(DemandeEtudiant $demande, array $assignations = []): Etudiant
    {
        $etudiant = new Etudiant(['id_etudiant' => 'student', 'name' => 'Étudiant test']);
        $etudiant->setRelation('derniereDemande', $demande)
            ->setRelation('demandes', collect([$demande]))
            ->setRelation('assignationsChambre', collect($assignations))
            ->setRelation('assignationActive', null);

        return $etudiant;
    }

    public function test_resident_view_shows_room_rent_and_unpaid_balance(): void
    {
        $demande = (new DemandeEtudiant(['id_demande' => 'request', 'campagne_id' => 'campaign', 'statut_demande' => 'Acceptée']))
            ->setRelation('documents', collect());
        $chambre = (new Chambre(['id_chambre' => 'room', 'numero_chambre' => 'B12']))
            ->setRelation('batiment', new Batiment(['name' => 'Bâtiment B']))
            ->setRelation('typeChambre', null)
            ->setRelation('cite', null);
        $assignation = (new AssignationChambre(['id_assignation' => 'stay', 'campagne_id' => 'campaign', 'date_assignation' => '2026-09-01']))
            ->setRelation('chambre', $chambre);

        $props = app(EtudiantDashboard::class)->props(
            $this->etudiant($demande, [$assignation]),
            new Campagne(['annee_campagne' => '2026-2027']),
            ['stats' => ['total_impayes' => 30000, 'nb_impayes' => 2], 'paiements' => collect(), 'prochainPaiement' => ['libelle' => 'Novembre 2026']],
            collect(),
        );

        $this->assertNull($props['demande']);
        $this->assertSame(['code' => 'Acceptée', 'libelle' => 'Acceptée', 'ton' => 'green'], $props['statut']);
        $this->assertSame('B12', $props['resident']['chambre']);
        $this->assertSame('Bâtiment B', $props['resident']['batiment']);
        $this->assertSame(2, $props['resident']['nbImpayes']);
        $this->assertStringContainsString('30 000', $props['resident']['resteAPayer']);
        $this->assertSame('Attribution de la chambre B12', $props['resident']['activites'][0]['texte']);
        $this->assertSame(route('etudiants.dashboard', ['section' => 'paiements']), $props['resident']['urls']['paiements']);
    }

    public function test_request_view_offers_a_decision_only_for_a_pre_allocated_request(): void
    {
        $campagne = new Campagne(['annee_campagne' => '2026-2027', 'date_echeance_demande' => '2026-11-30']);
        $demande = (new DemandeEtudiant(['id_demande' => 'request', 'campagne_id' => 'campaign', 'statut_demande' => 'En_attente',
            'etat_dossier' => 'pre_attribue', 'assignation_id' => 'offer']))
            ->setRelation('documents', collect([
                new \App\Models\DocumentEtudiant(['id_document' => 1, 'type_document' => 'Bulletin', 'statut' => 'valide']),
                new \App\Models\DocumentEtudiant(['id_document' => 2, 'type_document' => 'Certificat', 'statut' => 'en_verification']),
            ]));

        $props = app(EtudiantDashboard::class)->props($this->etudiant($demande), $campagne, [], collect());

        $this->assertNull($props['resident']);
        $this->assertSame('amber', $props['statut']['ton']);
        $this->assertSame(50, $props['demande']['progression']);
        $this->assertSame(['valide', 'en_attente'], array_column($props['demande']['documents'], 'statut'));
        $this->assertSame('offer', $props['demande']['decision']['assignationId']);
        $this->assertSame(route('etudiants.validation-demande', ['idDemande' => 'request']), $props['demande']['decision']['url']);
        $this->assertStringStartsWith('2026-11-30T23:59:59', $props['demande']['echeanceIso']);

        $demande->etat_dossier = 'eligible';
        $props = app(EtudiantDashboard::class)->props($this->etudiant($demande), $campagne, [], collect());
        $this->assertNull($props['demande']['decision']);
    }

    public function test_refusal_without_reason_returns_a_field_error(): void
    {
        $this->actingAs(new Etudiant(['id_etudiant' => 'student', 'name' => 'Étudiant test']), 'etudiant');

        $this->from(route('etudiants.dashboard'))
            ->post(route('etudiants.validation-demande', ['idDemande' => 'request']), ['action' => 'refuser', 'assignation_id' => 'offer'])
            ->assertRedirect(route('etudiants.dashboard'))
            ->assertSessionHasErrors(['motif_refus' => 'Indiquez le motif du refus.']);
    }
}
