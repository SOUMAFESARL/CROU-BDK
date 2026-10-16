import { z } from "zod";

export const ETATS = [
    { code: "bon", libelle: "Bon", ton: "success" },
    { code: "moyen", libelle: "Moyen", ton: "warning" },
    { code: "mauvais", libelle: "Mauvais", ton: "danger" },
] as const;

export type Etat = (typeof ETATS)[number]["code"];

/** Éléments proposés pour une nouvelle pièce (l'étudiant peut les renommer, en ajouter ou en retirer). */
export const ELEMENTS_PAR_DEFAUT = ["Sol", "Murs", "Plafond", "Fenêtres", "Porte", "Électricité", "Équipements"];

export const PIECES_PAR_DEFAUT = ["Chambre", "Cuisine", "Salle de bain"];

/** Limite de `EdlPhotoService` : 6 photos générales par pièce. */
export const MAX_PHOTOS_PIECE = 6;

/** Date du jour `aaaa-mm-jj` (heure locale). */
export const aujourdHui = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

const element = z
    .object({
        nom: z.string().trim().min(1, "Nommez l’élément.").max(255, "255 caractères maximum."),
        /** Vide tant que l'étudiant n'a pas choisi (refusé). */
        etat: z
            .enum(["bon", "moyen", "mauvais"])
            .nullable()
            .refine((v) => v !== null, "Indiquez l’état de l’élément."),
        observation: z.string().max(2000, "2 000 caractères maximum."),
        /** Chemin déjà enregistré ou image `data:` à téléverser. */
        photo: z.string().nullable(),
    })
    // Même règle que dashboardController::buildData (revérifiée côté serveur).
    .refine((e) => e.etat !== "mauvais" || e.observation.trim() !== "", {
        path: ["observation"],
        message: "Commentaire obligatoire quand l’état est mauvais.",
    });

/**
 * État des lieux d'entrée saisi par l'étudiant (dashboardController::storeEdl / updateEdl).
 * Confort de saisie uniquement : le serveur revérifie le verrouillage, la chambre, les photos et les commentaires.
 */
export function entreeSchema(signatureExistante: boolean) {
    return z.object({
        date_etat: z
            .string()
            .min(1, "Indiquez la date de l’état des lieux.")
            .refine((v) => v <= aujourdHui(), "La date ne peut pas être dans le futur."),
        nbre_cle_recus: z
            .string()
            .regex(/^\d{1,2}$/, "Indiquez le nombre de clés remises (0 à 99)."),
        pieces: z
            .array(
                z.object({
                    nom: z.string().trim().min(1, "Nommez la pièce.").max(255, "255 caractères maximum."),
                    photos: z.array(z.string()).max(MAX_PHOTOS_PIECE, `${MAX_PHOTOS_PIECE} photos maximum par pièce.`),
                    elements: z.array(element).min(1, "Ajoutez au moins un élément à cette pièce."),
                }),
            )
            .min(1, "Ajoutez au moins une pièce."),
        observation_generale: z.string().max(5000, "5 000 caractères maximum."),
        signature_etudiant: z
            .string()
            .nullable()
            .refine((v) => signatureExistante || !!v, "Signez dans le cadre pour valider l’état des lieux."),
    });
}

export type Entree = z.infer<ReturnType<typeof entreeSchema>>;

/** Valeurs en cours de saisie (état d'un élément encore vide). */
export type EntreeSaisie = z.input<ReturnType<typeof entreeSchema>>;

/** Champs validés à chaque étape de l'assistant. */
export const CHAMPS_ETAPE: (keyof Entree)[][] = [
    ["date_etat", "nbre_cle_recus"],
    ["pieces"],
    ["observation_generale", "signature_etudiant"],
];

/** Signature de l'état des lieux de sortie établi par le CROU. */
export const signatureSortieSchema = z.object({
    signature_etudiant: z.string().nullable().refine((v) => !!v, "Signez dans le cadre pour valider l’état des lieux de sortie."),
    lu: z.literal(true, "Confirmez avoir pris connaissance des constatations."),
});

export type SignatureSortie = z.infer<typeof signatureSortieSchema>;
