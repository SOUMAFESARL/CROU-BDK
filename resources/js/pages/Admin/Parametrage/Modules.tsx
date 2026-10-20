import { zodResolver } from '@hookform/resolvers/zod';
import { ChevronRight, ChevronsDownUp, ChevronsUpDown, Info, Lock, Network, Pencil, Plus, Power, ShieldCheck, Trash2, TriangleAlert } from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import { Controller, useForm, type Path } from 'react-hook-form';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { ChampRecherche, ErreurGenerale, Note, PiedAssistant, Recap, useAssistant, Vide } from '@/components/parametrage-hebergement/shared';
import { ToggleCard } from '@/components/parc-immobilier/form/inputs';
import { ACTION_COLORS, moduleIcon } from '@/components/roles/permission-editor';
import { repartirErreurs } from '@/components/vie-residentielle/shared';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { StatCard } from '@/components/ui/stat-card';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { nombre, sansAccents } from '@/lib/text';
import { cn } from '@/lib/utils';
import { enSlug, moduleSchema, type ModuleValeurs } from '@/schemas/modules';

/** Module tel que sérialisé par `ModuleController::index()` (liste à plat). */
type Module = {
    id: number;
    parent_id: number | null;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    route_name: string | null;
    menu_key: string | null;
    submenu_key: string | null;
    position: number;
    actions: string[];
    is_active: boolean;
    show_in_menu: boolean;
    is_system: boolean;
    enfants: number;
    permissions: number;
    /** Permissions du module attribuées à au moins un rôle (bloquent la suppression). */
    permissions_attribuees: number;
    urls: {
        update: string | null;
        toggle: string | null;
        destroy: string | null;
    };
};

type Props = {
    modules: Module[];
    actions: Record<string, string>;
    stats: {
        total: number;
        actifs: number;
        inactifs: number;
        permissions: number;
    };
    urls: { store: string | null; roles: string | null };
};

type Statut = 'tous' | 'actifs' | 'inactifs';
type Edition = { module: Module | null; parentId: number | null };

const RACINE = 'racine';

/** Gestion des modules (remplace `parametrage/modules/index.blade.php`) ; actions `store`, `update`, `toggle`, `destroy` inchangées. */
export default function Modules({ modules, actions, stats, urls }: Props) {
    const [recherche, setRecherche] = useState('');
    const [statut, setStatut] = useState<Statut>('tous');
    const [ouverts, setOuverts] = useState<Set<number>>(() => new Set());
    const [edition, setEdition] = useState<Edition | null>(null);
    const [bascule, setBascule] = useState<Module | null>(null);
    const [suppression, setSuppression] = useState<Module | null>(null);
    const [envoi, setEnvoi] = useState(false);

    const enfants = useMemo(() => {
        const parParent = new Map<number | null, Module[]>();
        modules.forEach((m) => parParent.set(m.parent_id, [...(parParent.get(m.parent_id) ?? []), m]));
        parParent.forEach((liste) => liste.sort((a, b) => a.position - b.position || a.name.localeCompare(b.name, 'fr')));
        return parParent;
    }, [modules]);
    const racines = enfants.get(null) ?? [];

    // Recherche locale (toute l'arborescence est chargée) : un module reste affiché s'il correspond aux filtres ou si l'un de ses descendants y correspond.
    const filtre = sansAccents(recherche.trim().toLowerCase());
    const filtrage = filtre !== '' || statut !== 'tous';
    const visibles = useMemo(() => {
        const ids = new Set<number>();
        const correspond = (m: Module) =>
            (statut === 'tous' || m.is_active === (statut === 'actifs')) &&
            (filtre === '' || sansAccents([m.name, m.slug, m.route_name ?? ''].join(' ').toLowerCase()).includes(filtre));
        const parcourir = (m: Module): boolean => {
            const descendant = (enfants.get(m.id) ?? []).map(parcourir).some(Boolean);
            if (descendant || correspond(m)) ids.add(m.id);
            return ids.has(m.id);
        };
        racines.forEach(parcourir);
        return ids;
    }, [enfants, racines, filtre, statut]);

    const avecEnfants = modules.filter((m) => (enfants.get(m.id) ?? []).length > 0).map((m) => m.id);
    const toutOuvert = avecEnfants.length > 0 && avecEnfants.every((id) => ouverts.has(id));
    const basculer = (id: number) =>
        setOuverts((actuels) => {
            const suivants = new Set(actuels);
            if (suivants.has(id)) suivants.delete(id);
            else suivants.add(id);
            return suivants;
        });

    const executer = async (url: string, method: 'patch' | 'delete', fermer: () => void) => {
        setEnvoi(true);
        const ok = await envoyer(url, {}, { method });
        setEnvoi(false);
        if (ok) fermer();
    };

    const rendreNoeud = (m: Module, profondeur: number) => (
        <Noeud
            key={m.id}
            module={m}
            profondeur={profondeur}
            actions={actions}
            ouvert={filtrage || ouverts.has(m.id)}
            onBasculer={() => basculer(m.id)}
            peutAjouter={!!urls.store}
            onAjouter={() => setEdition({ module: null, parentId: m.id })}
            onModifier={() => setEdition({ module: m, parentId: m.parent_id })}
            onActiver={() => (m.is_active ? setBascule(m) : m.urls.toggle && void executer(m.urls.toggle, 'patch', () => undefined))}
            onSupprimer={() => setSuppression(m)}
        >
            {(enfants.get(m.id) ?? []).filter((e) => visibles.has(e.id)).map((e) => rendreNoeud(e, profondeur + 1))}
        </Noeud>
    );

    const enfantsSuppression = suppression ? (enfants.get(suppression.id) ?? []).length : 0;
    const blocage = !suppression
        ? null
        : enfantsSuppression > 0
          ? `Ce module contient ${nombre(enfantsSuppression)} sous-module(s) : déplacez-les ou supprimez-les d’abord.`
          : suppression.permissions_attribuees > 0
            ? `${nombre(suppression.permissions_attribuees)} permission(s) de ce module sont attribuées à des rôles : retirez-les d’abord depuis Rôles et permissions.`
            : null;

    return (
        <AdminLayout title="Gestion des modules">
            <PageHeader
                title="Gestion des modules"
                description="Arborescence fonctionnelle de l’application : chaque action cochée sur un module génère la permission correspondante."
                actions={
                    <div className="flex flex-wrap gap-2">
                        {urls.roles && (
                            <a
                                href={urls.roles}
                                className="inline-flex h-10 items-center justify-center gap-2 rounded-brand border border-primary bg-white px-4 text-sm text-primary transition-colors hover:bg-soft focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                            >
                                <ShieldCheck {...ICON} aria-hidden /> Rôles et permissions
                            </a>
                        )}
                        {urls.store && (
                            <Button size="sm" className="h-10" onClick={() => setEdition({ module: null, parentId: null })}>
                                <Plus className="size-4" aria-hidden /> Ajouter un module
                            </Button>
                        )}
                    </div>
                }
            />

            <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard tone="green" label="Modules" value={nombre(stats.total)} />
                <StatCard tone="blue" label="Modules actifs" value={nombre(stats.actifs)} />
                <StatCard tone="amber" label="Modules inactifs" value={nombre(stats.inactifs)} />
                <StatCard tone="green" label="Permissions générées" value={nombre(stats.permissions)} />
            </div>

            <div className="mb-4 flex flex-col gap-3 rounded-brand border bg-white p-4 sm:flex-row sm:items-center">
                <ChampRecherche value={recherche} onChange={setRecherche} placeholder="Rechercher un module, une route, un identifiant" />
                <div className="w-full sm:w-48">
                    <Select value={statut} onValueChange={(valeur) => setStatut(valeur as Statut)}>
                        <SelectTrigger aria-label="Statut">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="tous">Tous les statuts</SelectItem>
                            <SelectItem value="actifs">Actifs</SelectItem>
                            <SelectItem value="inactifs">Inactifs</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    className="h-10 sm:ml-auto"
                    disabled={filtrage || avecEnfants.length === 0}
                    onClick={() => setOuverts(toutOuvert ? new Set() : new Set(avecEnfants))}
                >
                    {toutOuvert ? <ChevronsDownUp className="size-4" aria-hidden /> : <ChevronsUpDown className="size-4" aria-hidden />}
                    {toutOuvert ? 'Tout replier' : 'Tout déplier'}
                </Button>
            </div>

            {racines.length === 0 ? (
                <Vide icone={<Network />}>Aucun module configuré.</Vide>
            ) : visibles.size === 0 ? (
                <Vide icone={<Network />}>Aucun module ne correspond à ces critères.</Vide>
            ) : (
                <div className="space-y-3">
                    {racines
                        .filter((r) => visibles.has(r.id))
                        .map((r) => (
                            <section key={r.id} className="overflow-hidden rounded-brand border bg-white">
                                {rendreNoeud(r, 0)}
                            </section>
                        ))}
                </div>
            )}

            <Dialog open={edition !== null} onOpenChange={(ouvert) => !ouvert && setEdition(null)}>
                <DialogContent className="max-w-3xl">
                    {edition !== null && (
                        <Formulaire
                            key={edition.module ? edition.module.id : `nouveau-${edition.parentId ?? RACINE}`}
                            module={edition.module}
                            parentInitial={edition.parentId}
                            modules={modules}
                            enfants={enfants}
                            actions={actions}
                            url={edition.module ? edition.module.urls.update : urls.store}
                            onClose={() => setEdition(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={bascule !== null}
                onOpenChange={(ouvert) => !ouvert && setBascule(null)}
                title={`Désactiver le module « ${bascule?.name ?? ''} » ?`}
                description="Les rôles autres que super-administrateur perdent l’accès à ce module tant qu’il reste inactif, même s’ils en ont les permissions. Vous pourrez le réactiver à tout moment."
                confirmLabel="Désactiver"
                confirmIcon={<Power className="size-4" aria-hidden />}
                danger
                processing={envoi}
                onConfirm={() => bascule?.urls.toggle && void executer(bascule.urls.toggle, 'patch', () => setBascule(null))}
            />

            <ConfirmDialog
                open={suppression !== null}
                onOpenChange={(ouvert) => !ouvert && setSuppression(null)}
                title={`Supprimer le module « ${suppression?.name ?? ''} » ?`}
                description={blocage ?? 'Le module et ses permissions sont supprimés définitivement.'}
                confirmLabel="Supprimer"
                confirmIcon={<Trash2 className="size-4" aria-hidden />}
                danger
                processing={envoi}
                confirmDisabled={blocage !== null}
                onConfirm={() => suppression?.urls.destroy && void executer(suppression.urls.destroy, 'delete', () => setSuppression(null))}
            />
        </AdminLayout>
    );
}

type NoeudProps = {
    module: Module;
    profondeur: number;
    actions: Record<string, string>;
    ouvert: boolean;
    onBasculer: () => void;
    peutAjouter: boolean;
    onAjouter: () => void;
    onModifier: () => void;
    onActiver: () => void;
    onSupprimer: () => void;
    children: ReactNode[];
};

function Noeud({ module: m, profondeur, actions, ouvert, onBasculer, peutAjouter, onAjouter, onModifier, onActiver, onSupprimer, children }: NoeudProps) {
    const Icone = moduleIcon(m.slug);
    const racine = profondeur === 0;
    const aDesEnfants = m.enfants > 0;

    return (
        <div className={cn(!racine && 'border-t')}>
            <div
                className={cn('flex flex-col gap-3 py-3 pr-4 lg:flex-row lg:items-center', racine ? 'bg-white' : 'bg-surface/40', !m.is_active && 'opacity-75')}
                style={{ paddingLeft: `${16 + profondeur * 28}px` }}
            >
                <div className="flex min-w-0 flex-1 items-start gap-3">
                    <button
                        type="button"
                        onClick={onBasculer}
                        disabled={!aDesEnfants}
                        aria-expanded={aDesEnfants ? ouvert : undefined}
                        aria-label={ouvert ? `Replier ${m.name}` : `Déplier ${m.name}`}
                        className="mt-2 flex size-6 shrink-0 cursor-pointer items-center justify-center rounded-brand text-muted hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none disabled:invisible"
                    >
                        <ChevronRight className={cn('size-4 transition-transform', ouvert && 'rotate-90')} aria-hidden />
                    </button>
                    <span
                        className={cn(
                            'flex shrink-0 items-center justify-center rounded-brand',
                            racine ? 'size-10 bg-soft text-primary' : 'size-9 bg-white text-muted ring-1 ring-gray-200',
                        )}
                        aria-hidden
                    >
                        <Icone {...ICON} />
                    </span>
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h3 className={cn('text-gray-900', racine ? 'text-[15px] font-medium' : 'text-sm font-medium')}>{m.name}</h3>
                            <Badge variant={m.is_active ? 'success' : 'danger'}>{m.is_active ? 'Actif' : 'Inactif'}</Badge>
                            {m.is_system && (
                                <Badge variant="neutral">
                                    <Lock className="size-3" aria-hidden /> Système
                                </Badge>
                            )}
                            {!m.show_in_menu && <Badge variant="warning">Masqué du menu</Badge>}
                        </div>
                        <p className="mt-0.5 flex flex-wrap gap-x-3 text-xs text-muted">
                            <code className="text-primary">{m.slug}</code>
                            <span>{m.route_name || 'Aucune route'}</span>
                            <span>Position {m.position}</span>
                            {aDesEnfants && <span>{nombre(m.enfants)} sous-module(s)</span>}
                        </p>
                        {m.actions.length > 0 && (
                            <div className="mt-2 flex flex-wrap gap-1.5">
                                {m.actions.map((a) => (
                                    <span
                                        key={a}
                                        className={cn('rounded border px-2 py-0.5 text-xs', ACTION_COLORS[a] ?? 'border-gray-200 bg-gray-50 text-gray-700')}
                                    >
                                        {actions[a] ?? a}
                                    </span>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
                <RowActions>
                    {peutAjouter && <RowAction label="Ajouter un sous-module" icon={Plus} tone="view" onClick={onAjouter} />}
                    {m.urls.update && <RowAction label="Modifier" icon={Pencil} tone="edit" onClick={onModifier} />}
                    {m.urls.toggle && (
                        <RowAction label={m.is_active ? 'Désactiver' : 'Activer'} icon={Power} tone={m.is_active ? 'default' : 'success'} onClick={onActiver} />
                    )}
                    {m.urls.destroy && <RowAction label="Supprimer" icon={Trash2} tone="danger" onClick={onSupprimer} />}
                </RowActions>
            </div>
            {ouvert && children}
        </div>
    );
}

const ETAPES = ['Identification', 'Navigation', 'Actions', 'Vérification'];
const CHAMPS: Path<ModuleValeurs>[][] = [
    ['parent_id', 'name', 'slug', 'description'],
    ['route_name', 'menu_key', 'submenu_key', 'icon', 'position', 'is_active', 'show_in_menu'],
    ['actions'],
    [],
];

type FormulaireProps = {
    module: Module | null;
    parentInitial: number | null;
    modules: Module[];
    enfants: Map<number | null, Module[]>;
    actions: Record<string, string>;
    url: string | null;
    onClose: () => void;
};

function Formulaire({ module, parentInitial, modules, enfants, actions, url, onClose }: FormulaireProps) {
    const [erreur, setErreur] = useState<string | null>(null);
    // À la création, l'identifiant suit le parent et le nom tant qu'il n'a pas été saisi à la main.
    const [slugAuto, setSlugAuto] = useState(module === null);

    // Parents possibles : tout sauf le module lui-même et ses descendants (refusé aussi côté serveur).
    const options = useMemo(() => {
        const exclus = new Set<number>();
        const exclure = (id: number) => {
            exclus.add(id);
            (enfants.get(id) ?? []).forEach((e) => exclure(e.id));
        };
        if (module) exclure(module.id);
        const liste: { module: Module; profondeur: number }[] = [];
        const parcourir = (parent: number | null, profondeur: number) =>
            (enfants.get(parent) ?? []).forEach((m) => {
                if (exclus.has(m.id)) return;
                liste.push({ module: m, profondeur });
                parcourir(m.id, profondeur + 1);
            });
        parcourir(null, 0);
        return liste;
    }, [module, enfants]);

    const positionSuivante = (parent: number | null) => Math.max(0, ...(enfants.get(parent) ?? []).map((m) => m.position)) + 1;

    const {
        register,
        control,
        trigger,
        watch,
        setValue,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<ModuleValeurs>({
        resolver: zodResolver(moduleSchema),
        mode: 'onTouched',
        defaultValues: {
            parent_id: parentInitial ? String(parentInitial) : RACINE,
            name: module?.name ?? '',
            slug: module?.slug ?? (parentInitial ? `${modules.find((m) => m.id === parentInitial)?.slug ?? ''}.` : ''),
            description: module?.description ?? '',
            icon: module?.icon ?? '',
            route_name: module?.route_name ?? '',
            menu_key: module?.menu_key ?? '',
            submenu_key: module?.submenu_key ?? '',
            position: String(module?.position ?? positionSuivante(parentInitial)),
            actions: module?.actions ?? ['view'],
            is_active: module?.is_active ?? true,
            show_in_menu: module?.show_in_menu ?? true,
        },
    });
    const { etape, atteinte, aller, allerA, toutValider, derniere } = useAssistant<ModuleValeurs>(CHAMPS, trigger, module !== null);
    const v = watch();
    const parent = v.parent_id === RACINE ? null : (modules.find((m) => String(m.id) === v.parent_id) ?? null);
    const retirees = module ? module.actions.filter((a) => !v.actions.includes(a)) : [];

    const proposerSlug = (nom: string, parentSlug: string | null) => {
        const suffixe = enSlug(nom);
        setValue('slug', parentSlug ? `${parentSlug}.${suffixe}` : suffixe, {
            shouldValidate: suffixe !== '',
        });
    };

    const enregistrer = (valeurs: ModuleValeurs) =>
        envoyer<ModuleValeurs>(
            url!,
            {
                parent_id: valeurs.parent_id === RACINE ? null : Number(valeurs.parent_id),
                name: valeurs.name.trim(),
                slug: valeurs.slug.trim(),
                description: valeurs.description.trim() || null,
                icon: valeurs.icon.trim() || null,
                route_name: valeurs.route_name.trim() || null,
                menu_key: valeurs.menu_key.trim() || null,
                submenu_key: valeurs.submenu_key.trim() || null,
                position: Number(valeurs.position),
                actions: valeurs.actions,
                is_active: valeurs.is_active,
                show_in_menu: valeurs.show_in_menu,
            },
            {
                method: module ? 'put' : 'post',
                onSuccess: onClose,
                onError: (erreurs) => setErreur(repartirErreurs(erreurs, CHAMPS, (champ, e) => setError(champ, e), aller)),
            },
        );

    const vide = <span className="text-muted">Non renseigné</span>;

    return (
        <form
            noValidate
            className="flex min-h-0 flex-col"
            onSubmit={async (event) => {
                event.preventDefault();
                setErreur(null);
                if (!derniere) await allerA(etape + 1);
                else if (await toutValider()) await handleSubmit(enregistrer)();
            }}
        >
            <DialogHeader>
                <DialogTitle>{module ? `Modifier le module « ${module.name} »` : 'Ajouter un module'}</DialogTitle>
                <DialogDescription>
                    Étape {etape + 1} sur {ETAPES.length}
                    {module?.is_system && ' · Module système'}
                </DialogDescription>
            </DialogHeader>
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={(index) => void allerA(index)} />
            <DialogBody className="space-y-4">
                <ErreurGenerale message={erreur} />

                {etape === 0 && (
                    <>
                        <Controller
                            control={control}
                            name="parent_id"
                            render={({ field }) => (
                                <Field id="mod-parent" label="Module parent" required error={errors.parent_id?.message}>
                                    <Select
                                        value={field.value}
                                        onValueChange={(valeur) => {
                                            field.onChange(valeur);
                                            const p = valeur === RACINE ? null : Number(valeur);
                                            if (slugAuto) proposerSlug(v.name, modules.find((m) => m.id === p)?.slug ?? null);
                                            if (!module) setValue('position', String(positionSuivante(p)));
                                        }}
                                    >
                                        <SelectTrigger id="mod-parent" invalid={!!errors.parent_id}>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={RACINE}>Aucun : module principal</SelectItem>
                                            {options.map(({ module: m, profondeur }) => (
                                                <SelectItem key={m.id} value={String(m.id)}>
                                                    <span
                                                        style={{
                                                            paddingLeft: `${profondeur * 16}px`,
                                                        }}
                                                    >
                                                        {m.name}
                                                    </span>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </Field>
                            )}
                        />
                        <Field id="mod-name" label="Nom du module" required error={errors.name?.message}>
                            <Input
                                id="mod-name"
                                maxLength={120}
                                placeholder="Ex. Sanctions disciplinaires"
                                invalid={!!errors.name}
                                autoFocus
                                {...register('name', {
                                    onChange: (event) => slugAuto && proposerSlug(event.target.value, parent?.slug ?? null),
                                })}
                            />
                        </Field>
                        <Field
                            id="mod-slug"
                            label="Identifiant technique"
                            required
                            error={errors.slug?.message}
                            hint={
                                module?.is_system
                                    ? 'Identifiant d’un module système : il est utilisé par les droits d’accès et ne peut pas être modifié.'
                                    : 'Préfixe des permissions générées (ex. hebergement.sanctions.view).'
                            }
                        >
                            <Input
                                id="mod-slug"
                                maxLength={160}
                                placeholder="Ex. hebergement.sanctions"
                                className="font-mono"
                                readOnly={module?.is_system}
                                invalid={!!errors.slug}
                                {...register('slug', {
                                    onChange: () => setSlugAuto(false),
                                })}
                            />
                        </Field>
                        <Field id="mod-description" label="Description" error={errors.description?.message}>
                            <Textarea
                                id="mod-description"
                                rows={3}
                                maxLength={1000}
                                placeholder="Ex. Suivi des avertissements et sanctions des résidents"
                                invalid={!!errors.description}
                                {...register('description')}
                            />
                        </Field>
                    </>
                )}

                {etape === 1 && (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field id="mod-route" label="Nom de la route" error={errors.route_name?.message} hint="Page ouverte depuis le menu.">
                                <Input
                                    id="mod-route"
                                    maxLength={180}
                                    placeholder="Ex. admin.hebergement.index"
                                    className="font-mono"
                                    invalid={!!errors.route_name}
                                    {...register('route_name')}
                                />
                            </Field>
                            <Field
                                id="mod-position"
                                label="Position"
                                required
                                error={errors.position?.message}
                                hint="Ordre d’affichage parmi les modules du même parent."
                            >
                                <Input id="mod-position" inputMode="numeric" placeholder="Ex. 1" invalid={!!errors.position} {...register('position')} />
                            </Field>
                            <Field id="mod-menu" label="Clé du menu" error={errors.menu_key?.message}>
                                <Input id="mod-menu" maxLength={80} placeholder="Ex. hebergement" invalid={!!errors.menu_key} {...register('menu_key')} />
                            </Field>
                            <Field id="mod-submenu" label="Clé du sous-menu" error={errors.submenu_key?.message}>
                                <Input id="mod-submenu" maxLength={80} placeholder="Ex. vie-cite" invalid={!!errors.submenu_key} {...register('submenu_key')} />
                            </Field>
                        </div>
                        <Field id="mod-icon" label="Icône" error={errors.icon?.message} hint="Classe Font Awesome utilisée par les écrans Blade (facultative).">
                            <Input id="mod-icon" maxLength={100} placeholder="Ex. fa-solid fa-shield" invalid={!!errors.icon} {...register('icon')} />
                        </Field>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <ToggleCard
                                checked={v.is_active}
                                onChange={(valeur) =>
                                    setValue('is_active', valeur, {
                                        shouldDirty: true,
                                    })
                                }
                                label="Module actif"
                                icon={<Power />}
                                description="Inactif, il n’est plus accessible aux rôles non super-administrateurs."
                            />
                            <ToggleCard
                                checked={v.show_in_menu}
                                onChange={(valeur) =>
                                    setValue('show_in_menu', valeur, {
                                        shouldDirty: true,
                                    })
                                }
                                label="Afficher dans le menu"
                                icon={<Network />}
                                description="Lien visible dans la navigation de l’administration."
                            />
                        </div>
                    </>
                )}

                {etape === 2 && (
                    <fieldset className="space-y-3">
                        <legend className="flex w-full items-center justify-between gap-3 text-sm font-medium text-gray-800">
                            <span>
                                Actions et permissions à générer <span className="text-red-600">*</span>
                            </span>
                            <button
                                type="button"
                                className="cursor-pointer text-sm font-normal text-primary hover:underline"
                                onClick={() =>
                                    setValue('actions', v.actions.length === Object.keys(actions).length ? [] : Object.keys(actions), {
                                        shouldValidate: true,
                                        shouldDirty: true,
                                    })
                                }
                            >
                                {v.actions.length === Object.keys(actions).length ? 'Tout décocher' : 'Tout cocher'}
                            </button>
                        </legend>
                        <Controller
                            control={control}
                            name="actions"
                            render={({ field }) => (
                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    {Object.entries(actions).map(([cle, libelle]) => {
                                        const coche = field.value.includes(cle);
                                        const id = `mod-action-${cle}`;
                                        return (
                                            <label
                                                key={cle}
                                                htmlFor={id}
                                                className={cn(
                                                    'flex cursor-pointer items-start gap-2.5 rounded-brand border px-3 py-2.5 text-sm transition-colors',
                                                    coche
                                                        ? 'border-primary bg-soft text-gray-900'
                                                        : 'border-gray-200 bg-white text-muted hover:border-gray-300',
                                                )}
                                            >
                                                <Checkbox
                                                    id={id}
                                                    className="mt-0.5"
                                                    checked={coche}
                                                    onCheckedChange={(valeur) =>
                                                        field.onChange(valeur === true ? [...field.value, cle] : field.value.filter((a) => a !== cle))
                                                    }
                                                />
                                                <span className="min-w-0">
                                                    <span className="block">{libelle}</span>
                                                    <code className="block truncate text-xs text-muted">
                                                        {v.slug || '…'}.{cle}
                                                    </code>
                                                </span>
                                            </label>
                                        );
                                    })}
                                </div>
                            )}
                        />
                        {errors.actions && (
                            <p role="alert" className="text-xs font-medium text-red-600">
                                {errors.actions.message}
                            </p>
                        )}
                        {retirees.length > 0 ? (
                            <Note icone={<TriangleAlert />} ton="attention">
                                <p>
                                    Les permissions {retirees.map((a) => `« ${actions[a] ?? a} »`).join(', ')} seront supprimées et retirées de tous les rôles
                                    qui les possèdent.
                                </p>
                            </Note>
                        ) : (
                            <Note icone={<Info />}>
                                <p>
                                    Chaque action cochée crée une permission attribuée automatiquement au super-administrateur, puis aux autres rôles depuis
                                    Rôles et permissions.
                                </p>
                            </Note>
                        )}
                    </fieldset>
                )}

                {etape === 3 && (
                    <Recap
                        lignes={[
                            {
                                label: 'Module parent',
                                valeur: parent ? parent.name : 'Aucun : module principal',
                            },
                            {
                                label: 'Nom',
                                valeur: <span className="font-medium">{v.name.trim()}</span>,
                            },
                            {
                                label: 'Identifiant technique',
                                valeur: <code className="text-primary">{v.slug.trim()}</code>,
                            },
                            {
                                label: 'Description',
                                valeur: v.description.trim() || <span className="text-muted">Aucune</span>,
                            },
                            {
                                label: 'Route',
                                valeur: v.route_name.trim() ? <code>{v.route_name.trim()}</code> : vide,
                            },
                            {
                                label: 'Menu · sous-menu',
                                valeur: [v.menu_key.trim(), v.submenu_key.trim()].filter(Boolean).join(' · ') || vide,
                            },
                            { label: 'Position', valeur: v.position },
                            {
                                label: 'Statut',
                                valeur: `${v.is_active ? 'Actif' : 'Inactif'} · ${v.show_in_menu ? 'affiché dans le menu' : 'masqué du menu'}`,
                            },
                            {
                                label: `Actions (${v.actions.length})`,
                                valeur: (
                                    <span className="flex flex-wrap gap-1 sm:justify-end">
                                        {v.actions.map((a) => (
                                            <span
                                                key={a}
                                                className={cn(
                                                    'rounded border px-2 py-0.5 text-xs',
                                                    ACTION_COLORS[a] ?? 'border-gray-200 bg-gray-50 text-gray-700',
                                                )}
                                            >
                                                {actions[a] ?? a}
                                            </span>
                                        ))}
                                    </span>
                                ),
                            },
                            ...(retirees.length > 0
                                ? [
                                      {
                                          label: 'Permissions supprimées',
                                          valeur: <span className="text-red-700">{retirees.map((a) => actions[a] ?? a).join(', ')}</span>,
                                      },
                                  ]
                                : []),
                        ]}
                    />
                )}
            </DialogBody>
            <PiedAssistant
                etape={etape}
                derniere={derniere}
                envoi={isSubmitting}
                desactive={!url}
                onAnnuler={onClose}
                onPrecedent={() => aller(etape - 1)}
                libelle={module ? 'Enregistrer les modifications' : 'Créer le module'}
            />
        </form>
    );
}
