<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EtudiantLoginPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // La vue Inertia lit la configuration système (nom, logo…).
        Schema::create('config_app', function (Blueprint $table) {
            $table->string('config_id')->primary();
            $table->timestamps();
        });

        Schema::create('campagne', function (Blueprint $table) {
            $table->string('id_campagne')->primary();
            $table->string('statut')->nullable();
            $table->date('periode_debut')->nullable();
            $table->date('periode_fin')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function loginPageProps(): array
    {
        $this->withoutVite();
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);

        $html = $this->get('/login')->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? ''), true)['props'] ?? [];
    }

    public function test_lien_de_demande_affiche_pendant_une_campagne_ouverte(): void
    {
        DB::table('campagne')->insert([
            'id_campagne' => 'c1',
            'statut' => 'active',
            'periode_debut' => today()->subDay()->toDateString(),
            'periode_fin' => today()->addDay()->toDateString(),
        ]);

        $this->assertSame(route('etudiant.register'), $this->loginPageProps()['registerUrl']);
    }

    public function test_lien_de_demande_masque_hors_periode_d_admission(): void
    {
        DB::table('campagne')->insert([
            'id_campagne' => 'c1',
            'statut' => 'active',
            'periode_debut' => today()->subMonth()->toDateString(),
            'periode_fin' => today()->subDay()->toDateString(),
        ]);

        $this->assertNull($this->loginPageProps()['registerUrl']);
    }

    public function test_etudiant_login_page_renders_inertia_component(): void
    {
        $this->withoutVite();
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);

        // Un middleware global reconstruit la réponse : assertInertia() n'y a pas accès, on lit donc data-page.
        $html = $this->get('/login')->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);
        $page = json_decode(html_entity_decode($matches[1] ?? ''), true);

        $this->assertSame('Etudiant/Auth/Login', $page['component'] ?? null);
        $this->assertSame(route('login.etudiant'), $page['props']['loginUrl']);
        $this->assertNull($page['props']['registerUrl']);
        $this->assertNotEmpty($page['props']['csrfToken']);
    }

    public function test_home_page_shows_login_space_choice(): void
    {
        $this->withoutVite();
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);

        $html = $this->get('/')->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);
        $page = json_decode(html_entity_decode($matches[1] ?? ''), true);

        $this->assertSame('Auth/Choix', $page['component'] ?? null);
        $this->assertSame(route('admin.login'), $page['props']['adminLoginUrl']);
        $this->assertSame(route('etudiant.login'), $page['props']['etudiantLoginUrl']);
        $this->assertNotSame('/admin', parse_url($page['props']['adminLoginUrl'], PHP_URL_PATH));
    }

    public function test_admin_root_redirects_to_home(): void
    {
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);

        $this->get('/admin')->assertRedirect('/');
    }
}
