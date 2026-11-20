import type { Paginated } from '@/types/global';
import type { EtatCriteres, Volet } from './criteres';

export type StatutCampagne = 'active' | 'planifier' | 'terminer';
export type FiltreCampagne = 'tous' | StatutCampagne;

export const NIVEAUX = {
    L1: 'Licence 1',
    L2: 'Licence 2',
    L3: 'Licence 3',
    M1: 'Master 1',
    M2: 'Master 2',
    D1: 'Doctorat 1',
    D2: 'Doctorat 2',
    D3: 'Doctorat 3',
} as const;

export type Niveau = keyof typeof NIVEAUX;

/** Types d'admission proposés par une campagne (au moins admission ou réadmission). */
export const TYPES_ADMISSION = [
    { name: 'admission', label: 'Admission', description: 'Premières demandes de logement' },
    { name: 'readmission', label: 'Réadmission', description: 'Étudiants déjà logés qui renouvellent' },
    { name: 'internationnaux', label: 'Étudiants internationaux', description: 'Ouvrir la campagne aux étudiants étrangers' },
] as const;

/** Type de campagne choisi dans le formulaire ; enregistré dans les colonnes `admission` et `readmission`. */
export const TYPES_CAMPAGNE = [
    { value: 'admission', label: 'Admission', description: 'Premières demandes de logement' },
    { value: 'readmission', label: 'Réadmission', description: 'Étudiants déjà logés qui renouvellent' },
    { value: 'admission_readmission', label: 'Admission et réadmission', description: 'Nouvelles demandes et renouvellements' },
] as const;

export type TypeCampagne = (typeof TYPES_CAMPAGNE)[number]['value'];

/** Conditions oui / non de la campagne. */
export const CONDITIONS = [
    { name: 'redoublement', label: 'Redoublants autorisés', description: 'Les étudiants redoublants peuvent postuler' },
    { name: 'pmr', label: 'Étudiants PMR', description: 'Accepter les étudiants à mobilité réduite' },
    { name: 'is_participate', label: 'Vie en résidence', description: 'Participer aux activités organisées par le CROU Bondoukou' },
    { name: 'is_discipline', label: 'Étudiant discipliné', description: 'N’avoir causé aucun trouble à l’ordre ou à la discipline' },
] as const;

export type Condition = (typeof TYPES_ADMISSION)[number]['name'] | (typeof CONDITIONS)[number]['name'];

export const STATUTS_CAMPAGNE: Record<StatutCampagne, { label: string; variant: 'success' | 'info' | 'neutral' }> = {
    active: { label: 'Active', variant: 'success' },
    planifier: { label: 'Planifiée', variant: 'info' },
    terminer: { label: 'Terminée', variant: 'neutral' },
};

export type CampagneItem = {
    id: string;
    annee: string;
    /** Vide pour les campagnes créées avant l'ajout du libellé. */
    libelle: string | null;
    description: string | null;
    statut: StatutCampagne | string | null;
    periode_debut: string | null;
    periode_fin: string | null;
    /** Date de la validation automatique en lot ; null = chaque dossier est décidé dès sa réception. */
    date_validation_auto: string | null;
    criteres: {
        moyenne_min: number | null;
        pourcentage_validate: number | null;
        distance_min: number | null;
        /** Âge maximum par niveau, seulement les niveaux renseignés. */
        ages: Partial<Record<Niveau, number>>;
    };
    conditions: Record<Condition, boolean>;
    residences: { name: string; places: number }[];
    documents: string[];
    /** État des critères de chaque volet (null : volet fermé par la campagne). */
    criteres_etat: Record<Volet, EtatCriteres | null>;
    /** Actions autorisées pour l'administrateur connecté (null = non autorisée). */
    urls: {
        edit: string | null;
        destroy: string | null;
        /** Page « Critères de la campagne » (niveaux admission et/ou réadmission selon le type). */
        criteres: string | null;
    };
};

export type CampagnesProps = {
    campagnes: Paginated<CampagneItem>;
    stats: { active: number; planifier: number; terminer: number; total: number };
    etat: FiltreCampagne;
    /** Recherche (libellé, année, description) et année universitaire, appliquées côté serveur. */
    filters: { q: string; annee: string };
    /** Années universitaires des campagnes existantes, la plus récente d'abord. */
    annees: string[];
    urls: {
        hebergement: string;
        admissions: string | null;
        /** Onglet « Décision de la commission » (demandes validées ou mises en attente depuis les listes provisoires). */
        validees: string | null;
        /** Onglet « Listes définitives » (demandes approuvées en commission, depuis les demandes validées ou la file d’attente). */
        definitives: string | null;
        create: string | null;
        filtres: Record<FiltreCampagne, string>;
    };
};

export type ResidenceOption = { id: string; name: string; chambres: number };

export type CreateCampagneProps = {
    residences: ResidenceOption[];
    documents: { id: string; name: string }[];
    urls: { hebergement: string; index: string; submit: string | null };
};

/** Valeurs saisies dans l'assistant (dates `aaaa-mm-jj`, identifiants des cités et documents). */
export type CampagneFormValues = {
    libelle: string;
    annee_campagne: string;
    /** Vide pour une campagne antérieure dont le public n'a pas été renseigné. */
    type: TypeCampagne | '';
    description: string;
    periode_debut: string;
    periode_fin: string;
    residences: string[];
    documents: string[];
};

export type EditCampagneProps = CreateCampagneProps & {
    campagne: CampagneFormValues & { id: string };
    /** Dossiers (demandes, attributions, dérogations) déjà rattachés à la campagne. */
    dossiers: number;
};

/** `aaaa-mm-jj` → `jj/mm/aaaa` (sans passer par Date, donc sans décalage de fuseau). */
export const formatDate = (iso: string | null | undefined) => {
    if (!iso) return '—';
    const [annee, mois, jour] = iso.slice(0, 10).split('-');
    return jour && mois && annee ? `${jour}/${mois}/${annee}` : '—';
};

/** Nombre de jours d'une période, bornes incluses. */
export const dureeJours = (debut: string | null, fin: string | null) => {
    if (!debut || !fin) return null;
    const jours = Math.round((Date.parse(fin.slice(0, 10)) - Date.parse(debut.slice(0, 10))) / 86_400_000);
    return Number.isFinite(jours) ? jours + 1 : null;
};

export const nomCampagne = (campagne: Pick<CampagneItem, 'libelle' | 'annee'>) => campagne.libelle || `Campagne ${campagne.annee}`;

export const typeCampagne = (conditions: CampagneItem['conditions']) =>
    conditions.admission && conditions.readmission ? 'Admission et réadmission' : conditions.admission ? 'Admission' : conditions.readmission ? 'Réadmission' : '—';
