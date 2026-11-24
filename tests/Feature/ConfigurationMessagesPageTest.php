<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use App\Mail\AdmissionResultMail;
use App\Models\ConfigApp;
use App\Models\Role;
use App\Models\User;
use App\Services\AdmissionEmailTemplates;
use App\Services\AdmissionResultDiffusion;
use App\Services\AdmissionSmsTemplates;
use App\Services\AnnonceDiffusionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Page « Configuration des messages » (Paramétrage) migrée vers React. */
class ConfigurationMessagesPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutVite();
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Role::create(['id' => 1, 'name' => 'Administrateur']);
        Schema::create('config_app', function (Blueprint $table) {
            $table->string('config_id')->primary();
            $table->json('admission_sms_templates')->nullable();
            $table->string('region_id')->nullable();
            $table->string('ville_id')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
        // Ligne unique de configuration, présente dans la base réelle (identifiant fixe de ConfigAppRepository).
        DB::table('config_app')->insert(['config_id' => 'ed3acb1a-d6f6-45c0-979a-b6bbbc08cd05']);
        foreach (['2026_08_11_000001_create_role_permissions_tables.php', '2026_08_11_000002_create_modules_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        $this->actingAs(new User(['id_user' => 'test-admin', 'role_id' => 1]), 'admin');
    }

    /** Un middleware global reconstruit la réponse : assertInertia() n'y a pas accès, on lit donc data-page. */
    private function page(): array
    {
        $html = $this->get(route('admin.parametrage.configuration-messages.index'))->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? ''), true) ?? [];
    }

    private function variables(string $cle): string
    {
        return implode(' ', AdmissionSmsTemplates::VARIABLES[$cle]);
    }

    public function test_les_informations_entre_accolades_ne_peuvent_etre_ni_supprimees_ni_inventees(): void
    {
        $cle = AdmissionSmsTemplates::DEFINITIVE;
        $defauts = AdmissionSmsTemplates::resolve([]);
        $enregistrer = fn (array $donnees) => $this->put(route('admin.parametrage.configuration-messages.update'), $donnees + ['messages' => $defauts]);

        $this->assertSame(AdmissionSmsTemplates::VARIABLES, $this->page()['props']['variables']);
        $enregistrer([])->assertSessionHasNoErrors();
        foreach ([str_replace('{{libelle_campagne}}', '', $defauts[$cle]), $defauts[$cle].' {{inconnue}}'] as $texte) {
            $enregistrer(['messages' => array_replace($defauts, [$cle => $texte])])->assertSessionHasErrors('messages.'.$cle);
        }
        $enregistrer(['formatted' => [$cle => '<p>Bravo</p>']])->assertSessionHasErrors('formatted.'.$cle);
        // Le mot de passe ne figure que dans le message de la liste définitive.
        $enregistrer(['messages' => array_replace($defauts, [AdmissionSmsTemplates::ATTENTE => $defauts[AdmissionSmsTemplates::ATTENTE].' {{password_étudiant}}'])])
            ->assertSessionHasErrors('messages.'.AdmissionSmsTemplates::ATTENTE);
    }

    public function test_le_lien_vient_du_site_web_a_defaut_de_la_connexion_etudiant(): void
    {
        $this->assertSame(route('etudiant.login'), AdmissionSmsTemplates::platformUrl(ConfigApp::first()));

        Schema::table('config_app', fn (Blueprint $table) => $table->string('website')->nullable());
        DB::table('config_app')->update(['website' => 'https://crou-bondoukou.ci']);

        $this->assertSame('https://crou-bondoukou.ci', AdmissionSmsTemplates::platformUrl(ConfigApp::first()));
    }

    public function test_le_mot_de_passe_est_cree_a_l_envoi_sans_etre_enregistre_en_clair(): void
    {
        $this->preparerEnvois();
        config(['mail.default' => 'array']);
        $envoyes = fn () => app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->map(fn ($m) => $m->getOriginalMessage()->getHtmlBody())->all();
        DB::table('etudiants')->insert([
            ['id_etudiant' => 'nouveau', 'email1' => 'nouveau@example.com', 'password' => null],
            ['id_etudiant' => 'ancien', 'email1' => 'ancien@example.com', 'password' => Hash::make('Existant1')],
            ['id_etudiant' => 'autre', 'email1' => 'autre@example.com', 'password' => null],
        ]);
        DB::table('demande')->insert(array_map(fn ($id) => ['id_demande' => 'd-'.$id, 'etudiant_id' => $id], ['nouveau', 'ancien', 'autre']));
        // « autre » : message adressé à une adresse qui n'est pas son identifiant de connexion.
        foreach (['nouveau' => 'nouveau@example.com', 'ancien' => 'ancien@example.com', 'autre' => 'secondaire@example.com'] as $id => $adresse) {
            DB::table('admission_result_deliveries')->insert([
                'campagne_id' => 'c', 'demande_id' => 'd-'.$id, 'resultat' => AdmissionSmsTemplates::DEFINITIVE, 'channel' => 'email',
                'destination' => $adresse, 'subject' => 'Résultat', 'body' => 'Mot de passe : {{password_étudiant}}',
                'body_html' => '<p>Mot de passe : {{password_étudiant}}</p>', 'status' => 'pending',
            ]);
        }

        $this->assertSame(3, app(AdmissionResultDiffusion::class)->sendPending());
        $this->assertSame(['sent'], DB::table('admission_result_deliveries')->distinct()->pluck('status')->all());

        [$nouveau, $ancien, $autre] = $envoyes();
        $this->assertSame(1, preg_match('/Mot de passe : (\w{10})</', $nouveau, $trouve));
        $this->assertTrue(Hash::check($trouve[1], DB::table('etudiants')->where('id_etudiant', 'nouveau')->value('password')));
        // Accès déjà dans le message : pas de second bloc « Accès à votre espace étudiant ».
        $this->assertStringNotContainsString('Accès à votre espace étudiant', $nouveau);
        foreach ([$ancien, $autre] as $html) {
            $this->assertStringContainsString('votre mot de passe actuel', $html);
        }
        $this->assertTrue(Hash::check('Existant1', DB::table('etudiants')->where('id_etudiant', 'ancien')->value('password')));
        $this->assertNull(DB::table('etudiants')->where('id_etudiant', 'autre')->value('password'));
        $this->assertStringContainsString('{{password_étudiant}}', DB::table('admission_result_deliveries')->where('demande_id', 'd-nouveau')->value('body_html'));
    }

    public function test_le_mot_de_passe_n_est_pas_enregistre_si_l_envoi_echoue(): void
    {
        $this->preparerEnvois();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP indisponible'));
        DB::table('etudiants')->insert(['id_etudiant' => 'e', 'email1' => 'e@example.com', 'password' => null]);
        DB::table('demande')->insert(['id_demande' => 'd', 'etudiant_id' => 'e']);
        DB::table('admission_result_deliveries')->insert([
            'campagne_id' => 'c', 'demande_id' => 'd', 'resultat' => AdmissionSmsTemplates::DEFINITIVE, 'channel' => 'email',
            'destination' => 'e@example.com', 'subject' => 'Résultat', 'body' => '{{password_étudiant}}', 'status' => 'pending',
        ]);

        app(AdmissionResultDiffusion::class)->sendPending();

        $this->assertSame('failed', DB::table('admission_result_deliveries')->value('status'));
        $this->assertNull(DB::table('etudiants')->value('password'));
    }

    /** Tables et transport nécessaires à l'envoi des notifications de résultats. */
    private function preparerEnvois(): void
    {
        $this->mock(AnnonceDiffusionService::class)->shouldReceive('configureMail');
        foreach (['2026_11_23_000002_create_admission_result_deliveries_table.php', '2026_11_24_000004_add_body_html_to_admission_result_deliveries.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('demande', function (Blueprint $table) {
            $table->string('id_demande')->primary();
            $table->string('etudiant_id')->nullable();
            $table->timestamps();
        });
        Schema::create('etudiants', function (Blueprint $table) {
            $table->string('id_etudiant')->primary();
            $table->string('email1')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_la_page_rend_le_composant_react_avec_les_modeles_par_defaut(): void
    {
        $page = $this->page();

        $this->assertSame('Admin/Parametrage/ConfigurationMessages', $page['component']);
        $this->assertCount(count(AdmissionSmsTemplates::LABELS), $page['props']['labels']);
        $this->assertSame(AdmissionSmsTemplates::resolve([]), $page['props']['messages']);
        $this->assertSame(AdmissionSmsTemplates::resolve([]), $page['props']['defaults']);
        $this->assertArrayNotHasKey('maxLength', $page['props']);
        $this->assertSame(route('admin.parametrage.configuration-messages.update'), $page['props']['urls']['update']);
    }

    public function test_les_modeles_sont_enregistres_et_reaffiches(): void
    {
        $messages = [
            AdmissionSmsTemplates::DEFINITIVE => 'Bravo, vous êtes retenu.',
            AdmissionSmsTemplates::ATTENTE => 'Vous êtes sur liste d’attente.',
            AdmissionSmsTemplates::NON_RETENUE => 'Votre demande n’a pas été retenue.',
        ];
        $messages = array_map(fn (string $cle, string $texte) => $texte.' '.$this->variables($cle), array_keys($messages), $messages);
        $messages = array_combine(array_keys(AdmissionSmsTemplates::LABELS), $messages);

        $this->put(route('admin.parametrage.configuration-messages.update'), ['messages' => $messages])
            ->assertRedirect(route('admin.parametrage.configuration-messages.index'));

        $this->assertSame($messages[AdmissionSmsTemplates::ATTENTE], ConfigApp::first()->admission_sms_templates[AdmissionSmsTemplates::ATTENTE]);
        $this->assertSame($messages, $this->page()['props']['messages']);
    }

    public function test_la_mise_en_forme_est_nettoyee_et_le_sms_reste_en_texte_brut(): void
    {
        $cle = AdmissionSmsTemplates::DEFINITIVE;
        $messages = array_replace(AdmissionSmsTemplates::resolve([]), [$cle => 'Bravo à vous '.$this->variables($cle)]);

        $this->put(route('admin.parametrage.configuration-messages.update'), [
            'messages' => $messages,
            'formatted' => [$cle => '<p onclick="x()"><strong>Bravo</strong> à vous<script>alert(1)</script> '.preg_replace('/{{(.*?)}}/u', '<span data-variable="$1">{{$1}}</span>', $this->variables($cle)).'</p>'],
        ])->assertSessionHasNoErrors();

        $props = $this->page()['props'];
        $this->assertSame('Bravo à vous '.$this->variables($cle), $props['messages'][$cle]);
        $this->assertSame('<p><strong>Bravo</strong> à vous '.$this->variables($cle).'</p>', $props['formatted'][$cle]);
    }

    public function test_les_objets_email_sont_persistes(): void
    {
        // Ancien corps propre à l'e-mail : abandonné au profit du corps commun.
        ConfigApp::first()->update(['admission_sms_templates' => ['emails' => [AdmissionSmsTemplates::DEFINITIVE => ['subject' => 'Ancien objet', 'body' => 'Ancien corps']]]]);
        $this->assertSame('Ancien objet', $this->page()['props']['subjects'][AdmissionSmsTemplates::DEFINITIVE]);

        $subjects = AdmissionEmailTemplates::subjects([]);
        $subjects[AdmissionSmsTemplates::ATTENTE] = 'Décision de la commission';
        $messages = AdmissionSmsTemplates::resolve([]);

        $this->put(route('admin.parametrage.configuration-messages.update'), [
            'messages' => $messages,
            'subjects' => $subjects,
        ])->assertRedirect(route('admin.parametrage.configuration-messages.index'));

        $props = $this->page()['props'];
        $this->assertEquals($subjects, $props['subjects']);
        $this->assertSame(['subject' => $subjects[AdmissionSmsTemplates::DEFINITIVE]], ConfigApp::first()->admission_sms_templates['emails'][AdmissionSmsTemplates::DEFINITIVE]);
        $this->assertSame($messages, $props['messages']);
    }

    public function test_un_objet_vide_est_refuse(): void
    {
        $this->put(route('admin.parametrage.configuration-messages.update'), [
            'messages' => AdmissionSmsTemplates::resolve([]),
            'subjects' => array_replace(AdmissionEmailTemplates::subjects([]), [AdmissionSmsTemplates::ATTENTE => '']),
        ])->assertSessionHasErrors('subjects.'.AdmissionSmsTemplates::ATTENTE);
    }

    public function test_les_types_d_envoi_suivent_la_configuration_des_notifications(): void
    {
        $this->assertSame(['email' => true, 'sms' => false], $this->page()['props']['channels']);

        Schema::table('config_app', function (Blueprint $table) {
            $table->boolean('email_enabled')->nullable();
            $table->boolean('sms_enabled')->nullable();
        });
        DB::table('config_app')->update(['email_enabled' => false, 'sms_enabled' => true]);

        $props = $this->page()['props'];
        $this->assertSame(['email' => false, 'sms' => true], $props['channels']);
        $this->assertSame(route('admin.parametrage.configuration.index', ['menus' => 'notifications']), $props['urls']['notifications']);
    }

    public function test_l_envoi_d_un_message_peut_etre_desactive_puis_reactive(): void
    {
        $cle = AdmissionSmsTemplates::NON_RETENUE;
        $tous = array_fill_keys(array_keys(AdmissionSmsTemplates::LABELS), true);
        $this->assertSame($tous, $this->page()['props']['enabled']);

        $enregistrer = fn (array $donnees) => $this->put(
            route('admin.parametrage.configuration-messages.update'),
            ['messages' => AdmissionSmsTemplates::resolve([])] + $donnees,
        );

        $enregistrer(['enabled' => [$cle => false]])->assertSessionHasNoErrors();
        $this->assertSame(array_replace($tous, [$cle => false]), $this->page()['props']['enabled']);

        // Enregistrement sans le champ : l'état de chaque message est conservé.
        $enregistrer([])->assertSessionHasNoErrors();
        $this->assertFalse($this->page()['props']['enabled'][$cle]);

        $enregistrer(['enabled' => [$cle => 'peut-être']])->assertSessionHasErrors('enabled.'.$cle);

        $enregistrer(['enabled' => [$cle => true]])->assertSessionHasNoErrors();
        $this->assertSame($tous, $this->page()['props']['enabled']);
    }

    public function test_les_envois_en_attente_d_un_message_desactive_ne_partent_pas(): void
    {
        Mail::fake();
        $this->preparerEnvois();
        DB::table('admission_result_deliveries')->insert([
            'campagne_id' => 'c', 'demande_id' => 'd', 'resultat' => AdmissionSmsTemplates::NON_RETENUE, 'channel' => 'email',
            'destination' => 'a@example.com', 'subject' => 'Résultat', 'body' => 'Texte', 'status' => 'pending',
        ]);
        $activer = fn (bool $actif) => ConfigApp::first()->update(['admission_sms_templates' => ['enabled' => [AdmissionSmsTemplates::NON_RETENUE => $actif]]]);
        $diffusion = app(AdmissionResultDiffusion::class);

        $activer(false);
        $this->assertSame(0, $diffusion->sendPending());
        $this->assertSame('pending', DB::table('admission_result_deliveries')->value('status'));
        Mail::assertNothingSent();

        $activer(true);
        $this->assertSame(1, $diffusion->sendPending());
        Mail::assertSent(AdmissionResultMail::class, 1);
    }

    public function test_l_email_reprend_la_mise_en_forme_et_a_defaut_le_texte(): void
    {
        $mail = fn (?string $html) => new AdmissionResultMail('Objet', "Bravo\n<b>à vous</b>", $html, null, null);

        $mail('<p><strong>Bravo</strong></p>')->assertSeeInHtml('<p><strong>Bravo</strong></p>', false);
        $mail('<p><strong>Bravo</strong></p>')->assertSeeInText('Bravo');
        $mail(null)->assertSeeInHtml('&lt;b&gt;à vous&lt;/b&gt;', false);
    }

    public function test_l_email_porte_le_logo_joint_et_des_liens_cliquables(): void
    {
        config(['mail.default' => 'array']);
        Mail::to('a@example.com')->send(new AdmissionResultMail('Objet', 'Texte', '<p>Lien : https://crou-bondoukou.ci</p><p></p><p>Fin</p>', null, null));

        $message = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();
        $this->assertCount(1, $message->getAttachments());
        $this->assertSame('image', $message->getAttachments()[0]->getMediaType());
        $this->assertMatchesRegularExpression('/<img src="cid:[^"]+"/', $message->getHtmlBody());
        $this->assertStringContainsString('<a href="https://crou-bondoukou.ci">https://crou-bondoukou.ci</a>', $message->getHtmlBody());
        $this->assertStringContainsString('<p>&nbsp;</p>', $message->getHtmlBody());
    }

    public function test_un_message_vide_est_refuse_mais_pas_un_message_long(): void
    {
        $this->put(route('admin.parametrage.configuration-messages.update'), ['messages' => [
            AdmissionSmsTemplates::DEFINITIVE => '',
            AdmissionSmsTemplates::ATTENTE => str_repeat('a', 2000).$this->variables(AdmissionSmsTemplates::ATTENTE),
            AdmissionSmsTemplates::NON_RETENUE => 'Texte '.$this->variables(AdmissionSmsTemplates::NON_RETENUE),
        ]])->assertSessionHasErrors(['messages.'.AdmissionSmsTemplates::DEFINITIVE])
            ->assertSessionDoesntHaveErrors(['messages.'.AdmissionSmsTemplates::ATTENTE]);

        $this->assertNull(ConfigApp::first()->admission_sms_templates);
    }
}
