import { z } from 'zod';

/**
 * Paramétrage des sanctions (`ParametrageSanctionsController`) : mêmes bornes que le serveur, qui reste la source de vérité.
 * Les nombres sont saisis en texte (virgule acceptée) puis convertis à l'envoi.
 */

/** Effets d'un type de sanction (`CatalogueSanctions::EFFETS`). */
export const EFFETS = ['aucun', 'suspension', 'expulsion'] as const;
export type Effet = (typeof EFFETS)[number];

/** Nombre décimal saisi en texte (deux décimales au plus), borné. */
const decimal = (label: string, min: number, max: number) =>
    z
        .string()
        .trim()
        .superRefine((valeur, ctx) => {
            if (valeur === '') {
                ctx.addIssue({ code: 'custom', message: `Renseignez ${label}.` });
                return;
            }
            if (!/^\d+([.,]\d{1,2})?$/.test(valeur)) {
                ctx.addIssue({ code: 'custom', message: 'Saisissez un nombre (deux décimales au plus).' });
                return;
            }
            const n = Number(valeur.replace(',', '.'));
            if (n < min || n > max) ctx.addIssue({ code: 'custom', message: `La valeur doit être comprise entre ${min} et ${max}.` });
        });

/** Valeur numérique d'une saisie validée. */
export const enNombre = (valeur: string) => Number(valeur.trim().replace(',', '.'));

/** « 2 », « 2,5 » : affichage des points sans décimales inutiles. */
export const points = (valeur: number) => String(Math.round(valeur * 100) / 100).replace('.', ',');

export const noteInitialeSchema = (max: number) => z.object({ note_initiale: decimal('la note de conduite initiale', 1, max) });
export type NoteInitialeValeurs = z.infer<ReturnType<typeof noteInitialeSchema>>;

export const typeSanctionSchema = (max: number) =>
    z.object({
        libelle: z.string().trim().min(1, 'Renseignez le libellé de la sanction.').max(100, '100 caractères au maximum.'),
        points_retires: decimal('le nombre de points retirés', 0, max),
        description: z.string().trim().max(1000, '1 000 caractères au maximum.'),
        effet: z.enum(EFFETS, { error: 'Choisissez l’effet de la sanction.' }),
        actif: z.boolean(),
    });
export type TypeSanctionValeurs = z.infer<ReturnType<typeof typeSanctionSchema>>;

/** Champs de chaque étape de l'assistant d'ajout / modification (le dernier écran est la vérification). */
export const ETAPES_TYPE: (keyof TypeSanctionValeurs)[][] = [['libelle', 'points_retires', 'description'], ['effet', 'actif'], []];
