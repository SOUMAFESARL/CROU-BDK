<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use Tests\TestCase;

class EtudiantLoginPageTest extends TestCase
{
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
        $this->assertSame(route('etudiant.register'), $page['props']['registerUrl']);
        $this->assertNotEmpty($page['props']['csrfToken']);
    }
}
