import { z } from "zod";
import {
    CODES_QUOTAS,
    DEPARTAGE_VOLET,
    placesCasSociaux,
    type CodeQuota,
    type CritereDepartage,
    type PlacesCite,
    type Regles,
    type Volet,
} from "@/components/campagnes/criteres";

/**
 * Assistant des critères d'un volet (admission ou réadmission) d'une campagne. Mêmes bornes que `CampagneCriteresRequest`
 * (Laravel reste la source de vérité). Les nombres sont saisis en texte et convertis à l'envoi.
 * Le bloc du volet (`readmission` ou `admission`) est présent et validé ; l'autre reste absent.
 */

const capitaliser = (texte: string) =>
    texte.charAt(0).toUpperCase() + texte.slice(1);

/** Entier saisi en texte, borné ; `requis: false` accepte le champ vide. */
const entier = (label: string, min: number, max: number, requis = true) =>
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
            const n = Number(valeur);
            if (!Number.isInteger(n))
                ctx.addIssue({
                    code: "custom",
                    message: `${capitaliser(label)} doit être un nombre entier.`,
                });
            else if (n < min || n > max)
                ctx.addIssue({
                    code: "custom",
                    message: `${capitaliser(label)} doit être compris entre ${min} et ${max}.`,
                });
        });

const placesCite = z.object({
    cite_id: z.string(),
    places: entier("le nombre de places", 0, 100000, false),
    C1: entier("le quota des écoles sur concours", 0, 100000, false),
    C3: entier("le quota des clubs", 0, 100000, false),
    C5: entier("le quota du comité des résidents", 0, 100000, false),
});

const blocReadmission = z.object({
    ue_actif: z.boolean(),
    seuil_ue: entier("le seuil d’UE validées", 0, 100),
    concours_actif: z.boolean(),
    seuil_concours: entier("le seuil des écoles sur concours", 0, 100),
    filieres_concours: z.array(z.string()),
    loyers: z.object({
        actif: z.boolean(),
        mois_exiges: entier("le nombre de mois exigés", 1, 12),
        tolerance_fcfa: entier("la tolérance", 0, 10000000),
    }),
    categories: z.object({
        C1: z.object({ actif: z.boolean() }),
        C2: z.object({
            actif: z.boolean(),
            n: entier("le nombre de premiers par groupe", 1, 100),
            groupe: z.enum(["filiere_niveau", "filiere"]),
        }),
        C3: z.object({
            actif: z.boolean(),
            n: entier("le nombre de premiers par discipline", 1, 100),
        }),
        C4: z.object({
            actif: z.boolean(),
            taux: entier("le taux des cas sociaux", 0, 100),
            base: z.enum(["places_readmission_cite", "capacite_cite"]),
        }),
        C5: z.object({ actif: z.boolean(), prealables: z.boolean() }),
        C6: z.object({ actif: z.boolean() }),
    }),
    report_places: z.enum(["C6_puis_admission", "C6", "admission", "vacant"]),
    age_max: entier("l’âge maximum", 15, 60, false),
});

const blocAdmission = z.object({
    merite: z.enum(["moyenne_bac_desc", "rang_orientation_asc"]),
    tranche_basse: entier("la première tranche", 1, 2000),
    tranche_haute: entier("la seconde tranche", 1, 2000),
    handicap_visible: z.enum(["commission", "aucune"]),
    age_max_L1: entier("l’âge maximum en L1", 15, 60, false),
});

export const criteresSchema = z.object({
    calendrier: z.object({
        date_controle_prealables: z.string(),
        date_liste_provisoire: z.string(),
        delai_completude_jours: entier("le délai de complétude", 0, 60),
    }),
    recevabilite: z.object({ internationaux: z.boolean() }),
    readmission: blocReadmission.optional(),
    admission: blocAdmission.optional(),
    classement: z.object({
        priorite_boursiers: z.enum(["categorie", "departage", "aucune"]),
        source_boursier: z.enum(["justifie", "declare"]),
        // Critères du volet, dans l'ordre ; un critère décoché est désactivé (au moins un reste actif).
        departage: z
            .array(
                z.object({
                    critere: z.enum([
                        "age_plus_jeune",
                        "excellence",
                        "comportement",
                        "eloignement",
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
    motif: z.string().max(1000, "1 000 caractères au maximum."),
});

export type CriteresFormInput = z.input<typeof criteresSchema>;
export type CriteresFormOutput = z.output<typeof criteresSchema>;

const texte = (valeur: number | null | undefined) =>
    valeur === null || valeur === undefined ? "" : String(valeur);
const nombre = (valeur: string) =>
    valeur.trim() === "" ? null : Number(valeur);

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
            date_controle_prealables:
                regles.calendrier.date_controle_prealables ?? "",
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
                      ue_actif: r.ue_actif,
                      seuil_ue: texte(r.seuil_ue),
                      concours_actif: r.concours_actif,
                      seuil_concours: texte(r.seuil_concours),
                      filieres_concours: r.filieres_concours,
                      loyers: {
                          actif: r.loyers.actif,
                          mois_exiges: texte(r.loyers.mois_exiges),
                          tolerance_fcfa: texte(r.loyers.tolerance_fcfa),
                      },
                      categories: {
                          C1: { actif: r.categories.C1.actif },
                          C2: {
                              actif: r.categories.C2.actif,
                              n: texte(r.categories.C2.n),
                              groupe: r.categories.C2.groupe,
                          },
                          C3: {
                              actif: r.categories.C3.actif,
                              n: texte(r.categories.C3.n),
                          },
                          C4: {
                              actif: r.categories.C4.actif,
                              taux: texte(r.categories.C4.taux),
                              base: r.categories.C4.base,
                          },
                          C5: {
                              actif: r.categories.C5.actif,
                              prealables: r.categories.C5.prealables,
                          },
                          C6: { actif: r.categories.C6.actif },
                      },
                      report_places: r.report_places,
                      age_max: texte(r.age_max),
                  }
                : undefined,
        admission:
            volet === "admission" && a
                ? {
                      merite: a.merite,
                      tranche_basse: texte(a.eloignement.tranches_km[0]),
                      tranche_haute: texte(a.eloignement.tranches_km[1]),
                      handicap_visible: a.handicap_visible,
                      age_max_L1: texte(a.age_max_L1),
                  }
                : undefined,
        classement: {
            priorite_boursiers: c.priorite_boursiers,
            source_boursier: c.source_boursier,
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
            ...(Object.fromEntries(
                CODES_QUOTAS.map((code) => [code, texte(ligne.quotas?.[code])]),
            ) as Record<CodeQuota, string>),
        })),
        appliquer,
        motif: "",
    };
}

/** Critères saisis → format attendu par le serveur pour le volet. */
export function reglesAEnvoyer(volet: Volet, v: CriteresFormOutput) {
    const n = (valeur: string) => Number(valeur);
    const commun = {
        recevabilite: { internationaux: v.recevabilite.internationaux },
        classement: {
            priorite_boursiers: v.classement.priorite_boursiers,
            source_boursier: v.classement.source_boursier,
            departage: v.classement.departage
                .filter((item) => item.actif)
                .map((item) => item.critere),
            k_liste_elargie: n(v.classement.k_liste_elargie),
        },
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
                eloignement: {
                    tranches_km: [n(a.tranche_basse), n(a.tranche_haute)],
                },
                handicap_visible: a.handicap_visible,
                age_max_L1: nombre(a.age_max_L1),
            },
        };
    }

    const r = v.readmission!;
    return {
        ...commun,
        calendrier: {
            date_controle_prealables:
                v.calendrier.date_controle_prealables || null,
            date_liste_provisoire: v.calendrier.date_liste_provisoire || null,
            delai_completude_jours: n(v.calendrier.delai_completude_jours),
        },
        readmission: {
            ue_actif: r.ue_actif,
            seuil_ue: n(r.seuil_ue),
            concours_actif: r.concours_actif,
            seuil_concours: n(r.seuil_concours),
            filieres_concours: r.filieres_concours,
            loyers: {
                actif: r.loyers.actif,
                mois_exiges: n(r.loyers.mois_exiges),
                tolerance_fcfa: n(r.loyers.tolerance_fcfa),
            },
            categories: {
                C1: { actif: r.categories.C1.actif },
                C2: {
                    actif: r.categories.C2.actif,
                    n: n(r.categories.C2.n),
                    groupe: r.categories.C2.groupe,
                },
                C3: { actif: r.categories.C3.actif, n: n(r.categories.C3.n) },
                C4: {
                    actif: r.categories.C4.actif,
                    taux: n(r.categories.C4.taux),
                    base: r.categories.C4.base,
                },
                C5: {
                    actif: r.categories.C5.actif,
                    prealables: r.categories.C5.prealables,
                },
                C6: { actif: r.categories.C6.actif },
            },
            report_places: r.report_places,
            age_max: nombre(r.age_max),
        },
    };
}

/** Places saisies → format attendu par le serveur (sous-quotas en réadmission seulement). */
export function placesAEnvoyer(volet: Volet, v: CriteresFormOutput) {
    return v.places.map((ligne) => ({
        cite_id: ligne.cite_id,
        places: nombre(ligne.places),
        ...(volet === "readmission"
            ? {
                  quotas: Object.fromEntries(
                      CODES_QUOTAS.map((code) => [code, nombre(ligne[code])]),
                  ),
              }
            : {}),
    }));
}

/** Taux et base du C4 saisis, lus comme des nombres (calcul du quota des cas sociaux pendant la saisie des places). */
export function c4Saisi(v: CriteresFormInput) {
    const c4 = v.readmission?.categories.C4;
    return c4
        ? { actif: c4.actif, taux: Number(c4.taux) || 0, base: c4.base }
        : null;
}

/** Sous-quotas d'une cité de réadmission : C1, C3, C5 des catégories actives + C4 calculé. */
export function sousQuotasSaisis(
    v: CriteresFormInput,
    ligne: CriteresFormInput["places"][number],
    capacite: number,
) {
    const categories = v.readmission?.categories;
    const c4 = c4Saisi(v);
    if (!categories) return 0;
    return (
        CODES_QUOTAS.filter((code) => categories[code].actif).reduce(
            (somme, code) => somme + (nombre(ligne[code]) ?? 0),
            0,
        ) +
        ((c4 &&
            placesCasSociaux(c4, { capacite, places: nombre(ligne.places) })) ??
            0)
    );
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
};

/**
 * Règles entre champs (mêmes contrôles que le service) : tranches croissantes, dates après la clôture,
 * places du volet + places de l'autre volet ≤ capacité, sous-quotas ≤ places de réadmission. Clé = chemin du champ.
 */
export function reglesCroisees(
    v: CriteresFormInput,
    ctx: Contexte,
): Record<string, string> {
    const erreurs: Record<string, string> = {};
    const jjmmaaaa = (iso: string) => iso.split("-").reverse().join("/");

    if (ctx.controlerRegles) {
        if (v.admission) {
            const basse = Number(v.admission.tranche_basse);
            const haute = Number(v.admission.tranche_haute);
            if (
                v.admission.tranche_basse &&
                v.admission.tranche_haute &&
                basse >= haute
            ) {
                erreurs["admission.tranche_haute"] =
                    "La seconde tranche doit être supérieure à la première.";
            }
        }
        const controle =
            ctx.volet === "readmission"
                ? v.calendrier.date_controle_prealables
                : "";
        const liste = v.calendrier.date_liste_provisoire;
        if (ctx.cloture && controle && controle < ctx.cloture) {
            erreurs["calendrier.date_controle_prealables"] =
                `La date de contrôle doit suivre la clôture des dépôts (${jjmmaaaa(ctx.cloture)}).`;
        }
        if (ctx.cloture && liste && liste < ctx.cloture) {
            erreurs["calendrier.date_liste_provisoire"] =
                `La liste provisoire ne peut pas précéder la clôture des dépôts (${jjmmaaaa(ctx.cloture)}).`;
        } else if (liste && controle && liste < controle) {
            erreurs["calendrier.date_liste_provisoire"] =
                "La liste provisoire doit être générée après le contrôle des préalables.";
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
            if (ctx.volet !== "readmission" || places === null) return;
            const sousQuotas = sousQuotasSaisis(v, ligne, info.capacite);
            if (sousQuotas > places) {
                erreurs[`places.${index}.C1`] =
                    `Les sous-quotas (C1, C3, C4, C5 : ${sousQuotas}) dépassent les ${places} places de réadmission.`;
            }
        });
    }

    return erreurs;
}
