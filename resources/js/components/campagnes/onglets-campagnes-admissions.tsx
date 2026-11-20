import { Link } from '@inertiajs/react';
import { CalendarDays, ClipboardCheck, ClipboardList, ListChecks } from 'lucide-react';
import type { ReactNode } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { cn } from '@/lib/utils';

type Onglet = 'campagnes' | 'admissions' | 'validees' | 'definitives';

const ONGLETS: { cle: Onglet; label: string; icone: typeof CalendarDays }[] = [
    { cle: 'campagnes', label: 'Campagnes', icone: CalendarDays },
    { cle: 'admissions', label: 'Liste des demandes', icone: ClipboardList },
    { cle: 'validees', label: 'Décision de la commission', icone: ClipboardCheck },
    { cle: 'definitives', label: 'Liste définitive', icone: ListChecks },
];

/**
 * Onglets du menu « Campagnes & admission » : la liste des campagnes, la liste des demandes, la décision de la
 * commission (demandes validées ou mises en attente depuis les listes provisoires), puis la liste définitive (demandes
 * validées approuvées en commission).
 * Chaque onglet est une page à part (navigation Inertia) ; un onglet sans lien (droit manquant) est masqué.
 * `aside` : contenu aligné à droite sur la ligne des onglets (ex. campagne active).
 */
export function OngletsCampagnesAdmissions({ actif, urls, aside }: { actif: Onglet; urls: Partial<Record<Onglet, string | null>>; aside?: ReactNode }) {
    const visibles = ONGLETS.filter((onglet) => onglet.cle === actif || urls[onglet.cle]);

    return (
        <div className="mb-6 flex w-full flex-wrap items-center gap-x-4 border-b">
            <nav aria-label="Campagnes et admissions" className="flex min-w-0 items-center gap-1 overflow-x-auto overflow-y-hidden">
                {visibles.map(({ cle, label, icone: Icone }) => {
                    const courant = cle === actif;
                    const classes = cn(
                        'relative inline-flex h-11 shrink-0 items-center gap-2 px-3 text-sm whitespace-nowrap transition-colors duration-300',
                        'focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none focus-visible:ring-inset',
                        courant ? 'font-medium text-primary after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:bg-primary' : 'text-muted hover:text-primary',
                    );
                    const contenu = (
                        <>
                            <Icone {...ICON} aria-hidden />
                            {label}
                        </>
                    );

                    return courant || !urls[cle] ? (
                        <span key={cle} aria-current="page" className={classes}>
                            {contenu}
                        </span>
                    ) : (
                        <Link key={cle} href={urls[cle]!} className={classes}>
                            {contenu}
                        </Link>
                    );
                })}
            </nav>
            {aside && <div className="ml-auto flex min-w-0 flex-wrap items-center gap-x-4 gap-y-1 py-2 text-sm">{aside}</div>}
        </div>
    );
}
