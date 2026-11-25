import { z } from 'zod';

/** Confort de saisie uniquement : le serveur revérifie le numéro et retrouve la demande. */
export const resultatSchema = z.object({
    numero: z.string().trim().min(1, 'Saisissez votre numéro de table au bac ou votre matricule.').max(50, 'Le numéro ne doit pas dépasser 50 caractères.'),
});

export type SaisieResultat = z.infer<typeof resultatSchema>;

/** Campagnes dont les résultats sont visibles (menu « Résultats d'admission » de l'administration). */
export const campagnesVisiblesSchema = z.object({
    campagnes: z.array(z.string()).min(1, 'Cochez au moins une campagne.'),
});

export type CampagnesVisibles = z.infer<typeof campagnesVisiblesSchema>;
