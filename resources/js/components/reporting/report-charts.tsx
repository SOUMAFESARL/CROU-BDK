import { BarChart3, Table2 } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { Area, AreaChart, Bar, BarChart, CartesianGrid, Cell, LabelList, Pie, PieChart, XAxis, YAxis } from 'recharts';
import { ICON } from '@/components/layout/app-sidebar';
import { ChartContainer, ChartLegend, ChartLegendContent, ChartTooltip, ChartTooltipContent, type ChartConfig } from '@/components/ui/chart';
import { cn } from '@/lib/utils';

export type ReportTable = {
    kind?: 'distribution' | 'monthly' | 'comparison';
    title: string;
    headers: string[];
    rows: (string | number)[][];
};

/**
 * Palette catégorielle de la charte, dans un ordre fixe (jamais recyclé) :
 * vert, orange, bleu, vert clair, prune, rouge brique. Validée (bande de luminance, daltonisme ΔE ≥ 13) ;
 * l’orange et le vert clair ont un contraste < 3:1, d’où les valeurs toujours affichées en légende et la vue tableau.
 */
export const SERIES = ['#006400', '#E39A00', '#2F6DB5', '#6FA332', '#9C3D74', '#D9480F'];
const OTHER = '#A3A8AE';

export const nombre = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 });
const compact = new Intl.NumberFormat('fr-FR', { notation: 'compact', maximumFractionDigits: 1 });
const moisCourt = new Intl.DateTimeFormat('fr-FR', { month: 'short', year: '2-digit' });
const moisLong = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });

/** Codes techniques (`en_attente`, `hors_service`) → libellé lisible (« En attente »). */
export function libelle(value: string | number): string {
    const text = String(value).replace(/_/g, ' ').trim();
    return text.charAt(0).toUpperCase() + text.slice(1);
}

/** `aaaa-mm` → date du 1er du mois, ou null si le format est inattendu. */
function mois(value: string): Date | null {
    const match = /^(\d{4})-(\d{2})$/.exec(value);
    return match ? new Date(Number(match[1]), Number(match[2]) - 1, 1) : null;
}

const cellule = (value: string | number, index: number, kind?: ReportTable['kind']) => {
    if (typeof value === 'number') return nombre.format(value);
    if (kind === 'monthly' && index === 0) {
        const date = mois(value);
        return date ? libelle(moisLong.format(date)) : value;
    }
    return kind === 'distribution' && index === 0 ? libelle(value) : value;
};

/** Carte d’un graphique avec bascule vers la vue tableau (accessibilité, impression, valeurs exactes). */
export function ReportChartCard({ table, className }: { table: ReportTable; className?: string }) {
    const [vue, setVue] = useState<'graphique' | 'tableau'>('graphique');
    const vide = table.rows.length === 0;

    return (
        <section className={cn('flex min-w-0 flex-col rounded-md border bg-white p-4', className)}>
            <header className="mb-3 flex items-center justify-between gap-3">
                <h4 className="text-sm font-semibold text-gray-900">{table.title}</h4>
                {!vide && (
                    <div className="flex shrink-0 rounded-brand border bg-surface p-0.5 print:hidden" role="group" aria-label={`Affichage : ${table.title}`}>
                        <ToggleButton active={vue === 'graphique'} onClick={() => setVue('graphique')} label="Graphique">
                            <BarChart3 {...ICON} className="size-4" />
                        </ToggleButton>
                        <ToggleButton active={vue === 'tableau'} onClick={() => setVue('tableau')} label="Tableau">
                            <Table2 {...ICON} className="size-4" />
                        </ToggleButton>
                    </div>
                )}
            </header>
            {vide ? (
                <div className="flex flex-1 flex-col items-center justify-center gap-2 rounded-brand bg-surface py-10 text-sm text-muted">
                    <BarChart3 {...ICON} />
                    Aucune donnée pour cette sélection.
                </div>
            ) : vue === 'tableau' ? (
                <DataTable table={table} />
            ) : (
                <>
                    <Chart table={table} />
                    {/* À l’impression, les valeurs exactes accompagnent le graphique. */}
                    <div className="hidden print:block">
                        <DataTable table={table} />
                    </div>
                </>
            )}
        </section>
    );
}

function ToggleButton({ active, onClick, label, children }: { active: boolean; onClick: () => void; label: string; children: ReactNode }) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={active}
            title={label}
            aria-label={label}
            className={cn(
                'grid h-7 w-8 cursor-pointer place-items-center rounded-sm text-muted transition-colors',
                active ? 'bg-white text-primary shadow-sm' : 'hover:text-primary',
            )}
        >
            {children}
        </button>
    );
}

function DataTable({ table }: { table: ReportTable }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead>
                    <tr className="border-b text-left text-xs text-muted">
                        {table.headers.map((header, index) => (
                            <th key={header} scope="col" className={cn('px-2 py-2 font-medium', index > 0 && 'text-right')}>
                                {header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {table.rows.map((row, rowIndex) => (
                        <tr key={rowIndex} className="border-b last:border-0 hover:bg-surface">
                            {row.map((cell, index) => (
                                <td key={index} className={cn('px-2 py-2', index > 0 ? 'text-right tabular-nums text-gray-900' : 'text-gray-700')}>
                                    {cellule(cell, index, table.kind)}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function Chart({ table }: { table: ReportTable }) {
    if (table.kind === 'monthly') return <MonthlyChart table={table} />;
    if (table.kind === 'comparison') return <ComparisonChart table={table} />;
    return table.rows.length <= SERIES.length ? <DonutChart table={table} /> : <RankingChart table={table} />;
}

/** Évolution mensuelle : aire pour une tendance (≥ 3 mois), barres sinon. Une seule série, sans légende. */
function MonthlyChart({ table }: { table: ReportTable }) {
    const montant = table.headers[1]?.includes('F CFA');
    const config = { total: { label: montant ? 'Montant (F CFA)' : 'Nombre', color: SERIES[0] } } satisfies ChartConfig;
    const data = table.rows.map(([month, total]) => {
        const date = mois(String(month));
        return {
            mois: date ? libelle(moisCourt.format(date)) : String(month),
            detail: date ? libelle(moisLong.format(date)) : String(month),
            total: Number(total),
        };
    });
    const axes = (
        <>
            <CartesianGrid vertical={false} strokeDasharray="3 3" />
            <XAxis dataKey="mois" tickLine={false} axisLine={false} tickMargin={8} minTickGap={16} />
            <YAxis tickLine={false} axisLine={false} width={44} allowDecimals={false} tickFormatter={(value: number) => compact.format(value)} />
            <ChartTooltip cursor={data.length >= 3} content={<ChartTooltipContent indicator="line" labelFormatter={(_, payload) => payload?.[0]?.payload?.detail} />} />
        </>
    );

    return (
        <ChartContainer config={config} className="aspect-auto h-64 w-full">
            {data.length >= 3 ? (
                <AreaChart data={data} margin={{ left: 0, right: 8, top: 8 }}>
                    <defs>
                        <linearGradient id={`fill-${table.title.replace(/\W/g, '')}`} x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stopColor="var(--color-total)" stopOpacity={0.35} />
                            <stop offset="100%" stopColor="var(--color-total)" stopOpacity={0.02} />
                        </linearGradient>
                    </defs>
                    {axes}
                    <Area
                        type="monotone"
                        dataKey="total"
                        stroke="var(--color-total)"
                        strokeWidth={2}
                        fill={`url(#fill-${table.title.replace(/\W/g, '')})`}
                        dot={{ r: 3, strokeWidth: 2, fill: '#fff' }}
                        activeDot={{ r: 5 }}
                    />
                </AreaChart>
            ) : (
                <BarChart data={data} margin={{ left: 0, right: 8, top: 20 }}>
                    {axes}
                    <Bar dataKey="total" fill="var(--color-total)" radius={[4, 4, 0, 0]} maxBarSize={56}>
                        <LabelList dataKey="total" position="top" className="fill-gray-700 text-xs" formatter={(value) => compact.format(Number(value))} />
                    </Bar>
                </BarChart>
            )}
        </ChartContainer>
    );
}

/** Répartition en anneau (≤ 6 parts) avec total au centre et légende chiffrée. */
function DonutChart({ table }: { table: ReportTable }) {
    const total = table.rows.reduce((sum, row) => sum + Number(row[1]), 0);
    const data = table.rows.map(([name, value], index) => ({ name: libelle(name), value: Number(value), fill: SERIES[index] }));

    return (
        <div className="flex flex-1 flex-col items-center gap-4 sm:flex-row">
            {/* Recharts 3 n’affiche plus les <Label> enfants de <Pie> : le total central est superposé en HTML. */}
            <div className="relative aspect-square w-44 shrink-0">
                <ChartContainer config={{}} className="aspect-square h-full">
                    <PieChart>
                        <ChartTooltip content={<ChartTooltipContent hideLabel nameKey="name" />} />
                        <Pie data={data} dataKey="value" nameKey="name" innerRadius="66%" outerRadius="96%" paddingAngle={data.length > 1 ? 2 : 0} cornerRadius={4} stroke="#fff" strokeWidth={2}>
                            {data.map((entry) => (
                                <Cell key={entry.name} fill={entry.fill} />
                            ))}
                        </Pie>
                    </PieChart>
                </ChartContainer>
                <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                    <strong className="text-xl leading-6 font-bold text-gray-900 tabular-nums">{nombre.format(total)}</strong>
                    <small className="text-xs text-muted">au total</small>
                </div>
            </div>
            <ul className="w-full min-w-0 flex-1 space-y-2 text-sm">
                {data.map((entry) => (
                    <li key={entry.name} className="flex items-center gap-2">
                        <span className="size-2.5 shrink-0 rounded-sm" style={{ background: entry.fill }} />
                        <span className="min-w-0 flex-1 truncate text-gray-700" title={entry.name}>
                            {entry.name}
                        </span>
                        <strong className="text-gray-900 tabular-nums">{nombre.format(entry.value)}</strong>
                        <span className="w-12 text-right text-xs text-muted tabular-nums">{total ? `${nombre.format((100 * entry.value) / total)} %` : '—'}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

/** Répartition longue : barres horizontales classées, les plus petites regroupées dans « Autres ». */
function RankingChart({ table }: { table: ReportTable }) {
    const max = 8;
    const rows = table.rows.map(([name, value]) => ({ name: libelle(name), value: Number(value), fill: SERIES[0] }));
    const data = rows.length > max ? [...rows.slice(0, max - 1), { name: 'Autres', value: rows.slice(max - 1).reduce((sum, row) => sum + row.value, 0), fill: OTHER }] : rows;
    const config = { value: { label: 'Nombre', color: SERIES[0] } } satisfies ChartConfig;

    return (
        <ChartContainer config={config} className="aspect-auto w-full" style={{ height: Math.max(160, data.length * 36 + 16) }}>
            <BarChart data={data} layout="vertical" margin={{ left: 0, right: 40, top: 0, bottom: 0 }} barCategoryGap={6}>
                <XAxis type="number" hide />
                <YAxis type="category" dataKey="name" tickLine={false} axisLine={false} width={132} tickFormatter={(value: string) => (value.length > 18 ? `${value.slice(0, 17)}…` : value)} />
                <ChartTooltip cursor={{ fill: 'var(--color-soft, #EBF8DE)' }} content={<ChartTooltipContent hideIndicator />} />
                <Bar dataKey="value" radius={[0, 4, 4, 0]} maxBarSize={22}>
                    {data.map((entry) => (
                        <Cell key={entry.name} fill={entry.fill} />
                    ))}
                    <LabelList dataKey="value" position="right" className="fill-gray-700 text-xs" formatter={(value) => nombre.format(Number(value))} />
                </Bar>
            </BarChart>
        </ChartContainer>
    );
}

/** Comparatif des cités : capacité et places libres côte à côte (même unité, un seul axe). */
function ComparisonChart({ table }: { table: ReportTable }) {
    const config = {
        capacite: { label: table.headers[2] ?? 'Capacité', color: SERIES[0] },
        libres: { label: table.headers[3] ?? 'Places libres', color: SERIES[1] },
    } satisfies ChartConfig;
    const data = table.rows.map(([name, , capacite, libres]) => ({ name: String(name), capacite: Number(capacite), libres: Number(libres) }));

    return (
        <ChartContainer config={config} className="aspect-auto h-64 w-full">
            <BarChart data={data} margin={{ left: 0, right: 8, top: 8 }} barGap={2}>
                <CartesianGrid vertical={false} strokeDasharray="3 3" />
                <XAxis dataKey="name" tickLine={false} axisLine={false} tickMargin={8} interval={0} tickFormatter={(value: string) => (value.length > 14 ? `${value.slice(0, 13)}…` : value)} />
                <YAxis tickLine={false} axisLine={false} width={40} allowDecimals={false} />
                <ChartTooltip cursor={{ fill: '#F8F8F8' }} content={<ChartTooltipContent indicator="dot" />} />
                <ChartLegend content={<ChartLegendContent />} />
                <Bar dataKey="capacite" fill="var(--color-capacite)" radius={[4, 4, 0, 0]} maxBarSize={36} />
                <Bar dataKey="libres" fill="var(--color-libres)" radius={[4, 4, 0, 0]} maxBarSize={36} />
            </BarChart>
        </ChartContainer>
    );
}
