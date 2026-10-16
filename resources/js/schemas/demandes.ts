import { z } from 'zod';

export const MAX_OBSERVATIONS = 2000;
export const MAX_MOTIF_TRANSITION = 5000;

/**
 * Demande de sortie de chambre (`dashboardController::storeDepartureRequest`).
 * Confort de saisie uniquement : le serveur revérifie le préavis, le motif et la chambre active.
 */
export function sortieSchema(dateMin: string, motifs: readonly string[]) {
    return z.object({
        date_sortie_prevue: z
            .string()
            .min(1, 'Choisissez la date de sortie souhaitée.')
            .refine((valeur) => valeur >= dateMin, 'La date de sortie doit être prévue au moins 15 jours à l’avance.'),
        motif: z.string().refine((valeur) => motifs.includes(valeur), 'Choisissez le motif de la sortie.'),
        observations: z.string().trim().max(MAX_OBSERVATIONS, `Ce champ ne doit pas dépasser ${MAX_OBSERVATIONS} caractères.`),
        conditions_acceptees: z.boolean().refine((valeur) => valeur, 'Vous devez accepter les conditions de sortie.'),
    });
}

export type Sortie = z.infer<ReturnType<typeof sortieSchema>>;

/** Champs validés à chaque étape de l'assistant de sortie. */
export const CHAMPS_ETAPE_SORTIE: (keyof Sortie)[][] = [['date_sortie_prevue', 'motif'], ['observations'], ['conditions_acceptees']];

/** Soumission ou annulation du dossier (`DossierWorkflowController::student`) : motif obligatoire pour annuler. */
export function transitionSchema(motifObligatoire: boolean) {
    return z.object({
        motif: z
            .string()
            .trim()
            .max(MAX_MOTIF_TRANSITION, `Le motif ne doit pas dépasser ${MAX_MOTIF_TRANSITION} caractères.`)
            .refine((valeur) => !motifObligatoire || valeur.length > 0, 'Indiquez le motif de l’annulation.'),
    });
}

export type Transition = z.infer<ReturnType<typeof transitionSchema>>;
