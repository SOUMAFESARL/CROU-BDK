<?php

namespace Tests\Feature;

use App\Http\Services\ReferentielLibreService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Saisie libre de la filière, de la région et de la ville dans la demande d'hébergement. */
class ReferentielLibreServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('filiere', function (Blueprint $t) {
            $t->increments('id_filiere');
            $t->string('code_filiere');
            $t->string('name')->nullable();
            $t->timestamps();
        });
        Schema::create('regions', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('villes', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name');
            $t->integer('region_id');
            $t->timestamps();
        });

        DB::table('filiere')->insert(['id_filiere' => 4, 'code_filiere' => 'GC', 'name' => 'Génie Civil']);
        DB::table('regions')->insert(['id' => 7, 'name' => 'Gontougo']);
        DB::table('villes')->insert(['id' => 3, 'name' => 'Bondoukou', 'region_id' => 7]);
    }

    private function appliquer(array $donnees): Request
    {
        $request = Request::create('/register', 'POST', $donnees);
        app(ReferentielLibreService::class)->appliquer($request);

        return $request;
    }

    public function test_valeur_existante_reutilisee_sans_tenir_compte_des_accents_ni_de_la_casse(): void
    {
        $request = $this->appliquer(['filiere' => '', 'filiere_autre' => '  genie   civil ', 'region' => '7', 'ville' => '', 'ville_autre' => 'BONDOUKOU']);

        $this->assertSame('4', $request->input('filiere'));
        $this->assertSame('3', $request->input('ville'));
        $this->assertSame('3', $request->input('ville_id'));
        $this->assertSame(1, DB::table('filiere')->count());
        $this->assertSame(1, DB::table('villes')->count());
    }

    public function test_valeur_inconnue_ajoutee_au_referentiel(): void
    {
        $request = $this->appliquer(['filiere_autre' => 'Agronomie', 'region' => '', 'region_autre' => 'Bounkani', 'ville' => '', 'ville_autre' => 'Bouna']);

        $filiere = DB::table('filiere')->where('name', 'Agronomie')->first();
        $region = DB::table('regions')->where('name', 'Bounkani')->first();
        $ville = DB::table('villes')->where('name', 'Bouna')->first();

        $this->assertNotEmpty($filiere->code_filiere);
        $this->assertSame((string) $filiere->id_filiere, $request->input('filiere'));
        $this->assertSame((string) $region->id, $request->input('region'));
        $this->assertSame((string) $region->id, $request->input('regions_id'));
        $this->assertSame($region->id, (int) $ville->region_id);
        $this->assertSame((string) $ville->id, $request->input('ville'));
    }

    public function test_sans_saisie_libre_la_requete_est_inchangee(): void
    {
        $request = $this->appliquer(['filiere' => '4', 'region' => '7', 'ville' => '3']);

        $this->assertSame(['filiere' => '4', 'region' => '7', 'ville' => '3'], $request->all());
    }
}
