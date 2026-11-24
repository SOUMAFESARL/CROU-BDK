import { z } from 'zod';
import { telephoneObligatoire } from '@/lib/telephone';

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
export const fichier = (document: DocumentDemande) =>
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
/** Note au baccalauréat saisie sur 400 (ramenée sur 20 par le serveur). */
export const NOTE_BAC_MAX = 400;

const nombreValide = (valeur: string, min: number, max: number) => {
    const n = Number(valeur.replace(',', '.'));
    return Number.isFinite(n) && n >= min && n <= max;
};

/**
 * `terminales` : identifiants des niveaux « Terminale ». Niveau précédent = Terminale → note au bac sur 400, ville et numéro
 * de table du bac obligatoires ; sinon matricule obligatoire et moyenne générale sur 20 (la ville du bac n'est pas demandée).
 */
export const demandeHebergementSchema = (documents: DocumentDemande[], terminales: string[] = [], ordreNiveaux: string[] = []) =>
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
        nationalite: requis('La nationalité est obligatoire.').max(100, '100 caractères au maximum.'),
        // Études
        matricule: z.string().trim().max(50, '50 caractères au maximum.'),
        niveau_precedent: requis('Choisissez votre niveau précédent.'),
        niveau: requis('Choisissez votre niveau actuel.'),
        filiere: requis('Choisissez votre filière.'),
        // Moyenne générale (/20), ou note obtenue au bac (/400) quand le niveau précédent est la Terminale : contrôlée plus bas.
        moyenne: z.string().trim(),
        credit_valider: nombre(0, 60, 'Le nombre de crédits doit être compris entre 0 et 60.').refine((valeur) => valeur === '' || Number.isInteger(Number(valeur)), 'Saisissez un nombre entier de crédits.'),
        ville_bac: z.string().trim().max(150, '150 caractères au maximum.'),
        num_table_bac: z.string().trim().max(50, '50 caractères au maximum.'),
        // Situation sociale
        boursier: z.boolean(),
        // Adresse
        region: requis('Choisissez votre région.'),
        ville: requis('Choisissez votre ville.'),
        adresse: requis("L'adresse est obligatoire.").max(255, '255 caractères au maximum.'),
        distance: nombre(0, null, 'La distance doit être positive.'),
        // Documents et engagement
        documents: z.object(Object.fromEntries(documents.map((document) => [document.slug, fichier(document)]))),
        certifie: z.boolean().refine((valeur) => valeur, "Vous devez certifier l'exactitude des informations."),
    }).superRefine((donnees, ctx) => {
        // Rien à contrôler tant que le niveau précédent n'est pas choisi (les champs ne sont pas encore affichés).
        if (donnees.niveau_precedent === '') return;
        // `ordreNiveaux` : identifiants des niveaux du plus bas au plus haut.
        const rangActuel = ordreNiveaux.indexOf(donnees.niveau);
        const rangPrecedent = ordreNiveaux.indexOf(donnees.niveau_precedent);
        if (rangActuel >= 0 && rangPrecedent >= 0 && rangActuel < rangPrecedent) {
            ctx.addIssue({ code: 'custom', path: ['niveau'], message: 'Le niveau actuel ne peut pas être inférieur au niveau précédent.' });
        }
        const apresTerminale = terminales.includes(donnees.niveau_precedent);
        const [max, message] = apresTerminale
            ? [NOTE_BAC_MAX, `La note doit être comprise entre 1 et ${NOTE_BAC_MAX}.`]
            : [20, 'La moyenne doit être comprise entre 1 et 20.'];
        if (donnees.moyenne === '') {
            ctx.addIssue({ code: 'custom', path: ['moyenne'], message: 'Ce champ est obligatoire.' });
        } else if (!nombreValide(donnees.moyenne, 1, max)) {
            ctx.addIssue({ code: 'custom', path: ['moyenne'], message });
        }
        if (!apresTerminale && donnees.matricule === '') {
            ctx.addIssue({ code: 'custom', path: ['matricule'], message: 'Le matricule universitaire est obligatoire.' });
        }
        if (apresTerminale && donnees.ville_bac === '') {
            ctx.addIssue({ code: 'custom', path: ['ville_bac'], message: 'La ville/commune d’obtention du bac est obligatoire.' });
        }
        if (apresTerminale && donnees.num_table_bac === '') {
            ctx.addIssue({ code: 'custom', path: ['num_table_bac'], message: 'Le numéro de table au bac est obligatoire.' });
        }
    });

export type DemandeHebergement = z.infer<ReturnType<typeof demandeHebergementSchema>>;

export const ETAPES = ['Identité', 'Études', 'Situation', 'Adresse', 'Documents'] as const;

/** Champs validés à chaque étape de l'assistant (et utilisés pour replacer les erreurs serveur). */
export const CHAMPS_ETAPE: (keyof DemandeHebergement)[][] = [
    ['genre', 'name', 'date_naissance', 'lieu_naissance', 'nationalite', 'telephone', 'email', 'type_piece', 'num_piece', 'date_exp_piece', 'groupe_sanguin'],
    ['matricule', 'niveau_precedent', 'niveau', 'filiere', 'moyenne', 'credit_valider', 'ville_bac', 'num_table_bac'],
    ['boursier'],
    ['region', 'ville', 'adresse', 'distance'],
    ['documents', 'certifie'],
];
