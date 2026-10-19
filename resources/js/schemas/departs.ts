import { z } from 'zod';
import { aujourdHui, piecesSchema } from '@/schemas/etat-des-lieux';

/**
 * Formulaires du module Départs et états des lieux (admin).
 * Confort de saisie uniquement : le séjour actif, la demande approuvée, le seuil d'impayés et les retenues sont vérifiés par le serveur.
 */

/** Nouvelle demande de sortie (`DepartEtatDesLieuxController::storeDepartureRequest`). */
export const demandeSortieSchema = z.object({
    etudiant_id: z.string().min(1, 'Choisissez le résident qui quitte son logement.'),
    date_sortie_prevue: z
        .string()
        .min(1, 'Indiquez la date de sortie prévue.')
        .refine((v) => v >= aujourdHui(), 'La date de sortie ne peut pas être passée.'),
    motif: z.string().min(1, 'Choisissez le motif de la sortie.'),
    observations: z.string().max(2000, '2 000 caractères maximum.'),
});

export type DemandeSortie = z.infer<typeof demandeSortieSchema>;

export const CHAMPS_DEMANDE: (keyof DemandeSortie)[][] = [['etudiant_id'], ['date_sortie_prevue', 'motif', 'observations'], []];

/** Refus d'une demande de sortie : motif obligatoire (`decide`). */
export const refusSchema = z.object({
    motif_decision: z.string().trim().min(1, 'Indiquez le motif du refus.').max(2000, '2 000 caractères maximum.'),
});

export type Refus = z.infer<typeof refusSchema>;

/** Restitution de la caution après clôture (`restituerCaution`). */
export const restitutionSchema = z.object({
    methode_paiement: z.string().min(1, 'Choisissez le mode de restitution.'),
});

export type Restitution = z.infer<typeof restitutionSchema>;

/** Signature par le CROU de l'état des lieux d'entrée établi par l'étudiant (`updateEdl`). */
export const signatureEntreeSchema = z.object({
    verifie: z.literal(true, 'Confirmez avoir vérifié le constat avec l’étudiant.'),
    signature_crou: z
        .string()
        .nullable()
        .refine((v) => !!v, 'Signez dans le cadre pour valider l’état des lieux d’entrée.'),
});

export type SignatureEntree = z.infer<typeof signatureEntreeSchema>;

export const ETATS_CHAMBRE = [
    { value: 'disponible', label: 'Disponible', description: 'La chambre est remise en location.' },
    { value: 'maintenance', label: 'Maintenance', description: 'Une intervention est nécessaire avant la remise en location.' },
    { value: 'hors_service', label: 'Hors service', description: 'La chambre devient indisponible.' },
] as const;

/** État des lieux de sortie établi par le CROU (`storeEdl`) : clôture le séjour. */
export const sortieSchema = z.object({
    date_etat: z
        .string()
        .min(1, 'Indiquez la date de l’état des lieux.')
        .refine((v) => v <= aujourdHui(), 'La date ne peut pas être dans le futur.'),
    nbre_cle_recus: z.string().regex(/^\d{1,2}$/, 'Indiquez le nombre de clés rendues (0 à 99).'),
    etat_chambre_sortie: z
        .enum(['disponible', 'maintenance', 'hors_service'])
        .nullable()
        .refine((v) => v !== null, 'Choisissez le statut de la chambre après la sortie.'),
    pieces: piecesSchema,
    observation_generale: z.string().max(5000, '5 000 caractères maximum.'),
    signature_crou: z
        .string()
        .nullable()
        .refine((v) => !!v, 'La signature du CROU est obligatoire pour clôturer le séjour.'),
});

export type Sortie = z.infer<typeof sortieSchema>;
export type SortieSaisie = z.input<typeof sortieSchema>;

export const CHAMPS_SORTIE: (keyof Sortie)[][] = [['date_etat', 'nbre_cle_recus', 'etat_chambre_sortie'], ['pieces'], ['observation_generale', 'signature_crou']];

/** Montant entier saisi avec séparateurs de milliers (« 25 000 »). */
const montant = (message: string) => z.string().trim().min(1, message).regex(/^[\d\s ]+$/, 'Saisissez un montant en chiffres.');

/** Règles de clôture des séjours (`DepartureSettingController::update`). */
export const parametresSchema = z
    .object({
        notice_days: z
            .string()
            .trim()
            .min(1, 'Indiquez la durée du préavis.')
            .regex(/^\d+$/, 'Saisissez un nombre de jours entier.')
            .refine((v) => Number(v) <= 365, '365 jours maximum.'),
        late_notice_penalty: montant('Indiquez le montant de la pénalité (0 pour aucune).'),
        unpaid_blocking_threshold: montant('Indiquez le seuil d’impayés (0 pour ne jamais bloquer).'),
        grille: z.array(
            z.object({
                label: z.string().trim().min(1, 'Nommez la dégradation ou retirez la ligne.').max(255, '255 caractères maximum.'),
                amount: montant('Indiquez le montant retenu.'),
            }),
        ),
    })
    .superRefine((v, ctx) => {
        // Deux libellés identiques rendraient la correspondance avec les éléments ambiguë.
        const vus = new Map<string, number>();
        v.grille.forEach((ligne, index) => {
            const cle = ligne.label.trim().toLowerCase();
            if (!cle) return;
            if (vus.has(cle)) ctx.addIssue({ code: 'custom', path: ['grille', index, 'label'], message: 'Cette dégradation figure déjà dans la grille.' });
            else vus.set(cle, index);
        });
    });

export type Parametres = z.infer<typeof parametresSchema>;

export const enEntier = (valeur: string) => Number(valeur.replace(/\D/g, '') || 0);
