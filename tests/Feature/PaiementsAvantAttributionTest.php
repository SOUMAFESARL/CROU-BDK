<?php

namespace Tests\Feature;

use App\Http\Services\PaiementsAvantAttribution;
use App\Models\Campagne;
use App\Models\Chambre;
use App\Models\Etudiant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Attribution d'une chambre : caution (nouvelles demandes) et avance payées avant, selon les conditions de la campagne. */
class PaiementsAvantAttributionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('campagne', function (Blueprint $t) {
            $t->string('id_campagne')->primary();
            $t->string('annee_campagne')->nullable();
            $t->boolean('admission')->default(true);
            $t->boolean('readmission')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary();
            $t->string('name');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('type_chambre', function (Blueprint $t) {
            $t->string('id_type_chambre')->primary();
            $t->string('name');
            $t->decimal('montant', 12, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary();
            $t->string('type_chambre_id')->nullable();
            $t->decimal('prix', 12, 2)->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary();
            $t->string('etudiant_id')->nullable();
            $t->string('campagne_id')->nullable();
            $t->timestamps();
        });
        Schema::create('demande', function (Blueprint $t) {
            $t->string('id_demande')->primary();
            $t->string('etudiant_id');
            $t->string('campagne_id');
            $t->timestamps();
        });
        Schema::create('paiements', function (Blueprint $t) {
            $t->string('id_paiement')->primary();
            foreach (['resident_id', 'chambre_id', 'type_paiement', 'statut_paiement'] as $champ) {
                $t->string($champ)->nullable();
            }
            $t->decimal('montant', 12, 2)->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        // Campagne d'admission seule : conditions par défaut, 3 mois de caution et 2 mois d'avance.
        DB::table('campagne')->insert(['id_campagne' => 'k1', 'annee_campagne' => '2026-2027']);
        DB::table('etudiants')->insert(['id_etudiant' => 'e1', 'name' => 'Awa Koné']);
        DB::table('type_chambre')->insert(['id_type_chambre' => 't1', 'name' => 'Double', 'montant' => 8000]);
        DB::table('chambre')->insert(['id_chambre' => 'ch1', 'type_chambre_id' => 't1']);
        DB::table('demande')->insert(['id_demande' => 'd1', 'etudiant_id' => 'e1', 'campagne_id' => 'k1', 'created_at' => now()->subMonth()]);
    }

    private function payer(string $type, float $montant, ?string $chambre = null, $date = null): void
    {
        DB::table('paiements')->insert([
            'id_paiement' => uniqid('p', true), 'resident_id' => 'e1', 'chambre_id' => $chambre, 'type_paiement' => $type,
            'statut_paiement' => 'valide', 'montant' => $montant, 'created_at' => $date ?? now(),
        ]);
    }

    private function verifier(): void
    {
        app(PaiementsAvantAttribution::class)->verifier(Etudiant::find('e1'), Chambre::find('ch1'), Campagne::find('k1'));
    }

    public function test_l_attribution_est_refusee_tant_que_caution_et_avance_ne_sont_pas_payees(): void
    {
        // Caution payée en entier (3 × 8 000), avance partielle (1 mois sur 2).
        $this->payer('caution', 24000);
        $this->payer('avance', 8000);

        try {
            $this->verifier();
            $this->fail('L’avance incomplète doit bloquer l’attribution.');
        } catch (ValidationException $e) {
            $message = $e->errors()['paiements'][0];
            $this->assertStringContainsString('Attribution impossible : Awa Koné', $message);
            $this->assertStringContainsString('Avance : 8 000 FCFA restant sur 16 000 FCFA', $message);
        }
    }

    public function test_l_attribution_est_permise_une_fois_caution_et_avance_payees(): void
    {
        $this->payer('caution', 24000);
        $this->payer('avance', 16000);

        $this->verifier();
        $this->assertSame(['caution' => 0.0, 'avance' => 0.0], array_map(fn ($etat) => $etat['reste'], app(PaiementsAvantAttribution::class)->etat(Etudiant::find('e1'), Chambre::find('ch1'), Campagne::find('k1'))));
    }

    public function test_seuls_les_paiements_avant_attribution_de_la_campagne_comptent(): void
    {
        // Paiements d'un séjour précédent (avec chambre) ou antérieurs à la demande : ignorés.
        $this->payer('caution', 24000, 'ancienne');
        $this->payer('avance', 16000, null, now()->subYear());

        $etat = app(PaiementsAvantAttribution::class)->etat(Etudiant::find('e1'), Chambre::find('ch1'), Campagne::find('k1'));

        $this->assertSame([24000.0, 16000.0], [$etat['caution']['reste'], $etat['avance']['reste']]);
    }

    public function test_readmission_seule_l_avance_est_exigee(): void
    {
        // Campagne de réadmission seule : pas de caution, 3 mois d'avance.
        DB::table('campagne')->update(['admission' => false, 'readmission' => true]);

        $etat = app(PaiementsAvantAttribution::class)->etat(Etudiant::find('e1'), Chambre::find('ch1'), Campagne::find('k1'));

        $this->assertSame(['avance'], array_keys($etat));
        $this->assertSame(24000.0, $etat['avance']['exige']);
    }
}
