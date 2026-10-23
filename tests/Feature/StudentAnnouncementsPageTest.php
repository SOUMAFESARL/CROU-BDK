<?php

namespace Tests\Feature;

use App\Models\Etudiant;
use App\Services\EtudiantAnnonces;
use App\Services\EtudiantMenu;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Page React « Annonces » de l'espace étudiant (Etudiant/Annonces, `?section=annonce`). */
class StudentAnnouncementsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('annonces', function (Blueprint $t) {
            $t->string('id_annonce')->primary();
            foreach (['titre', 'statut', 'priorite', 'audience', 'created_by', 'updated_by', 'deleted_by'] as $f) {
                $t->string($f)->nullable();
            }
            foreach (['contenu', 'canaux', 'audience_cible', 'pieces_jointes'] as $f) {
                $t->text($f)->nullable();
            }
            foreach (['date_publication', 'date_expiration', 'date_planification'] as $f) {
                $t->timestamp($f)->nullable();
            }
            foreach (['vues', 'clics', 'reactions'] as $f) {
                $t->integer($f)->default(0);
            }
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->string('id_user')->primary();
            $t->string('code')->nullable();
            $t->string('name')->nullable();
            $t->softDeletes();
        });
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary();
            $t->softDeletes();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary();
            $t->string('cite_id');
            $t->string('batiment_id');
            $t->softDeletes();
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary();
            $t->string('etudiant_id');
            $t->string('chambre_id');
            $t->string('statut');
            $t->timestamp('date_assignation');
            $t->timestamp('date_fin')->nullable();
        });

        (require database_path('migrations/2026_10_23_000001_create_annonce_likes.php'))->up();

        DB::table('users')->insert(['id_user' => 'admin', 'code' => 'ADM1', 'name' => 'Service hébergement']);
        DB::table('etudiants')->insert([['id_etudiant' => 'a'], ['id_etudiant' => 'b']]);
    }

    /** Insertion directe : pas de diffusion (hooks du modèle Annonce). */
    private function annonce(string $id, array $donnees = []): void
    {
        DB::table('annonces')->insert(array_merge([
            'id_annonce' => $id, 'titre' => 'Annonce '.$id, 'contenu' => '<p>Bonjour <strong>résident</strong></p>',
            'canaux' => json_encode(['flux']), 'audience' => 'all', 'statut' => 'publie', 'priorite' => 'normale',
            'date_publication' => now()->subDay(), 'created_by' => 'ADM1',
        ], $donnees));
    }

    private function props(array $query = []): array
    {
        return app(EtudiantAnnonces::class)->props(Etudiant::findOrFail('a'), Request::create('/etudiant/dashboard', 'GET', ['section' => 'annonce'] + $query));
    }

    public function test_only_visible_announcements_are_listed_with_counters(): void
    {
        $this->annonce('urgent', ['priorite' => 'urgente', 'titre' => 'Coupure d’eau', 'contenu' => '<p>Coupure&nbsp;d’eau <em>demain</em></p><p>de 8h à 12h</p>',
            'pieces_jointes' => json_encode(['https://cdn.test/annonce/planning.pdf', 'https://cdn.test/annonce/def.png'])]);
        $this->annonce('ancienne', ['date_publication' => now()->subDays(10)]);
        $this->annonce('ciblee', ['priorite' => 'haute', 'audience' => 'specific', 'audience_cible' => json_encode(['etudiants' => ['a']])]);
        $this->annonce('autre-etudiant', ['audience' => 'specific', 'audience_cible' => json_encode(['etudiants' => ['b']])]);
        $this->annonce('email', ['canaux' => json_encode(['email'])]);
        $this->annonce('expiree', ['date_expiration' => now()->subHour()]);
        $this->annonce('brouillon', ['statut' => 'brouillon']);

        $props = $this->props();

        $this->assertSame(['toutes' => 3, 'urgente' => 1, 'haute' => 1, 'normale' => 1, 'nouvelles' => 2], $props['compteurs']);
        $this->assertSame(['urgent', 'ciblee', 'ancienne'], array_column($props['annonces'], 'id'));
        $urgente = $props['annonces'][0];
        $this->assertSame(['cle' => 'urgente', 'libelle' => 'Urgente', 'ton' => 'danger'], $urgente['priorite']);
        $this->assertSame('Coupure d’eau demain de 8h à 12h', str_replace("\u{a0}", ' ', $urgente['extrait']));
        $this->assertSame('Service hébergement', $urgente['auteur']);
        $this->assertTrue($urgente['nouvelle']);
        $this->assertFalse($props['annonces'][2]['nouvelle']);
        $this->assertSame([['url' => 'https://cdn.test/annonce/planning.pdf', 'nom' => 'planning.pdf']], $urgente['piecesJointes']);
        $this->assertSame(3, $props['pagination']['total']);
        $this->assertNull($props['selection']);
    }

    public function test_channel_and_priority_filters_search_and_shared_link(): void
    {
        $this->annonce('urgent', ['priorite' => 'urgente', 'titre' => 'Coupure d’eau', 'canaux' => json_encode(['flux', 'sms'])]);
        $this->annonce('fete', ['priorite' => 'haute', 'titre' => 'Fête de la résidence', 'contenu' => '<p>Rendez-vous au 100% foyer</p>']);
        $this->annonce('autre-etudiant', ['audience' => 'specific', 'audience_cible' => json_encode(['etudiants' => ['b']])]);

        $props = $this->props();
        $this->assertSame(['flux', 'sms', 'email', 'push'], array_column($props['canaux'], 'cle'));
        $this->assertSame(['Normale', 'Haute', 'Urgente'], array_column($props['priorites'], 'libelle'));
        $this->assertSame(['Flux d’actualité', 'SMS'], $props['annonces'][0]['canaux']);
        $this->assertSame('Haute', $props['annonces'][1]['priorite']['libelle']);

        $this->assertSame(['urgent'], array_column($this->props(['canal' => 'sms'])['annonces'], 'id'));
        $this->assertSame(['urgent'], array_column($this->props(['priorite' => 'urgente'])['annonces'], 'id'));
        $this->assertSame([], $this->props(['canal' => 'sms', 'priorite' => 'haute'])['annonces']);
        $this->assertSame(['canal' => '', 'priorite' => '', 'search' => ''], $this->props(['canal' => 'fax', 'priorite' => 'max'])['filtres']);

        $recherche = $this->props(['search' => '  fête  ']);
        $this->assertSame('fête', $recherche['filtres']['search']);
        $this->assertSame(['fete'], array_column($recherche['annonces'], 'id'));
        // Les jokers SQL saisis sont recherchés littéralement.
        $this->assertSame(['fete'], array_column($this->props(['search' => '100%'])['annonces'], 'id'));
        $this->assertSame([], $this->props(['search' => '_oupure'])['annonces']);
        // Les compteurs ne dépendent pas de la recherche.
        $this->assertSame(2, $recherche['compteurs']['toutes']);

        $this->assertSame('fete', $this->props(['annonce' => 'fete'])['selection']['id']);
        $this->assertNull($this->props(['annonce' => 'autre-etudiant'])['selection']);
    }

    public function test_like_toggles_once_per_student_and_only_on_visible_announcements(): void
    {
        $this->annonce('info', ['reactions' => 4]);
        $this->annonce('autre-etudiant', ['audience' => 'specific', 'audience_cible' => json_encode(['etudiants' => ['b']])]);
        $service = app(EtudiantAnnonces::class);
        $etudiant = Etudiant::findOrFail('a');

        $this->assertFalse($this->props()['annonces'][0]['aime']);
        $this->assertTrue($service->basculerJaime($etudiant, 'info'));
        $annonce = $this->props()['annonces'][0];
        $this->assertTrue($annonce['aime']);
        $this->assertSame(5, $annonce['jaimes']);
        $this->assertSame(route('etudiants.annonce.jaime', 'info'), $annonce['urlJaime']);
        $this->assertTrue($this->props(['annonce' => 'info'])['selection']['aime']);
        $this->assertDatabaseCount('annonce_likes', 1);

        $this->assertFalse($service->basculerJaime($etudiant, 'info'));
        $this->assertFalse($this->props()['annonces'][0]['aime']);
        $this->assertSame(4, (int) DB::table('annonces')->where('id_annonce', 'info')->value('reactions'));
        $this->assertDatabaseCount('annonce_likes', 0);

        try {
            $service->basculerJaime($etudiant, 'autre-etudiant');
            $this->fail('Une annonce non visible ne doit pas pouvoir être aimée.');
        } catch (\Illuminate\Validation\ValidationException) {
            $this->assertDatabaseCount('annonce_likes', 0);
        }
    }

    public function test_section_is_served_by_react(): void
    {
        $this->assertContains('annonce', EtudiantMenu::SECTIONS_REACT);
        $this->assertFileDoesNotExist(resource_path('views/etudiant/annonce.blade.php'));
    }
}
