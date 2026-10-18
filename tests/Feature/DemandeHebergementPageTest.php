<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
}
