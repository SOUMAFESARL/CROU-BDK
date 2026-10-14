<?php

namespace Tests\Feature;

use App\Models\Etudiant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentPersonalDocumentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $settings = new \App\Models\ConfigApp(['app_name' => 'Residence test']);
        $settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(\App\Repositories\Interfaces\ConfigAppRepositoryInterface::class)
            ->shouldReceive('getConfig')->andReturn($settings);
        $this->withoutMiddleware([\App\Http\Middleware\EnforceSystemSecurity::class, \App\Http\Middleware\LogUserActivity::class]);
        foreach (['paiements' => ['id_paiement', 'resident_id'], 'etat_des_lieux' => ['etat_des_lieux_id', 'id_etudiant'],
            'document_etudiant' => ['id_document', 'etudiant_id'], 'departure_requests' => ['id_demande_sortie', 'etudiant_id']] as $table => [$key, $owner]) {
            Schema::create($table, function (Blueprint $t) use ($key, $owner) {
                $t->string($key)->primary(); $t->string($owner);
                $t->string('statut_paiement')->nullable(); $t->softDeletes();
                foreach (['numero_paiement', 'type_paiement', 'devise', 'methode_paiement', 'reference_transaction'] as $field) $t->string($field)->nullable();
                $t->decimal('montant')->default(1000); $t->timestamp('date_paiement')->nullable();
                $t->timestamp('date_etat')->nullable(); $t->timestamp('date_demande')->nullable(); $t->timestamps();
            });
            DB::table($table)->insert([$key => 'foreign', $owner => 'other', 'statut_paiement' => 'valide']);
        }
        Schema::create('departure_settlements', function (Blueprint $t) {
            $t->string('id_decompte')->primary(); $t->string('demande_sortie_id');
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary(); $t->string('etudiant_id'); $t->string('statut');
        });
        $this->actingAs(new Etudiant(['id_etudiant' => 'student', 'name' => 'Étudiant test', 'matricule' => 'TEST']), 'etudiant');
    }

    public function test_other_students_documents_are_inaccessible(): void
    {
        foreach (['receipt', 'inventory', 'attachment', 'settlement'] as $action) {
            $this->get(route('etudiants.documents.'.$action, 'foreign'))->assertNotFound();
        }
        $this->get(route('etudiants.pdfContrat', ['id' => 'other']))->assertNotFound();
    }

    public function test_personal_document_page_lists_categories_without_foreign_records(): void
    {
        $this->get(route('etudiants.documents.personal'))->assertOk()
            ->assertSee('Mes documents')->assertSee('Aucun contrat disponible.')
            ->assertSee('Aucun état des lieux enregistré.')->assertDontSee('foreign');
    }

    public function test_student_shell_offers_logout_button(): void
    {
        $this->get(route('etudiants.documents.personal'))->assertOk()
            ->assertSee('action="'.route('etudiants.logout').'"', false)
            ->assertSee('Déconnexion');
    }

    public function test_student_can_log_out(): void
    {
        $this->post(route('etudiants.logout'))->assertRedirect(route('etudiant.login'));
        $this->assertGuest('etudiant');
        $this->get(route('etudiants.documents.personal'))->assertRedirect(route('etudiant.login'));
    }

    public function test_only_validated_payments_and_refunds_have_receipts(): void
    {
        DB::table('paiements')->where('id_paiement', 'foreign')->update(['resident_id' => 'student']);
        foreach (['en_attente', 'echoue', 'annule'] as $status) {
            DB::table('paiements')->update(['statut_paiement' => $status]);
            $this->get(route('etudiants.documents.receipt', 'foreign'))->assertNotFound();
        }
        foreach (['valide', 'rembourse'] as $status) {
            DB::table('paiements')->update(['statut_paiement' => $status, 'type_paiement' => 'caution', 'methode_paiement' => 'especes', 'devise' => 'XOF']);
            $response = $this->get(route('etudiants.documents.receipt', 'foreign'))->assertOk();
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }
    }
}
