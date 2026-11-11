import type { ComboboxOption } from '@/components/ui/combobox';
import type { StatutCampagne, TypeCampagne } from './types';

/**
 * Critères d'une campagne, définis en une fois pour chaque volet : critères d'admission (nouveaux étudiants)
 * et critères de réadmission (anciens résidents). Types et libellés partagés par la liste des campagnes et la page
 * « Critères de la campagne » du paramétrage (un niveau par volet ouvert).
 * Source : docs/notes/entonnoir-eligibilite-provisoire.pdf et plan-integration-campagne-criteres.pdf § 6.2.
 */

export type Volet = 'admission' | 'readmission';
export type CodeCategorie = 'C1' | 'C2' | 'C3' | 'C4' | 'C5' | 'C6';
export type CodeQuota = 'C1' | 'C3' | 'C5';
export type CritereDepartage = 'age_plus_jeune' | 'excellence' | 'comportement' | 'eloignement';

export type ReglesReadmission = {
    ue_actif: boolean;
    seuil_ue: number;
    concours_actif: boolean;
    seuil_concours: number;
    filieres_concours: string[];
    loyers: { actif: boolean; mois_exiges: number; tolerance_fcfa: number };
    categories: {
        C1: { actif: boolean };
        C2: { actif: boolean; n: number; groupe: 'filiere_niveau' | 'filiere' };
        C3: { actif: boolean; n: number };
        C4: { actif: boolean; taux: number; base: 'places_readmission_cite' | 'capacite_cite' };
        C5: { actif: boolean; prealables: boolean };
        C6: { actif: boolean };
    };
    report_places: 'C6_puis_admission' | 'C6' | 'admission' | 'vacant';
    age_max: number | null;
};

export type ReglesAdmission = {
    merite: 'moyenne_bac_desc' | 'rang_orientation_asc';
    eloignement: { tranches_km: [number, number]; hors_pays: string };
    handicap_visible: 'commission' | 'aucune';
    age_max_L1: number | null;
};

/** Jeu de critères d'un volet : le bloc `readmission` ou `admission` selon le volet. */
export type Regles = {
    /** `date_controle_prealables` : réadmission seulement (loyers et UE). */
    calendrier: { date_controle_prealables?: string | null; date_liste_provisoire: string | null; delai_completude_jours: number };
    recevabilite: { internationaux: boolean; sanctions_bloquantes: string[] };
    readmission?: ReglesReadmission;
    admission?: ReglesAdmission;
    classement: {
        priorite_boursiers: 'categorie' | 'departage' | 'aucune';
        source_boursier: 'justifie' | 'declare';
        departage: CritereDepartage[];
        k_liste_elargie: number;
    };
};

/**
 * Places d'une cité pour le volet (E0). `quotas`, `cas_sociaux` (C4, calculé) et `solde` (C2 / C6) : réadmission seulement.
 * `autre_volet` : places réservées par les critères de l'autre volet (contrôle de capacité).
 */
export type PlacesCite = {
    cite_id: string;
    cite: string;
    public: string | null;
    capacite: number;
    places: number | null;
    quotas?: Record<CodeQuota, number | null>;
    autre_volet?: number | null;
    cas_sociaux?: number | null;
    solde?: number | null;
};

export type EtatCriteres = { etat: 'a_parametrer' | 'brouillon' | 'en_vigueur'; version: number | null; brouillon: boolean };

export type CampagneCriteres = {
    id: string;
    libelle: string | null;
    annee: string;
    statut: StatutCampagne | string | null;
    type: TypeCampagne | '';
    periode_debut: string | null;
    periode_fin: string | null;
    date_echeance_demande: string | null;
    volets: Record<Volet, boolean>;
    modifiable: boolean;
};

export type DroitsCriteres = { voirRegles: boolean; configurerRegles: boolean; voirPlaces: boolean; configurerPlaces: boolean };

export type VersionCriteres = {
    version: number;
    statut: 'brouillon' | 'en_vigueur' | 'archivee';
    auteur: string | null;
    modifie_le: string | null;
    applique_par: string | null;
    applique_le: string | null;
    motif: string | null;
    regles: Regles | null;
    places: PlacesCite[] | null;
};

export type HistoriqueCriteres = Omit<VersionCriteres, 'regles' | 'places'> & { id: string };

export type FiliereOption = ComboboxOption;

/** Actions autorisées sur les critères du volet (null = non autorisée). */
export type UrlsCriteres = { submit: string | null; appliquer: string | null; abandonner: string | null };

/** Données d'une page de critères (`CriteresCampagneVue::page`) : définition, consultation et historique du volet. */
export type PageCriteres = {
    volet: Volet;
    campagne: CampagneCriteres;
    droits: DroitsCriteres;
    /** Critères en cours d'édition : brouillon, version en vigueur ou modèle CROU. */
    regles: Regles;
    places: PlacesCite[];
    source: 'brouillon' | 'en_vigueur' | 'modele';
    etat: EtatCriteres;
    enVigueur: VersionCriteres | null;
    /** Modèle CROU proposé tant qu'aucune version n'est en vigueur. */
    modele: { regles: Regles | null; places: PlacesCite[] | null } | null;
    versions: HistoriqueCriteres[];
    filieres: FiliereOption[];
    urls: UrlsCriteres;
};

export const VOLETS: Record<Volet, { titre: string; complement: string; public: string; ouverture: string }> = {
    admission: { titre: 'Critères d’admission', complement: 'd’admission', public: 'Nouveaux étudiants', ouverture: 'à l’admission' },
    readmission: { titre: 'Critères de réadmission', complement: 'de réadmission', public: 'Anciens résidents', ouverture: 'à la réadmission' },
};

export const STATUTS_VERSION: Record<VersionCriteres['statut'], { label: string; variant: 'success' | 'info' | 'neutral' }> = {
    en_vigueur: { label: 'En vigueur', variant: 'success' },
    brouillon: { label: 'Brouillon', variant: 'info' },
    archivee: { label: 'Archivée', variant: 'neutral' },
};

export const ETATS_CRITERES: Record<EtatCriteres['etat'], { label: string; variant: 'success' | 'info' | 'warning' }> = {
    a_parametrer: { label: 'À appliquer', variant: 'warning' },
    brouillon: { label: 'Brouillon', variant: 'info' },
    en_vigueur: { label: 'Appliqués', variant: 'success' },
};

export const CATEGORIES: Record<CodeCategorie, { label: string; condition: string; merite: string }> = {
    C1: {
        label: 'Écoles sur concours',
        condition: 'Filière sur concours et seuil de l’année validée atteint : réadmission d’office.',
        merite: '% de l’année validée, puis moyenne du PV',
    },
    C2: { label: 'Mérite par filière', condition: 'Hors concours, 100 % des UE validées.', merite: 'Rang du PV parmi les demandeurs du groupe' },
    C3: { label: 'Clubs et associations', condition: 'Figurer sur la liste transmise (14 disciplines).', merite: 'Rang transmis par le service' },
    C4: {
        label: 'Cas sociaux',
        condition: 'Dossier validé par la S/D des actions culturelles et sociales ; 100 % de l’année validée.',
        merite: 'Moyenne du PV',
    },
    C5: { label: 'Comité des résidents', condition: 'Membre en exercice du comité.', merite: 'Moyenne du PV' },
    C6: { label: 'Places restantes', condition: 'Tout éligible non retenu en C1 à C5.', merite: 'Moyenne du PV' },
};

export const CODES_CATEGORIES = Object.keys(CATEGORIES) as CodeCategorie[];
export const CODES_QUOTAS: CodeQuota[] = ['C1', 'C3', 'C5'];

export const DEPARTAGE: Record<CritereDepartage, { label: string; description: string }> = {
    eloignement: { label: 'Éloignement', description: 'Le candidat venant de plus loin d’abord (tranche du lieu d’obtention du bac)' },
    age_plus_jeune: { label: 'Âge', description: 'Le plus jeune d’abord (date de naissance exacte)' },
    excellence: { label: 'Excellence', description: 'Moyenne du PV décroissante, puis % d’UE validées' },
    comportement: { label: 'Comportement', description: 'Avis du gestionnaire de cité et du concierge coordonnateur' },
};

/** Critères de départage propres à chaque volet (ordre par défaut) ; le comportement ne s'applique pas à l'admission. */
export const DEPARTAGE_VOLET: Record<Volet, CritereDepartage[]> = {
    readmission: ['age_plus_jeune', 'excellence', 'comportement'],
    admission: ['eloignement', 'age_plus_jeune'],
};

export const PRIORITE_BOURSIERS: Record<Regles['classement']['priorite_boursiers'], { label: string; description: string }> = {
    categorie: { label: 'Toujours prioritaires', description: 'Le boursier passe devant le non-boursier, quel que soit le mérite (décision Q2).' },
    departage: { label: 'À mérite égal', description: 'Le boursier ne passe devant qu’en cas d’égalité de mérite.' },
    aucune: { label: 'Aucune priorité', description: 'Le statut de boursier n’intervient pas dans le classement.' },
};

export const SOURCE_BOURSIER: Record<Regles['classement']['source_boursier'], string> = {
    justifie: 'Boursier justifié (liste officielle ou pièce validée)',
    declare: 'Boursier déclaré par l’étudiant',
};

export const REPORT_PLACES: Record<ReglesReadmission['report_places'], string> = {
    C6_puis_admission: 'Sur les places restantes (C6), puis sur l’admission de la cité',
    C6: 'Sur les places restantes (C6) seulement',
    admission: 'Directement sur l’admission de la cité',
    vacant: 'Laissées vacantes',
};

export const GROUPES_C2: Record<ReglesReadmission['categories']['C2']['groupe'], string> = {
    filiere_niveau: 'Par filière et par niveau (L1, L2, L3, M1…)',
    filiere: 'Par filière, tous niveaux confondus',
};

export const BASES_C4: Record<ReglesReadmission['categories']['C4']['base'], string> = {
    places_readmission_cite: 'des places de réadmission de la cité',
    capacite_cite: 'de la capacité de la cité',
};

export const MERITE_ADMISSION: Record<ReglesAdmission['merite'], string> = {
    moyenne_bac_desc: 'Moyenne du baccalauréat (décroissante)',
    rang_orientation_asc: 'Rang d’orientation (croissant)',
};

export const HANDICAP_VISIBLE: Record<ReglesAdmission['handicap_visible'], string> = {
    commission: 'Proposé à la commission (motif obligatoire)',
    aucune: 'Pas d’exception : classement au mérite',
};

/** Tranches d'éloignement (lieu d'obtention du baccalauréat → Bondoukou), de la plus prioritaire à la moins prioritaire. */
export const tranchesEloignement = ([basse, haute]: [number, number]) => [
    { code: 'T4', libelle: `Plus de ${haute} km ou bac obtenu hors de la Côte d’Ivoire`, ordre: 'Prioritaire' },
    { code: 'T3', libelle: `De ${basse} à ${haute} km`, ordre: '2' },
    { code: 'T2', libelle: `Moins de ${basse} km, hors Bondoukou`, ordre: '3' },
    { code: 'T1', libelle: 'Bondoukou', ordre: '4' },
];

/** `aaaa-mm-jj hh:mm` → `jj/mm/aaaa à hh:mm`. */
export const formatDateHeure = (valeur: string | null) => {
    if (!valeur) return '—';
    const [date, heure] = valeur.split(' ');
    const [annee, mois, jour] = date.split('-');
    return `${jour}/${mois}/${annee}${heure ? ` à ${heure}` : ''}`;
};

/** Places C4 d'une cité selon le taux et la base des critères de réadmission (arrondi inférieur, comme le serveur). */
export const placesCasSociaux = (c4: ReglesReadmission['categories']['C4'], ligne: { capacite: number; places: number | null }) => {
    if (!c4.actif) return null;
    const base = c4.base === 'capacite_cite' ? ligne.capacite : ligne.places;
    return base === null ? null : Math.floor((base * c4.taux) / 100);
};
