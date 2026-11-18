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
        // Pièces de campagne (page et envoi web).
        Schema::create('document',function(Blueprint $t){
            $t->string('id_document')->primary();$t->string('name');$t->string('slug');$t->string('format_accepte')->nullable();
            $t->boolean('champs_requis')->default(false);$t->integer('ordre_affiche')->default(0);$t->timestamps();
        });
        Schema::create('document_campagne',function(Blueprint $t){$t->string('id_document_campagne')->primary();$t->string('id_campagne');$t->string('document_id');$t->timestamps();});
        Schema::create('document_etudiant',function(Blueprint $t){
            $t->string('id_document')->primary();$t->string('id_campagne')->nullable();$t->string('etudiant_id');$t->string('chemin_fichier');
            $t->string('extension');$t->integer('taille_octets')->default(0);$t->string('type_document')->nullable();
            $t->string('statut')->nullable();$t->string('created_by')->nullable();$t->timestamps();
        });
        Schema::create('filiere',function(Blueprint $t){$t->increments('id_filiere');$t->string('name');$t->string('code_filiere')->nullable();$t->timestamps();});
        DB::table('filiere')->insert([['id_filiere'=>1,'name'=>'Droit'],['id_filiere'=>2,'name'=>'Informatique']]);
        $this->racine=sys_get_temp_dir().'/pieces-readmission-'.uniqid();
        config(['student_documents.upload_root'=>$this->racine]);
    }
    private string $racine;
    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\File::deleteDirectory($this->racine);
        parent::tearDown();
    }
    private function documentsCampagne(): void
    {
        DB::table('document')->insert([
            ['id_document'=>'d-cert','name'=>'Certificat de scolarité','slug'=>'certificat','format_accepte'=>'pdf','champs_requis'=>true,'ordre_affiche'=>2],
            ['id_document'=>'d-photo','name'=>'Photo','slug'=>'photo','format_accepte'=>'jpg, png','champs_requis'=>false,'ordre_affiche'=>1],
        ]);
        DB::table('document_campagne')->insert([
            ['id_document_campagne'=>'dc1','id_campagne'=>'campaign','document_id'=>'d-cert'],
            ['id_document_campagne'=>'dc2','id_campagne'=>'campaign','document_id'=>'d-photo'],
        ]);
    }
    private function pdf(): \Illuminate\Http\UploadedFile
    {
        return \Illuminate\Http\UploadedFile::fake()->createWithContent('certificat.pdf',"%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");
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
            'campagne_id'=>'campaign','niveau'=>'L2','filiere'=>'1','moyenne'=>'12','credit_valider'=>'60','etudiant_id'=>'other',
        ])->assertCreated()->assertJsonPath('data.etudiant_id','student')->assertJsonPath('data.etat_dossier','soumis');
    }
    private function readmissionPage(): array
    {
        $this->withoutVite();
        $settings=new \App\Models\ConfigApp(['app_name'=>'Residence test']);
        $settings->setRelation('region',null)->setRelation('ville',null);
        $this->mock(\App\Repositories\Interfaces\ConfigAppRepositoryInterface::class)->shouldReceive('getConfig')->andReturn($settings);
        $this->withoutMiddleware([\App\Http\Middleware\EnforceSystemSecurity::class,\App\Http\Middleware\LogUserActivity::class]);
        $this->actingAs(Etudiant::findOrFail('student'),'etudiant');
        preg_match('/data-page="([^"]+)"/',$this->get(route('etudiants.readmission'))->assertOk()->getContent(),$matches);
        $page=json_decode(html_entity_decode($matches[1]??''),true);
        $this->assertSame('Etudiant/Readmission',$page['component']);
        return $page['props'];
    }
    public function test_readmission_page_lists_active_campaigns_and_levels(): void
    {
        $props=$this->readmissionPage();
        $this->assertSame([['id'=>'campaign','libelle'=>'2026-2027','dateLimite'=>now()->addMonth()->format('d/m/Y'),'documents'=>[]]],$props['campagnes']);
        $this->assertSame([['id'=>'L2','libelle'=>'Licence 2']],$props['niveaux']);
    }
    public function test_readmission_page_shows_campaign_label_with_year(): void
    {
        if (!Schema::hasColumn('campagne','libelle')) Schema::table('campagne',fn(Blueprint $t)=>$t->string('libelle')->nullable());
        DB::table('campagne')->update(['libelle'=>'Campagne principale']);
        $this->assertSame('Campagne principale – 2026-2027',$this->readmissionPage()['campagnes'][0]['libelle']);
    }
    public function test_readmission_page_orders_levels_and_hides_those_below_the_current_level(): void
    {
        DB::table('niveau_etude')->where('id_niveau_etude','L2')->update(['ordre'=>3]);
        DB::table('niveau_etude')->insert([
            ['id_niveau_etude'=>'DOC','name'=>'Doctorat','ordre'=>6],['id_niveau_etude'=>'TERM','name'=>'Terminale','ordre'=>1],
            ['id_niveau_etude'=>'L1','name'=>'Licence 1','ordre'=>2],['id_niveau_etude'=>'M1','name'=>'Master 1','ordre'=>5],
            ['id_niveau_etude'=>'L3','name'=>'Licence 3','ordre'=>4],
        ]);
        // Sans dossier antérieur : tous les niveaux de la Licence 1 au doctorat, sans la Terminale.
        $this->assertSame(['L1','L2','L3','M1','DOC'],array_column($this->readmissionPage()['niveaux'],'id'));
        // Dernier niveau déclaré : Licence 3 → seuls les niveaux supérieurs ou égaux restent.
        DB::table('demande')->insert(['id_demande'=>'old','etudiant_id'=>'student','campagne_id'=>'campaign','etat_dossier'=>'cloture','niveau_etude_actuel_code'=>'L3','created_at'=>now()->subYear(),'updated_at'=>now()->subYear()]);
        $this->assertSame(['L3','M1','DOC'],array_column($this->readmissionPage()['niveaux'],'id'));
    }
    public function test_readmission_page_lists_fields_and_preselects_the_previous_field_of_study(): void
    {
        $props=$this->readmissionPage();
        $this->assertSame([['id'=>'1','libelle'=>'Droit'],['id'=>'2','libelle'=>'Informatique']],$props['filieres']);
        $this->assertNull($props['filierePrecedente']);
        DB::table('demande')->insert([
            ['id_demande'=>'d-old','etudiant_id'=>'student','campagne_id'=>'campaign','etat_dossier'=>'cloture','filiere_code'=>'1','created_at'=>now()->subYears(2),'updated_at'=>now()->subYears(2)],
            ['id_demande'=>'d-last','etudiant_id'=>'student','campagne_id'=>'campaign','etat_dossier'=>'cloture','filiere_code'=>'2','created_at'=>now()->subYear(),'updated_at'=>now()->subYear()],
        ]);
        $this->assertSame('2',$this->readmissionPage()['filierePrecedente']);
    }
    public function test_readmission_page_lists_campaign_documents_as_configured(): void
    {
        $this->documentsCampagne();
        $this->assertSame([
            ['slug'=>'photo','nom'=>'Photo','requis'=>false,'formats'=>['jpg','png']],
            ['slug'=>'certificat','nom'=>'Certificat de scolarité','requis'=>true,'formats'=>['pdf']],
        ],$this->readmissionPage()['campagnes'][0]['documents']);
    }
    public function test_web_submission_stores_campaign_documents_with_the_dossier(): void
    {
        $this->documentsCampagne();
        $this->actingAs(Etudiant::findOrFail('student'),'etudiant');
        $this->withHeaders(['X-Inertia'=>'true'])->post(route('etudiants.readmission.store'),[
            'campagne_id'=>'campaign','niveau'=>'L2','filiere'=>'2','moyenne'=>'12','credit_valider'=>'60','boursier'=>0,'certificat'=>$this->pdf(),
        ])->assertRedirect(route('etudiants.dashboard',['section'=>'demandes']));
        $this->assertSame('soumis',DemandeEtudiant::firstOrFail()->etat_dossier);
        $this->assertSame('2',(string)DemandeEtudiant::firstOrFail()->filiere_code);
        $piece=\App\Models\DocumentEtudiant::firstOrFail();
        $this->assertSame(['student','campaign','Certificat de scolarité','pdf'],[$piece->etudiant_id,$piece->id_campagne,$piece->type_document,$piece->extension]);
        $this->assertFileExists($this->racine.'/admin/assets/images/etudiant-document/'.basename($piece->chemin_fichier));
    }
    public function test_web_submission_requires_mandatory_documents_and_an_allowed_level(): void
    {
        $this->documentsCampagne();
        DB::table('niveau_etude')->where('id_niveau_etude','L2')->update(['ordre'=>2]);
        DB::table('niveau_etude')->insert(['id_niveau_etude'=>'L3','name'=>'Licence 3','ordre'=>3]);
        DB::table('demande')->insert(['id_demande'=>'old','etudiant_id'=>'student','campagne_id'=>'campaign','etat_dossier'=>'cloture','niveau_etude_actuel_code'=>'L3','created_at'=>now()->subYear(),'updated_at'=>now()->subYear()]);
        $this->actingAs(Etudiant::findOrFail('student'),'etudiant');
        $this->from(route('etudiants.readmission'))->post(route('etudiants.readmission.store'),['campagne_id'=>'campaign','niveau'=>'L2'])
            ->assertSessionHasErrors(['certificat'=>'Le document Certificat de scolarité est obligatoire.','niveau','filiere'=>'Choisissez votre filière.','moyenne'=>'Saisissez votre moyenne.','credit_valider'=>'Saisissez le nombre de crédits validés.']);
        $this->assertSame(1,DemandeEtudiant::count());
        $this->assertSame(0,\App\Models\DocumentEtudiant::count());
    }
    public function test_react_form_submission_redirects_to_the_requests_section(): void
    {
        $this->actingAs(Etudiant::findOrFail('student'),'etudiant');
        $this->withHeaders(['X-Inertia'=>'true'])->post(route('etudiants.readmission.store'),[
            'campagne_id'=>'campaign','niveau'=>'L2','filiere_autre'=>'Génie civil','moyenne'=>'12.5','credit_valider'=>'60','boursier'=>0,'cas_social'=>'',
        ])->assertRedirect(route('etudiants.dashboard',['section'=>'demandes']));
        $this->assertSame('soumis',DemandeEtudiant::firstOrFail()->etat_dossier);
        // Filière saisie librement : ajoutée au référentiel puis enregistrée sur le dossier.
        $this->assertSame((string)DB::table('filiere')->where('name','Génie civil')->value('id_filiere'),(string)DemandeEtudiant::firstOrFail()->filiere_code);
        $this->assertSame('Votre demande de réadmission a été soumise avec votre compte existant.',session('success'));
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
