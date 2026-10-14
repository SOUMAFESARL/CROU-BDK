import { router } from '@inertiajs/react';
import { Check, ChevronRight, CircleAlert, Crown, Loader2, Network, Plus, Save, Search, ShieldCheck, Trash2, Undo2, UserCog, Users, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import { z } from 'zod';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { ICON } from '@/components/layout/app-sidebar';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ACTION_COLORS, collectPermissions, moduleIcon, type ModuleNode, PermissionEditor } from '@/components/roles/permission-editor';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { StatCard } from '@/components/ui/stat-card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type RoleRow = {
    id: string;
    name: string;
    usersCount: number;
    isSuperAdmin: boolean;
    editable: boolean;
    permissionIds: number[];
    updateUrl: string;
    destroyUrl: string | null;
};

type UserRow = {
    id: string;
    name: string;
    matricule: string | null;
    campus: string | null;
    role_id: string | null;
    role: string | null;
    statut: Statut;
    assignUrl: string | null;
};

type Statut = 'active' | 'inactive';
type AssignmentFilters = { search: string; statut: Statut | '' };

type RolesPermissionsProps = {
    roles: RoleRow[];
    moduleTree: ModuleNode[];
    permissionsCount: number;
    users: Paginated<UserRow> | null;
    usersTotal: number;
    filters: AssignmentFilters;
    can: {
        create: boolean;
        update: boolean;
        delete: boolean;
        assignments: boolean;
        assign: boolean;
    };
    storeUrl: string | null;
    modulesUrl: string | null;
};

type Onglet = 'roles' | 'modules' | 'affectations';

/** Même contrainte que la validation Laravel (`required|string|max:225|unique`). */
const roleNameSchema = (roles: RoleRow[], ignoreId?: string) =>
    z
        .string()
        .trim()
        .min(1, 'Le nom du rôle est obligatoire.')
        .max(225, 'Le nom du rôle ne doit pas dépasser 225 caractères.')
        .refine((name) => !roles.some((role) => role.id !== ignoreId && role.name.toLocaleLowerCase('fr') === name.toLocaleLowerCase('fr')), 'Un rôle porte déjà ce nom.');

const firstError = (errors: Record<string, string>) => Object.values(errors)[0];

const initiales = (name: string) =>
    name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((word) => Array.from(word)[0] ?? '')
        .join('')
        .toUpperCase() || 'U';

/** Onglet courant conservé dans l'URL (`?onglet=`) : la pagination des affectations recharge la page. */
function readOnglet(canAssignments: boolean): Onglet {
    const value = new URLSearchParams(window.location.search).get('onglet');
    if (value === 'modules' || (value === 'affectations' && canAssignments)) return value;
    return 'roles';
}

export default function RolesPermissionsIndex(props: RolesPermissionsProps) {
    const { roles, moduleTree, permissionsCount, users, usersTotal, can } = props;
    const [onglet, setOnglet] = useState<Onglet>(() => readOnglet(can.assignments));
    const [selectedId, setSelectedId] = useState<string | null>(roles[0]?.id ?? null);
    const [creating, setCreating] = useState(false);

    const selectedRole = roles.find((role) => role.id === selectedId) ?? roles[0] ?? null;

    function changeOnglet(value: string) {
        setOnglet(value as Onglet);
        const url = new URL(window.location.href);
        if (value === 'roles') url.searchParams.delete('onglet');
        else url.searchParams.set('onglet', value);
        window.history.replaceState(window.history.state, '', url);
    }

    return (
        <AdminLayout title="Rôles et permissions">
            <div className="mb-6 flex flex-col gap-4">
                <div className="flex flex-col gap-1">
                    <h1 className="page-title text-xl font-medium text-gray-900">Rôles et permissions</h1>
                    <p className="text-sm text-muted">Choisissez un rôle, puis définissez ses accès par module et sous-module.</p>
                </div>
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <StatCard tone="blue" label="Rôles" value={roles.length} />
                    <StatCard tone="green" label="Permissions" value={permissionsCount} />
                    {can.assignments && <StatCard tone="amber" label="Utilisateurs" value={usersTotal} />}
                </div>
            </div>

            <Tabs value={onglet} onValueChange={changeOnglet}>
                <TabsList>
                    <TabsTrigger value="roles">
                        <ShieldCheck {...ICON} aria-hidden />
                        Rôles
                    </TabsTrigger>
                    <TabsTrigger value="modules">
                        <Network {...ICON} aria-hidden />
                        Modules et actions
                    </TabsTrigger>
                    {can.assignments && (
                        <TabsTrigger value="affectations">
                            <UserCog {...ICON} aria-hidden />
                            Affectations utilisateurs
                        </TabsTrigger>
                    )}
                </TabsList>

                <TabsContent value="roles">
                    <div className="grid grid-cols-1 items-start gap-4 lg:grid-cols-[280px_minmax(0,1fr)] xl:grid-cols-[320px_minmax(0,1fr)]">
                        <RoleList roles={roles} selectedId={selectedRole?.id ?? null} onSelect={setSelectedId} onCreate={props.storeUrl ? () => setCreating(true) : null} />

                        {selectedRole ? (
                            <RoleEditor
                                // Remonté à chaque changement de rôle ou après enregistrement : repart des droits du serveur.
                                key={`${selectedRole.id}:${selectedRole.name}:${selectedRole.permissionIds.join(',')}`}
                                role={selectedRole}
                                roles={roles}
                                tree={moduleTree}
                                onDeleted={() => setSelectedId(null)}
                            />
                        ) : (
                            <div className="rounded-brand border border-dashed bg-white px-6 py-16 text-center text-sm text-muted">Aucun rôle. Créez le premier rôle pour commencer.</div>
                        )}
                    </div>
                </TabsContent>

                <TabsContent value="modules">
                    <ModuleCatalog tree={moduleTree} modulesUrl={props.modulesUrl} />
                </TabsContent>

                {can.assignments && users && (
                    <TabsContent value="affectations">
                        <Assignments users={users} roles={roles} filters={props.filters} canAssign={can.assign} />
                    </TabsContent>
                )}
            </Tabs>

            {props.storeUrl && (
                <CreateRoleDialog
                    open={creating}
                    onOpenChange={setCreating}
                    storeUrl={props.storeUrl}
                    roles={roles}
                    tree={moduleTree}
                    onCreated={(name, nextRoles) => setSelectedId(nextRoles.find((role) => role.name === name)?.id ?? null)}
                />
            )}
        </AdminLayout>
    );
}

/* ───────────────────────── Onglet « Rôles » ───────────────────────── */

function RoleList({ roles, selectedId, onSelect, onCreate }: { roles: RoleRow[]; selectedId: string | null; onSelect: (id: string) => void; onCreate: (() => void) | null }) {
    return (
        <div className="rounded-brand border bg-white lg:sticky lg:top-20">
            <div className="flex items-center justify-between gap-2 border-b px-4 py-3">
                <h2 className="text-sm font-medium text-gray-900">
                    Rôles <span className="text-muted">({roles.length})</span>
                </h2>
                {onCreate && (
                    <Button size="sm" onClick={onCreate}>
                        <Plus className="size-4" aria-hidden />
                        Nouveau rôle
                    </Button>
                )}
            </div>
            {roles.length === 0 ? (
                <p className="px-4 py-8 text-center text-sm text-muted">Aucun rôle.</p>
            ) : (
                <ul className="max-h-[60vh] space-y-1 overflow-y-auto p-2" aria-label="Rôles">
                    {roles.map((role) => {
                        const active = role.id === selectedId;

                        return (
                            <li key={role.id}>
                                <button
                                    type="button"
                                    onClick={() => onSelect(role.id)}
                                    aria-current={active || undefined}
                                    className={cn(
                                        'flex w-full cursor-pointer items-center gap-3 rounded-brand px-3 py-2.5 text-left transition-colors focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                                        active ? 'bg-soft text-primary' : 'text-gray-900 hover:bg-surface',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'flex size-9 shrink-0 items-center justify-center rounded-brand',
                                            role.isSuperAdmin ? 'bg-amber-50 text-amber-700' : active ? 'bg-white text-primary' : 'bg-soft text-primary',
                                        )}
                                        aria-hidden
                                    >
                                        {role.isSuperAdmin ? <Crown {...ICON} /> : <ShieldCheck {...ICON} />}
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className={cn('block truncate text-sm', active && 'font-medium')}>{role.name}</span>
                                        <span className="block truncate text-xs text-muted">
                                            {role.usersCount} utilisateur(s) · {role.permissionIds.length} droit(s)
                                        </span>
                                    </span>
                                    <ChevronRight {...ICON} className={cn('shrink-0', active ? 'text-primary' : 'text-gray-300')} aria-hidden />
                                </button>
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}

function RoleEditor({ role, roles, tree, onDeleted }: { role: RoleRow; roles: RoleRow[]; tree: ModuleNode[]; onDeleted: () => void }) {
    const allIds = useMemo(() => tree.flatMap((root) => collectPermissions(root).map((permission) => permission.id)), [tree]);
    const initial = useMemo(() => new Set(role.isSuperAdmin ? allIds : role.permissionIds), [role, allIds]);
    const [name, setName] = useState(role.name);
    const [selected, setSelected] = useState<Set<number>>(initial);
    const [nameError, setNameError] = useState<string | undefined>();
    const [saving, setSaving] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const readOnly = !role.editable;
    const dirty = name !== role.name || selected.size !== initial.size || [...selected].some((id) => !initial.has(id));
    const deleteBlocker = role.isSuperAdmin ? 'Le rôle super-administrateur est protégé.' : role.usersCount > 0 ? 'Retirez d’abord ce rôle des utilisateurs.' : null;

    function save() {
        const parsed = roleNameSchema(roles, role.id).safeParse(name);
        if (!parsed.success) {
            setNameError(parsed.error.issues[0]?.message);
            return;
        }
        router.put(
            role.updateUrl,
            { name: parsed.data, permissions: [...selected] },
            {
                preserveState: true,
                preserveScroll: true,
                onStart: () => setSaving(true),
                onFinish: () => setSaving(false),
                onError: (errors) => (errors.name ? setNameError(errors.name) : toast.error(firstError(errors))),
            },
        );
    }

    function destroy() {
        if (!role.destroyUrl) return;
        router.delete(role.destroyUrl, {
            preserveState: true,
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setConfirmDelete(false);
            },
            onSuccess: (page) => {
                if (!(page.props as unknown as RolesPermissionsProps).roles.some((item) => item.id === role.id)) onDeleted();
            },
            onError: (errors) => toast.error(firstError(errors)),
        });
    }

    return (
        <form
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                save();
            }}
            className="min-w-0 rounded-brand border bg-white"
        >
            <div className="space-y-5 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="w-full sm:max-w-md">
                        <Field id="role-name" label="Nom du rôle" required error={nameError}>
                            <Input
                                id="role-name"
                                value={name}
                                onChange={(event) => {
                                    setName(event.target.value);
                                    setNameError(undefined);
                                }}
                                disabled={readOnly}
                                invalid={!!nameError}
                                aria-describedby={nameError ? 'role-name-error' : undefined}
                                maxLength={225}
                            />
                        </Field>
                    </div>
                    <div className="flex flex-wrap gap-2 sm:justify-end sm:pt-7">
                        <Badge variant="success">
                            <Users className="size-3.5" aria-hidden />
                            {role.usersCount} utilisateur(s)
                        </Badge>
                        {readOnly && <Badge variant="neutral">Lecture seule</Badge>}
                    </div>
                </div>

                {role.isSuperAdmin && (
                    <p className="flex items-start gap-3 rounded-brand border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <Crown {...ICON} className="mt-0.5 shrink-0 text-amber-700" aria-hidden />
                        <span>
                            <span className="block font-medium">Super-administrateur</span>
                            Ce rôle possède automatiquement tous les droits actuels et futurs.
                        </span>
                    </p>
                )}

                <PermissionEditor tree={tree} selected={selected} onChange={setSelected} disabled={readOnly || role.isSuperAdmin} />
            </div>

            <div className="sticky bottom-0 flex flex-col-reverse gap-2 rounded-b-brand border-t bg-white/95 px-4 py-3 backdrop-blur sm:flex-row sm:items-center md:px-6">
                {role.destroyUrl && (
                    <Tooltip>
                        <TooltipTrigger asChild>
                            {/* L'info-bulle reste disponible quand le bouton est désactivé. */}
                            <span tabIndex={deleteBlocker ? 0 : -1} className="sm:mr-auto">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="h-10 w-full border-red-200 text-red-600 hover:bg-red-50 sm:w-auto"
                                    disabled={deleteBlocker !== null}
                                    onClick={() => setConfirmDelete(true)}
                                >
                                    <Trash2 className="size-4" aria-hidden />
                                    Supprimer
                                </Button>
                            </span>
                        </TooltipTrigger>
                        {deleteBlocker && <TooltipContent sideOffset={4}>{deleteBlocker}</TooltipContent>}
                    </Tooltip>
                )}
                {!readOnly && (
                    <>
                        {dirty && <span className="text-xs text-amber-700 sm:ml-auto">Modifications non enregistrées</span>}
                        {dirty && (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-10"
                                onClick={() => {
                                    setName(role.name);
                                    setSelected(initial);
                                    setNameError(undefined);
                                }}
                            >
                                <Undo2 className="size-4" aria-hidden />
                                Annuler
                            </Button>
                        )}
                        <Button type="submit" size="sm" className={cn('h-10', !dirty && 'sm:ml-auto')} disabled={!dirty || saving}>
                            {saving ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Save className="size-4" aria-hidden />}
                            Enregistrer
                        </Button>
                    </>
                )}
            </div>

            <AlertDialog open={confirmDelete} onOpenChange={setConfirmDelete}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Supprimer ce rôle ?</AlertDialogTitle>
                        <AlertDialogDescription>Le rôle « {role.name} » et ses permissions seront supprimés définitivement.</AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel asChild>
                            <Button variant="outline" size="sm" className="h-10">
                                Annuler
                            </Button>
                        </AlertDialogCancel>
                        <AlertDialogAction asChild>
                            <Button
                                size="sm"
                                className="h-10 bg-red-600 hover:bg-red-700"
                                disabled={deleting}
                                onClick={(event) => {
                                    event.preventDefault();
                                    destroy();
                                }}
                            >
                                {deleting && <Loader2 className="size-4 animate-spin" aria-hidden />}
                                Supprimer
                            </Button>
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </form>
    );
}

/* ─────────────────────── Création d'un rôle (2 étapes) ─────────────────────── */

const STEPS = ['Nom du rôle', 'Permissions'] as const;

function CreateRoleDialog({
    open,
    onOpenChange,
    storeUrl,
    roles,
    tree,
    onCreated,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    storeUrl: string;
    roles: RoleRow[];
    tree: ModuleNode[];
    onCreated: (name: string, roles: RoleRow[]) => void;
}) {
    const [step, setStep] = useState(0);
    const [name, setName] = useState('');
    const [nameError, setNameError] = useState<string | undefined>();
    const [selected, setSelected] = useState<Set<number>>(new Set());
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (open) {
            setStep(0);
            setName('');
            setNameError(undefined);
            setSelected(new Set());
        }
    }, [open]);

    function validateName() {
        const parsed = roleNameSchema(roles).safeParse(name);
        if (!parsed.success) {
            setNameError(parsed.error.issues[0]?.message);
            return null;
        }
        return parsed.data;
    }

    function submit() {
        const validName = validateName();
        if (validName === null) {
            setStep(0);
            return;
        }
        router.post(
            storeUrl,
            { name: validName, permissions: [...selected] },
            {
                preserveState: true,
                preserveScroll: true,
                onStart: () => setSubmitting(true),
                onFinish: () => setSubmitting(false),
                onSuccess: (page) => {
                    onOpenChange(false);
                    onCreated(validName, (page.props as unknown as RolesPermissionsProps).roles);
                },
                onError: (errors) => {
                    if (errors.name) {
                        setNameError(errors.name);
                        setStep(0);
                    } else {
                        toast.error(firstError(errors));
                    }
                },
            },
        );
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-3xl" aria-describedby="new-role-description">
                <DialogHeader>
                    <DialogTitle>Nouveau rôle</DialogTitle>
                    <DialogDescription id="new-role-description">Nommez le rôle, puis choisissez ses droits d’accès.</DialogDescription>
                    <ol className="mt-3 flex items-center gap-2" aria-label="Étapes">
                        {STEPS.map((label, index) => {
                            const done = index < step;
                            const current = index === step;

                            return (
                                <li key={label} className="flex items-center gap-2" aria-current={current ? 'step' : undefined}>
                                    <span
                                        className={cn(
                                            'flex size-6 items-center justify-center rounded-full text-xs',
                                            done ? 'bg-primary text-white' : current ? 'bg-soft text-primary ring-1 ring-secondary' : 'bg-gray-100 text-muted',
                                        )}
                                    >
                                        {done ? <Check className="size-3.5" aria-hidden /> : index + 1}
                                    </span>
                                    <span className={cn('text-sm', current ? 'text-gray-900' : 'text-muted')}>{label}</span>
                                    {index < STEPS.length - 1 && <span className="mx-1 h-px w-8 bg-gray-200" aria-hidden />}
                                </li>
                            );
                        })}
                    </ol>
                </DialogHeader>

                <form
                    noValidate
                    className="flex min-h-0 flex-1 flex-col"
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (step === 0) {
                            if (validateName() !== null) setStep(1);
                        } else {
                            submit();
                        }
                    }}
                >
                    <DialogBody>
                        {step === 0 ? (
                            <Field id="new-role-name" label="Nom du rôle" required error={nameError} hint="Ex. Chef service logement, Comptable, Gestionnaire de cité…">
                                <Input
                                    id="new-role-name"
                                    autoFocus
                                    value={name}
                                    onChange={(event) => {
                                        setName(event.target.value);
                                        setNameError(undefined);
                                    }}
                                    placeholder="Ex. Chef service logement"
                                    invalid={!!nameError}
                                    aria-describedby={nameError ? 'new-role-name-error' : 'new-role-name-hint'}
                                    maxLength={225}
                                />
                            </Field>
                        ) : (
                            <div className="space-y-4">
                                <p className="flex items-center gap-2 text-sm text-muted">
                                    <ShieldCheck {...ICON} className="shrink-0 text-primary" aria-hidden />
                                    Droits du rôle <span className="font-medium text-gray-900">« {name.trim()} »</span>
                                </p>
                                <PermissionEditor tree={tree} selected={selected} onChange={setSelected} />
                            </div>
                        )}
                    </DialogBody>

                    <DialogFooter>
                        {step === 0 ? (
                            <Button variant="outline" size="sm" className="h-10" onClick={() => onOpenChange(false)}>
                                Annuler
                            </Button>
                        ) : (
                            <Button variant="outline" size="sm" className="h-10" onClick={() => setStep(0)}>
                                Précédent
                            </Button>
                        )}
                        <Button type="submit" size="sm" className="h-10" disabled={submitting}>
                            {submitting && <Loader2 className="size-4 animate-spin" aria-hidden />}
                            {step === 0 ? 'Suivant' : 'Créer le rôle'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/* ─────────────────────── Onglet « Modules et actions » ─────────────────────── */

function ModuleCatalog({ tree, modulesUrl }: { tree: ModuleNode[]; modulesUrl: string | null }) {
    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-3 rounded-brand border bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-sm font-medium text-gray-900">Référentiel des modules</h2>
                    <p className="text-sm text-muted">Lecture seule : les actions se modifient depuis la gestion des modules.</p>
                </div>
                {modulesUrl && (
                    <a
                        href={modulesUrl}
                        className="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-brand border border-primary bg-white px-4 text-sm text-primary transition-colors hover:bg-soft focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                    >
                        <Network {...ICON} aria-hidden />
                        Gérer les modules
                    </a>
                )}
            </div>

            {tree.length === 0 ? (
                <p className="rounded-brand border border-dashed bg-white px-4 py-12 text-center text-sm text-muted">Aucun module actif.</p>
            ) : (
                <div className="grid grid-cols-1 items-start gap-4 xl:grid-cols-2">
                    {tree.map((root) => {
                        const Icon = moduleIcon(root.slug);

                        return (
                            <section key={root.id} className="rounded-brand border bg-white">
                                <header className="flex items-center gap-3 border-b px-4 py-3">
                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                                        <Icon {...ICON} />
                                    </span>
                                    <h3 className="min-w-0 flex-1 truncate text-sm font-medium text-gray-900">{root.name}</h3>
                                    <Badge variant="neutral" className="px-2 font-semibold">
                                        {collectPermissions(root).length} action(s)
                                    </Badge>
                                </header>
                                <div className="space-y-2 p-4">
                                    {root.permissions.length > 0 && <ActionChips node={root} />}
                                    {root.children.map((child) => (
                                        <CatalogNode key={child.id} node={child} />
                                    ))}
                                    {root.permissions.length === 0 && root.children.length === 0 && <p className="text-sm text-muted">Aucune action.</p>}
                                </div>
                            </section>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

function ActionChips({ node }: { node: ModuleNode }) {
    return (
        <div className="flex flex-wrap gap-1.5">
            {node.permissions.map((permission) => (
                <span
                    key={permission.id}
                    title={permission.description ?? undefined}
                    className={cn('rounded border px-2 py-0.5 text-xs', ACTION_COLORS[permission.action] ?? 'border-gray-200 bg-gray-50 text-gray-700')}
                >
                    {permission.label}
                </span>
            ))}
        </div>
    );
}

function CatalogNode({ node }: { node: ModuleNode }) {
    const [open, setOpen] = useState(false);

    return (
        <div className="rounded-brand border">
            <button
                type="button"
                onClick={() => setOpen(!open)}
                aria-expanded={open}
                className="flex w-full cursor-pointer items-center gap-2 px-3 py-2.5 text-left text-sm text-gray-900 hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
            >
                <ChevronRight {...ICON} className={cn('shrink-0 text-muted transition-transform', open && 'rotate-90')} aria-hidden />
                <span className="truncate">{node.name}</span>
                <span className="ml-auto shrink-0 text-xs text-muted tabular-nums">{collectPermissions(node).length}</span>
            </button>
            {open && (
                <div className="space-y-2 border-t px-3 py-3">
                    {node.permissions.length > 0 ? <ActionChips node={node} /> : node.children.length === 0 && <p className="text-xs text-muted">Aucune action.</p>}
                    {node.children.map((child) => (
                        <CatalogNode key={child.id} node={child} />
                    ))}
                </div>
            )}
        </div>
    );
}

/* ─────────────────────── Onglet « Affectations utilisateurs » ─────────────────────── */

function Assignments({ users, roles, filters, canAssign }: { users: Paginated<UserRow>; roles: RoleRow[]; filters: AssignmentFilters; canAssign: boolean }) {
    const [search, setSearch] = useState(filters.search);
    const firstRender = useRef(true);
    const filtered = Boolean(filters.search || filters.statut);

    // Recherche côté serveur 350 ms après la dernière frappe (retour en page 1, onglet conservé).
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        const timer = window.setTimeout(() => reload({ search }), 350);
        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    function reload(changes: Partial<AssignmentFilters>) {
        const next = { ...filters, search, ...changes };
        router.get(
            window.location.pathname,
            { onglet: 'affectations', ...Object.fromEntries(Object.entries(next).filter(([, value]) => value !== '')) },
            {
                only: ['users', 'filters'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    const columns: DataTableColumn<UserRow>[] = [
        {
            key: 'user',
            header: 'Utilisateur',
            cell: (user) => (
                <div className="flex items-center gap-3">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-soft text-xs font-medium text-primary" aria-hidden>
                        {initiales(user.name)}
                    </span>
                    <div className="min-w-0">
                        <p className="truncate font-medium text-gray-900">{user.name}</p>
                        <p className="truncate text-xs text-muted">{user.matricule || 'Sans matricule'}</p>
                    </div>
                </div>
            ),
        },
        {
            key: 'role',
            header: 'Rôle actuel',
            cell: (user) => (user.role ? <Badge variant="success">{user.role}</Badge> : <Badge variant="neutral">Non défini</Badge>),
        },
        {
            key: 'statut',
            header: 'Statut',
            cell: (user) =>
                user.statut === 'active' ? (
                    <Badge variant="success">
                        <span className="size-1.5 rounded-full bg-primary" aria-hidden />
                        Actif
                    </Badge>
                ) : (
                    <Badge variant="neutral">
                        <span className="size-1.5 rounded-full bg-gray-400" aria-hidden />
                        Inactif
                    </Badge>
                ),
        },
        ...(canAssign
            ? [
                  {
                      key: 'assign',
                      header: 'Affectation',
                      headClassName: 'w-px',
                      cell: (user: UserRow) => (user.assignUrl ? <AssignRole key={`${user.id}:${user.role_id}`} user={user} roles={roles} /> : null),
                  },
              ]
            : []),
    ];

    return (
        <div className="space-y-3">
            <p className="flex items-center gap-2 text-sm text-muted">
                <CircleAlert className="size-4 shrink-0 text-secondary" aria-hidden />
                Un utilisateur hérite de toutes les permissions de son rôle.
            </p>
            <DataTable
                columns={columns}
                data={users}
                rowKey={(user) => user.id}
                emptyMessage={filtered ? 'Aucun utilisateur ne correspond à ces critères.' : 'Aucun utilisateur.'}
                toolbar={
                    <>
                        <div className="w-full sm:max-w-xs">
                            <Input
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Rechercher un utilisateur…"
                                aria-label="Rechercher un utilisateur"
                                icon={<Search />}
                                className="h-10"
                            />
                        </div>
                        <Select value={filters.statut || 'all'} onValueChange={(value) => reload({ statut: value === 'all' ? '' : (value as Statut) })}>
                            <SelectTrigger aria-label="Filtrer par statut" className="h-10 w-full sm:w-44">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous les statuts</SelectItem>
                                <SelectItem value="active">Actifs</SelectItem>
                                <SelectItem value="inactive">Inactifs</SelectItem>
                            </SelectContent>
                        </Select>
                        {filtered && (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-10"
                                onClick={() => {
                                    setSearch('');
                                    reload({ search: '', statut: '' });
                                }}
                            >
                                <X className="size-4" aria-hidden />
                                Réinitialiser
                            </Button>
                        )}
                    </>
                }
            />
        </div>
    );
}

function AssignRole({ user, roles }: { user: UserRow; roles: RoleRow[] }) {
    const [roleId, setRoleId] = useState(user.role_id ?? '');
    const [saving, setSaving] = useState(false);
    const unchanged = roleId === '' || roleId === user.role_id;

    function assign() {
        if (!user.assignUrl || unchanged) return;
        router.put(
            user.assignUrl,
            { role_id: roleId },
            {
                preserveState: true,
                preserveScroll: true,
                onStart: () => setSaving(true),
                onFinish: () => setSaving(false),
                onError: (errors) => toast.error(firstError(errors)),
            },
        );
    }

    return (
        <div className="flex items-center gap-2">
            <Select value={roleId} onValueChange={setRoleId}>
                <SelectTrigger aria-label={`Rôle de ${user.name}`} className="h-9 w-48">
                    <SelectValue placeholder="Sélectionner" />
                </SelectTrigger>
                <SelectContent>
                    {roles.map((role) => (
                        <SelectItem key={role.id} value={role.id}>
                            {role.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <Button size="sm" disabled={unchanged || saving} onClick={assign}>
                {saving ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />}
                Affecter
            </Button>
        </div>
    );
}
