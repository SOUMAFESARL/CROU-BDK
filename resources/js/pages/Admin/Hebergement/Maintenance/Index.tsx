import { router } from '@inertiajs/react';
import { BarChart3, Eye, Pencil, Plus, Search, UserPlus, Wrench, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { SimpleTable } from '@/components/data-table/simple-table';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { MaintenancierDialog } from '@/components/maintenance/maintenancier-dialog';
import { OrdreDialog } from '@/components/maintenance/ordre-dialog';
import { fcfa, PRIORITES, PrioriteBadge, StatutOtBadge, type Maintenancier } from '@/components/maintenance/shared';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { StatCard } from '@/components/ui/stat-card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { dateHeure, type Structure } from '@/components/vie-residentielle/shared';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Ordre = {
    id: string;
    numero: string;
    titre: string | null;
    residence: string | null;
    batiment: string | null;
    chambre: string | null;
    demandeur: string | null;
    telephone: string | null;
    categorie: string;
    categorie_label: string;
    description: string | null;
    priorite: string;
    priorite_label: string;
    statut: string;
    statut_label: string;
    technicien: string | null;
    date_creation: string | null;
    date_resolution: string | null;
    urls: { show: string | null; edit: string | null };
};

type StatCite = {
    id: string;
    name: string;
    total: number;
    termine: number;
    urgent: number;
    cout_total: number;
    temps_moyen: number;
    taux_resolution: number;
    principale_cause: string | null;
};

type Filtres = { search: string; cite_id: string; priorite: string; categorie: string };

type Props = {
    ordres: Paginated<Ordre>;
    ordresTermines: Paginated<Ordre>;
    stats: {
        total: number;
        en_attente: number;
        en_cours: number;
        en_attente_pieces: number;
        termine: number;
        urgent: number;
        cout_total: number;
        temps_moyen: number;
        satisfaction: number;
        satisfaction_count: number;
    };
    statsParCite: StatCite[];
    filters: Filtres;
    categories: { value: string; label: string }[];
    specialites: string[];
    maintenanciers: Maintenancier[];
    structure: Structure;
    urls: { index: string; store: string | null; storeMaintenancier: string | null; rapport: string | null };
};

const TOUS = 'tous';

/** Maintenance et suivi des ordres de travail (remplace `vie_residentielle/maintenance/index.blade.php`). */
export default function MaintenanceIndex({ ordres, ordresTermines, stats, statsParCite, filters, categories, specialites, maintenanciers, structure, urls }: Props) {
    const [nouvelOrdre, setNouvelOrdre] = useState(false);
    const [nouveauMaintenancier, setNouveauMaintenancier] = useState(false);
    const [recherche, setRecherche] = useState(filters.search);
    const ouverts = stats.en_attente + stats.en_cours + stats.en_attente_pieces;
    const tauxResolution = stats.total > 0 ? Math.round((stats.termine / stats.total) * 100) : 0;
    // Onglet ouvert d'office sur les terminés quand on pagine cette liste.
    const ongletInitial = typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('termines_page') ? 'termines' : 'ouverts';

    const filtrer = (suivants: Partial<Filtres>) => {
        const valeurs = { ...filters, ...suivants };
        router.get(urls.index, Object.fromEntries(Object.entries(valeurs).filter(([, v]) => v && v !== TOUS)), { preserveState: true, preserveScroll: true, replace: true });
    };

    // Recherche appliquée après une courte pause de frappe.
    useEffect(() => {
        if (recherche === filters.search) return;
        const minuterie = window.setTimeout(() => filtrer({ search: recherche.trim() }), 400);
        return () => window.clearTimeout(minuterie);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [recherche]);

    const filtreActif = Boolean(filters.search || filters.cite_id || filters.priorite || filters.categorie);

    const colonnes = (terminees: boolean): DataTableColumn<Ordre>[] => [
        {
            key: 'numero',
            header: 'Ordre',
            cell: (o) => (
                <div className="min-w-36">
                    <p className="font-medium whitespace-nowrap text-gray-900">{o.numero}</p>
                    {o.titre && <p className="line-clamp-1 text-xs text-muted">{o.titre}</p>}
                </div>
            ),
        },
        {
            key: 'lieu',
            header: 'Emplacement',
            cell: (o) => (
                <div className="min-w-32">
                    <p>{o.residence ?? 'Toute la cité'}</p>
                    <p className="text-xs text-muted">{[o.batiment, o.chambre ? `Ch. ${o.chambre}` : null].filter(Boolean).join(' · ') || 'Parties communes'}</p>
                </div>
            ),
        },
        {
            key: 'demandeur',
            header: 'Demandeur',
            cell: (o) =>
                o.demandeur ? (
                    <div className="min-w-32">
                        <p>{o.demandeur}</p>
                        {o.telephone && <p className="text-xs text-muted">{o.telephone}</p>}
                    </div>
                ) : (
                    <span className="text-muted">Administration</span>
                ),
        },
        {
            key: 'categorie',
            header: 'Type',
            cell: (o) => (
                <div className="max-w-56">
                    <p>{o.categorie_label}</p>
                    {o.description && <p className="line-clamp-1 text-xs text-muted">{o.description}</p>}
                </div>
            ),
        },
        { key: 'priorite', header: 'Priorité', cell: (o) => <PrioriteBadge priorite={o.priorite} label={o.priorite_label} /> },
        { key: 'statut', header: 'Statut', cell: (o) => <StatutOtBadge statut={o.statut} label={o.statut_label} /> },
        {
            key: 'date',
            header: terminees ? 'Terminé le' : 'Créé le',
            className: 'whitespace-nowrap',
            cell: (o) => dateHeure(terminees ? o.date_resolution : o.date_creation),
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (o) => (
                <RowActions>
                    {o.urls.show && <RowAction label="Voir la fiche" icon={Eye} href={o.urls.show} tone="view" />}
                    {o.urls.edit && <RowAction label="Modifier" icon={Pencil} href={o.urls.edit} tone="edit" />}
                </RowActions>
            ),
        },
    ];

    const colonnesCites: DataTableColumn<StatCite>[] = [
        { key: 'name', header: 'Résidence', cell: (s) => <span className="font-medium text-gray-900">{s.name}</span> },
        { key: 'total', header: 'OT créés', className: 'text-right tabular-nums', headClassName: 'text-right', cell: (s) => nombre(s.total) },
        { key: 'termine', header: 'OT résolus', className: 'text-right tabular-nums', headClassName: 'text-right', cell: (s) => nombre(s.termine) },
        {
            key: 'taux',
            header: 'Résolution',
            cell: (s) => (
                <div className="flex min-w-32 items-center gap-2">
                    <Progress value={s.taux_resolution} tone={s.taux_resolution >= 80 ? 'primary' : s.taux_resolution >= 60 ? 'accent' : 'danger'} className="w-16" />
                    <span className="text-xs font-medium tabular-nums">{s.taux_resolution} %</span>
                </div>
            ),
        },
        { key: 'temps', header: 'Temps moyen', className: 'tabular-nums whitespace-nowrap', cell: (s) => (s.temps_moyen > 0 ? `${nombre(s.temps_moyen, 1)} j` : '—') },
        { key: 'cout', header: 'Coût', className: 'text-right tabular-nums whitespace-nowrap', headClassName: 'text-right', cell: (s) => fcfa(s.cout_total) },
        { key: 'urgent', header: 'Urgents', cell: (s) => <Badge variant={s.urgent > 0 ? 'danger' : 'success'}>{nombre(s.urgent)}</Badge> },
        { key: 'cause', header: 'Cause principale', cell: (s) => s.principale_cause ?? <span className="text-muted">—</span> },
    ];

    const meilleure = statsParCite.filter((s) => s.total > 0).sort((a, b) => b.taux_resolution - a.taux_resolution)[0];

    const barreFiltres = (
        <>
            <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <Input
                    value={recherche}
                    onChange={(event) => setRecherche(event.target.value)}
                    placeholder="N° d’OT, titre, description…"
                    aria-label="Rechercher un ordre de travail"
                    icon={<Search />}
                    className="h-10"
                />
                <Select value={filters.cite_id || TOUS} onValueChange={(valeur) => filtrer({ cite_id: valeur })}>
                    <SelectTrigger className="h-10" aria-label="Filtrer par résidence">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={TOUS}>Toutes les résidences</SelectItem>
                        {structure.cites.map((cite) => (
                            <SelectItem key={cite.id} value={cite.id}>
                                {cite.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Select value={filters.priorite || TOUS} onValueChange={(valeur) => filtrer({ priorite: valeur })}>
                    <SelectTrigger className="h-10" aria-label="Filtrer par priorité">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={TOUS}>Toutes les priorités</SelectItem>
                        {PRIORITES.map((p) => (
                            <SelectItem key={p.value} value={p.value}>
                                {p.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Select value={filters.categorie || TOUS} onValueChange={(valeur) => filtrer({ categorie: valeur })}>
                    <SelectTrigger className="h-10" aria-label="Filtrer par type d’intervention">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={TOUS}>Tous les types</SelectItem>
                        {categories.map((c) => (
                            <SelectItem key={c.value} value={c.value}>
                                {c.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            {filtreActif && (
                <Button
                    variant="ghost"
                    size="sm"
                    className="h-10"
                    onClick={() => {
                        setRecherche('');
                        router.get(urls.index, {}, { preserveScroll: true });
                    }}
                >
                    <X className="size-4" aria-hidden /> Réinitialiser
                </Button>
            )}
        </>
    );

    const vide = (message: string) => (
        <span className="flex flex-col items-center gap-2">
            <Wrench {...ICON} aria-hidden />
            {filtreActif ? 'Aucun ordre de travail ne correspond à ces filtres.' : message}
        </span>
    );

    return (
        <AdminLayout title="Maintenance">
            <PageHeader
                title="Maintenance et suivi"
                description="Ordres de travail (OT), interventions des maintenanciers et suivi par résidence. Un ordre terminé remet la chambre à disposition si aucun autre ordre n’est ouvert."
                actions={
                    (urls.rapport || urls.storeMaintenancier || urls.store) && (
                        <>
                            {urls.rapport && (
                                <a href={urls.rapport} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                                    <BarChart3 {...ICON} aria-hidden /> Rapport maintenance
                                </a>
                            )}
                            {urls.storeMaintenancier && (
                                <Button variant="secondary" size="sm" className="h-10 px-4" onClick={() => setNouveauMaintenancier(true)}>
                                    <UserPlus {...ICON} aria-hidden /> Nouveau maintenancier
                                </Button>
                            )}
                            {urls.store && (
                                <Button size="sm" className="h-10 px-4" onClick={() => setNouvelOrdre(true)}>
                                    <Plus {...ICON} aria-hidden /> Nouvel ordre de travail
                                </Button>
                            )}
                        </>
                    )
                }
            />

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                <StatCard tone="blue" label="OT ouverts" value={nombre(ouverts)} extra={`${nombre(stats.en_cours)} en cours`} />
                <StatCard tone="green" label="OT résolus" value={nombre(stats.termine)} extra={`${tauxResolution} % résolus`} />
                <StatCard tone="amber" label="Temps moyen de résolution" value={`${nombre(stats.temps_moyen, 1)} j`} extra="Objectif < 3 j" highlight={stats.temps_moyen > 3} />
                <StatCard tone="plum" label="OT critiques" value={nombre(stats.urgent)} extra={stats.urgent > 0 ? 'À traiter' : 'Aucun'} highlight={stats.urgent > 0} />
                <StatCard tone="blue" label="Coût de maintenance" value={fcfa(stats.cout_total)} extra="Coût réel cumulé" />
                <StatCard
                    tone="green"
                    label="Satisfaction des résidents"
                    value={stats.satisfaction_count > 0 ? `${nombre(stats.satisfaction)} / 5` : '—'}
                    extra={stats.satisfaction_count > 0 ? `${nombre(stats.satisfaction_count)} avis` : 'Aucun avis'}
                />
            </div>

            <section className="mt-6">
                <Tabs defaultValue={ongletInitial}>
                    <TabsList aria-label="Ordres de travail">
                        <TabsTrigger value="ouverts">
                            Non terminés <Badge variant="neutral">{nombre(ordres.total)}</Badge>
                        </TabsTrigger>
                        <TabsTrigger value="termines">
                            Terminés <Badge variant="neutral">{nombre(ordresTermines.total)}</Badge>
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent value="ouverts">
                        <DataTable columns={colonnes(false)} data={ordres} rowKey={(o) => o.id} toolbar={barreFiltres} emptyMessage={vide('Aucun ordre de travail en cours.')} />
                    </TabsContent>
                    <TabsContent value="termines">
                        <DataTable columns={colonnes(true)} data={ordresTermines} rowKey={(o) => o.id} toolbar={barreFiltres} emptyMessage={vide('Aucun ordre de travail terminé.')} />
                    </TabsContent>
                </Tabs>
            </section>

            <section className="mt-8">
                <div className="mb-3 flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                    <h2 className="text-base font-medium text-gray-900">Récapitulatif par résidence</h2>
                    <span className="text-sm text-muted">
                        Objectif de résolution : plus de 85 %
                        {meilleure && (
                            <>
                                {' '}
                                · meilleure résidence : <strong className="font-medium text-gray-900">{meilleure.name}</strong> ({meilleure.taux_resolution} %)
                            </>
                        )}
                    </span>
                </div>
                <div className="overflow-hidden rounded-brand border bg-white">
                    <SimpleTable columns={colonnesCites} rows={statsParCite} rowKey={(s) => s.id} emptyMessage="Aucune résidence." />
                    <div className="flex flex-wrap gap-x-6 gap-y-1 border-t bg-surface px-4 py-2.5 text-sm">
                        <span className="font-medium text-gray-900">Ensemble du CROU</span>
                        <span>
                            {nombre(stats.total)} OT · {nombre(stats.termine)} résolus ({tauxResolution} %)
                        </span>
                        <span>{fcfa(stats.cout_total)}</span>
                        <span className={stats.urgent > 0 ? 'font-medium text-red-700' : undefined}>{nombre(stats.urgent)} critique(s)</span>
                    </div>
                </div>
            </section>

            {urls.store && (
                <OrdreDialog open={nouvelOrdre} onClose={() => setNouvelOrdre(false)} url={urls.store} structure={structure} categories={categories} maintenanciers={maintenanciers} />
            )}
            {urls.storeMaintenancier && (
                <MaintenancierDialog open={nouveauMaintenancier} onClose={() => setNouveauMaintenancier(false)} url={urls.storeMaintenancier} specialites={specialites} cites={structure.cites} />
            )}
        </AdminLayout>
    );
}
