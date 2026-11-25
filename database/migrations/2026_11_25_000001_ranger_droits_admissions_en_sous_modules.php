<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Campagnes et admissions : les droits des onglets quittent le module, qui ne garde que ses droits de base (consulter,
 * créer, modifier, supprimer…). Chaque onglet devient un sous-module, comme « Dérogations d’admission ».
 * Le droit de l’onglet « Campagnes » se confond avec « Consulter » du module, celui de
 * « Toutes les demandes » avec « Liste des demandes » ; les
 * listes provisoires (admissions et réadmissions) partagent les mêmes droits, les deux onglets des
 * listes définitives aussi ; « Approuver » (bouton « Approuver la liste », commun aux
 * demandes validées et à la file d’attente) se donne sur « Décision de la commission ».
 * Dérogations : « Valider » (ancien circuit à deux responsables) devient « Déroger » (bouton de la fiche, immédiat),
 * qui remplace aussi « Lever un refus automatique » de chaque liste.
 * Les droits sont déplacés (`hebergement.admissions.commission_validees_view` →
 * `hebergement.admissions.commission-validees.view`) : les rôles qui les ont les gardent. Idempotente.
 */
return new class extends Migration
{
    private const MODULE = 'hebergement.admissions';

    /** Onglet => [onglet parent, nom, actions (ancien nom)] ; un parent précède toujours ses enfants. */
    private const ONGLETS = [
        'demandes' => [null, 'Liste des demandes', ['view']],
        'recevables' => ['demandes', 'Demandes recevables', ['view']],
        'recevables_demandes' => ['recevables', 'Liste des demandes recevables', ['view']],
        'provisoire' => ['recevables', 'Listes provisoires', ['view', 'valider', 'refuser', 'attente', 'export']],
        'rejetees' => ['demandes', 'Demandes irrecevables', ['view']],
        'refusees' => ['demandes', 'Demandes refusées', ['view']],
        'commission' => [null, 'Décision de la commission', ['view', 'approuver']],
        'commission_validees' => ['commission', 'Demandes validées', ['view', 'refuser', 'attente']],
        'commission_attente' => ['commission', 'File d’attente', ['view', 'valider', 'refuser']],
        'definitives' => [null, 'Listes définitives', ['view', 'publier', 'export']],
        'dossier' => [null, 'Dossier de candidature', ['traiter', 'document', 'decision']],
    ];

    private const LIBELLES = [
        'view' => 'Consulter', 'validate' => 'Valider', 'refuser' => 'Refuser', 'attente' => 'Mettre en attente',
        'approuver' => 'Approuver', 'publier' => 'Publier', 'export' => 'Exporter',
        'traiter' => 'Traiter',
        'document' => 'Valider ou rejeter une pièce', 'decision' => 'Décider depuis la fiche', 'deroger' => 'Déroger',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $module = DB::table('modules')->where('slug', self::MODULE)->first();
            if (! $module) {
                return;
            }

            $superAdmin = DB::table('roles')->where('id', 1)->exists();
            $ids = [];
            $niveaux = [];
            $aRetirer = ['campagnes_view', 'commission_validees_approuver', 'toutes_view'];
            $position = 0;
            foreach (self::ONGLETS as $onglet => [$parent, $nom, $actions]) {
                $slug = self::MODULE.'.'.str_replace('_', '-', $onglet);
                $niveaux[$onglet] = ($parent ? $niveaux[$parent] : (int) $module->level) + 1;
                $droits = array_map(fn ($action) => $action === 'valider' ? 'validate' : $action, $actions);
                $position++;
                $ids[$onglet] = DB::table('modules')->where('slug', $slug)->value('id') ?? DB::table('modules')->insertGetId([
                    'parent_id' => $parent ? $ids[$parent] : $module->id, 'name' => $nom, 'slug' => $slug,
                    'position' => $position, 'level' => $niveaux[$onglet], 'actions' => json_encode($droits),
                    'is_active' => true, 'show_in_menu' => false, 'is_system' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                DB::table('modules')->where('id', $ids[$onglet])->update(['actions' => json_encode($droits), 'updated_at' => now()]);

                foreach ($actions as $rang => $action) {
                    $aRetirer[] = $onglet.'_'.$action;
                    $id = $this->deplacer(self::MODULE.'.'.$onglet.'_'.$action, $slug, $ids[$onglet], $nom, $droits[$rang]);
                    if ($superAdmin) {
                        $this->accorder(1, $id);
                    }
                }
            }

            // Onglet « Campagnes » : pas de sous-module, son droit rejoint « Consulter » du module.
            $this->fondre(['campagnes_view', 'campagnes.view'], 'view');
            DB::table('modules')->where('slug', self::MODULE.'.campagnes')->delete();
            // « Toutes les demandes » : c'est la liste des demandes elle-même. Qui ne pouvait pas l'ouvrir ne la reçoit pas.
            $this->limiter('demandes.view', ['toutes_view', 'toutes.view']);
            $this->fondre(['toutes_view', 'toutes.view'], 'demandes.view');
            DB::table('modules')->where('slug', self::MODULE.'.toutes')->delete();
            // Une seule série de droits pour les listes provisoires des admissions et des réadmissions.
            foreach (['admission', 'readmission'] as $volet) {
                foreach (['view', 'valider', 'refuser', 'attente', 'export'] as $action) {
                    $aRetirer[] = "provisoire_{$volet}_{$action}";
                    $droit = $action === 'valider' ? 'validate' : $action;
                    $this->fondre(["provisoire_{$volet}_{$action}", "provisoire-{$volet}.{$droit}"], 'provisoire.'.$droit);
                }
                DB::table('modules')->where('slug', self::MODULE.'.provisoire-'.$volet)->delete();
            }
            // Listes définitives : les droits se donnent sans distinguer demandes validées et file d'attente.
            $this->limiter('definitives.view', ['definitive_validees_view', 'definitive-validees.view', 'definitive_attente_view', 'definitive-attente.view']);
            foreach (['validees', 'attente'] as $liste) {
                foreach (['view', 'publier', 'export'] as $action) {
                    $aRetirer[] = "definitive_{$liste}_{$action}";
                    $this->fondre(["definitive_{$liste}_{$action}", "definitive-{$liste}.{$action}"], 'definitives.'.$action);
                }
                DB::table('modules')->where('slug', self::MODULE.'.definitive-'.$liste)->delete();
            }
            // Dérogations : le droit affiche le bouton « Déroger », appliqué sur-le-champ par l'agent qui l'a.
            $derogations = DB::table('modules')->where('slug', self::MODULE.'.derogations')->first();
            if ($derogations) {
                $id = $this->deplacer(self::MODULE.'.derogations.validate', $derogations->slug, (int) $derogations->id, $derogations->name, 'deroger');
                DB::table('permissions')->where('id', $id)->update(['description' => 'Afficher le bouton « Déroger » de la fiche du dossier et des listes, et appliquer la dérogation.']);
                DB::table('modules')->where('id', $derogations->id)->update(['actions' => json_encode(['deroger']), 'updated_at' => now()]);
            }
            foreach (['toutes', 'demandes', 'recevables_demandes', 'rejetees', 'refusees', 'dossier'] as $onglet) {
                $aRetirer[] = $onglet.'_lever_refus';
                $this->fondre([$onglet.'_lever_refus', str_replace('_', '-', $onglet).'.lever_refus'], 'derogations.deroger');
            }
            // Le bouton « Approuver la liste » est commun aux deux onglets de la décision de la commission.
            $this->fondre(['commission_validees_approuver', 'commission-validees.approuver'], 'commission.approuver');

            DB::table('modules')->where('id', $module->id)->update([
                'actions' => json_encode(array_values(array_diff(json_decode($module->actions ?: '[]', true) ?: [], $aRetirer))),
                'updated_at' => now(),
            ]);
        });
    }

    /** Renomme l'ancien droit (ses attributions suivent) ; si les deux existent, l'ancien est fusionné dans le nouveau. */
    private function deplacer(string $ancienSlug, string $moduleSlug, int $moduleId, string $module, string $action): int
    {
        $libelle = self::LIBELLES[$action].' — '.$module;
        $droit = [
            'slug' => $moduleSlug.'.'.$action, 'module_id' => $moduleId, 'module' => $module, 'action' => $action,
            'name' => $libelle, 'description' => $libelle, 'updated_at' => now(),
        ];
        $ancien = DB::table('permissions')->where('slug', $ancienSlug)->value('id');
        $id = DB::table('permissions')->where('slug', $droit['slug'])->value('id');

        if ($id && $ancien) {
            $this->fusionner($ancien, $id);
        }

        $id ??= $ancien;
        if (! $id) {
            return (int) DB::table('permissions')->insertGetId($droit + ['created_at' => now()]);
        }
        DB::table('permissions')->where('id', $id)->update($droit);

        return (int) $id;
    }

    /** Retire un droit aux rôles qui n'ont pas l'ancien droit (s'il existe encore) avec lequel il va être regroupé. */
    private function limiter(string $droit, array $anciens): void
    {
        $ids = DB::table('permissions')->whereIn('slug', array_map(fn ($ancien) => self::MODULE.'.'.$ancien, $anciens))->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }
        $roles = DB::table('role_permission')->whereIn('permission_id', $ids)->pluck('role_id');
        DB::table('role_permission')
            ->whereIn('permission_id', DB::table('permissions')->where('slug', self::MODULE.'.'.$droit)->pluck('id'))
            ->whereNotIn('role_id', [1, ...$roles])->delete();
    }

    /** Regroupe d'anciens droits du module dans un autre de ses droits. */
    private function fondre(array $anciens, string $nouveau): void
    {
        $id = DB::table('permissions')->where('slug', self::MODULE.'.'.$nouveau)->value('id');
        if (! $id) {
            return;
        }
        foreach (DB::table('permissions')->whereIn('slug', array_map(fn ($ancien) => self::MODULE.'.'.$ancien, $anciens))->pluck('id') as $ancien) {
            $this->fusionner($ancien, $id);
        }
    }

    /** Les rôles de l'ancien droit reçoivent le nouveau, puis l'ancien est supprimé. */
    private function fusionner(int $ancien, int $nouveau): void
    {
        foreach (DB::table('role_permission')->where('permission_id', $ancien)->pluck('role_id') as $role) {
            $this->accorder($role, $nouveau);
        }
        DB::table('role_permission')->where('permission_id', $ancien)->delete();
        DB::table('permissions')->where('id', $ancien)->delete();
    }

    private function accorder(int|string $role, int $permission): void
    {
        $lien = ['role_id' => $role, 'permission_id' => $permission];
        if (! DB::table('role_permission')->where($lien)->exists()) {
            DB::table('role_permission')->insert($lien + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Le rangement reste : les droits peuvent avoir été attribués à des rôles personnalisés.
    }
};
