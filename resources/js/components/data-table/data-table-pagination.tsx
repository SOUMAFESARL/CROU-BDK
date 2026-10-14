import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import type { Paginated } from '@/types/global';

/**
 * Pages à afficher : la première, la dernière et la page courante avec ses voisines ;
 * les trous sont remplacés par « … » (ex. 1 … 4 5 6 … 10). Un « … » qui ne masquerait
 * qu'une seule page est remplacé par cette page.
 */
function pageWindow(current: number, last: number): (number | 'ellipsis')[] {
    if (last <= 7) {
        return Array.from({ length: last }, (_, index) => index + 1);
    }

    const start = Math.max(2, Math.min(current - 1, last - 4));
    const end = Math.min(last - 1, Math.max(current + 1, 5));
    const pages: (number | 'ellipsis')[] = [1];

    if (start > 3) pages.push('ellipsis');
    else if (start === 3) pages.push(2);
    for (let page = start; page <= end; page++) pages.push(page);
    if (end < last - 2) pages.push('ellipsis');
    else if (end === last - 2) pages.push(last - 1);
    pages.push(last);

    return pages;
}

/** URL d'une page en conservant les filtres de la requête (`withQueryString()`). */
function pageUrl(firstPageUrl: string, page: number): string {
    const url = new URL(firstPageUrl, window.location.origin);
    url.searchParams.set('page', String(page));
    return url.toString();
}

/** Pied de tableau : « x à y sur z » + pagination shadcn (pagination côté serveur Laravel). */
export function DataTablePagination<T>({ data }: { data: Paginated<T> }) {
    if (data.total === 0) {
        return null;
    }

    return (
        <div className="flex flex-col items-center justify-between gap-3 border-t px-4 py-3 sm:flex-row">
            <p className="text-sm text-muted">
                Affichage de <span className="font-semibold text-gray-900">{data.from}</span> à{' '}
                <span className="font-semibold text-gray-900">{data.to}</span> sur{' '}
                <span className="font-semibold text-gray-900">{data.total}</span>
            </p>

            {data.last_page > 1 && (
                <Pagination className="sm:justify-end">
                    <PaginationContent>
                        <PaginationItem>
                            <PaginationPrevious href={data.prev_page_url} />
                        </PaginationItem>
                        {pageWindow(data.current_page, data.last_page).map((page, index) =>
                            page === 'ellipsis' ? (
                                <PaginationItem key={`ellipsis-${index}`} className="hidden sm:block">
                                    <PaginationEllipsis />
                                </PaginationItem>
                            ) : (
                                <PaginationItem key={page} className="hidden sm:block">
                                    <PaginationLink
                                        href={pageUrl(data.first_page_url, page)}
                                        isActive={page === data.current_page}
                                        aria-label={`Page ${page}`}
                                    >
                                        {page}
                                    </PaginationLink>
                                </PaginationItem>
                            ),
                        )}
                        <PaginationItem className="px-2 text-sm text-muted sm:hidden">
                            {data.current_page} / {data.last_page}
                        </PaginationItem>
                        <PaginationItem>
                            <PaginationNext href={data.next_page_url} />
                        </PaginationItem>
                    </PaginationContent>
                </Pagination>
            )}
        </div>
    );
}
