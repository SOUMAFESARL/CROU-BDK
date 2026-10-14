<?php
namespace App\Http\Services;

use App\Models\{AssignationChambre, DepartureRequest, DisciplinaryReport, Etudiant, Sanction, SanctionHistory};
use Illuminate\Validation\ValidationException;

class DisciplinaryImpactService
{
    public const LABELS = [
        'aucune' => 'Aucune sanction', 'avertissement' => 'Avertissement',
        'blame' => 'Blâme', 'suspension' => 'Suspension', 'expulsion' => 'Expulsion',
    ];

    public static function decisionRules(): array
    {
        return [
            'sanction' => 'nullable|required_if:action,decider|in:aucune,avertissement,blame,suspension,expulsion',
            'etudiant_sanctionne_id' => 'nullable|required_if:sanction,avertissement,blame,suspension,expulsion|string|exists:etudiants,id_etudiant',
            'sanction_fin' => 'nullable|required_if:sanction,suspension|date|after_or_equal:today',
        ];
    }

    public function statut(?string $studentId): string
    {
        if (!$studentId) return 'aucune';
        $types = Sanction::where('scope_type', 'etudiant')->where('etudiant_id', $studentId)
            ->where('statut', 'active')
            ->where(fn ($q) => $q->whereNull('date_debut')->orWhereDate('date_debut', '<=', today()))
            ->where(fn ($q) => $q->whereNull('date_fin')->orWhereDate('date_fin', '>=', today()))
            ->pluck('type')->all();
        foreach (['expulsion', 'suspension', 'blame', 'avertissement'] as $type) {
            if (in_array($type, $types, true)) return $type;
        }
        return 'aucune';
    }

    public function verifier(Etudiant $student): void
    {
        $status = $this->statut($student->getKey());
        if (in_array($status, ['suspension', 'expulsion'], true)) {
            throw ValidationException::withMessages([
                'etudiant_id' => 'Admission ou attribution bloquée : '.self::LABELS[$status].' disciplinaire active.',
            ]);
        }
    }

    // Appelé dans la transaction de décision : rapport, sanction et effets sont atomiques.
    public function decider(DisciplinaryReport $report, array $data, ?string $author): void
    {
        $type = $data['sanction'];
        $report->update(['sanction_decidee' => $type]);
        if ($type === 'aucune') return;
        // Ne jamais déduire le destinataire du déclarant d'un signalement.
        $student = Etudiant::whereKey($data['etudiant_sanctionne_id'])->lockForUpdate()->firstOrFail();
        $assignment = AssignationChambre::where('etudiant_id', $student->getKey())->actives()
            ->with('chambre')->latest('date_assignation')->first();
        $sanction = Sanction::create([
            'rapport_id' => $report->getKey(), 'scope_type' => 'etudiant',
            'etudiant_id' => $student->getKey(), 'type' => $type, 'statut' => 'active',
            'cite_id' => $assignment?->chambre?->cite_id,
            'batiment_id' => $assignment?->chambre?->batiment_id,
            'chambre_id' => $assignment?->chambre_id,
            'motif' => $data['decision'], 'faits_reproches' => $report->description,
            'date_decision' => today(), 'date_debut' => today(),
            'date_fin' => $type === 'suspension' ? $data['sanction_fin'] : null,
            'commentaire' => $data['commentaire'] ?? null, 'created_by' => $author,
        ]);
        SanctionHistory::create([
            'sanction_id' => $sanction->getKey(), 'action' => 'decision_incident',
            'nouveau_statut' => 'active', 'auteur_id' => $author,
            'details' => 'Décision du rapport '.$report->numero_rapport.' : '.self::LABELS[$type].'.',
        ]);
        $this->synchroniser($sanction, $author);
    }

    public function synchroniser(Sanction $sanction, ?string $author): void
    {
        if ($sanction->scope_type !== 'etudiant' || !$sanction->etudiant_id) return;
        Etudiant::whereKey($sanction->etudiant_id)->lockForUpdate()->firstOrFail();
        if ($sanction->type !== 'expulsion') return;
        if ($sanction->statut !== 'active') {
            $other = Sanction::where('etudiant_id', $sanction->etudiant_id)->where('scope_type', 'etudiant')
                ->where('type', 'expulsion')->where('statut', 'active')->whereKeyNot($sanction->getKey())->first();
            if ($other) { $this->synchroniser($other, $author); return; }
            // Une sortie déjà clôturée ne doit jamais être rouverte.
            DepartureRequest::where('sanction_id', $sanction->getKey())->where('statut', 'en_attente')
                ->update(['statut' => 'annulee', 'decision' => 'annulee',
                    'motif_decision' => 'Sanction levée ou annulée.', 'date_decision' => now(), 'traite_par' => $author]);
            return;
        }
        $assignments = AssignationChambre::where('etudiant_id', $sanction->etudiant_id)
            ->mobilisantPlace()->lockForUpdate()->get();
        foreach ($assignments as $assignment) {
            if ($assignment->statut === 'en_attente') {
                app(AllocationChambreService::class)->annulerAssignation(
                    $assignment->getKey(), 'Expulsion disciplinaire : '.$sanction->numero_sanction);
                continue;
            }
            // Conserver l'occupation jusqu'au constat de sortie et à la remise des clés.
            $departure = DepartureRequest::where('assignation_id', $assignment->getKey())
                ->where('statut', 'en_attente')->lockForUpdate()->first();
            if (!$departure) {
                $departure = new DepartureRequest([
                    'etudiant_id' => $sanction->etudiant_id, 'assignation_id' => $assignment->getKey(),
                    'chambre_id' => $assignment->chambre_id, 'date_demande' => today(),
                    'date_sortie_prevue' => today(), 'created_by' => $author,
                ]);
            }
            $departure->fill([
                'sanction_id' => $sanction->getKey(), 'motif' => 'expulsion',
                'observations' => 'Expulsion disciplinaire '.$sanction->numero_sanction.'. État des lieux de sortie à réaliser.',
                'statut' => 'en_attente', 'decision' => 'approuvee',
                'motif_decision' => $sanction->motif, 'date_decision' => now(), 'traite_par' => $author,
            ])->save();
        }
    }
}
