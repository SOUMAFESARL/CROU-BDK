import { z } from 'zod';
import { telephoneObligatoire } from '@/lib/telephone';

export const MAX_SITUATION = 500;
/** Taille maximale d'une pièce jointe, alignée sur la règle serveur (`max:2048`). */
export const TAILLE_MAX_DOCUMENT = 2 * 1024 * 1024;

export type DocumentDemande = { slug: string; nom: string; requis: boolean; formats: string[] };

const requis = (message: string) => z.string().trim().min(1, message);

/** Nombre facultatif saisi en texte : vide accepté, sinon borné (le serveur revérifie). */
const nombre = (min: number, max: number | null, message: string, obligatoire?: string) =>
    z
        .string()
        .trim()
        .refine((valeur) => !obligatoire || valeur !== '', obligatoire ?? '')
        .refine((valeur) => {
            if (valeur === '') return true;
            const n = Number(valeur.replace(',', '.'));
            return Number.isFinite(n) && n >= min && (max === null || n <= max);
        }, message);

const extension = (nom: string) => nom.split('.').pop()?.toLowerCase() ?? '';

/** Pièce jointe : présence (si requise), taille et format attendus par la campagne. */
const fichier = (document: DocumentDemande) =>
    z
        .custom<File | null>((valeur) => valeur === null || valeur instanceof File)
        .refine((valeur) => !document.requis || valeur !== null, 'Ce document est obligatoire.')
        .refine((valeur) => !valeur || valeur.size <= TAILLE_MAX_DOCUMENT, 'Le fichier ne doit pas dépasser 2 Mo.')
        .refine(
            (valeur) => !valeur || document.formats.length === 0 || document.formats.includes(extension(valeur.name)),
            `Format attendu : ${document.formats.join(', ').toUpperCase()}.`,
        );

/**
 * Demande d'hébergement d'un nouvel étudiant (`frontedController::postRegister`).
 * Confort de saisie uniquement : unicité (e-mail, téléphone, pièce, matricule), campagne et éligibilité restent vérifiées par le serveur.
 */
export const demandeHebergementSchema = (documents: DocumentDemande[]) =>
    z.object({
        // Identité
        genre: requis('Choisissez la civilité.'),
        name: requis('Le nom et les prénoms sont obligatoires.').max(255, '255 caractères au maximum.'),
        date_naissance: requis('La date de naissance est obligatoire.'),
        lieu_naissance: requis('Le lieu de naissance est obligatoire.').max(255, '255 caractères au maximum.'),
        telephone: telephoneObligatoire(),
        email: requis("L'adresse e-mail est obligatoire.").pipe(z.email('Adresse e-mail invalide.')),
        type_piece: requis('Choisissez le type de pièce.'),
        num_piece: requis('Le numéro de la pièce est obligatoire.'),
        date_exp_piece: requis("La date d'expiration de la pièce est obligatoire."),
        groupe_sanguin: z.string(),
        // Études
        matricule: requis('Le matricule est obligatoire.'),
        annee_academique_precedent: requis("L'année académique précédente est obligatoire.").regex(/^\d{4}-\d{4}$/, 'Format attendu : 2025-2026.'),
        niveau_precedent: requis('Choisissez votre niveau précédent.'),
        niveau: requis('Choisissez votre niveau actuel.'),
        filiere: requis('Choisissez votre filière.'),
        moyenne: nombre(1, 20, 'La moyenne doit être comprise entre 1 et 20.', 'La moyenne est obligatoire.'),
        credit_valider: nombre(0, 60, 'Le nombre de crédits doit être compris entre 0 et 60.').refine((valeur) => valeur === '' || Number.isInteger(Number(valeur)), 'Saisissez un nombre entier de crédits.'),
        redoublement: z.boolean(),
        // Situation sociale
        boursier: z.boolean(),
        pmr: z.boolean(),
        cas_social: z.string().trim().max(MAX_SITUATION, `Ce champ ne doit pas dépasser ${MAX_SITUATION} caractères.`),
        // Adresse
        region: requis('Choisissez votre région.'),
        ville: requis('Choisissez votre ville.'),
        adresse: requis("L'adresse est obligatoire.").max(255, '255 caractères au maximum.'),
        nationalite: requis('La nationalité est obligatoire.').max(100, '100 caractères au maximum.'),
        distance: nombre(0, null, 'La distance doit être positive.'),
        // Documents et engagement
        documents: z.object(Object.fromEntries(documents.map((document) => [document.slug, fichier(document)]))),
        certifie: z.boolean().refine((valeur) => valeur, "Vous devez certifier l'exactitude des informations."),
        save_as_draft: z.boolean(),
    });

export type DemandeHebergement = z.infer<ReturnType<typeof demandeHebergementSchema>>;

export const ETAPES = ['Identité', 'Études', 'Situation', 'Adresse', 'Documents'] as const;

/** Champs validés à chaque étape de l'assistant (et utilisés pour replacer les erreurs serveur). */
export const CHAMPS_ETAPE: (keyof DemandeHebergement)[][] = [
    ['genre', 'name', 'date_naissance', 'lieu_naissance', 'telephone', 'email', 'type_piece', 'num_piece', 'date_exp_piece', 'groupe_sanguin'],
    ['matricule', 'annee_academique_precedent', 'niveau_precedent', 'niveau', 'filiere', 'moyenne', 'credit_valider', 'redoublement'],
    ['boursier', 'pmr', 'cas_social'],
    ['region', 'ville', 'adresse', 'nationalite', 'distance'],
    ['documents', 'certifie', 'save_as_draft'],
];
