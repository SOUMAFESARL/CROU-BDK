<?php

namespace Tests\Feature;

use App\Models\{Etudiant, StudentApiToken, LegalDocument, EtatDesLieux};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class StudentDocumentsInspectionApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware(\App\Http\Middleware\LogUserActivity::class);
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary(); $t->integer('statut'); $t->softDeletes();
        });
        DB::table('etudiants')->insert(['id_etudiant' => 'student', 'statut' => 1]);
        (require database_path('migrations/2026_08_27_100000_create_student_api_tokens_table.php'))->up();
        StudentApiToken::create(['etudiant_id' => 'student', 'token_hash' => hash('sha256', 'documents-token')]);
        $this->withToken('documents-token');
        (require database_path('migrations/2026_09_23_000002_create_student_contract_approvals.php'))->up();
        Schema::create('etat_des_lieux', function (Blueprint $t) {
            $t->string('etat_des_lieux_id')->primary();
            foreach (['id_etudiant', 'id_chambre', 'id_batiment', 'id_cite', 'type_etat', 'created_by', 'updated_by'] as $field) $t->string($field)->nullable();
            $t->text('signature_crou')->nullable(); $t->text('signature_etudiant')->nullable();
            $t->text('observation_generale')->nullable(); $t->integer('nbre_cle_recus')->nullable();
            $t->dateTime('date_etat')->nullable(); $t->timestamps();
        });
        Schema::create('edl_piece', function (Blueprint $t) {
            $t->string('edl_piece_id')->primary(); $t->string('id_etat_des_lieux'); $t->string('name_piece');
            $t->integer('ordre'); $t->string('created_by')->nullable(); $t->timestamps();
        });
        Schema::create('edl_element', function (Blueprint $t) {
            $t->string('edl_element_id')->primary(); $t->string('id_edl_piece'); $t->string('name_element');
            $t->text('photo')->nullable(); $t->string('etat'); $t->string('observation')->nullable(); $t->integer('ordre'); $t->string('created_by')->nullable(); $t->timestamps();
        });
        Schema::create('edl_image', function (Blueprint $t) {
            $t->string('edl_image_id')->primary(); $t->string('id_edl_piece'); $t->string('chemin_fichier');
            $t->string('created_by')->nullable(); $t->timestamps();
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary(); $t->string('etudiant_id'); $t->string('chambre_id');
            $t->string('statut'); $t->dateTime('date_assignation'); $t->dateTime('date_fin')->nullable(); $t->timestamps();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary(); $t->string('batiment_id'); $t->string('cite_id'); $t->softDeletes();
        });
        DB::table('chambre')->insert(['id_chambre' => 'room', 'batiment_id' => 'building', 'cite_id' => 'site']);
        DB::table('assignation_chambres')->insert(['id_assignation' => 'assignment', 'etudiant_id' => 'student',
            'chambre_id' => 'room', 'statut' => 'confirmee', 'date_assignation' => now()]);
    }

    public function test_optional_element_photo_is_saved_preserved_and_removed(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $payload = ['pieces' => [['name_piece' => 'Chambre', 'elements' => [
            ['name_element' => 'Sol', 'etat' => 'bon', 'photo' => $this->signature()],
            ['name_element' => 'Porte', 'etat' => 'bon'],
        ]]]];
        $created = $this->postJson('/api/V1/etat-des-lieux/eld', $payload)->assertCreated();
        $id = $created->json('data.etat_des_lieux_id');
        $path = $created->json('data.pieces.0.elements.0.photo');
        $this->assertStringStartsWith('edl/photos/', $path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($path);
        $this->assertNotEmpty($created->json('data.pieces.0.elements.0.photo_url'));
        $created->assertJsonPath('data.pieces.0.elements.1.photo', null);
        $payload['pieces'][0]['elements'][0]['photo'] = $path;
        $this->putJson('/api/V1/etat-des-lieux/eld/'.$id, $payload)->assertOk()
            ->assertJsonPath('data.pieces.0.elements.0.photo', $path);
        $this->assertCount(1, \Illuminate\Support\Facades\Storage::disk('public')->allFiles());
        $payload['pieces'][0]['elements'][0]['photo'] = null;
        $this->putJson('/api/V1/etat-des-lieux/eld/'.$id, $payload)->assertOk()
            ->assertJsonPath('data.pieces.0.elements.0.photo', null);
    }

    public function test_inspection_display_returns_piece_and_element_photo_urls(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $created = $this->postJson('/api/V1/etat-des-lieux/eld', ['pieces' => [[
            'name_piece' => 'Chambre', 'photos' => [$this->signature()],
            'elements' => [['name_element' => 'Sol', 'etat' => 'bon', 'photo' => $this->signature()]],
        ]]])->assertCreated();
        $id = $created->json('data.etat_des_lieux_id');
        $elementPath = $created->json('data.pieces.0.elements.0.photo');
        $piecePath = $created->json('data.pieces.0.images.0.chemin_fichier');
        $this->getJson('/api/V1/etat-des-lieux/'.$id)->assertOk()
            ->assertJsonPath('data.pieces.0.elements.0.photo', $elementPath)
            ->assertJsonPath('data.pieces.0.elements.0.photo_url', rtrim(config('app.storage_public'), '/').'/'.$elementPath)
            ->assertJsonPath('data.pieces.0.images.0.url', rtrim(config('app.storage_public'), '/').'/'.$piecePath);
        $this->getJson('/api/V1/etat-des-lieux?type_etat=entree')->assertOk()
            ->assertJsonPath('data.data.0.pieces.0.elements.0.photo_url', rtrim(config('app.storage_public'), '/').'/'.$elementPath)
            ->assertJsonPath('data.data.0.pieces.0.images.0.url', rtrim(config('app.storage_public'), '/').'/'.$piecePath);
    }

    public function test_invalid_element_photo_is_rejected_without_creating_inspection(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->postJson('/api/V1/etat-des-lieux/eld', ['pieces' => [['name_piece' => 'Chambre', 'elements' => [
            ['name_element' => 'Sol', 'etat' => 'bon', 'photo' => 'edl/photos/foreign.jpg'],
        ]]]])->assertUnprocessable();
        $this->assertSame(0, EtatDesLieux::count());
        $this->assertSame([], \Illuminate\Support\Facades\Storage::disk('public')->allFiles());
    }

    public function test_exit_repository_stores_optional_element_photo(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $inventory = app(\App\Repositories\Interfaces\EtatDesLieuxRepositoryInterface::class)->create([
            'created_by' => 'student', 'id_etudiant' => 'student', 'id_chambre' => 'room',
            'id_batiment' => 'building', 'id_cite' => 'site', 'type_etat' => 'sortie',
            'pieces' => [['name_piece' => 'Chambre', 'elements' => [
                ['name_element' => 'Sol', 'etat' => 'bon', 'photo' => $this->signature()],
                ['name_element' => 'Porte', 'etat' => 'bon'],
            ]]],
        ]);
        $this->assertSame('sortie', $inventory->type_etat);
        $elements = $inventory->fresh('pieces.elements')->pieces[0]->elements;
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($elements[0]->photo);
        $this->assertNull($elements[1]->photo);
    }

    private function inspection(string $id, string $owner = 'student', string $type = 'sortie'): void
    {
        DB::table('etat_des_lieux')->insert(['etat_des_lieux_id' => $id, 'id_etudiant' => $owner,
            'id_chambre' => 'room', 'type_etat' => $type, 'date_etat' => now()]);
    }

    private function signature(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=';
    }

    public function test_inspection_list_detail_and_approval_are_scoped_to_bearer_owner(): void
    {
        $this->inspection('own');
        $this->inspection('foreign', 'other');
        $this->getJson('/api/V1/etat-des-lieux?etudiant_id=other')->assertOk()
            ->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.etat_des_lieux_id', 'own');
        $this->getJson('/api/V1/etat-des-lieux/foreign')->assertNotFound();
        $this->getJson('/api/V1/etudiant/etat-des-lieux-sortie?etudiant_id=other')
            ->assertOk()->assertJsonPath('data.etat_des_lieux_id', 'own');
        $payload = ['signature_etudiant' => $this->signature(), 'etudiant_id' => 'other'];
        $this->postJson('/api/V1/etudiant/etat-des-lieux-sortie/foreign/approuver', $payload)->assertNotFound();
        $this->patchJson('/api/V1/etudiant/etat-des-lieux-sortie/own/approuver', $payload)->assertUnprocessable();
        DB::table('etat_des_lieux')->where('etat_des_lieux_id', 'own')->update(['signature_crou' => 'crou.png']);
        $this->patchJson('/api/V1/etudiant/etat-des-lieux-sortie/own/approuver', ['signature_etudiant' => 'data:image/png;base64,bad'])
            ->assertUnprocessable();
        $this->patchJson('/api/V1/etudiant/etat-des-lieux-sortie/own/approuver', $payload)->assertOk();
        $this->postJson('/api/V1/etudiant/etat-des-lieux-sortie/own/approuver', $payload)->assertConflict();
        $this->assertSame('student', EtatDesLieux::find('own')->updated_by);
        $this->assertNull(EtatDesLieux::find('foreign')->signature_etudiant);
    }

    public function test_entry_can_be_signed_separately_before_crou_locking(): void
    {
        $this->inspection('entry', 'student', 'entree');
        $this->getJson('/api/V1/etat-des-lieux/entry')->assertOk()
            ->assertJsonPath('data.can_approve', true)->assertJsonPath('data.approval_status', 'pending_student');
        $this->postJson('/api/V1/etat-des-lieux/entry/approuver', ['signature_etudiant' => $this->signature()])
            ->assertOk()->assertJsonPath('data.can_approve', false)->assertJsonPath('data.approval_status', 'pending_crou');
        $this->patchJson('/api/V1/etat-des-lieux/entry/approuver', ['signature_etudiant' => $this->signature()])->assertConflict();
        $this->inspection('locked', 'student', 'entree');
        DB::table('etat_des_lieux')->where('etat_des_lieux_id', 'locked')->update(['signature_crou' => 'crou.png']);
        $this->postJson('/api/V1/etat-des-lieux/locked/approuver', ['signature_etudiant' => $this->signature()])->assertConflict();
        $this->postJson('/api/V1/etudiant/etat-des-lieux-sortie/entry/approuver', ['signature_etudiant' => $this->signature()])->assertNotFound();
    }

    public function test_exit_approval_exposes_actions_and_rejects_content_changes(): void
    {
        $this->inspection('exit');
        $this->getJson('/api/V1/etat-des-lieux/exit')->assertOk()
            ->assertJsonPath('data.can_edit', false)->assertJsonPath('data.can_approve', false)
            ->assertJsonPath('data.approval_status', 'pending_crou');
        DB::table('etat_des_lieux')->where('etat_des_lieux_id', 'exit')->update(['signature_crou' => 'crou.png']);
        $this->getJson('/api/V1/etat-des-lieux/exit')->assertOk()
            ->assertJsonPath('data.can_approve', true)->assertJsonPath('data.approval_status', 'pending_student');
        $this->postJson('/api/V1/etudiant/etat-des-lieux-sortie/exit/approuver', [
            'signature_etudiant' => $this->signature(), 'pieces' => [['name_piece' => 'Falsifiée']],
        ])->assertUnprocessable();
        $this->assertNull(EtatDesLieux::find('exit')->signature_etudiant);
        $this->postJson('/api/V1/etudiant/etat-des-lieux-sortie/exit/approuver', [
            'signature_etudiant' => $this->signature(),
        ])->assertOk()->assertJsonPath('data.can_approve', false)
            ->assertJsonPath('data.approval_status', 'approved');
    }

    public function test_entry_creation_and_update_use_assignment_and_lock_signed_inspection(): void
    {
        $payload = ['type_etat' => 'entree', 'id_etudiant' => 'other', 'id_chambre' => 'other-room',
            'pieces' => [['name_piece' => 'Chambre', 'elements' => [['name_element' => 'Sol', 'etat' => 'bon']]]]];
        $created = $this->postJson('/api/V1/etat-des-lieux/eld', $payload)->assertCreated()
            ->assertJsonPath('data.id_etudiant', 'student')->assertJsonPath('data.id_chambre', 'room');
        $id = $created->json('data.etat_des_lieux_id');
        $this->assertSame('student', EtatDesLieux::find($id)->created_by);
        $this->postJson('/api/V1/etat-des-lieux/eld', $payload)->assertConflict();
        $this->putJson('/api/V1/etat-des-lieux/eld/'.$id, ['observation_generale' => 'Mise à jour'])->assertOk();
        $this->assertSame(1, EtatDesLieux::find($id)->pieces()->count());
        $this->putJson('/api/V1/etat-des-lieux/eld/'.$id, ['signature_crou' => 'forged'])->assertUnprocessable();
        $this->putJson('/api/V1/etat-des-lieux/eld/'.$id, [
            'pieces' => [['name_piece' => 'Chambre', 'elements' => [['name_element' => 'Sol', 'etat' => 'mauvais']]]],
        ])->assertUnprocessable();
        DB::table('etat_des_lieux')->where('etat_des_lieux_id', $id)->update(['signature_crou' => 'signed']);
        $this->putJson('/api/V1/etat-des-lieux/eld/'.$id, ['nbre_cle_recus' => 99])->assertConflict();
        $this->putJson('/api/V1/etat-des-lieux/eld/foreign', [])->assertNotFound();
    }

    public function test_document_list_uses_only_published_snapshot_and_download_requires_auth(): void
    {
        (require database_path('migrations/2026_09_20_000001_create_legal_documents_tables.php'))->up();
        $published = LegalDocument::create(['type' => 'reglement_interieur', 'title' => 'Working title',
            'institution' => 'CROU', 'body' => 'body', 'signatory_roles' => [], 'status' => 'published']);
        $snapshot = ['draft' => false, 'title' => 'Published title', 'reference' => 'DOC-1', 'version_label' => '1.0',
            'institution' => 'CROU', 'effective_date' => null, 'academic_year' => null, 'scope_label' => null,
            'body' => 'Published body', 'schedule' => [], 'location' => null, 'contact' => null, 'signatures' => [], 'validated_at' => null];
        $version = $published->versions()->create(['revision' => 1, 'snapshot' => $snapshot]);
        $published->update(['published_version_id' => $version->id]);
        $draft = LegalDocument::create(['type' => 'reglement_interieur', 'title' => 'Secret draft',
            'institution' => 'CROU', 'body' => 'body', 'signatory_roles' => [], 'status' => 'draft']);
        $this->getJson('/api/V1/documents')->assertOk()->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', 'Published title')->assertDontSee('Secret draft')->assertDontSee('Working title');
        $this->getJson('/api/V1/documents/'.$draft->id.'/pdf')->assertNotFound();
        $pdf = $this->get('/api/V1/documents/'.$published->id.'/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $published->update(['published_version_id' => null]);
        $this->getJson('/api/V1/documents')->assertOk()->assertJsonCount(0, 'data.data');
    }

    public function test_personal_documents_and_downloads_cannot_access_another_student(): void
    {
        Schema::create('paiements', function (Blueprint $t) {
            $t->string('id_paiement')->primary(); $t->string('resident_id'); $t->string('statut_paiement');
            $t->string('numero_paiement'); $t->dateTime('date_paiement')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('document_etudiant', function (Blueprint $t) {
            $t->string('id_document')->primary(); $t->string('etudiant_id'); $t->timestamps();
        });
        Schema::create('departure_requests', function (Blueprint $t) {
            $t->string('id_demande_sortie')->primary(); $t->string('etudiant_id'); $t->timestamps();
        });
        Schema::create('departure_settlements', function (Blueprint $t) {
            $t->string('id_decompte')->primary(); $t->string('demande_sortie_id');
        });
        foreach (['own' => 'student', 'foreign' => 'other'] as $id => $owner) {
            DB::table('paiements')->insert(['id_paiement' => $id, 'resident_id' => $owner, 'statut_paiement' => 'valide', 'numero_paiement' => $id]);
            DB::table('document_etudiant')->insert(['id_document' => $id, 'etudiant_id' => $owner]);
            DB::table('departure_requests')->insert(['id_demande_sortie' => $id, 'etudiant_id' => $owner]);
        }
        $this->inspection('own');
        $this->inspection('foreign', 'other');
        foreach (['own' => 'student', 'foreign' => 'other'] as $suffix => $owner) {
            DB::table('student_contract_approvals')->insert(['id' => 'contract-'.$suffix,
                'assignment_id' => 'assignment-'.$suffix, 'student_id' => $owner, 'student_name' => $owner,
                'document_html' => '<html>Contrat</html>', 'document_hash' => hash('sha256', 'contract')]);
        }
        $documents = $this->getJson('/api/V1/mes-documents')->assertOk()->assertJsonCount(4, 'data')->assertDontSee('foreign');
        $contract = collect($documents->json('data'))->firstWhere('type', 'contrat');
        $this->assertSame('contract-own', $contract['id']);
        $this->assertFalse($contract['approved']);
        $this->assertStringContainsString('/contrats/contract-own/approuver', $contract['approval_url']);
        foreach (['recu', 'etat-des-lieux', 'justificatif', 'decompte'] as $type) {
            $this->getJson('/api/V1/mes-documents/'.$type.'/foreign')->assertNotFound();
        }
    }

    public function test_expired_and_disabled_student_tokens_are_rejected(): void
    {
        StudentApiToken::query()->update(['expires_at' => now()->subMinute()]);
        $this->getJson('/api/V1/etat-des-lieux')->assertUnauthorized();
        StudentApiToken::query()->update(['expires_at' => null]);
        DB::table('etudiants')->update(['statut' => 0]);
        $this->getJson('/api/V1/documents')->assertUnauthorized();
    }
}
