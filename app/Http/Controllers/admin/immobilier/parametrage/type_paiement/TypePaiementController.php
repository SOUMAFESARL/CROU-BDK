<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\type_paiement;

use App\Http\Controllers\admin\immobilier\parametrage\ReferentielInertia;
use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Models\TypePaiement;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Types de paiement (Paramétrage hébergement) proposés au champ « Type de paiement » du formulaire « Nouveau paiement ».
 * Le code d'un type (interne, non affiché), enregistré sur les paiements, est dérivé du libellé à la création puis ne change plus.
 */
class TypePaiementController extends Controller
{
    use ReferentielInertia;

    private const ROUTE = 'admin.hebergement.parametrage-herb.type-paiement.';

    public function index(Request $request)
    {
        $url = $this->urlSiAutorise($request);
        $query = TypePaiement::query()->ordonnes();
        $q = $this->rechercher($request, $query);
        $paiements = Paiement::withTrashed()->selectRaw('type_paiement, count(*) as total')->groupBy('type_paiement')->pluck('total', 'type_paiement');
        $total = TypePaiement::count();
        $actifs = TypePaiement::where('actif', true)->count();

        return Inertia::render('Admin/Hebergement/Parametrage/TypesPaiement', [
            'types' => $query->paginate(10)->withQueryString()->through(fn (TypePaiement $type) => [
                'id' => (string) $type->id_type_paiement,
                'name' => $type->name,
                'description' => $type->description,
                'statut' => $type->actif ? 'actif' : 'inactif',
                'systeme' => $type->systeme,
                'paiements' => (int) ($paiements[$type->code] ?? 0),
                'urls' => [
                    'update' => $url(self::ROUTE.'update', $type->id_type_paiement),
                    // Suppression proposée pour tous les types ; refusée tant qu'un paiement utilise le type.
                    'destroy' => $url(self::ROUTE.'destroy', $type->id_type_paiement),
                ],
            ]),
            'stats' => ['total' => $total, 'utilises' => $actifs, 'inutilises' => $total - $actifs],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route(self::ROUTE.'index'),
                'store' => $url(self::ROUTE.'store'),
                'paiements' => $url('admin.hebergement.paiements.index'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $donnees = $this->valider($request);
        TypePaiement::create($donnees + [
            'code' => TypePaiement::codeDepuis($donnees['name']),
            'systeme' => false,
            'ordre' => 50,
        ]);

        return back()->with('success', 'Type de paiement ajouté avec succès.');
    }

    public function update(Request $request, string $id)
    {
        $type = TypePaiement::findOrFail($id);
        $donnees = $this->valider($request, $type);
        if ($type->systeme && ! $donnees['actif']) {
            throw ValidationException::withMessages(['statut' => 'Ce type est utilisé par l’application (loyers, cautions, paiements en ligne) : il reste actif.']);
        }
        $type->update($donnees);

        return back()->with('success', 'Type de paiement modifié avec succès.');
    }

    public function destroy(string $id)
    {
        $type = TypePaiement::findOrFail($id);
        $utilisations = Paiement::withTrashed()->where('type_paiement', $type->code)->count();
        if ($utilisations > 0) {
            throw ValidationException::withMessages(['type' => $type->systeme
                ? "Ce type est utilisé par {$utilisations} paiement(s) : il ne peut pas être supprimé."
                : "Ce type est utilisé par {$utilisations} paiement(s) : désactivez-le plutôt que de le supprimer."]);
        }
        // Un type système non utilisé peut être supprimé : il n'est plus proposé dans « Nouveau paiement »,
        // et les paiements enregistrés par l'application (loyers en ligne, cautions) gardent leur libellé par défaut.
        $type->delete();

        return back()->with('success', 'Type de paiement supprimé avec succès.');
    }

    /** @return array{name: string, description: ?string, actif: bool} */
    private function valider(Request $request, ?TypePaiement $type = null): array
    {
        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:100', function ($attribut, $valeur, $echec) use ($type) {
                $doublon = TypePaiement::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $valeur))])
                    ->when($type, fn ($q) => $q->whereKeyNot($type->getKey()))
                    ->exists();
                if ($doublon) {
                    $echec('Un type de paiement porte déjà ce libellé.');
                }
            }],
            'description' => 'nullable|string|max:2000',
            'statut' => 'required|in:actif,inactif',
        ], [
            'name.required' => 'Renseignez le libellé du type de paiement.',
            'name.max' => 'Le libellé ne doit pas dépasser 100 caractères.',
            'statut.required' => 'Choisissez le statut du type de paiement.',
        ]);

        return [
            'name' => trim($donnees['name']),
            'description' => ($donnees['description'] ?? '') !== '' ? $donnees['description'] : null,
            'actif' => $donnees['statut'] === 'actif',
        ];
    }
}
