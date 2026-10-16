import { Link } from '@inertiajs/react';
import { BedDouble, CalendarDays, Eye, Inbox, LayoutGrid, List } from 'lucide-react';
import { useEffect, useState } from 'react';
import { ClassementAttribution, StatutCampagne, pourcent, type CampagneAllocation, type Classement } from '@/components/allocations/shared';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { buttonVariants } from '@/components/ui/button';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Ligne = CampagneAllocation & {
    a_loger: number;
    propositions: number;
    confirmees: number;
    taux: number;
    urls: { show: string | null; allocate: string | null };
};

type Props = {
    campagnes: Paginated<Ligne>;
    stats: { total: number; active: number; planifiee: number; terminee: number };
    classement: Classement | null;
};

type Vue = 'liste' | 'cartes';
const CLE_VUE = 'allocations.vue';

/** Campagnes d'attribution des chambres (remplace `allocation_index.blade.php`). */
export default function AllocationsIndex({ campagnes, stats, classement }: Props) {
    const [vue, setVue] = useState<Vue>('liste');

    // Vue mémorisée dans le navigateur, comme l'ancienne page.
    useEffect(() => {
        try {
            if (localStorage.getItem(CLE_VUE) === 'cartes') setVue('cartes');
        } catch {
            /* stockage indisponible : vue par défaut */
        }
    }, []);
    const changerVue = (nouvelle: Vue) => {
        setVue(nouvelle);
        try {
            localStorage.setItem(CLE_VUE, nouvelle);
        } catch {
            /* stockage indisponible */
        }
    };

    const colonnes: DataTableColumn<Ligne>[] = [
        {
            key: 'campagne',
            header: 'Campagne',
            cell: (ligne) => (
                <div className="min-w-40">
                    <p className="font-medium text-gray-900">{ligne.annee}</p>
                    {ligne.description && (
                        <p className="line-clamp-1 max-w-64 text-xs text-muted" title={ligne.description}>
                            {ligne.description}
                        </p>
                    )}
                </div>
            ),
        },
        { key: 'statut', header: 'Statut', cell: (ligne) => <StatutCampagne campagne={ligne} /> },
        { key: 'periode', header: 'Période', className: 'whitespace-nowrap tabular-nums', cell: (ligne) => <Periode ligne={ligne} /> },
        { key: 'a_loger', header: <span title="Dossiers éligibles en attente d’une proposition de chambre">À loger</span>, className: 'tabular-nums', cell: (ligne) => nombre(ligne.a_loger) },
        {
            key: 'propositions',
            header: <span title="Propositions de chambre en attente de réponse ou de validation">Propositions</span>,
            className: 'tabular-nums',
            cell: (ligne) => nombre(ligne.propositions),
        },
        { key: 'confirmees', header: 'Confirmées', className: 'tabular-nums', cell: (ligne) => nombre(ligne.confirmees) },
        { key: 'taux', header: 'Taux d’allocation', cell: (ligne) => <Taux valeur={ligne.taux} /> },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (ligne) => (
                <RowActions>
                    {ligne.urls.show && <RowAction label="Suivi de la campagne" icon={Eye} tone="view" href={ligne.urls.show} />}
                    {ligne.urls.allocate && <RowAction label="Attribuer une chambre" icon={BedDouble} href={ligne.urls.allocate} />}
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Attribution des chambres">
            <PageHeader
                title="Attribution des chambres"
                description="Choisissez une campagne pour proposer des chambres aux dossiers éligibles, suivre les propositions et valider les attributions définitives."
            />

            <KpiFilters
                label="Indicateurs des campagnes"
                items={[
                    { key: 'total', label: 'Campagnes', value: nombre(stats.total), tone: 'blue' },
                    { key: 'active', label: 'En cours', value: nombre(stats.active), tone: 'green' },
                    { key: 'planifiee', label: 'Planifiées', value: nombre(stats.planifiee), tone: 'amber' },
                    { key: 'terminee', label: 'Terminées', value: nombre(stats.terminee), tone: 'plum' },
                ]}
            />

            <ClassementAttribution classement={classement} />

            <section className="mt-6">
                <div className="mb-3 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 className="text-base font-medium text-gray-900">Campagnes d’attribution</h2>
                        <p className="text-xs text-muted">Les campagnes annulées ne sont pas affichées.</p>
                    </div>
                    <div role="group" aria-label="Affichage des campagnes" className="flex gap-1 rounded-brand border bg-white p-1">
                        {(
                            [
                                ['liste', List, 'Liste'],
                                ['cartes', LayoutGrid, 'Cartes'],
                            ] as const
                        ).map(([cle, Icone, libelle]) => (
                            <button
                                key={cle}
                                type="button"
                                aria-pressed={vue === cle}
                                title={`Affichage en ${libelle.toLowerCase()}`}
                                onClick={() => changerVue(cle)}
                                className={cn(
                                    'inline-flex size-8 cursor-pointer items-center justify-center rounded-[6px] text-muted transition-colors hover:text-primary',
                                    vue === cle && 'bg-soft text-primary',
                                )}
                            >
                                <Icone {...ICON} aria-hidden />
                                <span className="sr-only">{libelle}</span>
                            </button>
                        ))}
                    </div>
                </div>

                {vue === 'liste' ? (
                    <DataTable columns={colonnes} data={campagnes} rowKey={(ligne) => ligne.id} emptyMessage={<Vide />} />
                ) : (
                    <div className="overflow-hidden rounded-brand border bg-white">
                        {campagnes.data.length === 0 ? (
                            <div className="py-12 text-center text-muted">
                                <Vide />
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
                                {campagnes.data.map((ligne) => (
                                    <CarteCampagne key={ligne.id} ligne={ligne} />
                                ))}
                            </div>
                        )}
                        <DataTablePagination data={campagnes} />
                    </div>
                )}
            </section>
        </AdminLayout>
    );
}

function Periode({ ligne }: { ligne: CampagneAllocation }) {
    return (
        <>
            {dateFr(ligne.periode_debut)} <span className="text-muted">→</span> {dateFr(ligne.periode_fin)}
        </>
    );
}

function Taux({ valeur }: { valeur: number }) {
    return (
        <div className="flex w-32 flex-col gap-1" title="Attributions confirmées / étudiants ayant déposé une demande">
            <span className="text-sm font-medium tabular-nums">{pourcent(valeur)}</span>
            <Progress value={valeur} />
        </div>
    );
}

function Vide() {
    return (
        <span className="flex flex-col items-center gap-2">
            <Inbox {...ICON} aria-hidden />
            Aucune campagne pour le moment.
        </span>
    );
}

function CarteCampagne({ ligne }: { ligne: Ligne }) {
    return (
        <article className="flex flex-col gap-3 rounded-brand border p-4">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="font-medium text-gray-900">{ligne.annee}</p>
                    {ligne.description && <p className="line-clamp-2 text-xs text-muted">{ligne.description}</p>}
                </div>
                <StatutCampagne campagne={ligne} />
            </div>
            <p className="flex items-center gap-1.5 text-xs text-muted">
                <CalendarDays className="size-3.5" aria-hidden /> <Periode ligne={ligne} />
            </p>
            <dl className="grid grid-cols-3 gap-2 rounded-brand bg-surface p-3 text-center">
                {(
                    [
                        ['À loger', ligne.a_loger],
                        ['Propositions', ligne.propositions],
                        ['Confirmées', ligne.confirmees],
                    ] as const
                ).map(([terme, valeur]) => (
                    <div key={terme}>
                        <dt className="text-xs text-muted">{terme}</dt>
                        <dd className="text-base font-medium tabular-nums">{nombre(valeur)}</dd>
                    </div>
                ))}
            </dl>
            <div className="flex flex-col gap-1">
                <span className="text-xs text-muted">
                    Taux d’allocation : <span className="font-medium text-gray-900">{pourcent(ligne.taux)}</span>
                </span>
                <Progress value={ligne.taux} />
            </div>
            <div className="mt-auto flex gap-2">
                {ligne.urls.show && (
                    <Link href={ligne.urls.show} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'flex-1')}>
                        <Eye className="size-4" aria-hidden /> Détails
                    </Link>
                )}
                {ligne.urls.allocate && (
                    <Link href={ligne.urls.allocate} className={cn(buttonVariants({ size: 'sm' }), 'flex-1')}>
                        <BedDouble className="size-4" aria-hidden /> Allouer
                    </Link>
                )}
            </div>
        </article>
    );
}
