<?php

namespace Tests\Feature;

use App\Models\Etudiant;
use App\Models\PaymentProvider;
use App\Services\EtudiantMenu;
use App\Services\EtudiantPaiements;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Page React « Mes paiements » de l'espace étudiant (Etudiant/MesPaiements) et quittances de loyer. */
class StudentPaymentsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-26 10:00:00');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $settings = new \App\Models\ConfigApp(['app_name' => 'Residence test']);
        $settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(\App\Repositories\Interfaces\ConfigAppRepositoryInterface::class)
            ->shouldReceive('getConfig')->andReturn($settings);
        foreach (['000001_create_payment_providers_table', '000003_add_specific_payment_credentials', '000004_create_online_payments_table'] as $migration) {
            (require database_path('migrations/2026_09_19_'.$migration.'.php'))->up();
        }
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary(); $t->string('etudiant_id'); $t->string('chambre_id'); $t->string('campagne_id')->nullable();
            $t->string('statut'); $t->dateTime('date_assignation'); $t->dateTime('date_fin')->nullable(); $t->timestamps();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary(); $t->string('numero_chambre'); $t->string('cite_id')->nullable();
            $t->string('type_chambre_id')->nullable(); $t->decimal('prix', 10, 2)->nullable(); $t->softDeletes();
        });
        Schema::create('loyers', function (Blueprint $t) {
            $t->string('id_loyer')->primary();
            foreach (['etudiant_id', 'chambre_id', 'assignation_id', 'statut'] as $f) $t->string($f)->nullable();
            foreach (['montant_total', 'total_paye', 'solde_restant', 'total_penalites'] as $f) $t->decimal($f, 10, 2)->default(0);
            $t->integer('mois_facturation'); $t->integer('annee_facturation');
            $t->date('date_limit_paiement')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('paiements', function (Blueprint $t) {
            $t->string('id_paiement')->primary();
            foreach (['numero_paiement', 'resident_id', 'type_paiement', 'contrat_id', 'loyer_id', 'mois', 'methode_paiement', 'reference_transaction', 'statut_paiement'] as $f) $t->string($f)->nullable();
            $t->decimal('montant', 15, 2); $t->timestamp('date_paiement')->nullable(); $t->timestamps(); $t->softDeletes();
        });

        Schema::create('demande', function (Blueprint $t) {
            $t->string('id_demande')->primary();
            foreach (['etudiant_id', 'campagne_id', 'filiere_code', 'niveau_etude_actuel_code'] as $f) $t->string($f)->nullable();
            $t->timestamps();
        });
        Schema::create('filiere', function (Blueprint $t) {
            $t->string('id_filiere')->primary(); $t->string('name');
        });
        Schema::create('niveau_etude', function (Blueprint $t) {
            $t->string('id_niveau_etude')->primary(); $t->string('name');
        });
        Schema::create('genre', function (Blueprint $t) {
            $t->id(); $t->string('name');
        });
        DB::table('genre')->insert([['id' => 1, 'name' => 'Homme'], ['id' => 2, 'name' => 'Femme']]);
        DB::table('filiere')->insert(['id_filiere' => 'f-info', 'name' => 'Informatique']);
        DB::table('niveau_etude')->insert(['id_niveau_etude' => 'n-l2', 'name' => 'Licence 2']);
        DB::table('demande')->insert(['id_demande' => 'd1', 'etudiant_id' => 'student', 'filiere_code' => 'f-info',
            'niveau_etude_actuel_code' => 'n-l2', 'created_at' => now()->subMonths(2)]);

        DB::table('chambre')->insert(['id_chambre' => 'room', 'numero_chambre' => 'A-101', 'prix' => 10000]);
        DB::table('assignation_chambres')->insert(['id_assignation' => 'stay', 'etudiant_id' => 'student', 'chambre_id' => 'room',
            'statut' => 'confirmee', 'date_assignation' => now()->subMonth(), 'created_at' => now()->subMonth()]);
        DB::table('loyers')->insert([
            ['id_loyer' => 'sept', 'etudiant_id' => 'student', 'chambre_id' => 'room', 'assignation_id' => 'stay', 'statut' => 'actif', 'montant_total' => 10000,
                'total_paye' => 2000, 'solde_restant' => 8000, 'total_penalites' => 500, 'mois_facturation' => 9, 'annee_facturation' => 2026,
                'date_limit_paiement' => now()->subDays(10)->toDateString()],
            ['id_loyer' => 'oct', 'etudiant_id' => 'student', 'chambre_id' => 'room', 'assignation_id' => 'stay', 'statut' => 'actif', 'montant_total' => 10000,
                'total_paye' => 10000, 'solde_restant' => 0, 'total_penalites' => 0, 'mois_facturation' => 10, 'annee_facturation' => 2026,
                'date_limit_paiement' => null],
            ['id_loyer' => 'other', 'etudiant_id' => 'mate', 'chambre_id' => 'room', 'assignation_id' => null, 'statut' => 'actif', 'montant_total' => 99999,
                'total_paye' => 0, 'solde_restant' => 99999, 'total_penalites' => 0, 'mois_facturation' => 9, 'annee_facturation' => 2026,
                'date_limit_paiement' => null],
        ]);
        DB::table('paiements')->insert([
            ['id_paiement' => 'paid', 'numero_paiement' => 'PAY-1', 'resident_id' => 'student', 'type_paiement' => 'loyer', 'loyer_id' => 'sept',
                'mois' => json_encode(['09-2026']), 'methode_paiement' => 'mtn_money', 'statut_paiement' => 'valide', 'montant' => 2000, 'created_at' => now()->subDays(2)],
            ['id_paiement' => 'failed', 'numero_paiement' => 'PAY-2', 'resident_id' => 'student', 'type_paiement' => 'loyer', 'loyer_id' => null,
                'mois' => null, 'methode_paiement' => null, 'statut_paiement' => 'echoue', 'montant' => 1000, 'created_at' => now()->subDay()],
            ['id_paiement' => 'cancelled', 'numero_paiement' => 'PAY-3', 'resident_id' => 'student', 'type_paiement' => 'loyer', 'loyer_id' => null,
                'mois' => null, 'methode_paiement' => null, 'statut_paiement' => 'annule', 'montant' => 500, 'created_at' => now()],
        ]);
        PaymentProvider::where('code', 'wave')->firstOrFail()->update(['is_active' => true, 'api_key' => 'wave-secret', 'webhook_secret' => 'hook-secret']);
    }

    private function props(array $query = []): array
    {
        $etudiant = new Etudiant(['id_etudiant' => 'student', 'telephone1' => '+225 07 00 00 00 01']);

        return app(EtudiantPaiements::class)->props($etudiant, Request::create('/etudiant/dashboard', 'GET', ['section' => 'paiements'] + $query));
    }

    public function test_props_list_the_months_of_the_stay_payable_rent_and_history(): void
    {
        $props = $this->props();

        // Mois écoulés du séjour, du premier loyer facturé (septembre) au mois en cours, le plus récent en premier.
        $this->assertSame(['2026-11', '2026-10', '2026-09'], array_column($props['loyers'], 'id'));
        [$novembre, $octobre, $septembre] = $props['loyers'];
        $this->assertSame(['Novembre 2026', false, 'Non payé', 'warning'], [$novembre['periode'], $novembre['paye'], $novembre['statut']['libelle'], $novembre['statut']['ton']]);
        $this->assertStringContainsString('10 000', $novembre['montant']);
        $this->assertNull($novembre['quittance']);
        $this->assertArrayNotHasKey('reste', $novembre);
        $this->assertSame([true, 'Payé'], [$octobre['paye'], $octobre['statut']['libelle']]);
        $this->assertSame(route('etudiants.quittances.show', '2026-10'), $octobre['quittance']['voir']);
        $this->assertSame(route('etudiants.quittances.show', '2026-10').'?telecharger=1', $octobre['quittance']['telecharger']);
        // Septembre n'est payé qu'en partie : non payé, en retard.
        $this->assertSame([false, 'Non payé', 'danger'], [$septembre['paye'], $septembre['statut']['libelle'], $septembre['statut']['ton']]);

        // Le montant payable vient du solde serveur du plus ancien loyer de la chambre occupée.
        $reglement = $props['reglement'];
        $this->assertNull($reglement['indisponible']);
        $this->assertSame(['sept', 'A-101', 8000], [$reglement['loyer']['id'], $reglement['loyer']['chambre'], $reglement['loyer']['soldeValeur']]);
        $this->assertSame('0700000001', $reglement['telephone']);
        $this->assertSame([['code' => 'wave', 'nom' => 'Wave']], $reglement['reseaux']);
        $this->assertArrayNotHasKey('situation', $props);
        $this->assertArrayNotHasKey('enLigne', $props);

        // Les paiements annulés sont masqués ; seul un paiement validé a un reçu, le même que celui de l'administration.
        $this->assertSame(['failed', 'paid'], array_column($props['paiements'], 'id'));
        $this->assertNull($props['paiements'][0]['recu']);
        $this->assertSame([
            'voir' => route('etudiants.paiements.recu', 'paid'),
            'telecharger' => route('etudiants.paiements.recu', 'paid').'?telecharger=1',
        ], $props['paiements'][1]['recu']);
        $this->assertSame(['Loyer', 'Septembre 2026', 'MTN Money', 'Payé'], [$props['paiements'][1]['type'], $props['paiements'][1]['periode'], $props['paiements'][1]['methode'], $props['paiements'][1]['statut']['libelle']]);
        $this->assertSame(route('etudiants.online-payments.store'), $props['urls']['payer']);

        $this->assertStringNotContainsString('wave-secret', json_encode($props));
        $this->assertStringNotContainsString('hook-secret', json_encode($props));
    }

    public function test_an_advance_is_a_rent_and_the_stay_lasts_ten_months_at_most(): void
    {
        // L'avance payée au départ est un loyer : le mois couvert est payé, même avec un loyer facturé non soldé.
        DB::table('loyers')->insert(['id_loyer' => 'nov', 'etudiant_id' => 'student', 'chambre_id' => 'room', 'assignation_id' => 'stay',
            'statut' => 'actif', 'montant_total' => 10000, 'total_paye' => 0, 'solde_restant' => 10000, 'total_penalites' => 0,
            'mois_facturation' => 11, 'annee_facturation' => 2026]);
        DB::table('paiements')->insert(['id_paiement' => 'advance', 'numero_paiement' => 'PAY-9', 'resident_id' => 'student',
            'type_paiement' => 'avance', 'mois' => json_encode(['11-2026']), 'methode_paiement' => 'especes',
            'statut_paiement' => 'valide', 'montant' => 10000, 'date_paiement' => '2026-10-20 09:00:00', 'created_at' => now()]);

        $novembre = $this->props()['loyers'][0];
        $this->assertSame(['2026-11', true, true, 'Payé', '20/10/2026'], [$novembre['id'], $novembre['paye'], $novembre['avance'], $novembre['statut']['libelle'], $novembre['datePaiement']]);

        $quittance = app(EtudiantPaiements::class)->quittance(new Etudiant(['id_etudiant' => 'student', 'name' => 'Awa K.', 'genre_id' => 2]), '2026-11');
        $this->assertSame('Madame', $quittance['civilite']);
        $this->assertSame(['Novembre 2026', 'A-101'], [$quittance['periode'], $quittance['chambre']]);
        // Filière et niveau de la demande de logement.
        $this->assertSame(['filiere' => 'Informatique', 'niveau' => 'Licence 2'], $quittance['parcours']);
        $this->assertSame(['PAY-9'], array_column($quittance['paiements'], 'numero'));
        $html = view('pdf.quittance-loyer', $quittance)->render();
        $this->assertStringContainsString('Quittance de loyer', $html);
        $this->assertStringContainsString('Novembre 2026', $html);
        $this->assertStringContainsString('Informatique', $html);
        $this->assertStringContainsString('Madame', $html);
        $this->assertStringContainsString('Licence 2', $html);
        // Pas de quittance pour un mois non payé ni hors du séjour.
        $this->assertNull(app(EtudiantPaiements::class)->quittance(new Etudiant(['id_etudiant' => 'student']), '2026-09'));
        $this->assertNull(app(EtudiantPaiements::class)->quittance(new Etudiant(['id_etudiant' => 'student']), '2025-11'));
        $this->assertNull(app(EtudiantPaiements::class)->quittance(new Etudiant(['id_etudiant' => 'mate']), '2026-10'));

        // Une campagne compte 10 mois de logement : rien au-delà du 10e mois du séjour.
        $this->travelTo('2027-11-15 10:00:00');
        $mois = array_column($this->props()['loyers'], 'id');
        $this->assertCount(EtudiantPaiements::MOIS_PAR_CAMPAGNE, $mois);
        $this->assertSame(['2027-06', '2026-09'], [$mois[0], end($mois)]);
    }

    public function test_months_paid_in_advance_before_the_room_are_listed_as_paid_rent(): void
    {
        // Attribution du 26/11 sans loyer facturé ; avance de 2 mois versée avant l'attribution (mois suivants).
        DB::table('loyers')->where('etudiant_id', 'student')->delete();
        DB::table('paiements')->delete();
        DB::table('assignation_chambres')->update(['date_assignation' => now()->subHour()]);
        DB::table('paiements')->insert([
            ['id_paiement' => 'advance', 'numero_paiement' => 'PAY-9', 'resident_id' => 'student', 'type_paiement' => 'avance',
                'loyer_id' => null, 'mois' => json_encode(['12-2026', '01-2027']), 'methode_paiement' => 'especes',
                'statut_paiement' => 'valide', 'montant' => 16000, 'created_at' => now()->subDay()],
            ['id_paiement' => 'deposit', 'numero_paiement' => 'PAY-10', 'resident_id' => 'student', 'type_paiement' => 'caution',
                'loyer_id' => null, 'mois' => json_encode([]), 'methode_paiement' => 'especes',
                'statut_paiement' => 'valide', 'montant' => 24000, 'created_at' => now()->subDay()],
        ]);

        // Sans campagne sur l'attribution, le relevé part du mois d'entrée ; les mois à venir payés d'avance suivent.
        $loyers = $this->props()['loyers'];
        $this->assertSame(['2027-01', '2026-12', '2026-11'], array_column($loyers, 'id'));
        $this->assertSame([true, true, 'Payé'], [$loyers[0]['paye'], $loyers[0]['avance'], $loyers[0]['statut']['libelle']]);
        $this->assertStringContainsString('8 000', $loyers[0]['montant']);
        $this->assertNotNull($loyers[1]['quittance']);
        // Sur la quittance, le montant du paiement est la part du mois : il égale le loyer du mois.
        $quittance = app(EtudiantPaiements::class)->quittance(new Etudiant(['id_etudiant' => 'student']), '2026-12');
        $this->assertSame($quittance['montant'], $quittance['paiements'][0]['montant']);
        $this->assertStringContainsString('8 000', $quittance['paiements'][0]['montant']);
        $this->assertArrayNotHasKey('total', $quittance['paiements'][0]);
        $this->assertSame([false, 'Non payé'], [$loyers[2]['paye'], $loyers[2]['statut']['libelle']]);
        // La caution n'est pas un loyer ; l'historique indique le type de chaque paiement.
        // (« Avance » est un type du paramétrage, absent de la base de test : son code est affiché.)
        $this->assertEqualsCanonicalizing(['avance', 'Caution'], array_column($this->props()['paiements'], 'type'));
    }

    public function test_the_rent_calendar_of_the_campaign_sets_the_months_and_due_dates(): void
    {
        // Calendrier des loyers de la campagne : décembre 2026 n'en fait pas partie, échéance le 5 de chaque mois.
        Schema::create('campagne', function (Blueprint $t) {
            $t->string('id_campagne')->primary(); $t->string('annee_campagne')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        (require database_path('migrations/2026_11_26_000001_add_calendrier_loyer_to_campagne_table.php'))->up();
        $mois = ['2026-09', '2026-10', '2026-11', '2027-01', '2027-02', '2027-03', '2027-04', '2027-05', '2027-06', '2027-07'];
        DB::table('campagne')->insert(['id_campagne' => 'k1', 'annee_campagne' => '2026-2027',
            'calendrier_loyer' => json_encode(array_map(fn ($cle) => ['mois' => $cle, 'echeance' => $cle.'-05'], $mois))]);
        DB::table('assignation_chambres')->update(['campagne_id' => 'k1']);

        $loyers = $this->props()['loyers'];
        $this->assertSame(['2026-11', '2026-10', '2026-09'], array_column($loyers, 'id'));
        // Novembre, sans loyer facturé : échéance du calendrier, dépassée.
        $this->assertSame(['05/11/2026', 'Non payé', 'danger'], [$loyers[0]['limite'], $loyers[0]['statut']['libelle'], $loyers[0]['statut']['ton']]);

        // En fin de séjour : les 10 mois du calendrier, sans décembre.
        $this->travelTo('2027-11-15 10:00:00');
        $this->assertSame(array_reverse($mois), array_column($this->props()['loyers'], 'id'));
    }

    public function test_history_filter(): void
    {
        $props = $this->props(['statut' => 'echoue']);
        $this->assertSame('echoue', $props['statut']);
        $this->assertSame(['failed'], array_column($props['paiements'], 'id'));
        $this->assertNull($this->props(['statut' => 'annule'])['statut']);
    }

    public function test_open_tab_comes_from_the_url_and_history_links_stay_on_history(): void
    {
        $this->assertSame('loyers', $this->props()['onglet']);
        $this->assertSame('historique', $this->props(['onglet' => 'historique'])['onglet']);
        // Les onglets retirés (« Situation », « Paiements en ligne ») ouvrent les loyers.
        $this->assertSame('loyers', $this->props(['onglet' => 'situation'])['onglet']);
        $this->assertSame('loyers', $this->props(['onglet' => 'en-ligne'])['onglet']);
        $this->assertSame('historique', $this->props(['statut' => 'valide'])['onglet']);

        DB::table('paiements')->insert(array_map(fn ($i) => ['id_paiement' => "p-$i", 'numero_paiement' => "PAY-1$i", 'resident_id' => 'student',
            'mois' => null, 'methode_paiement' => null, 'statut_paiement' => 'valide', 'montant' => 100, 'created_at' => now()], range(1, 12)));
        $suivant = $this->props(['onglet' => 'loyers'])['pagination']['suivant'];
        $this->assertStringContainsString('onglet=historique', $suivant);
        $this->assertStringNotContainsString('onglet=loyers', $suivant);
    }

    public function test_nothing_is_payable_without_an_occupied_room(): void
    {
        DB::table('assignation_chambres')->update(['statut' => 'annulee']);

        $props = $this->props();

        $this->assertNull($props['reglement']['loyer']);
        $this->assertStringContainsString('Aucune chambre occupée', $props['reglement']['indisponible']);
        $this->assertSame([], $props['loyers']);
    }

    public function test_payments_and_maintenance_open_as_react_pages_from_the_menu(): void
    {
        $this->assertContains('paiements', EtudiantMenu::SECTIONS_REACT);
        $this->assertContains('maintenance', EtudiantMenu::SECTIONS_REACT);
        $this->assertFileDoesNotExist(resource_path('views/etudiant/paiements.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/etudiant/maintenance.blade.php'));
    }
}
