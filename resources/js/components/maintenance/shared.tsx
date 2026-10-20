import { Badge } from '@/components/ui/badge';
import type { Variante } from '@/components/vie-residentielle/shared';
import { montant } from '@/lib/systeme';

export const PRIORITES = [
    { value: 'faible', label: 'Faible', description: 'Peut attendre une intervention planifiée.' },
    { value: 'moyenne', label: 'Moyenne', description: 'À traiter dans les prochains jours.' },
    { value: 'elevee', label: 'Élevée', description: 'Gêne importante pour les résidents.' },
    { value: 'critique', label: 'Critique', description: 'Danger ou panne bloquante : urgence.' },
] as const;

export const TYPES_INTERVENTION = [
    { value: 'corrective', label: 'Corrective' },
    { value: 'preventive', label: 'Préventive' },
    { value: 'urgente', label: 'Urgente' },
] as const;

/** Statuts de `OrdreTravail` : couleurs de `getStatutBadgeAttribute` ramenées à la charte. */
export const STATUTS_OT: Record<string, { label: string; variante: Variante }> = {
    en_attente: { label: 'En attente', variante: 'danger' },
    en_cours: { label: 'En cours', variante: 'warning' },
    en_attente_pieces: { label: 'En attente de pièces', variante: 'info' },
    termine: { label: 'Terminé', variante: 'success' },
    annule: { label: 'Annulé', variante: 'neutral' },
    reporte: { label: 'Reporté', variante: 'neutral' },
};

const VARIANTES_PRIORITE: Record<string, Variante> = { critique: 'danger', elevee: 'warning', moyenne: 'info', faible: 'neutral' };

export function PrioriteBadge({ priorite, label }: { priorite: string; label: string }) {
    return <Badge variant={VARIANTES_PRIORITE[priorite] ?? 'neutral'}>{label}</Badge>;
}

export function StatutOtBadge({ statut, label }: { statut: string; label?: string }) {
    const info = STATUTS_OT[statut];
    return <Badge variant={info?.variante ?? 'neutral'}>{label ?? info?.label ?? statut}</Badge>;
}

export const RESULTATS = [
    { value: 'resolu', label: 'Résolu' },
    { value: 'partiellement_resolu', label: 'Partiellement résolu' },
    { value: 'non_resolu', label: 'Non résolu' },
    { value: 'necessite_suivi', label: 'Nécessite un suivi' },
] as const;

export const fcfa = (valeur: number | string | null | undefined) => montant(valeur);

export type Maintenancier = { id: string; name: string; specialite: string; tel: string | null; email: string | null; cite: string | null };

export const libelleSpecialite = (specialite: string) => specialite.charAt(0).toUpperCase() + specialite.slice(1).replace(/_/g, ' ');
