import type { ComboboxOption } from "@/components/ui/combobox";
import type { StatutCampagne, TypeCampagne } from "./types";

/**
 * Critères d'une campagne, en trois jeux versionnés séparément : le paramétrage général (commun aux deux volets et
 * appliqué en premier), les critères d'admission (nouveaux étudiants) et les critères de réadmission (anciens résidents).
 * Chaque volet a deux niveaux : critères de rejet, puis critères de classement de la liste provisoire.
 * Types et libellés partagés par la liste des campagnes et la page « Critères de la campagne » du paramétrage.
 * Voir docs/notes/eligibilite-criteres-campagne.md.
 */

export type Volet = "admission" | "readmission";
/** Jeu de critères : paramétrage général ou volet. */
export type Jeu = "general" | Volet;
export type CodeCategorie = "C1" | "C2" | "C3" | "C4" | "C5" | "C6";
export type CritereDepartage =
    "age_plus_jeune" | "excellence" | "comportement" | "hors_ville_bac";

export type CreditsFiliere = {
    filiere_id: string;
    seuil: number;
    concours: boolean;
};
export type AgeNiveau = { niveau: string; age: number };

/** Paramétrage général de la campagne. */
export type ReglesGenerales = {
    credits: { seuil_defaut: number; filieres: CreditsFiliere[] };
    rang_filiere: {
        actif: boolean;
        n: number;
        groupe: "filiere_niveau" | "filiere";
    };
    clubs: { actif: boolean; n: number };
    cas_sociaux: {
        actif: boolean;
        taux: number;
        base: "places_readmission_cite" | "capacite_cite";
    };
    comite: { actif: boolean; places: number };
    age_max: AgeNiveau[];
};

export type ReglesReadmission = {
    loyers: { actif: boolean; mois_exiges: number };
    credits_actif: boolean;
    conduite: { actif: boolean; sanctions: string[] };
    age_actif: boolean;
    report_places: "C6_puis_admission" | "C6" | "admission" | "vacant";
};

export type MeriteAdmission = "moyenne_bac_desc" | "credits_desc";

export type ReglesAdmission = {
    merite: MeriteAdmission;
    seuil_actif: boolean;
    seuil: number | null;
    age_actif: boolean;
    ville_bac: string;
};

/** Jeu de critères d'un volet : le bloc `readmission` ou `admission` selon le volet. */
export type Regles = {
    calendrier: {
        date_liste_provisoire: string | null;
        delai_completude_jours: number;
    };
    recevabilite: { internationaux: boolean; sanctions_bloquantes: string[] };
    readmission?: ReglesReadmission;
    admission?: ReglesAdmission;
    classement: {
        /** Réadmission seulement. */
        /** `categorie` : boursiers prioritaires ; `aucune` : pas de priorité. */
        priorite_boursiers?: "categorie" | "aucune";
        departage: CritereDepartage[];
        k_liste_elargie: number;
    };
};

/**
 * Places d'une cité pour le volet (E0). `cas_sociaux` (C4, calculé selon le paramétrage général) : réadmission seulement.
 * `autre_volet` : places réservées par les critères de l'autre volet (contrôle de capacité).
 */
export type PlacesCite = {
    cite_id: string;
    cite: string;
    public: string | null;
    capacite: number;
    places: number | null;
    autre_volet?: number | null;
    cas_sociaux?: number | null;
};

export type EtatCriteres = {
    etat: "a_parametrer" | "brouillon" | "en_vigueur";
    version: number | null;
    brouillon: boolean;
};

export type CampagneCriteres = {
    id: string;
    libelle: string | null;
    annee: string;
    statut: StatutCampagne | string | null;
    type: TypeCampagne | "";
    periode_debut: string | null;
    periode_fin: string | null;
    date_echeance_demande: string | null;
    volets: Record<Volet, boolean>;
    modifiable: boolean;
    /** « en cours » ou « terminée » quand les critères sont figés (campagne active ou terminée). */
    verrou: string | null;
};

export type DroitsCriteres = {
    voirRegles: boolean;
    configurerRegles: boolean;
    voirPlaces: boolean;
    configurerPlaces: boolean;
};

export type VersionCriteres<R = Regles> = {
    version: number;
    statut: "brouillon" | "en_vigueur" | "archivee";
    auteur: string | null;
    modifie_le: string | null;
    applique_par: string | null;
    applique_le: string | null;
    motif: string | null;
    regles: R | null;
    places: PlacesCite[] | null;
};

export type HistoriqueCriteres = Omit<VersionCriteres, "regles" | "places"> & {
    id: string;
};

export type FiliereOption = ComboboxOption;
export type NiveauEtudeOption = ComboboxOption;

/** Paramétrage général appliqué par un volet (version en vigueur, sinon brouillon ou modèle) et catégories qui en découlent. */
export type GeneralApplique = {
    etat: EtatCriteres["etat"];
    regles: ReglesGenerales;
    categories: Record<CodeCategorie, { actif: boolean; merite: string[] }>;
};

/** Actions autorisées sur les critères du jeu (null = non autorisée). */
export type UrlsCriteres = {
    submit: string | null;
    appliquer: string | null;
    abandonner: string | null;
};

/** Données communes d'un niveau de la page (`CriteresCampagneVue::page`). */
type PageBase<J extends Jeu, R> = {
    volet: J;
    campagne: CampagneCriteres;
    droits: DroitsCriteres;
    /** Critères en cours d'édition : brouillon, version en vigueur ou modèle CROU. */
    regles: R;
    places: PlacesCite[];
    source: "brouillon" | "en_vigueur" | "modele";
    etat: EtatCriteres;
    enVigueur: VersionCriteres<R> | null;
    /** Modèle CROU proposé tant qu'aucune version n'est en vigueur. */
    modele: { regles: R | null; places: PlacesCite[] | null } | null;
    versions: HistoriqueCriteres[];
    filieres: FiliereOption[];
    niveauxEtude: NiveauEtudeOption[];
    urls: UrlsCriteres;
};

/** Niveau d'un volet (admission ou réadmission). */
export type PageCriteres = PageBase<Volet, Regles> & {
    general: GeneralApplique | null;
};

/** Niveau du paramétrage général. */
export type PageGeneral = PageBase<"general", ReglesGenerales> & {
    general: null;
};

export type PageNiveau = PageCriteres | PageGeneral;

export const JEUX: Record<
    Jeu,
    { titre: string; complement: string; public: string; ouverture: string }
> = {
    general: {
        titre: "Paramétrage général",
        complement: "généraux",
        public: "Toute la campagne",
        ouverture: "",
    },
    admission: {
        titre: "Critères d’admission",
        complement: "d’admission",
        public: "Nouveaux étudiants",
        ouverture: "à l’admission",
    },
    readmission: {
        titre: "Critères de réadmission",
        complement: "de réadmission",
        public: "Anciens résidents",
        ouverture: "à la réadmission",
    },
};

/** Volets seuls (liste des campagnes). */
export const VOLETS: Record<Volet, (typeof JEUX)[Volet]> = {
    admission: JEUX.admission,
    readmission: JEUX.readmission,
};

export const STATUTS_VERSION: Record<
    VersionCriteres["statut"],
    { label: string; variant: "success" | "info" | "neutral" }
> = {
    en_vigueur: { label: "En vigueur", variant: "success" },
    brouillon: { label: "Brouillon", variant: "info" },
    archivee: { label: "Archivée", variant: "neutral" },
};

export const ETATS_CRITERES: Record<
    EtatCriteres["etat"],
    { label: string; variant: "success" | "info" | "warning" }
> = {
    a_parametrer: { label: "À appliquer", variant: "warning" },
    brouillon: { label: "Brouillon", variant: "info" },
    en_vigueur: { label: "Appliqués", variant: "success" },
};

export const CATEGORIES: Record<
    CodeCategorie,
    { label: string; condition: string; merite: string }
> = {
    C1: {
        label: "Écoles sur concours",
        condition:
            "Filière marquée « école sur concours » et pourcentage de crédits de la filière atteint : réadmission d’office.",
        merite: "% de l’année validée, puis moyenne du PV",
    },
    C2: {
        label: "Rang par filière",
        condition: "Les N premiers de chaque filière, crédits exigés validés.",
        merite: "Rang du PV parmi les demandeurs du groupe",
    },
    C3: {
        label: "Clubs et associations",
        condition:
            "Les N premiers de chaque discipline, sur la liste transmise.",
        merite: "Rang transmis par le service",
    },
    C4: {
        label: "Cas sociaux",
        condition:
            "Dossier validé par la S/D des actions culturelles et sociales.",
        merite: "Moyenne du PV",
    },
    C5: {
        label: "Comité des résidents",
        condition: "Membre en exercice du comité.",
        merite: "Moyenne du PV",
    },
    C6: {
        label: "Places restantes",
        condition:
            "Tout candidat recevable non retenu dans les catégories précédentes.",
        merite: "Moyenne du PV",
    },
};

export const CODES_CATEGORIES = Object.keys(CATEGORIES) as CodeCategorie[];

export const DEPARTAGE: Record<
    CritereDepartage,
    { label: string; description: string }
> = {
    hors_ville_bac: {
        label: "Ville du bac",
        description:
            "Le baccalauréat obtenu hors de la ville de référence d’abord",
    },
    age_plus_jeune: {
        label: "Âge",
        description: "Le plus jeune d’abord (date de naissance exacte)",
    },
    excellence: {
        label: "Excellence",
        description: "% de crédits validés, puis moyenne du PV décroissante",
    },
    comportement: {
        label: "Comportement",
        description:
            "Avis du gestionnaire de cité et du concierge coordonnateur",
    },
};

/** Critères de départage propres à chaque volet (ordre proposé). */
export const DEPARTAGE_VOLET: Record<Volet, CritereDepartage[]> = {
    readmission: ["age_plus_jeune", "excellence", "comportement"],
    admission: ["hors_ville_bac", "age_plus_jeune"],
};

export const REPORT_PLACES: Record<ReglesReadmission["report_places"], string> =
    {
        C6_puis_admission:
            "Sur les places restantes, puis sur l’admission de la cité",
        C6: "Sur les places restantes seulement",
        admission: "Directement sur l’admission de la cité",
        vacant: "Laissées vacantes",
    };

export const GROUPES_C2: Record<
    ReglesGenerales["rang_filiere"]["groupe"],
    string
> = {
    filiere_niveau: "Par filière et par niveau",
    filiere: "Par filière, tous niveaux confondus",
};

export const BASES_C4: Record<ReglesGenerales["cas_sociaux"]["base"], string> =
    {
        places_readmission_cite: "des places de réadmission de la cité",
        capacite_cite: "de la capacité de la cité",
    };

/** Mérite des nouveaux étudiants : sert au seuil de rejet et au classement. */
export const MERITE_ADMISSION: Record<
    MeriteAdmission,
    { label: string; seuil: string; unite: string; max: number }
> = {
    moyenne_bac_desc: {
        label: "Note du baccalauréat",
        seuil: "Note minimale au baccalauréat",
        unite: "/ 20",
        max: 20,
    },
    credits_desc: {
        label: "Nombre de crédits",
        seuil: "Crédits minimum",
        unite: "crédits",
        max: 360,
    },
};

/** `aaaa-mm-jj hh:mm` → `jj/mm/aaaa à hh:mm`. */
export const formatDateHeure = (valeur: string | null) => {
    if (!valeur) return "—";
    const [date, heure] = valeur.split(" ");
    const [annee, mois, jour] = date.split("-");
    return `${jour}/${mois}/${annee}${heure ? ` à ${heure}` : ""}`;
};

/** Places C4 d'une cité selon le taux et la base du paramétrage général (arrondi inférieur, comme le serveur). */
export const placesCasSociaux = (
    c4: ReglesGenerales["cas_sociaux"],
    ligne: { capacite: number; places: number | null },
) => {
    if (!c4.actif) return null;
    const base = c4.base === "capacite_cite" ? ligne.capacite : ligne.places;
    return base === null ? null : Math.floor((base * c4.taux) / 100);
};

/** Crédits exigés, une ligne par filière : [« ENSEAU », « 80 % »], puis [« Autres filières », « 100 % »]. */
export const lignesCredits = (
    credits: {
        seuil_defaut: number | string;
        filieres: { filiere_id: string; seuil: number | string }[];
    },
    filieres: FiliereOption[],
): [string, string][] => [
    ...credits.filieres.map((ligne): [string, string] => [
        filieres.find((f) => f.value === ligne.filiere_id)?.label ??
            (ligne.filiere_id || "—"),
        `${ligne.seuil} %`,
    ]),
    [
        credits.filieres.length ? "Autres filières" : "Toutes les filières",
        `${credits.seuil_defaut} %`,
    ],
];
