import { router } from '@inertiajs/react';
import { Eye, Gavel, PenLine, Scale, ShieldAlert, X } from 'lucide-react';
import { useState } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { SanctionDialog } from '@/components/discipline/sanction-dialog';
import { StatutSanctionDialog } from '@/components/discipline/statut-sanction-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet';
import { StatCard } from '@/components/ui/stat-card';
import { Chronologie, Lignes, type Structure } from '@/components/vie-residentielle/shared';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

export type Sanction = {
    id: string;
    numero: string;
    date_decision: string | null;
    portee: 'etudiant' | 'chambre' | 'batiment' | 'cite';
    cible: { titre: string; detail: string | null };
    type: string;
    type_label: string;
    motif: string;
    faits: string | null;
    date_debut: string | null;
    date_fin: string | null;
    statut: 'active' | 'levee' | 'annulee';
    historique: { id: string; action: string; details: string | null; ancien_statut: string | null; nouveau_statut: string | null; date: string | null }[];
    url_statut: string | null;
};

type Filtres = { type: string; statut: string; cite: string };

type Props = {
    sanctions: Paginated<Sanction>;
    stats: { total: number; active: number; avertissements: number; suspensions: number };
    filters: Filtres;
    structure: Structure;
    urls: { index: string; store: string | null; incidents: string | null };
};

const TOUS = 'tous';

export const STATUTS_SANCTION: Record<string, { label: string; variante: 'danger' | 'success' | 'neutral' }> = {
    active: { label: 'Active', variante: 'danger' },
    levee: { label: 'Levée', variante: 'success' },
    annulee: { label: 'Annulée', variante: 'neutral' },
};

const VARIANTES_TYPE: Record<string, 'warning' | 'danger' | 'info'> = { avertissement: 'warning', blame: 'danger', suspension: 'info', expulsion: 'danger' };

const TYPES = [
    { value: 'avertissement', label: 'Avertissement' },
    { value: 'blame', label: 'Blâme' },
    { value: 'suspension', label: 'Suspension' },
    { value: 'expulsion', label: 'Expulsion' },
];

const libelleStatut = (statut: string | null) => (statut ? (STATUTS_SANCTION[statut]?.label ?? statut) : '—');

/** Sanctions disciplinaires (remplace `vie_residentielle/sanctions/index.blade.php`). */
export default function SanctionsIndex({ sanctions, stats, filters, structure, urls }: Props) {
    const [nouvelle, setNouvelle] = useState(false);
    const [detail, setDetail] = useState<Sanction | null>(null);
    const [statut, setStatut] = useState<Sanction | null>(null);

    const filtrer = (cle: keyof Filtres, valeur: string) => {
        const suivants = { ...filters, [cle]: valeur === TOUS ? '' : valeur };
        router.get(urls.index, Object.fromEntries(Object.entries(suivants).filter(([, v]) => v)), { preserveState: true, preserveScroll: true, replace: true });
    };
    const filtreActif = Boolean(filters.type || filters.statut || filters.cite);

    const colonnes: DataTableColumn<Sanction>[] = [
        {
            key: 'decision',
            header: 'Décision',
            cell: (s) => (
                <div className="whitespace-nowrap">
                    <p className="font-medium text-gray-900">{s.numero}</p>
                    <p className="text-xs text-muted">{dateFr(s.date_decision)}</p>
                </div>
            ),
        },
        {
            key: 'cible',
            header: 'Personnes concernées',
            cell: (s) => (
                <div className="min-w-44">
                    <p className="font-medium text-gray-900">{s.cible.titre}</p>
                    {s.cible.detail && <p className="text-xs text-muted">{s.cible.detail}</p>}
                </div>
            ),
        },
        { key: 'type', header: 'Type', cell: (s) => <Badge variant={VARIANTES_TYPE[s.type] ?? 'neutral'}>{s.type_label}</Badge> },
        { key: 'motif', header: 'Motif', cell: (s) => <span className="line-clamp-2 max-w-64">{s.motif}</span> },
        {
            key: 'periode',
            header: 'Période',
            className: 'whitespace-nowrap',
            cell: (s) => (s.date_debut || s.date_fin ? `${dateFr(s.date_debut)} au ${dateFr(s.date_fin)}` : <span className="text-muted">—</span>),
        },
        { key: 'statut', header: 'Statut', cell: (s) => <Badge variant={STATUTS_SANCTION[s.statut]?.variante ?? 'neutral'}>{libelleStatut(s.statut)}</Badge> },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (s) => (
                <RowActions>
                    <RowAction label={`Voir le détail et l’historique (${s.historique.length})`} icon={Eye} tone="view" onClick={() => setDetail(s)} />
                    {s.url_statut && <RowAction label="Modifier le statut" icon={PenLine} tone="edit" onClick={() => setStatut(s)} />}
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Sanctions disciplinaires">
            <PageHeader
                title="Sanctions disciplinaires"
                description="Appliquer le règlement en conservant une trace complète de chaque décision. Une sanction active bloque, selon son type, les nouvelles admissions et attributions du résident."
                actions={
                    (urls.incidents || urls.store) && (
                        <>
                            {urls.incidents && (
                                <a href={urls.incidents} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                                    <ShieldAlert {...ICON} aria-hidden /> Rapports disciplinaires
                                </a>
                            )}
                            {urls.store && (
                                <Button size="sm" className="h-10 px-4" onClick={() => setNouvelle(true)}>
                                    <Gavel {...ICON} aria-hidden /> Appliquer une sanction
                                </Button>
                            )}
                        </>
                    )
                }
            />

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard tone="blue" label="Sanctions enregistrées" value={nombre(stats.total)} extra="Historique complet" />
                <StatCard tone="plum" label="Sanctions actives" value={nombre(stats.active)} extra="En vigueur" highlight={stats.active > 0} />
                <StatCard tone="amber" label="Avertissements actifs" value={nombre(stats.avertissements)} extra="Premier niveau" />
                <StatCard tone="green" label="Suspensions actives" value={nombre(stats.suspensions)} extra="Accès bloqués" highlight={stats.suspensions > 0} />
            </div>

            <section className="mt-6">
                <div className="mb-3 flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-medium text-gray-900">Registre des sanctions</h2>
                    <span className="text-sm text-muted">{nombre(sanctions.total)} sanction(s)</span>
                </div>
                <DataTable
                    columns={colonnes}
                    data={sanctions}
                    rowKey={(s) => s.id}
                    toolbar={
                        <>
                            <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                                <Select value={filters.type || TOUS} onValueChange={(valeur) => filtrer('type', valeur)}>
                                    <SelectTrigger className="h-10" aria-label="Filtrer par type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TOUS}>Tous les types</SelectItem>
                                        {TYPES.map((type) => (
                                            <SelectItem key={type.value} value={type.value}>
                                                {type.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={filters.statut || TOUS} onValueChange={(valeur) => filtrer('statut', valeur)}>
                                    <SelectTrigger className="h-10" aria-label="Filtrer par statut">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TOUS}>Tous les statuts</SelectItem>
                                        {Object.entries(STATUTS_SANCTION).map(([valeur, info]) => (
                                            <SelectItem key={valeur} value={valeur}>
                                                {info.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={filters.cite || TOUS} onValueChange={(valeur) => filtrer('cite', valeur)}>
                                    <SelectTrigger className="h-10" aria-label="Filtrer par cité">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TOUS}>Toutes les cités</SelectItem>
                                        {structure.cites.map((cite) => (
                                            <SelectItem key={cite.id} value={cite.id}>
                                                {cite.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            {filtreActif && (
                                <Button variant="ghost" size="sm" className="h-10" onClick={() => router.get(urls.index, {}, { preserveScroll: true })}>
                                    <X className="size-4" aria-hidden /> Réinitialiser
                                </Button>
                            )}
                        </>
                    }
                    emptyMessage={
                        <span className="flex flex-col items-center gap-2">
                            <Scale {...ICON} aria-hidden />
                            {filtreActif ? 'Aucune sanction ne correspond à ces filtres.' : 'Aucune sanction enregistrée.'}
                        </span>
                    }
                />
            </section>

            {urls.store && <SanctionDialog open={nouvelle} onClose={() => setNouvelle(false)} url={urls.store} structure={structure} />}
            <StatutSanctionDialog sanction={statut} onClose={() => setStatut(null)} />

            <Sheet open={detail !== null} onOpenChange={(ouvert) => !ouvert && setDetail(null)}>
                <SheetContent side="right" showCloseButton={false} className="w-full gap-0 p-0 sm:max-w-md">
                    {detail && (
                        <>
                            <header className="flex items-start gap-3 border-b px-5 py-4">
                                <span className="flex size-11 shrink-0 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                                    <Scale {...ICON} />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <SheetDescription className="text-xs text-muted">Sanction {detail.numero}</SheetDescription>
                                    <SheetTitle className="truncate text-lg font-medium text-gray-900">{detail.cible.titre}</SheetTitle>
                                    <div className="mt-2 flex flex-wrap gap-1.5">
                                        <Badge variant={VARIANTES_TYPE[detail.type] ?? 'neutral'}>{detail.type_label}</Badge>
                                        <Badge variant={STATUTS_SANCTION[detail.statut]?.variante ?? 'neutral'}>{libelleStatut(detail.statut)}</Badge>
                                    </div>
                                </div>
                                <SheetClose className="inline-flex size-8 cursor-pointer items-center justify-center rounded-brand text-muted transition-colors hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none">
                                    <X {...ICON} />
                                    <span className="sr-only">Fermer</span>
                                </SheetClose>
                            </header>
                            <div className="min-h-0 flex-1 space-y-6 overflow-y-auto px-5 py-5">
                                <Lignes
                                    lignes={[
                                        ['Décision du', dateFr(detail.date_decision)],
                                        ['Concerne', detail.cible.detail ?? detail.cible.titre],
                                        ...((detail.date_debut || detail.date_fin) ? ([['Période', `${dateFr(detail.date_debut)} au ${dateFr(detail.date_fin)}`]] as [string, string][]) : []),
                                    ]}
                                />
                                <div>
                                    <h3 className="mb-1.5 text-sm font-medium text-gray-900">Motif</h3>
                                    <p className="text-sm whitespace-pre-line text-gray-700">{detail.motif}</p>
                                </div>
                                {detail.faits && (
                                    <div>
                                        <h3 className="mb-1.5 text-sm font-medium text-gray-900">Faits reprochés</h3>
                                        <p className="text-sm whitespace-pre-line text-gray-700">{detail.faits}</p>
                                    </div>
                                )}
                                <div>
                                    <h3 className="mb-3 text-sm font-medium text-gray-900">Historique</h3>
                                    <Chronologie
                                        evenements={detail.historique.map((item) => ({
                                            id: item.id,
                                            titre: <span className="capitalize">{item.action.replace(/_/g, ' ')}</span>,
                                            date: item.date,
                                            texte: (
                                                <>
                                                    {item.details}
                                                    {(item.ancien_statut || item.nouveau_statut) && (
                                                        <span className="mt-0.5 block text-xs text-muted">
                                                            {libelleStatut(item.ancien_statut)} → {libelleStatut(item.nouveau_statut)}
                                                        </span>
                                                    )}
                                                </>
                                            ),
                                            ton: item.nouveau_statut === 'active' ? 'danger' : item.nouveau_statut === 'levee' ? 'primary' : 'muted',
                                        }))}
                                    />
                                </div>
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>
        </AdminLayout>
    );
}
