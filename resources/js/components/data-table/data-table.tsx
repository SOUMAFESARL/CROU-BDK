import { router } from '@inertiajs/react';
import type { MouseEvent, ReactNode } from 'react';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

export type DataTableColumn<T> = {
    key: string;
    header: ReactNode;
    cell: (row: T) => ReactNode;
    className?: string;
    headClassName?: string;
};

type DataTableProps<T> = {
    columns: DataTableColumn<T>[];
    data: Paginated<T>;
    rowKey: (row: T) => string;
    /** Barre au-dessus du tableau (recherche, filtres, bouton d'ajout). */
    toolbar?: ReactNode;
    emptyMessage?: ReactNode;
    /** Page ouverte au clic sur la ligne (null : ligne non cliquable). */
    rowHref?: (row: T) => string | null;
};

/** Éléments de la ligne qui gardent leur propre action (case à cocher, bouton, lien, champ). */
const INTERACTIF = 'a, button, input, select, textarea, label, [role="checkbox"], [role="button"], [role="menuitem"]';

/** Clic sur une ligne : ouvre `href` (nouvel onglet avec Ctrl/Cmd ou clic molette), sauf sur un élément interactif ou une sélection de texte. */
function ouvrirLigne(event: MouseEvent<HTMLTableRowElement>, href: string) {
    if ((event.target as HTMLElement).closest(INTERACTIF) || window.getSelection()?.toString()) return;
    if (event.ctrlKey || event.metaKey || event.button === 1) {
        window.open(href, '_blank', 'noopener');
        return;
    }
    router.visit(href);
}

/**
 * Tableau paginé commun à tout l'espace admin : shadcn `Table` + `Pagination`,
 * pagination côté serveur (10 lignes par page, `->paginate(10)->withQueryString()`).
 */
export function DataTable<T>({ columns, data, rowKey, toolbar, emptyMessage = 'Aucun résultat.', rowHref }: DataTableProps<T>) {
    return (
        <div className="overflow-hidden rounded-brand border bg-white">
            {toolbar && <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center">{toolbar}</div>}

            <Table>
                <TableHeader>
                    <TableRow className="hover:bg-transparent">
                        {columns.map((column) => (
                            <TableHead key={column.key} className={column.headClassName}>
                                {column.header}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {data.data.length === 0 ? (
                        <TableRow className="hover:bg-transparent">
                            <TableCell colSpan={columns.length} className="h-32 text-center text-muted">
                                {emptyMessage}
                            </TableCell>
                        </TableRow>
                    ) : (
                        data.data.map((row) => {
                            const href = rowHref?.(row) ?? null;

                            return (
                                <TableRow
                                    key={rowKey(row)}
                                    className={cn(href && 'cursor-pointer')}
                                    onClick={href ? (event) => ouvrirLigne(event, href) : undefined}
                                    onAuxClick={href ? (event) => event.button === 1 && ouvrirLigne(event, href) : undefined}
                                >
                                    {columns.map((column) => (
                                        <TableCell key={column.key} className={cn(column.className)}>
                                            {column.cell(row)}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            );
                        })
                    )}
                </TableBody>
            </Table>

            <DataTablePagination data={data} />
        </div>
    );
}
