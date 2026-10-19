<?php
namespace Tests\Feature;

use App\Http\Services\AdmissionDerogationService;
use App\Models\{AdmissionDerogation, Etudiant, User};
use App\Repositories\Interfaces\EtudiantRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdmissionDerogationTest extends TestCase
{
    private AdmissionDerogationService $service;
    private array $evaluation = ['eligible' => false, 'regles' => [['label' => 'Distance', 'ok' => false]]];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_08_10_000001_create_sanctions_tables.php'))->up();
        (require database_path('migrations/2026_08_10_000002_add_scope_to_sanctions_table.php'))->up();
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        DB::table('roles')->insert([['id' => 1, 'name' => 'Administrateur'], ['id' => 2, 'name' => 'Sous-DAL'], ['id' => 3, 'name' => 'Chef du service logement']]);
        (require database_path('migrations/2026_08_11_000001_create_role_permissions_tables.php'))->up();
        (require database_path('migrations/2026_08_11_000002_create_modules_table.php'))->up();
        (require database_path('migrations/2026_09_23_000001_create_admission_derogations.php'))->up();
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary(); $t->string('statut_inscription'); $t->boolean('is_rejet')->default(0); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('demande', function (Blueprint $t) {
            $t->string('id_demande')->primary(); $t->string('etudiant_id'); $t->string('campagne_id'); $t->string('statut_demande'); $t->timestamps();
        });
        Schema::create('campagne', function (Blueprint $t) { $t->string('id_campagne')->primary(); $t->string('statut'); $t->softDeletes(); });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation'); $t->string('etudiant_id'); $t->string('statut'); $t->timestamp('date_fin')->nullable();
        });
        Schema::table('demande',function(Blueprint $t){$t->string('assignation_id')->nullable();$t->string('etat_dossier')->default('soumis');});
        Schema::create('dossier_transitions',function(Blueprint $t){
            $t->id();$t->string('demande_id');$t->string('ancien_etat')->nullable();$t->string('nouvel_etat');
            $t->string('acteur_type');$t->string('acteur_id')->nullable();$t->text('motif')->nullable();$t->json('details')->nullable();$t->timestamp('created_at');
        });
        Schema::create('niveau_etude',function(Blueprint $t){$t->string('id_niveau_etude')->primary();$t->softDeletes();});
        DB::table('etudiants')->insert(['id_etudiant' => 'student', 'statut_inscription' => 'demande']);
        DB::table('demande')->insert(['id_demande' => 'request', 'etudiant_id' => 'student', 'campagne_id' => 'campaign', 'statut_demande' => 'Refusée']);
        DB::table('campagne')->insert(['id_campagne' => 'campaign', 'statut' => 'active']);
        $repo = $this->mock(EtudiantRepositoryInterface::class);
        $repo->shouldReceive('findWithRelations')->andReturnUsing(fn () => Etudiant::findOrFail('student'));
        $repo->shouldReceive('verifierEligibilite')->andReturnUsing(fn () => $this->evaluation);
        $this->service = app(AdmissionDerogationService::class);
    }

    private function actor(string $id, int $role = 2): User
    {
        $actor = new User(['id_user' => $id, 'name' => $id, 'role_id' => $role]);
        $this->actingAs($actor,'admin');
        return $actor;
    }

    public function test_authorized_second_responsible_can_approve_with_audit_without_reserving_a_room(): void
    {
        $exception = $this->service->request($this->actor('requester', 1), 'student', 'request', 'Situation exceptionnelle');
        $this->assertSame('demande', Etudiant::findOrFail('student')->statut_inscription);
        Model::withoutEvents(fn () => $this->service->decide($this->actor('approver', 3), $exception->id, true, 'Décision motivée'));
        // Éligible : en attente de la confirmation de l'étudiant.
        $this->assertSame('en_attente', Etudiant::findOrFail('student')->statut_inscription);
        $this->assertSame('approved', $exception->fresh()->status);
        $this->assertSame('approver', $exception->fresh()->decided_by);
        $this->assertSame('Distance', $exception->fresh()->eligibility['regles'][0]['label']);
        $this->assertSame(0, DB::table('assignation_chambres')->count());
    }

    public function test_both_requested_roles_have_permission_but_generic_admin_does_not(): void
    {
        $this->assertTrue($this->service->canApprove($this->actor('sous', 2)));
        $this->assertTrue($this->service->canApprove($this->actor('chef', 3)));
        $this->assertFalse($this->service->canApprove($this->actor('admin', 1)));
        $this->assertFalse($this->service->canApprove(null));
        DB::table('role_permission')->insert(['role_id' => 1, 'permission_id' => DB::table('permissions')->where('slug', 'hebergement.admissions.derogations.validate')->value('id')]);
        $this->assertFalse($this->service->canApprove($this->actor('superadmin', 1)));
    }

    public function test_self_approval_is_rejected(): void
    {
        $actor = $this->actor('requester');
        $exception = $this->service->request($actor, 'student', 'request', 'Motif');
        $this->expectException(ValidationException::class);
        $this->service->decide($actor, $exception->id, true, 'Motif');
    }

    public function test_unprivileged_approval_is_forbidden(): void
    {
        $exception = $this->service->request($this->actor('requester'), 'student', 'request', 'Motif');
        try {
            $this->service->decide($this->actor('admin', 1), $exception->id, true, 'Motif');
            $this->fail('Approbation non autorisée');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('pending', $exception->fresh()->status);
        }
    }

    public function test_changed_criteria_require_a_new_request(): void
    {
        $exception = $this->service->request($this->actor('requester'), 'student', 'request', 'Motif');
        $this->evaluation['regles'][] = ['label' => 'Documents', 'ok' => false];
        $this->expectException(ValidationException::class);
        $this->service->decide($this->actor('approver'), $exception->id, true, 'Motif');
    }

    public function test_rejection_is_recorded_and_cannot_be_rewritten(): void
    {
        $exception = $this->service->request($this->actor('requester'), 'student', 'request', 'Motif');
        $this->service->decide($this->actor('approver'), $exception->id, false, 'Justification insuffisante');
        $this->assertSame('rejected', $exception->fresh()->status);
        $this->assertSame('demande', Etudiant::findOrFail('student')->statut_inscription);
        $this->expectException(ValidationException::class);
        $this->service->decide($this->actor('approver'), $exception->id, true, 'Autre motif');
    }

    public function test_empty_reason_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->request($this->actor('requester'), 'student', 'request', '   ');
    }

    public function test_duplicate_pending_request_is_rejected(): void
    {
        $this->service->request($this->actor('requester'), 'student', 'request', 'Motif');
        $this->expectException(ValidationException::class);
        $this->service->request($this->actor('another'), 'student', 'request', 'Autre motif');
    }

    public function test_existing_reservation_does_not_prevent_an_eligibility_derogation(): void
    {
        DB::table('assignation_chambres')->insert(['id_assignation' => 'reservation', 'etudiant_id' => 'student', 'statut' => 'reservee']);
        $exception = $this->service->request($this->actor('requester'), 'student', 'request', 'Place réservée');
        Model::withoutEvents(fn () => $this->service->decide($this->actor('approver', 3), $exception->id, true, 'Accord motivé'));
        $this->assertSame('approved', $exception->fresh()->status);
        $this->assertSame(1, DB::table('assignation_chambres')->count());
    }

    public function test_closed_campaign_cannot_receive_a_derogation(): void
    {
        DB::table('campagne')->update(['statut' => 'cloturee']);
        $this->expectException(ValidationException::class);
        $this->service->request($this->actor('requester'), 'student', 'request', 'Motif');
    }
}
