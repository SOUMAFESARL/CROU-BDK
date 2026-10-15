import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { StatCard, type KpiTone } from '@/components/ui/stat-card';
import { cn } from '@/lib/utils';

export type KpiFilter = {
    key: string;
    label: string;
    value: ReactNode;
    tone: KpiTone;
    /** Lien du filtre ; sans lien, la carte est un simple indicateur. */
    href?: string;
    extra?: string;
    highlight?: boolean;
};

type KpiFiltersProps = {
    items: KpiFilter[];
    active?: string;
    label: string;
    className?: string;
    /** Entoure la carte du filtre actif (par défaut). */
    ringActive?: boolean;
};

/** Indicateurs (`StatCard`) qui servent aussi de filtres : le filtre actif est entouré. */
export function KpiFilters({ items, active, label, className, ringActive = true }: KpiFiltersProps) {
    return (
        <nav aria-label={label} className={cn('grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4', className)}>
            {items.map((item) => {
                const carte = <StatCard tone={item.tone} label={item.label} value={item.value} extra={item.extra} highlight={item.highlight} />;
                if (!item.href) return <div key={item.key}>{carte}</div>;
                const actif = item.key === active;

                return (
                    <Link
                        key={item.key}
                        href={item.href}
                        preserveScroll
                        aria-current={actif ? 'page' : undefined}
                        className={cn(
                            'rounded-md transition-shadow focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                            actif && ringActive ? 'ring-2 ring-primary ring-offset-2' : 'hover:shadow-sm',
                        )}
                    >
                        {carte}
                    </Link>
                );
            })}
        </nav>
    );
}
