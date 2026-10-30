import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { cn } from '@/lib/utils';

/** Briques de mise en page des pages React de l'espace étudiant (cartes « surtitre + titre », lignes, états vides). */

type PanneauProps = {
    surtitre: string;
    titre: string;
    /** Pastille à droite du titre (compteur…). */
    badge?: ReactNode;
    /** Action à droite de l'en-tête (bouton…). */
    action?: ReactNode;
    id?: string;
    className?: string;
    children: ReactNode;
};

export function Panneau({ surtitre, titre, badge, action, id, className, children }: PanneauProps) {
    const titreId = id ? `${id}-titre` : undefined;

    return (
        <section id={id} aria-labelledby={titreId} className={cn('rounded-brand border bg-white p-4 md:p-5', className)}>
            <div className="mb-3 flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-xs text-muted">{surtitre}</p>
                    <h2 id={titreId} className="text-base font-medium text-gray-900">
                        {titre}
                    </h2>
                </div>
                {(badge !== undefined || action) && (
                    <div className="flex shrink-0 flex-wrap items-center justify-end gap-2">
                        {badge !== undefined && (
                            <span className="shrink-0 rounded-brand bg-surface px-2.5 py-0.5 text-xs font-medium whitespace-nowrap text-muted tabular-nums">{badge}</span>
                        )}
                        {action}
                    </div>
                )}
            </div>
            {children}
        </section>
    );
}

export function Liste({ children, className }: { children: ReactNode; className?: string }) {
    return <ul className={cn('-mx-2 divide-y', className)}>{children}</ul>;
}

/** Pastille d'icône d'une ligne : verte quand l'élément est terminé, orange quand il demande une action. */
export function IconeLigne({ icone: Icone, ton }: { icone: LucideIcon; ton?: 'vert' | 'orange' }) {
    return (
        <span
            className={cn(
                'grid size-10 shrink-0 place-items-center rounded-brand',
                ton === 'vert' ? 'bg-soft text-primary' : ton === 'orange' ? 'bg-accent/15 text-amber-800' : 'bg-surface text-muted',
            )}
        >
            <Icone {...ICON} />
        </span>
    );
}

type LigneProps = {
    icone: LucideIcon;
    titre: ReactNode;
    detail?: ReactNode;
    /** Libellé au-dessus de la valeur (fiche d'information) au lieu d'un détail en dessous. */
    libelle?: string;
    ton?: 'vert' | 'orange';
    complement?: ReactNode;
    /** Ligne entièrement cliquable. */
    href?: string;
    nouvelOnglet?: boolean;
    fin?: LucideIcon;
};

export function Ligne({ icone, titre, detail, libelle, ton, complement, href, nouvelOnglet, fin: Fin }: LigneProps) {
    const contenu = (
        <>
            <IconeLigne icone={icone} ton={ton} />
            <span className="min-w-0 flex-1">
                {libelle && <small className="block truncate text-xs text-muted">{libelle}</small>}
                <strong className={cn('block truncate text-sm font-medium text-gray-900', href && 'group-hover:text-primary')}>{titre}</strong>
                {detail && <small className="block truncate text-xs text-muted">{detail}</small>}
            </span>
            {complement}
            {Fin && <Fin {...ICON} className="shrink-0 text-muted group-hover:text-primary" />}
        </>
    );
    const classe = 'flex min-h-14 items-center gap-3 rounded-brand px-2 py-2.5';

    return (
        <li>
            {href ? (
                <a
                    href={href}
                    {...(nouvelOnglet ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
                    className={cn(classe, 'group hover:bg-surface focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none')}
                >
                    {contenu}
                </a>
            ) : (
                <div className={classe}>{contenu}</div>
            )}
        </li>
    );
}

export function Vide({ icone: Icone, children }: { icone: LucideIcon; children: ReactNode }) {
    return (
        <p className="flex items-center gap-2.5 rounded-brand bg-surface p-3 text-sm text-muted">
            <Icone {...ICON} className="shrink-0" />
            <span>{children}</span>
        </p>
    );
}

/** Encadré d'information vert pâle (#EBF8DE). */
export function Notice({ icone: Icone, children, className }: { icone: LucideIcon; children: ReactNode; className?: string }) {
    return (
        <div className={cn('flex items-start gap-2.5 rounded-brand bg-soft p-3 text-sm text-primary', className)}>
            <Icone {...ICON} className="mt-0.5 shrink-0" />
            <div>{children}</div>
        </div>
    );
}

export type Filtre = { cle: string; libelle: string; href: string; compte?: number };

/** Puces de filtre d'une liste (liens Inertia : la page garde son défilement). */
export function Filtres({ filtres, actif, libelle }: { filtres: Filtre[]; actif: string; libelle: string }) {
    return (
        <nav aria-label={libelle} className="-mx-1 mb-3 flex gap-2 overflow-x-auto px-1 pb-1">
            {filtres.map((filtre) => (
                <Link
                    key={filtre.cle}
                    href={filtre.href}
                    preserveScroll
                    preserveState
                    aria-current={filtre.cle === actif ? 'page' : undefined}
                    className={cn(
                        'inline-flex h-9 shrink-0 items-center gap-1.5 rounded-brand border px-3.5 text-sm whitespace-nowrap transition-colors focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                        filtre.cle === actif ? 'border-primary bg-primary font-medium text-white' : 'border-gray-300 bg-white text-muted hover:bg-surface',
                    )}
                >
                    {filtre.libelle}
                    {filtre.compte !== undefined && (
                        <span className={cn('rounded-sm px-1.5 text-xs tabular-nums', filtre.cle === actif ? 'bg-white/20' : 'bg-surface')}>{filtre.compte}</span>
                    )}
                </Link>
            ))}
        </nav>
    );
}

export type PaginationSimpleProps = { page: number; pages: number; total: number; precedent: string | null; suivant: string | null };

/** Pagination précédent / suivant (EtudiantMaintenance::pagination), masquée sur une seule page. */
export function PaginationSimple({ page, pages, precedent, suivant }: PaginationSimpleProps) {
    if (pages <= 1) return null;

    return (
        <div className="mt-3 flex items-center justify-between gap-3 border-t pt-3">
            <PaginationPrevious href={precedent} />
            <span className="text-sm text-muted tabular-nums">
                Page {page} sur {pages}
            </span>
            <PaginationNext href={suivant} />
        </div>
    );
}
