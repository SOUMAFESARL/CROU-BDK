import type { ReactNode } from 'react';
import type { DataTableColumn } from '@/components/data-table/data-table';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';

type SimpleTableProps<T> = {
    columns: DataTableColumn<T>[];
    rows: T[];
    rowKey: (row: T) => string;
    emptyMessage?: ReactNode;
};

/** Tableau non paginé (listes déjà chargées et filtrées dans la page), mêmes colonnes que `DataTable`. */
export function SimpleTable<T>({ columns, rows, rowKey, emptyMessage = 'Aucun résultat.' }: SimpleTableProps<T>) {
    return (
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
                {rows.length === 0 ? (
                    <TableRow className="hover:bg-transparent">
                        <TableCell colSpan={columns.length} className="h-32 text-center text-muted">
                            {emptyMessage}
                        </TableCell>
                    </TableRow>
                ) : (
                    rows.map((row) => (
                        <TableRow key={rowKey(row)}>
                            {columns.map((column) => (
                                <TableCell key={column.key} className={cn(column.className)}>
                                    {column.cell(row)}
                                </TableCell>
                            ))}
                        </TableRow>
                    ))
                )}
            </TableBody>
        </Table>
    );
}
