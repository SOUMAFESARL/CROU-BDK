<?php

namespace App\Http\Controllers\etudiant;

use App\Http\Controllers\Controller;
use App\Models\AssignationChambre;
use App\Models\DisciplinaryReport;
use App\Models\DisciplinaryReportHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DisciplinaryIncidentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $student = Auth::guard('etudiant')->user();

        $data = $request->validateWithBag('incident', [
            'date_incident' => ['required', 'date', 'before_or_equal:now'],
            'nature' => ['required', Rule::in(['tapage', 'violence', 'harcelement', 'degradation', 'vol', 'non_respect_reglement', 'autre'])],
            'lieu' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'temoins' => ['nullable', 'string', 'max:2000'],
            'gravite' => ['required', Rule::in(['faible', 'moyenne', 'grave', 'critique'])],
            'confidentiel' => ['nullable', 'boolean'],
        ], [
            'description.min' => 'Décrivez les faits en au moins 20 caractères.',
            'date_incident.before_or_equal' => 'La date de l’incident ne peut pas être future.',
        ]);

        $assignment = AssignationChambre::actives()
            ->where('etudiant_id', $student->id_etudiant)
            ->with('chambre')
            ->latest('date_assignation')
            ->first();

        if (!$assignment || !$assignment->chambre) {
            throw ValidationException::withMessages([
                'residence' => 'Aucune chambre active n’est associée à votre compte. Contactez l’administration.',
            ]);
        }

        $natureLabels = [
            'tapage' => 'Tapage / nuisance sonore',
            'violence' => 'Violence ou menace',
            'harcelement' => 'Harcèlement',
            'degradation' => 'Dégradation volontaire',
            'vol' => 'Vol ou tentative de vol',
            'non_respect_reglement' => 'Non-respect du règlement',
            'autre' => 'Autre incident',
        ];

        $report = DB::transaction(function () use ($data, $student, $assignment, $natureLabels) {
            $report = DisciplinaryReport::create([
                'etudiant_id' => $student->id_etudiant,
                'declarant_id' => $student->id_etudiant,
                'source' => 'etudiant',
                'cite_id' => $assignment->chambre->cite_id,
                'batiment_id' => $assignment->chambre->batiment_id,
                'chambre_id' => $assignment->chambre_id,
                'date_incident' => $data['date_incident'],
                'nature' => $natureLabels[$data['nature']],
                'lieu' => $data['lieu'],
                'description' => $data['description'],
                'temoins' => $data['temoins'] ?? null,
                'gravite' => $data['gravite'],
                'confidentiel' => (bool) ($data['confidentiel'] ?? false),
                'statut' => 'transmis',
                'transmis_at' => now(),
                'created_by' => $student->id_etudiant,
            ]);

            DisciplinaryReportHistory::create([
                'rapport_id' => $report->id_rapport,
                'action' => 'declaration_etudiant',
                'nouveau_statut' => 'transmis',
                'commentaire' => 'Incident déclaré depuis l’espace étudiant.',
                'auteur_id' => $student->id_etudiant,
            ]);

            return $report;
        });

        return redirect()->route('etudiants.dashboard', ['section' => 'incidents'])
            ->with('incident_success', "Votre signalement {$report->numero_rapport} a été transmis au Service Sécurité.");
    }
}
