import { Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    BedDouble,
    Building2,
    CalendarDays,
    ClipboardList,
    DoorOpen,
    KeyRound,
    LayoutGrid,
    Package,
    Printer,
    Settings2,
    Users,
    Wallet,
    type LucideIcon,
} from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { nombre, ReportChartCard, type ReportTable } from '@/components/reporting/report-charts';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { StatCard, type KpiTone } from '@/components/ui/stat-card';
import { cn } from '@/lib/utils';
import type { SharedProps } from '@/types/global';

type Periode = 'week' | 'month' | 'quarter' | 'year' | 'custom';

type Section = {
    title: string;
    note: string;
    unavailable: boolean;
    metrics: Record<string, number>;
    tables: ReportTable[];
};

type ReportingProps = {
    module: string;
    modules: { key: string; label: string; href: string }[];
    reports: { title: string; sections: Section[] }[];
    filters: { periode: Periode; debut: string; fin: string; cite: string | null };
    cites: { id: string; name: string }[];
    synthese: string;
    canExport: boolean;
    generatedAt: string;
};

const PERIODES: { value: Periode; label: string }[] = [
    { value: 'week', label: 'Cette semaine' },
    { value: 'month', label: 'Ce mois' },
    { value: 'quarter', label: 'Ce trimestre' },
    { value: 'year', label: 'Cette année' },
    { value: 'custom', label: 'Dates personnalisées' },
];

/** Icône et description de chaque module (clés de HousingReportingService::MODULES). */
const MODULES: Record<string, { icon: LucideIcon; description: string }> = {
    'parc-immobilier': { icon: Building2, description: 'Capacité, occupation, places libres et comparatif des cités.' },
    equipements: { icon: Package, description: 'Stock central, seuils d’alerte, entrées et sorties.' },
    campagnes: { icon: ClipboardList, description: 'Dossiers déposés, parcours, niveaux et taux de satisfaction.' },
    residents: { icon: Users, description: 'Résidents actifs, nouvelles affectations et contrats approuvés.' },
    'entree-logement': { icon: KeyRound, description: 'Notifications envoyées et rendez-vous d’entrée.' },
    'vie-cite': { icon: BedDouble, description: 'Maintenance, discipline et annonces.' },
    departs: { icon: DoorOpen, description: 'Demandes de sortie, états des lieux et décomptes.' },
    finances: { icon: Wallet, description: 'Loyers facturés, encaissements et paiements en ligne.' },
    'parametrage-herb': { icon: Settings2, description: 'Types de chambres, de cités et d’équipements.' },
};

/** Teintes des KPI dans un ordre fixe (voir components/ui/stat-card.tsx). */
const TONES: KpiTone[] = ['green', 'blue', 'amber', 'plum', 'neutral', 'red'];

const dateFr = (iso: string) => iso.split('-').reverse().join('/');

/** Le libellé porte l’unité (« … (F CFA) », « … (%) ») : elle passe dans la valeur. */
function metrique(label: string, value: number) {
    if (label.endsWith('(F CFA)')) return { label: label.replace(/\s*\(F CFA\)$/, ''), value: `${nombre.format(value)} F CFA`, percent: null };
    if (label.endsWith('(%)')) return { label: label.replace(/\s*\(%\)$/, ''), value: `${nombre.format(value)} %`, percent: value };
    return { label, value: nombre.format(value), percent: null };
}

export default function ReportingIndex({ module, modules, reports, filters, cites, synthese, canExport, generatedAt }: ReportingProps) {
    const { errors } = usePage<SharedProps>().props;
    const courant = modules.find((item) => item.key === module);
    const titre = courant?.label ?? 'Statistiques par module';
    const cite = cites.find((item) => item.id === filters.cite)?.name ?? 'Toutes les cités';

    return (
        <AdminLayout title={`Statistiques — ${courant?.label ?? 'Hébergement'}`}>
            <div className="flex max-w-[1600px] flex-col gap-5">
                <PageHeader
                    title={titre}
                    description={
                        <span className="flex flex-wrap items-center gap-2">
                            <CalendarDays {...ICON} className="size-4" />
                            Du {dateFr(filters.debut)} au {dateFr(filters.fin)}
                            <span className="h-3 w-px bg-gray-300" />
                            {cite}
                        </span>
                    }
                    actions={
                        module !== 'synthese' && canExport ? (
                            <Button variant="secondary" size="sm" className="h-10 px-4 print:hidden" onClick={() => window.print()}>
                                <Printer {...ICON} />
                                Imprimer / PDF
                            </Button>
                        ) : undefined
                    }
                />

                {Object.keys(errors).length > 0 && (
                    <div role="alert" className="flex gap-2 rounded-brand border border-[#F9D98A] bg-[#FEEBC0] p-3 text-sm text-[#6B3F00]">
                        <AlertTriangle {...ICON} className="shrink-0" />
                        <div>{Object.values(errors).map((error) => <p key={error}>{error}</p>)}</div>
                    </div>
                )}

                <Filtres filters={filters} cites={cites} />

                {module === 'synthese' ? (
                    <Annuaire modules={modules} />
                ) : (
                    <>
                        <nav aria-label="Statistiques par module" className="flex gap-2 overflow-x-auto pb-1 print:hidden">
                            <ModuleLink href={synthese} label="Tous les modules" icon={LayoutGrid} />
                            {modules.map((item) => (
                                <ModuleLink key={item.key} href={item.href} label={item.label} icon={MODULES[item.key]?.icon ?? LayoutGrid} active={item.key === module} />
                            ))}
                        </nav>

                        {reports.flatMap((report) => report.sections).map((section) => (
                            <SectionRapport key={section.title} section={section} />
                        ))}

                        <p className="text-xs text-muted">
                            Rapport généré le {generatedAt}. Les états et soldes correspondent aux données actuelles, même pour une période passée.
                        </p>
                    </>
                )}
            </div>
        </AdminLayout>
    );
}

/** Filtres GET (période, dates, cité) ; modifier une date bascule sur « Dates personnalisées ». */
function Filtres({ filters, cites }: Pick<ReportingProps, 'filters' | 'cites'>) {
    const [periode, setPeriode] = useState<Periode>(filters.periode);
    const [debut, setDebut] = useState(filters.debut);
    const [fin, setFin] = useState(filters.fin);
    const [cite, setCite] = useState(filters.cite ?? '');
    const [erreur, setErreur] = useState<string | null>(null);

    const appliquer = (event: FormEvent) => {
        event.preventDefault();
        if (periode === 'custom' && (!debut || !fin)) return setErreur('Renseignez les dates de début et de fin.');
        if (periode === 'custom' && fin < debut) return setErreur('La date de fin doit être postérieure ou égale à la date de début.');
        setErreur(null);
        router.get(
            window.location.pathname,
            { periode, ...(periode === 'custom' ? { debut, fin } : {}), ...(cite ? { cite } : {}) },
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <form onSubmit={appliquer} className="flex flex-col gap-3 rounded-md border bg-white p-4 print:hidden" noValidate>
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-[repeat(4,minmax(0,1fr))_auto] lg:items-end">
                <Champ label="Période" id="report-period">
                    <Select value={periode} onValueChange={(value) => setPeriode(value as Periode)}>
                        <SelectTrigger id="report-period" className="h-11">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {PERIODES.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Champ>
                <Champ label="Début" id="report-start">
                    <DatePicker
                        id="report-start"
                        value={debut}
                        max={fin}
                        invalid={!!erreur}
                        onChange={(value) => {
                            setDebut(value);
                            setPeriode('custom');
                        }}
                    />
                </Champ>
                <Champ label="Fin" id="report-end">
                    <DatePicker
                        id="report-end"
                        value={fin}
                        min={debut}
                        invalid={!!erreur}
                        onChange={(value) => {
                            setFin(value);
                            setPeriode('custom');
                        }}
                    />
                </Champ>
                <Champ label="Cité" id="report-cite">
                    <Select value={cite || 'all'} onValueChange={(value) => setCite(value === 'all' ? '' : value)}>
                        <SelectTrigger id="report-cite" className="h-11">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Toutes les cités</SelectItem>
                            {cites.map((item) => (
                                <SelectItem key={item.id} value={item.id}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Champ>
                <Button type="submit">Appliquer</Button>
            </div>
            {erreur && (
                <p role="alert" className="text-xs font-medium text-red-600">
                    {erreur}
                </p>
            )}
        </form>
    );
}

function Champ({ label, id, children }: { label: string; id: string; children: ReactNode }) {
    return (
        <div className="space-y-1.5">
            <label htmlFor={id} className="block text-sm font-medium text-gray-800">
                {label}
            </label>
            {children}
        </div>
    );
}

function ModuleLink({ href, label, icon: Icon, active = false }: { href: string; label: string; icon: LucideIcon; active?: boolean }) {
    return (
        <Link
            href={href}
            preserveScroll
            aria-current={active ? 'page' : undefined}
            className={cn(
                'flex shrink-0 items-center gap-2 rounded-brand border px-3 py-2 text-sm whitespace-nowrap transition-colors',
                active ? 'border-primary bg-primary text-white' : 'bg-white text-muted hover:border-primary hover:text-primary',
            )}
        >
            <Icon {...ICON} className="size-4" />
            {label}
        </Link>
    );
}

/** Page d’entrée : choix d’un module (aucun rapport n’est calculé tant qu’il n’est pas choisi). */
function Annuaire({ modules }: Pick<ReportingProps, 'modules'>) {
    if (modules.length === 0) {
        return (
            <div className="rounded-md border bg-white p-8 text-center text-sm text-muted">
                Aucun module de statistiques n’est accessible avec votre rôle.
            </div>
        );
    }

    return (
        <section aria-labelledby="choose-module" className="flex flex-col gap-3">
            <div>
                <h2 id="choose-module" className="font-semibold text-gray-900">
                    Choisissez un module
                </h2>
                <p className="text-sm text-muted">Les filtres sélectionnés sont conservés d’un module à l’autre.</p>
            </div>
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                {modules.map((item) => {
                    const meta = MODULES[item.key];
                    const Icon = meta?.icon ?? LayoutGrid;

                    return (
                        <Link
                            key={item.key}
                            href={item.href}
                            className="group flex items-start gap-4 rounded-md border bg-white p-5 transition-colors hover:border-primary hover:bg-[#F8FCF3] focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                        >
                            <span className="grid size-10 shrink-0 place-items-center rounded-brand bg-soft text-primary">
                                <Icon {...ICON} />
                            </span>
                            <span className="flex min-w-0 flex-1 flex-col gap-1">
                                <strong className="font-semibold text-gray-900">{item.label}</strong>
                                <small className="text-sm text-muted">{meta?.description}</small>
                                <span className="mt-2 flex items-center gap-1 text-sm font-medium text-primary">
                                    Consulter les statistiques
                                    <ArrowRight {...ICON} className="size-4 transition-transform group-hover:translate-x-0.5" />
                                </span>
                            </span>
                        </Link>
                    );
                })}
            </div>
        </section>
    );
}

function SectionRapport({ section }: { section: Section }) {
    const metrics = Object.entries(section.metrics).map(([label, value]) => metrique(label, value));
    // Un seul tableau prend toute la largeur ; deux ou plus se partagent la ligne.
    const pleine = section.tables.length === 1;

    return (
        <section className="flex flex-col gap-4 rounded-md border bg-white p-5 break-inside-avoid">
            <header>
                <h2 className="font-semibold text-gray-900">{section.title}</h2>
                <p className="mt-1 text-xs leading-5 text-muted">{section.note}</p>
            </header>

            {section.unavailable ? (
                <p role="status" className="flex gap-2 rounded-brand border border-[#F9D98A] bg-[#FEEBC0] p-3 text-sm text-[#6B3F00]">
                    <AlertTriangle {...ICON} className="shrink-0" />
                    Statistiques indisponibles : ce module n’est pas encore installé dans la base de données.
                </p>
            ) : (
                <>
                    {metrics.length > 0 && (
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                            {metrics.map((metric, index) => (
                                <StatCard key={metric.label} label={metric.label} value={metric.value} tone={TONES[index % TONES.length]}>
                                    {metric.percent !== null && (
                                        <div className="h-1.5 overflow-hidden rounded-full bg-white/70">
                                            <span className="block h-full rounded-full bg-current" style={{ width: `${Math.min(100, Math.max(0, metric.percent))}%` }} />
                                        </div>
                                    )}
                                </StatCard>
                            ))}
                        </div>
                    )}
                    {section.tables.length > 0 && (
                        <div className={cn('grid gap-4', !pleine && 'lg:grid-cols-2 2xl:grid-cols-3')}>
                            {section.tables.map((table) => (
                                <ReportChartCard key={table.title} table={table} className="bg-white" />
                            ))}
                        </div>
                    )}
                </>
            )}
        </section>
    );
}
