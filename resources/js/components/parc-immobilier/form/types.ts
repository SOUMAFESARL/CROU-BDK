import { z } from 'zod';

export type Option = { id: string; name: string };

export type UserOption = Option & {
    matricule: string | null;
    telephone: string | null;
    telephone2: string | null;
    email: string | null;
    email2: string | null;
};

export type CiteOption = Option & { type_cite_id: string | null; actif: boolean };

export type BatimentOption = Option & {
    cite_id: string;
    type_occupation_id: string | null;
    type_architecture: 'simple' | 'complexe' | null;
    actif: boolean;
};

export type TypeChambreOption = Option & { montant: number; capacite: number; equipements: string[] };

export type CiteItem = {
    id: string;
    name: string;
    code_cite: string | null;
    type_cite: string | null;
    capacite_max: number | null;
    nombre_batiments: number | null;
    superficie: number | string | null;
    annee_construction: number | null;
    statut: string | null;
    responsable_id: string | null;
    telephone1: string | null;
    telephone2: string | null;
    telephone3: string | null;
    email1: string | null;
    email2: string | null;
    reglement_id: string | null;
    adresse: string | null;
    latitude: number | null;
    longitude: number | null;
    wifi_gratuit: boolean;
    salle_etude: boolean;
    infirmerie: boolean;
    restaurant: boolean;
    plan: string | null;
};

export type BatimentItem = {
    id: string;
    name: string;
    code_cite: string;
    type_occupation: string | null;
    responsable_id: string | null;
    etat_initial: string | null;
    type_architecture: 'simple' | 'complexe' | null;
    latitude: number | null;
    longitude: number | null;
    acces_handicape: boolean;
    ascenseur: boolean;
    gardiennage: boolean;
    description: string | null;
    total_chambres: number;
    total_etages: number;
    total_ailes: number;
    plan: string | null;
};

export type FormUrls = {
    hebergement: string;
    index: string;
    create: { cite: string; batiment: string; chambre: string };
    submit: string | null;
    chambresBatiment: string | null;
    chambresNiveau: string | null;
    massCreate: string | null;
    quickCreate: string | null;
    updateChambre: string | null;
};

/* ───────────── Règles Zod communes (valeurs saisies gardées en texte, converties à l'envoi) ───────────── */

const ENTIER = /^\d+$/;
const DECIMAL = /^\d+([.,]\d+)?$/;

/** Entier obligatoire compris entre min et max. */
export const entierObligatoire = (libelle: string, min: number, max: number) =>
    z
        .string()
        .trim()
        .min(1, `${libelle} est obligatoire.`)
        .refine((v) => ENTIER.test(v), `${libelle} doit être un nombre entier.`)
        .refine((v) => Number(v) >= min && Number(v) <= max, `${libelle} doit être compris entre ${min} et ${max}.`);

/** Entier facultatif compris entre min et max. */
export const entierFacultatif = (libelle: string, min: number, max: number) =>
    z
        .string()
        .trim()
        .refine((v) => v === '' || ENTIER.test(v), `${libelle} doit être un nombre entier.`)
        .refine((v) => v === '' || (Number(v) >= min && Number(v) <= max), `${libelle} doit être compris entre ${min} et ${max}.`);

/** Nombre décimal facultatif positif. */
export const decimalFacultatif = (libelle: string) =>
    z
        .string()
        .trim()
        .refine((v) => v === '' || DECIMAL.test(v), `${libelle} doit être un nombre positif.`);

/** Coordonnée GPS (latitude ±90, longitude ±180), obligatoire ou non. */
export const coordonnee = (libelle: string, limite: 90 | 180, obligatoire: boolean) =>
    z
        .string()
        .trim()
        .refine((v) => !obligatoire || v !== '', `${libelle} est obligatoire.`)
        .refine((v) => v === '' || /^-?\d+([.,]\d+)?$/.test(v), `${libelle} doit être un nombre (ex. ${limite === 90 ? '8.040200' : '-2.800000'}).`)
        .refine((v) => v === '' || Math.abs(Number(v.replace(',', '.'))) <= limite, `${libelle} doit être comprise entre -${limite} et ${limite}.`);

/** Téléphone ivoirien facultatif saisi par paires (« 07 00 00 00 00 »). */
export const telephoneFacultatif = (prefixes: string[]) =>
    z
        .string()
        .transform((v) => v.replace(/\D/g, ''))
        .refine((v) => v === '' || v.length === 10, 'Le téléphone doit contenir 10 chiffres.')
        .refine((v) => v === '' || prefixes.includes(v.slice(0, 2)), `Le téléphone doit commencer par ${prefixes.join(', ')}.`);

export const emailFacultatif = z
    .string()
    .trim()
    .max(255, '255 caractères au maximum.')
    .refine((v) => v === '' || z.email().safeParse(v).success, "L'adresse e-mail n'est pas valide.");

/** Préfixes des numéros ivoiriens (mêmes que `UserRequest::PREFIXES_TELEPHONE_CI`). */
export const PREFIXES_CI = ['01', '05', '07', '21', '25', '27'];

export const texte = (valeur: unknown) => (valeur === null || valeur === undefined ? '' : String(valeur));

/** Nom d'un type d'occupation normalisé (« Uniquement Homme » → « uniquement homme »). */
export const normaliser = (nom: string | null | undefined) =>
    (nom ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .trim()
        .toLowerCase();

export const estMixte = (nom: string | null | undefined) => normaliser(nom) === 'mixte';
