import { router, usePage } from '@inertiajs/react';
import { Download, FileSpreadsheet, Printer, X } from 'lucide-react';
import type { VariantProps } from 'class-variance-authority';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { AdminLayout } from '@/components/layout/admin-layout';
import { Badge, type badgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import type { Paginated, SharedProps } from '@/types/global';

type Action = 'login' | 'logout' | 'create' | 'update' | 'delete' | 'action';

type LogRow = {
    id: string;
    date: string | null;
    actor: string;
    actor_type: string | null;
    action: string;
    action_label: string;
    ip: string | null;
    status: number | null;
};

type Filters = {
    actor_id: string;
    action: Action | '';
    date_from: string;
    date_to: string;
};

type ActivityLogsIndexProps = {
    logs: Paginated<LogRow>;
    filters: Filters;
    actors: { id: string; label: string }[];
    actions: Record<Action, string>;
    canViewAll: boolean;
};

type BadgeVariant = NonNullable<VariantProps<typeof badgeVariants>['variant']>;

const actionVariants: Record<string, BadgeVariant> = {
    login: 'success',
    create: 'success',
    update: 'info',
    delete: 'danger',
    logout: 'danger',
};

/** Code HTTP : 2xx/3xx réussite, 4xx avertissement, 5xx erreur. */
const statusVariant = (status: number): BadgeVariant => (status >= 500 ? 'danger' : status >= 400 ? 'warning' : 'success');

const initiales = (name: string) =>
    name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((word) => Array.from(word)[0] ?? '')
        .join('')
        .toUpperCase() || 'A';

export default function ActivityLogsIndex({ logs, filters, actors, actions, canViewAll }: ActivityLogsIndexProps) {
    const { errors } = usePage<SharedProps>().props;
    const filtered = Object.values(filters).some((value) => value !== '');

    const activeFilters = (values: Filters) => Object.fromEntries(Object.entries(values).filter(([, value]) => value !== ''));

    function applyFilters(changes: Partial<Filters>) {
        router.get(window.location.pathname, activeFilters({ ...filters, ...changes }), { preserveState: true, preserveScroll: true, replace: true });
    }

    /** URL d'export (Excel / impression) reprenant les filtres en cours, sans la page. */
    const exportUrl = (suffix: 'export' | 'impression') => {
        const query = new URLSearchParams(activeFilters(filters)).toString();
        return `${window.location.pathname.replace(/\/$/, '')}/${suffix}${query ? `?${query}` : ''}`;
    };

    const columns: DataTableColumn<LogRow>[] = [
        {
            key: 'date',
            header: 'Date et heure',
            headClassName: 'whitespace-nowrap',
            cell: (log) => <span className="font-medium whitespace-nowrap text-gray-900">{log.date ?? '—'}</span>,
        },
        ...(canViewAll
            ? [
                  {
                      key: 'actor',
                      header: 'Utilisateur',
                      cell: (log: LogRow) => (
                          <div className="flex items-center gap-3">
                              <span
                                  className="flex size-9 shrink-0 items-center justify-center rounded-full bg-soft text-xs font-semibold text-primary"
                                  aria-hidden
                              >
                                  {initiales(log.actor)}
                              </span>
                              <div className="min-w-0">
                                  <p className="truncate font-medium text-gray-900">{log.actor}</p>
                                  <p className="truncate text-xs text-muted">{log.actor_type ?? '—'}</p>
                              </div>
                          </div>
                      ),
                  },
              ]
            : []),
        {
            key: 'action',
            header: 'Action',
            cell: (log) => <Badge variant={actionVariants[log.action] ?? 'neutral'}>{log.action_label}</Badge>,
        },
        { key: 'ip', header: 'Adresse IP', cell: (log) => log.ip ?? <span className="text-muted">—</span> },
        {
            key: 'status',
            header: 'Résultat',
            cell: (log) => (log.status ? <Badge variant={statusVariant(log.status)}>{log.status}</Badge> : <span className="text-muted">—</span>),
        },
    ];

    return (
        <AdminLayout title="Journal d'activité">
            <div className="mb-6 flex flex-col gap-4">
                <div className="flex flex-col gap-1">
                    <h1 className="page-title text-xl font-medium text-gray-900">{canViewAll ? 'Activités de tous les utilisateurs' : 'Mes activités'}</h1>
                    <p className="text-sm text-muted">
                        {canViewAll ? "Suivi des opérations réalisées dans l'application." : 'Historique des opérations réalisées avec votre compte.'}
                    </p>
                </div>
                <Separator />
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="secondary"
                            className="self-end"
                            disabled={logs.total === 0}
                        >
                            <Download className="size-4" aria-hidden />
                            Exporter
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-48">
                        <DropdownMenuItem asChild className="cursor-pointer">
                            <a href={exportUrl('impression')} target="_blank" rel="noopener">
                                <Printer aria-hidden />
                                Imprimer
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild className="cursor-pointer">
                            <a href={exportUrl('export')} download>
                                <FileSpreadsheet aria-hidden />
                                Excel (.xlsx)
                            </a>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <DataTable
                columns={columns}
                data={logs}
                rowKey={(log) => log.id}
                emptyMessage={filtered ? 'Aucune activité trouvée pour ces critères.' : 'Aucune activité enregistrée.'}
                toolbar={
                    <div className="flex w-full flex-col gap-3">
                        <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                            {canViewAll && (
                                <Select value={filters.actor_id || 'all'} onValueChange={(value) => applyFilters({ actor_id: value === 'all' ? '' : value })}>
                                    <SelectTrigger aria-label="Filtrer par utilisateur" className="h-10 w-full sm:w-56">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Tous les utilisateurs</SelectItem>
                                        {actors.map((actor) => (
                                            <SelectItem key={actor.id} value={actor.id}>
                                                {actor.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                            <Select value={filters.action || 'all'} onValueChange={(value) => applyFilters({ action: value === 'all' ? '' : (value as Action) })}>
                                <SelectTrigger aria-label="Filtrer par action" className="h-10 w-full sm:w-44">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Toutes les actions</SelectItem>
                                    {Object.entries(actions).map(([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {/* « Du » et « Au » restent côte à côte, y compris sur petit écran. */}
                            <div className="grid grid-cols-2 gap-3 sm:flex sm:items-center">
                                <div className="flex min-w-0 items-center gap-2 text-sm text-muted">
                                    <span aria-hidden>Du</span>
                                    <DatePicker
                                        aria-label="Date de début"
                                        value={filters.date_from}
                                        max={filters.date_to || undefined}
                                        onChange={(value) => applyFilters({ date_from: value })}
                                        invalid={Boolean(errors.date_from)}
                                        className="h-10 min-w-0 sm:w-40"
                                    />
                                </div>
                                <div className="flex min-w-0 items-center gap-2 text-sm text-muted">
                                    <span aria-hidden>Au</span>
                                    <DatePicker
                                        aria-label="Date de fin"
                                        value={filters.date_to}
                                        min={filters.date_from || undefined}
                                        onChange={(value) => applyFilters({ date_to: value })}
                                        invalid={Boolean(errors.date_to)}
                                        className="h-10 min-w-0 sm:w-40"
                                    />
                                </div>
                            </div>
                            {filtered && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-10"
                                    onClick={() => router.get(window.location.pathname, {}, { preserveState: true, replace: true })}
                                >
                                    <X className="size-4" aria-hidden />
                                    Réinitialiser
                                </Button>
                            )}
                        </div>
                        {(errors.date_from || errors.date_to || errors.export) && (
                            <p role="alert" className="text-sm text-red-600">
                                {errors.date_from ?? errors.date_to ?? errors.export}
                            </p>
                        )}
                    </div>
                }
            />
        </AdminLayout>
    );
}
