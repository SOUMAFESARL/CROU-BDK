import { Badge } from '@/components/ui/badge';
import { nombre } from '@/lib/text';

/** Montant au format « 12 500 FCFA ». */
export const fcfa = (valeur: number | string | null | undefined) => `${nombre(valeur)} FCFA`;

/** Montant abrégé pour les axes des graphiques (« 1,2 M », « 350 k »). */
export const fcfaCourt = (valeur: number) => {
    if (valeur >= 1_000_000) return `${nombre(valeur / 1_000_000, 1)} M`;
    if (valeur >= 1_000) return `${nombre(valeur / 1_000, 1)} k`;
    return nombre(valeur);
};

type Variante = 'success' | 'warning' | 'danger' | 'neutral' | 'info';

/** Couleur du badge selon `statut_paiement` (même correspondance que `Paiement::getStatutBadgeAttribute`). */
const VARIANTES: Record<string, Variante> = {
    valide: 'success',
    en_attente: 'warning',
    echoue: 'danger',
    annule: 'neutral',
    rembourse: 'info',
};

/** Variante à partir de la classe Bootstrap renvoyée par le modèle (`success`, `warning`, `secondary`…). */
export const varianteBadge = (classe: string | null | undefined): Variante =>
    classe === 'secondary' ? 'neutral' : (['success', 'warning', 'danger', 'info'].includes(classe ?? '') ? (classe as Variante) : 'neutral');

export function StatutPaiementBadge({ statut, label }: { statut: string; label: string }) {
    return <Badge variant={VARIANTES[statut] ?? 'neutral'}>{label}</Badge>;
}

/** Taux de recouvrement : vert à partir de 90 %, orange en dessous (seuil des anciens écrans). */
export const toneTaux = (taux: number) => (taux >= 90 ? 'primary' : 'accent');

export const MOIS = [
    { value: '01', label: 'Janvier' },
    { value: '02', label: 'Février' },
    { value: '03', label: 'Mars' },
    { value: '04', label: 'Avril' },
    { value: '05', label: 'Mai' },
    { value: '06', label: 'Juin' },
    { value: '07', label: 'Juillet' },
    { value: '08', label: 'Août' },
    { value: '09', label: 'Septembre' },
    { value: '10', label: 'Octobre' },
    { value: '11', label: 'Novembre' },
    { value: '12', label: 'Décembre' },
];
