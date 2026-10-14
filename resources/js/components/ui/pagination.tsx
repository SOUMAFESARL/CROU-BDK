import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-react';
import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * Pagination shadcn/ui. Les liens sont des `Link` Inertia (pas de rechargement complet) ;
 * un lien sans `href` est rendu désactivé.
 */
function Pagination({ className, ...props }: React.ComponentProps<'nav'>) {
    return <nav role="navigation" aria-label="Pagination" data-slot="pagination" className={cn('flex', className)} {...props} />;
}

function PaginationContent({ className, ...props }: React.ComponentProps<'ul'>) {
    return <ul data-slot="pagination-content" className={cn('flex flex-row items-center gap-1', className)} {...props} />;
}

function PaginationItem(props: React.ComponentProps<'li'>) {
    return <li data-slot="pagination-item" {...props} />;
}

type PaginationLinkProps = {
    href?: string | null;
    isActive?: boolean;
    className?: string;
    children: React.ReactNode;
    'aria-label'?: string;
};

const linkBase =
    'inline-flex h-9 min-w-9 items-center justify-center gap-1 rounded-brand px-2.5 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-secondary';

function PaginationLink({ href, isActive, className, children, ...props }: PaginationLinkProps) {
    if (!href) {
        return (
            <span aria-disabled="true" className={cn(linkBase, 'cursor-not-allowed text-gray-400', className)} {...props}>
                {children}
            </span>
        );
    }

    return (
        <Link
            href={href}
            preserveScroll
            preserveState
            aria-current={isActive ? 'page' : undefined}
            data-slot="pagination-link"
            data-active={isActive}
            className={cn(
                linkBase,
                isActive ? 'border border-primary bg-primary text-white' : 'border border-transparent text-muted hover:bg-soft hover:text-primary',
                className,
            )}
            {...props}
        >
            {children}
        </Link>
    );
}

function PaginationPrevious({ href, className }: { href?: string | null; className?: string }) {
    return (
        <PaginationLink href={href} aria-label="Page précédente" className={cn('px-2.5 sm:pr-3', className)}>
            <ChevronLeft className="size-4" />
            <span className="hidden sm:block">Précédent</span>
        </PaginationLink>
    );
}

function PaginationNext({ href, className }: { href?: string | null; className?: string }) {
    return (
        <PaginationLink href={href} aria-label="Page suivante" className={cn('px-2.5 sm:pl-3', className)}>
            <span className="hidden sm:block">Suivant</span>
            <ChevronRight className="size-4" />
        </PaginationLink>
    );
}

function PaginationEllipsis({ className, ...props }: React.ComponentProps<'span'>) {
    return (
        <span aria-hidden data-slot="pagination-ellipsis" className={cn('flex size-9 items-center justify-center text-muted', className)} {...props}>
            <MoreHorizontal className="size-4" />
            <span className="sr-only">Autres pages</span>
        </span>
    );
}

export { Pagination, PaginationContent, PaginationLink, PaginationItem, PaginationPrevious, PaginationNext, PaginationEllipsis };
