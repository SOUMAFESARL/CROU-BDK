<?php
namespace Tests\Feature;
use App\Http\Services\CampagneService;
use App\Models\{Campagne,DemandeEtudiant};
use Illuminate\Support\Facades\DB;
class ExpiredCampaignAutoRejectTest extends \Tests\Support\DossierTestCase
{
    public function test_it_uses_student_id_for_demande_relation(): void
    {
        $relation=(new DemandeEtudiant)->etudiant();
        $this->assertSame('etudiant_id',$relation->getForeignKeyName());
        $this->assertSame('id_etudiant',$relation->getOwnerKeyName());
    }
    public function test_expiration_is_traced_and_releases_the_proposed_bed(): void
    {
        // Proposition en attente de l'ancien parcours.
        $dossier=$this->eligibleRequest();
        $assignment=$this->reserver();
        DB::table('campagne')->update(['statut'=>'terminer']);
        $count=app(CampagneService::class)->rejeterDemandesCampagneExpiree(collect([Campagne::findOrFail('campaign')]));
        $this->assertSame(1,$count);
        $this->assertSame('expire',$dossier->fresh()->etat_dossier);
        $this->assertSame('annulee',$assignment->fresh()->statut);
        $this->assertSame('systeme',$dossier->transitions()->orderByDesc('id')->get()->last()->acteur_type);
        $this->assertSame(0,app(CampagneService::class)->rejeterDemandesCampagneExpiree(collect([Campagne::findOrFail('campaign')])));
    }
    public function test_active_campaign_and_definitive_attribution_are_preserved(): void
    {
        $dossier=$this->eligibleRequest();
        $this->assertSame(0,app(CampagneService::class)->rejeterDemandesCampagneExpiree(collect([Campagne::findOrFail('campaign')])));
        $this->allocate();
        DB::table('campagne')->update(['statut'=>'terminer']);
        $this->assertSame(0,app(CampagneService::class)->rejeterDemandesCampagneExpiree(collect([Campagne::findOrFail('campaign')])));
        $this->assertSame('attribue_definitif',$dossier->fresh()->etat_dossier);
    }
}
