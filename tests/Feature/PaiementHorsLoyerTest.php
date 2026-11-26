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
        // Campagne de l'étudiant (attribution confirmée, sinon dernière demande) et son calendrier des loyers.
        Schema::create('campagne', function (Blueprint $t) {
            $t->string('id_campagne')->primary();
            $t->timestamps();
            $t->softDeletes();
        });
        (require database_path('migrations/2026_11_26_000001_add_calendrier_loyer_to_campagne_table.php'))->up();
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary();
            foreach (['etudiant_id', 'campagne_id', 'statut'] as $champ) {
                $t->string($champ)->nullable();
            }
            $t->dateTime('date_assignation')->nullable();
            $t->timestamps();
        });
        Schema::create('demande', function (Blueprint $t) {
            $t->string('id_demande')->primary();
            $t->string('etudiant_id')->nullable();
            $t->string('campagne_id')->nullable();
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

    /** Campagne k1 de e1 (attribution confirmée, entrée le `$entree`) avec un calendrier d'octobre 2026 à juillet 2027 sans décembre. */
    private function calendrier(string $entree = '2026-10-01'): array
    {
        $mois = ['2026-10', '2026-11', '2027-01', '2027-02', '2027-03', '2027-04', '2027-05', '2027-06', '2027-07', '2027-08'];
        $calendrier = array_map(fn (string $cle) => ['mois' => $cle, 'echeance' => $cle.'-05'], $mois);
        DB::table('campagne')->insert(['id_campagne' => 'k1', 'calendrier_loyer' => json_encode($calendrier)]);
        DB::table('assignation_chambres')->insert(['id_assignation' => 'a1', 'etudiant_id' => 'e1', 'campagne_id' => 'k1', 'statut' => 'confirmee', 'date_assignation' => $entree, 'created_at' => now()]);

        return $calendrier;
    }

    public function test_le_calendrier_des_loyers_fixe_les_mois_a_payer(): void
    {
        $this->calendrier();
        DB::table('paiements')->insert([
            'id_paiement' => 'p-loyer', 'resident_id' => 'e1', 'type_paiement' => 'loyer', 'statut_paiement' => 'valide',
            'mois' => json_encode(['10-2026', '11-2026']), 'created_at' => now(),
        ]);
        $service = $this->service(Mockery::mock(LoyerRepositoryInterface::class));

        // Décembre n'est pas au calendrier : après novembre vient janvier ; rien au-delà d'août 2027 (8 mois restants).
        $this->assertSame('01-2027', $service->prochainMoisAPayer('e1'));
        $this->assertSame(['01-2027', '02-2027', '03-2027'], $service->moisConsecutifs('e1', 3));
        $this->assertCount(8, $service->moisConsecutifs('e1', 12));
    }

    public function test_sans_mois_paye_le_premier_mois_du_calendrier_suit_l_entree(): void
    {
        // Entrée en novembre 2026, aucun loyer payé : premier mois à payer novembre (octobre est avant l'entrée).
        $this->calendrier('2026-11-15');
        $service = $this->service(Mockery::mock(LoyerRepositoryInterface::class));

        $this->assertSame('11-2026', $service->prochainMoisAPayer('e1'));
        $this->assertSame(['11-2026', '01-2027'], $service->moisConsecutifs('e1', 2));

        // Échéance d'un mois : celle du calendrier, sinon le 10 du mois.
        $calendrier = app(\App\Services\CalendrierLoyer::class);
        $this->assertSame('2027-01-05 23:59:59', $calendrier->echeance($calendrier->pourEtudiant('e1'), 1, 2027)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-12-10 23:59:59', $calendrier->echeance($calendrier->pourEtudiant('e1'), 12, 2026)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-12-10 23:59:59', $calendrier->echeance([], 12, 2026)->format('Y-m-d H:i:s'));
    }

    public function test_les_penalites_deja_calculees_sont_retirees_des_loyers(): void
    {
        Schema::create('loyers', function (Blueprint $t) {
            $t->string('id_loyer')->primary();
            foreach (['solde_restant', 'total_penalites', 'penalite_journaliere'] as $champ) {
                $t->decimal($champ, 12, 2)->default(0);
            }
        });
        DB::table('loyers')->insert([
            ['id_loyer' => 'l1', 'solde_restant' => 13000, 'total_penalites' => 1000, 'penalite_journaliere' => 240],
            ['id_loyer' => 'l2', 'solde_restant' => 500, 'total_penalites' => 800, 'penalite_journaliere' => 0],
            ['id_loyer' => 'l3', 'solde_restant' => 12000, 'total_penalites' => 0, 'penalite_journaliere' => 0],
        ]);

        (require database_path('migrations/2026_11_26_000002_supprimer_penalites_loyers.php'))->up();

        $loyers = DB::table('loyers')->orderBy('id_loyer')->get()->map(fn ($l) => [(float) $l->solde_restant, (float) $l->total_penalites, (float) $l->penalite_journaliere])->all();
        $this->assertSame([[12000.0, 0.0, 0.0], [0.0, 0.0, 0.0], [12000.0, 0.0, 0.0]], $loyers);
    }
}
