import { Accessibility, PowerOff } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { ETATS, estActif, nombre } from './labels';
import type { ChambreItem, EtatChambre } from './types';

type RoomPlanProps = {
    chambres: ChambreItem[];
    /** Filtres en cours : les chambres qui n'y répondent pas restent visibles mais atténuées. */
    etat: EtatChambre | '';
    search: string;
    onSelect: (chambre: ChambreItem) => void;
};

const LEGENDE: EtatChambre[] = ['disponible', 'occupee', 'reservee', 'maintenance'];

/** Plan du bâtiment : toutes les chambres regroupées par niveau, colorées selon leur état. */
export function RoomPlan({ chambres, etat, search, onSelect }: RoomPlanProps) {
    const niveaux = new Map<number | null, ChambreItem[]>();
    [...chambres]
        .sort((a, b) => a.name.localeCompare(b.name, 'fr', { numeric: true }))
        .forEach((chambre) => niveaux.set(chambre.niveau, [...(niveaux.get(chambre.niveau) ?? []), chambre]));
    const ordre = [...niveaux.keys()].sort((a, b) => (a ?? Infinity) - (b ?? Infinity));
    const terme = search.trim().toLowerCase();
    const visible = (chambre: ChambreItem) =>
        (!etat || chambre.etat === etat) && (!terme || chambre.name.toLowerCase().includes(terme));

    if (chambres.length === 0) {
        return <p className="py-16 text-center text-sm text-muted">Aucune chambre configurée dans ce bâtiment.</p>;
    }

    return (
        <div className="space-y-6 p-4">
            <ul className="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-muted">
                {LEGENDE.map((cle) => (
                    <li key={cle} className="flex items-center gap-1.5">
                        <span className={cn('size-2.5 rounded-sm', ETATS[cle].dot)} aria-hidden />
                        {ETATS[cle].label}
                        <span className="font-medium text-gray-900">
                            {nombre.format(chambres.filter((c) => c.etat === cle || (cle === 'maintenance' && c.etat === 'hors_service')).length)}
                        </span>
                    </li>
                ))}
                <li className="flex items-center gap-1.5">
                    <PowerOff size={12} strokeWidth={1.5} aria-hidden />
                    Désactivée
                </li>
            </ul>

            {ordre.map((niveau) => {
                const liste = niveaux.get(niveau) ?? [];
                const occupees = liste.filter((c) => c.etat === 'occupee').length;

                return (
                    <section key={niveau ?? 'aucun'} aria-label={niveau === null ? 'Niveau non renseigné' : `Niveau ${niveau}`}>
                        <header className="mb-2.5 flex items-baseline gap-3">
                            <h3 className="text-sm font-medium text-gray-900">{niveau === null ? 'Niveau non renseigné' : `Niveau ${niveau}`}</h3>
                            <span className="text-xs text-muted">
                                {liste.length} chambre{liste.length > 1 ? 's' : ''} · {occupees} occupée{occupees > 1 ? 's' : ''}
                            </span>
                            <span className="h-px flex-1 bg-border" aria-hidden />
                        </header>
                        <div className="grid grid-cols-[repeat(auto-fill,minmax(4.75rem,1fr))] gap-2">
                            {liste.map((chambre) => {
                                const style = chambre.etat ? ETATS[chambre.etat] : null;
                                const actif = estActif(chambre);

                                return (
                                    <Tooltip key={chambre.id}>
                                        <TooltipTrigger asChild>
                                            <button
                                                type="button"
                                                onClick={() => onSelect(chambre)}
                                                className={cn(
                                                    'relative flex h-16 cursor-pointer flex-col items-center justify-center gap-1 rounded-brand border text-sm font-medium transition-all focus-visible:ring-2 focus-visible:ring-secondary focus-visible:ring-offset-1 focus-visible:outline-none',
                                                    style?.tile ?? 'border-gray-200 bg-gray-50 text-muted',
                                                    !actif && 'border-dashed opacity-60 saturate-0',
                                                    !visible(chambre) && 'opacity-25',
                                                )}
                                                aria-label={`Chambre ${chambre.name}, ${style?.label ?? 'état non renseigné'}${actif ? '' : ', désactivée'}`}
                                            >
                                                {!actif && <PowerOff size={11} strokeWidth={1.5} className="absolute top-1 right-1" aria-hidden />}
                                                {chambre.pmr && <Accessibility size={11} strokeWidth={1.5} className="absolute top-1 left-1" aria-hidden />}
                                                <span className="max-w-full truncate px-1">{chambre.name}</span>
                                                <span className="flex gap-0.5" aria-hidden>
                                                    {Array.from({ length: Math.min(Math.max(chambre.capacite, 1), 6) }, (_, index) => (
                                                        <span
                                                            key={index}
                                                            className={cn(
                                                                'size-1.5 rounded-full border border-current',
                                                                index < chambre.etudiants.length && 'bg-current',
                                                            )}
                                                        />
                                                    ))}
                                                </span>
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent sideOffset={4}>
                                            {chambre.type_chambre ?? 'Chambre'} · {chambre.etudiants.length}/{chambre.capacite} occupant
                                            {chambre.capacite > 1 ? 's' : ''}
                                        </TooltipContent>
                                    </Tooltip>
                                );
                            })}
                        </div>
                    </section>
                );
            })}
        </div>
    );
}
