<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use App\Models\ConfigApp;
use App\Models\Role;
use App\Models\User;
use App\Services\AdmissionSmsTemplates;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        $this->put(route('admin.parametrage.configuration-messages.update'), ['messages' => $messages])
            ->assertRedirect(route('admin.parametrage.configuration-messages.index'));

        $this->assertSame($messages[AdmissionSmsTemplates::ATTENTE], ConfigApp::first()->admission_sms_templates[AdmissionSmsTemplates::ATTENTE]);
        $this->assertSame($messages, $this->page()['props']['messages']);
    }

    public function test_la_mise_en_forme_est_nettoyee_et_le_sms_reste_en_texte_brut(): void
    {
        $cle = AdmissionSmsTemplates::DEFINITIVE;
        $messages = array_replace(AdmissionSmsTemplates::resolve([]), [$cle => 'Bravo à vous']);

        $this->put(route('admin.parametrage.configuration-messages.update'), [
            'messages' => $messages,
            'formatted' => [$cle => '<p onclick="x()"><strong>Bravo</strong> à vous<script>alert(1)</script></p>'],
        ])->assertSessionHasNoErrors();

        $props = $this->page()['props'];
        $this->assertSame('Bravo à vous', $props['messages'][$cle]);
        $this->assertSame('<p><strong>Bravo</strong> à vous</p>', $props['formatted'][$cle]);
    }

    public function test_un_message_vide_est_refuse_mais_pas_un_message_long(): void
    {
        $this->put(route('admin.parametrage.configuration-messages.update'), ['messages' => [
            AdmissionSmsTemplates::DEFINITIVE => '',
            AdmissionSmsTemplates::ATTENTE => str_repeat('a', 2000),
            AdmissionSmsTemplates::NON_RETENUE => 'Texte',
        ]])->assertSessionHasErrors(['messages.'.AdmissionSmsTemplates::DEFINITIVE])
            ->assertSessionDoesntHaveErrors(['messages.'.AdmissionSmsTemplates::ATTENTE]);

        $this->assertNull(ConfigApp::first()->admission_sms_templates);
    }
}
