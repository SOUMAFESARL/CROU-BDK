import { z } from 'zod';
import { type DocumentDemande, fichier } from '@/schemas/demande-hebergement';

export const MAX_SITUATION = 2000;

/** Nombre obligatoire saisi en texte, borné (le serveur revérifie). */
const nombre = (min: number, max: number | null, message: string, obligatoire: string) =>
    z
        .string()
        .trim()
        .min(1, obligatoire)
        .refine((valeur) => {
            if (valeur === '') return true;
            const n = Number(valeur.replace(',', '.'));
            return Number.isFinite(n) && n >= min && (max === null || n <= max);
        }, message);

/**
 * Demande de réadmission (`ReadmissionPageController::store`), avec les pièces de la campagne choisie.
 * Confort de saisie uniquement : le serveur revérifie tout (campagne active, délai, séjour clôturé, dossier existant, pièces).
 */
export const readmissionSchema = (documents: DocumentDemande[]) =>
    z.object({
        campagne_id: z.string().min(1, 'Choisissez la campagne.'),
        niveau: z.string().min(1, 'Choisissez votre niveau d’étude actuel.'),
        filiere: z.string().trim().min(1, 'Choisissez votre filière.').max(150 + 'autre:'.length, 'Le nom de la filière ne doit pas dépasser 150 caractères.'),
        moyenne: nombre(0, 20, 'La moyenne doit être comprise entre 0 et 20.', 'Saisissez votre moyenne.'),
        credit_valider: nombre(0, 60, 'Le nombre de crédits doit être compris entre 0 et 60.', 'Saisissez le nombre de crédits validés.'),
        boursier: z.boolean(),
        documents: z.object(Object.fromEntries(documents.map((document) => [document.slug, fichier(document)]))),
        cas_social: z.string().trim().max(MAX_SITUATION, `Ce champ ne doit pas dépasser ${MAX_SITUATION} caractères.`),
    });

export type Readmission = {
    campagne_id: string;
    niveau: string;
    /** Identifiant du référentiel, ou `autre:<nom>` pour une saisie libre. */
    filiere: string;
    moyenne: string;
    credit_valider: string;
    boursier: boolean;
    /** Pièces indexées par slug du document. */
    documents: Record<string, File | null>;
    cas_social: string;
};

export const ETAPES = ['Campagne', 'Résultats universitaires', 'Documents', 'Situation'];

/** Champs validés à chaque étape de l'assistant. */
export const CHAMPS_ETAPE: (keyof Readmission)[][] = [['campagne_id', 'niveau', 'filiere'], ['moyenne', 'credit_valider', 'boursier'], ['documents'], ['cas_social']];
