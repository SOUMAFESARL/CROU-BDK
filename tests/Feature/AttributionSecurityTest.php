<?php

namespace Tests\Feature;

use App\Http\Services\AllocationChambreService;
use App\Http\Services\AttributionRules;
use App\Models\AssignationChambre;
use App\Models\Campagne;
use App\Models\Chambre;
use App\Models\Etudiant;
use App\Repositories\AssignationChambreRepository;
use App\Repositories\Interfaces\ChambreRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class AttributionSecurityTest extends \Tests\Support\DossierTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['student','other'] as $student) {
            $dossier=$this->dossier($student);
            $this->workflow->admin($dossier->getKey(),'eligible');
            $this->confirm($student);
        }
    }
    protected function allocate(string $student = 'student', ?string $room = 'room'): ?AssignationChambre
    {
        if (in_array($this->workflow->current($student,'campaign')->etat_dossier,['annule','expire','refuse'],true)) {
            $this->travel(1)->seconds();
            $dossier=$this->dossier($student);
            $this->workflow->admin($dossier->getKey(),'eligible');
        }
        return parent::allocate($student,$room);
    }
    private function confirmAccepted(string $id): bool
    {
        $assignment=AssignationChambre::findOrFail($id);
        $dossier=\App\Models\DemandeEtudiant::where('assignation_id',$id)->first();
        if ($dossier && $dossier->etat_dossier==='pre_attribue') {
            $this->workflow->transition($dossier->getKey(),'accepte','etudiant',$assignment->etudiant_id,null,['assignation_id'=>$id]);
        }
        return $this->service->confirmerAssignation($id);
    }

    public function test_pending_reservation_consumes_the_last_place(): void
    {
        $this->allocate();
        $this->assertSame(0, Chambre::findOrFail('room')->getNombrePlacesDisponibles());
        $this->expectException(ValidationException::class);
        $this->allocate('other');
    }

    public function test_duplicate_pending_assignment_for_the_student_is_rejected(): void
    {
        $this->room('second');
        $this->allocate();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('deja une chambre');
        $this->allocate('student', 'second');
    }

    public function test_deactivated_room_building_or_residence_is_rejected(): void
    {
        foreach (['chambre', 'batiment', 'cite'] as $table) {
            DB::table($table)->update(['statut' => 'desactive']);
            try {
                $this->allocate();
                $this->fail('Une attribution ne doit pas passer dans '.$table);
            } catch (ValidationException $e) {
                $this->assertStringContainsString('désactivé', $e->getMessage());
            }
            DB::table($table)->update(['statut' => 'actif']);
        }
        $this->assertSame(0, AssignationChambre::count());
    }

    public function test_pmr_requires_explicit_confirmation(): void
    {
        DB::table('chambre')->update(['pmr' => true]);
        try {
            $this->allocate();
            $this->fail('PMR non confirmé');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('PMR', $e->getMessage());
        }
        DB::table('etudiants')->where('id_etudiant', 'student')->update(['pmr_confirme' => true]);
        $this->assertSame('confirmee', $this->allocate()->statut);
    }

    public function test_sex_restrictions_apply_at_each_level(): void
    {
        foreach (['chambre' => 'type_occupation_id', 'batiment' => 'type_occupation_id', 'cite' => 'type_cite_id'] as $table => $column) {
            DB::table($table)->update([$column => 'femmes']);
            try {
                $this->allocate();
                $this->fail('Restriction ignoree dans '.$table);
            } catch (ValidationException $e) {
                $this->assertStringContainsString('sexe', $e->getMessage());
            }
            DB::table($table)->update([$column => 'mixte']);
        }
        DB::table('cite')->update(['type_cite_id' => 'femmes']);
        DB::table('etudiants')->where('id_etudiant', 'student')->update(['genre_id' => 2]);
        $this->assertNotNull($this->allocate());
    }

    public function test_reference_labels_of_type_cite_are_recognized(): void
    {
        // Libellés réels du référentiel en production.
        DB::table('type_cite')->insert([
            ['id_type_cite' => 'uh', 'name' => 'Uniquement Homme'],
            ['id_type_cite' => 'uf', 'name' => 'Uniquement Femme'],
        ]);
        DB::table('chambre')->update(['type_occupation_id' => 'uf']);
        try {
            $this->allocate();
            $this->fail('Libellé « Uniquement Femme » ignoré');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('sexe', $e->getMessage());
        }

        DB::table('chambre')->update(['type_occupation_id' => 'uh']);
        $this->assertNotNull($this->allocate());
    }

    public function test_confirmation_does_not_count_its_own_reservation_twice(): void
    {
        $assignment = $this->reserver();
        Model::withoutEvents(fn () => $this->confirmAccepted($assignment->getKey()));
        $this->assertSame('confirmee', $assignment->fresh()->statut);
        $this->assertSame('occupee', Chambre::findOrFail('room')->etat);
        $this->assertSame(1, Chambre::findOrFail('room')->occupants_actuels);
        $this->assertTrue($this->confirmAccepted($assignment->getKey()));
    }

    public function test_confirmation_rechecks_rules(): void
    {
        $assignment = $this->reserver();
        DB::table('chambre')->update(['statut' => 'desactive']);
        $this->expectException(ValidationException::class);
        $this->confirmAccepted($assignment->getKey());
    }

    public function test_failed_reassignment_keeps_the_original_reservation(): void
    {
        $assignment = $this->reserver();
        $this->room('blocked', ['etat' => 'maintenance']);
        try {
            $this->service->reaffecterChambre(Etudiant::findOrFail('student'), Chambre::findOrFail('blocked'), Campagne::findOrFail('campaign'));
            $this->fail('La reaffectation doit echouer');
        } catch (ValidationException $e) {
            $this->assertSame('en_attente', $assignment->fresh()->statut);
            $this->assertNull($assignment->fresh()->date_fin);
            $this->assertSame(1, AssignationChambre::count());
        }
    }

    public function test_cancellation_releases_a_place_without_clearing_maintenance(): void
    {
        $assignment = $this->reserver();
        DB::table('chambre')->update(['etat' => 'maintenance']);
        $this->service->annulerAssignation($assignment->getKey());
        $room = Chambre::findOrFail('room');
        $this->assertSame(1, $room->getNombrePlacesDisponibles());
        $this->assertSame('maintenance', $room->etat);
    }

    public function test_expired_reservations_do_not_block_a_new_assignment(): void
    {
        $assignment = $this->reserver();
        DB::table('assignation_chambres')->update(['date_fin' => now()->subMinute()]);
        $this->assertNotNull($this->allocate('other'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('expirée');
        $this->confirmAccepted($assignment->getKey());
    }

    public function test_automatic_assignment_skips_an_incompatible_first_room(): void
    {
        DB::table('chambre')->update(['capacite_max' => 2, 'type_occupation_id' => 'femmes']);
        $this->room('second', ['capacite_max' => 3]);
        $this->assertSame('second', $this->allocate('student', null)->chambre_id);
    }

    public function test_room_outside_campaign_is_rejected(): void
    {
        DB::table('residence_campagne')->delete();
        $this->expectException(ValidationException::class);
        $this->allocate();
    }

    public function test_scope_keeps_the_selected_room_type_filter(): void
    {
        DB::table('chambre')->update(['type_chambre_id' => 'type-a']);
        $this->room('second', ['type_chambre_id' => 'type-b']);
        $this->assertSame(['room'], Chambre::parType('type-a')->disponibles('campaign')->pluck('id_chambre')->all());
    }

    public function test_reservations_in_another_campaign_still_use_capacity(): void
    {
        $this->allocate();
        DB::table('assignation_chambres')->update(['campagne_id' => 'another-campaign']);
        $this->expectException(ValidationException::class);
        $this->allocate('other');
    }

    public function test_multiple_room_accepts_only_its_remaining_capacity(): void
    {
        DB::table('chambre')->update(['capacite_max' => 2]);
        $first = $this->reserver();
        $second = $this->reserver('other');
        Model::withoutEvents(fn () => $this->confirmAccepted($first->getKey()));
        $this->assertSame(0, Chambre::findOrFail('room')->getNombrePlacesDisponibles());
        Model::withoutEvents(fn () => $this->confirmAccepted($second->getKey()));
        $this->assertSame('occupee', Chambre::findOrFail('room')->etat);
        $this->assertSame(2, Chambre::findOrFail('room')->occupants_actuels);
    }

    public function test_pmr_migration_defaults_existing_students_to_unconfirmed(): void
    {
        Schema::table('etudiants', fn (Blueprint $t) => $t->dropColumn(['pmr_confirme', 'pmr_confirme_by', 'pmr_confirme_at']));
        $migration = require database_path('migrations/2026_09_21_000001_add_pmr_confirmation_to_etudiants.php');
        $migration->up();
        $this->assertFalse(Etudiant::findOrFail('student')->pmr_confirme);
        $this->assertNull(Etudiant::findOrFail('student')->pmr_confirme_by);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('etudiants', 'pmr_confirme'));
        $this->assertSame(2, Etudiant::count());
    }

    public function test_agent_can_record_pmr_confirmation_with_actor_and_date(): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\EnforceSystemSecurity::class, \App\Http\Middleware\LogUserActivity::class]);
        $this->actingAs(new \App\Models\User(['id_user' => 'agent']), 'admin');
        DB::table((new \App\Models\DemandeEtudiant())->getTable())->insert([
            'id_demande' => 'request', 'etudiant_id' => 'student', 'campagne_id' => 'campaign',
        ]);
        $this->post(route('admin.allocation.pmr', 'campaign'), [
            'etudiant_id' => 'student', 'pmr_confirme' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $student = Etudiant::findOrFail('student');
        $this->assertTrue($student->pmr_confirme);
        $this->assertSame('agent', $student->pmr_confirme_by);
        $this->assertNotNull($student->pmr_confirme_at);
        $this->post(route('admin.allocation.pmr', 'other-campaign'), [
            'etudiant_id' => 'student', 'pmr_confirme' => 0,
        ])->assertNotFound();
        $this->assertTrue($student->fresh()->pmr_confirme);
    }

    public function test_pmr_confirmation_requires_an_authenticated_agent(): void
    {
        auth('admin')->logout();
        $this->withoutMiddleware([\App\Http\Middleware\EnforceSystemSecurity::class, \App\Http\Middleware\LogUserActivity::class]);
        $this->post(route('admin.allocation.pmr', 'campaign'), [
            'etudiant_id' => 'student', 'pmr_confirme' => 1,
        ])->assertRedirect('/');
        $this->assertFalse(Etudiant::findOrFail('student')->pmr_confirme);
    }

    public function test_unavailable_states_block_new_assignments_and_bed_lists(): void
    {
        foreach (['reservee', 'occupee', 'maintenance', 'hors_service', 'fermee'] as $etat) {
            DB::table('chambre')->update(['etat' => $etat]);
            $room = Chambre::findOrFail('room');
            $this->assertFalse($room->peutAccueillir(), $etat);
            $this->assertSame([], app(\App\Http\Services\LitService::class)->disponibles($room), $etat);
            $this->assertSame([], $this->service->getChambresDisponibles(Etudiant::findOrFail('student'), Campagne::findOrFail('campaign')), $etat);
            $this->assertNull($this->allocate('student', null), $etat);
            try {
                $this->allocate();
                $this->fail('Attribution autorisée pour '.$etat);
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('chambre_id', $e->errors());
            }
        }
        $this->assertSame(0, AssignationChambre::count());
    }

    public function test_departure_keeps_room_blocks_and_refreshes_occupancy(): void
    {
        foreach (['reservee', 'maintenance', 'hors_service', 'fermee'] as $etat) {
            DB::table('chambre')->update(['etat' => $etat, 'occupants_actuels' => 1]);
            $room = Chambre::findOrFail('room');
            $room->appliquerEtatApresDepart('disponible');
            $this->assertSame($etat, $room->fresh()->etat);
            $this->assertSame(0, $room->fresh()->occupants_actuels);
        }
        DB::table('chambre')->update(['etat' => 'occupee']);
        $room = Chambre::findOrFail('room');
        $room->appliquerEtatApresDepart('disponible');
        $this->assertSame('disponible', $room->fresh()->etat);
        $room->appliquerEtatApresDepart('maintenance');
        $this->assertSame('maintenance', $room->fresh()->etat);
        $room->appliquerEtatApresDepart('hors_service');
        $this->assertSame('hors_service', $room->fresh()->etat);
        $room->appliquerEtatApresDepart('maintenance');
        $this->assertSame('hors_service', $room->fresh()->etat);
    }

    public function test_cancellation_preserves_all_administrative_blocks(): void
    {
        foreach (['reservee', 'maintenance', 'hors_service', 'fermee'] as $etat) {
            DB::table('chambre')->update(['etat' => 'disponible']);
            $assignment = $this->reserver();
            DB::table('chambre')->update(['etat' => $etat]);
            $this->service->annulerAssignation($assignment->getKey());
            $this->assertSame($etat, Chambre::findOrFail('room')->etat);
            $this->assertSame(0, Chambre::findOrFail('room')->occupants_actuels);
        }
    }

    public function test_technical_blocks_prevent_confirmation_without_losing_reservation(): void
    {
        $assignment = $this->reserver();
        foreach (['maintenance', 'hors_service', 'fermee'] as $etat) {
            DB::table('chambre')->update(['etat' => $etat]);
            try {
                $this->confirmAccepted($assignment->getKey());
                $this->fail('Confirmation autorisée pour '.$etat);
            } catch (ValidationException $e) {
                $this->assertSame('en_attente', $assignment->fresh()->statut);
            }
        }
    }

    public function test_existing_reservation_can_be_confirmed_without_clearing_manual_room_reservation(): void
    {
        $assignment = $this->reserver();
        DB::table('chambre')->update(['etat' => 'reservee']);
        Model::withoutEvents(fn () => $this->confirmAccepted($assignment->getKey()));
        $this->assertSame('confirmee', $assignment->fresh()->statut);
        $this->assertSame('reservee', Chambre::findOrFail('room')->etat);
    }

    public function test_manual_state_changes_reject_inconsistent_occupancy_and_open_maintenance(): void
    {
        Schema::create('ordres_travail', function (Blueprint $t) {
            $t->string('id_ordres_travail')->primary();
            $t->string('chambre_id');
            $t->string('statut');
        });
        $service = app(\App\Http\Services\ChambreEtatService::class);
        try {
            $service->modifier('room', ['etat' => 'occupee']);
            $this->fail('Chambre vide marquée occupée');
        } catch (ValidationException $e) {
            $this->assertSame('disponible', Chambre::findOrFail('room')->etat);
        }
        $assignment = $this->reserver();
        $service->modifier('room', ['etat' => 'hors_service']);
        try {
            $service->modifier('room', ['etat' => 'disponible']);
            $this->fail('Chambre entièrement réservée remise à disposition');
        } catch (ValidationException $e) {
            $this->assertSame('hors_service', Chambre::findOrFail('room')->etat);
        }
        $this->service->annulerAssignation($assignment->getKey());
        DB::table('ordres_travail')->insert(['id_ordres_travail' => 'order', 'chambre_id' => 'room', 'statut' => 'en_cours']);
        try {
            $service->modifier('room', ['etat' => 'disponible']);
            $this->fail('Maintenance ouverte ignorée');
        } catch (ValidationException $e) {
            $this->assertSame('hors_service', Chambre::findOrFail('room')->etat);
        }
        DB::table('ordres_travail')->update(['statut' => 'termine']);
        $this->assertSame('disponible', $service->modifier('room', ['etat' => 'disponible'])->etat);
    }

    public function test_specific_bed_is_reserved_and_cannot_be_shared(): void
    {
        DB::table('chambre')->update(['capacite_max' => 3]);
        $assignment = $this->service->allocateChambre(Etudiant::findOrFail('student'),
            Campagne::findOrFail('campaign'), Chambre::findOrFail('room'), 2);
        $this->assertSame(2, $assignment->lit->numero);
        $this->assertSame('room', $assignment->lit->chambre_id);
        $this->expectException(ValidationException::class);
        $this->service->allocateChambre(Etudiant::findOrFail('other'),
            Campagne::findOrFail('campaign'), Chambre::findOrFail('room'), 2);
    }

    public function test_automatic_bed_choice_and_cancellation_reuse(): void
    {
        DB::table('chambre')->update(['capacite_max' => 2]);
        $first = $this->reserver();
        $second = $this->reserver('other');
        $this->assertSame(1, $first->lit->numero);
        $this->assertSame(2, $second->lit->numero);
        $this->service->annulerAssignation($first->getKey());
        $this->assertSame(1, $this->allocate()->lit->numero);
        $this->assertSame(1, $first->fresh()->lit->numero);
    }

    public function test_invalid_bed_number_rolls_back_the_assignment(): void
    {
        try {
            $this->service->allocateChambre(Etudiant::findOrFail('student'),
                Campagne::findOrFail('campaign'), Chambre::findOrFail('room'), 9);
            $this->fail('Lit hors capacite');
        } catch (ValidationException $e) {
            $this->assertSame(0, AssignationChambre::count());
            $this->assertSame(1, Chambre::findOrFail('room')->getNombrePlacesDisponibles());
        }
    }

    public function test_legacy_assignments_receive_beds_without_changing_their_status(): void
    {
        DB::table('chambre')->update(['capacite_max' => 3]);
        DB::table('assignation_chambres')->insert([
            'id_assignation' => 'legacy', 'etudiant_id' => 'student', 'chambre_id' => 'room',
            'campagne_id' => 'campaign', 'statut' => 'confirmee', 'date_assignation' => now()->subDay(),
        ]);
        $beds = app(\App\Http\Services\LitService::class);
        $this->assertSame([2, 3], $beds->disponibles(Chambre::findOrFail('room')));
        $beds->synchroniser(Chambre::findOrFail('room'));
        $legacy = AssignationChambre::findOrFail('legacy');
        $this->assertSame(1, $legacy->lit->numero);
        $this->assertSame('confirmee', $legacy->statut);
        $beds->synchroniser(Chambre::findOrFail('room'));
        $this->assertSame(3, \App\Models\Lit::count());
        $this->assertSame(2, $this->allocate('other')->lit->numero);
    }

    public function test_confirmation_keeps_the_chosen_bed(): void
    {
        DB::table('chambre')->update(['capacite_max' => 2]);
        $assignment = $this->reserver('student', 'room', 2);
        Model::withoutEvents(fn () => $this->confirmAccepted($assignment->getKey()));
        $this->assertSame(2, $assignment->fresh()->lit->numero);
        $this->assertSame('confirmee', $assignment->fresh()->statut);
    }

    public function test_capacity_cannot_remove_a_reserved_bed(): void
    {
        DB::table('chambre')->update(['capacite_max' => 3]);
        $this->service->allocateChambre(Etudiant::findOrFail('student'),
            Campagne::findOrFail('campaign'), Chambre::findOrFail('room'), 3);
        $this->expectException(ValidationException::class);
        Chambre::findOrFail('room')->update(['capacite_max' => 2]);
    }

    public function test_new_capacity_provisions_numbered_beds(): void
    {
        Chambre::findOrFail('room')->update(['capacite_max' => 4]);
        $this->assertSame([1, 2, 3, 4], Chambre::findOrFail('room')->lits->pluck('numero')->all());
    }

    public function test_overbooked_legacy_room_is_reported_without_partial_conversion(): void
    {
        foreach (['student', 'other'] as $id) {
            DB::table('assignation_chambres')->insert([
                'id_assignation' => $id, 'etudiant_id' => $id, 'chambre_id' => 'room',
                'statut' => 'en_attente', 'date_assignation' => now(),
            ]);
        }
        try {
            app(\App\Http\Services\LitService::class)->synchroniser(Chambre::findOrFail('room'));
            $this->fail('Surcapacite');
        } catch (ValidationException $e) {
            $this->assertSame(0, \App\Models\Lit::count());
            $this->assertSame(2, AssignationChambre::whereNull('lit_id')->count());
        }
    }

    public function test_disciplinary_expulsion_cancels_a_reservation_and_releases_its_bed(): void
    {
        $reservation = $this->reserver();
        $sanction = new \App\Models\Sanction(['id_sanction'=>'expulsion', 'numero_sanction'=>'SAN-TEST',
            'scope_type'=>'etudiant', 'etudiant_id'=>'student', 'type'=>'expulsion', 'statut'=>'active']);
        DB::transaction(fn () => app(\App\Http\Services\DisciplinaryImpactService::class)->synchroniser($sanction, 'agent'));
        $this->assertSame('annulee', $reservation->fresh()->statut);
        $this->assertSame(1, Chambre::findOrFail('room')->getNombrePlacesDisponibles());
        $other = $this->allocate('other');
        $this->assertSame($reservation->lit_id, $other->lit_id);
    }
}
