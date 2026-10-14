import type { Paginated } from '@/types/global';

export type Niveau = 'cite' | 'batiment' | 'chambre';

export type EtatChambre = 'disponible' | 'occupee' | 'reservee' | 'maintenance' | 'hors_service';

/** Compteurs calculés côté serveur (`ParcImmobilierController::agregerChambres`). */
export type Resume = {
    chambres: number;
    disponibles: number;
    occupees: number;
    reservees: number;
    maintenance: number;
    places: number;
    occupants: number;
    categories: Record<string, number>;
};

export type ElementUrls = {
    edit: string | null;
    status: string | null;
    destroy: string | null;
};

type Base = {
    id: string;
    name: string;
    statut: string;
    occupation: string | null;
    urls: ElementUrls;
};

export type CiteItem = Base & {
    type: 'cite';
    code: string | null;
    responsable: string | null;
    /** Nom seul du responsable (carte), sans le téléphone. */
    responsable_nom: string | null;
    localisation: string | null;
    batiments: number;
    batiments_desactives: number;
    resume: Resume;
};

export type BatimentItem = Base & {
    type: 'batiment';
    cite: string;
    responsable: string | null;
    responsable_nom: string | null;
    localisation: string | null;
    niveaux: number;
    resume: Resume;
};

export type ChambreItem = Base & {
    type: 'chambre';
    etat: EtatChambre | null;
    type_chambre: string | null;
    type_chambre_id: string;
    niveau: number | null;
    capacite: number;
    nb_lits: number;
    superficie: number | null;
    prix: number;
    niveau_confort: string | null;
    pmr: boolean;
    etudiants: { nom: string; matricule: string | null }[];
};

export type ParcItem = CiteItem | BatimentItem | ChambreItem;

export type ContexteElement = { id: string; name: string; statut: string; resume: Resume | null };

export type ParcProps = {
    niveau: Niveau;
    items: Paginated<ParcItem>;
    plan: ChambreItem[];
    filters: { q: string; etat: EtatChambre | ''; statut: string };
    statutsCite: string[];
    statutsBatiment: string[];
    contexte: { cite: ContexteElement | null; batiment: ContexteElement | null };
    stats: {
        cites: number;
        batiments: number;
        chambres: number;
        disponibles: number;
        occupees: number;
        reservees: number;
        maintenance: number;
        places: number;
        occupants: number;
    };
    typesChambre: { id: string; name: string }[];
    urls: {
        hebergement: string;
        updateChambre: string | null;
        map: string | null;
        equipements: string | null;
        create: Partial<Record<Niveau, string>>;
    };
};
