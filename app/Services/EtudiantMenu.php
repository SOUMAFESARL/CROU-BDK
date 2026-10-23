<?php

namespace App\Services;

use App\Models\DemandeEtudiant;
use App\Models\Etudiant;
use Illuminate\Http\Request;

/**
 * Menu de l'espace étudiant, partagé par la coque Blade (<x-etudiant-shell>)
 * et la mise en page React (EtudiantLayout, props Inertia `menuEtudiant`).
 *
 * Les entrées `page` correspondent aux sections du tableau de bord
 * (etudiant/index.blade.php) : elles sont actives selon `?section=`.
 * Les sections migrées en React (SECTIONS_REACT) s'ouvrent par un lien classique,
 * sans bascule locale dans la page Blade.
 */
class EtudiantMenu
{
    /** Sections `?section=` servies par une page Inertia (dashboardController::index). */
    public const SECTIONS_REACT = ['dashboard', 'chambre', 'paiements', 'maintenance', 'etat-lieux', 'demandes', 'annonce', 'incidents', 'carte-interactive'];

    /**
     * @return list<array{label: string, items: list<array{label: string, icon: string, href: string, active: bool, page: ?string, react: bool}>}>
     */
    public function groupes(Request $request): array
    {
        // Dossier éligible non encore confirmé : seul le tableau de bord (réponse attendue) est accessible.
        if (self::confirmationAttendue($request->user('etudiant'))) {
            return [['label' => 'Principal', 'items' => [[
                'label' => 'Tableau de bord', 'icon' => 'layout-dashboard', 'href' => route('etudiants.dashboard'),
                'page' => 'dashboard', 'react' => true, 'active' => $request->routeIs('etudiants.dashboard'),
            ]]]];
        }

        $surTableauDeBord = $request->routeIs('etudiants.dashboard');
        $section = $surTableauDeBord ? ($request->query('section') ?: 'dashboard') : null;
        $sectionUrl = fn (string $page) => route('etudiants.dashboard', ['section' => $page]);

        $groupes = [
            'Principal' => [
                ['label' => 'Tableau de bord', 'icon' => 'layout-dashboard', 'page' => 'dashboard', 'href' => route('etudiants.dashboard')],
                ['label' => 'Ma chambre', 'icon' => 'bed-double', 'page' => 'chambre', 'href' => $sectionUrl('chambre')],
            ],
            'Gestion' => [
                ['label' => 'Mon entrée et notifications', 'icon' => 'calendar-check', 'href' => route('etudiants.housing-entry'), 'routes' => ['etudiants.housing-entry']],
                ['label' => 'Réadmission', 'icon' => 'file-plus', 'href' => route('etudiants.readmission'), 'routes' => ['etudiants.readmission*']],
                ['label' => 'Demandes', 'icon' => 'file-pen-line', 'page' => 'demandes', 'href' => $sectionUrl('demandes')],
                ['label' => 'État des lieux', 'icon' => 'clipboard-check', 'page' => 'etat-lieux', 'href' => $sectionUrl('etat-lieux')],
                ['label' => 'Paiements', 'icon' => 'credit-card', 'page' => 'paiements', 'href' => $sectionUrl('paiements')],
                ['label' => 'Annonces', 'icon' => 'megaphone', 'page' => 'annonce', 'href' => $sectionUrl('annonce')],
                ['label' => 'Maintenance', 'icon' => 'wrench', 'page' => 'maintenance', 'href' => $sectionUrl('maintenance')],
                ['label' => 'Signaler un incident', 'icon' => 'triangle-alert', 'page' => 'incidents', 'href' => $sectionUrl('incidents')],
                ['label' => 'Carte interactive', 'icon' => 'map', 'page' => 'carte-interactive', 'href' => $sectionUrl('carte-interactive')],
                ['label' => 'Documents de la résidence', 'icon' => 'file-text', 'href' => route('etudiants.documents.index'), 'routes' => ['etudiants.documents.index']],
                ['label' => 'Mes documents', 'icon' => 'folder-open', 'href' => route('etudiants.documents.personal'), 'routes' => ['etudiants.documents.personal', 'etudiants.contract.*']],
            ],
        ];

        $resultat = [];
        foreach ($groupes as $libelle => $items) {
            $resultat[] = [
                'label' => $libelle,
                'items' => array_map(fn (array $item) => [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'href' => $item['href'],
                    'page' => $item['page'] ?? null,
                    'react' => in_array($item['page'] ?? null, self::SECTIONS_REACT, true),
                    'active' => isset($item['page'])
                        ? $section === $item['page']
                        : $request->routeIs(...$item['routes']),
                ], $items),
            ];
        }

        return $resultat;
    }

    /** La dernière demande de l'étudiant est « Éligible » : il doit confirmer ou refuser avant d'accéder au reste de l'espace. */
    public static function confirmationAttendue(?Etudiant $etudiant): bool
    {
        // `statut_inscription` vaut « en_attente » pour un dossier éligible (DossierWorkflow) : évite une requête dans les autres cas.
        return $etudiant?->exists === true && strtolower((string) $etudiant->statut_inscription) === 'en_attente'
            && DemandeEtudiant::where('etudiant_id', $etudiant->getKey())
            ->latest('created_at')->orderByDesc('id_demande')->value('etat_dossier') === 'eligible';
    }

    /** Initiales affichées dans l'avatar (deux premiers mots, accents compris). */
    public static function initiales(?string $nom): string
    {
        return collect(preg_split('/\s+/', trim($nom ?: 'Étudiant')))
            ->filter()
            ->take(2)
            ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
            ->implode('');
    }
}
