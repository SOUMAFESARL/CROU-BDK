<?php

namespace App\Services;

use App\Models\User;

final class AdmissionPermissions
{
    public const PREFIX = 'hebergement.admissions.';
    public const ROUTE = 'admin.hebergement.campagnes_admission.';
    public const SCOPES = [
        'campagnes' => ['Campagnes', ['view']],
        'demandes' => ['Liste des demandes', ['view']],
        'toutes' => ['Toutes les demandes', ['view', 'lever_refus']],
        'recevables' => ['Demandes recevables', ['view']],
        'recevables_demandes' => ['Demandes recevables / Liste des demandes', ['view', 'lever_refus']],
        'provisoire_admission' => ['Liste provisoire admissions', ['view', 'valider', 'refuser', 'attente', 'export']],
        'provisoire_readmission' => ['Liste provisoire réadmissions', ['view', 'valider', 'refuser', 'attente', 'export']],
        'rejetees' => ['Demandes irrecevables', ['view', 'lever_refus']],
        'refusees' => ['Demandes refusées', ['view', 'lever_refus']],
        'commission' => ['Décision de la commission', ['view']],
        'commission_validees' => ['Commission / Demandes validées', ['view', 'refuser', 'attente', 'approuver']],
        'commission_attente' => ['Commission / File d’attente', ['view', 'valider', 'refuser', 'approuver']],
        'definitives' => ['Listes définitives', ['view']],
        'definitive_validees' => ['Liste définitive / Demandes validées', ['view', 'publier', 'export']],
        'definitive_attente' => ['Liste définitive / File d’attente', ['view', 'publier', 'export']],
        'dossier' => ['Dossier de candidature', ['lever_refus', 'traiter', 'document', 'decision']],
    ];

    private const SECTIONS = [
        'toutes' => ['toutes' => 'toutes'],
        'recevables' => ['demandes' => 'recevables_demandes', 'admission' => 'provisoire_admission', 'readmission' => 'provisoire_readmission'],
        'rejetees' => ['rejetees' => 'rejetees'],
        'refusees' => ['refusees' => 'refusees'],
        'validees' => ['validees' => 'commission_validees', 'attente' => 'commission_attente'],
        'definitives' => ['validees' => 'definitive_validees', 'attente' => 'definitive_attente'],
    ];

    private const PARENTS = [
        'toutes' => ['demandes'],
        'recevables' => ['demandes'],
        'rejetees' => ['demandes'],
        'refusees' => ['demandes'],
        'recevables_demandes' => ['demandes', 'recevables'],
        'provisoire_admission' => ['demandes', 'recevables'],
        'provisoire_readmission' => ['demandes', 'recevables'],
        'commission_validees' => ['commission'],
        'commission_attente' => ['commission'],
        'definitive_validees' => ['definitives'],
        'definitive_attente' => ['definitives'],
    ];

    public static function slug(string $scope, string $action = 'view'): string
    {
        return self::PREFIX.$scope.'_'.$action;
    }

    public function allows(?User $user, string $scope, string $action = 'view'): bool
    {
        $actions = self::SCOPES[$scope][1] ?? [];
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
        return $slugs;
    }
}
