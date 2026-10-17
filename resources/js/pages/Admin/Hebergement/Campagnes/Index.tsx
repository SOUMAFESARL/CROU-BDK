 import { Link, router } from '@inertiajs/react';
import { ClipboardList, Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { CampagneDetailsSheet, StatutCampagneBadge } from '@/components/campagnes/details-sheet';
import { formatDate, typeCampagne, type CampagneItem, type CampagnesProps, type FiltreCampagne } from '@/components/campagnes/types';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { StatCard, type KpiTone } from '@/components/ui/stat-card';
import { cn } from '@/lib/utils';

const FILTRES: { filtre: FiltreCampagne; label: string; titre: string; stat: keyof CampagnesProps['stats']; tone: KpiTone }[] = [
    { filtre: 'tous', label: 'Toutes les campagnes', titre: 'Toutes les campagnes', stat: 'total', tone: 'blue' },
    { filtre: 'active', label: 'Actives', titre: 'Campagnes actives', stat: 'active', tone: 'green' },
    { filtre: 'planifier', label: 'Planifiées', titre: 'Campagnes planifiées', stat: 'planifier', tone: 'plum' },
    { filtre: 'terminer', label: 'Terminées', titre: 'Campagnes terminées', stat: 'terminer', tone: 'amber' },
];

/** Liste des campagnes d'admission et de réadmission (remplace la vue Blade `campagnes.blade.php`). */
export default function CampagnesIndex({ campagnes, stats, etat, urls }: CampagnesProps) {
    const [details, setDetails] = useState<CampagneItem | null>(null);
    const [suppression, setSuppression] = useState<CampagneItem | null>(null);
    const [traitement, setTraitement] = useState(false);
    const filtre = FILTRES.find((item) => item.filtre === etat) ?? FILTRES[0];

    const colonnes: DataTableColumn<CampagneItem>[] = [
        {
            key: 'campagne',
            header: 'Campagne',
            cell: (campagne) => (
                <div className="min-w-44">
                    <p className="font-medium whitespace-nowrap text-gray-900">Campagne {campagne.annee}</p>
                    {campagne.description && <p className="line-clamp-1 max-w-72 text-xs text-muted">{campagne.description}</p>}
                </div>
            ),
        },
        {
            key: 'periode',
            header: 'Période',
            className: 'whitespace-nowrap tabular-nums',
            cell: (campagne) => (
                <>
                    {formatDate(campagne.periode_debut)} <span className="text-muted">→</span> {formatDate(campagne.periode_fin)}
                </>
            ),
        },
        { key: 'type', header: 'Type', cell: (campagne) => <Badge variant="success">{typeCampagne(campagne.conditions)}</Badge> },
        { key: 'statut', header: 'Statut', cell: (campagne) => <StatutCampagneBadge statut={campagne.statut} /> },
        {
            key: 'documents',
            header: 'Documents requis',
            cell: (campagne) => (
                <Puces
                    valeurs={campagne.documents.slice(0, 3)}
                    reste={campagne.documents.length - 3}
                    variant="info"
                />
            ),
        },
        {
            key: 'residences',
            header: 'Résidences (places libres)',
            cell: (campagne) => <Puces valeurs={campagne.residences.map((residence) => `${residence.name} (${residence.places})`)} variant="success" />,
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            headClassName: 'w-28',
            className: 'text-right',
            cell: (campagne) => (
                <RowActions>
                    <RowAction label="Voir le détail" icon={Eye} tone="view" onClick={() => setDetails(campagne)} />
                    {campagne.urls.edit && (
                        <RowAction label="Modifier" icon={Pencil} tone="edit" onClick={() => router.visit(campagne.urls.edit!)} />
                    )}
                    {campagne.urls.destroy && (
                        <RowAction label="Supprimer" icon={Trash2} tone="danger" onClick={() => setSuppression(campagne)} />
                    )}
                </RowActions>
            ),
        },
    ];

    const supprimer = () => {
        if (!suppression?.urls.destroy) return;
        router.delete(suppression.urls.destroy, {
            preserveScroll: true,
            onStart: () => setTraitement(true),
            onFinish: () => {
                setTraitement(false);
                setSuppression(null);
            },
        });
    };

    return (
        <AdminLayout title="Campagnes">
            <div className="mb-6 flex flex-col gap-4">
                <div className="flex flex-col gap-1">
                    <h1 className="page-title text-xl font-medium text-gray-900">Campagnes</h1>
                    <p className="text-sm text-muted">Planifiez les campagnes d’admission et de réadmission, leurs critères et les résidences ouvertes.</p>
                </div>
                <Separator />
                <div className="flex flex-wrap items-center justify-end gap-2">
                    {urls.admissions && (
                        <a
                            href={urls.admissions}
                            className="inline-flex h-10 items-center gap-2 rounded-brand border border-primary bg-white px-4 text-sm font-medium text-primary transition-colors hover:bg-soft focus-visible:bg-soft focus-visible:outline-none"
                        >
                            <ClipboardList {...ICON} aria-hidden />
                            Admissions
                        </a>
                    )}
                    {urls.create && (
                        <Link href={urls.create} className={cn(buttonVariants({ size: 'sm' }), 'h-10 px-4')}>
                            <Plus {...ICON} aria-hidden />
                            Nouvelle campagne
                        </Link>
                    )}
                </div>
            </div>

            {/* Indicateurs par statut, qui servent aussi de filtres. */}
            <nav aria-label="Filtrer les campagnes par statut" className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {FILTRES.map((item) => {
                    const actif = item.filtre === filtre.filtre;
                    return (
                        <Link
                            key={item.filtre}
                            href={urls.filtres[item.filtre]}
                            preserveScroll
                            aria-current={actif ? 'page' : undefined}
                            className={cn(
                                'rounded-md transition-shadow focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                                actif ? 'ring-2 ring-primary ring-offset-2' : 'hover:shadow-sm',
                            )}
                        >
                            <StatCard tone={item.tone} label={item.label} value={stats[item.stat] ?? 0} />
                        </Link>
                    );
                })}
            </nav>

            <section className="mt-6">
                <div className="mb-3 flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-medium text-gray-900">{filtre.titre}</h2>
                    <span className="text-sm text-muted">{campagnes.total} campagne(s)</span>
                </div>
                <DataTable columns={colonnes} data={campagnes} rowKey={(campagne) => campagne.id} emptyMessage="Aucune campagne trouvée pour ce filtre." />
            </section>

            <CampagneDetailsSheet campagne={details} onClose={() => setDetails(null)} />

            <ConfirmDialog
                open={suppression !== null}
                onOpenChange={(ouvert) => !ouvert && setSuppression(null)}
                title="Supprimer cette campagne ?"
                description={
                    suppression && (
                        <>
                            La campagne {suppression.annee} ne sera plus proposée aux étudiants. Une campagne pour laquelle des demandes ont déjà été déposées
                            ne peut pas être supprimée.
                        </>
                    )
                }
                confirmLabel="Supprimer"
                confirmIcon={<Trash2 className="size-4" aria-hidden />}
                danger
                processing={traitement}
                onConfirm={supprimer}
            />
        </AdminLayout>
    );
}

/** Pastilles d'une cellule (documents, résidences), avec « +n » au-delà. */
function Puces({ valeurs, reste = 0, variant }: { valeurs: string[]; reste?: number; variant: 'success' | 'info' }) {
    if (valeurs.length === 0) return <span className="text-muted">—</span>;

    return (
        <div className="flex max-w-80 flex-wrap gap-1">
            {valeurs.map((valeur, index) => (
                <Badge key={`${valeur}-${index}`} variant={variant}>
                    {valeur}
                </Badge>
            ))}
            {reste > 0 && <Badge variant="neutral">+{reste}</Badge>}
        </div>
    );
}
