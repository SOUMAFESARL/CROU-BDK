<?php

namespace App\Services;

use App\Models\User;

final class AdmissionPermissions
{
    public const PREFIX = 'hebergement.admissions.';
    public const ROUTE = 'admin.hebergement.campagnes_admission.';
    public const SCOPES = [
        'campagnes' => ['Campagnes', ['view']],
        'toutes' => ['Liste des demandes', ['view']],
        'recevables' => ['Demandes recevables', ['view']],
        'recevables_demandes' => ['Demandes recevables / Liste des demandes', ['view']],
        'provisoire_admission' => ['Liste provisoire admissions', ['view', 'valider', 'refuser', 'attente', 'export']],
        'provisoire_readmission' => ['Liste provisoire réadmissions', ['view', 'valider', 'refuser', 'attente', 'export']],
        'rejetees' => ['Demandes irrecevables', ['view']],
        'refusees' => ['Demandes refusées', ['view']],
        'commission' => ['Décision de la commission', ['view', 'approuver']],
        'commission_validees' => ['Commission / Demandes validées', ['view', 'refuser', 'attente']],
        'commission_attente' => ['Commission / File d’attente', ['view', 'valider', 'refuser']],
        'definitive_validees' => ['Liste définitive / Demandes validées', ['view', 'publier', 'export']],
        'definitive_attente' => ['Liste définitive / File d’attente', ['view', 'export']],
        'dossier' => ['Dossier de candidature', ['traiter', 'document', 'decision']],
    ];

    /** « Déroger » : bouton de la fiche du dossier et levée des refus automatiques dans les listes. */
    private const DEROGATIONS = ['derogations' => ['deroger']];

    private const SECTIONS = [
        'toutes' => ['toutes' => 'toutes'],
        'recevables' => ['demandes' => 'recevables_demandes', 'admission' => 'provisoire_admission', 'readmission' => 'provisoire_readmission'],
        'rejetees' => ['rejetees' => 'rejetees'],
        'refusees' => ['refusees' => 'refusees'],
        'validees' => ['validees' => 'commission_validees', 'attente' => 'commission_attente'],
        'definitives' => ['validees' => 'definitive_validees', 'attente' => 'definitive_attente'],
    ];

    private const PARENTS = [
        'recevables_demandes' => ['recevables'],
        'provisoire_admission' => ['recevables'],
        'provisoire_readmission' => ['recevables'],
        'commission_validees' => ['commission'],
        'commission_attente' => ['commission'],
    ];

    /** Chaque onglet est un sous-module (`hebergement.admissions.commission-validees`) ; « valider » y est l'action commune `validate`. */
    public static function slug(string $scope, string $action = 'view'): string
    {
        $module = match ($scope) {
            // La liste des campagnes suit les droits de base du module.
            'campagnes' => '',
            // L'onglet « Toutes les demandes » est le sous-module « Liste des demandes » lui-même.
            'toutes' => 'demandes.',
            // Les mêmes droits valent pour les deux listes provisoires (admissions et réadmissions).
            'provisoire_admission', 'provisoire_readmission' => 'provisoire.',
            // … et pour les deux onglets des listes définitives (demandes validées et file d’attente).
            'definitive_validees', 'definitive_attente' => 'definitives.',
            default => str_replace('_', '-', $scope).'.',
        };

        return self::PREFIX.$module.($action === 'valider' ? 'validate' : $action);
    }

    public function allows(?User $user, string $scope, string $action = 'view'): bool
    {
        $actions = self::SCOPES[$scope][1] ?? self::DEROGATIONS[$scope] ?? [];
        if (!$user || !in_array($action, $actions, true) || !$user->hasPermission(self::slug($scope, $action))) {
            return false;
        }

        $views = self::PARENTS[$scope] ?? [];
        if ($action !== 'view' && in_array('view', $actions, true)) {
            $views[] = $scope;
        }

        return collect($views)->every(fn ($parent) => $user->hasPermission(self::slug($parent)));
    }

    public function isListTab(string $etat, ?string $tab): bool
    {
        return $tab !== null && isset(self::SECTIONS[$etat][$tab]);
    }

    public function sections(?User $user, string $etat): array
    {
        return array_filter(self::SECTIONS[$etat] ?? [], fn ($scope) => $this->allows($user, $scope));
    }

    public function url(?User $user, string $etat, array $params = []): ?string
    {
        return $this->sections($user, $etat) === [] ? null : route(self::ROUTE.'admission', ['etat' => $etat] + $params);
    }

    public function demandesUrl(?User $user, array $params = []): ?string
    {
        foreach (['toutes', 'recevables', 'rejetees', 'refusees'] as $etat) {
            if ($url = $this->url($user, $etat, $params)) {
                return $url;
            }
        }

        return null;
    }

    public static function slugs(string $action, ?array $scopes = null): array
    {
        $slugs = [];
        foreach (self::SCOPES as $scope => [, $actions]) {
            if (($scopes === null || in_array($scope, $scopes, true)) && in_array($action, $actions, true)) {
                $slugs[] = self::slug($scope, $action);
            }
        }
        return array_values(array_unique($slugs));
    }
}
