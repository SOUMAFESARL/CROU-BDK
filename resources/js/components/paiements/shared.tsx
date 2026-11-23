import { Badge } from '@/components/ui/badge';
import { montant } from '@/lib/systeme';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';

/** Montant au format de Configuration › Devises (« 12 500 FCFA » par défaut). */
export const fcfa = (valeur: number | string | null | undefined) => montant(valeur);

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

/**
 * Couleurs des types de paiement (`paiements.type_paiement`), bien contrastées pour se repérer d'un coup d'œil
 * dans les listes. Types système : couleur fixe ; types ajoutés au paramétrage : couleur stable déduite du code.
 */
const COULEURS_TYPE: Record<string, string> = {
    loyer: 'border-[#006400] bg-[#006400] text-white',
    avance: 'border-sky-700 bg-sky-600 text-white',
    caution: 'border-violet-300 bg-violet-100 text-violet-800',
    penalite: 'border-red-700 bg-red-600 text-white',
    autre: 'border-gray-500 bg-gray-500 text-white',
};

const COULEURS_TYPE_AJOUTE = [
    'border-[#FAAB04] bg-[#FAAB04] text-gray-900',
    'border-teal-700 bg-teal-600 text-white',
    'border-pink-700 bg-pink-600 text-white',
    'border-indigo-700 bg-indigo-600 text-white',
    'border-orange-700 bg-orange-600 text-white',
    'border-cyan-800 bg-cyan-700 text-white',
];

export const couleurTypePaiement = (code: string | null | undefined) => {
    const cle = (code ?? '').toLowerCase();
    if (COULEURS_TYPE[cle]) return COULEURS_TYPE[cle];
    const somme = [...cle].reduce((total, caractere) => total + caractere.charCodeAt(0), 0);
    return COULEURS_TYPE_AJOUTE[somme % COULEURS_TYPE_AJOUTE.length];
};

export function TypePaiementBadge({ code, label, className }: { code: string | null | undefined; label: string; className?: string }) {
    return <Badge className={cn('border font-semibold', couleurTypePaiement(code), className)}>{label || '—'}</Badge>;
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
