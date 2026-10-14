import { router } from '@inertiajs/react';
import {
    Building,
    Building2,
    ChevronDown,
    ChevronRight,
    LayoutGrid,
    Map as MapIcon,
    PackageCheck,
    Plus,
    Rows3,
    Search,
    X,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { toast } from 'sonner';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { ItemActions, type ParcActions } from '@/components/parc-immobilier/actions';
import { ChambreDialog } from '@/components/parc-immobilier/chambre-dialog';
import { DetailsSheet } from '@/components/parc-immobilier/details-sheet';
import { EntityCard } from '@/components/parc-immobilier/entity-card';
import { messageErreur, parcHttp } from '@/components/parc-immobilier/http';
import { ETATS, EtatBadge, estActif, nombre, pluriel, STATUTS, StatutBadge, TYPE_ICONS, TYPE_LABELS } from '@/components/parc-immobilier/labels';
import { OccupationBar, tauxOccupation } from '@/components/parc-immobilier/occupation-bar';
import { RoomPlan } from '@/components/parc-immobilier/room-plan';
import { StatCard } from '@/components/ui/stat-card';
import type {
    BatimentItem,
    ChambreItem,
    CiteItem,
    ContexteElement,
    EtatChambre,
    Niveau,
    ParcItem,
    ParcProps,
} from '@/components/parc-immobilier/types';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Vue = 'cartes' | 'tableau' | 'plan';

const RECHARGER = ['items', 'plan', 'stats', 'contexte'];

const MESSAGES_DESACTIVATION: Record<Niveau, (nom: string) => string> = {
    cite: (nom) =>
        `La cité « ${nom} » sera désactivée, ainsi que tous ses bâtiments et toutes ses chambres. Elle ne pourra plus être utilisée tant qu'elle ne sera pas réactivée.`,
    batiment: (nom) =>
        `Le bâtiment « ${nom} » sera désactivé, ainsi que toutes ses chambres. Il ne pourra plus être utilisé tant qu'il ne sera pas réactivé.`,
    chambre: (nom) =>
        `La chambre « ${nom} » sera désactivée et ne sera plus proposée pour une nouvelle affectation tant qu'elle ne sera pas réactivée.`,
};

/** Vue mémorisée par navigateur (structure : cartes/tableau ; chambres : plan/tableau). */
function useVue(niveau: Niveau): [Vue, (vue: Vue) => void] {
    const cle = `parc-immobilier.vue.${niveau === 'chambre' ? 'chambres' : 'structure'}`;
    const defaut: Vue = niveau === 'chambre' ? 'plan' : 'cartes';
    const lire = (): Vue => {
        try {
            const valeur = window.localStorage.getItem(cle) as Vue | null;
            return valeur && (niveau === 'chambre' ? ['plan', 'tableau'] : ['cartes', 'tableau']).includes(valeur) ? valeur : defaut;
        } catch {
            return defaut;
        }
    };
    const [vue, setVue] = useState<Vue>(lire);

    // eslint-disable-next-line react-hooks/exhaustive-deps
    useEffect(() => setVue(lire()), [cle]);

    return [
        vue,
        (suivante) => {
            setVue(suivante);
            try {
                window.localStorage.setItem(cle, suivante);
            } catch {
                // Stockage indisponible (navigation privée) : la vue n'est simplement pas mémorisée.
            }
        },
    ];
}

export default function ParcImmobilierIndex({ niveau, items, plan, filters, statutsCite, statutsBatiment, contexte, stats, typesChambre, urls }: ParcProps) {
    const [search, setSearch] = useState(filters.q);
    const [vue, setVue] = useVue(niveau);
    const [details, setDetails] = useState<ParcItem | null>(null);
    const [aModifier, setAModifier] = useState<ChambreItem | null>(null);
    const [aDesactiver, setADesactiver] = useState<ParcItem | null>(null);
    const [aSupprimer, setASupprimer] = useState<ParcItem | null>(null);
    const premierRendu = useRef(true);

    // Paramètres qui situent la page (cité ou bâtiment ouvert), conservés par la recherche et les filtres.
    const position: Record<string, string> =
        niveau === 'chambre' && contexte.batiment
            ? { batiment: contexte.batiment.id }
            : niveau === 'batiment' && contexte.cite
              ? { cite: contexte.cite.id }
              : {};

    useEffect(() => setSearch(filters.q), [filters.q, niveau]);

    // Recherche côté serveur 350 ms après la dernière frappe (retour en page 1).
    useEffect(() => {
        if (premierRendu.current) {
            premierRendu.current = false;
            return;
        }
        if (search === filters.q) return;
        const timer = window.setTimeout(() => appliquer({ q: search }), 350);
        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    function appliquer(changes: Partial<ParcProps['filters']>) {
        const next = { ...filters, q: search, ...changes };
        router.get(
            window.location.pathname,
            { ...position, ...Object.fromEntries(Object.entries(next).filter(([, value]) => value !== '')) },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const naviguer = (params: Record<string, string>) => router.get(window.location.pathname, params);

    async function envoyerStatut(item: ParcItem) {
        if (!item.urls.status) return;
        try {
            const { data } = await parcHttp.post(item.urls.status);
            toast.success(data.status === 'desactive' ? `${TYPE_LABELS[item.type]} désactivé(e).` : `${TYPE_LABELS[item.type]} activé(e).`);
            router.reload({ only: RECHARGER });
        } catch (error) {
            toast.error(messageErreur(error, 'La mise à jour du statut a échoué.'));
        }
    }

    const actions: ParcActions = {
        voir: setDetails,
        ouvrir: (item) => {
            if (item.type === 'cite') naviguer({ cite: item.id });
            else if (item.type === 'batiment') naviguer({ batiment: item.id });
            else setDetails(item);
        },
        modifier: urls.updateChambre ? (item) => item.type === 'chambre' && setAModifier(item) : null,
        basculer: (item) => (estActif(item) ? setADesactiver(item) : envoyerStatut(item)),
        supprimer: setASupprimer,
    };

    const filtresActifs = filters.q !== '' || filters.etat !== '' || filters.statut !== '';

    const barreOutils = (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div className="w-full sm:max-w-xs">
                <Input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={niveau === 'cite' ? 'Rechercher une cité…' : niveau === 'batiment' ? 'Rechercher un bâtiment…' : 'Numéro de chambre…'}
                    aria-label="Rechercher"
                    icon={<Search />}
                    className="h-10"
                />
            </div>
            {niveau === 'cite' && (
                <Select value={filters.statut || 'all'} onValueChange={(value) => appliquer({ statut: value === 'all' ? '' : value })}>
                    <SelectTrigger aria-label="Filtrer les cités par statut" className="h-10 w-full sm:w-52">
                        <span className="flex items-center gap-2">
                            <Building2 size={16} strokeWidth={1.25} className="text-primary" aria-hidden />
                            <SelectValue />
                        </span>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Toutes les cités</SelectItem>
                        {statutsCite.map((statut) => (
                            <SelectItem key={statut} value={statut}>
                                {`Cités : ${(STATUTS[statut] ?? statut).toLowerCase()}`}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}
            {niveau === 'batiment' && (
                <Select value={filters.statut || 'all'} onValueChange={(value) => appliquer({ statut: value === 'all' ? '' : value })}>
                    <SelectTrigger aria-label="Filtrer les bâtiments par statut" className="h-10 w-full sm:w-56">
                        <span className="flex items-center gap-2">
                            <Building size={16} strokeWidth={1.25} className="text-primary" aria-hidden />
                            <SelectValue />
                        </span>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Tous les bâtiments</SelectItem>
                        {statutsBatiment.map((statut) => (
                            <SelectItem key={statut} value={statut}>
                                {`Bâtiments : ${(STATUTS[statut] ?? statut).toLowerCase()}`}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}
            {niveau === 'chambre' && (
                <Select value={filters.etat || 'all'} onValueChange={(value) => appliquer({ etat: value === 'all' ? '' : (value as EtatChambre) })}>
                    <SelectTrigger aria-label="Filtrer par état" className="h-10 w-full sm:w-48">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Tous les états</SelectItem>
                        {(Object.keys(ETATS) as EtatChambre[]).map((etat) => (
                            <SelectItem key={etat} value={etat}>
                                {ETATS[etat].label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}
            {filtresActifs && (
                <Button
                    variant="ghost"
                    size="sm"
                    className="h-10"
                    onClick={() => {
                        setSearch('');
                        router.get(window.location.pathname, position, { preserveState: true, replace: true });
                    }}
                >
                    <X className="size-4" aria-hidden />
                    Réinitialiser
                </Button>
            )}
            <ChoixVue
                valeur={vue}
                onChange={setVue}
                options={
                    niveau === 'chambre'
                        ? [
                              { value: 'plan', label: 'Plan', icon: LayoutGrid },
                              { value: 'tableau', label: 'Tableau', icon: Rows3 },
                          ]
                        : [
                              { value: 'cartes', label: 'Cartes', icon: LayoutGrid },
                              { value: 'tableau', label: 'Tableau', icon: Rows3 },
                          ]
                }
            />
        </div>
    );

    return (
        <AdminLayout title="Parc immobilier">
            <div className="mb-6 flex flex-col gap-4">
                <div className="flex flex-col gap-1">
                    <h1 className="page-title text-xl font-medium text-gray-900">Parc immobilier</h1>
                    <p className="text-sm text-muted">Cités, bâtiments et chambres du CROU de Bondoukou.</p>
                </div>
                <Separator />
                <div className="flex flex-wrap items-center justify-end gap-2">
                    {urls.equipements && (
                        <LienBouton href={urls.equipements} icon={PackageCheck}>
                            Équipements affectés
                        </LienBouton>
                    )}
                    {urls.map && (
                        <LienBouton href={urls.map} icon={MapIcon}>
                            Vue carte
                        </LienBouton>
                    )}
                    {Object.keys(urls.create).length > 0 && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button size="sm" className="h-10 px-4">
                                    <Plus className="size-4" aria-hidden />
                                    Ajouter
                                    <ChevronDown className="size-4 opacity-80" aria-hidden />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-52">
                                {(['cite', 'batiment', 'chambre'] as const).map((type) => {
                                    const href = urls.create[type];
                                    const Icon = TYPE_ICONS[type];
                                    return (
                                        href && (
                                            <DropdownMenuItem key={type} asChild className="cursor-pointer gap-2.5 py-2">
                                                <a href={href}>
                                                    <Icon {...ICON} className="text-primary" aria-hidden />
                                                    {type === 'cite' ? 'Nouvelle cité' : type === 'batiment' ? 'Nouveau bâtiment' : 'Nouvelle chambre'}
                                                </a>
                                            </DropdownMenuItem>
                                        )
                                    );
                                })}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            </div>

            <Indicateurs stats={stats} />

            <section className="mt-6 rounded-brand border bg-white">
                {niveau !== 'cite' && (
                    <>
                        <Parcours niveau={niveau} contexte={contexte} naviguer={naviguer} />
                        <Contexte niveau={niveau} contexte={contexte} />
                    </>
                )}
                <div className={cn('px-4 py-3', niveau !== 'cite' && 'border-t')}>{barreOutils}</div>
            </section>

            <div className="mt-4">
                {vue === 'plan' && niveau === 'chambre' ? (
                    <div className="rounded-brand border bg-white">
                        <RoomPlan chambres={plan} etat={filters.etat} search={search} onSelect={setDetails} />
                    </div>
                ) : vue === 'cartes' && niveau !== 'chambre' ? (
                    <Cartes items={items as Paginated<CiteItem | BatimentItem>} actions={actions} vide={messageVide(niveau, filtresActifs)} />
                ) : (
                    <DataTable
                        columns={colonnes(niveau, actions)}
                        data={items}
                        rowKey={(item) => item.id}
                        emptyMessage={messageVide(niveau, filtresActifs)}
                    />
                )}
            </div>

            <DetailsSheet item={details} onClose={() => setDetails(null)} actions={actions} />

            {urls.updateChambre && (
                <ChambreDialog chambre={aModifier} onClose={() => setAModifier(null)} url={urls.updateChambre} typesChambre={typesChambre} />
            )}

            <Confirmation
                ouvert={aDesactiver !== null}
                onClose={() => setADesactiver(null)}
                titre={`Désactiver ${aDesactiver?.type === 'chambre' ? 'cette chambre' : aDesactiver?.type === 'batiment' ? 'ce bâtiment' : 'cette cité'} ?`}
                description={aDesactiver ? MESSAGES_DESACTIVATION[aDesactiver.type](aDesactiver.name) : ''}
                action="Désactiver"
                onConfirm={() => {
                    if (aDesactiver) envoyerStatut(aDesactiver);
                    setADesactiver(null);
                }}
            />

            <Confirmation
                ouvert={aSupprimer !== null}
                onClose={() => setASupprimer(null)}
                titre={`Supprimer ${aSupprimer?.type === 'chambre' ? `la chambre ${aSupprimer.name}` : `« ${aSupprimer?.name ?? ''} »`} ?`}
                description="Cette suppression est définitive. Elle est refusée tant que l'élément contient des bâtiments, des chambres ou des occupants."
                action="Supprimer"
                onConfirm={() => {
                    if (aSupprimer?.urls.destroy) {
                        router.delete(aSupprimer.urls.destroy, { preserveScroll: true, onFinish: () => setASupprimer(null) });
                    }
                }}
            />
        </AdminLayout>
    );
}

function messageVide(niveau: Niveau, filtres: boolean): string {
    if (filtres) return 'Aucun élément ne correspond à ces critères.';
    return niveau === 'cite' ? 'Aucune cité enregistrée.' : niveau === 'batiment' ? 'Aucun bâtiment dans cette cité.' : 'Aucune chambre dans ce bâtiment.';
}

function LienBouton({ href, icon: Icon, target, children }: { href: string; icon: LucideIcon; target?: string; children: ReactNode }) {
    return (
        <a
            href={href}
            target={target}
            rel={target ? 'noopener' : undefined}
            className="inline-flex h-10 items-center gap-2 rounded-brand border border-primary bg-white px-4 text-sm font-semibold text-primary transition-colors hover:bg-soft focus-visible:bg-soft focus-visible:outline-none"
        >
            <Icon size={16} strokeWidth={1.5} aria-hidden />
            {children}
        </a>
    );
}

/** Indicateurs du parc entier (toujours globaux, quel que soit le niveau affiché). */
function Indicateurs({ stats }: { stats: ParcProps['stats'] }) {
    const taux = tauxOccupation(stats);

    return (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard tone="blue" label="Cités" value={nombre.format(stats.cites)} />
            <StatCard tone="plum" label="Bâtiments" value={nombre.format(stats.batiments)} extra="tous statuts" />
            <StatCard tone="amber" label="Chambres" value={nombre.format(stats.chambres)} extra={`${nombre.format(stats.disponibles)} disponibles`} />
            <StatCard
                tone="green"
                label="Taux d’occupation"
                value={`${taux} %`}
                extra={`${nombre.format(stats.occupants)} / ${nombre.format(stats.places)} places`}
            >
                <OccupationBar resume={{ ...stats, categories: {} }} />
            </StatCard>
        </div>
    );
}

/** Fil de navigation dans le parc : Toutes les cités › Cité › Bâtiment. */
function Parcours({ niveau, contexte, naviguer }: { niveau: Niveau; contexte: ParcProps['contexte']; naviguer: (params: Record<string, string>) => void }) {
    const etapes: { label: string; icon: LucideIcon; params: Record<string, string> | null }[] = [
        { label: 'Toutes les cités', icon: Building2, params: niveau === 'cite' ? null : {} },
    ];
    if (contexte.cite) {
        etapes.push({ label: contexte.cite.name, icon: Building2, params: niveau === 'batiment' ? null : { cite: contexte.cite.id } });
    }
    if (contexte.batiment) {
        etapes.push({ label: contexte.batiment.name, icon: Building, params: null });
    }

    return (
        <nav aria-label="Position dans le parc" className="border-b px-4 py-3">
            <ol className="flex flex-wrap items-center gap-1 text-sm">
                {etapes.map((etape, index) => (
                    <li key={`${etape.label}-${index}`} className="flex items-center gap-1">
                        {index > 0 && <ChevronRight size={16} strokeWidth={1.25} className="text-gray-400" aria-hidden />}
                        {etape.params ? (
                            <button
                                type="button"
                                onClick={() => naviguer(etape.params as Record<string, string>)}
                                className="inline-flex cursor-pointer items-center gap-1.5 rounded-brand px-2.5 py-1.5 text-muted transition-colors hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                            >
                                <etape.icon size={16} strokeWidth={1.25} aria-hidden />
                                {etape.label}
                            </button>
                        ) : (
                            <span aria-current="location" className="inline-flex items-center gap-1.5 rounded-brand bg-soft px-2.5 py-1.5 font-medium text-primary">
                                <etape.icon size={16} strokeWidth={1.25} aria-hidden />
                                {etape.label}
                            </span>
                        )}
                    </li>
                ))}
            </ol>
        </nav>
    );
}

/** Résumé de la cité ou du bâtiment ouvert. */
function Contexte({ niveau, contexte }: { niveau: Niveau; contexte: ParcProps['contexte'] }) {
    const element: ContexteElement | null = niveau === 'chambre' ? contexte.batiment : niveau === 'batiment' ? contexte.cite : null;

    if (!element?.resume) return null;

    const resume = element.resume;
    const Icon = TYPE_ICONS[niveau === 'chambre' ? 'batiment' : 'cite'];

    return (
        <div className="grid gap-4 px-4 py-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] lg:items-center">
            <div className="flex items-center gap-3">
                <span className="flex size-11 shrink-0 items-center justify-center rounded-brand bg-primary text-white" aria-hidden>
                    <Icon {...ICON} />
                </span>
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="truncate text-base font-medium text-gray-900">{element.name}</h2>
                        <StatutBadge statut={element.statut} />
                    </div>
                    <p className="text-sm text-muted">
                        {pluriel(resume.chambres, 'chambre')} · {nombre.format(resume.occupants)}/{nombre.format(resume.places)} places occupées
                    </p>
                </div>
            </div>
            <div className="space-y-2">
                <div className="flex items-baseline justify-between text-xs">
                    <span className="text-muted">Occupation des places</span>
                    <span className="font-medium text-gray-900">{tauxOccupation(resume)} %</span>
                </div>
                <OccupationBar resume={resume} legend />
            </div>
        </div>
    );
}

function ChoixVue({ valeur, onChange, options }: { valeur: Vue; onChange: (vue: Vue) => void; options: { value: Vue; label: string; icon: LucideIcon }[] }) {
    return (
        <div role="radiogroup" aria-label="Affichage" className="inline-flex h-10 shrink-0 rounded-brand border bg-white p-0.5 sm:ml-auto">
            {options.map((option) => {
                const actif = valeur === option.value;
                return (
                    <button
                        key={option.value}
                        type="button"
                        role="radio"
                        aria-checked={actif}
                        onClick={() => onChange(option.value)}
                        className={cn(
                            'inline-flex cursor-pointer items-center gap-1.5 rounded-[6px] px-3 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                            actif ? 'bg-primary text-white' : 'text-muted hover:bg-soft hover:text-primary',
                        )}
                    >
                        <option.icon size={16} strokeWidth={1.5} aria-hidden />
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}

function Cartes({ items, actions, vide }: { items: Paginated<CiteItem | BatimentItem>; actions: ParcActions; vide: string }) {
    if (items.data.length === 0) {
        return <p className="rounded-brand border border-dashed bg-white py-16 text-center text-sm text-muted">{vide}</p>;
    }

    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-3">
                {items.data.map((item) => (
                    <EntityCard key={item.id} item={item} actions={actions} />
                ))}
            </div>
            {items.last_page > 1 && (
                <div className="overflow-hidden rounded-brand border bg-white [&>div]:border-t-0">
                    <DataTablePagination data={items} />
                </div>
            )}
        </div>
    );
}

function NomCellule({ item, sousTitre, onClick }: { item: ParcItem; sousTitre: ReactNode; onClick: () => void }) {
    const Icon = TYPE_ICONS[item.type];
    return (
        <div className="flex items-center gap-3">
            <span
                className={cn('flex size-9 shrink-0 items-center justify-center rounded-brand', estActif(item) ? 'bg-soft text-primary' : 'bg-gray-100 text-gray-400')}
                aria-hidden
            >
                <Icon size={18} strokeWidth={1.25} />
            </span>
            <div className="min-w-0">
                <button type="button" onClick={onClick} className="block max-w-full cursor-pointer truncate text-left font-medium text-gray-900 hover:text-primary">
                    {item.type === 'chambre' ? `Chambre ${item.name}` : item.name}
                </button>
                <p className="truncate text-xs text-muted">{sousTitre || '—'}</p>
            </div>
        </div>
    );
}

function CelluleOccupation({ item }: { item: CiteItem | BatimentItem }) {
    return (
        <div className="w-36 space-y-1.5">
            <div className="flex justify-between text-xs">
                <span className="text-muted">
                    {nombre.format(item.resume.occupants)}/{nombre.format(item.resume.places)} places
                </span>
                <span className="font-medium text-gray-900">{tauxOccupation(item.resume)} %</span>
            </div>
            <OccupationBar resume={item.resume} />
        </div>
    );
}

function colonnes(niveau: Niveau, actions: ParcActions): DataTableColumn<ParcItem>[] {
    const statut: DataTableColumn<ParcItem> = { key: 'statut', header: 'Statut', cell: (item) => <StatutBadge statut={item.statut} /> };
    const actionsCol: DataTableColumn<ParcItem> = {
        key: 'actions',
        header: <span className="sr-only">Actions</span>,
        headClassName: 'w-px text-right',
        className: 'text-right',
        cell: (item) => <ItemActions item={item} actions={actions} />,
    };

    if (niveau === 'chambre') {
        const c = (cell: (chambre: ChambreItem) => ReactNode) => (item: ParcItem) => cell(item as ChambreItem);
        return [
            { key: 'nom', header: 'Chambre', cell: c((ch) => <NomCellule item={ch} sousTitre={ch.type_chambre} onClick={() => actions.voir(ch)} />) },
            { key: 'niveau', header: 'Niveau', cell: c((ch) => ch.niveau ?? <span className="text-muted">—</span>) },
            { key: 'occupation', header: 'Occupation', cell: c((ch) => ch.occupation ?? <span className="text-muted">—</span>) },
            {
                key: 'occupants',
                header: 'Occupants',
                cell: c((ch) => (
                    <span className="text-gray-900">
                        {ch.etudiants.length}
                        <span className="text-muted">/{ch.capacite}</span>
                    </span>
                )),
            },
            { key: 'etat', header: 'État', cell: c((ch) => <EtatBadge etat={ch.etat} />) },
            statut,
            actionsCol,
        ];
    }

    const s = (cell: (item: CiteItem | BatimentItem) => ReactNode) => (item: ParcItem) => cell(item as CiteItem | BatimentItem);

    return [
        {
            key: 'nom',
            header: niveau === 'cite' ? 'Cité' : 'Bâtiment',
            cell: s((item) => (
                <NomCellule
                    item={item}
                    sousTitre={[item.type === 'cite' ? item.code : null, item.occupation].filter(Boolean).join(' · ')}
                    onClick={() => actions.ouvrir(item)}
                />
            )),
        },
        {
            key: 'responsable',
            header: niveau === 'cite' ? 'Responsable' : 'Concierge',
            cell: s((item) => item.responsable ?? <span className="text-muted">Aucun</span>),
            className: 'max-w-56 truncate',
        },
        niveau === 'cite'
            ? { key: 'batiments', header: 'Bâtiments', cell: s((item) => nombre.format((item as CiteItem).batiments)) }
            : { key: 'niveaux', header: 'Niveaux', cell: s((item) => (item as BatimentItem).niveaux || <span className="text-muted">—</span>) },
        { key: 'chambres', header: 'Chambres', cell: s((item) => nombre.format(item.resume.chambres)) },
        { key: 'occupation', header: 'Occupation', cell: s((item) => <CelluleOccupation item={item} />) },
        statut,
        actionsCol,
    ];
}

function Confirmation({
    ouvert,
    onClose,
    titre,
    description,
    action,
    onConfirm,
}: {
    ouvert: boolean;
    onClose: () => void;
    titre: string;
    description: string;
    action: string;
    onConfirm: () => void;
}) {
    return (
        <AlertDialog open={ouvert} onOpenChange={(open) => !open && onClose()}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{titre}</AlertDialogTitle>
                    <AlertDialogDescription>{description}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel asChild>
                        <Button variant="outline" size="sm" className="h-10">
                            Annuler
                        </Button>
                    </AlertDialogCancel>
                    <AlertDialogAction asChild>
                        <Button
                            size="sm"
                            className="h-10 bg-red-600 hover:bg-red-700"
                            onClick={(event) => {
                                event.preventDefault();
                                onConfirm();
                            }}
                        >
                            {action}
                        </Button>
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
