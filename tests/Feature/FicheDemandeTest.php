<?php

namespace Tests\Feature;

use App\Http\Services\FicheDemandeService;
use App\Models\Etudiant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Fiche de demande (PDF) : lien signé de la fin du formulaire public et téléchargement depuis l'espace étudiant. */
class FicheDemandeTest extends \Tests\Support\DossierTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::table('etudiants', function (Blueprint $t) {
            foreach (['email1', 'telephone1', 'numero_piece', 'matricule', 'password', 'nationalite', 'lieu_naissance', 'adresse'] as $champ) {
                $t->string($champ)->nullable();
            }
            foreach (['type_piece_id', 'regions_id', 'ville_id'] as $champ) {
                $t->unsignedBigInteger($champ)->nullable();
            }
            $t->date('date_naissance')->nullable();
            $t->date('date_expiration')->nullable();
            $t->decimal('distance')->nullable();
        });
        Schema::table('demande', function (Blueprint $t) {
            foreach (['niveau_etude_precedent_code', 'filiere_code', 'ville_bac', 'num_table_bac'] as $champ) {
                $t->string($champ)->nullable();
            }
            $t->decimal('moyenne')->nullable();
            $t->integer('credit_valider')->nullable();
            $t->boolean('boursier')->default(false);
        });
        Schema::table('campagne', function (Blueprint $t) {
            $t->string('libelle')->nullable();
            $t->string('annee_campagne')->nullable();
        });
        foreach (['type_piece', 'regions', 'villes'] as $table) {
            Schema::create($table, function (Blueprint $t) { $t->id(); $t->string('name'); });
        }
        Schema::create('filiere', function (Blueprint $t) { $t->string('id_filiere')->primary(); $t->string('name'); });
        Schema::create('document_etudiant', function (Blueprint $t) {
            $t->string('id_document')->primary(); $t->string('id_campagne')->nullable(); $t->string('etudiant_id');
            $t->string('type_document')->nullable(); $t->timestamps();
        });
        DB::table('campagne')->update(['annee_campagne' => '2026-2027']);
        DB::table('niveau_etude')->insert([['id_niveau_etude' => 'tle', 'name' => 'Terminale'], ['id_niveau_etude' => 'l1', 'name' => 'Licence 1']]);
        DB::table('filiere')->insert(['id_filiere' => 'info', 'name' => 'Informatique']);
        DB::table('etudiants')->where('id_etudiant', 'student')->update([
            'name' => 'KOUASSI Aya', 'email1' => 'aya@exemple.com', 'telephone1' => '0701020304', 'numero_piece' => 'CI-12345',
            'adresse' => 'Quartier Zanzan', 'date_naissance' => '2006-05-12',
        ]);
        DB::table('document_etudiant')->insert([
            ['id_document' => 'p1', 'id_campagne' => 'campaign', 'etudiant_id' => 'student', 'type_document' => 'CNI'],
            ['id_document' => 'p2', 'id_campagne' => 'ancienne', 'etudiant_id' => 'student', 'type_document' => 'Ancienne pièce'],
        ]);
    }

    private function fiche(): FicheDemandeService
    {
        return app(FicheDemandeService::class);
    }

    private function demandeBachelier(): \App\Models\DemandeEtudiant
    {
        $demande = $this->dossier();
        DB::table('demande')->where('id_demande', $demande->getKey())->update([
            'niveau_etude_precedent_code' => 'tle', 'niveau_etude_actuel_code' => 'l1', 'filiere_code' => 'info',
            'moyenne' => 12.5, 'num_table_bac' => '25AB123',
        ]);

        return $demande->fresh();
    }

    public function test_la_fiche_reprend_les_informations_declarees_et_les_pieces_de_la_campagne(): void
    {
        $demande = $this->demandeBachelier();
        $donnees = $this->fiche()->donnees($demande);

        $this->assertSame($demande->numero_demande, $donnees['reference']);
        $this->assertSame('2026-2027', $donnees['campagne']);
        $this->assertSame('KOUASSI Aya', $donnees['rubriques']['Identité']['Nom et prénoms']);
        $this->assertSame('12/05/2006', $donnees['rubriques']['Identité']['Date et lieu de naissance']);
        $this->assertSame('250 / 400', $donnees['rubriques']['Études']['Note obtenue au bac']);
        $this->assertArrayNotHasKey('Moyenne générale', $donnees['rubriques']['Études']);
        $this->assertSame('Informatique', $donnees['rubriques']['Études']['Filière']);
        $this->assertSame(['CNI'], $donnees['pieces']);

        // L'état du dossier n'est jamais imprimé : le résultat attend la publication des listes.
        $html = view('pdf.fiche-demande', $donnees)->render();
        $this->assertStringContainsString($demande->numero_demande, $html);
        $this->assertStringNotContainsString('Recevable', $html);
        $this->assertStringNotContainsString('Soumis', $html);
    }

    public function test_compte_existant_reconnu_sans_mot_de_passe_ne_revele_pas_ses_donnees_personnelles(): void
    {
        $html = view('pdf.fiche-demande', $this->fiche()->donnees($this->demandeBachelier(), true))->render();

        foreach (['0701020304', 'CI-12345', 'aya@exemple.com', 'Quartier Zanzan', '12/05/2006'] as $secret) {
            $this->assertStringNotContainsString($secret, $html);
        }
    }

    public function test_le_lien_public_exige_une_signature_intacte_et_non_expiree(): void
    {
        $demande = $this->demandeBachelier();
        $lien = $this->fiche()->lienPublic($demande);
        $restreint = $this->fiche()->lienPublic($demande, true);

        $reponse = $this->get($lien)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('fiche-demande-'.$demande->numero_demande.'.pdf', $reponse->headers->get('content-disposition'));
        $this->get($restreint)->assertOk();

        $this->get('/demande/'.$demande->getKey().'/fiche')->assertForbidden();
        $this->get(str_replace($demande->getKey(), 'autre', $lien))->assertForbidden();
        // Le caractère restreint fait partie de la signature.
        $this->get(str_replace('restreinte=1', 'restreinte=0', $restreint))->assertForbidden();

        $this->travel(FicheDemandeService::VALIDITE_HEURES * 60 + 1)->minutes();
        $this->get($lien)->assertForbidden();
    }

    public function test_un_brouillon_n_a_pas_de_fiche(): void
    {
        $brouillon = $this->dossier('student', true);

        $this->assertNull($this->fiche()->lienEspaceEtudiant($brouillon));
        $this->get($this->fiche()->lienPublic($brouillon))->assertNotFound();
    }

    public function test_un_etudiant_ne_telecharge_que_ses_propres_fiches(): void
    {
        $demande = $this->demandeBachelier();
        $lien = $this->fiche()->lienEspaceEtudiant($demande);

        $this->actingAs(Etudiant::findOrFail('student'), 'etudiant')->get($lien)
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs(Etudiant::findOrFail('other'), 'etudiant')->get($lien)->assertNotFound();
    }
}
