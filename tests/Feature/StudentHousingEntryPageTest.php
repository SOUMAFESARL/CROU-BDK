<?php

namespace Tests\Feature;

use App\Models\Etudiant;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentHousingEntryPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $settings = new \App\Models\ConfigApp(['app_name' => 'Residence test']);
        $settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(\App\Repositories\Interfaces\ConfigAppRepositoryInterface::class)
            ->shouldReceive('getConfig')->andReturn($settings);
        $this->withoutMiddleware([\App\Http\Middleware\EnforceSystemSecurity::class, \App\Http\Middleware\LogUserActivity::class]);
        (require database_path('migrations/2026_09_24_000001_create_housing_entry_workflow.php'))->up();
        (require database_path('migrations/2026_09_23_000002_create_student_contract_approvals.php'))->up();
        $this->actingAs(new Etudiant(['id_etudiant' => 'student', 'name' => 'Étudiant test', 'matricule' => 'TEST']), 'etudiant');
    }

    /** Page Inertia : composant et props lus dans data-page (un middleware global reconstruit la réponse). */
    private function inertiaPage(string $url): array
    {
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? ''), true) ?? [];
    }

    private function message(string $student, string $kind, string $status = 'sent'): void
    {
        DB::table('housing_entry_messages')->insert([
            'event_key' => $kind.':'.$student, 'student_id' => $student, 'kind' => $kind,
            'subject' => 'Objet '.$kind.' '.$student, 'body' => 'Corps', 'status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_page_shows_only_the_students_appointment_and_notifications(): void
    {
        $this->message('student', 'contract');
        $this->message('student', 'proposal', 'cancelled');
        $this->message('other', 'appointment');
        foreach (['student' => now()->addDays(2), 'other' => now()->addDay()] as $student => $start) {
            DB::table('housing_entry_appointments')->insert([
                'assignment_id' => 'a-'.$student, 'student_id' => $student, 'starts_at' => $start->setTime(9, 0),
                'ends_at' => $start->copy()->setTime(9, 30), 'location' => 'Bureau Logement', 'status' => 'scheduled',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $page = $this->inertiaPage(route('etudiants.housing-entry'));

        $this->assertSame('Etudiant/EntreeLogement', $page['component'] ?? null);
        $props = $page['props'];
        $this->assertSame('09:00', $props['prochainRendezVous']['debut']);
        $this->assertSame('programme', $props['prochainRendezVous']['statut']);
        $this->assertCount(1, $props['rendezVous']);
        $this->assertSame(2, $props['pagination']['total']);
        $this->assertEqualsCanonicalizing(['contract', 'proposal'], array_column($props['notifications'], 'type'));
        $this->assertTrue(collect($props['notifications'])->firstWhere('type', 'proposal')['annule']);
        $this->assertNull($props['contrat']);
        $this->assertStringNotContainsString('Objet appointment other', json_encode($props));
        $menu = collect($props['menuEtudiant'])->flatMap(fn ($groupe) => $groupe['items']);
        $this->assertTrue($menu->firstWhere('label', 'Mon entrée et notifications')['active']);
    }

    public function test_page_without_appointment_and_json_response_are_kept(): void
    {
        $page = $this->inertiaPage(route('etudiants.housing-entry'));
        $this->assertNull($page['props']['prochainRendezVous']);
        $this->assertSame([], $page['props']['notifications']);

        $this->getJson(route('etudiants.housing-entry'))->assertOk()->assertJsonStructure(['messages', 'appointments']);
    }
}
