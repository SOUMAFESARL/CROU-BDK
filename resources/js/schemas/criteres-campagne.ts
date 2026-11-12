import { z } from "zod";
import {
    DEPARTAGE_VOLET,
    MERITE_ADMISSION,
    placesCasSociaux,
    type CritereDepartage,
    type PlacesCite,
    type Regles,
    type ReglesGenerales,
    type NiveauEtudeOption,
    type Volet,
} from "@/components/campagnes/criteres";

/**
 * Assistants des critères d'une campagne : paramétrage général et critères d'un volet (admission ou réadmission).
 * Mêmes bornes que `CampagneCriteresRequest` (Laravel reste la source de vérité). Les nombres sont saisis en texte
 * et convertis à l'envoi. Le bloc du volet (`readmission` ou `admission`) est présent et validé ; l'autre reste absent.
 */

const capitaliser = (texte: string) =>
    texte.charAt(0).toUpperCase() + texte.slice(1);

/** Nombre saisi en texte, borné ; `requis: false` accepte le champ vide, `decimal` accepte deux décimales. */
const nombreBorne = (
    label: string,
    min: number,
    max: number,
    { requis = true, decimal = false } = {},
) =>
    z
        .string()
        .trim()
        .superRefine((valeur, ctx) => {
            if (valeur === "") {
                if (requis)
                    ctx.addIssue({
                        code: "custom",
                        message: `Renseignez ${label}.`,
                    });
                return;
            }
            const n = Number(valeur.replace(",", "."));
            if (
                Number.isNaN(n) ||
                (decimal ? !/^\d+([.,]\d{1,2})?$/.test(valeur) : !Number.isInteger(n))
            )
                ctx.addIssue({
                    code: "custom",
                    message: decimal
                        ? `${capitaliser(label)} doit être un nombre (deux décimales au plus).`
                        : `${capitaliser(label)} doit être un nombre entier.`,
                });
            else if (n < min || n > max)
                ctx.addIssue({
                    code: "custom",
                    message: `${capitaliser(label)} doit être compris entre ${min} et ${max}.`,
                });
        });

const entier = (label: string, min: number, max: number, requis = true) =>
    nombreBorne(label, min, max, { requis });

const texte = (valeur: number | null | undefined) =>
    valeur === null || valeur === undefined ? "" : String(valeur);
const nombre = (valeur: string) =>
    valeur.trim() === "" ? null : Number(valeur.replace(",", "."));

/* ------------------------------------------------------------------ */
/* Paramétrage général                                                 */
/* ------------------------------------------------------------------ */

export const generalSchema = z.object({
    credits: z.object({
        seuil_defaut: entier("le pourcentage de crédits exigé", 0, 100),
        filieres: z.array(
            z.object({
                filiere_id: z.string().min(1, "Choisissez la filière."),
                seuil: entier("le pourcentage de la filière", 0, 100),
                concours: z.boolean(),
            }),
        ),
    }),
    rang_filiere: z.object({
        actif: z.boolean(),
        n: entier("le nombre de premiers par filière", 1, 100),
        groupe: z.enum(["filiere_niveau", "filiere"]),
    }),
    clubs: z.object({
        actif: z.boolean(),
        n: entier("le nombre de premiers des clubs", 1, 100),
    }),
    cas_sociaux: z.object({
        actif: z.boolean(),
        taux: entier("la part des cas sociaux", 0, 100),
        base: z.enum(["places_readmission_cite", "capacite_cite"]),
    }),
    comite: z.object({
        actif: z.boolean(),
        places: entier("le nombre de places du comité", 0, 100000),
    }),
    age_max: z.array(
        z.object({
            niveau: z.string(),
            age: entier("l’âge maximum", 15, 60, false),
        }),
    ),
    appliquer: z.boolean(),
});

export type GeneralFormInput = z.input<typeof generalSchema>;
export type GeneralFormOutput = z.output<typeof generalSchema>;

/** Valeurs initiales du paramétrage général ; un champ d'âge par niveau d'études (vide = pas de limite). */
export function valeursGenerales(
    regles: ReglesGenerales,
    niveaux: NiveauEtudeOption[],
    appliquer: boolean,
): GeneralFormInput {
    const ages = new Map(regles.age_max.map((ligne) => [ligne.niveau, ligne.age]));
    // Niveaux du paramétrage, puis ceux d'un âge enregistré pour un niveau supprimé depuis.
    const codes = [
        ...niveaux.map((niveau) => niveau.value),
        ...regles.age_max
            .map((ligne) => ligne.niveau)
            .filter((code) => !niveaux.some((niveau) => niveau.value === code)),
    ];

    return {
        credits: {
            seuil_defaut: texte(regles.credits.seuil_defaut),
            filieres: regles.credits.filieres.map((ligne) => ({
                filiere_id: ligne.filiere_id,
                seuil: texte(ligne.seuil),
                concours: ligne.concours,
            })),
        },
        rang_filiere: {
            actif: regles.rang_filiere.actif,
            n: texte(regles.rang_filiere.n),
            groupe: regles.rang_filiere.groupe,
        },
        clubs: { actif: regles.clubs.actif, n: texte(regles.clubs.n) },
        cas_sociaux: {
            actif: regles.cas_sociaux.actif,
            taux: texte(regles.cas_sociaux.taux),
            base: regles.cas_sociaux.base,
        },
        comite: {
            actif: regles.comite.actif,
            places: texte(regles.comite.places),
        },
        age_max: codes.map((code) => ({ niveau: code, age: texte(ages.get(code)) })),
        appliquer,
    };
}

/** Paramétrage général saisi → format attendu par le serveur. */
export function generalAEnvoyer(v: GeneralFormOutput) {
    const n = (valeur: string) => Number(valeur);
    return {
        credits: {
            seuil_defaut: n(v.credits.seuil_defaut),
            filieres: v.credits.filieres.map((ligne) => ({
                filiere_id: ligne.filiere_id,
                seuil: n(ligne.seuil),
                concours: ligne.concours,
            })),
        },
        rang_filiere: {
            actif: v.rang_filiere.actif,
            n: n(v.rang_filiere.n),
            groupe: v.rang_filiere.groupe,
        },
        clubs: { actif: v.clubs.actif, n: n(v.clubs.n) },
        cas_sociaux: {
            actif: v.cas_sociaux.actif,
            taux: n(v.cas_sociaux.taux),
            base: v.cas_sociaux.base,
        },
        comite: { actif: v.comite.actif, places: n(v.comite.places) },
        age_max: v.age_max
            .filter((ligne) => ligne.age.trim() !== "")
            .map((ligne) => ({ niveau: ligne.niveau, age: n(ligne.age) })),
    };
}

/** Une filière ne peut avoir qu'un seuil propre. */
export function reglesCroiseesGenerales(
    v: GeneralFormInput,
): Record<string, string> {
    const erreurs: Record<string, string> = {};
    const vues = new Set<string>();
    v.credits.filieres.forEach((ligne, index) => {
        if (ligne.filiere_id && vues.has(ligne.filiere_id))
            erreurs[`credits.filieres.${index}.filiere_id`] =
                "Cette filière a déjà son propre seuil.";
        vues.add(ligne.filiere_id);
    });
    return erreurs;
}

/* ------------------------------------------------------------------ */
/* Critères d'un volet                                                 */
/* ------------------------------------------------------------------ */

const placesCite = z.object({
    cite_id: z.string(),
    places: entier("le nombre de places", 0, 100000, false),
});

const blocReadmission = z.object({
    loyers: z.object({
        actif: z.boolean(),
        mois_exiges: entier("le nombre de mois exigés", 1, 12),
    }),
    credits_actif: z.boolean(),
    conduite_actif: z.boolean(),
    age_actif: z.boolean(),
    report_places: z.enum(["C6_puis_admission", "C6", "admission", "vacant"]),
});

const blocAdmission = z.object({
    merite: z.enum(["moyenne_bac_desc", "credits_desc"]),
    seuil_actif: z.boolean(),
    seuil: z.string(),
    age_actif: z.boolean(),
    ville_bac: z
        .string()
        .trim()
        .min(1, "Renseignez la ville de référence.")
        .max(100, "100 caractères au maximum."),
});

export const criteresSchema = z
    .object({
        calendrier: z.object({
            date_liste_provisoire: z.string(),
            delai_completude_jours: entier("le délai de complétude", 0, 60),
        }),
        recevabilite: z.object({ internationaux: z.boolean() }),
        readmission: blocReadmission.optional(),
        admission: blocAdmission.optional(),
        classement: z.object({
            // Case unique : priorité aux boursiers ou non.
            priorite_boursiers: z.boolean(),
            // Critères du volet, dans l'ordre ; un critère décoché est désactivé (au moins un reste actif).
            departage: z
                .array(
                    z.object({
                        critere: z.enum([
                            "age_plus_jeune",
                            "excellence",
                            "comportement",
                            "hors_ville_bac",
                        ]),
                        actif: z.boolean(),
                    }),
                )
                .min(1)
                .refine(
                    (liste) => liste.some((item) => item.actif),
                    "Gardez au moins un critère de départage actif.",
                ),
            k_liste_elargie: entier("le coefficient de la liste élargie", 1, 5),
        }),
        places: z.array(placesCite),
        appliquer: z.boolean(),
    })
    .superRefine((v, ctx) => {
        // Seuil de rejet en admission : borné selon le mérite (note sur 20 ou crédits), exigé s'il est actif.
        const a = v.admission;
        if (!a?.seuil_actif) return;
        const info = MERITE_ADMISSION[a.merite];
        const resultat = nombreBorne(info.seuil.toLowerCase(), 0, info.max, {
            decimal: true,
        }).safeParse(a.seuil);
        if (!resultat.success)
            ctx.addIssue({
                code: "custom",
                path: ["admission", "seuil"],
                message: resultat.error.issues[0]?.message ?? "Seuil invalide.",
            });
    });

export type CriteresFormInput = z.input<typeof criteresSchema>;
export type CriteresFormOutput = z.output<typeof criteresSchema>;

/** Valeurs initiales de l'assistant à partir des critères et des places enregistrés du volet. */
export function valeursInitiales(
    volet: Volet,
    regles: Regles,
    places: PlacesCite[],
    appliquer: boolean,
): CriteresFormInput {
    const { readmission: r, admission: a, classement: c } = regles;
    const criteresVolet = DEPARTAGE_VOLET[volet];

    return {
        calendrier: {
            date_liste_provisoire:
                regles.calendrier.date_liste_provisoire ?? "",
            delai_completude_jours: texte(
                regles.calendrier.delai_completude_jours,
            ),
        },
        recevabilite: { internationaux: regles.recevabilite.internationaux },
        readmission:
            volet === "readmission" && r
                ? {
                      loyers: {
                          actif: r.loyers.actif,
                          mois_exiges: texte(r.loyers.mois_exiges),
                      },
                      credits_actif: r.credits_actif,
                      conduite_actif: r.conduite.actif,
                      age_actif: r.age_actif,
                      report_places: r.report_places,
                  }
                : undefined,
        admission:
            volet === "admission" && a
                ? {
                      merite: a.merite,
                      seuil_actif: a.seuil_actif,
                      seuil: texte(a.seuil),
                      age_actif: a.age_actif,
                      ville_bac: a.ville_bac,
                  }
                : undefined,
        classement: {
            // Sans objet en admission : valeurs neutres, non envoyées.
            priorite_boursiers:
                (c.priorite_boursiers ?? "aucune") !== "aucune",
            // Actifs dans l'ordre enregistré, puis les critères du volet désactivés.
            departage: [
                ...c.departage
                    .filter((critere) => criteresVolet.includes(critere))
                    .map((critere) => ({ critere, actif: true })),
                ...criteresVolet
                    .filter((critere) => !c.departage.includes(critere))
                    .map((critere: CritereDepartage) => ({
                        critere,
                        actif: false,
                    })),
            ],
            k_liste_elargie: texte(c.k_liste_elargie),
        },
        places: places.map((ligne) => ({
            cite_id: ligne.cite_id,
            places: texte(ligne.places),
        })),
        appliquer,
    };
}

/** Critères saisis → format attendu par le serveur pour le volet. */
export function reglesAEnvoyer(volet: Volet, v: CriteresFormOutput) {
    const n = (valeur: string) => Number(valeur);
    const commun = {
        recevabilite: { internationaux: v.recevabilite.internationaux },
    };
    const classement = {
        departage: v.classement.departage
            .filter((item) => item.actif)
            .map((item) => item.critere),
        k_liste_elargie: n(v.classement.k_liste_elargie),
    };

    if (volet === "admission" && v.admission) {
        const a = v.admission;
        return {
            ...commun,
            calendrier: {
                date_liste_provisoire:
                    v.calendrier.date_liste_provisoire || null,
                delai_completude_jours: n(v.calendrier.delai_completude_jours),
            },
            admission: {
                merite: a.merite,
                seuil_actif: a.seuil_actif,
                seuil: nombre(a.seuil),
                age_actif: a.age_actif,
                ville_bac: a.ville_bac,
            },
            classement,
        };
    }

    const r = v.readmission!;
    return {
        ...commun,
        calendrier: {
            date_liste_provisoire: v.calendrier.date_liste_provisoire || null,
            delai_completude_jours: n(v.calendrier.delai_completude_jours),
        },
        readmission: {
            loyers: {
                actif: r.loyers.actif,
                mois_exiges: n(r.loyers.mois_exiges),
            },
            credits_actif: r.credits_actif,
            conduite: { actif: r.conduite_actif },
            age_actif: r.age_actif,
            report_places: r.report_places,
        },
        classement: {
            ...classement,
            priorite_boursiers: v.classement.priorite_boursiers
                ? "categorie"
                : "aucune",
        },
    };
}

/** Places saisies → format attendu par le serveur. */
export function placesAEnvoyer(v: CriteresFormOutput) {
    return v.places.map((ligne) => ({
        cite_id: ligne.cite_id,
        places: nombre(ligne.places),
    }));
}

/** Places réservées en réadmission (cas sociaux calculés par cité + comité) pour les places saisies. */
export function reservationsSaisies(
    v: CriteresFormInput,
    general: ReglesGenerales | null,
    capacites: Record<string, { capacite: number }>,
) {
    const saisies = v.places.filter((ligne) => ligne.places.trim() !== "");
    const total = saisies.reduce((s, l) => s + (nombre(l.places) ?? 0), 0);
    if (!general) return { total, casSociaux: 0, comite: 0, saisies: saisies.length };
    const casSociaux = saisies.reduce(
        (s, l) =>
            s +
            (placesCasSociaux(general.cas_sociaux, {
                capacite: capacites[l.cite_id]?.capacite ?? 0,
                places: nombre(l.places),
            }) ?? 0),
        0,
    );
    const comite = general.comite.actif ? general.comite.places : 0;
    return { total, casSociaux, comite, saisies: saisies.length };
}

type Contexte = {
    volet: Volet;
    capacites: Record<
        string,
        { cite: string; capacite: number; autre: number | null }
    >;
    cloture: string | null;
    controlerPlaces: boolean;
    controlerRegles: boolean;
    general: ReglesGenerales | null;
};

/**
 * Règles entre champs (mêmes contrôles que le service) : dates après la clôture, places du volet + places de l'autre
 * volet ≤ capacité, places réservées en réadmission (cas sociaux + comité) ≤ places de réadmission. Clé = chemin du champ.
 */
export function reglesCroisees(
    v: CriteresFormInput,
    ctx: Contexte,
): Record<string, string> {
    const erreurs: Record<string, string> = {};
    const jjmmaaaa = (iso: string) => iso.split("-").reverse().join("/");

    if (ctx.controlerRegles) {
        const liste = v.calendrier.date_liste_provisoire;
        if (ctx.cloture && liste && liste < ctx.cloture) {
            erreurs["calendrier.date_liste_provisoire"] =
                `La liste provisoire ne peut pas précéder la clôture des dépôts (${jjmmaaaa(ctx.cloture)}).`;
        }
        if (
            new Set(v.classement.departage.map((item) => item.critere)).size !==
            v.classement.departage.length
        ) {
            erreurs["classement.departage"] =
                "Chaque critère de départage ne doit apparaître qu’une fois.";
        }
    }

    if (ctx.controlerPlaces || ctx.controlerRegles) {
        v.places.forEach((ligne, index) => {
            const info = ctx.capacites[ligne.cite_id];
            if (!info) return;
            const places = nombre(ligne.places);
            const total = (places ?? 0) + (info.autre ?? 0);
            if (total > info.capacite) {
                erreurs[`places.${index}.places`] = info.autre
                    ? `${places ?? 0} places + ${info.autre} réservées à l’autre volet pour une capacité de ${info.capacite}.`
                    : `${places ?? 0} places pour une capacité de ${info.capacite}.`;
            }
        });
        if (ctx.volet === "readmission" && v.places.length > 0) {
            const r = reservationsSaisies(v, ctx.general, ctx.capacites);
            if (r.saisies > 0 && r.casSociaux + r.comite > r.total) {
                erreurs["places.0.places"] ??=
                    `Les places réservées (cas sociaux : ${r.casSociaux}, comité : ${r.comite}) dépassent les ${r.total} places de réadmission.`;
            }
        }
    }

    return erreurs;
}
