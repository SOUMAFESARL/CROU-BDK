<?php
namespace Tests\Feature;
use App\Http\Services\{ReadmissionService,StudentIdentityResolver};
use App\Models\{Etudiant,DemandeEtudiant};
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Validation\ValidationException;

class ReadmissionTest extends \Tests\Support\DossierTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::table('etudiants',function(Blueprint $t){
            $t->string('telephone1')->nullable();$t->string('numero_piece')->nullable();$t->string('password')->nullable();
        });
        Schema::table('campagne',function(Blueprint $t){
            $t->string('annee_campagne')->nullable();$t->boolean('readmission')->nullable();
            $t->date('date_echeance_demande')->nullable();
        });
        Schema::table('demande',function(Blueprint $t){
            foreach(['niveau_etude_precedent_code','annee_academique_precedent','filiere_code','type_hebergement_souhaite','periode','moyenne','credit_valider'] as $field) $t->string($field)->nullable();
            $t->boolean('boursier')->default(false);
        });
        Schema::create('departure_requests',function(Blueprint $t){$t->id();$t->string('etudiant_id');$t->string('statut');});
        DB::table('departure_requests')->insert(['etudiant_id'=>'student','statut'=>'cloturee']);
        DB::table('etudiants')->where('id_etudiant','student')->update([
            'statut_inscription'=>'archivee','telephone1'=>'+225 07 01 02 03 04','numero_piece'=>'CI-12345','password'=>'unchanged',
        ]);
        DB::table('campagne')->update(['annee_campagne'=>'2026-2027','readmission'=>true,'date_echeance_demande'=>now()->addMonth()]);
        DB::table('niveau_etude')->insert(['id_niveau_etude'=>'L2','name'=>'Licence 2']);
    }
    private function submit()
    {
        return app(ReadmissionService::class)->submit(Etudiant::findOrFail('student'),['campagne_id'=>'campaign','niveau'=>'L2','moyenne'=>14,'credit_valider'=>60]);
    }
    public function test_readmission_reuses_account_and_creates_a_submitted_dossier(): void
    {
        $dossier=$this->submit();
        $this->assertSame('student',$dossier->etudiant_id);
        $this->assertSame('soumis',$dossier->etat_dossier);
        $this->assertSame(2,Etudiant::count());
        $this->assertSame('unchanged',Etudiant::findOrFail('student')->password);
        $this->assertSame('demande',Etudiant::findOrFail('student')->statut_inscription);
        $this->assertSame('2026-2027',$dossier->annee_academique_actuel);
    }
    public function test_duplicate_submission_is_rejected(): void
    {
        $this->submit();
        try {$this->submit();$this->fail('Double dossier');}
        catch(ValidationException $e){$this->assertSame(1,DemandeEtudiant::count());}
    }
    public function test_inactive_campaign_closed_deadline_or_disabled_readmission_blocks_submission(): void
    {
        foreach([
            ['statut'=>'terminer'],
            ['statut'=>'active','readmission'=>false],
            ['readmission'=>true,'date_echeance_demande'=>now()->subDay()],
        ] as $changes){
            DB::table('campagne')->update($changes);
            try {$this->submit();$this->fail('Campagne non autorisée');}
            catch(ValidationException $e){$this->assertSame(0,DemandeEtudiant::count());}
        }
    }
    public function test_pending_stay_blocks_readmission(): void
    {
        $this->eligibleRequest();$this->allocate();
        $this->expectException(ValidationException::class);
        $this->submit();
    }
    public function test_identity_matches_phone_formats_or_cni(): void
    {
        $resolver=app(StudentIdentityResolver::class);
        foreach(['0701020304','002250701020304','+2250701020304'] as $phone)
            $this->assertSame('student',$resolver->find($phone,null)->getKey());
        $this->assertSame('student',$resolver->find(null,'ci 12345')->getKey());
        $this->assertNull($resolver->find('0100000000','unknown'));
    }
    public function test_conflicting_identifiers_do_not_merge_two_people(): void
    {
        DB::table('etudiants')->where('id_etudiant','other')->update(['numero_piece'=>'OTHER']);
        $this->expectException(ValidationException::class);
        app(StudentIdentityResolver::class)->find('0701020304','OTHER');
    }
    public function test_administrator_reuses_identity_without_overwriting_account(): void
    {
        $existing=app(StudentIdentityResolver::class)->existingForAdmin(['telephone1'=>'0701020304','name'=>'Autre saisie','password'=>'new']);
        $this->assertSame('student',$existing->getKey());
        $this->assertSame('unchanged',$existing->password);
        $this->assertSame(2,Etudiant::count());
    }
    public function test_public_creation_cannot_reuse_or_duplicate_an_existing_identity(): void
    {
        $this->expectException(ValidationException::class);
        (new \App\Repositories\EtudiantRepository)->create(['telephone1'=>'0701020304','numero_piece'=>'CI12345']);
    }
    public function test_web_submission_uses_authenticated_student_not_supplied_identifier(): void
    {
        $this->actingAs(Etudiant::findOrFail('student'),'etudiant');
        $this->postJson(route('etudiants.readmission.store'),[
            'campagne_id'=>'campaign','niveau'=>'L2','etudiant_id'=>'other',
        ])->assertCreated()->assertJsonPath('data.etudiant_id','student')->assertJsonPath('data.etat_dossier','soumis');
    }
    public function test_admin_import_associates_existing_student_without_creating_an_account(): void
    {
        $controller=app(\App\Http\Controllers\admin\Etudiant\etudiantController::class);
        $method=new \ReflectionMethod($controller,'registerImportedAdmission');
        $method->invoke($controller,[
            'telephone1'=>'0701020304','numero_piece'=>'CI12345',
            'campagne_id'=>'campaign','chambre_id'=>'room','cite_id'=>'cite','batiment_id'=>'building',
            'date_entree'=>now()->toDateString(),'niveau_etude_actuel_code'=>'L2',
        ]);
        $this->assertSame(2,Etudiant::count());
        $this->assertSame('student',DemandeEtudiant::firstOrFail()->etudiant_id);
        $this->assertSame('pre_attribue',DemandeEtudiant::firstOrFail()->etat_dossier);
        $this->assertSame('unchanged',Etudiant::findOrFail('student')->password);
        $this->assertSame('+225 07 01 02 03 04',Etudiant::findOrFail('student')->telephone1);
    }
    public function test_api_requires_token_and_reuses_authenticated_account(): void
    {
        (require database_path('migrations/2026_08_27_100000_create_student_api_tokens_table.php'))->up();
        DB::table('etudiants')->where('id_etudiant','student')->update(['statut'=>1]);
        \App\Models\StudentApiToken::create(['etudiant_id'=>'student','token_hash'=>hash('sha256','readmission-token'),'expires_at'=>now()->addHour()]);
        $this->postJson('/api/V1/readmission',['campagne_id'=>'campaign','niveau'=>'L2'])->assertUnauthorized();
        $this->withToken('readmission-token')->postJson('/api/V1/readmission',[
            'campagne_id'=>'campaign','niveau'=>'L2','etudiant_id'=>'other',
        ])->assertCreated()->assertJsonPath('data.etudiant_id','student');
        $this->assertSame(2,Etudiant::count());
    }
}
