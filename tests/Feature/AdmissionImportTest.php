<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckAdminAccess;
use App\Models\AssignationChambre;
use App\Models\DemandeEtudiant;
use App\Models\Etudiant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Import en masse des étudiants déjà logés (modèle Excel + lecture + enregistrement). */
class AdmissionImportTest extends \Tests\Support\DossierTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::table('etudiants', function (Blueprint $t) {
            foreach (['matricule', 'numero_etudiant', 'date_naissance', 'lieu_naissance', 'telephone1', 'telephone2', 'email1', 'email2',
                'nationalite', 'adresse', 'countrie_id', 'regions_id', 'ville_id', 'departement_code', 'faculte', 'moyenne',
                'annee_academique_actuel', 'type_piece_id', 'numero_piece', 'date_delivrance', 'date_expiration', 'groupe_sanguin_id',
                'name_tuteur', 'telephone_tuteur', 'adresse_tuteur', 'type_bourse', 'distance', 'date_inscription', 'date_validation',
                'users_validation', 'created_by'] as $column) {
                $t->string($column)->nullable();
            }
            foreach (['allergies', 'maladies_chroniques', 'autiste', 'boursier', 'is_validation', 'is_rejet'] as $column) {
                $t->boolean($column)->default(false);
            }
        });
        Schema::table('demande', function (Blueprint $t) {
            foreach (['type_hebergement_souhaite', 'annee_academique_precedent', 'niveau_etude_precedent_code', 'filiere_code',
                'credit_valider', 'periode', 'moyenne'] as $column) {
                $t->string($column)->nullable();
            }
            $t->boolean('boursier')->default(false);
        });
        Schema::table('campagne', fn (Blueprint $t) => $t->string('annee_campagne')->nullable());
        Schema::table('batiment', fn (Blueprint $t) => $t->boolean('is_valid')->default(true));
        DB::table('campagne')->update(['annee_campagne' => '2026-2027']);
        DB::table('cite')->update(['name' => 'Cité Universitaire']);
        DB::table('batiment')->update(['name' => 'Bâtiment A']);
        DB::table('chambre')->update(['numero_chambre' => '101', 'capacite_max' => 2]);
        $this->withoutMiddleware(CheckAdminAccess::class);
    }

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('etudiants.csv', $content);
    }

    private function import(UploadedFile $file)
    {
        return $this->from(route('admin.etudiant.admission_directe'))
            ->post(route('admin.etudiant.admission_directe.import'), ['fichier' => $file]);
    }

    public function test_template_uses_french_headers_and_is_readable_back(): void
    {
        $response = $this->get(route('admin.etudiant.admission_directe.template'))->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $lists = $zip->getFromName('xl/worksheets/sheet2.xml');
        $this->assertNotFalse($zip->getFromName('xl/styles.xml'));
        $zip->close();

        foreach (['nom_prenoms', 'civilite', 'matricule', 'telephone', 'cite', 'batiment', 'campagne', 'chambre', 'date_entree', 'numero_lit'] as $header) {
            $this->assertStringContainsString('<t>' . $header . '</t>', $sheet);
        }
        $this->assertStringNotContainsString('<t>name</t>', $sheet);
        $this->assertStringNotContainsString('<t>telephone1</t>', $sheet);
        $this->assertStringNotContainsString('freezePane', $sheet);
        // Chambres indexées par « cité|bâtiment » : pas de mélange entre bâtiments homonymes.
        $this->assertStringContainsString('Cité Universitaire|Bâtiment A', $lists);
        $this->assertStringContainsString('<t>2026-2027</t>', $lists);

        // Ligne d'exemple remplie avec des valeurs réelles de la base.
        $rows = (new \ReflectionMethod(\App\Http\Controllers\admin\Etudiant\etudiantController::class, 'readImportRows'))
            ->invoke(app(\App\Http\Controllers\admin\Etudiant\etudiantController::class), new UploadedFile($path, 'modele.xlsx', null, null, true));
        $this->assertCount(1, $rows);
        $this->assertStringStartsWith('EXEMPLE', $rows[0]['nom_prenoms']);
        $this->assertSame('Cité Universitaire', $rows[0]['cite']);
        $this->assertSame('Bâtiment A', $rows[0]['batiment']);
        $this->assertSame('101', $rows[0]['chambre']);
        $this->assertSame('2026-2027', $rows[0]['campagne']);
        $this->assertSame('0700000000', $rows[0]['telephone']);
    }

    public function test_example_line_left_in_the_template_is_ignored(): void
    {
        $this->import($this->csv("nom_prenoms;civilite;matricule;telephone;cite;batiment;campagne;chambre;date_entree;numero_lit\n"
            . "EXEMPLE - Kouassi Jean (ligne à remplacer);Masculin;MAT-0001;0700000000;Cité Universitaire;Bâtiment A;2026-2027;101;01/10/2026;1\n"
            . "Vrai Étudiant;Masculin;MAT040;0700000040;Cité Universitaire;Bâtiment A;2026-2027;101;01/10/2026;\n"))
            ->assertSessionHas('success');

        $this->assertNull(Etudiant::where('matricule', 'MAT-0001')->first());
        $this->assertNotNull(Etudiant::where('matricule', 'MAT040')->first());
    }

    public function test_import_with_template_headers_and_empty_bed_registers_the_resident(): void
    {
        $this->import($this->csv("\xEF\xBB\xBFnom_prenoms;civilite;matricule;telephone;cite;batiment;campagne;chambre;date_entree;numero_lit\n"
            . "Kouassi Jean;Masculin;MAT001;0700000001;Cité Universitaire;Bâtiment A;2026-2027;101;01/10/2026;\n"))
            ->assertRedirect(route('admin.hebergement.residents_contrat.admission'))->assertSessionHas('success')->assertSessionMissing('warning');

        $student = Etudiant::where('matricule', 'MAT001')->firstOrFail();
        $this->assertSame('Kouassi Jean', $student->name);
        $this->assertSame('residents', $student->statut_inscription);
        $this->assertSame('attribue_definitif', DemandeEtudiant::where('etudiant_id', $student->getKey())->firstOrFail()->etat_dossier);
        $assignment = AssignationChambre::where('etudiant_id', $student->getKey())->firstOrFail();
        $this->assertSame('room', $assignment->chambre_id);
        $this->assertSame(1, $assignment->lit->numero);
    }

    public function test_legacy_headers_and_windows_1252_csv_are_still_accepted(): void
    {
        $content = mb_convert_encoding("name;civilite;matricule;telephone1;cite;batiment;campagne;chambre;date_entree;numero_lit\n"
            . "Konan Awa;Féminin;MAT002;0700000002;Cité Universitaire;Bâtiment A;2026-2027;101;2026-10-01;2\n", 'Windows-1252', 'UTF-8');
        $this->import($this->csv($content))->assertSessionHas('success');

        $student = Etudiant::where('matricule', 'MAT002')->firstOrFail();
        $this->assertSame(2, AssignationChambre::where('etudiant_id', $student->getKey())->firstOrFail()->lit->numero);
    }

    public function test_rejected_lines_explain_what_to_fix_without_blocking_the_others(): void
    {
        $this->import($this->csv("nom_prenoms;civilite;matricule;telephone;cite;batiment;campagne;chambre;date_entree;numero_lit\n"
            . "Inconnu;Masculin;MAT010;0700000010;Cité Fantôme;Bâtiment A;2026-2027;101;01/10/2026;\n"
            . "Valide;Masculin;MAT011;0700000011;Cité Universitaire;Bâtiment A;2026-2027;101;01/10/2026;\n"))
            ->assertSessionHas('warning', fn (string $message) => str_contains($message, '1 étudiant(s)')
                && str_contains($message, 'Ligne 2 : Valeur à corriger : cite « Cité Fantôme » introuvable'));

        $this->assertNull(Etudiant::where('matricule', 'MAT010')->first());
        $this->assertNotNull(Etudiant::where('matricule', 'MAT011')->first());
    }

    public function test_full_room_is_reported_with_a_readable_reason(): void
    {
        DB::table('chambre')->update(['capacite_max' => 1]);
        $this->import($this->csv("nom_prenoms;civilite;matricule;telephone;cite;batiment;campagne;chambre;date_entree;numero_lit\n"
            . "Premier;Masculin;MAT020;0700000020;Cité Universitaire;Bâtiment A;2026-2027;101;01/10/2026;\n"
            . "Second;Masculin;MAT021;0700000021;Cité Universitaire;Bâtiment A;2026-2027;101;01/10/2026;\n"))
            ->assertSessionHas('warning', fn (string $message) => str_contains($message, 'Ligne 3 : La chambre « 101 » n\'est plus disponible'));
    }

    public function test_phone_already_used_by_another_student_is_reported_instead_of_reusing_his_record(): void
    {
        $this->import($this->csv("nom_prenoms;civilite;matricule;telephone;cite;batiment;campagne;chambre;date_entree;numero_lit\n"
            . "Akesse Martin;Masculin;MAT040;0700000040;Cité Universitaire;Bâtiment A;2026-2027;101;01/10/2026;\n"))
            ->assertSessionHas('success');

        // Nouveau fichier : autre étudiant, autre matricule, mais même téléphone.
        $this->import($this->csv("nom_prenoms;civilite;matricule;telephone;cite;batiment;campagne;chambre;date_entree;numero_lit\n"
            . "Yao Jacques;Masculin;MAT041;0700000040;Cité Universitaire;Bâtiment A;2026-2027;101;01/10/2026;\n"))
            ->assertSessionHas('warning', fn (string $message) => str_contains($message,
                "Ligne 2 : Le téléphone 0700000040 est déjà utilisé par l'étudiant « Akesse Martin » (matricule MAT040)"));

        $this->assertNull(Etudiant::where('matricule', 'MAT041')->first());
    }

    public function test_missing_columns_are_listed_before_any_line_is_processed(): void
    {
        $this->import($this->csv("nom_prenoms;matricule\nKouassi;MAT030\n"))
            ->assertSessionHasErrors(['fichier' => 'Colonnes manquantes : civilite, telephone, cite, batiment, campagne, chambre, date_entree. Téléchargez le modèle et conservez ses en-têtes.']);
        $this->assertNull(Etudiant::where('matricule', 'MAT030')->first());
    }
}
