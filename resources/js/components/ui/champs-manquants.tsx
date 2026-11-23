import { CircleAlert } from 'lucide-react';
import type { ChampManquant } from '@/lib/champs-manquants';
import { cn } from '@/lib/utils';

type Props = {
    manquants: ChampManquant[];
    /** Libellés des étapes (affichés à côté de chaque champ). */
    etapes: readonly string[];
    /** Ramène à l'étape où renseigner le champ. */
    onAller: (etape: number) => void;
    /** Fin de la phrase d'introduction : « … pour pouvoir {action} ». */
    action?: string;
    className?: string;
};

/** Récapitulatif d'un formulaire en étapes : champs obligatoires encore vides (l'enregistrement reste bloqué). */
export function ChampsManquants({ manquants, etapes, onAller, action = 'enregistrer', className }: Props) {
    if (manquants.length === 0) return null;
    return (
        <div role="status" className={cn('rounded-brand border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800', className)}>
            <p className="flex items-center gap-2">
                <CircleAlert className="size-4 shrink-0" aria-hidden />
                Renseignez les champs obligatoires suivants pour pouvoir {action} :
            </p>
            <ul className="mt-2 list-disc space-y-1 pl-10">
                {manquants.map((item) => (
                    <li key={item.cle}>
                        <button type="button" className="cursor-pointer text-left underline-offset-2 hover:underline" onClick={() => onAller(item.etape)}>
                            {item.libelle}
                        </button>
                        {etapes[item.etape] && <span className="text-amber-700/80"> · étape {etapes[item.etape]}</span>}
                    </li>
                ))}
            </ul>
        </div>
    );
}
