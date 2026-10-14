import { router } from "@inertiajs/react";
import {
    AlertTriangle,
    ArrowRight,
    BedDouble,
    Building2,
    CalendarDays,
    ChevronRight,
    Clock,
    type LucideIcon,
} from "lucide-react";
import type { CSSProperties, ReactNode } from "react";
import {
    Area,
    AreaChart,
    Bar,
    CartesianGrid,
    Cell,
    ComposedChart,
    Pie,
    PieChart,
    XAxis,
    YAxis,
} from "recharts";
import { AdminLayout } from "@/components/layout/admin-layout";
import { ICON } from "@/components/layout/app-sidebar";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from "@/components/ui/chart";
import { StatCard, type KpiTone } from "@/components/ui/stat-card";
import { cn } from "@/lib/utils";
import { Separator } from '@/components/ui/separator';

type Period = "week" | "month" | "quarter" | "year";

type DashboardProps = {
    access: {
        roleName: string;
        students: boolean;
        housing: boolean;
        parc: boolean;
        admissions: boolean;
        finance: boolean;
        maintenance: boolean;
    };
    period: Period;
    periodLabel: string;
    stats: {
        students: number;
        occupiedBeds: number;
        capacity: number;
        availableBeds: number;
        occupancyRate: number;
        revenue: number;
        revenueLabel: string | null;
        pendingRequests: number;
        openWorkOrders: number;
        urgentWorkOrders: number;
    };
    requestStatuses: { labels: string[]; values: number[] };
    trend: { labels: string[]; requests: number[]; payments: number[] };
    residences: {
        name: string;
        capacity: number;
        occupied: number;
        rate: number;
    }[];
    links: {
        parc: string | null;
        admissions: string | null;
        paiements: string | null;
        maintenance: string | null;
    };
};

const PERIODS: { value: Period; label: string }[] = [
    { value: "week", label: "Cette semaine" },
    { value: "month", label: "Ce mois" },
    { value: "quarter", label: "Ce trimestre" },
    { value: "year", label: "Cette année" },
];

/** Couleurs de la charte utilisées par les graphiques. */
const COLORS = {
    primary: "#006400",
    secondary: "#86BB46",
    accent: "#FAAB04",
    soft: "#EBF8DE",
    danger: "#DC2626",
};

const STATUS_COLORS = [
    COLORS.accent,
    COLORS.secondary,
    COLORS.primary,
    COLORS.danger,
];

const nombre = new Intl.NumberFormat("fr-FR");
const pourcentage = new Intl.NumberFormat("fr-FR", {
    maximumFractionDigits: 1,
});
const compact = new Intl.NumberFormat("fr-FR", {
    notation: "compact",
    maximumFractionDigits: 1,
});

function pluriel(n: number, mot: string): string {
    return `${n} ${mot}${n > 1 ? "s" : ""}`;
}

function changerPeriode(periode: string) {
    router.get(window.location.pathname, { periode }, { preserveScroll: true });
}

export default function DashboardIndex({
    access,
    period,
    periodLabel,
    stats,
    requestStatuses,
    trend,
    residences,
    links,
}: DashboardProps) {
    const showTrend = access.admissions || access.finance;
    const hasIndicators =
        access.housing ||
        access.admissions ||
        access.finance ||
        access.maintenance;
    const showAlerts = access.parc || access.admissions || access.maintenance;

    return (
        <AdminLayout title="Tableau de bord">
            <div className="flex max-w-[1600px] flex-col gap-4">
                <div className="flex flex-col gap-4">
                    <div>
                        <h1 className="page-title text-2xl font-medium text-gray-900">
                            Mon tableau de bord
                        </h1>
                        <p className="mt-1 text-sm text-muted">
                            Les indicateurs et les accès correspondant aux
                            permissions de votre rôle.
                        </p>
                    </div>
                    <Separator />
                    <Select value={period} onValueChange={changerPeriode}>
                        <SelectTrigger
                            aria-label="Choisir une période"
                            className="h-10 w-full border-primary bg-white font-semibold text-primary hover:bg-soft focus:ring-0 focus-visible:bg-soft data-[state=open]:bg-soft sm:w-auto sm:self-end [&_svg]:text-primary"
                        >
                            <span className="flex items-center gap-2">
                                <CalendarDays
                                    {...ICON}
                                    className="text-primary"
                                />
                                <SelectValue />
                            </span>
                        </SelectTrigger>
                        <SelectContent align="end">
                            {PERIODS.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <p className="flex items-center gap-2 text-sm text-muted">
                    <span className="size-2 rounded-full bg-secondary ring-4 ring-soft" />
                    Données actualisées
                    <span className="h-3 w-px bg-gray-300" />
                    {periodLabel}
                </p>

                <KpiSection access={access} stats={stats} trend={trend} />

                {!hasIndicators && (
                    <Card className="gap-2">
                        <CardHeader>
                            <CardTitle>
                                Aucun indicateur disponible pour votre rôle
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted">
                            Vos accès sont définis dans les rôles et permissions
                            par l’administrateur.
                        </CardContent>
                    </Card>
                )}

                {(showTrend || access.housing) && (
                    <div
                        className={cn(
                            "grid gap-4",
                            showTrend &&
                                access.housing &&
                                "xl:grid-cols-[minmax(0,1.8fr)_minmax(320px,0.8fr)]",
                        )}
                    >
                        {showTrend && (
                            <ActivityChart
                                trend={trend}
                                admissions={access.admissions}
                                finance={access.finance}
                            />
                        )}
                        {access.housing && <OccupancyChart stats={stats} />}
                    </div>
                )}

                <div className="grid gap-4 lg:grid-cols-2 2xl:grid-cols-[1.35fr_0.75fr_0.9fr]">
                    {access.housing && (
                        <ResidencesPanel
                            residences={residences}
                            parcUrl={links.parc}
                        />
                    )}
                    {access.admissions && (
                        <RequestsChart statuses={requestStatuses} />
                    )}
                    {showAlerts && (
                        <Panel
                            kicker="À surveiller"
                            title="Points d’attention"
                            className="lg:col-span-2 2xl:col-span-1"
                        >
                            <div className="divide-y">
                                {links.maintenance && (
                                    <AlertRow
                                        href={links.maintenance}
                                        icon={AlertTriangle}
                                        urgent={stats.urgentWorkOrders > 0}
                                        title={`${pluriel(stats.urgentWorkOrders, "intervention")} ${stats.urgentWorkOrders > 1 ? "urgentes" : "urgente"}`}
                                        subtitle="Maintenance technique"
                                    />
                                )}
                                {links.admissions && (
                                    <AlertRow
                                        href={links.admissions}
                                        icon={Clock}
                                        title={`${pluriel(stats.pendingRequests, "dossier")} à traiter`}
                                        subtitle="Campagnes et admissions"
                                    />
                                )}
                                {links.parc && (
                                    <AlertRow
                                        href={links.parc}
                                        icon={BedDouble}
                                        title={`${pluriel(stats.availableBeds, "place")} ${stats.availableBeds > 1 ? "disponibles" : "disponible"}`}
                                        subtitle="Capacité d’accueil actuelle"
                                    />
                                )}
                            </div>
                        </Panel>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}

type Kpi = {
    key: "students" | "housing" | "admissions" | "maintenance";
    label: string;
    value: string;
    extra?: string;
    highlight?: boolean;
};

/** KPI en cartes compactes (4 par ligne), la carte « Finances » à droite (dernière position). */
function KpiSection({
    access,
    stats,
    trend,
}: Pick<DashboardProps, "access" | "stats" | "trend">) {
    const kpis: Kpi[] = [];
    if (access.students) {
        kpis.push({
            key: "students",
            label: "Étudiants enregistrés",
            value: nombre.format(stats.students),
        });
    }
    if (access.housing) {
        kpis.push({
            key: "housing",
            label: "Résidents actifs",
            value: nombre.format(stats.occupiedBeds),
            extra: `${stats.occupancyRate}% d’occupation`,
        });
    }
    if (access.admissions) {
        kpis.push({
            key: "admissions",
            label: "Demandes à traiter",
            value: nombre.format(stats.pendingRequests),
            highlight: stats.pendingRequests > 0,
        });
    }
    if (access.maintenance) {
        kpis.push({
            key: "maintenance",
            label: "Interventions ouvertes",
            value: nombre.format(stats.openWorkOrders),
            extra: `${stats.urgentWorkOrders} ${stats.urgentWorkOrders > 1 ? "urgentes" : "urgente"}`,
            highlight: stats.urgentWorkOrders > 0,
        });
    }

    if (kpis.length === 0 && !access.finance) {
        return null;
    }

    // Jusqu’à 4 cartes par ligne ; au-delà, elles passent à la ligne suivante.
    const columns = Math.min(kpis.length, 4);

    return (
        <div
            className={cn(
                "grid gap-3",
                kpis.length > 0 &&
                    access.finance &&
                    "xl:grid-cols-[minmax(0,1fr)_minmax(300px,0.4fr)]",
            )}
        >
            {kpis.length > 0 && (
                <div
                    className="grid gap-3 sm:grid-cols-2 lg:grid-cols-[repeat(var(--kpi-cols),minmax(0,1fr))]"
                    style={{ "--kpi-cols": columns } as CSSProperties}
                >
                    {kpis.map((kpi) => (
                        <StatCard
                            key={kpi.key}
                            label={kpi.label}
                            value={kpi.value}
                            extra={kpi.extra}
                            highlight={kpi.highlight}
                            tone={KPI_TONE[kpi.key]}
                        />
                    ))}
                </div>
            )}
            {access.finance && (
                <FinanceCard value={stats.revenueLabel ?? "—"} trend={trend} />
            )}
        </div>
    );
}

/** Teinte de chaque indicateur (voir `components/ui/stat-card.tsx`). */
const KPI_TONE: Record<Kpi["key"], KpiTone> = {
    students: "blue",
    housing: "green",
    admissions: "amber",
    maintenance: "plum",
};

function FinanceCard({
    value,
    trend,
}: {
    value: string;
    trend: DashboardProps["trend"];
}) {
    const data = trend.labels.map((label, index) => ({
        label,
        encaissements: trend.payments[index] ?? 0,
    }));
    const config = {
        encaissements: { label: "Encaissements", color: COLORS.accent },
    } satisfies ChartConfig;

    return (
        <article className="flex items-center gap-4 rounded-md border border-primary bg-primary p-3.5 text-white">
            <div className="flex shrink-0 flex-col gap-2">
                <small className="flex items-center gap-2 text-xs leading-4 font-medium">
                    Encaissements
                    <span className="rounded bg-[#FAAB04] px-1.5 py-0.5 font-semibold text-[#1F2328]">
                        Finances
                    </span>
                </small>
                <strong className="text-lg leading-5 font-bold">{value}</strong>
            </div>
            <ChartContainer
                config={config}
                className="aspect-auto h-11 min-w-0 flex-1"
            >
                <AreaChart
                    data={data}
                    margin={{ left: 0, right: 0, top: 2, bottom: 0 }}
                >
                    <defs>
                        <linearGradient
                            id="finance-fill"
                            x1="0"
                            y1="0"
                            x2="0"
                            y2="1"
                        >
                            <stop
                                offset="0%"
                                stopColor="var(--color-encaissements)"
                                stopOpacity={0.5}
                            />
                            <stop
                                offset="100%"
                                stopColor="var(--color-encaissements)"
                                stopOpacity={0}
                            />
                        </linearGradient>
                    </defs>
                    <XAxis dataKey="label" hide />
                    <ChartTooltip
                        cursor={false}
                        content={<ChartTooltipContent indicator="dot" />}
                    />
                    <Area
                        type="monotone"
                        dataKey="encaissements"
                        stroke="var(--color-encaissements)"
                        strokeWidth={2}
                        fill="url(#finance-fill)"
                    />
                </AreaChart>
            </ChartContainer>
        </article>
    );
}

function Panel({
    kicker,
    title,
    action,
    className,
    children,
}: {
    kicker: string;
    title: string;
    action?: ReactNode;
    className?: string;
    children: ReactNode;
}) {
    return (
        <section
            className={cn("min-w-0 rounded-md border bg-white p-5", className)}
        >
            <div className="mb-4 flex items-start justify-between gap-4">
                <div>
                    <p className="text-xs font-semibold tracking-wider text-secondary uppercase">
                        {kicker}
                    </p>
                    <h2 className="mt-1 font-semibold text-gray-900">
                        {title}
                    </h2>
                </div>
                {action}
            </div>
            {children}
        </section>
    );
}

function ActivityChart({
    trend,
    admissions,
    finance,
}: {
    trend: DashboardProps["trend"];
    admissions: boolean;
    finance: boolean;
}) {
    const data = trend.labels.map((label, index) => ({
        label,
        demandes: trend.requests[index] ?? 0,
        encaissements: trend.payments[index] ?? 0,
    }));
    const config = {
        demandes: { label: "Demandes", color: COLORS.secondary },
        encaissements: { label: "Encaissements", color: COLORS.primary },
    } satisfies ChartConfig;
    const title =
        admissions && finance
            ? "Évolution des demandes et encaissements"
            : finance
              ? "Évolution des encaissements"
              : "Évolution des demandes";

    return (
        <Panel kicker="Activité" title={title}>
            <ChartContainer config={config} className="aspect-auto h-80 w-full">
                <ComposedChart
                    data={data}
                    margin={{ left: 0, right: 0, top: 8 }}
                >
                    <CartesianGrid vertical={false} strokeDasharray="4 4" />
                    <XAxis
                        dataKey="label"
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                        minTickGap={16}
                    />
                    {admissions && (
                        <YAxis
                            yAxisId="demandes"
                            allowDecimals={false}
                            tickLine={false}
                            axisLine={false}
                            width={32}
                        />
                    )}
                    {finance && (
                        <YAxis
                            yAxisId="encaissements"
                            orientation={admissions ? "right" : "left"}
                            tickLine={false}
                            axisLine={false}
                            width={48}
                            tickFormatter={(value: number) =>
                                `${compact.format(value)} F`
                            }
                        />
                    )}
                    <ChartTooltip
                        content={<ChartTooltipContent indicator="dot" />}
                    />
                    <ChartLegend content={<ChartLegendContent />} />
                    {finance && (
                        <Bar
                            yAxisId="encaissements"
                            dataKey="encaissements"
                            fill="var(--color-encaissements)"
                            radius={[4, 4, 0, 0]}
                            maxBarSize={28}
                        />
                    )}
                    {admissions && (
                        <Area
                            yAxisId="demandes"
                            type="monotone"
                            dataKey="demandes"
                            stroke="var(--color-demandes)"
                            strokeWidth={2}
                            fill="var(--color-demandes)"
                            fillOpacity={0.15}
                        />
                    )}
                </ComposedChart>
            </ChartContainer>
        </Panel>
    );
}

function Donut({
    data,
    config,
    center,
    caption,
    centerColor,
}: {
    data: { name: string; value: number; fill: string }[];
    config: ChartConfig;
    center: string;
    caption: string;
    centerColor?: string;
}) {
    return (
        // Recharts 3 n’affiche plus les <Label> enfants de <Pie> : le texte central est superposé en HTML.
        <div className="relative mx-auto aspect-square h-60">
            <ChartContainer config={config} className="aspect-square h-full">
                <PieChart>
                    <ChartTooltip
                        content={
                            <ChartTooltipContent hideLabel nameKey="name" />
                        }
                    />
                    <Pie
                        data={data}
                        dataKey="value"
                        nameKey="name"
                        innerRadius="70%"
                        outerRadius="95%"
                        strokeWidth={3}
                        stroke="#fff"
                    >
                        {data.map((entry) => (
                            <Cell key={entry.name} fill={entry.fill} />
                        ))}
                    </Pie>
                </PieChart>
            </ChartContainer>
            <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                <strong
                    className="text-2xl leading-7 font-bold text-gray-900"
                    style={centerColor ? { color: centerColor } : undefined}
                >
                    {center}
                </strong>
                <small className="text-xs text-muted">{caption}</small>
            </div>
        </div>
    );
}

function OccupancyChart({ stats }: { stats: DashboardProps["stats"] }) {
    // Avec une capacité nulle, une part grise évite un anneau vide.
    const data =
        stats.capacity > 0
            ? [
                  {
                      name: "Occupés",
                      value: stats.occupiedBeds,
                      fill: COLORS.accent,
                  },
                  {
                      name: "Disponibles",
                      value: stats.availableBeds,
                      fill: COLORS.soft,
                  },
              ]
            : [{ name: "Aucun lit", value: 1, fill: "#E5E7EB" }];

    return (
        <Panel kicker="Capacité" title="Occupation globale">
            <Donut
                data={data}
                config={{}}
                center={`${pourcentage.format(stats.occupancyRate)} %`}
                caption="Taux d’occupation"
                centerColor={stats.capacity > 0 ? COLORS.accent : undefined}
            />
            <div className="mt-2 grid grid-cols-3 border-t pt-4 text-sm">
                <Legend
                    color={COLORS.accent}
                    value={stats.occupiedBeds}
                    label="Occupés"
                />
                <Legend
                    color={COLORS.soft}
                    value={stats.availableBeds}
                    label="Disponibles"
                />
                <Legend
                    color={COLORS.secondary}
                    value={stats.capacity}
                    label="Capacité"
                />
            </div>
        </Panel>
    );
}

function Legend({
    color,
    value,
    label,
}: {
    color: string;
    value: number;
    label: string;
}) {
    return (
        <div className="relative pl-3">
            <span
                className="absolute top-0.5 left-0 h-4 w-1 rounded"
                style={{ background: color }}
            />
            <strong className="block text-gray-900">
                {nombre.format(value)}
            </strong>
            <small className="text-muted">{label}</small>
        </div>
    );
}

function RequestsChart({
    statuses,
}: {
    statuses: DashboardProps["requestStatuses"];
}) {
    const total = statuses.values.reduce((sum, value) => sum + value, 0);
    const data =
        total > 0
            ? statuses.labels.map((name, index) => ({
                  name,
                  value: statuses.values[index] ?? 0,
                  fill: STATUS_COLORS[index] ?? COLORS.primary,
              }))
            : [{ name: "Aucune demande", value: 1, fill: "#E5E7EB" }];

    return (
        <Panel kicker="Admissions" title="Répartition des demandes">
            <Donut
                data={data}
                config={{}}
                center={nombre.format(total)}
                caption="demandes au total"
            />
            <ul className="mt-2 grid grid-cols-2 gap-2 text-sm">
                {statuses.labels.map((label, index) => (
                    <li
                        key={label}
                        className="flex items-center gap-2 text-muted"
                    >
                        <span
                            className="size-2.5 rounded-full"
                            style={{ background: STATUS_COLORS[index] }}
                        />
                        {label}
                        <strong className="ml-auto text-gray-900">
                            {statuses.values[index] ?? 0}
                        </strong>
                    </li>
                ))}
            </ul>
        </Panel>
    );
}

function ResidencesPanel({
    residences,
    parcUrl,
}: {
    residences: DashboardProps["residences"];
    parcUrl: string | null;
}) {
    return (
        <Panel
            kicker="Hébergement"
            title="Occupation par cité"
            action={
                parcUrl && (
                    <a
                        href={parcUrl}
                        className="flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                    >
                        Voir le parc <ArrowRight {...ICON} className="size-4" />
                    </a>
                )
            }
        >
            {residences.length === 0 ? (
                <div className="flex flex-col items-center gap-2 py-10 text-sm text-muted">
                    <Building2 size={24} strokeWidth={1.25} />
                    Aucune cité configurée pour le moment.
                </div>
            ) : (
                <div className="divide-y">
                    {residences.map((residence) => (
                        <div
                            key={residence.name}
                            className="flex items-center gap-4 py-2.5"
                        >
                            <div className="flex min-w-40 items-center gap-2.5">
                                <span className="grid size-8 place-items-center rounded-md bg-soft text-xs font-bold text-primary uppercase">
                                    {residence.name.slice(0, 2)}
                                </span>
                                <div className="min-w-0">
                                    <strong className="block truncate text-sm text-gray-900">
                                        {residence.name}
                                    </strong>
                                    <small className="text-muted">
                                        {residence.occupied} /{" "}
                                        {residence.capacity} lits
                                    </small>
                                </div>
                            </div>
                            <div className="flex flex-1 items-center gap-2">
                                <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-soft">
                                    <span
                                        className="block h-full rounded-full bg-primary"
                                        style={{ width: `${residence.rate}%` }}
                                    />
                                </div>
                                <strong className="w-10 text-right text-sm text-gray-900">
                                    {residence.rate}%
                                </strong>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </Panel>
    );
}

function AlertRow({
    href,
    icon: Icon,
    title,
    subtitle,
    urgent = false,
}: {
    href: string;
    icon: LucideIcon;
    title: string;
    subtitle: string;
    urgent?: boolean;
}) {
    return (
        <a href={href} className="group flex items-center gap-3 py-3">
            <span
                className={cn(
                    "grid size-8 place-items-center rounded-md",
                    urgent ? "bg-red-50 text-red-600" : "bg-surface text-muted",
                )}
            >
                <Icon {...ICON} />
            </span>
            <div className="min-w-0 flex-1">
                <strong className="block text-sm text-gray-900">{title}</strong>
                <small className="text-muted">{subtitle}</small>
            </div>
            <ChevronRight
                {...ICON}
                className="text-muted group-hover:text-primary"
            />
        </a>
    );
}
