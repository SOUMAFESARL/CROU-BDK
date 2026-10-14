import { router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Power, PowerOff, Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { AdminLayout } from '@/components/layout/admin-layout';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Paginated } from '@/types/global';

type Statut = 'active' | 'inactive';

type UserRow = {
    id: string;
    name: string;
    civilite: string | null;
    matricule: string | null;
    role: string | null;
    email: string | null;
    telephone: string | null;
    statut: Statut;
    urls: {
        show: string | null;
        edit: string | null;
        status: string | null;
    };
};

type UsersIndexProps = {
    users: Paginated<UserRow>;
    filters: { search: string; statut: Statut | '' };
    showPersonal: boolean;
    createUrl: string | null;
};

const initiales = (name: string) =>
    name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((word) => Array.from(word)[0] ?? '')
        .join('')
        .toUpperCase() || 'U';

export default function UsersIndex({ users, filters, showPersonal, createUrl }: UsersIndexProps) {
    const [search, setSearch] = useState(filters.search);
    const [toToggle, setToToggle] = useState<UserRow | null>(null);
    const firstRender = useRef(true);

    // Recherche côté serveur, déclenchée 350 ms après la dernière frappe (retour en page 1).
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        const timer = window.setTimeout(() => applyFilters({ search }), 350);
        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    function applyFilters(changes: Partial<UsersIndexProps['filters']>) {
        const next = { ...filters, search, ...changes };
        router.get(
            window.location.pathname,
            Object.fromEntries(Object.entries(next).filter(([, value]) => value !== '')),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function confirmToggle() {
        if (!toToggle?.urls.status) {
            return;
        }
        router.put(
            toToggle.urls.status,
            { statut: toToggle.statut === 'active' ? 'inactive' : 'active' },
            { preserveScroll: true, onFinish: () => setToToggle(null) },
        );
    }

    const columns: DataTableColumn<UserRow>[] = [
        {
            key: 'user',
            header: 'Utilisateur',
            cell: (user) => (
                <div className="flex items-center gap-3">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-soft text-xs font-semibold text-primary" aria-hidden>
                        {initiales(user.name)}
                    </span>
                    <div className="min-w-0">
                        <p className="truncate font-medium text-gray-900">{user.name}</p>
                        <p className="truncate text-xs text-muted">{[user.civilite, user.matricule].filter(Boolean).join(' · ') || '—'}</p>
                    </div>
                </div>
            ),
        },
        { key: 'role', header: 'Rôle', cell: (user) => user.role ?? <span className="text-muted">Non renseigné</span> },
        ...(showPersonal
            ? [
                  { key: 'email', header: 'E-mail', cell: (user: UserRow) => user.email ?? '—' },
                  { key: 'telephone', header: 'Téléphone', cell: (user: UserRow) => user.telephone ?? '—' },
              ]
            : []),
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
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            headClassName: 'w-px text-right',
            className: 'text-right',
            cell: (user) => (
                <RowActions>
                    {user.urls.show && <RowAction label="Voir la fiche" icon={Eye} tone="view" href={user.urls.show} />}
                    {user.urls.edit && <RowAction label="Modifier" icon={Pencil} tone="edit" href={user.urls.edit} />}
                    {user.urls.status &&
                        (user.statut === 'active' ? (
                            <RowAction label="Désactiver" icon={PowerOff} tone="danger" onClick={() => setToToggle(user)} />
                        ) : (
                            <RowAction label="Activer" icon={Power} tone="success" onClick={() => setToToggle(user)} />
                        ))}
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Utilisateurs">
            <div className="mb-6 flex flex-col gap-1">
                <h1 className="page-title text-xl font-medium text-gray-900">Liste des utilisateurs</h1>
                <p className="text-sm text-muted">Comptes ayant accès à l'espace d'administration.</p>
            </div>

            <DataTable
                columns={columns}
                data={users}
                rowKey={(user) => user.id}
                emptyMessage={filters.search || filters.statut ? 'Aucun utilisateur ne correspond à ces critères.' : 'Aucun utilisateur.'}
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
                        <Select
                            value={filters.statut || 'all'}
                            onValueChange={(value) => applyFilters({ statut: value === 'all' ? '' : (value as Statut) })}
                        >
                            <SelectTrigger aria-label="Filtrer par statut" className="h-10 w-full sm:w-44">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous les statuts</SelectItem>
                                <SelectItem value="active">Actifs</SelectItem>
                                <SelectItem value="inactive">Inactifs</SelectItem>
                            </SelectContent>
                        </Select>
                        {(filters.search || filters.statut) && (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-10"
                                onClick={() => {
                                    setSearch('');
                                    router.get(window.location.pathname, {}, { preserveState: true, replace: true });
                                }}
                            >
                                <X className="size-4" aria-hidden />
                                Réinitialiser
                            </Button>
                        )}
                        {createUrl && (
                            <a
                                href={createUrl}
                                className="inline-flex h-10 items-center justify-center gap-2 rounded-brand bg-primary px-4 text-sm font-semibold text-white transition-colors hover:bg-primary-hover focus-visible:ring-2 focus-visible:ring-secondary focus-visible:ring-offset-2 focus-visible:outline-none sm:ml-auto"
                            >
                                <Plus className="size-4" aria-hidden />
                                Nouvel utilisateur
                            </a>
                        )}
                    </>
                }
            />

            <AlertDialog open={toToggle !== null} onOpenChange={(open) => !open && setToToggle(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>{toToggle?.statut === 'active' ? 'Désactiver ce compte ?' : 'Activer ce compte ?'}</AlertDialogTitle>
                        <AlertDialogDescription>
                            {toToggle?.statut === 'active'
                                ? `${toToggle?.name} ne pourra plus se connecter à l'espace d'administration.`
                                : `${toToggle?.name} pourra de nouveau se connecter à l'espace d'administration.`}
                        </AlertDialogDescription>
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
                                className={toToggle?.statut === 'active' ? 'h-10 bg-red-600 hover:bg-red-700' : 'h-10'}
                                onClick={(event) => {
                                    event.preventDefault();
                                    confirmToggle();
                                }}
                            >
                                {toToggle?.statut === 'active' ? 'Désactiver' : 'Activer'}
                            </Button>
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AdminLayout>
    );
}
