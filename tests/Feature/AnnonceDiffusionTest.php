<?php
namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use App\Mail\AnnonceMail;
use App\Models\Annonce;
use App\Models\AnnonceDelivery;
use App\Models\ConfigApp;
use App\Models\Etudiant;
use App\Models\User;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use App\Services\AnnonceDiffusionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnnonceDiffusionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'mail.default' => 'array']);
        DB::purge('sqlite');
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);
        $this->mock(ConfigAppRepositoryInterface::class)->shouldReceive('getConfig')->andReturn(new ConfigApp());
        $this->actingAs(new User(['id_user' => 'admin', 'code' => 'admin', 'role_id' => 1]), 'admin');
        Schema::create('config_app', function (Blueprint $t) {
            $t->string('config_id')->primary();
            $t->boolean('email_enabled')->default(true);
            $t->boolean('sms_enabled')->default(true);
            foreach (['sms_provider','sms_api_key','sms_sender','mail_driver','mail_host','mail_port','mail_username','mail_password','mail_from_address','mail_from_name'] as $f) $t->string($f)->nullable();
            $t->timestamps();
        });
        Schema::create('annonces', function (Blueprint $t) {
            $t->string('id_annonce')->primary();
            foreach (['titre','statut','priorite','audience','created_by','updated_by','deleted_by'] as $f) $t->string($f)->nullable();
            foreach (['contenu','canaux','audience_cible','pieces_jointes'] as $f) $t->text($f)->nullable();
            foreach (['date_publication','date_expiration','date_planification'] as $f) $t->timestamp($f)->nullable();
            foreach (['vues','clics','reactions'] as $f) $t->integer($f)->default(0);
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary();
            foreach (['email1','email2','telephone1','telephone2'] as $f) $t->string($f)->nullable();
            $t->softDeletes();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary(); $t->string('cite_id'); $t->string('batiment_id'); $t->softDeletes();
        });
        Schema::create('cite', fn (Blueprint $t) => $t->string('id_cite')->primary());
        Schema::create('batiment', fn (Blueprint $t) => $t->string('id_batiment')->primary());
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary(); $t->string('etudiant_id'); $t->string('chambre_id');
            $t->string('statut'); $t->timestamp('date_assignation'); $t->timestamp('date_fin')->nullable();
        });
        (require database_path('migrations/2026_09_20_000004_create_annonce_deliveries.php'))->up();
        DB::table('config_app')->insert([
            'config_id' => 'test', 'sms_api_url' => 'https://sms.example.test/send',
            'sms_provider' => 'helper_json', 'sms_api_key' => 'test-key', 'sms_sender' => 'CROU', 'mail_driver' => 'array',
        ]);
        Http::preventStrayRequests();
        Http::fake(['sms.example.test/*' => Http::response(['status' => 'success'], 200)]);
        Mail::fake();
    }

    private function student(string $id, array $data = []): Etudiant
    {
        DB::table('etudiants')->insert(array_merge(['id_etudiant' => $id, 'email1' => $id.'@example.test', 'telephone1' => '0700000001'], $data));
        return Etudiant::findOrFail($id);
    }

    private function annonce(array $data = []): Annonce
    {
        return Annonce::create(array_merge([
            'titre' => 'Information résidence', 'contenu' => '<p>Bonjour <strong>résident</strong></p>',
            'canaux' => ['flux'], 'audience' => 'all', 'statut' => 'publie',
        ], $data));
    }

    public function test_web_also_emails_and_repeated_processing_does_not_duplicate(): void
    {
        $student = $this->student('a');
        $annonce = $this->annonce();
        $service = app(AnnonceDiffusionService::class);
        $service->prepare($annonce->id_annonce);
        $service->sendPending();
        $annonce->publier();
        $service->sendPending();
        Mail::assertSent(AnnonceMail::class, fn ($mail) => $mail->hasTo('a@example.test') && $mail->content === 'Bonjour résident');
        Mail::assertSentCount(1);
        Http::assertNothingSent();
        $this->assertTrue(Annonce::visibleParEtudiant($student)->whereKey($annonce->id_annonce)->exists());
        $this->assertDatabaseCount('annonce_deliveries', 1);
        $this->assertDatabaseHas('annonce_deliveries', ['status' => 'sent']);
    }

    public function test_sms_includes_email_and_normalizes_the_country_code(): void
    {
        $student = $this->student('a', ['telephone1' => '+225 07 00 00 00 01']);
        $this->annonce(['canaux' => ['sms', 'email']]);
        app(AnnonceDiffusionService::class)->sendPending();
        Mail::assertSentCount(1);
        Http::assertSent(fn ($r) => $r['recipient'] === '2250700000001' && $r->hasHeader('Authorization', 'Bearer test-key') && str_contains($r['message'], 'Bonjour résident'));
        Http::assertSentCount(1);
        $this->assertSame(0, Annonce::visibleParEtudiant($student)->count());
    }

    public function test_email_channel_never_sends_sms_or_publishes_on_web(): void
    {
        $student = $this->student('a');
        $this->annonce(['canaux' => ['email']]);
        app(AnnonceDiffusionService::class)->sendPending();
        Mail::assertSentCount(1);
        Http::assertNothingSent();
        $this->assertSame(0, Annonce::visibleParEtudiant($student)->count());
    }

    public function test_contact_fallback_and_missing_email(): void
    {
        $this->student('a', ['email1' => '', 'email2' => 'fallback@example.test', 'telephone1' => 'invalid', 'telephone2' => '0500000002']);
        $this->student('b', ['email1' => null]);
        $this->annonce(['canaux' => ['sms']]);
        app(AnnonceDiffusionService::class)->sendPending();
        Mail::assertSent(AnnonceMail::class, fn ($mail) => $mail->hasTo('fallback@example.test'));
        Mail::assertSentCount(1);
        Http::assertSentCount(2);
    }

    public function test_drafts_and_future_schedules_do_not_send_until_due(): void
    {
        $this->student('a');
        $this->annonce(['statut' => 'brouillon']);
        $planned = $this->annonce(['statut' => 'planifie', 'date_planification' => now()->addHour()]);
        $this->artisan('annonces:diffuser')->assertSuccessful();
        Mail::assertNothingSent();
        Http::assertNothingSent();
        $this->travel(61)->minutes();
        $this->artisan('annonces:diffuser')->assertSuccessful();
        $this->assertSame('publie', $planned->fresh()->statut);
        Mail::assertSentCount(1);
        $this->travelBack();
    }

    public function test_residential_targeting_excludes_expired_assignments_and_other_rooms(): void
    {
        $a = $this->student('a'); $b = $this->student('b'); $this->student('c');
        DB::table('chambre')->insert(['id_chambre' => 'room', 'cite_id' => 'cite', 'batiment_id' => 'batiment']);
        foreach (['a' => null, 'b' => now()->subDay()] as $id => $end) {
            DB::table('assignation_chambres')->insert(['id_assignation' => $id, 'etudiant_id' => $id, 'chambre_id' => 'room', 'statut' => 'confirmee', 'date_assignation' => now()->subMonth(), 'date_fin' => $end]);
        }
        foreach (['cite' => ['cites' => ['cite']], 'batiment' => ['batiments' => ['batiment']], 'chambre' => ['chambres' => ['room']]] as $audience => $target) {
            $annonce = $this->annonce(['audience' => $audience, 'audience_cible' => $target]);
            $this->assertSame(['a'], $annonce->getDestinataires()->all());
            $this->assertTrue(Annonce::visibleParEtudiant($a)->whereKey($annonce->id_annonce)->exists());
            $this->assertFalse(Annonce::visibleParEtudiant($b)->whereKey($annonce->id_annonce)->exists());
        }
        app(AnnonceDiffusionService::class)->sendPending();
        Mail::assertSentCount(3);
    }

    public function test_specific_audience_only_sends_to_selected_student(): void
    {
        $this->student('a'); $this->student('b');
        $this->annonce(['audience' => 'specific', 'audience_cible' => ['etudiants' => ['b']]]);
        app(AnnonceDiffusionService::class)->sendPending();
        Mail::assertSentCount(1);
        Mail::assertSent(AnnonceMail::class, fn ($mail) => $mail->hasTo('b@example.test'));
    }

    public function test_sms_failure_does_not_prevent_email_or_report_success(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(['sms.example.test/*' => Http::response(['status' => 'error'], 200)]);
        $this->student('a');
        $this->annonce(['canaux' => ['sms']]);
        app(AnnonceDiffusionService::class)->sendPending();
        $this->assertDatabaseHas('annonce_deliveries', ['channel' => 'sms', 'status' => 'failed']);
        $this->assertDatabaseHas('annonce_deliveries', ['channel' => 'email', 'status' => 'sent']);
        Mail::assertSentCount(1);
    }

    public function test_missing_configuration_does_not_fake_sms_success(): void
    {
        DB::table('config_app')->update(['sms_api_url' => null]);
        $this->assertFalse(EnvoiSMS('0700000001', 'Test'));
        Http::assertNothingSent();
    }

    public function test_cancelled_announcement_stops_pending_delivery(): void
    {
        $this->student('a');
        $annonce = $this->annonce();
        $annonce->annuler();
        app(AnnonceDiffusionService::class)->sendPending();
        Mail::assertNothingSent();
        $this->assertDatabaseHas('annonce_deliveries', ['status' => 'cancelled']);
    }

    public function test_admin_creation_persists_target_and_rejects_mobile_channel(): void
    {
        $this->student('a');
        $url = route('admin.hebergement.annonce.store');
        $data = ['titre' => 'Test', 'contenu' => 'Message', 'canaux' => ['email'], 'audience' => 'specific', 'etudiants' => ['a'], 'statut' => 'publie', 'priorite' => 'normale'];
        $this->postJson($url, array_replace($data, ['canaux' => ['push']]))->assertUnprocessable()->assertJsonValidationErrors('canaux.0');
        $response = $this->postJson($url, $data)->assertCreated();
        $annonce = Annonce::findOrFail($response->json('data.id_annonce'));
        $this->assertSame(['etudiants' => ['a']], $annonce->audience_cible);
        $this->assertDatabaseCount('annonce_deliveries', 1);
        $this->putJson(route('admin.hebergement.annonce.update', $annonce->id_annonce), array_replace($data, ['titre' => 'Modifié']))->assertSuccessful();
        $this->assertDatabaseCount('annonce_deliveries', 1);
    }

    /** Page React : création, modification et suppression reviennent à la liste avec un message (plus de JSON brut). */
    public function test_page_actions_redirect_back_with_a_message(): void
    {
        $this->student('a');
        $data = ['titre' => 'Coupure d’eau', 'contenu' => '<p>Demain matin.</p>', 'canaux' => ['email'], 'audience' => 'specific', 'etudiants' => ['a'], 'statut' => 'brouillon', 'priorite' => 'haute'];
        $liste = route('admin.hebergement.annonce.index');

        $this->from($liste)->post(route('admin.hebergement.annonce.store'), $data)->assertRedirect($liste)->assertSessionHas('success', 'Brouillon enregistré.');
        $annonce = Annonce::firstOrFail();
        $this->assertSame(['etudiants' => ['a']], $annonce->audience_cible);
        $this->assertDatabaseCount('annonce_deliveries', 0);

        $this->from($liste)->put(route('admin.hebergement.annonce.update', $annonce->id_annonce), array_replace($data, ['titre' => 'Coupure d’eau prolongée']))
            ->assertRedirect($liste)->assertSessionHas('success');
        $this->assertSame('Coupure d’eau prolongée', $annonce->fresh()->titre);

        $this->from($liste)->post(route('admin.hebergement.annonce.store'), array_replace($data, ['statut' => 'planifie', 'date_planifiee' => now()->subDay()->toDateTimeString()]))
            ->assertRedirect($liste)->assertSessionHasErrors(['date_planifiee' => 'La date de publication doit être dans le futur.']);

        $this->from($liste)->delete(route('admin.hebergement.annonce.destroy', $annonce->id_annonce))->assertRedirect($liste)->assertSessionHas('success', 'Annonce supprimée.');
        $this->assertSoftDeleted('annonces', ['id_annonce' => $annonce->id_annonce]);
    }

    public function test_index_page_sends_explicit_props_and_action_urls(): void
    {
        Schema::table('etudiants', function (Blueprint $t) {
            $t->string('name')->nullable();
            $t->string('matricule')->nullable();
            $t->string('statut_inscription')->nullable();
        });
        Schema::table('batiment', function (Blueprint $t) {
            $t->string('cite_id')->nullable();
            $t->string('name')->nullable();
            $t->softDeletes();
        });
        Schema::table('chambre', fn (Blueprint $t) => $t->string('numero_chambre')->nullable());
        Schema::create('users', function (Blueprint $t) {
            $t->string('id_user')->primary();
            $t->string('code')->nullable();
            $t->string('name')->nullable();
            $t->softDeletes();
        });
        $this->mock(\App\Http\Services\CiteService::class)->shouldReceive('getCites')
            ->andReturn(new \Illuminate\Database\Eloquent\Collection([(new \App\Models\Cite)->forceFill(['id_cite' => 'c1', 'name' => 'Cité Bondoukou'])]));
        $this->student('a', ['name' => 'Awa Koné', 'matricule' => 'M001', 'statut_inscription' => 'residents']);
        $this->student('b', ['name' => 'Non résident', 'statut_inscription' => 'demande']);
        $publiee = $this->annonce(['audience' => 'specific', 'audience_cible' => ['etudiants' => ['a']], 'canaux' => ['email'], 'contenu' => '<p>Bonjour <strong>à tous</strong></p>']);
        $brouillon = $this->annonce(['titre' => 'Brouillon', 'statut' => 'brouillon']);

        $request = \Illuminate\Http\Request::create(route('admin.hebergement.annonce.index'), 'GET', ['statut' => 'inconnu', 'canal' => 'push', 'date_debut' => '15/10/2026']);
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn () => auth('admin')->user());
        $page = app()->call([app(\App\Http\Controllers\admin\immobilier\vie_residentielle\AnnonceController::class), 'index'], ['request' => $request])
            ->toResponse($request)->getData(true);

        $this->assertSame('Admin/Hebergement/Annonces/Index', $page['component']);
        $props = $page['props'];
        // Valeurs de filtre inconnues ignorées.
        $this->assertSame(['statut' => '', 'canal' => '', 'search' => '', 'date_debut' => '', 'date_fin' => ''], $props['filters']);
        $this->assertSame(2, $props['stats']['total']);
        $lignes = collect($props['annonces']['data'])->keyBy('id');
        $this->assertSame('Bonjour à tous', $lignes[$publiee->id_annonce]['extrait']);
        $this->assertSame(['etudiants' => ['a']], $lignes[$publiee->id_annonce]['cibles']);
        $this->assertNull($lignes[$publiee->id_annonce]['urls']['publish']);
        $this->assertNotNull($lignes[$brouillon->id_annonce]['urls']['publish']);
        $this->assertNotNull($lignes[$brouillon->id_annonce]['urls']['update']);
        // Seuls les résidents sont proposés pour une diffusion ciblée.
        $this->assertSame([['id' => 'a', 'name' => 'Awa Koné', 'matricule' => 'M001']], $props['cibles']['etudiants']);
        $this->assertSame([['id' => 'c1', 'name' => 'Cité Bondoukou']], $props['cibles']['cites']);
        $this->assertNotNull($props['urls']['store']);
    }
}
