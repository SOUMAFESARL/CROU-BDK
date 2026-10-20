/**
 * Paramètres de Configuration partagés par toutes les pages (prop Inertia `systeme`, HandleInertiaRequests) :
 * nom du CROU et format des montants (Configuration › Devises). Mis à jour à chaque navigation par `admin.tsx`,
 * ce qui permet aux simples fonctions de formatage (`fcfa`…) de les lire sans hook.
 */
export type Systeme = {
    nom: string;
    nomComplet: string;
    devise: { symbole: string; position: 'before' | 'after'; separateur: ' ' | '.' | ',' };
    /** Modes acceptés (Configuration › Paiements) : cash, mobile, transfer, card. */
    modesPaiement: string[];
};

let courant: Systeme = {
    nom: 'CROU Bondoukou',
    nomComplet: 'Centre Régional des Œuvres Universitaires de Bondoukou',
    devise: { symbole: 'FCFA', position: 'after', separateur: ' ' },
    modesPaiement: ['cash', 'mobile'],
};

export function definirSysteme(valeur: Systeme | null | undefined) {
    if (valeur) courant = valeur;
}

export const systeme = () => courant;

/** Symbole monétaire affiché (ex. « FCFA »), pour les libellés de champs. */
export const symboleDevise = () => courant.devise.symbole;

/** Nombre entier avec le séparateur de milliers configuré (ex. « 12 500 »). */
export function nombreSysteme(valeur: number | string | null | undefined): string {
    const n = Math.round(Number(valeur ?? 0)) || 0;
    const signe = n < 0 ? '-' : '';
    return signe + String(Math.abs(n)).replace(/\B(?=(\d{3})+(?!\d))/g, courant.devise.separateur);
}

/** Montant avec symbole à la position configurée (ex. « 12 500 FCFA » ou « FCFA 12 500 »). */
export function montant(valeur: number | string | null | undefined): string {
    const { symbole, position } = courant.devise;
    return position === 'before' ? `${symbole} ${nombreSysteme(valeur)}` : `${nombreSysteme(valeur)} ${symbole}`;
}
