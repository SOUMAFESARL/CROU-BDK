import { z } from 'zod';

/**
 * Schémas des formulaires du paramétrage hébergement. Ils reprennent les règles des contrôleurs
 * (Laravel reste la source de vérité) et rendent obligatoires les champs indispensables.
 * Les nombres sont saisis en texte puis convertis à l'envoi.
 */

const nombreSaisi = (label: string, { min = 0, max, entier = true, requis = true }: { min?: number; max?: number; entier?: boolean; requis?: boolean } = {}) =>
    z
        .string()
        .trim()
        .superRefine((valeur, ctx) => {
            if (valeur === '') {
                if (requis) ctx.addIssue({ code: 'custom', message: `Renseignez ${label}.` });
                return;
            }
            const n = Number(valeur.replace(',', '.'));
            if (!Number.isFinite(n) || (entier && !Number.isInteger(n))) {
                ctx.addIssue({ code: 'custom', message: entier ? `${capitaliser(label)} doit être un nombre entier.` : `${capitaliser(label)} doit être un nombre.` });
            } else if (n < min || (max !== undefined && n > max)) {
                ctx.addIssue({ code: 'custom', message: max !== undefined ? `${capitaliser(label)} doit être compris entre ${min} et ${max}.` : `${capitaliser(label)} doit être au moins ${min}.` });
            }
        });

const capitaliser = (texte: string) => texte.charAt(0).toUpperCase() + texte.slice(1);

/** Valeur numérique d'une saisie validée (vide → null). */
export const enNombre = (valeur: string) => (valeur.trim() === '' ? null : Number(valeur.trim().replace(',', '.')));

const description = z.string().trim().max(2000, 'La description ne doit pas dépasser 2000 caractères.');

/**
 * Référentiels simples (`ReferentielPage`) : un texte par champ, avec la longueur maximale du contrôleur
 * (types de cité `name` ≤ 250, départements, filières, types d'utilisateur…). `requis` porte le message du champ obligatoire.
 */
export const referentielSchema = (champs: readonly { cle: string; label: string; max: number; requis?: string }[]) =>
    z.object(
        Object.fromEntries(
            champs.map((champ) => {
                const texte = z.string().trim().max(champ.max, `${champ.label} : ${champ.max} caractères au maximum.`);
                return [champ.cle, champ.requis ? texte.min(1, champ.requis) : texte];
            }),
        ),
    );
export type ReferentielValeurs = Record<string, string>;

/** `TypeChambreController::store/update`. */
export const typeChambreSchema = z.object({
    name: z.string().trim().min(1, 'Renseignez le nom du type.').max(100, 'Le nom ne doit pas dépasser 100 caractères.'),
    description,
    superficie: nombreSaisi('la superficie', { requis: false, max: 1000 }),
    montant: nombreSaisi('le loyer mensuel', { max: 10_000_000 }),
    caution: nombreSaisi('le nombre de mois de caution', { max: 24 }),
    avance: nombreSaisi('le nombre de mois d’avance', { max: 24 }),
    // Une liste vide n'est pas envoyée par le formulaire : elle ne viderait pas les équipements enregistrés.
    equipements: z.array(z.string()).min(1, 'Cochez au moins un équipement.'),
});
export type TypeChambreValeurs = z.infer<typeof typeChambreSchema>;

/** `documentController::store/update`. */
export const documentSchema = z.object({
    name: z.string().trim().min(1, 'Renseignez le nom du document.').max(100, 'Le nom ne doit pas dépasser 100 caractères.'),
    description,
    ordre_affiche: nombreSaisi('l’ordre d’affichage', { max: 999 }),
    delai_validite_mois: nombreSaisi('le délai de validité', { requis: false, max: 120 }),
    requis: z.boolean(),
    actif: z.boolean(),
});
export type DocumentValeurs = z.infer<typeof documentSchema>;

/** `ScoringCampagneController::store` : chaque valeur entre 0 et 100, pondérations totalisant 100 %. */
export const scoringSchema = (fields: Record<string, Record<string, string>>) =>
    z
        .object({
            weights: z.object(Object.fromEntries(Object.keys(fields).map((g) => [g, nombreSaisi('la pondération', { max: 100, entier: false })]))),
            points: z.object(
                Object.fromEntries(
                    Object.entries(fields).map(([g, champs]) => [g, z.object(Object.fromEntries(Object.keys(champs).map((k) => [k, nombreSaisi('le nombre de points', { max: 100, entier: false })])))]),
                ),
            ),
            distance_thresholds: z.object(
                Object.fromEntries(Object.keys(SEUILS_DISTANCE_DEFAUT).map((k) => [k, nombreSaisi('le seuil de distance', { min: 1, max: 10000 })])),
            ),
            minimum_score: nombreSaisi('le score minimum', { max: 100, entier: false }),
            pmr_first: z.boolean(),
            active: z.boolean(),
        })
        .superRefine((v, ctx) => {
            // Tranches de distance : chaque seuil doit dépasser le précédent.
            let precedent: number | null = null;
            for (const cle of Object.keys(SEUILS_DISTANCE_DEFAUT)) {
                const seuil = enNombre((v.distance_thresholds as Record<string, string>)[cle]);
                if (seuil === null || !Number.isFinite(seuil)) return;
                if (precedent !== null && seuil <= precedent) {
                    ctx.addIssue({ code: 'custom', path: ['distance_thresholds', cle], message: 'Ce seuil doit être supérieur au précédent.' });
                }
                precedent = seuil;
            }
        })
        .superRefine((v, ctx) => {
            const valeurs = Object.values(v.weights as Record<string, string>).map(enNombre);
            if (valeurs.some((n) => n === null || !Number.isFinite(n))) return;
            const total = valeurs.reduce<number>((somme, n) => somme + (n ?? 0), 0);
            if (Math.abs(total - 100) > 0.00001) {
                ctx.addIssue({ code: 'custom', path: ['weights'], message: `Le total des pondérations doit être égal à 100 % (actuellement ${arrondi(total)} %).` });
            }
        });
/** Début (km) de chaque tranche de distance au-delà de « local » (`CampaignScoringService::DISTANCE_THRESHOLDS`). */
export const SEUILS_DISTANCE_DEFAUT = { proche: 50, loin: 100, tres_loin: 200 } as const;

export type ScoringValeurs = {
    weights: Record<string, string>;
    points: Record<string, Record<string, string>>;
    distance_thresholds: Record<string, string>;
    minimum_score: string;
    pmr_first: boolean;
    active: boolean;
};

export const arrondi = (n: number) => Math.round(n * 100) / 100;

const heure = /^([01]\d|2[0-3]):[0-5]\d$/;
const minutes = (h: string) => Number(h.slice(0, 2)) * 60 + Number(h.slice(3, 5));

/** `HousingEntryController::update` : les créneaux deviennent obligatoires quand la programmation est activée. */
export const entreeSchema = (cles: string[]) =>
    z
        .object({
            enabled: z.boolean(),
            proposal_hours: nombreSaisi('le délai de réponse', { min: 1, max: 720 }),
            appointments_enabled: z.boolean(),
            start_date: z.string(),
            weekdays: z.array(z.number().int().min(1).max(7)),
            opening_time: z.string(),
            closing_time: z.string(),
            slot_minutes: nombreSaisi('la durée du créneau', { min: 5, max: 240 }),
            capacity: nombreSaisi('le nombre d’étudiants par créneau', { min: 1, max: 100 }),
            lead_hours: nombreSaisi('le délai minimal', { max: 720 }),
            location: z.string().trim().max(255, 'Le lieu ne doit pas dépasser 255 caractères.'),
            contract: z.object(
                Object.fromEntries(cles.map((cle) => [cle, z.string().trim().min(1, 'Ce champ est obligatoire.').max(3000, '3000 caractères au maximum.')])),
            ),
        })
        .superRefine((v, ctx) => {
            if (!v.appointments_enabled) return;
            if (!v.start_date) ctx.addIssue({ code: 'custom', path: ['start_date'], message: 'Choisissez la date de début des rendez-vous.' });
            if (v.weekdays.length === 0) ctx.addIssue({ code: 'custom', path: ['weekdays'], message: 'Choisissez au moins un jour d’accueil.' });
            if (!heure.test(v.opening_time)) ctx.addIssue({ code: 'custom', path: ['opening_time'], message: 'Indiquez l’heure d’ouverture.' });
            if (!heure.test(v.closing_time)) ctx.addIssue({ code: 'custom', path: ['closing_time'], message: 'Indiquez l’heure de fermeture.' });
            if (!v.location.trim()) ctx.addIssue({ code: 'custom', path: ['location'], message: 'Indiquez le lieu d’accueil.' });
            if (heure.test(v.opening_time) && heure.test(v.closing_time)) {
                const plage = minutes(v.closing_time) - minutes(v.opening_time);
                if (plage <= 0) ctx.addIssue({ code: 'custom', path: ['closing_time'], message: 'La fermeture doit être après l’ouverture.' });
                else if (plage < Number(v.slot_minutes)) ctx.addIssue({ code: 'custom', path: ['slot_minutes'], message: 'La plage d’accueil doit contenir au moins un créneau.' });
            }
        });
export type EntreeValeurs = z.infer<ReturnType<typeof entreeSchema>>;
