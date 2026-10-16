import { Link, router } from '@inertiajs/react';
import { BarChart3, Check, Download, Eye, Plus, Send, Wallet, X } from 'lucide-react';
import { useState } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { SimpleTable } from '@/components/data-table/simple-table';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { NouveauPaiementDialog } from '@/components/paiements/nouveau-paiement-dialog';
import { RelanceDialog } from '@/components/paiements/relance-dialog';
import { fcfa, MOIS, StatutPaiementBadge, toneTaux } from '@/components/paiements/shared';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { StatCard } from '@/components/ui/stat-card';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Paiement = {
    id: string;
    numero: string | null;
    resident: string | null;
    telephone: string | null;
    residence: string | null;
    chambre: string | null;
    mois: string | null;
    montant: number;
    methode: string;
    type: string;
    statut: string;
    statut_label: string;
    date_paiement: string | null;
    urls: { show: string | null; valider: string | null; annuler: string | null };
};

type StatResidence = {
    residence: string;
    total_attendu: string;
    total_encaisse: string;
    taux_recouvrement: number;
    impayes: string;
    nb_residents: number;
    loyer_moyen: string;
    alerte: boolean;
};

type Filtres = { residence: string; statut: string; mois: string };

type Props = {
    paiements: Paginated<Paiement>;
    kpis: { total_encaisse: number; taux_recouvrement: number; impayes: number; penalites: number; nombre_paiements: number; nombre_retard: number };
    statsParResidence: StatResidence[];
    residences: { id: string; name: string }[];
    filters: Filtres;
    urls: { index: string; export: string | null; store: string | null; recherche: string | null; relances: string | null; rapport: string | null };
};

const TOUS = 'tous';

const STATUTS = [
    { value: 'valide', label: 'Payé' },
    { value: 'en_attente', label: 'En attente' },
    { value: 'annule', label: 'Annulé' },
];

/** Les 24 derniers mois au format `aaaa-mm` (filtre « Mois » de l'action `index`). */
const moisRecents = () => {
    const maintenant = new Date();
    return Array.from({ length: 24 }, (_, index) => {
        const date = new Date(maintenant.getFullYear(), maintenant.getMonth() - index, 1);
        const mois = String(date.getMonth() + 1).padStart(2, '0');
        return { value: `${date.getFullYear()}-${mois}`, label: `${MOIS[date.getMonth()].label} ${date.getFullYear()}` };
    });
};

type Action = { type: 'valider' | 'annuler'; paiement: Paiement } | null;

/** Paiements des loyers (remplace `vie_residentielle/paiement/index.blade.php`). */
export default function PaiementsIndex({ paiements, kpis, statsParResidence, residences, filters, urls }: Props) {
    const [nouveau, setNouveau] = useState(false);
    const [relance, setRelance] = useState(false);
    const [action, setAction] = useState<Action>(null);
    const [traitement, setTraitement] = useState(false);
    const mois = moisRecents();
    if (filters.mois && !mois.some((item) => item.value === filters.mois)) mois.push({ value: filters.mois, label: filters.mois });

    const filtrer = (cle: keyof Filtres, valeur: string) => {
        const suivants = { ...filters, [cle]: valeur === TOUS ? '' : valeur };
        router.get(urls.index, Object.fromEntries(Object.entries(suivants).filter(([, v]) => v)), { preserveState: true, preserveScroll: true, replace: true });
    };
    const filtreActif = Boolean(filters.residence || filters.statut || filters.mois);

    const confirmer = () => {
        const url = action && action.paiement.urls[action.type];
        if (!url) return;
        router.post(url, {}, {
            preserveScroll: true,
            onStart: () => setTraitement(true),
            onFinish: () => {
                setTraitement(false);
                setAction(null);
            },
        });
    };

    const colonnes: DataTableColumn<Paiement>[] = [
        {
            key: 'resident',
            header: 'Résident',
            cell: (p) => (
                <div className="min-w-40">
                    <p className="font-medium text-gray-900">{p.resident ?? 'N/A'}</p>
                    <p className="text-xs text-muted">{p.telephone ?? '—'}</p>
                </div>
            ),
        },
        {
            key: 'logement',
            header: 'Logement',
            cell: (p) => (
                <div className="min-w-28">
                    <p>{p.residence ?? 'N/A'}</p>
                    <p className="text-xs text-muted">Chambre {p.chambre ?? 'N/A'}</p>
                </div>
            ),
        },
        { key: 'mois', header: 'Mois', cell: (p) => (p.mois ? <span className="line-clamp-2 max-w-48 capitalize">{p.mois}</span> : <span className="text-muted">—</span>) },
        { key: 'montant', header: 'Montant', className: 'whitespace-nowrap font-medium tabular-nums', cell: (p) => fcfa(p.montant) },
        { key: 'methode', header: 'Méthode', cell: (p) => <Badge variant="info">{p.methode}</Badge> },
        { key: 'statut', header: 'Statut', cell: (p) => <StatutPaiementBadge statut={p.statut} label={p.statut_label} /> },
        {
            key: 'date',
            header: 'Date de paiement',
            className: 'whitespace-nowrap',
            cell: (p) =>
                p.date_paiement ? (
                    <>
                        {dateFr(p.date_paiement)}
                        <p className="text-xs text-muted">{p.date_paiement.slice(11, 16)}</p>
                    </>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (p) => (
                <RowActions>
                    {p.urls.valider && <RowAction label="Valider le paiement" icon={Check} tone="success" onClick={() => setAction({ type: 'valider', paiement: p })} />}
                    {p.urls.annuler && <RowAction label="Annuler le paiement" icon={X} tone="danger" onClick={() => setAction({ type: 'annuler', paiement: p })} />}
                    {p.urls.show && <RowAction label="Voir le détail" icon={Eye} href={p.urls.show} tone="view" />}
                </RowActions>
            ),
        },
    ];

    const colonnesResidence: DataTableColumn<StatResidence>[] = [
        {
            key: 'residence',
            header: 'Résidence',
            cell: (s) => (
                <span className="flex items-center gap-2 font-medium text-gray-900">
                    {s.residence}
                    {s.alerte && <Badge variant="warning">Sous l’objectif</Badge>}
                </span>
            ),
        },
        { key: 'attendu', header: 'Total attendu', className: 'whitespace-nowrap tabular-nums', cell: (s) => s.total_attendu },
        { key: 'encaisse', header: 'Total encaissé', className: 'whitespace-nowrap tabular-nums', cell: (s) => s.total_encaisse },
        {
            key: 'taux',
            header: 'Recouvrement',
            cell: (s) => (
                <div className="flex min-w-32 items-center gap-2.5">
                    <Progress value={s.taux_recouvrement} tone={toneTaux(s.taux_recouvrement)} label={`${s.taux_recouvrement} % recouvré`} />
                    <span className="w-12 text-right text-xs font-medium tabular-nums">{nombre(s.taux_recouvrement, 1)} %</span>
                </div>
            ),
        },
        { key: 'impayes', header: 'Impayés', className: 'whitespace-nowrap tabular-nums', cell: (s) => s.impayes },
        { key: 'residents', header: 'Résidents', className: 'text-center tabular-nums', headClassName: 'text-center', cell: (s) => nombre(s.nb_residents) },
        { key: 'moyen', header: 'Loyer moyen', className: 'whitespace-nowrap tabular-nums', cell: (s) => s.loyer_moyen },
    ];

    return (
        <AdminLayout title="Paiements des loyers">
            <PageHeader
                title="Paiements des loyers"
                description="Encaissements des résidents et suivi des impayés. Les montants dus sont toujours calculés à partir des loyers enregistrés."
                actions={
                    (urls.rapport || urls.relances || urls.store) && (
                        <>
                            {urls.rapport && (
                                <Link href={urls.rapport} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                                    <BarChart3 {...ICON} aria-hidden /> Rapport financier
                                </Link>
                            )}
                            {urls.relances && (
                                <Button variant="secondary" size="sm" className="h-10 px-4" onClick={() => setRelance(true)}>
                                    <Send {...ICON} aria-hidden /> Relancer les impayés
                                </Button>
                            )}
                            {urls.store && urls.recherche && (
                                <Button size="sm" className="h-10 px-4" onClick={() => setNouveau(true)}>
                                    <Plus {...ICON} aria-hidden /> Nouveau paiement
                                </Button>
                            )}
                        </>
                    )
                }
            />

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard tone="green" label="Total encaissé" value={fcfa(kpis.total_encaisse)} extra={`${nombre(kpis.nombre_paiements)} paiement(s) ce mois`} />
                <StatCard tone="blue" label="Taux de recouvrement" value={`${nombre(kpis.taux_recouvrement, 2)} %`} extra="Objectif > 95 %" highlight={kpis.taux_recouvrement < 95} />
                <StatCard tone="amber" label="Impayés" value={fcfa(kpis.impayes)} extra={`${nombre(kpis.nombre_retard)} loyer(s) en retard`} highlight={kpis.nombre_retard > 0} />
                <StatCard tone="plum" label="Pénalités" value={fcfa(kpis.penalites)} extra="Retards accumulés" />
            </div>

            <section className="mt-6">
                <div className="mb-3 flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-medium text-gray-900">Liste des paiements</h2>
                    <span className="text-sm text-muted">{nombre(paiements.total)} paiement(s)</span>
                </div>
                <DataTable
                    columns={colonnes}
                    data={paiements}
                    rowKey={(p) => p.id}
                    toolbar={
                        <>
                            <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                                <Select value={filters.residence || TOUS} onValueChange={(valeur) => filtrer('residence', valeur)}>
                                    <SelectTrigger className="h-10" aria-label="Filtrer par résidence">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TOUS}>Toutes les résidences</SelectItem>
                                        {residences.map((residence) => (
                                            <SelectItem key={residence.id} value={residence.id}>
                                                {residence.name}
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
                                        {STATUTS.map((statut) => (
                                            <SelectItem key={statut.value} value={statut.value}>
                                                {statut.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={filters.mois || TOUS} onValueChange={(valeur) => filtrer('mois', valeur)}>
                                    <SelectTrigger className="h-10" aria-label="Filtrer par mois de paiement">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TOUS}>Tous les mois</SelectItem>
                                        {mois.map((item) => (
                                            <SelectItem key={item.value} value={item.value}>
                                                {item.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex gap-2">
                                {filtreActif && (
                                    <Button variant="ghost" size="sm" className="h-10" onClick={() => router.get(urls.index, {}, { preserveScroll: true })}>
                                        <X className="size-4" aria-hidden /> Réinitialiser
                                    </Button>
                                )}
                                {urls.export && (
                                    <a href={urls.export} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                                        <Download {...ICON} aria-hidden /> Exporter (CSV)
                                    </a>
                                )}
                            </div>
                        </>
                    }
                    emptyMessage={
                        <span className="flex flex-col items-center gap-2">
                            <Wallet {...ICON} aria-hidden />
                            {filtreActif ? 'Aucun paiement ne correspond à ces filtres.' : 'Aucun paiement enregistré.'}
                        </span>
                    }
                />
            </section>

            <section className="mt-8">
                <div className="mb-3 flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-medium text-gray-900">Récapitulatif par résidence</h2>
                    <span className="text-sm text-muted">Objectif de recouvrement : plus de 95 %</span>
                </div>
                <div className="overflow-hidden rounded-brand border bg-white">
                    <SimpleTable columns={colonnesResidence} rows={statsParResidence} rowKey={(s) => s.residence} emptyMessage="Aucune donnée de loyer par résidence." />
                </div>
            </section>

            {urls.store && urls.recherche && <NouveauPaiementDialog open={nouveau} onClose={() => setNouveau(false)} urlStore={urls.store} urlRecherche={urls.recherche} />}
            {urls.relances && <RelanceDialog open={relance} onClose={() => setRelance(false)} url={urls.relances} nombreRetard={kpis.nombre_retard} />}

            <ConfirmDialog
                open={action !== null}
                onOpenChange={(ouvert) => !ouvert && setAction(null)}
                title={action?.type === 'valider' ? 'Valider ce paiement ?' : 'Annuler ce paiement ?'}
                description={
                    action && (
                        <>
                            {action.paiement.resident ?? 'Résident inconnu'} · <strong className="font-medium text-gray-900">{fcfa(action.paiement.montant)}</strong>
                            {action.type === 'valider' ? '. Le paiement sera marqué comme payé à la date du jour.' : '. Le paiement sera marqué comme annulé.'}
                        </>
                    )
                }
                confirmLabel={action?.type === 'valider' ? 'Valider le paiement' : 'Annuler le paiement'}
                confirmIcon={action?.type === 'valider' ? <Check className="size-4" aria-hidden /> : <X className="size-4" aria-hidden />}
                danger={action?.type === 'annuler'}
                processing={traitement}
                onConfirm={confirmer}
            />
        </AdminLayout>
    );
}
