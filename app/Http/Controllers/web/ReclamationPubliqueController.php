<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Services\ReclamationService;
use App\Models\Reclamation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Formulaire public « Faire une réclamation », ouvert depuis la page de connexion pendant une période de réclamation. */
class ReclamationPubliqueController extends Controller
{
    public function __construct(private ReclamationService $reclamations) {}

    public function create(): Response
    {
        $periode = $this->reclamations->periodeOuverte();

        return Inertia::render('Etudiant/Auth/Reclamation', [
            'periode' => $periode ? [
                'campagne' => collect([$periode->campagne->libelle, $periode->campagne->annee_campagne])->filter()->implode(' · '),
                'dateFin' => $periode->date_fin->toDateString(),
            ] : null,
            'types' => $periode
                ? collect($this->reclamations->typesOuverts($periode->campagne))->map(fn ($type) => ['value' => $type, 'label' => Reclamation::TYPES[$type]])->values()
                : [],
            'liens' => ['envoyer' => route('etudiant.reclamation.store'), 'connexion' => route('etudiant.login')],
            'logoUrl' => systemLogo('admin/assets/images/logos/crou_bdk.jpeg'),
            'backgroundUrl' => asset('files/assets/images/auth/crou_bondoukou.jpg'),
            'year' => now()->year,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $periode = $this->reclamations->periodeOuverte();
        if (! $periode) {
            return back()->with('error', 'Les réclamations ne sont pas ouvertes pour le moment.');
        }

        $donnees = $request->validate([
            'nom_prenoms' => ['required', 'string', 'min:3', 'max:150'],
            'num_bac_matricule' => ['required', 'string', 'max:50'],
            'type_demande' => ['required', Rule::in($this->reclamations->typesOuverts($periode->campagne))],
            'motif' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'nom_prenoms.required' => 'Saisissez votre nom et vos prénoms.',
            'nom_prenoms.min' => 'Saisissez votre nom et vos prénoms complets.',
            'nom_prenoms.max' => 'Le nom et les prénoms ne doivent pas dépasser 150 caractères.',
            'num_bac_matricule.required' => 'Saisissez votre numéro de table au bac ou votre matricule.',
            'num_bac_matricule.max' => 'Le numéro ne doit pas dépasser 50 caractères.',
            'type_demande.required' => 'Choisissez le type de votre demande.',
            'type_demande.in' => 'Ce type de demande n’est pas ouvert pour cette campagne.',
            'motif.required' => 'Expliquez le motif de votre réclamation.',
            'motif.min' => 'Expliquez votre motif en quelques mots (10 caractères minimum).',
            'motif.max' => 'Le motif ne doit pas dépasser 2 000 caractères.',
        ]);
        $reclamation = $this->reclamations->deposer($periode, $donnees);

        return back()->with('success', "Votre réclamation a bien été enregistrée sous le numéro {$reclamation->numero}. Conservez ce numéro.");
    }
}
