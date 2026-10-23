import { z } from "zod";
import { maintenantLocal } from "@/components/vie-residentielle/shared";

/** Niveaux d'urgence acceptés par `DisciplinaryIncidentController::store`. */
export const GRAVITES = [
    { code: "faible", libelle: "Faible", detail: "Gêne ponctuelle." },
    { code: "moyenne", libelle: "Moyenne", detail: "Intervention souhaitée." },
    { code: "grave", libelle: "Grave", detail: "La sécurité est menacée." },
    { code: "critique", libelle: "Critique", detail: "Danger immédiat." },
] as const;

export const MIN_DESCRIPTION = 20;
export const MAX_DESCRIPTION = 5000;
export const MAX_TEMOINS = 2000;

/**
 * Déclaration d'un incident disciplinaire depuis l'espace étudiant.
 * Confort de saisie uniquement : le serveur revérifie tout (nature, date passée, chambre active).
 */
export function incidentSchema(natures: readonly string[]) {
    return z.object({
        nature: z
            .string()
            .refine(
                (valeur) => natures.includes(valeur),
                "Choisissez la nature de l’incident.",
            ),
        date_incident: z
            .string()
            .min(1, "Indiquez la date et l’heure de l’incident.")
            .refine(
                (valeur) => valeur <= maintenantLocal(),
                "La date de l’incident ne peut pas être future.",
            ),
        lieu: z
            .string()
            .trim()
            .min(1, "Indiquez le lieu précis de l’incident.")
            .max(255, "Le lieu ne doit pas dépasser 255 caractères."),
        gravite: z.enum(
            GRAVITES.map((g) => g.code) as [string, ...string[]],
            "Indiquez le niveau d’urgence.",
        ),
        description: z
            .string()
            .trim()
            .min(
                MIN_DESCRIPTION,
                `Décrivez les faits en au moins ${MIN_DESCRIPTION} caractères.`,
            )
            .max(
                MAX_DESCRIPTION,
                `La description ne doit pas dépasser ${MAX_DESCRIPTION} caractères.`,
            ),
        temoins: z
            .string()
            .max(
                MAX_TEMOINS,
                `Les témoins ne doivent pas dépasser ${MAX_TEMOINS} caractères.`,
            ),
        confidentiel: z.boolean(),
        sincerite: z
            .boolean()
            .refine(
                (valeur) => valeur,
                "Certifiez que les informations communiquées sont sincères.",
            ),
    });
}

export type Incident = z.infer<ReturnType<typeof incidentSchema>>;

/** Champs validés à chaque étape de l'assistant. */
export const CHAMPS_ETAPE: (keyof Incident)[][] = [
    ["nature", "date_incident", "lieu", "gravite"],
    ["description", "temoins"],
    ["confidentiel", "sincerite"],
];
