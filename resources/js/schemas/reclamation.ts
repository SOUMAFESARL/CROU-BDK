import { z } from 'zod';

/** Confort de saisie uniquement : le serveur revérifie tout et rattache la réclamation à la demande. */
export const reclamationSchema = z.object({
    nom_prenoms: z.string().trim().min(3, 'Saisissez votre nom et vos prénoms complets.').max(150, 'Le nom et les prénoms ne doivent pas dépasser 150 caractères.'),
    num_bac_matricule: z.string().trim().min(1, 'Saisissez votre numéro de table au bac ou votre matricule.').max(50, 'Le numéro ne doit pas dépasser 50 caractères.'),
    type_demande: z.string().min(1, 'Choisissez le type de votre demande.'),
    motif: z.string().trim().min(10, 'Expliquez votre motif en quelques mots (10 caractères minimum).').max(2000, 'Le motif ne doit pas dépasser 2 000 caractères.'),
});

export type SaisieReclamation = z.infer<typeof reclamationSchema>;

/** Champs validés à chaque étape du formulaire public (identité, puis réclamation). */
export const CHAMPS_ETAPE_RECLAMATION: (keyof SaisieReclamation)[][] = [
    ['nom_prenoms', 'num_bac_matricule'],
    ['type_demande', 'motif'],
];

/** Traitement d'une réclamation : décision. */
export const traitementReclamationSchema = z.object({
    statut: z.enum(['acceptee', 'rejetee'], 'Choisissez la décision.'),
});

export type TraitementReclamation = z.infer<typeof traitementReclamationSchema>;

/** Période de réclamation (dates `aaaa-mm-jj`). */
export const periodeReclamationSchema = z
    .object({
        campagne_id: z.string().min(1, 'Choisissez la campagne à laquelle s’applique la réclamation.'),
        date_debut: z.string().min(1, 'La date de début est obligatoire.'),
        date_fin: z.string().min(1, 'La date de fin est obligatoire.'),
    })
    .refine((valeurs) => !valeurs.date_debut || !valeurs.date_fin || valeurs.date_fin >= valeurs.date_debut, {
        path: ['date_fin'],
        message: 'La date de fin doit être postérieure ou égale à la date de début.',
    });

export type PeriodeReclamation = z.infer<typeof periodeReclamationSchema>;
