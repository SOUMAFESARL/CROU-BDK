import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

/** Parc envoyé par le serveur pour les listes en cascade cité → bâtiment → chambre → résident. */
export type CiteOption = { id: string; name: string };
export type BatimentOption = { id: string; cite_id: string; name: string };
export type ChambreOption = { id: string; batiment_id: string; numero: string };
export type ResidentOption = { id: string; name: string; matricule: string | null; chambre_id: string };

export type Structure = {
    cites: CiteOption[];
    batiments: BatimentOption[];
    chambres: ChambreOption[];
    residents?: ResidentOption[];
};

export type Variante = 'success' | 'warning' | 'danger' | 'neutral' | 'info';

/** `aaaa-mm-jj hh:mm` → « jj/mm/aaaa à hh:mm » (ou seulement la date). */
export const dateHeure = (valeur: string | null | undefined) => {
    if (!valeur) return '—';
    const [jour, heure] = valeur.replace('T', ' ').split(' ');
    const [annee, mois, j] = jour.split('-');
    const date = j ? `${j}/${mois}/${annee}` : jour;
    return heure ? `${date} à ${heure.slice(0, 5)}` : date;
};

/** Valeur courante pour un champ `datetime-local` (heure locale). */
export const maintenantLocal = () => {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 16);
};

export const aujourdhui = () => maintenantLocal().slice(0, 10);

/**
 * Répartit les erreurs Laravel d'un assistant en plusieurs étapes : chaque erreur de champ va sous son champ
 * et l'assistant revient à la première étape fautive ; les autres messages sont renvoyés (encadré d'erreur).
 */
export function repartirErreurs<T extends string>(
    erreurs: Record<string, string>,
    champsEtapes: readonly (readonly T[])[],
    setError: (champ: T, erreur: { type: string; message: string }) => void,
    setEtape: (etape: number) => void,
): string | null {
    let fautive = -1;
    let autre: string | null = null;
    Object.entries(erreurs).forEach(([cle, message]) => {
        const champ = cle.split('.')[0] as T;
        const index = champsEtapes.findIndex((champs) => champs.includes(champ));
        if (index < 0) {
            autre ??= message;
            return;
        }
        setError(champ, { type: 'server', message });
        fautive = fautive < 0 ? index : Math.min(fautive, index);
    });
    if (fautive >= 0) setEtape(fautive);
    return autre;
}

/** Badge d'état générique : la page fournit la correspondance valeur → variante et libellé. */
export function EtatBadge({ variante, children, className }: { variante: Variante; children: ReactNode; className?: string }) {
    return (
        <Badge variant={variante} className={className}>
            {children}
        </Badge>
    );
}

/** Bloc blanc à bordure fine avec titre (sections des fiches et des tableaux de bord). */
export function Panneau({
    titre,
    description,
    actions,
    children,
    className,
    bodyClassName,
}: {
    titre: ReactNode;
    description?: ReactNode;
    actions?: ReactNode;
    children: ReactNode;
    className?: string;
    bodyClassName?: string;
}) {
    return (
        <section className={cn('overflow-hidden rounded-brand border bg-white', className)}>
            <header className="flex flex-wrap items-start justify-between gap-3 border-b px-5 py-3.5">
                <div className="min-w-0">
                    <h2 className="text-sm font-medium text-gray-900">{titre}</h2>
                    {description && <p className="mt-0.5 text-xs text-muted">{description}</p>}
                </div>
                {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
            </header>
            <div className={cn('px-5 py-4', bodyClassName)}>{children}</div>
        </section>
    );
}

/** Liste « libellé : valeur » (récapitulatifs et fiches). */
export function Lignes({ lignes, className }: { lignes: [string, ReactNode][]; className?: string }) {
    return (
        <dl className={cn('divide-y rounded-brand border text-sm', className)}>
            {lignes.map(([libelle, valeur]) => (
                <div key={libelle} className="flex flex-col gap-0.5 px-4 py-2.5 sm:flex-row sm:items-baseline sm:justify-between sm:gap-6">
                    <dt className="shrink-0 text-muted">{libelle}</dt>
                    <dd className="min-w-0 text-gray-900 sm:text-right">{valeur || <span className="text-muted">—</span>}</dd>
                </div>
            ))}
        </dl>
    );
}

export type EvenementChronologie = { id: string; titre: ReactNode; date?: string | null; texte?: ReactNode; ton?: 'primary' | 'accent' | 'danger' | 'muted' };

const TONS_CHRONO = {
    primary: 'border-primary bg-soft',
    accent: 'border-accent bg-[#FFF6E0]',
    danger: 'border-red-500 bg-red-50',
    muted: 'border-gray-300 bg-white',
};

/** Chronologie verticale (piste d'audit, historique d'un ordre de travail ou d'une sanction). */
export function Chronologie({ evenements, vide = 'Aucun événement enregistré.' }: { evenements: EvenementChronologie[]; vide?: string }) {
    if (evenements.length === 0) return <p className="py-4 text-center text-sm text-muted">{vide}</p>;

    return (
        <ol className="relative space-y-4 pl-6 before:absolute before:top-1.5 before:bottom-1.5 before:left-[7px] before:w-px before:bg-gray-200">
            {evenements.map((evenement) => (
                <li key={evenement.id} className="relative">
                    <span aria-hidden className={cn('absolute top-1 -left-6 size-3.5 rounded-full border-2', TONS_CHRONO[evenement.ton ?? 'primary'])} />
                    <div className="flex flex-wrap items-baseline justify-between gap-x-3">
                        <p className="text-sm font-medium text-gray-900">{evenement.titre}</p>
                        {evenement.date && <time className="text-xs text-muted">{dateHeure(evenement.date)}</time>}
                    </div>
                    {evenement.texte && <div className="mt-0.5 text-sm whitespace-pre-line text-gray-700">{evenement.texte}</div>}
                </li>
            ))}
        </ol>
    );
}

/** Carte de choix (bouton radio stylé) : portée d'une sanction, canal d'une annonce… */
export function ChoixCarte({
    actif,
    onClick,
    titre,
    description,
    icone,
    role = 'radio',
}: {
    actif: boolean;
    onClick: () => void;
    titre: string;
    description?: string;
    icone?: ReactNode;
    role?: 'radio' | 'checkbox';
}) {
    return (
        <button
            type="button"
            role={role}
            aria-checked={actif}
            onClick={onClick}
            className={cn(
                'flex w-full cursor-pointer items-start gap-3 rounded-brand border p-3 text-left transition-colors focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                actif ? 'border-primary bg-soft' : 'border-gray-300 bg-white hover:border-secondary',
            )}
        >
            {icone && <span className={cn('mt-0.5 shrink-0', actif ? 'text-primary' : 'text-muted')}>{icone}</span>}
            <span className="min-w-0">
                <span className={cn('block text-sm', actif ? 'font-medium text-primary' : 'text-gray-900')}>{titre}</span>
                {description && <span className="mt-0.5 block text-xs text-muted">{description}</span>}
            </span>
        </button>
    );
}

/** Encadré d'information ou d'alerte dans un formulaire. */
export function Encadre({ ton = 'info', icone, children }: { ton?: 'info' | 'warning' | 'danger'; icone?: ReactNode; children: ReactNode }) {
    const tons = {
        info: 'border-[#C9DCF3] bg-[#EEF5FC] text-[#0B3A6E]',
        warning: 'border-amber-200 bg-amber-50 text-amber-800',
        danger: 'border-red-200 bg-red-50 text-red-700',
    };
    return (
        <div className={cn('flex items-start gap-2 rounded-brand border px-4 py-3 text-sm', tons[ton])}>
            {icone && <span className="mt-0.5 shrink-0">{icone}</span>}
            <div className="min-w-0">{children}</div>
        </div>
    );
}
