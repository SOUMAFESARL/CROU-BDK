import { Link } from '@inertiajs/react';
import { CalendarDays, ClipboardCheck, ClipboardList, ListChecks } from 'lucide-react';
import { useEffect, useLayoutEffect, useRef, useState, type ReactNode } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { cn } from '@/lib/utils';

type Onglet = 'campagnes' | 'admissions' | 'validees' | 'definitives';

const ONGLETS: { cle: Onglet; label: string; icone: typeof CalendarDays }[] = [
    { cle: 'campagnes', label: 'Campagnes', icone: CalendarDays },
    { cle: 'admissions', label: 'Liste des demandes', icone: ClipboardList },
    { cle: 'validees', label: 'Décision de la commission', icone: ClipboardCheck },
    { cle: 'definitives', label: 'Listes définitives', icone: ListChecks },
];

type Position = { left: number; top: number; width: number };

/**
 * Dernière position du soulignement, conservée d'une page à l'autre : chaque onglet est une page Inertia distincte
 * (le composant est remonté), le soulignement repart donc de l'onglet quitté et glisse vers le nouvel onglet.
 */
let dernierePosition: Position | null = null;

/**
 * Onglets du menu « Campagnes & admission » : la liste des campagnes, la liste des demandes, la décision de la
 * commission (demandes validées ou mises en attente depuis les listes provisoires), puis les listes définitives (demandes
 * approuvées en commission, depuis les demandes validées ou la file d'attente).
 * Chaque onglet est une page à part (navigation Inertia) ; un onglet sans lien (droit manquant) est masqué.
 * Navigation fluide : l'onglet choisi s'active dès le clic (avant la réponse du serveur) et le soulignement glisse
 * vers lui, comme les onglets des listes (`OngletsRecevables`).
 * `aside` : contenu aligné à droite sur la ligne des onglets (ex. campagne active).
 */
export function OngletsCampagnesAdmissions({ actif, urls, aside }: { actif: Onglet; urls: Partial<Record<Onglet, string | null>>; aside?: ReactNode }) {
    const visibles = ONGLETS.filter((onglet) => onglet.cle === actif || urls[onglet.cle]);
    const navRef = useRef<HTMLElement>(null);
    /** Onglet choisi (mis à jour dès le clic) ; il diffère de `actif` le temps du chargement. */
    const [vise, setVise] = useState<Onglet>(actif);
    const [position, setPosition] = useState<Position | null>(dernierePosition);

    // Réponse du serveur (ou navigation annulée) : l'onglet affiché fait foi.
    useEffect(() => setVise(actif), [actif]);

    // Soulignement : part de la position précédente puis suit l'onglet choisi (et la largeur de la barre).
    useLayoutEffect(() => {
        const nav = navRef.current;
        if (!nav) return;
        const mesurer = () => {
            const onglet = nav.querySelector<HTMLElement>(`[data-onglet="${vise}"]`);
            const suivante = onglet ? { left: onglet.offsetLeft, top: onglet.offsetTop + onglet.offsetHeight - 2, width: onglet.offsetWidth } : null;
            dernierePosition = suivante;
            return suivante;
        };
        // Image suivante : laisse le navigateur peindre la position de départ pour que la transition ait lieu.
        const image = window.requestAnimationFrame(() => setPosition(mesurer()));
        const observateur = new ResizeObserver(() => setPosition(mesurer()));
        observateur.observe(nav);
        return () => {
            window.cancelAnimationFrame(image);
            observateur.disconnect();
        };
    }, [vise]);

    return (
        <div className="mb-6 flex w-full flex-wrap items-center gap-x-4 border-b">
            <nav ref={navRef} aria-label="Campagnes et admissions" className="relative flex min-w-0 items-center gap-1 overflow-x-auto overflow-y-hidden [scrollbar-width:none]! [&::-webkit-scrollbar]:hidden!">
                {visibles.map(({ cle, label, icone: Icone }) => {
                    const courant = cle === vise;
                    const classes = cn(
                        'inline-flex h-11 shrink-0 items-center gap-2 px-3 text-sm whitespace-nowrap transition-colors duration-300',
                        'focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none focus-visible:ring-inset',
                        courant ? 'font-medium text-primary' : 'text-muted hover:text-primary',
                    );
                    const contenu = (
                        <>
                            <Icone {...ICON} aria-hidden />
                            {label}
                        </>
                    );

                    return cle === actif || !urls[cle] ? (
                        <span key={cle} data-onglet={cle} aria-current={cle === actif ? 'page' : undefined} className={classes}>
                            {contenu}
                        </span>
                    ) : (
                        <Link key={cle} data-onglet={cle} href={urls[cle]!} onClick={() => setVise(cle)} className={classes}>
                            {contenu}
                        </Link>
                    );
                })}
                {position && (
                    <span
                        aria-hidden
                        className="pointer-events-none absolute top-0 left-0 h-0.5 bg-primary transition-[transform,width] duration-300 ease-out motion-reduce:transition-none"
                        style={{ width: position.width, transform: `translate(${position.left}px, ${position.top}px)` }}
                    />
                )}
            </nav>
            {aside && <div className="ml-auto flex min-w-0 flex-wrap items-center gap-x-4 gap-y-1 py-2 text-sm">{aside}</div>}
        </div>
    );
}
