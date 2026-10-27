import { z } from 'zod';

export const MAX_SITUATION = 2000;

/** Nombre facultatif saisi en texte : vide accepté, sinon borné (le serveur revérifie). */
const nombre = (min: number, max: number | null, message: string) =>
    z
        .string()
        .trim()
        .refine((valeur) => {
            if (valeur === '') return true;
            const n = Number(valeur.replace(',', '.'));
            return Number.isFinite(n) && n >= min && (max === null || n <= max);
        }, message);

/**
 * Demande de réadmission (`ReadmissionController::store`).
 * Confort de saisie uniquement : le serveur revérifie tout (campagne active, délai, séjour clôturé, dossier existant).
 */
export const readmissionSchema = z.object({
    campagne_id: z.string().min(1, 'Choisissez la campagne.'),
    niveau: z.string().min(1, 'Choisissez votre niveau d’étude actuel.'),
    moyenne: nombre(0, 20, 'La moyenne doit être comprise entre 0 et 20.'),
    credit_valider: nombre(0, 60, 'Le nombre de crédits doit être compris entre 0 et 60.'),
    boursier: z.boolean(),
    cas_social: z.string().trim().max(MAX_SITUATION, `Ce champ ne doit pas dépasser ${MAX_SITUATION} caractères.`),
});

export type Readmission = z.infer<typeof readmissionSchema>;

/** Champs validés à chaque étape de l'assistant. */
export const CHAMPS_ETAPE: (keyof Readmission)[][] = [['campagne_id', 'niveau'], ['moyenne', 'credit_valider', 'boursier'], ['cas_social']];
