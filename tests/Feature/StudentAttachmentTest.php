<?php
namespace Tests\Feature;

use App\Http\Controllers\etudiant\DocumentController;
use App\Http\Services\StudentAttachmentLocation;
use App\Models\Etudiant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class StudentAttachmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite', 'database.connections.sqlite.database'=>':memory:',
            'student_documents.legacy_origin'=>'https://croudedaloa.soumafe.ci']);
        DB::purge('sqlite');
        Schema::create('document_etudiant', function (Blueprint $t) {
            $t->string('id_document')->primary(); $t->string('etudiant_id'); $t->string('chemin_fichier');
        });
        auth('etudiant')->setUser(new Etudiant(['id_etudiant'=>'student']));
    }
    public function test_recorded_production_url_opens_when_file_is_not_local(): void
    {
        $url='https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/attachment-test-remote.pdf';
        DB::table('document_etudiant')->insert(['id_document'=>'doc','etudiant_id'=>'student','chemin_fichier'=>$url]);
        $response=app(DocumentController::class)->attachment('doc');
        $this->assertSame(302,$response->getStatusCode());
        $this->assertSame($url,$response->getTargetUrl());
    }
    public function test_another_students_document_is_not_accessible(): void
    {
        DB::table('document_etudiant')->insert(['id_document'=>'doc','etudiant_id'=>'other','chemin_fichier'=>'test.pdf']);
        $this->expectException(ModelNotFoundException::class);
        app(DocumentController::class)->attachment('doc');
    }
    public function test_remote_urls_are_limited_to_the_document_origin_and_directory(): void
    {
        $resolver=app(StudentAttachmentLocation::class);
        foreach (['https://evil.example/admin/assets/images/etudiant-document/test.pdf',
            'https://croudedaloa.soumafe.ci/other/test.pdf',
            'https://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/%2e%2e%2fsecret',
            'http://croudedaloa.soumafe.ci/admin/assets/images/etudiant-document/test.pdf'] as $url) {
            $this->assertNull($resolver->remoteUrl($url));
        }
    }
    public function test_a_local_pdf_opens_inline(): void
    {
        $name='attachment-test-'.bin2hex(random_bytes(8)).'.pdf';
        $file=storage_path('app/public/'.$name);
        file_put_contents($file,"%PDF-1.4\n%%EOF\n");
        try {
            DB::table('document_etudiant')->insert(['id_document'=>'doc','etudiant_id'=>'student','chemin_fichier'=>'storage/'.$name]);
            $response=app(DocumentController::class)->attachment('doc');
            $this->assertSame(realpath($file),$response->getFile()->getRealPath());
            $this->assertStringNotContainsString('attachment',(string)$response->headers->get('Content-Disposition'));
        } finally { unlink($file); }
    }
}
