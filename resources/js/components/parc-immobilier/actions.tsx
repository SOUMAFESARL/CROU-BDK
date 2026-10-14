import { Eye, Pencil, Power, PowerOff, Trash2 } from 'lucide-react';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { estActif } from './labels';
import type { ParcItem } from './types';

/** Actions disponibles sur un élément du parc (gérées par la page : panneau, fenêtres de confirmation…). */
export type ParcActions = {
    voir: (item: ParcItem) => void;
    ouvrir: (item: ParcItem) => void;
    modifier: ((item: ParcItem) => void) | null;
    basculer: (item: ParcItem) => void;
    supprimer: (item: ParcItem) => void;
};

/** Le formulaire de modification existe pour les cités et bâtiments (lien) ; les chambres passent par la fenêtre. */
export const peutModifier = (item: ParcItem, actions: ParcActions) => item.type === 'chambre' ? actions.modifier !== null : item.urls.edit !== null;

/** Icônes d'action (voir, modifier, activer/désactiver, supprimer) avec info-bulles. */
export function ItemActions({ item, actions }: { item: ParcItem; actions: ParcActions }) {
    const actif = estActif(item);

    return (
        <RowActions>
            <RowAction label="Voir les détails" icon={Eye} tone="view" onClick={() => actions.voir(item)} />
            {peutModifier(item, actions) &&
                (item.type === 'chambre' ? (
                    <RowAction label="Modifier" icon={Pencil} tone="edit" onClick={() => actions.modifier?.(item)} />
                ) : (
                    <RowAction label="Modifier" icon={Pencil} tone="edit" href={item.urls.edit ?? undefined} />
                ))}
            {item.urls.status &&
                (actif ? (
                    <RowAction label="Désactiver" icon={PowerOff} tone="danger" onClick={() => actions.basculer(item)} />
                ) : (
                    <RowAction label="Activer" icon={Power} tone="success" onClick={() => actions.basculer(item)} />
                ))}
            {item.urls.destroy && <RowAction label="Supprimer" icon={Trash2} tone="danger" onClick={() => actions.supprimer(item)} />}
        </RowActions>
    );
}
