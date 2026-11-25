import { z } from 'zod';

/** Confort de saisie uniquement : le serveur revérifie le numéro et retrouve la demande. */
export const resultatSchema = z.object({
    numero: z.string().trim().min(1, 'Saisissez votre numéro de table au bac ou votre matricule.').max(50, 'Le numéro ne doit pas dépasser 50 caractères.'),
});

export type SaisieResultat = z.infer<typeof resultatSchema>;
