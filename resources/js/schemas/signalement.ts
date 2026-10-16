import { z } from "zod";

/** Niveaux d'urgence acceptés par `dashboardController::storeOrdreTravail` (OrdreTravail::PRIORITES). */
export const PRIORITES = [
    {
        code: "faible",
        libelle: "Peut attendre",
        detail: "Gêne légère, sans conséquence immédiate.",
    },
    {
        code: "moyenne",
        libelle: "Normale",
        detail: "À traiter dans les prochains jours.",
    },
    {
        code: "elevee",
        libelle: "Urgente",
        detail: "Empêche d’utiliser la chambre normalement.",
    },
    {
        code: "critique",
        libelle: "Critique",
        detail: "Danger : fuite importante, électricité, sécurité.",
    },
] as const;

export const PERIODES = [
    "Matin (8h-12h)",
    "Après-midi (13h-17h)",
    "Soirée (17h-20h)",
] as const;

export const MAX_DESCRIPTION = 3000;

/** Date du jour `aaaa-mm-jj` (heure locale). */
export const aujourdHui = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

/**
 * Signalement d'un équipement défectueux depuis « Ma chambre ».
 * Confort de saisie uniquement : le serveur revérifie tout (catégorie, urgence, équipement affecté à la chambre).
 */
export function signalementSchema(categories: readonly string[]) {
    return z.object({
        /** Index de l'équipement dans la liste de la chambre, ou `autre`. */
        equipement: z
            .string()
            .min(1, "Choisissez l’équipement concerné, ou « Autre problème »."),
        categorie: z
            .string()
            .refine(
                (valeur) => categories.includes(valeur),
                "Choisissez la catégorie du problème.",
            ),
        titre: z
            .string()
            .trim()
            .min(3, "Donnez un titre court au problème (3 caractères minimum).")
            .max(255, "Le titre ne doit pas dépasser 255 caractères."),
        description: z
            .string()
            .trim()
            .min(
                10,
                "Décrivez le problème en quelques mots (10 caractères minimum).",
            )
            .max(
                MAX_DESCRIPTION,
                `La description ne doit pas dépasser ${MAX_DESCRIPTION} caractères.`,
            ),
        priorite: z.enum(
            PRIORITES.map((p) => p.code) as [string, ...string[]],
            "Indiquez le niveau d’urgence.",
        ),
        date_disponibilite: z
            .string()
            .refine(
                (valeur) => valeur === "" || valeur >= aujourdHui(),
                "La date de disponibilité ne peut pas être passée.",
            ),
        periode_disponibilite: z.string(),
    });
}

export type Signalement = z.infer<ReturnType<typeof signalementSchema>>;

/** Champs validés à chaque étape de l'assistant. */
export const CHAMPS_ETAPE: (keyof Signalement)[][] = [
    ["equipement", "categorie", "titre"],
    ["description", "priorite"],
    ["date_disponibilite", "periode_disponibilite"],
];
