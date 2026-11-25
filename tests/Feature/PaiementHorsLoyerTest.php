<?php

namespace Tests\Feature;

use App\Http\Services\LoyerService;
use App\Http\Services\PaiementService;
use App\Models\Paiement;
use App\Repositories\Interfaces\LoyerRepositoryInterface;
use App\Repositories\PaiementRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

/** « Nouveau paiement » : seuls les paiements de type loyer sont répartis sur les loyers ; les autres types sont enregistrés tels quels. */
class PaiementHorsLoyerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('paiements', function (Blueprint $t) {
            $t->string('id_paiement')->primary();
            foreach (['numero_paiement', 'resident_id', 'contrat_id', 'chambre_id', 'batiment_id', 'cite_id', 'loyer_id', 'type_paiement', 'devise', 'methode_paiement', 'reference_transaction', 'statut_paiement', 'created_by', 'updated_by', 'encaissement_id'] as $champ) {
                $t->string($champ)->nullable();
            }
            $t->text('mois')->nullable();
            $t->decimal('montant', 12, 2)->default(0);
            $t->decimal('montant_du', 12, 2)->default(0);
            $t->dateTime('date_paiement')->nullable();
            $t->date('date_echeance')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('paiement_repartitions', function (Blueprint $t) {
            $t->string('id_repartition')->primary();
            $t->string('paiement_id');
            $t->decimal('montant', 12, 2);
            $t->timestamps();
        });
    }

    private function service(LoyerRepositoryInterface $loyers): PaiementService
    {
        return new PaiementService(new PaiementRepository(new Paiement), $loyers, Mockery::mock(LoyerService::class));
    }

    private function donnees(string $type): array
    {
        return [
            'resident_id' => 'e1', 'chambre_id' => 'ch1', 'batiment_id' => 'b1', 'cite_id' => 'c1', 'contrat_id' => 'a1',
            'type_paiement' => $type, 'montant' => 5000, 'montant_du' => 25000, 'methode_paiement' => 'especes', 'notes' => 'Dossier 2026',
        ];
    }

    public function test_un_paiement_hors_loyer_ne_touche_pas_aux_loyers(): void
    {
        // Aucun calcul d'arriérés, aucun loyer créé ni soldé.
        $loyers = Mockery::mock(LoyerRepositoryInterface::class);
        $loyers->shouldNotReceive('calculerArriere');

        $paiement = $this->service($loyers)->creerPaiement($this->donnees('frais_de_dossier'));

        $this->assertSame(['frais_de_dossier', 'valide', null, []], [$paiement->type_paiement, $paiement->statut_paiement, $paiement->loyer_id, $paiement->mois]);
        // Le montant dû est le montant versé (pas le loyer mensuel envoyé par le formulaire).
        $this->assertEquals([5000, 5000], [$paiement->montant, $paiement->montant_du]);
        $this->assertSame(0, DB::table('paiement_repartitions')->count());
        $this->assertSame(1, Paiement::count());
    }

    public function test_les_types_d_origine_autres_que_loyer_sont_aussi_exclus(): void
    {
        $loyers = Mockery::mock(LoyerRepositoryInterface::class);
        $loyers->shouldNotReceive('calculerArriere');

        foreach (['caution', 'penalite', 'autre'] as $type) {
            $this->assertNull($this->service($loyers)->creerPaiement($this->donnees($type))->loyer_id);
        }
        $this->assertSame(3, Paiement::count());
    }

    public function test_plusieurs_types_coches_creent_un_paiement_par_type(): void
    {
        $loyers = Mockery::mock(LoyerRepositoryInterface::class);
        $loyers->shouldNotReceive('calculerArriere');
        $commun = collect($this->donnees('autre'))->except(['type_paiement', 'montant'])->all();

        $paiements = $this->service($loyers)->creerPaiements($commun, [
            ['type_paiement' => 'caution', 'montant' => 30000],
            ['type_paiement' => 'frais_de_dossier', 'montant' => 5000],
        ]);

        $this->assertCount(2, $paiements);
        // Un seul encaissement : reçu commun aux deux types.
        $this->assertNotNull($paiements[0]->encaissement_id);
        $this->assertSame($paiements[0]->encaissement_id, $paiements[1]->encaissement_id);
        $this->assertCount(2, $paiements[0]->paiementsDuRecu());
        $this->assertEquals(
            [['caution', 30000, 'e1', 'especes'], ['frais_de_dossier', 5000, 'e1', 'especes']],
            Paiement::orderBy('montant', 'desc')->get()->map(fn ($p) => [$p->type_paiement, (float) $p->montant, $p->resident_id, $p->methode_paiement])->all()
        );
    }

    public function test_un_echec_sur_un_type_annule_tous_les_paiements(): void
    {
        $loyers = Mockery::mock(LoyerRepositoryInterface::class);
        $loyers->shouldReceive('calculerArriere')->once()->andThrow(new \RuntimeException('échec du loyer'));
        $commun = collect($this->donnees('autre'))->except(['type_paiement', 'montant'])->all();

        try {
            $this->service($loyers)->creerPaiements($commun, [
                ['type_paiement' => 'caution', 'montant' => 30000],
                ['type_paiement' => 'loyer', 'montant' => 25000],
            ]);
            $this->fail('L’échec du loyer doit être remonté.');
        } catch (\RuntimeException $e) {
            $this->assertSame('échec du loyer', $e->getMessage());
        }
        // La caution enregistrée avant le loyer est annulée avec lui.
        $this->assertSame(0, Paiement::count());
    }

    public function test_un_loyer_ou_une_avance_passe_toujours_par_la_repartition(): void
    {
        foreach (['loyer', 'avance'] as $type) {
            $loyers = Mockery::mock(LoyerRepositoryInterface::class);
            $loyers->shouldReceive('calculerArriere')->once()->andThrow(new \RuntimeException('calcul des loyers atteint'));

            try {
                $this->service($loyers)->creerPaiement($this->donnees($type));
                $this->fail("« {$type} » doit passer par le calcul des arriérés.");
            } catch (\RuntimeException $e) {
                $this->assertSame('calcul des loyers atteint', $e->getMessage());
            }
        }
        $this->assertSame(0, Paiement::count());
    }

    public function test_une_avance_avant_l_attribution_est_enregistree_hors_loyer(): void
    {
        // Pas encore de chambre ni de loyer : l'avance est enregistrée telle quelle, sans mois.
        $loyers = Mockery::mock(LoyerRepositoryInterface::class);
        $loyers->shouldNotReceive('calculerArriere');

        $paiement = $this->service($loyers)->creerPaiement(['chambre_id' => null, 'batiment_id' => null, 'avant_attribution' => true] + $this->donnees('avance'));

        $this->assertSame(['avance', null, [], null], [$paiement->type_paiement, $paiement->loyer_id, $paiement->mois, $paiement->chambre_id]);
        $this->assertEquals(5000, $paiement->montant);
    }

    public function test_le_dernier_mois_paye_ignore_les_paiements_hors_loyer(): void
    {
        DB::table('paiements')->insert([
            ['id_paiement' => 'p-loyer', 'resident_id' => 'e1', 'type_paiement' => 'loyer', 'statut_paiement' => 'valide', 'mois' => json_encode(['10-2026', '11-2026']), 'created_at' => now()->subDay()],
            ['id_paiement' => 'p-caution', 'resident_id' => 'e1', 'type_paiement' => 'caution', 'statut_paiement' => 'valide', 'mois' => json_encode([]), 'created_at' => now()],
        ]);

        $service = $this->service(Mockery::mock(LoyerRepositoryInterface::class));

        $this->assertSame('12-2026', $service->prochainMoisAPayer('e1'));
        $this->assertSame(['12-2026', '01-2027', '02-2027'], $service->moisConsecutifs('e1', 3));
    }
}
