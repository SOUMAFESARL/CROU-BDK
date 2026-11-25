import { Badge } from '@/components/ui/badge';

/** Libellés de `DossierWorkflow::LABELS` (état de `Demande.etat_dossier`). */
export const LIBELLES_ETAT_DOSSIER: Record<string, string> = {
    brouillon: 'Brouillon',
    soumis: 'Soumis',
    incomplet: 'Incomplet',
    en_etude: 'En étude',
    eligible: 'Recevable',
    confirme: 'Admis à loger',
    pre_attribue: 'Pré-attribué',
    accepte: 'Accepté par l’étudiant',
    attribue_definitif: 'Attribué définitif',
    refuse: 'Refusé',
    annule: 'Annulé',
    expire: 'Expiré',
    cloture: 'Clôturé',
};

const VARIANTES: Record<string, 'success' | 'neutral' | 'warning' | 'danger' | 'info'> = {
    brouillon: 'neutral',
    soumis: 'info',
    incomplet: 'warning',
    en_etude: 'info',
    eligible: 'success',
    confirme: 'success',
    pre_attribue: 'success',
    accepte: 'success',
    attribue_definitif: 'success',
    refuse: 'danger',
    annule: 'neutral',
    expire: 'neutral',
    cloture: 'neutral',
};

/** États du dossier sans badge : l'état « Soumis » (simple dépôt de la demande) n'est pas affiché. */
export const ETATS_SANS_BADGE = ['soumis'];

/** Badge de l'état du dossier (remplace le partiel `campagnes_admission/partials/etat-dossier`) ; rien pour `ETATS_SANS_BADGE`. */
export function EtatDossierBadge({ etat, labels }: { etat: string | null | undefined; labels?: Record<string, string> }) {
    if (!etat) return <Badge variant="warning">À vérifier</Badge>;
    if (ETATS_SANS_BADGE.includes(etat)) return null;
    return <Badge variant={VARIANTES[etat] ?? 'warning'}>{labels?.[etat] ?? LIBELLES_ETAT_DOSSIER[etat] ?? 'À vérifier'}</Badge>;
}
