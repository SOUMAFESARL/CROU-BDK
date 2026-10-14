<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\immobilier\parc_immobilier\ParcImmobilierController;
use App\Http\Controllers\MapController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParcImmobilierPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $t) {
            $t->string('id_user')->primary(); $t->string('name'); $t->string('telephone_principal')->nullable();
            $t->integer('role_id')->nullable(); $t->string('campus_id')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('campus', function (Blueprint $t) {
            $t->string('id_campus')->primary(); $t->string('name'); $t->string('adresse')->nullable(); $t->string('statut')->default('actif');
            $t->float('latitude')->nullable(); $t->float('longitude')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('type_cite', function (Blueprint $t) { $t->string('id_type_cite')->primary(); $t->string('name'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('type_chambre', function (Blueprint $t) { $t->string('id_type_chambre')->primary(); $t->string('name'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('cite', function (Blueprint $t) {
            $t->string('id_cite')->primary(); $t->string('code_cite')->nullable(); $t->string('name');
            foreach (['type_cite_id', 'campus_id', 'adresse', 'responsable_id', 'statut', 'telephone1'] as $column) {
                $t->string($column)->nullable();
            }
            foreach (['wifi_gratuit', 'salle_etude', 'infirmerie', 'restaurant'] as $column) {
                $t->boolean($column)->default(false);
            }
            $t->float('latitude')->nullable(); $t->float('longitude')->nullable();
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('batiment', function (Blueprint $t) {
            $t->string('id_batiment')->primary(); $t->string('name');
            foreach (['cite_id', 'campus_id', 'responsable_id', 'type_occupation_id'] as $column) {
                $t->string($column)->nullable();
            }
            $t->string('statut')->default('actif'); $t->integer('total_etages')->default(0);
            $t->float('latitude')->nullable(); $t->float('longitude')->nullable(); $t->boolean('is_valid')->default(true);
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary();
            foreach (['numero_chambre', 'type_chambre_id', 'type_occupation_id', 'batiment_id', 'cite_id', 'niveau_aile_code', 'niveau_confort'] as $column) {
                $t->string($column)->nullable();
            }
            $t->string('statut')->default('actif'); $t->string('etat')->default('disponible');
            $t->integer('capacite_max')->default(1); $t->integer('nb_lits')->default(1); $t->integer('prix')->default(0);
            $t->decimal('superficie')->nullable(); $t->boolean('pmr')->default(false);
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('niveau_aile_batiment', function (Blueprint $t) {
            $t->increments('id_niveau_aile'); $t->string('niveau_aile_code'); $t->integer('niveau_numero'); $t->timestamps();
        });
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary(); $t->string('name'); $t->string('matricule')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary(); $t->string('etudiant_id'); $t->string('chambre_id'); $t->string('statut');
            $t->timestamp('date_assignation')->nullable(); $t->timestamp('date_fin')->nullable(); $t->timestamps();
        });

        DB::table('users')->insert(['id_user' => 'resp', 'name' => 'Kouassi Responsable', 'telephone_principal' => '0700000000']);
        DB::table('type_cite')->insert([['id_type_cite' => 'tg', 'name' => 'Garçons'], ['id_type_cite' => 'tf', 'name' => 'Filles']]);
        DB::table('type_chambre')->insert(['id_type_chambre' => 'simple', 'name' => 'Simple']);
        DB::table('cite')->insert([
            ['id_cite' => 'cA', 'code_cite' => 'CA', 'name' => 'Cité A', 'type_cite_id' => 'tg', 'responsable_id' => 'resp', 'statut' => 'actif'],
            ['id_cite' => 'cB', 'code_cite' => 'CB', 'name' => 'Cité B', 'type_cite_id' => 'tf', 'responsable_id' => null, 'statut' => 'desactive'],
        ]);
        foreach (range(1, 11) as $i) {
            DB::table('batiment')->insert(['id_batiment' => "b{$i}", 'name' => sprintf('Bâtiment %02d', $i), 'cite_id' => 'cA', 'type_occupation_id' => 'tg']);
        }
        DB::table('niveau_aile_batiment')->insert([['niveau_aile_code' => 'n1', 'niveau_numero' => 1], ['niveau_aile_code' => 'n2', 'niveau_numero' => 2]]);
        DB::table('chambre')->insert([
            ['id_chambre' => 'r1', 'numero_chambre' => '101', 'type_chambre_id' => 'simple', 'type_occupation_id' => 'tg', 'batiment_id' => 'b1', 'niveau_aile_code' => 'n1', 'etat' => 'occupee', 'capacite_max' => 2],
            ['id_chambre' => 'r2', 'numero_chambre' => '102', 'type_chambre_id' => 'simple', 'type_occupation_id' => 'tg', 'batiment_id' => 'b1', 'niveau_aile_code' => 'n1', 'etat' => 'disponible', 'capacite_max' => 2],
            ['id_chambre' => 'r3', 'numero_chambre' => '201', 'type_chambre_id' => 'simple', 'type_occupation_id' => 'tg', 'batiment_id' => 'b1', 'niveau_aile_code' => 'n2', 'etat' => 'maintenance', 'capacite_max' => 1],
            // Chambre non configurée (sans type) : ignorée partout, comme dans l'ancienne vue.
            ['id_chambre' => 'r4', 'numero_chambre' => '202', 'type_chambre_id' => null, 'type_occupation_id' => 'tg', 'batiment_id' => 'b1', 'niveau_aile_code' => 'n2', 'etat' => 'disponible', 'capacite_max' => 1],
        ]);
        DB::table('etudiants')->insert(['id_etudiant' => 'e1', 'name' => 'Awa Koné', 'matricule' => 'MAT01']);
        DB::table('assignation_chambres')->insert([
            ['id_assignation' => 'a1', 'etudiant_id' => 'e1', 'chambre_id' => 'r1', 'statut' => 'confirmee', 'date_assignation' => now()->subMonth(), 'date_fin' => null],
            ['id_assignation' => 'a2', 'etudiant_id' => 'e1', 'chambre_id' => 'r2', 'statut' => 'annulee', 'date_assignation' => now()->subMonth(), 'date_fin' => null],
        ]);

        $this->actingAs(new User(['id_user' => 'actor', 'role_id' => 1, 'name' => 'Admin']), 'admin');
    }

    public function test_liste_des_cites_rendue_avec_inertia_et_agregats(): void
    {
        $page = $this->parcPage([]);

        $this->assertSame('Admin/Hebergement/ParcImmobilier/Index', $page['component']);
        $props = $page['props'];
        $this->assertSame('cite', $props['niveau']);
        $this->assertSame(['cites' => 2, 'batiments' => 11, 'chambres' => 3, 'disponibles' => 1, 'occupees' => 1, 'reservees' => 0, 'maintenance' => 1, 'places' => 5, 'occupants' => 1], $props['stats']);

        $cites = collect($props['items']['data'])->keyBy('id');
        $this->assertSame(['cA', 'cB'], $cites->keys()->all());
        $this->assertSame('Kouassi Responsable - 0700000000', $cites['cA']['responsable']);
        $this->assertSame('Kouassi Responsable', $cites['cA']['responsable_nom']);
        $this->assertNull($cites['cB']['responsable_nom']);
        $this->assertSame(11, $cites['cA']['batiments']);
        $this->assertSame(3, $cites['cA']['resume']['chambres']);
        $this->assertSame(5, $cites['cA']['resume']['places']);
        $this->assertSame(1, $cites['cA']['resume']['occupants']);
        $this->assertSame(['Garçons' => 1], $cites['cA']['resume']['categories']);
        $this->assertSame(0, $cites['cB']['resume']['chambres']);
        $this->assertNotNull($cites['cA']['urls']['edit']);
        $this->assertNotNull($cites['cA']['urls']['status']);
        $this->assertSame([], $props['plan']);
    }

    public function test_cites_filtrees_par_statut(): void
    {
        $props = $this->parcPage(['statut' => 'desactive'])['props'];

        $this->assertSame(['actif', 'desactive'], $props['statutsCite']);
        $this->assertSame('desactive', $props['filters']['statut']);
        $this->assertSame(['cB'], array_column($props['items']['data'], 'id'));

        // Un statut absent du parc est ignoré : toutes les cités sont listées.
        $inconnu = $this->parcPage(['statut' => 'nimporte'])['props'];
        $this->assertSame('', $inconnu['filters']['statut']);
        $this->assertCount(2, $inconnu['items']['data']);
    }

    public function test_batiments_d_une_cite_pagines_par_dix(): void
    {
        $props = $this->parcPage(['cite' => 'cA'])['props'];

        $this->assertSame('batiment', $props['niveau']);
        $this->assertSame('Cité A', $props['contexte']['cite']['name']);
        $this->assertSame(3, $props['contexte']['cite']['resume']['chambres']);
        $this->assertCount(10, $props['items']['data']);
        $this->assertSame(11, $props['items']['total']);
        $b1 = $props['items']['data'][0];
        $this->assertSame('b1', $b1['id']);
        $this->assertSame(2, $b1['niveaux']);
        $this->assertSame(['chambres' => 3, 'disponibles' => 1, 'occupees' => 1, 'reservees' => 0, 'maintenance' => 1], array_intersect_key($b1['resume'], array_flip(['chambres', 'disponibles', 'occupees', 'reservees', 'maintenance'])));

        $recherche = $this->parcPage(['cite' => 'cA', 'q' => '11'])['props'];
        $this->assertSame(['b11'], array_column($recherche['items']['data'], 'id'));
    }

    public function test_batiments_filtres_par_statut(): void
    {
        DB::table('batiment')->where('id_batiment', 'b3')->update(['statut' => 'desactive']);

        $props = $this->parcPage(['cite' => 'cA', 'statut' => 'desactive'])['props'];

        $this->assertSame(['actif', 'desactive'], $props['statutsBatiment']);
        $this->assertSame('desactive', $props['filters']['statut']);
        $this->assertSame(['b3'], array_column($props['items']['data'], 'id'));

        // Un statut absent des bâtiments de la cité est ignoré.
        $inconnu = $this->parcPage(['cite' => 'cA', 'statut' => 'nimporte'])['props'];
        $this->assertSame('', $inconnu['filters']['statut']);
        $this->assertSame(11, $inconnu['items']['total']);
    }

    public function test_chambres_d_un_batiment_avec_plan_et_filtre_etat(): void
    {
        $props = $this->parcPage(['batiment' => 'b1'])['props'];

        $this->assertSame('chambre', $props['niveau']);
        // La cité est retrouvée depuis le bâtiment pour le fil de navigation.
        $this->assertSame('cA', $props['contexte']['cite']['id']);
        $this->assertSame(['101', '102', '201'], array_column($props['plan'], 'name'));
        $r1 = collect($props['plan'])->firstWhere('id', 'r1');
        $this->assertSame(1, $r1['niveau']);
        $this->assertSame([['nom' => 'Awa Koné', 'matricule' => 'MAT01']], $r1['etudiants']);
        $this->assertNull($r1['urls']['edit']);
        $this->assertNotNull($props['urls']['updateChambre']);
        $this->assertSame([['id' => 'simple', 'name' => 'Simple']], $props['typesChambre']);

        $filtre = $this->parcPage(['batiment' => 'b1', 'etat' => 'disponible'])['props'];
        $this->assertSame(['r2'], array_column($filtre['items']['data'], 'id'));
        $this->assertCount(3, $filtre['plan']);

        // Un état inconnu est ignoré.
        $this->assertSame('', $this->parcPage(['batiment' => 'b1', 'etat' => 'nimporte'])['props']['filters']['etat']);
    }

    public function test_carte_du_parc_avec_positions_et_occupation(): void
    {
        DB::table('cite')->where('id_cite', 'cA')->update(['latitude' => 8.04, 'longitude' => -2.8, 'wifi_gratuit' => true]);
        DB::table('batiment')->where('id_batiment', 'b1')->update(['latitude' => 8.041, 'longitude' => -2.801]);
        DB::table('batiment')->where('id_batiment', 'b2')->update(['is_valid' => false]);
        DB::table('campus')->insert([
            ['id_campus' => 'k1', 'name' => 'Campus Bondoukou', 'latitude' => 8.05, 'longitude' => -2.79],
            ['id_campus' => 'k2', 'name' => 'Campus sans position', 'latitude' => null, 'longitude' => null],
        ]);

        $request = Request::create('/admin/map', 'GET');
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn ($guard = null) => auth('admin')->user());
        $this->app->instance('request', $request);
        $page = app()->call([app(MapController::class), 'index'], ['request' => $request])->toResponse($request)->getData(true);

        $this->assertSame('Admin/Hebergement/Carte/Index', $page['component']);
        $props = $page['props'];
        $cites = collect($props['cites'])->keyBy('id');
        $this->assertSame(['lat' => 8.04, 'lng' => -2.8], $cites['cA']['position']);
        $this->assertNull($cites['cB']['position']);
        $this->assertSame('Kouassi Responsable', $cites['cA']['responsable']);
        $this->assertSame(['Wi-Fi gratuit'], $cites['cA']['services']);
        // Bâtiment invalide exclu : 10 bâtiments valides sur 11.
        $this->assertSame(10, $cites['cA']['batiments']);
        $this->assertSame(['chambres' => 3, 'disponibles' => 1, 'places' => 5, 'occupants' => 1], $cites['cA']['resume']);
        $this->assertCount(10, $props['batiments']);
        $this->assertSame(['lat' => 8.041, 'lng' => -2.801], collect($props['batiments'])->firstWhere('id', 'b1')['position']);
        $this->assertNotNull($cites['cA']['urls']['parc']);
        $this->assertSame(['k1'], array_column($props['campus'], 'id'));
    }

    private function parcPage(array $query): array
    {
        $request = Request::create('/admin/gestion-hebergement/parc-immobilier', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn ($guard = null) => auth('admin')->user());
        $this->app->instance('request', $request);

        return app()->call([app(ParcImmobilierController::class), 'index'], ['request' => $request])->toResponse($request)->getData(true);
    }
}
