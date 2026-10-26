<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use Illuminate\Database\Schema\Blueprint;
use App\Http\Controllers\web\frontedController;
use App\Models\Etudiant;
use App\Notifications\EtudiantIdentifiantsNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Page publique /register (React) : données de la campagne ouverte et référentiels du formulaire. */
class DemandeHebergementPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('config_app', fn (Blueprint $t) => $t->string('config_id')->primary());
        Schema::create('campagne', function (Blueprint $t) {
            $t->string('id_campagne')->primary();
            $t->string('annee_campagne')->nullable();
            $t->string('statut')->nullable();
            $t->date('periode_debut')->nullable();
            $t->date('periode_fin')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('document_campagne', function (Blueprint $t) {
            $t->string('id_document_campagne')->primary();
            $t->string('id_campagne');
            $t->string('document_id');
            $t->timestamps();
        });
        Schema::create('document', function (Blueprint $t) {
            $t->string('id_document')->primary();
            $t->string('name');
            $t->string('slug');
            $t->string('format_accepte')->nullable();
            $t->boolean('champs_requis')->default(false);
            $t->integer('ordre_affiche')->default(0);
            $t->timestamps();
        });
        foreach (['genre', 'type_piece', 'groupe_sanguins', 'regions'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->timestamps();
            });
        }
        Schema::create('villes', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('region_id');
            $t->timestamps();
        });
        Schema::create('niveau_etude', function (Blueprint $t) {
            $t->string('id_niveau_etude')->primary();
            $t->string('name');
            $t->unsignedInteger('ordre')->default(0);
            $t->timestamps();
        });
        Schema::create('filiere', function (Blueprint $t) {
            $t->string('id_filiere')->primary();
            $t->string('name');
            $t->timestamps();
        });

        DB::table('genre')->insert(['id' => 1, 'name' => 'Madame']);
        DB::table('regions')->insert(['id' => 7, 'name' => 'Gontougo']);
        DB::table('villes')->insert(['id' => 3, 'name' => 'Bondoukou', 'region_id' => 7]);
        DB::table('niveau_etude')->insert(['id_niveau_etude' => 'l1', 'name' => 'Licence 1']);
        DB::table('filiere')->insert(['id_filiere' => 'info', 'name' => 'Informatique']);
    }

    private function pageProps(): array
    {
        $this->withoutVite();
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);

        $html = $this->get('/register')->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);
        $page = json_decode(html_entity_decode($matches[1] ?? ''), true);
        $this->assertSame('Etudiant/Auth/Demande', $page['component'] ?? null);

        return $page['props'];
    }

    private function campagneOuverte(): void
    {
        DB::table('campagne')->insert([
            'id_campagne' => 'c1',
            'annee_campagne' => '2026-2027',
            'statut' => 'active',
            'periode_debut' => today()->subDay()->toDateString(),
            'periode_fin' => today()->addMonth()->toDateString(),
            'created_at' => now(),
        ]);
        DB::table('document')->insert([
            ['id_document' => 'd1', 'name' => 'Extrait de naissance', 'slug' => 'extrait-de-naissance', 'format_accepte' => 'pdf', 'champs_requis' => true, 'ordre_affiche' => 2],
            ['id_document' => 'd2', 'name' => 'CMU', 'slug' => 'cmu', 'format_accepte' => 'pdf, jpg', 'champs_requis' => false, 'ordre_affiche' => 1],
        ]);
        DB::table('document_campagne')->insert([
            ['id_document_campagne' => 'dc1', 'id_campagne' => 'c1', 'document_id' => 'd1'],
            ['id_document_campagne' => 'dc2', 'id_campagne' => 'c1', 'document_id' => 'd2'],
        ]);
    }

    public function test_formulaire_recoit_la_campagne_ouverte_et_ses_documents(): void
    {
        $this->campagneOuverte();

        $props = $this->pageProps();

        $this->assertSame(['id' => 'c1', 'annee' => '2026-2027', 'dateLimite' => today()->addMonth()->format('d/m/Y')], $props['campagne']);
        $this->assertSame(['cmu', 'extrait-de-naissance'], array_column($props['documents'], 'slug'));
        $this->assertSame(['pdf', 'jpg'], $props['documents'][0]['formats']);
        $this->assertTrue($props['documents'][1]['requis']);
        $this->assertSame([['id' => '1', 'libelle' => 'Madame']], $props['genres']);
        $this->assertSame([['id' => '3', 'libelle' => 'Bondoukou', 'region' => '7']], $props['villes']);
        $this->assertSame([['id' => 'l1', 'libelle' => 'Licence 1']], $props['niveaux']);
        $this->assertSame(route('register.post'), $props['liens']['envoyer']);
    }

    public function test_sans_campagne_ouverte_le_formulaire_n_est_pas_propose(): void
    {
        DB::table('campagne')->insert([
            'id_campagne' => 'c0',
            'annee_campagne' => '2025-2026',
            'statut' => 'terminer',
            'periode_debut' => today()->subYear()->toDateString(),
            'periode_fin' => today()->subMonth()->toDateString(),
        ]);

        $props = $this->pageProps();

        $this->assertNull($props['campagne']);
        $this->assertSame([], $props['documents']);
    }

    public function test_le_serveur_exige_les_pieces_obligatoires_dans_le_bon_format(): void
    {
        $this->campagneOuverte();
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary();
            $t->string('email1')->nullable();
            $t->string('telephone1')->nullable();
            $t->string('numero_piece')->nullable();
            $t->string('matricule')->nullable();
        });
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class, \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $this->from('/register')->post('/register', [
            'id_campagne' => 'c1',
            'cmu' => \Illuminate\Http\UploadedFile::fake()->create('cmu.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors([
            'extrait-de-naissance' => 'Le document Extrait de naissance est obligatoire.',
            'cmu' => 'Le document CMU doit être au format pdf, jpg.',
        ]);

        $this->from('/register')->post('/register', [
            'id_campagne' => 'c1',
            'extrait-de-naissance' => \Illuminate\Http\UploadedFile::fake()->create('extrait.pdf', 3000, 'application/pdf'),
        ])->assertSessionHasErrors(['extrait-de-naissance' => 'Le document Extrait de naissance ne doit pas dépasser 2 Mo.'])
            ->assertSessionDoesntHaveErrors('cmu');
    }

    public function test_le_mot_de_passe_est_envoye_a_l_email_saisi_dans_la_demande(): void
    {
        Notification::fake();
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary();
            $t->string('email1');
            $t->string('password')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        DB::table('etudiants')->insert(['id_etudiant' => 'e1', 'email1' => 'aya@exemple.com']);
        $etudiant = Etudiant::findOrFail('e1');

        $message = (new \ReflectionMethod(frontedController::class, 'envoyerIdentifiants'))->invoke(app(frontedController::class), $etudiant);

        $this->assertStringContainsString('aya@exemple.com', $message);
        $motDePasse = null;
        Notification::assertSentOnDemand(EtudiantIdentifiantsNotification::class, function ($notification, $canaux, $destinataire) use (&$motDePasse) {
            $ligne = collect($notification->toMail($destinataire)->introLines)->first(fn ($ligne) => str_starts_with($ligne, 'Mot de passe : '));
            $motDePasse = substr((string) $ligne, strlen('Mot de passe : '));

            return $destinataire->routes['mail'] === 'aya@exemple.com';
        });
        $this->assertNotSame('', $motDePasse);
        $this->assertTrue(Hash::check($motDePasse, $etudiant->fresh()->password));
    }
}
