import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowLeftRight, History, Minus, Plus } from 'lucide-react';
import { useState } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { MouvementDialog } from '@/components/equipements/mouvement-dialog';
import { EquipementThumb, StockBadge } from '@/components/equipements/stock-badge';
import { STATUTS_STOCK, tauxStock, type EquipementItem } from '@/components/equipements/types';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { dateFr, nombre } from '@/lib/text';
import type { Paginated } from '@/types/global';

type Filtre = 'tous' | 'entry' | 'exit';

type Mouvement = {
    id: string;
    date: string | null;
    type: 'entry' | 'exit';
    quantite: number;
    motif: string | null;
    auteur: string | null;
};

type Props = {
    equipement: EquipementItem & { reference: string | null };
    stats: { tous: number; entry: number; exit: number; entrees: number; sorties: number; dernier: string | null };
    type: Filtre;
    mouvements: Paginated<Mouvement>;
    urls: { catalogue: string | null; storeMouvement: string | null; filtres: Record<Filtre, string> };
};

const TITRES: Record<Filtre, string> = { tous: 'Journal des mouvements', entry: 'Entrées en magasin', exit: 'Sorties du magasin' };

/** Historique des entrées et sorties de stock d'un équipement (remplace `equipements/historique.blade.php`). */
export default function EquipementHistorique({ equipement, stats, type, mouvements, urls }: Props) {
    const [mouvement, setMouvement] = useState(false);
    const taux = tauxStock(equipement.stock_actuel, Math.max(equipement.stock_initial, stats.entrees));

    const colonnes: DataTableColumn<Mouvement>[] = [
        { key: 'num', header: '#', headClassName: 'w-12', className: 'text-muted tabular-nums', cell: (ligne) => (mouvements.from ?? 1) + mouvements.data.indexOf(ligne) },
        {
            key: 'date',
            header: 'Date',
            cell: (ligne) => (
                <div className="flex flex-col whitespace-nowrap">
                    <span className="font-medium text-gray-900">{dateFr(ligne.date)}</span>
                    <span className="text-xs text-muted">à {ligne.date?.slice(11, 16)}</span>
                </div>
            ),
        },
        {
            key: 'type',
            header: 'Type',
            cell: (ligne) =>
                ligne.type === 'entry' ? (
                    <Badge variant="success">
                        <Plus className="size-3.5" aria-hidden /> Entrée
                    </Badge>
                ) : (
                    <Badge variant="danger">
                        <Minus className="size-3.5" aria-hidden /> Sortie
                    </Badge>
                ),
        },
        {
            key: 'quantite',
            header: 'Quantité',
            cell: (ligne) => (
                <span className={ligne.type === 'entry' ? 'font-medium text-primary tabular-nums' : 'font-medium text-red-700 tabular-nums'}>
                    {ligne.type === 'entry' ? '+' : '−'}
                    {nombre(ligne.quantite)}
                </span>
            ),
        },
        {
            key: 'motif',
            header: 'Motif',
            cell: (ligne) =>
                ligne.motif ? (
                    <span title={ligne.motif} className="line-clamp-2 max-w-90">
                        {ligne.motif}
                    </span>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
        { key: 'auteur', header: 'Saisi par', cell: (ligne) => ligne.auteur ?? <span className="text-muted">Non renseigné</span> },
    ];

    return (
        <AdminLayout title="Historique des mouvements">
            <PageHeader
                title="Historique des mouvements"
                description="Toutes les entrées et sorties de stock de cet équipement, de la plus récente à la plus ancienne. Les affectations au parc et les retours au magasin y figurent aussi."
                actions={
                    (urls.catalogue || urls.storeMouvement) && (
                        <>
                            {urls.catalogue && (
                                <Link href={urls.catalogue} className="inline-flex h-10 items-center gap-2 rounded-brand border border-gray-300 bg-white px-4 text-sm font-medium text-muted hover:bg-surface">
                                    <ArrowLeft {...ICON} aria-hidden />
                                    Retour au catalogue
                                </Link>
                            )}
                            {urls.storeMouvement && (
                                <Button size="sm" className="h-10 px-4" onClick={() => setMouvement(true)}>
                                    <ArrowLeftRight {...ICON} aria-hidden />
                                    Mouvement de stock
                                </Button>
                            )}
                        </>
                    )
                }
            />

            {/* Fiche de l'équipement */}
            <section className="mb-6 flex flex-wrap items-center gap-x-6 gap-y-4 rounded-brand border bg-white p-4">
                <div className="flex min-w-0 flex-[1_1_260px] items-center gap-3.5">
                    <EquipementThumb photo={equipement.photo} className="size-16" />
                    <div className="min-w-0">
                        <h2 className="text-base font-medium text-gray-900">{equipement.name}</h2>
                        <div className="mt-1.5 flex flex-wrap items-center gap-2 text-sm text-muted">
                            <span>{equipement.type ?? 'Type non défini'}</span>
                            {equipement.reference && <span className="rounded bg-surface px-2 py-0.5 font-mono text-xs">{equipement.reference}</span>}
                            <StockBadge statut={equipement.statut} />
                        </div>
                    </div>
                </div>
                <div className="grid w-full grid-cols-3 gap-x-7 gap-y-1 sm:w-auto">
                    <Chiffre label="Stock actuel" valeur={equipement.stock_actuel} />
                    <Chiffre label="Stock initial" valeur={equipement.stock_initial} />
                    <Chiffre label="Seuil d’alerte" valeur={equipement.threshold} />
                    <Progress
                        className="col-span-3 mt-2"
                        value={taux}
                        tone={STATUTS_STOCK[equipement.statut].barre}
                        label={`${taux} % des quantités reçues encore en magasin`}
                    />
                </div>
            </section>

            <KpiFilters
                label="Filtrer les mouvements"
                active={type}
                className="xl:grid-cols-3"
                items={[
                    {
                        key: 'tous',
                        label: 'Tous les mouvements',
                        value: nombre(stats.tous),
                        tone: 'blue',
                        href: urls.filtres.tous,
                        extra: stats.dernier ? `dernier le ${dateFr(stats.dernier)}` : undefined,
                    },
                    { key: 'entry', label: 'Entrées en magasin', value: nombre(stats.entry), tone: 'green', href: urls.filtres.entry, extra: `+${nombre(stats.entrees)} unité(s)` },
                    { key: 'exit', label: 'Sorties du magasin', value: nombre(stats.exit), tone: 'plum', href: urls.filtres.exit, extra: `−${nombre(stats.sorties)} unité(s)` },
                ]}
            />

            <section className="mt-6">
                <div className="mb-3 flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-medium text-gray-900">{TITRES[type]}</h2>
                    {type !== 'tous' && (
                        <Link href={urls.filtres.tous} preserveScroll className="text-sm font-medium text-primary hover:underline">
                            Voir tous les mouvements
                        </Link>
                    )}
                </div>
                <DataTable
                    columns={colonnes}
                    data={mouvements}
                    rowKey={(ligne) => ligne.id}
                    emptyMessage={
                        <span className="flex flex-col items-center gap-2">
                            <History {...ICON} aria-hidden />
                            {type !== 'tous' ? 'Aucun mouvement de ce type pour cet équipement.' : 'Aucun mouvement enregistré pour cet équipement.'}
                        </span>
                    }
                />
            </section>

            {urls.storeMouvement && <MouvementDialog equipement={mouvement ? equipement : null} url={urls.storeMouvement} onClose={() => setMouvement(false)} />}
        </AdminLayout>
    );
}

function Chiffre({ label, valeur }: { label: string; valeur: number }) {
    return (
        <div className="flex flex-col gap-0.5">
            <span className="text-xs text-muted">{label}</span>
            <strong className="text-lg font-medium tabular-nums">{nombre(valeur)}</strong>
        </div>
    );
}
