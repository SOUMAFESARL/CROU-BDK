<?php

namespace Tests\Feature;

use App\Models\DocumentEtudiant;
use App\Repositories\DocumentEtudiantRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Pièces jointes de la demande d'hébergement : copie sur disque puis une ligne document_etudiant par fichier. */
class PiecesDemandeHebergementTest extends TestCase
{
    private string $racine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->racine = sys_get_temp_dir().'/pieces-demande-'.uniqid();
        config(['student_documents.upload_root' => $this->racine]);

        Schema::create('document', function (Blueprint $t) {
            $t->string('id_document')->primary();
            $t->string('name');
            $t->string('slug');
            $t->timestamps();
        });
        Schema::create('document_etudiant', function (Blueprint $t) {
            $t->string('id_document')->primary();
            $t->string('id_campagne')->nullable();
            $t->string('etudiant_id');
            $t->string('chemin_fichier');
            $t->string('extension');
            $t->integer('taille_octets')->default(0);
            $t->string('type_document')->nullable();
            $t->string('statut')->nullable();
            $t->string('created_by')->nullable();
            $t->timestamps();
        });
        DB::table('document')->insert([
            ['id_document' => 'd1', 'name' => 'CNI', 'slug' => 'cni'],
            ['id_document' => 'd2', 'name' => 'Photo', 'slug' => 'photo'],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->racine);
        parent::tearDown();
    }

    private function pdf(string $nom = 'cni.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nom, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");
    }

    public function test_les_pieces_recues_sont_copiees_et_enregistrees(): void
    {
        $depot = app(DocumentEtudiantRepository::class);

        $pieces = $depot->stockerPieces(['cni' => $this->pdf(), 'photo' => UploadedFile::fake()->image('photo.png'), 'name' => 'Aya']);
        $depot->enregistrerPieces($pieces, 'e1', 'c1');

        $this->assertCount(2, $pieces);
        foreach ($pieces as $piece) {
            $this->assertFileExists($piece['fichier']);
        }
        $lignes = DocumentEtudiant::orderBy('type_document')->get();
        $this->assertSame(['CNI', 'Photo'], $lignes->pluck('type_document')->all());
        $this->assertSame(['pdf', 'png'], $lignes->pluck('extension')->all());
        $this->assertSame(['e1'], $lignes->pluck('etudiant_id')->unique()->values()->all());
        $this->assertSame(['c1'], $lignes->pluck('id_campagne')->unique()->values()->all());
        $this->assertGreaterThan(0, $lignes->first()->taille_octets);
        $this->assertStringEndsWith('/admin/assets/images/etudiant-document/'.basename($pieces[0]['fichier']), $lignes->first()->chemin_fichier);
    }

    public function test_un_fichier_au_contenu_refuse_annule_toutes_les_copies(): void
    {
        $depot = app(DocumentEtudiantRepository::class);
        // Vrai fichier envoyé : le type est lu dans le contenu, pas déduit du nom « .pdf ».
        $script = tempnam(sys_get_temp_dir(), 'piece');
        file_put_contents($script, '<?php echo 1;');
        $deguise = new UploadedFile($script, 'photo.pdf', null, null, true);
        // Le faux fichier Laravel garde un descripteur ouvert, ce qui empêcherait sa suppression sous Windows.
        $pdf = tempnam(sys_get_temp_dir(), 'piece');
        file_put_contents($pdf, $this->pdf()->get());

        try {
            $depot->stockerPieces(['cni' => new UploadedFile($pdf, 'cni.pdf', null, null, true), 'photo' => $deguise]);
            $this->fail('Le fichier déguisé aurait dû être refusé.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('photo', $exception->errors());
        }

        $this->assertSame([], File::exists(DocumentEtudiantRepository::dossierPieces()) ? File::files(DocumentEtudiantRepository::dossierPieces()) : []);
        $this->assertSame(0, DocumentEtudiant::count());
    }

    public function test_sans_fichier_rien_n_est_cree(): void
    {
        $depot = app(DocumentEtudiantRepository::class);

        $this->assertNull($depot->create(['id_etudiant' => 'e1', 'cni' => null]));
        $this->assertSame(0, DocumentEtudiant::count());
    }
}
