import { Link } from '@inertiajs/react';
import { CalendarDays, ClipboardList } from 'lucide-react';
import { ICON } from '@/components/layout/app-sidebar';
import { cn } from '@/lib/utils';

type Onglet = 'campagnes' | 'admissions';

const ONGLETS: { cle: Onglet; label: string; icone: typeof CalendarDays }[] = [
    { cle: 'campagnes', label: 'Campagnes', icone: CalendarDays },
    { cle: 'admissions', label: 'Admissions', icone: ClipboardList },
];

/**
 * Onglets du menu « Campagnes & admission » : la liste des campagnes d'abord, puis les admissions.
 * Chaque onglet est une page à part (navigation Inertia) ; un onglet sans lien (droit manquant) est masqué.
 */
export function OngletsCampagnesAdmissions({ actif, urls }: { actif: Onglet; urls: Record<Onglet, string | null> }) {
    const visibles = ONGLETS.filter((onglet) => onglet.cle === actif || urls[onglet.cle]);

    return (
        <nav aria-label="Campagnes et admissions" className="mb-6 flex w-full items-center gap-1 overflow-x-auto overflow-y-hidden border-b">
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
    );
}
