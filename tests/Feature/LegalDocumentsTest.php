<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use App\Models\ConfigApp;
use App\Models\Etudiant;
use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;
use App\Models\LegalSignatory;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegalDocumentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Storage::fake('local');
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);
        $settings = new ConfigApp(['app_name' => 'Résidence test']);
        $settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(ConfigAppRepositoryInterface::class)->shouldReceive('getConfig')->andReturn($settings);
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Role::create(['id' => 1, 'name' => 'Administrateur']);
        Role::create(['id' => 2, 'name' => 'Agent']);
        foreach ([
            '2026_08_11_000001_create_role_permissions_tables.php',
            '2026_08_11_000002_create_modules_table.php',
            '2026_09_20_000001_create_legal_documents_tables.php',
            '2026_09_20_000002_add_legal_documents_permissions.php',
            '2026_09_20_000003_add_legal_document_body_format.php',
            '2026_10_12_000001_add_uploaded_file_to_legal_documents.php',
        ] as $file) (require database_path('migrations/'.$file))->up();
        $this->admin();
    }

    private function admin(int $role = 1): void
    {
        $this->actingAs(new User(['id_user' => 'test-admin', 'name' => 'Admin Test', 'role_id' => $role]), 'admin');
    }

    private function route(string $action, mixed $parameters = []): string
    {
        return route('admin.parametrage.legal-documents.'.$action, $parameters);
    }

    public function test_stale_replacement_is_rejected_and_current_file_is_kept(): void
    {
        $this->post($this->route('store'), $this->uploadData())->assertSessionHasNoErrors();
        $document = LegalDocument::firstOrFail();
        $path = $document->file_path;
        $this->put($this->route('update', $document), $this->uploadData('nouveau.pdf') + ['lock_version' => 99])->assertSessionHasErrors('document');
        $this->assertSame($path, $document->fresh()->file_path);
        $this->assertCount(1, Storage::disk('local')->allFiles('legal-documents'));
    }

    public function test_unprivileged_agent_cannot_upload_or_read_documents(): void
    {
        $this->admin(2);
        $this->post($this->route('store'), $this->uploadData())->assertForbidden();
        $this->get($this->route('index'))->assertForbidden();
    }
    public function test_students_can_read_only_the_validated_uploaded_file(): void
    {
        $this->post($this->route('store'), $this->uploadData())->assertSessionHasNoErrors();
        $document = LegalDocument::firstOrFail();
        $this->actingAs(new Etudiant(['id_etudiant' => 'student']), 'etudiant');
        $this->get(route('etudiants.documents.pdf', $document))->assertNotFound();
        $this->admin();
        $this->post($this->route('publish', $document), ['lock_version' => $document->lock_version])->assertSessionHasNoErrors();
        $this->actingAs(new Etudiant(['id_etudiant' => 'student']), 'etudiant');
        $this->get(route('etudiants.documents.pdf', $document))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
    private function uploadData(string $name = 'reglement.pdf'): array
    {
        return ['type' => 'reglement_interieur', 'title' => 'Règlement', 'institution' => 'CROU',
            'version_label' => '1.0', 'file' => UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF")];
    }

    public function test_upload_publish_and_replacement_preserve_the_original_version(): void
    {
        $this->post($this->route('store'), $this->uploadData())->assertSessionHasNoErrors()->assertRedirect();
        $document = LegalDocument::firstOrFail();
        Storage::disk('local')->assertExists($document->file_path);
        $originalPath = $document->file_path;
        $this->assertSame([], $document->signatory_roles);
        $this->get($this->route('pdf', $document))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->post($this->route('publish', $document), ['lock_version' => $document->lock_version])->assertSessionHasNoErrors();
        $document->refresh();
        $version = $document->versions()->firstOrFail();
        $this->assertSame($originalPath, $version->snapshot['file_path']);
        $this->put($this->route('update', $document), $this->uploadData('nouveau.pdf') + ['lock_version' => $document->lock_version])->assertSessionHasNoErrors();
        $document->refresh();
        $this->assertNotSame($originalPath, $document->file_path);
        Storage::disk('local')->assertExists($originalPath);
        $this->get($this->route('versions.pdf', [$document, $version]))->assertOk();
    }

    public function test_file_is_required_and_non_pdf_is_rejected(): void
    {
        $data = $this->uploadData(); unset($data['file']);
        $this->post($this->route('store'), $data + ['body' => 'Texte rédigé'])->assertSessionHasErrors('file');
        $data['file'] = UploadedFile::fake()->createWithContent('document.pdf', 'ceci est du texte');
        $this->post($this->route('store'), $data)->assertSessionHasErrors('file');
        $this->assertSame(0, LegalDocument::count());
    }

    public function test_signature_module_is_removed_and_editor_is_replaced(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.parametrage.legal-signatories.index'));
        $page = $this->page($this->route('create', ['type' => 'guide_resident']));
        $this->assertSame('Admin/Parametrage/DocumentsLegaux/Edit', $page['component']);
        $this->assertNull($page['props']['document']['id']);
        $this->assertSame('Guide du résident', $page['props']['document']['type_label']);
        $this->assertArrayNotHasKey('body', $page['props']['document']);
        $this->assertSame($this->route('store'), $page['props']['urls']['submit']);
        $this->assertNull($page['props']['urls']['publish']);
        $this->get($this->route('create', ['type' => 'inconnu']))->assertNotFound();
    }

    public function test_index_page_lists_documents_with_counters_and_filters(): void
    {
        $this->post($this->route('store'), $this->uploadData())->assertSessionHasNoErrors();
        $this->post($this->route('store'), ['type' => 'guide_resident'] + $this->uploadData('guide.pdf'))->assertSessionHasNoErrors();
        $document = LegalDocument::where('type', 'reglement_interieur')->firstOrFail();
        $this->post($this->route('publish', $document), ['lock_version' => $document->lock_version])->assertSessionHasNoErrors();

        $page = $this->page($this->route('index'));
        $this->assertSame('Admin/Parametrage/DocumentsLegaux/Index', $page['component']);
        $this->assertSame(['total' => 2, 'valides' => 1, 'brouillons' => 1, 'types_sans_version' => 3], $page['props']['kpis']);
        $this->assertCount(4, $page['props']['types']);
        $this->assertSame(2, $page['props']['documents']['total']);
        $this->assertSame($this->route('edit', $document), collect($page['props']['documents']['data'])->firstWhere('id', (string) $document->id)['urls']['edit']);

        $filtre = $this->page($this->route('index', ['statut' => 'published']))['props'];
        $this->assertSame(1, $filtre['documents']['total']);
        $this->assertSame('published', $filtre['documents']['data'][0]['status']);
        $this->assertSame(1, $this->page($this->route('index', ['type' => 'guide_resident']))['props']['documents']['total']);
        // Valeurs inconnues ignorées.
        $ignore = $this->page($this->route('index', ['type' => 'x', 'statut' => 'y']))['props'];
        $this->assertSame(['type' => '', 'statut' => ''], $ignore['filters']);
        $this->assertSame(2, $ignore['documents']['total']);
    }

    public function test_edit_page_exposes_versions_and_publish_action(): void
    {
        $this->post($this->route('store'), $this->uploadData())->assertSessionHasNoErrors();
        $document = LegalDocument::firstOrFail();

        $brouillon = $this->page($this->route('edit', $document))['props'];
        $this->assertSame('reglement.pdf', $brouillon['document']['file_name']);
        $this->assertSame($this->route('publish', $document), $brouillon['urls']['publish']);
        $this->assertSame($this->route('update', $document), $brouillon['urls']['submit']);
        $this->assertArrayNotHasKey('file_path', $brouillon['document']);

        $this->post($this->route('publish', $document), ['lock_version' => $document->lock_version])->assertSessionHasNoErrors();
        $valide = $this->page($this->route('edit', $document))['props'];
        $this->assertSame('published', $valide['document']['status']);
        $this->assertNull($valide['urls']['publish']);
        $this->assertCount(1, $valide['versions']);
        $this->assertSame(1, $valide['versions'][0]['revision']);
    }

    public function test_document_can_be_removed_with_its_history_and_files(): void
    {
        $this->post($this->route('store'), $this->uploadData())->assertSessionHasNoErrors();
        $document = LegalDocument::firstOrFail();
        $this->post($this->route('publish', $document), ['lock_version' => $document->lock_version])->assertSessionHasNoErrors();
        $document->refresh();
        $this->put($this->route('update', $document), $this->uploadData('nouveau.pdf') + ['lock_version' => $document->lock_version])->assertSessionHasNoErrors();
        $this->assertCount(2, Storage::disk('local')->allFiles('legal-documents'));
        $this->assertSame($this->route('destroy', $document), $this->page($this->route('edit', $document))['props']['urls']['destroy']);

        $this->admin(2);
        $this->delete($this->route('destroy', $document))->assertForbidden();
        $this->admin();

        $this->delete($this->route('destroy', $document))->assertRedirect($this->route('index'));
        $this->assertSame(0, LegalDocument::count());
        $this->assertSame(0, DB::table('legal_document_versions')->count());
        $this->assertCount(0, Storage::disk('local')->allFiles('legal-documents'));
        $this->actingAs(new Etudiant(['id_etudiant' => 'student']), 'etudiant');
        $this->get(route('etudiants.documents.pdf', $document->id))->assertNotFound();
    }

    /** Page Inertia rendue (lue dans `data-page` : un middleware global reconstruit la réponse). */
    private function page(string $url): array
    {
        $this->withoutVite();
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? ''), true) ?? [];
    }
}