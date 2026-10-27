<?php
namespace Tests\Feature;
use App\Http\Services\NouvelleDemandeService;
use App\Models\{Campagne,Etudiant};
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Validation\ValidationException;

/** Nouvelle demande par le formulaire public (/register) d'un étudiant qui a déjà un compte mais n'a jamais été logé. */
class NouvelleDemandeTest extends \Tests\Support\DossierTestCase
{
    private const IDENTIFIANTS = ['email'=>'aya@exemple.com','telephone'=>'07 01 02 03 04','matricule'=>'MAT-01','num_piece'=>'CI 12345'];

    protected function setUp(): void
    {
        parent::setUp();
        Schema::table('etudiants',function(Blueprint $t){
            foreach(['email1','telephone1','numero_piece','matricule','password'] as $field) $t->string($field)->nullable();
        });
        Schema::table('campagne',function(Blueprint $t){
            $t->string('annee_campagne')->nullable();$t->date('date_echeance_demande')->nullable();
        });
        Schema::create('departure_requests',function(Blueprint $t){$t->id();$t->string('etudiant_id');$t->string('statut');});
        Schema::create('document_campagne',function(Blueprint $t){$t->string('id_document_campagne')->primary();$t->string('id_campagne');$t->string('document_id');$t->timestamps();});
        Schema::create('document',function(Blueprint $t){$t->string('id_document')->primary();$t->string('name');$t->string('slug');$t->string('format_accepte')->nullable();$t->boolean('champs_requis')->default(false);$t->timestamps();});
        DB::table('etudiants')->where('id_etudiant','student')->update([
            'statut_inscription'=>'annuler','email1'=>'aya@exemple.com','telephone1'=>'+225 07 01 02 03 04','numero_piece'=>'CI-12345','matricule'=>'MAT-01',
        ]);
        DB::table('campagne')->update(['annee_campagne'=>'2026-2027','date_echeance_demande'=>now()->addMonth()]);
    }
    private function service(): NouvelleDemandeService
    {
        return app(NouvelleDemandeService::class);
    }
    private function reconnu(array $changes=[]): ?Etudiant
    {
        $d=array_merge(self::IDENTIFIANTS,$changes);
        return $this->service()->compteExistant($d['email'],$d['matricule'],$d['telephone']);
    }
    private function peutDeposer(): void
    {
        $this->service()->ensureCanApply(Etudiant::findOrFail('student'),Campagne::findOrFail('campaign'));
    }
    public function test_existing_account_is_recognised_by_email_and_matricule(): void
    {
        $this->assertSame('student',$this->reconnu()?->getKey());
        // Téléphone et pièce ne servent pas à la reconnaissance.
        $this->assertSame('student',$this->reconnu(['email'=>'AYA@exemple.com ','matricule'=>'mat-01','telephone'=>'0500000000','num_piece'=>'AUTRE'])?->getKey());
        foreach([['email'=>'autre@exemple.com'],['matricule'=>'MAT-02'],['matricule'=>''],['email'=>'']] as $changes)
            $this->assertNull($this->reconnu($changes),json_encode($changes));
    }
    public function test_account_without_email_is_recognised_by_matricule_and_registered_phone(): void
    {
        DB::table('etudiants')->where('id_etudiant','student')->update(['email1'=>null]);
        $this->assertSame('student',$this->reconnu(['email'=>'nouvel@exemple.com','telephone'=>'0701020304'])?->getKey());
        // Mauvais téléphone, ou e-mail déjà utilisé par un autre compte : non reconnu.
        $this->assertNull($this->reconnu(['email'=>'nouvel@exemple.com','telephone'=>'0500000000']));
        DB::table('etudiants')->where('id_etudiant','other')->update(['email1'=>'pris@exemple.com']);
        $this->assertNull($this->reconnu(['email'=>'pris@exemple.com','telephone'=>'0701020304']));
    }
    public function test_one_request_per_student_and_per_campaign_even_after_a_refusal(): void
    {
        $this->peutDeposer();
        $dossier=$this->dossier();
        foreach([null,'refuse'] as $etat){
            if ($etat) $this->workflow->admin($dossier->getKey(),$etat,'Critères non remplis.');
            try {$this->peutDeposer();$this->fail('Deuxième demande sur la campagne');}
            catch(ValidationException $e){$this->assertStringContainsString('déjà déposé une demande pour cette campagne',$e->errors()['campagne_id'][0]);}
        }
        // Une autre campagne reste ouverte à l'étudiant.
        DB::table('campagne')->insert(['id_campagne'=>'campaign2','statut'=>'active','annee_campagne'=>'2027-2028','date_echeance_demande'=>now()->addMonth()]);
        $this->service()->ensureCanApply(Etudiant::findOrFail('student'),Campagne::findOrFail('campaign2'));
    }
    public function test_closed_or_inactive_campaign_is_rejected(): void
    {
        foreach([['statut'=>'terminer'],['statut'=>'active','date_echeance_demande'=>now()->subDay()]] as $changes){
            DB::table('campagne')->update($changes);
            try {$this->peutDeposer();$this->fail('Campagne fermée');}
            catch(ValidationException $e){$this->addToAssertionCount(1);}
        }
    }
    public function test_current_or_former_resident_must_use_readmission(): void
    {
        $this->assertFalse($this->service()->estDejaResident(Etudiant::findOrFail('student')));
        DB::table('departure_requests')->insert(['etudiant_id'=>'student','statut'=>'cloturee']);
        $this->assertTrue($this->service()->estDejaResident(Etudiant::findOrFail('student')));
        try {$this->peutDeposer();$this->fail('Résident');}
        catch(ValidationException $e){$this->assertSame(NouvelleDemandeService::MESSAGE_RESIDENT,$e->errors()['campagne_id'][0]);}
    }
    public function test_allocated_student_is_resident(): void
    {
        $this->eligibleRequest();$this->allocate();
        $this->assertTrue($this->service()->estDejaResident(Etudiant::findOrFail('student')));
    }
    public function test_existing_identity_check_is_skipped_only_for_the_recognised_account(): void
    {
        $erreurs=function(array $donnees){
            $request=\App\Http\Requests\DemandeHebergementRequest::create('/register','POST',$donnees);
            $validator=\Illuminate\Support\Facades\Validator::make([],[]);
            $request->withValidator($validator);
            $validator->passes();
            return $validator->errors()->keys();
        };
        $this->assertSame([],$erreurs(self::IDENTIFIANTS));
        $this->assertSame(['telephone'],$erreurs(array_merge(self::IDENTIFIANTS,['email'=>'autre@exemple.com'])));
    }
    public function test_public_form_skips_uniqueness_only_for_the_recognised_account(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $champs=['email','telephone','matricule','num_piece'];
        $this->from('/register')->post('/register',self::IDENTIFIANTS+['id_campagne'=>'campaign'])
            ->assertSessionDoesntHaveErrors($champs);
        $this->from('/register')->post('/register',array_merge(self::IDENTIFIANTS,['matricule'=>'MAT-02','id_campagne'=>'campaign']))
            ->assertSessionHasErrors(['email'=>'Un compte existe déjà avec cette information. Pour une nouvelle demande, saisissez l’e-mail et le matricule de votre compte (si votre compte n’a pas d’e-mail : votre matricule et le téléphone enregistré).']);
    }
}
