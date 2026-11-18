<?php

namespace App\Http\Controllers\etudiant;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReadmissionWebRequest;
use App\Http\Services\ReadmissionFormulaire;
use App\Http\Services\ReadmissionService;
use App\Models\Campagne;
use App\Models\Document;
use App\Models\Filiere;
use App\Repositories\DocumentEtudiantRepository;
use App\Repositories\Interfaces\DocumentEtudiantRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Formulaire web « Réadmission » de l'espace étudiant (Inertia).
 * Distinct de ReadmissionController, partagé avec l'API mobile, dont le contrat reste inchangé.
 */
class ReadmissionPageController extends Controller
{
    public function show(Request $request, ReadmissionFormulaire $formulaire)
    {
        $student = $request->attributes->get('student') ?? auth('etudiant')->user();
        abort_unless($student, 401);

        return Inertia::render('Etudiant/Readmission', [
            'campagnes' => Campagne::where('statut', 'active')->get()->map(fn ($campagne) => [
                'id' => $campagne->getKey(),
                'libelle' => $this->libelleCampagne($campagne),
                'dateLimite' => $campagne->date_echeance_demande ? Carbon::parse($campagne->date_echeance_demande)->format('d/m/Y') : null,
                // Pièces demandées par la campagne, telles que paramétrées par l'administration.
                'documents' => $formulaire->documentsCampagne($campagne->getKey())->map(fn (Document $document) => [
                    'slug' => (string) $document->slug,
                    'nom' => (string) $document->name,
                    'requis' => (bool) $document->champs_requis,
                    'formats' => $formulaire->formats($document),
                ])->values(),
            ])->values(),
            'niveaux' => $formulaire->niveauxProposes($student)
                ->map(fn ($niveau) => ['id' => (string) $niveau->getKey(), 'libelle' => (string) $niveau->name])
                ->values(),
            'filieres' => Filiere::orderBy('name')->get()
                ->map(fn ($filiere) => ['id' => (string) $filiere->getKey(), 'libelle' => (string) $filiere->name])
                ->values(),
            // Pré-sélection : la filière de la dernière demande de l'étudiant.
            'filierePrecedente' => $formulaire->filierePrecedente($student),
            'liens' => ['envoyer' => route('etudiants.readmission.store'), 'demandes' => route('etudiants.dashboard', ['section' => 'demandes'])],
        ]);
    }

    /**
     * Dépôt de la réadmission avec ses pièces : les fichiers sont copiés sur disque avant toute écriture en base,
     * puis le dossier et les pièces sont enregistrés ensemble ; en cas d'échec, les fichiers copiés sont supprimés.
     */
    public function store(ReadmissionWebRequest $request, ReadmissionService $service, DocumentEtudiantRepositoryInterface $documents)
    {
        $student = $request->etudiant();
        $pieces = $documents->stockerPieces($request->pieces());

        try {
            $dossier = DB::transaction(function () use ($request, $service, $documents, $student, $pieces) {
                $dossier = $service->submit($student, $request->donnees());
                $documents->enregistrerPieces($pieces, $student->getKey(), $request->input('campagne_id'));

                return $dossier;
            });
        } catch (\Throwable $e) {
            DocumentEtudiantRepository::supprimerFichiers($pieces);
            throw $e;
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Demande de réadmission soumise.', 'data' => $dossier], 201);
        }

        return redirect()->route('etudiants.dashboard', ['section' => 'demandes'])
            ->with('success', 'Votre demande de réadmission a été soumise avec votre compte existant.');
    }

    /** « Libellé – année » ; l'un ou l'autre seul s'il manque une information. */
    private function libelleCampagne(Campagne $campagne): string
    {
        $libelle = trim((string) $campagne->libelle);
        $annee = trim((string) $campagne->annee_campagne);
        if ($libelle !== '' && $annee !== '' && ! str_contains($libelle, $annee)) {
            return $libelle.' – '.$annee;
        }

        return $libelle !== '' ? $libelle : ($annee !== '' ? $annee : 'Campagne en cours');
    }
}
