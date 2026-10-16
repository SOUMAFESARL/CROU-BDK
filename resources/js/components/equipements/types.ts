export type StatutStock = 'disponible' | 'faible' | 'rupture';

export type EquipementItem = {
    id: string;
    name: string;
    type: string | null;
    type_id: string | null;
    photo: string | null;
    stock_initial: number;
    stock_actuel: number;
    threshold: number;
    statut: StatutStock;
};

export type TypeEquipement = { id: string; name: string };

/** Libellé, variante de badge et couleur de barre de chaque statut de stock (mêmes seuils que `Equipement::setStockStatus()`). */
export const STATUTS_STOCK: Record<StatutStock, { label: string; variant: 'success' | 'warning' | 'danger'; barre: 'secondary' | 'accent' | 'danger' }> = {
    disponible: { label: 'Stock suffisant', variant: 'success', barre: 'secondary' },
    faible: { label: 'Stock faible', variant: 'warning', barre: 'accent' },
    rupture: { label: 'Rupture', variant: 'danger', barre: 'danger' },
};

/** Part du stock de référence encore en magasin, de 0 à 100. */
export const tauxStock = (actuel: number, reference: number) => (reference > 0 ? Math.max(0, Math.min(100, Math.round((actuel / reference) * 100))) : actuel > 0 ? 100 : 0);
