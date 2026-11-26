import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import type { ReactNode } from 'react';
import { Bar, CartesianGrid, Cell, ComposedChart, Line, Pie, PieChart, XAxis, YAxis } from 'recharts';
import type { DataTableColumn } from '@/components/data-table/data-table';
import { SimpleTable } from '@/components/data-table/simple-table';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { fcfa, fcfaCourt } from '@/components/paiements/shared';
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

/** `campagne` : identifiant ou `toutes` ; `mois` : mois de loyer de la campagne (`aaaa-mm`), vide pour toute la campagne. */
type Filtres = { campagne: string; mois: string; residence: string };

type Props = {
    stats: {
        /** Loyers facturés de la période (mois de l'échéance). */
        total_attendu: number;
        /** Part payée de ces loyers : base du taux de recouvrement. */
        total_recouvre: number;
        /** Paiements validés de la période, tous types (loyer, avance, caution…). */
        total_encaisse: number;
        total_impayes: number;
        total_retard: number;
        nb_retard: number;
        nb_impayes: number;
        nb_loyers: number;
        nb_paiements: number;
        taux_recouvrement: number;
    };
    evolution: { label: string; encaisse: number; attendu: number }[];
    /** Loyers de la période : payé, reste dû pas encore échu, reste dû en retard. */
    repartition: { paye: number; a_echoir: number; retard: number };
    statsParMode: Mode[];
    residences: { id: string; name: string }[];
    campagnes: { id: string; libelle: string; active: boolean }[];
    /** Mois de loyer de la campagne choisie (calendrier des loyers) ; vide pour « Toutes les campagnes ». */
    mois: { value: string; label: string }[];
    filters: Filtres;
    urls: { rapport: string; index: string | null };
};

const COULEURS = { primary: '#006400', secondary: '#86BB46', accent: '#FAAB04', danger: '#DC2626' };
const TOUS = 'tous';
/** Valeur du filtre « Campagne » pour toutes les campagnes (comme la page Paiements). */
const TOUTES_CAMPAGNES = 'toutes';

/**
 * Rapport financier (App\Services\RapportFinancier) : attendu, recouvré, impayés et retards sur les loyers facturés ;
 * total encaissé, modes de paiement et évolution sur les paiements validés, tous types confondus.
 */
export default function RapportFinancier({ stats, evolution, repartition, statsParMode, residences, campagnes, mois, filters, urls }: Props) {
    const campagneChoisie = campagnes.find((campagne) => campagne.id === filters.campagne);

    // Changer de campagne remet le mois à « Tous les mois » (les mois dépendent de la campagne).
    const filtrer = (cle: keyof Filtres, valeur: string) => {
        const suivants = { ...filters, [cle]: valeur === TOUS ? '' : valeur, ...(cle === 'campagne' ? { mois: '' } : {}) };
        router.get(urls.rapport, Object.fromEntries(Object.entries(suivants).filter(([, v]) => v)), { preserveState: true, preserveScroll: true, replace: true });
    };

    const totalModes = statsParMode.reduce((somme, mode) => ({ nombre: somme.nombre + mode.nombre, total: somme.total + mode.total }), { nombre: 0, total: 0 });

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

    const configEvolution = {
        encaisse: { label: 'Montants encaissés', color: COULEURS.primary },
        attendu: { label: 'Montants attendus', color: COULEURS.accent },
    } satisfies ChartConfig;

    const donneesRepartition = [
        { name: 'Payé', value: repartition.paye, fill: COULEURS.primary },
        { name: 'À échoir', value: repartition.a_echoir, fill: COULEURS.accent },
        { name: 'En retard', value: repartition.retard, fill: COULEURS.danger },
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

            {/* Cité, campagne puis mois de cette campagne : la page est rechargée à chaque changement. */}
            <section aria-label="Période du rapport" className="mb-6 grid grid-cols-1 gap-3 rounded-brand border bg-white p-4 sm:grid-cols-3 print:hidden">
                <Filtre label="Cité">
                    <Select value={filters.residence || TOUS} onValueChange={(valeur) => filtrer('residence', valeur)}>
                        <SelectTrigger className="h-10" aria-label="Cité">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={TOUS}>Toutes les cités</SelectItem>
                            {residences.map((residence) => (
                                <SelectItem key={residence.id} value={residence.id}>
                                    {residence.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Filtre>
                <Filtre label="Campagne">
                    <Select value={filters.campagne} onValueChange={(valeur) => filtrer('campagne', valeur)}>
                        <SelectTrigger className="h-10" aria-label="Campagne">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={TOUTES_CAMPAGNES}>Toutes les campagnes</SelectItem>
                            {campagnes.map((campagne) => (
                                <SelectItem key={campagne.id} value={campagne.id}>
                                    {campagne.libelle}
                                    {campagne.active ? ' (active)' : ''}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Filtre>
                <Filtre label="Mois">
                    <Select value={filters.mois || TOUS} onValueChange={(valeur) => filtrer('mois', valeur)} disabled={mois.length === 0}>
                        <SelectTrigger className="h-10" aria-label="Mois">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={TOUS}>{mois.length === 0 ? 'Choisissez une campagne' : 'Tous les mois de la campagne'}</SelectItem>
                            {mois.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Filtre>
            </section>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <StatCard tone="green" label="Total encaissé" value={fcfa(stats.total_encaisse)} />
                <StatCard tone="blue" label="Total attendu" value={fcfa(stats.total_attendu)} extra={`${nombre(stats.nb_loyers)} loyer(s)`} />
                <StatCard tone="blue" label="Taux de recouvrement" value={`${nombre(stats.taux_recouvrement, 1)} %`} highlight={stats.taux_recouvrement < 95} />
                <StatCard tone="amber" label="Impayés" value={fcfa(stats.total_impayes)} extra={`${nombre(stats.nb_impayes)} loyer(s) impayé(s)`} highlight={stats.total_impayes > 0} />
                <StatCard tone="amber" label="Retards" value={fcfa(stats.total_retard)} extra={`${nombre(stats.nb_retard)} loyer(s) en retard`} />
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Panneau titre="Évolution des paiements" extra={<Badge variant="success">{campagneChoisie?.libelle ?? 'Toutes les campagnes'}</Badge>} className="lg:col-span-2">
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
                <Panneau titre="Répartition des loyers">
                    <Donut donnees={donneesRepartition} vide="Aucun loyer sur la période." />
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
