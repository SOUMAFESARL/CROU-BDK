import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Eye, Printer } from 'lucide-react';
import type { ReactNode } from 'react';
import { Bar, CartesianGrid, Cell, ComposedChart, Line, Pie, PieChart, XAxis, YAxis } from 'recharts';
import type { DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { SimpleTable } from '@/components/data-table/simple-table';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { fcfa, fcfaCourt, MOIS, toneTaux, varianteBadge } from '@/components/paiements/shared';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { ChartContainer, ChartLegend, ChartLegendContent, ChartTooltip, ChartTooltipContent, type ChartConfig } from '@/components/ui/chart';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { StatCard } from '@/components/ui/stat-card';
import { symboleDevise } from '@/lib/systeme';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';

type Mode = { methode: string; libelle: string; couleur: string; nombre: number; total: number; moyenne: number; pourcentage: number };

type StatResidence = {
    residence: string | null;
    total_attendu: number;
    total_encaisse: number;
    total_impayes: number;
    taux_recouvrement: number;
    nb_residents: number;
    loyer_moyen: number;
    nb_en_retard: number;
    alerte: boolean;
};

type DernierPaiement = {
    id: string;
    numero: string | null;
    resident: string;
    montant: number;
    methode: string;
    statut_label: string;
    statut_badge: string;
    date: string | null;
    url: string | null;
};

type Filtres = { annee: string; mois: string; residence: string };

type Props = {
    stats: {
        total_attendu: number;
        total_encaisse: number;
        total_impayes: number;
        total_penalites: number;
        total_retard: number;
        nb_retard: number;
        nb_impayes: number;
        nb_loyers: number;
        nb_paiements: number;
        taux_recouvrement: number;
    };
    evolution: { label: string; encaisse: number; attendu: number }[];
    repartition: { paye: number; impaye: number; retard: number };
    statsParMode: Mode[];
    statsParResidence: StatResidence[];
    derniersPaiements: DernierPaiement[];
    residences: { id: string; name: string }[];
    filters: Filtres;
    urls: { rapport: string; index: string | null };
};

const COULEURS = { primary: '#006400', secondary: '#86BB46', accent: '#FAAB04', danger: '#DC2626' };
const TOUS = 'tous';

/** Rapport financier des loyers (remplace `vie_residentielle/paiement/rapport-financier.blade.php`, Chart.js → Recharts). */
export default function RapportFinancier({ stats, evolution, repartition, statsParMode, statsParResidence, derniersPaiements, residences, filters, urls }: Props) {
    const anneeCourante = new Date().getFullYear();
    const annees = Array.from({ length: 6 }, (_, index) => String(anneeCourante - index));
    if (!annees.includes(filters.annee)) annees.unshift(filters.annee);

    const filtrer = (cle: keyof Filtres, valeur: string) => {
        const suivants = { ...filters, [cle]: valeur === TOUS ? '' : valeur };
        router.get(urls.rapport, Object.fromEntries(Object.entries(suivants).filter(([, v]) => v)), { preserveState: true, preserveScroll: true, replace: true });
    };

    const totalModes = statsParMode.reduce((somme, mode) => ({ nombre: somme.nombre + mode.nombre, total: somme.total + mode.total }), { nombre: 0, total: 0 });
    const totauxResidences = statsParResidence.reduce(
        (somme, s) => ({ attendu: somme.attendu + s.total_attendu, encaisse: somme.encaisse + s.total_encaisse, impayes: somme.impayes + s.total_impayes }),
        { attendu: 0, encaisse: 0, impayes: 0 },
    );
    const tauxGlobal = totauxResidences.attendu > 0 ? Math.round((totauxResidences.encaisse / totauxResidences.attendu) * 1000) / 10 : 0;

    const colonnesModes: DataTableColumn<Mode>[] = [
        {
            key: 'mode',
            header: 'Mode de paiement',
            cell: (m) => (
                <span className="flex items-center gap-2 font-medium text-gray-900">
                    <span className="size-2.5 shrink-0 rounded-full" style={{ background: m.couleur }} aria-hidden />
                    {m.libelle}
                </span>
            ),
        },
        { key: 'nombre', header: 'Paiements', className: 'text-center tabular-nums', headClassName: 'text-center', cell: (m) => nombre(m.nombre) },
        { key: 'total', header: 'Montant total', className: 'whitespace-nowrap font-medium tabular-nums', cell: (m) => fcfa(m.total) },
        { key: 'moyenne', header: 'Montant moyen', className: 'whitespace-nowrap tabular-nums', cell: (m) => fcfa(m.moyenne) },
        {
            key: 'part',
            header: 'Part',
            cell: (m) => (
                <div className="flex min-w-28 items-center gap-2.5">
                    <Progress value={m.pourcentage} tone="secondary" label={`${m.pourcentage} % du total`} />
                    <span className="w-12 text-right text-xs font-medium tabular-nums">{nombre(m.pourcentage, 1)} %</span>
                </div>
            ),
        },
    ];

    const colonnesResidences: DataTableColumn<StatResidence>[] = [
        {
            key: 'residence',
            header: 'Résidence',
            cell: (s) => (
                <span className="flex items-center gap-2 font-medium text-gray-900">
                    {s.residence ?? 'N/A'}
                    {s.alerte && <Badge variant="warning">Sous l’objectif</Badge>}
                </span>
            ),
        },
        { key: 'attendu', header: 'Attendu', className: 'whitespace-nowrap tabular-nums', cell: (s) => fcfa(s.total_attendu) },
        { key: 'encaisse', header: 'Encaissé', className: 'whitespace-nowrap tabular-nums', cell: (s) => fcfa(s.total_encaisse) },
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
        { key: 'impayes', header: 'Impayés', className: 'whitespace-nowrap tabular-nums', cell: (s) => fcfa(s.total_impayes) },
        { key: 'residents', header: 'Résidents', className: 'text-center tabular-nums', headClassName: 'text-center', cell: (s) => nombre(s.nb_residents) },
        { key: 'moyen', header: 'Loyer moyen', className: 'whitespace-nowrap tabular-nums', cell: (s) => fcfa(s.loyer_moyen) },
        {
            key: 'retards',
            header: 'Retards',
            cell: (s) => <Badge variant={s.nb_en_retard > 0 ? 'danger' : 'success'}>{nombre(s.nb_en_retard)} retard(s)</Badge>,
        },
    ];

    const colonnesDerniers: DataTableColumn<DernierPaiement>[] = [
        { key: 'numero', header: 'Numéro', className: 'font-mono text-[13px]', cell: (p) => p.numero ?? 'N/A' },
        { key: 'resident', header: 'Résident', className: 'font-medium text-gray-900', cell: (p) => p.resident },
        { key: 'montant', header: 'Montant', className: 'whitespace-nowrap font-medium tabular-nums', cell: (p) => fcfa(p.montant) },
        { key: 'methode', header: 'Méthode', cell: (p) => <Badge variant="info">{p.methode}</Badge> },
        { key: 'statut', header: 'Statut', cell: (p) => <Badge variant={varianteBadge(p.statut_badge)}>{p.statut_label}</Badge> },
        { key: 'date', header: 'Date', className: 'whitespace-nowrap', cell: (p) => p.date ?? <span className="text-muted">—</span> },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (p) => <RowActions>{p.url && <RowAction label="Voir le détail" icon={Eye} href={p.url} tone="view" />}</RowActions>,
        },
    ];

    const configEvolution = {
        encaisse: { label: 'Montants encaissés', color: COULEURS.primary },
        attendu: { label: 'Montants attendus', color: COULEURS.accent },
    } satisfies ChartConfig;

    const donneesRepartition = [
        { name: 'Payé', value: repartition.paye, fill: COULEURS.primary },
        { name: 'Impayé', value: Math.max(0, repartition.impaye), fill: COULEURS.danger },
        { name: 'En retard', value: repartition.retard, fill: COULEURS.accent },
    ];
    const donneesModes = statsParMode.map((mode) => ({ name: mode.libelle, value: mode.total, fill: mode.couleur }));

    return (
        <AdminLayout title="Rapport financier">
            <PageHeader
                title="Rapport financier"
                description="Encaissements, impayés et recouvrement des loyers sur la période choisie."
                actions={
                    <div className="flex flex-wrap items-center justify-end gap-2 print:hidden">
                        {urls.index && (
                            <Link href={urls.index} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                                <ArrowLeft {...ICON} aria-hidden /> Retour aux paiements
                            </Link>
                        )}
                        <Button variant="secondary" size="sm" className="h-10 px-4" onClick={() => window.print()}>
                            <Printer {...ICON} aria-hidden /> Imprimer
                        </Button>
                    </div>
                }
            />

            {/* Période et résidence : la page est rechargée à chaque changement. */}
            <section aria-label="Période du rapport" className="mb-6 grid grid-cols-1 gap-3 rounded-brand border bg-white p-4 sm:grid-cols-3 print:hidden">
                <Filtre label="Année">
                    <Select value={filters.annee} onValueChange={(valeur) => filtrer('annee', valeur)}>
                        <SelectTrigger className="h-10" aria-label="Année">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {annees.map((annee) => (
                                <SelectItem key={annee} value={annee}>
                                    {annee}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Filtre>
                <Filtre label="Mois">
                    <Select value={filters.mois || TOUS} onValueChange={(valeur) => filtrer('mois', valeur)}>
                        <SelectTrigger className="h-10" aria-label="Mois">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={TOUS}>Tous les mois</SelectItem>
                            {MOIS.map((mois) => (
                                <SelectItem key={mois.value} value={mois.value}>
                                    {mois.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Filtre>
                <Filtre label="Résidence">
                    <Select value={filters.residence || TOUS} onValueChange={(valeur) => filtrer('residence', valeur)}>
                        <SelectTrigger className="h-10" aria-label="Résidence">
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
                </Filtre>
            </section>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard tone="green" label="Total encaissé" value={fcfa(stats.total_encaisse)} extra={`${nombre(stats.nb_paiements)} paiement(s)`} />
                <StatCard tone="blue" label="Total attendu" value={fcfa(stats.total_attendu)} extra={`${nombre(stats.nb_loyers)} loyer(s)`} />
                <StatCard tone="blue" label="Taux de recouvrement" value={`${nombre(stats.taux_recouvrement, 1)} %`} extra="Objectif > 95 %" highlight={stats.taux_recouvrement < 95}>
                    <Progress value={stats.taux_recouvrement} tone={toneTaux(stats.taux_recouvrement)} label={`${stats.taux_recouvrement} % recouvré`} className="bg-white" />
                </StatCard>
                <StatCard tone="amber" label="Impayés" value={fcfa(stats.total_impayes)} extra={`${nombre(stats.nb_impayes)} loyer(s) impayé(s)`} highlight={stats.total_impayes > 0} />
                <StatCard tone="amber" label="Retards" value={fcfa(stats.total_retard)} extra={`${nombre(stats.nb_retard)} loyer(s) en retard`} />
                <StatCard tone="plum" label="Pénalités" value={fcfa(stats.total_penalites)} extra="Retards accumulés" />
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Panneau titre="Évolution des paiements" extra={<Badge variant="success">{filters.annee}</Badge>} className="lg:col-span-2">
                    <ChartContainer config={configEvolution} className="aspect-auto h-80 w-full">
                        <ComposedChart data={evolution} margin={{ left: 0, right: 8, top: 8 }}>
                            <CartesianGrid vertical={false} strokeDasharray="4 4" />
                            <XAxis dataKey="label" tickLine={false} axisLine={false} tickMargin={8} />
                            <YAxis tickLine={false} axisLine={false} width={56} tickFormatter={(valeur: number) => fcfaCourt(valeur)} />
                            <ChartTooltip content={<ChartTooltipContent indicator="dot" formatter={montantTooltip({ encaisse: configEvolution.encaisse.label, attendu: configEvolution.attendu.label })} />} />
                            <ChartLegend content={<ChartLegendContent />} />
                            <Bar dataKey="encaisse" fill="var(--color-encaisse)" radius={[4, 4, 0, 0]} maxBarSize={28} />
                            <Line dataKey="attendu" type="monotone" stroke="var(--color-attendu)" strokeWidth={2} strokeDasharray="5 5" dot={false} />
                        </ComposedChart>
                    </ChartContainer>
                </Panneau>
                <Panneau titre="Répartition des montants">
                    <Donut donnees={donneesRepartition} vide="Aucun montant sur la période." />
                </Panneau>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <section className="overflow-hidden rounded-brand border bg-white lg:col-span-2">
                    <div className="flex items-center justify-between gap-3 border-b px-5 py-4">
                        <h2 className="text-base font-medium text-gray-900">Répartition par mode de paiement</h2>
                        <span className="text-sm text-muted">{nombre(statsParMode.length)} mode(s)</span>
                    </div>
                    <SimpleTable columns={colonnesModes} rows={statsParMode} rowKey={(m) => m.methode} emptyMessage="Aucune donnée pour la période sélectionnée." />
                    {statsParMode.length > 0 && (
                        <Totaux>
                            <span>
                                Paiements : <strong className="font-medium tabular-nums">{nombre(totalModes.nombre)}</strong>
                            </span>
                            <span>
                                Total : <strong className="font-medium tabular-nums">{fcfa(totalModes.total)}</strong>
                            </span>
                        </Totaux>
                    )}
                </section>
                <Panneau titre="Part de chaque mode">
                    <Donut donnees={donneesModes} vide="Aucun paiement validé sur la période." />
                </Panneau>
            </div>

            <section className="mt-6 overflow-hidden rounded-brand border bg-white">
                <div className="flex items-center justify-between gap-3 border-b px-5 py-4">
                    <h2 className="text-base font-medium text-gray-900">Détail par résidence</h2>
                    <span className="text-sm text-muted">{nombre(statsParResidence.length)} résidence(s)</span>
                </div>
                <SimpleTable columns={colonnesResidences} rows={statsParResidence} rowKey={(s) => s.residence ?? 'na'} emptyMessage="Aucune donnée pour la période sélectionnée." />
                {statsParResidence.length > 0 && (
                    <Totaux>
                        <span>
                            Attendu : <strong className="font-medium tabular-nums">{fcfa(totauxResidences.attendu)}</strong>
                        </span>
                        <span>
                            Encaissé : <strong className="font-medium tabular-nums">{fcfa(totauxResidences.encaisse)}</strong>
                        </span>
                        <span>
                            Impayés : <strong className="font-medium tabular-nums">{fcfa(totauxResidences.impayes)}</strong>
                        </span>
                        <Badge variant={tauxGlobal >= 90 ? 'success' : 'warning'}>{nombre(tauxGlobal, 1)} % recouvré</Badge>
                    </Totaux>
                )}
            </section>

            <section className="mt-6 overflow-hidden rounded-brand border bg-white">
                <div className="flex items-center justify-between gap-3 border-b px-5 py-4">
                    <h2 className="text-base font-medium text-gray-900">Derniers paiements</h2>
                    {urls.index && (
                        <Link href={urls.index} className="text-sm font-medium text-primary hover:underline print:hidden">
                            Voir tous les paiements
                        </Link>
                    )}
                </div>
                <SimpleTable columns={colonnesDerniers} rows={derniersPaiements} rowKey={(p) => p.id} emptyMessage="Aucun paiement récent." />
            </section>
        </AdminLayout>
    );
}

/** Info-bulle « libellé · montant FCFA » ; `libelles` traduit les clés des séries (graphique d’évolution). */
const montantTooltip = (libelles: Record<string, string> = {}) => (valeur: unknown, nom: unknown, item: { color?: string }, _index: number, payload: unknown) => {
    const ligne = payload as { name?: string; fill?: string } | undefined;
    const libelle = libelles[String(nom)] ?? ligne?.name ?? String(nom);
    const couleur = item.color ?? ligne?.fill;
    return (
        <div className="flex w-full items-center justify-between gap-4">
            <span className="flex items-center gap-1.5 text-muted">
                <span className="size-2.5 rounded-[2px]" style={{ background: couleur }} aria-hidden />
                {libelle}
            </span>
            <span className="font-medium text-gray-900 tabular-nums">{fcfa(Number(valeur))}</span>
        </div>
    );
};

function Donut({ donnees, vide }: { donnees: { name: string; value: number; fill: string }[]; vide: string }) {
    const total = donnees.reduce((somme, item) => somme + item.value, 0);
    if (total <= 0) return <p className="flex h-60 items-center justify-center text-center text-sm text-muted">{vide}</p>;

    return (
        <div className="flex flex-col gap-4">
            <div className="relative mx-auto aspect-square h-52">
                <ChartContainer config={{}} className="aspect-square h-full">
                    <PieChart>
                        <ChartTooltip content={<ChartTooltipContent hideLabel nameKey="name" formatter={montantTooltip()} />} />
                        <Pie data={donnees} dataKey="value" nameKey="name" innerRadius="68%" outerRadius="95%" strokeWidth={3} stroke="#fff">
                            {donnees.map((item) => (
                                <Cell key={item.name} fill={item.fill} />
                            ))}
                        </Pie>
                    </PieChart>
                </ChartContainer>
                <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                    <strong className="text-lg leading-6 font-medium text-gray-900 tabular-nums">{fcfaCourt(total)}</strong>
                    <small className="text-xs text-muted">{symboleDevise()} au total</small>
                </div>
            </div>
            <ul className="space-y-1.5 text-sm">
                {donnees.map((item) => (
                    <li key={item.name} className="flex items-center justify-between gap-3">
                        <span className="flex items-center gap-2 text-muted">
                            <span className="size-2.5 rounded-full" style={{ background: item.fill }} aria-hidden />
                            {item.name}
                        </span>
                        <span className="font-medium tabular-nums">
                            {fcfa(item.value)} <span className="font-normal text-muted">· {nombre((item.value / total) * 100, 1)} %</span>
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function Panneau({ titre, extra, className, children }: { titre: string; extra?: ReactNode; className?: string; children: ReactNode }) {
    return (
        <section className={cn('rounded-brand border bg-white p-5', className)}>
            <div className="mb-4 flex items-center justify-between gap-3">
                <h2 className="text-base font-medium text-gray-900">{titre}</h2>
                {extra}
            </div>
            {children}
        </section>
    );
}

function Filtre({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex flex-col gap-1.5">
            <span className="text-xs text-muted">{label}</span>
            {children}
        </div>
    );
}

function Totaux({ children }: { children: ReactNode }) {
    return <div className="flex flex-col gap-2 border-t bg-surface px-5 py-3 text-sm sm:flex-row sm:flex-wrap sm:items-center sm:justify-end sm:gap-6">{children}</div>;
}
