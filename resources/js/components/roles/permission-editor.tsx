import { Boxes, Building2, ChevronRight, History, LayoutDashboard, type LucideIcon, Search, Settings, Users } from 'lucide-react';
import { useMemo, useState } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { cn } from '@/lib/utils';

export type ModulePermission = {
    id: number;
    action: string;
    label: string;
    description: string | null;
};

/** Nœud de l'arbre des modules (RolePermissionController::serializeNode). */
export type ModuleNode = {
    id: number;
    slug: string;
    name: string;
    permissions: ModulePermission[];
    children: ModuleNode[];
};

/** Icônes Lucide des grands modules (les modules stockent des classes Font Awesome, non utilisées ici). */
const MODULE_ICONS: Record<string, LucideIcon> = {
    dashboard: LayoutDashboard,
    utilisateurs: Users,
    hebergement: Building2,
    parametrage: Settings,
    activity: History,
};

export const moduleIcon = (slug: string): LucideIcon => MODULE_ICONS[slug] ?? Boxes;

/** Couleur d’étiquette par type d’action : toutes les actions identiques partagent la même couleur. */
export const ACTION_COLORS: Record<string, string> = {
    view: 'border-sky-200 bg-sky-50 text-sky-800',
    personal: 'border-sky-200 bg-sky-50 text-sky-800',
    documents: 'border-sky-200 bg-sky-50 text-sky-800',
    history: 'border-sky-200 bg-sky-50 text-sky-800',
    create: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    update: 'border-amber-200 bg-amber-50 text-amber-800',
    delete: 'border-red-200 bg-red-50 text-red-700',
    validate: 'border-teal-200 bg-teal-50 text-teal-800',
    assign: 'border-violet-200 bg-violet-50 text-violet-800',
    export: 'border-indigo-200 bg-indigo-50 text-indigo-800',
    configure: 'border-slate-300 bg-slate-100 text-slate-700',
    transmit: 'border-cyan-200 bg-cyan-50 text-cyan-800',
    close: 'border-rose-200 bg-rose-50 text-rose-800',
};

/** Case cochée dans la teinte de son action (mêmes regroupements que ACTION_COLORS). */
const ACTION_CHECKBOX_COLORS: Record<string, string> = {
    view: 'data-[state=checked]:border-sky-600 data-[state=checked]:bg-sky-600',
    personal: 'data-[state=checked]:border-sky-600 data-[state=checked]:bg-sky-600',
    documents: 'data-[state=checked]:border-sky-600 data-[state=checked]:bg-sky-600',
    history: 'data-[state=checked]:border-sky-600 data-[state=checked]:bg-sky-600',
    create: 'data-[state=checked]:border-emerald-600 data-[state=checked]:bg-emerald-600',
    update: 'data-[state=checked]:border-amber-500 data-[state=checked]:bg-amber-500',
    delete: 'data-[state=checked]:border-red-600 data-[state=checked]:bg-red-600',
    validate: 'data-[state=checked]:border-teal-600 data-[state=checked]:bg-teal-600',
    assign: 'data-[state=checked]:border-violet-600 data-[state=checked]:bg-violet-600',
    export: 'data-[state=checked]:border-indigo-600 data-[state=checked]:bg-indigo-600',
    configure: 'data-[state=checked]:border-slate-600 data-[state=checked]:bg-slate-600',
    transmit: 'data-[state=checked]:border-cyan-600 data-[state=checked]:bg-cyan-600',
    close: 'data-[state=checked]:border-rose-600 data-[state=checked]:bg-rose-600',
};

/** Toutes les permissions d'un nœud et de ses descendants, dans l'ordre d'affichage. */
export function collectPermissions(node: ModuleNode, depth = 0): (ModulePermission & { depth: number })[] {
    return [...node.permissions.map((permission) => ({ ...permission, depth })), ...node.children.flatMap((child) => collectPermissions(child, depth + 1))];
}

const normalize = (value: string) =>
    value
        .toLocaleLowerCase('fr')
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '');

function nodeMatches(node: ModuleNode, term: string): boolean {
    return normalize(node.name).includes(term) || node.permissions.some((permission) => normalize(permission.label).includes(term)) || node.children.some((child) => nodeMatches(child, term));
}

type PermissionEditorProps = {
    tree: ModuleNode[];
    selected: Set<number>;
    onChange: (next: Set<number>) => void;
    /** Lecture seule (rôle super-administrateur, absence de droit de modification). */
    disabled?: boolean;
};

/**
 * Éditeur des droits d'un rôle : un onglet par grand module, puis ses sous-modules repliables.
 * Mêmes règles que l'ancienne vue : décocher un module retire tous ses droits, le cocher
 * accorde le droit d'entrée (« Consulter » du module, sinon le premier consulter/gérer/configurer).
 */
export function PermissionEditor({ tree, selected, onChange, disabled = false }: PermissionEditorProps) {
    const [search, setSearch] = useState('');
    const term = normalize(search.trim());

    const totals = useMemo(() => new Map(tree.map((root) => [root.id, collectPermissions(root)])), [tree]);

    function toggle(ids: number[], checked: boolean) {
        const next = new Set(selected);
        ids.forEach((id) => (checked ? next.add(id) : next.delete(id)));
        onChange(next);
    }

    function toggleModule(root: ModuleNode, checked: boolean) {
        const permissions = totals.get(root.id) ?? [];
        if (!checked) {
            toggle(
                permissions.map((permission) => permission.id),
                false,
            );
            return;
        }
        const entry =
            permissions.find((permission) => permission.depth === 0 && permission.action === 'view') ?? permissions.find((permission) => ['view', 'manage', 'configure'].includes(permission.action));
        if (entry) toggle([entry.id], true);
    }

    if (tree.length === 0) {
        return <p className="rounded-brand border border-dashed px-4 py-8 text-center text-sm text-muted">Aucun module actif.</p>;
    }

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div className="w-full sm:max-w-sm">
                    <Input
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Rechercher un sous-module ou une action…"
                        aria-label="Rechercher un sous-module ou une action"
                        icon={<Search />}
                        className="h-10"
                    />
                </div>
                <p className="text-sm text-muted" aria-live="polite">
                    <span className="font-medium text-primary">{selected.size}</span> droit(s) sélectionné(s)
                </p>
            </div>

            <Tabs defaultValue={String(tree[0].id)} className="gap-4">
                <TabsList aria-label="Grands modules" className="flex-wrap gap-2 overflow-visible border-b-0">
                    {tree.map((root) => {
                        const Icon = moduleIcon(root.slug);
                        const permissions = totals.get(root.id) ?? [];
                        const count = permissions.filter((permission) => selected.has(permission.id)).length;

                        return (
                            <TabsTrigger
                                key={root.id}
                                value={String(root.id)}
                                className="mb-0 h-10 rounded-brand border border-gray-200 bg-white px-3 data-[state=active]:border-secondary data-[state=active]:bg-soft"
                            >
                                <Icon {...ICON} aria-hidden />
                                {root.name}
                                <span className={cn('rounded px-1.5 text-xs tabular-nums', count > 0 ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-muted')}>
                                    {count}/{permissions.length}
                                </span>
                            </TabsTrigger>
                        );
                    })}
                </TabsList>

                {tree.map((root) => {
                    const permissions = totals.get(root.id) ?? [];
                    const count = permissions.filter((permission) => selected.has(permission.id)).length;
                    const children = term ? root.children.filter((child) => nodeMatches(child, term)) : root.children;
                    const rootPermissions = term && !normalize(root.name).includes(term) ? root.permissions.filter((permission) => normalize(permission.label).includes(term)) : root.permissions;

                    return (
                        <TabsContent key={root.id} value={String(root.id)} className="space-y-4">
                            <div className="flex flex-col gap-3 rounded-brand border bg-surface/60 p-3 sm:flex-row sm:items-center sm:justify-between">
                                <label className={cn('flex items-center gap-2.5 text-sm font-medium text-gray-900', !disabled && 'cursor-pointer')}>
                                    <Checkbox
                                        checked={count === 0 ? false : count === permissions.length ? true : 'indeterminate'}
                                        onCheckedChange={(checked) => toggleModule(root, checked !== false && count === 0)}
                                        disabled={disabled || permissions.length === 0}
                                    />
                                    Autoriser le module {root.name}
                                </label>
                                <button
                                    type="button"
                                    disabled={disabled || permissions.length === 0}
                                    onClick={() =>
                                        toggle(
                                            permissions.map((permission) => permission.id),
                                            count !== permissions.length,
                                        )
                                    }
                                    className="h-9 cursor-pointer rounded-brand border border-primary/30 bg-white px-3 text-sm text-primary transition-colors hover:bg-soft disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {count === permissions.length && permissions.length > 0 ? 'Tout retirer' : 'Tout sélectionner'}
                                </button>
                            </div>
                            <p className="text-xs text-muted">Décochez le module pour retirer tous ses droits ; cochez les actions nécessaires pour définir les accès.</p>

                            {rootPermissions.length > 0 && (
                                <div className="space-y-2">
                                    <h4 className="text-xs tracking-wide text-muted uppercase">Actions du module</h4>
                                    <PermissionGrid permissions={rootPermissions} selected={selected} disabled={disabled} onToggle={toggle} />
                                </div>
                            )}

                            {children.length > 0 && (
                                <div className="space-y-2">
                                    {children.map((child) => (
                                        <PermissionNode key={child.id} node={child} selected={selected} disabled={disabled} onToggle={toggle} forceOpen={Boolean(term)} />
                                    ))}
                                </div>
                            )}

                            {term && rootPermissions.length === 0 && children.length === 0 && (
                                <p className="rounded-brand border border-dashed px-4 py-6 text-center text-sm text-muted">Aucun sous-module ni action ne correspond à « {search.trim()} ».</p>
                            )}
                        </TabsContent>
                    );
                })}
            </Tabs>
        </div>
    );
}

type NodeProps = {
    selected: Set<number>;
    disabled: boolean;
    onToggle: (ids: number[], checked: boolean) => void;
};

function PermissionGrid({ permissions, selected, disabled, onToggle }: NodeProps & { permissions: ModulePermission[] }) {
    return (
        <div className="grid grid-cols-2 gap-1.5 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            {permissions.map((permission) => {
                const checked = selected.has(permission.id);

                return (
                    <label
                        key={permission.id}
                        title={permission.description ?? undefined}
                        className={cn(
                            'flex items-center gap-2 rounded-brand border px-2.5 py-1.5 text-sm transition-colors',
                            checked ? (ACTION_COLORS[permission.action] ?? 'border-secondary/60 bg-soft text-gray-900') : 'border-gray-200 bg-white text-muted',
                            !disabled && 'cursor-pointer',
                            !disabled && !checked && 'hover:border-gray-300 hover:text-gray-900',
                        )}
                    >
                        <Checkbox checked={checked} disabled={disabled} className={ACTION_CHECKBOX_COLORS[permission.action]} onCheckedChange={(value) => onToggle([permission.id], value === true)} />
                        <span className="min-w-0 truncate" title={permission.description ?? permission.label}>{permission.label}</span>
                    </label>
                );
            })}
        </div>
    );
}

function PermissionNode({ node, selected, disabled, onToggle, forceOpen }: NodeProps & { node: ModuleNode; forceOpen: boolean }) {
    const [open, setOpen] = useState(false);
    const expanded = open || forceOpen;
    const permissions = collectPermissions(node);
    const count = permissions.filter((permission) => selected.has(permission.id)).length;

    return (
        <div className="rounded-brand border bg-white">
            <div className="flex items-center gap-3 px-3 py-2.5">
                <Checkbox
                    aria-label={`Tous les droits de ${node.name}`}
                    checked={count === 0 ? false : count === permissions.length ? true : 'indeterminate'}
                    disabled={disabled || permissions.length === 0}
                    onCheckedChange={() =>
                        onToggle(
                            permissions.map((permission) => permission.id),
                            count !== permissions.length,
                        )
                    }
                />
                <button
                    type="button"
                    onClick={() => setOpen(!expanded)}
                    aria-expanded={expanded}
                    className="flex min-w-0 flex-1 cursor-pointer items-center gap-2 text-left text-sm text-gray-900 hover:text-primary focus-visible:outline-none"
                >
                    <ChevronRight {...ICON} className={cn('shrink-0 text-muted transition-transform', expanded && 'rotate-90')} aria-hidden />
                    <span className="truncate">{node.name}</span>
                    <span className="ml-auto shrink-0 text-xs text-muted tabular-nums">
                        {count}/{permissions.length}
                    </span>
                </button>
            </div>

            {expanded && (
                <div className="space-y-2 border-t px-3 py-3">
                    {node.permissions.length > 0 && <PermissionGrid permissions={node.permissions} selected={selected} disabled={disabled} onToggle={onToggle} />}
                    {node.children.map((child) => (
                        <PermissionNode key={child.id} node={child} selected={selected} disabled={disabled} onToggle={onToggle} forceOpen={forceOpen} />
                    ))}
                    {node.permissions.length === 0 && node.children.length === 0 && <p className="text-xs text-muted">Aucune action pour ce sous-module.</p>}
                </div>
            )}
        </div>
    );
}
