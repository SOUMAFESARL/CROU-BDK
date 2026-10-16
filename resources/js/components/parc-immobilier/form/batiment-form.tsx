import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { isAxiosError } from 'axios';
import {
    Accessibility,
    ArrowUpDown,
    BedDouble,
    Building,
    Building2,
    ClipboardCheck,
    Layers,
    Loader2,
    LocateFixed,
    Map as MapIcon,
    MapPin,
    Network,
    Plus,
    RotateCcw,
    ShieldCheck,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { Controller, useForm, type FieldPath } from 'react-hook-form';
import { toast } from 'sonner';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { messageErreur, parcHttp } from '../http';
import { nombre } from '../labels';
import { ChoiceCard, Counter, erreurPlan, PlanUpload, RecapSection, ToggleCard } from './inputs';
import { geocoder, lireCoordonnee, PositionPicker } from './position-picker';
import {
    coordonnee,
    entierObligatoire,
    estMixte,
    normaliser,
    texte,
    type AileExistante,
    type BatimentItem,
    type CiteOption,
    type Option,
    type UserOption,
} from './types';
import { FormAlert, SectionTitle, useWizard, Wizard, type WizardStep } from './wizard';

const PLAN_MAX_MO = 2;
const MAX_NIVEAUX = 30;
const MAX_AILES = 12;

const ETATS = [
    { value: 'operationnel', label: 'Opérationnel' },
    { value: 'renovation', label: 'En rénovation' },
    { value: 'en_construction', label: 'En construction' },
    { value: 'ferme', label: 'Hors service' },
] as const;

const niveauSchema = z.object({
    chambres: entierObligatoire('Le nombre de chambres', 0, 500),
    type: z.string().min(1, "Choisissez le type d'occupation."),
});

/** Niveau en modification : on ne descend jamais sous le nombre de chambres déjà créées. */
const niveauExistantSchema = z
    .object({
        code: z.string().nullable(),
        numero: z.number(),
        existantes: z.number(),
        occupees: z.number(),
        chambres: entierObligatoire('Le nombre de chambres', 0, 500),
        type: z.string().min(1, "Choisissez le type d'occupation."),
    })
    .refine((niveau) => Number(niveau.chambres) >= niveau.existantes, {
        path: ['chambres'],
        message: 'Impossible de descendre sous le nombre de chambres déjà créées.',
    });

const schema = z.object({
    code_cite: z.string().min(1, 'Choisissez la cité de rattachement.'),
    name: z.string().trim().min(1, 'Le nom du bâtiment est obligatoire.').max(100, '100 caractères au maximum.'),
    type_occupation: z.string().min(1, "Choisissez le type d'occupation."),
    responsable_id: z.string(),
    etat_initial: z.enum(['operationnel', 'renovation', 'en_construction', 'ferme']),
    adresse: z.string().max(500),
    latitude: coordonnee('La latitude', 90, false),
    longitude: coordonnee('La longitude', 180, false),
    type_architecture: z.enum(['simple', 'complexe']),
    nombre_etages: entierObligatoire("Le nombre d'étages", 0, MAX_NIVEAUX),
    etages: z.array(niveauSchema),
    nombre_ailes: entierObligatoire("Le nombre d'ailes", 1, MAX_AILES),
    config_niveaux: z.enum(['meme-hauteur', 'hauteur-variable']),
    nombre_niveaux_global: entierObligatoire("Le nombre d'étages", 0, MAX_NIVEAUX),
    ailes: z.array(
        z.object({
            nom: z.string().trim().min(1, "Le nom de l'aile est obligatoire.").max(100, '100 caractères au maximum.'),
            niveaux: entierObligatoire("Le nombre d'étages", 0, MAX_NIVEAUX),
            etages: z.array(niveauSchema),
        }),
    ),
    /** Structure existante (modification uniquement). */
    existant: z.array(
        z.object({
            code: z.string().nullable(),
            nom: z.string().trim().min(1, "Le nom de l'aile est obligatoire.").max(100, '100 caractères au maximum.'),
            niveaux: z.array(niveauExistantSchema),
            /** Niveaux existants retirés (du plus haut au plus bas), supprimés à l'enregistrement ; gardés pour pouvoir les rétablir. */
            retires: z.array(niveauExistantSchema),
        }),
    ),
    acces_handicape: z.boolean(),
    ascenseur: z.boolean(),
    gardiennage: z.boolean(),
    description: z.string().max(2000, '2000 caractères au maximum.'),
});

type FormInput = z.input<typeof schema>;
type FormOutput = z.output<typeof schema>;
type Champ = FieldPath<FormInput>;
type Niveau = FormInput['etages'][number];
type AileEdition = FormInput['existant'][number];
type NiveauEdition = AileEdition['niveaux'][number];
type EtapeId = 'identite' | 'localisation' | 'structure' | 'services' | 'verification';

const ETAPES: Record<EtapeId, WizardStep> = {
    identite: { label: 'Identification', description: 'Cité, nom, occupation, état', icon: Building },
    localisation: { label: 'Localisation', description: 'Position GPS (facultative)', icon: MapPin },
    structure: { label: 'Structure', description: 'Étages, ailes et chambres', icon: Layers },
    services: { label: 'Services et plan', description: 'Équipements, plan, notes', icon: ShieldCheck },
    verification: { label: 'Vérification', description: 'Relire avant d’enregistrer', icon: ClipboardCheck },
};

const CHAMPS_FIXES: Record<EtapeId, Champ[]> = {
    identite: ['code_cite', 'name', 'type_occupation', 'responsable_id', 'etat_initial'],
    localisation: ['latitude', 'longitude'],
    structure: ['type_architecture'],
    services: ['acces_handicape', 'ascenseur', 'gardiennage', 'description'],
    verification: [],
};

/** Champs renvoyés par `storeBatiment` / `updateBatiment` → étape où les corriger. */
const ETAPE_ERREUR_SERVEUR: Record<string, EtapeId> = {
    code_cite: 'identite',
    name: 'identite',
    type_occupation: 'identite',
    responsable_id: 'identite',
    etat_initial: 'identite',
    latitude: 'localisation',
    longitude: 'localisation',
    type_architecture: 'structure',
    structure_data: 'structure',
    plan_file: 'services',
};

const libelleNiveau = (index: number) => (index === 0 ? 'Rez-de-chaussée' : `Étage ${index}`);

/** Redimensionne une liste en conservant les éléments existants. */
function redimensionner<T>(liste: T[], taille: number, creer: (index: number) => T): T[] {
    return Array.from({ length: taille }, (_, index) => liste[index] ?? creer(index));
}

const totalNiveaux = (niveaux: Niveau[]) => niveaux.reduce((somme, niveau) => somme + (Number.parseInt(niveau.chambres, 10) || 0), 0);

/** Chambres à ajouter à la structure existante (différence avec les chambres déjà créées). */
const chambresAjoutees = (ailes: AileEdition[]) =>
    ailes.reduce((s, aile) => s + aile.niveaux.reduce((t, n) => t + Math.max(0, (Number.parseInt(n.chambres, 10) || 0) - n.existantes), 0), 0);

const chambresExistantes = (ailes: AileEdition[]) => ailes.reduce((s, aile) => s + aile.niveaux.reduce((t, n) => t + n.existantes, 0), 0);

/** Valeur stockée d'un niveau ('hommes', 'femmes', 'mixte') → valeur du formulaire (nom normalisé du type de cité). */
function typeDepuisStockage(valeur: string | null, types: Option[], defaut: string): string {
    const v = normaliser(valeur);
    const correspond = (nom: string) => {
        const n = normaliser(nom);
        if (v.includes('mixte')) return n.includes('mixte');
        if (v.includes('femme') || v.includes('fille')) return n.includes('femme') || n.includes('fille');
        if (v.includes('homme') || v.includes('garcon')) return n.includes('homme') || n.includes('garcon');
        return false;
    };
    const type = types.find((t) => correspond(t.name));
    return type ? normaliser(type.name) : defaut;
}

/** Structure existante → valeurs du formulaire ; un bâtiment sans aile reçoit une aile vide (créée seulement si des niveaux y sont ajoutés). */
function structureEdition(structure: AileExistante[], types: Option[], defaut: string): AileEdition[] {
    if (!structure.length) return [{ code: null, nom: 'Aile Principale', niveaux: [], retires: [] }];
    return structure.map((aile) => ({
        code: aile.code,
        nom: aile.nom || 'Aile Principale',
        niveaux: aile.niveaux.map((niveau) => ({
            code: niveau.code,
            numero: niveau.numero,
            existantes: niveau.chambres,
            occupees: niveau.occupees,
            chambres: String(niveau.chambres),
            type: typeDepuisStockage(niveau.type_occupation, types, defaut),
        })),
        retires: [],
    }));
}

type BatimentFormProps = {
    item: BatimentItem | null;
    campusId: string;
    cites: CiteOption[];
    typesCite: Option[];
    users: UserOption[];
    preselectionCite: string | null;
    submitUrl: string | null;
    cancelUrl: string;
};

export function BatimentForm({ item, campusId, cites, typesCite, users, preselectionCite, submitUrl, cancelUrl }: BatimentFormProps) {
    const isEdit = item !== null;
    // En modification, l'étape Structure permet d'ajouter niveaux et chambres (jamais d'en retirer) et toutes les étapes sont accessibles.
    const etapes: EtapeId[] = ['identite', 'localisation', 'structure', 'services', 'verification'];
    const steps = etapes.map((id) => ETAPES[id]);
    const wizard = useWizard(steps.length, { toutesAccessibles: isEdit });
    const etapeCourante = etapes[wizard.current];
    const [submitting, setSubmitting] = useState(false);
    const [erreurGenerale, setErreurGenerale] = useState<string | null>(null);
    const [erreurStructure, setErreurStructure] = useState<string | null>(null);
    const [plan, setPlan] = useState<File | null>(null);
    const [planError, setPlanError] = useState<string | null>(null);
    const [removePlan, setRemovePlan] = useState(false);
    const [carteOuverte, setCarteOuverte] = useState(false);
    const [geocodage, setGeocodage] = useState(false);

    const typeNom = (id: string | null | undefined) => typesCite.find((t) => t.id === id)?.name ?? '';
    const citeInitiale = item?.code_cite ?? (cites.some((c) => c.id === preselectionCite) ? preselectionCite! : '');

    const defaultValues = useMemo<FormInput>(() => {
        const type = item?.type_occupation ?? '';
        const typeNiveau = normaliser(typeNom(type)) || 'mixte';
        return {
            code_cite: citeInitiale,
            name: texte(item?.name),
            type_occupation: texte(type),
            responsable_id: texte(item?.responsable_id),
            etat_initial: (ETATS.some((e) => e.value === item?.etat_initial) ? item?.etat_initial : 'operationnel') as FormInput['etat_initial'],
            adresse: '',
            latitude: texte(item?.latitude),
            longitude: texte(item?.longitude),
            type_architecture: item?.type_architecture ?? 'simple',
            nombre_etages: '1',
            etages: redimensionner([], 2, () => ({ chambres: '0', type: typeNiveau })),
            nombre_ailes: '2',
            config_niveaux: 'meme-hauteur',
            nombre_niveaux_global: '1',
            ailes: redimensionner([], 2, (index) => ({ nom: `Aile ${index + 1}`, niveaux: '1', etages: redimensionner([], 2, () => ({ chambres: '0', type: typeNiveau })) })),
            existant: item ? structureEdition(item.structure ?? [], typesCite, typeNiveau) : [],
            acces_handicape: item?.acces_handicape ?? false,
            ascenseur: item?.ascenseur ?? false,
            gardiennage: item?.gardiennage ?? false,
            description: texte(item?.description),
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [item]);

    const {
        register,
        control,
        trigger,
        watch,
        setValue,
        getValues,
        setError,
        handleSubmit,
        formState: { errors },
    } = useForm<FormInput, unknown, FormOutput>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues });

    const values = watch();
    const cite = cites.find((c) => c.id === values.code_cite) ?? null;

    /** Types d'occupation permis par la cité : tous pour une cité mixte, sinon uniquement celui de la cité. */
    const typesPermis = useMemo(() => {
        if (!cite?.type_cite_id || estMixte(typeNom(cite.type_cite_id))) return typesCite;
        return typesCite.filter((t) => t.id === cite.type_cite_id);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [cite, typesCite]);

    /** Types possibles pour un niveau : ceux du bâtiment (tous s'il est mixte). Valeurs en minuscules, comme avant. */
    const typesNiveau = useMemo(() => {
        const nom = typeNom(values.type_occupation);
        const liste = !nom || estMixte(nom) ? typesCite : typesCite.filter((t) => t.id === values.type_occupation);
        return liste.map((t) => ({ value: normaliser(t.name), label: t.name }));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [values.type_occupation, typesCite]);

    /** Remet les niveaux sur un type permis quand le type du bâtiment change. */
    const alignerNiveaux = (typeId: string) => {
        const nom = typeNom(typeId);
        const permis = !nom || estMixte(nom) ? typesCite.map((t) => normaliser(t.name)) : [normaliser(nom)];
        const defaut = normaliser(nom) || permis[0] || 'mixte';
        const corriger = (niveaux: Niveau[]) => niveaux.map((n) => (permis.includes(n.type) ? n : { ...n, type: defaut }));
        setValue('etages', corriger(getValues('etages')));
        setValue(
            'ailes',
            getValues('ailes').map((aile) => ({ ...aile, etages: corriger(aile.etages) })),
        );
        // Un niveau occupé garde son occupation (le serveur refuse de la changer).
        setValue(
            'existant',
            getValues('existant').map((aile) => ({
                ...aile,
                niveaux: aile.niveaux.map((n) => (n.occupees > 0 || permis.includes(n.type) ? n : { ...n, type: defaut })),
            })),
        );
    };

    const choisirCite = (id: string) => {
        setValue('code_cite', id, { shouldValidate: true, shouldDirty: true });
        const nouvelle = cites.find((c) => c.id === id);
        const permis = !nouvelle?.type_cite_id || estMixte(typeNom(nouvelle.type_cite_id)) ? typesCite : typesCite.filter((t) => t.id === nouvelle.type_cite_id);
        const actuel = getValues('type_occupation');
        if (!permis.some((t) => t.id === actuel)) {
            const nouveau = permis.length === 1 ? permis[0].id : '';
            setValue('type_occupation', nouveau, { shouldDirty: true });
            alignerNiveaux(nouveau);
        }
    };

    const defautNiveau = (): Niveau => ({ chambres: '0', type: normaliser(typeNom(getValues('type_occupation'))) || typesNiveau[0]?.value || 'mixte' });

    const changerNombreEtages = (valeur: string) => {
        setValue('nombre_etages', valeur, { shouldValidate: true });
        const n = Number.parseInt(valeur, 10);
        if (Number.isFinite(n) && n >= 0 && n <= MAX_NIVEAUX) setValue('etages', redimensionner(getValues('etages'), n + 1, defautNiveau));
        setErreurStructure(null);
    };

    const changerNombreAiles = (valeur: string) => {
        setValue('nombre_ailes', valeur, { shouldValidate: true });
        const n = Number.parseInt(valeur, 10);
        if (!Number.isFinite(n) || n < 1 || n > MAX_AILES) return;
        const niveauxGlobal = getValues('nombre_niveaux_global');
        setValue(
            'ailes',
            redimensionner(getValues('ailes'), n, (index) => {
                const niveaux = getValues('config_niveaux') === 'meme-hauteur' ? niveauxGlobal : '1';
                return { nom: `Aile ${index + 1}`, niveaux, etages: redimensionner([], (Number.parseInt(niveaux, 10) || 0) + 1, defautNiveau) };
            }),
        );
        setErreurStructure(null);
    };

    const changerNiveauxAile = (index: number | 'tous', valeur: string) => {
        const n = Number.parseInt(valeur, 10);
        const valide = Number.isFinite(n) && n >= 0 && n <= MAX_NIVEAUX;
        if (index === 'tous') {
            setValue('nombre_niveaux_global', valeur, { shouldValidate: true });
        }
        setValue(
            'ailes',
            getValues('ailes').map((aile, i) =>
                index === 'tous' || i === index
                    ? { ...aile, niveaux: valeur, etages: valide ? redimensionner(aile.etages, n + 1, defautNiveau) : aile.etages }
                    : aile,
            ),
            { shouldValidate: index !== 'tous' },
        );
        setErreurStructure(null);
    };

    const changerConfig = (config: FormInput['config_niveaux']) => {
        setValue('config_niveaux', config);
        if (config === 'meme-hauteur') changerNiveauxAile('tous', getValues('nombre_niveaux_global'));
    };

    /* Structure en modification : ajout d'étages / d'ailes et de chambres. */
    const nouveauNiveau = (numero: number): NiveauEdition => ({ code: null, numero, existantes: 0, occupees: 0, chambres: '0', type: defautNiveau().type });

    const modifierExistant = (ailes: AileEdition[]) => {
        setValue('existant', ailes, { shouldValidate: true });
        setErreurStructure(null);
    };

    const ajouterEtage = (indexAile: number) => {
        const ailes = getValues('existant');
        const niveaux = ailes[indexAile].niveaux;
        const numero = niveaux.length ? Math.max(...niveaux.map((n) => n.numero)) + 1 : 0;
        if (numero > MAX_NIVEAUX) return;
        modifierExistant(ailes.map((a, i) => (i === indexAile ? { ...a, niveaux: [...a.niveaux, nouveauNiveau(numero)] } : a)));
    };

    /** Retire le niveau le plus haut de l'aile : un niveau nouveau disparaît, un niveau existant (libre) passe dans « retirés ». */
    const retirerNiveauSuperieur = (indexAile: number) =>
        modifierExistant(
            getValues('existant').map((a, i) => {
                const dernier = a.niveaux[a.niveaux.length - 1];
                if (i !== indexAile || !dernier || dernier.occupees > 0) return a;
                return {
                    ...a,
                    niveaux: a.niveaux.slice(0, -1),
                    retires: dernier.code ? [...a.retires, { ...dernier, chambres: String(dernier.existantes) }] : a.retires,
                };
            }),
        );

    /** Rétablit le dernier niveau retiré, s'il redevient le niveau le plus haut de l'aile. */
    const retablirNiveau = (indexAile: number) =>
        modifierExistant(
            getValues('existant').map((a, i) => {
                const niveau = a.retires[a.retires.length - 1];
                if (i !== indexAile || !niveau) return a;
                return { ...a, niveaux: [...a.niveaux, niveau], retires: a.retires.slice(0, -1) };
            }),
        );

    const ajouterAile = () => {
        const ailes = getValues('existant');
        if (ailes.length < MAX_AILES) modifierExistant([...ailes, { code: null, nom: `Aile ${ailes.length + 1}`, niveaux: [nouveauNiveau(0)], retires: [] }]);
    };

    const retirerAile = (indexAile: number) => modifierExistant(getValues('existant').filter((_, i) => i !== indexAile));

    const total = isEdit
        ? chambresExistantes(values.existant) + chambresAjoutees(values.existant)
        : values.type_architecture === 'simple'
          ? totalNiveaux(values.etages)
          : values.ailes.reduce((s, a) => s + totalNiveaux(a.etages), 0);
    const ajoutees = chambresAjoutees(values.existant);
    const nouveauxNiveaux = values.existant.reduce((s, aile) => s + aile.niveaux.filter((n) => !n.code).length, 0);
    const nombreNiveaux = values.existant.reduce((s, aile) => s + aile.niveaux.length, 0);
    const niveauxRetires = values.existant.reduce((s, aile) => s + aile.retires.length, 0);
    const chambresSupprimees = values.existant.reduce((s, aile) => s + aile.retires.reduce((t, n) => t + n.existantes, 0), 0);

    const champsEtape = (id: EtapeId): Champ[] => {
        if (id !== 'structure') return CHAMPS_FIXES[id];
        if (isEdit) return ['existant'];
        return values.type_architecture === 'simple'
            ? ['type_architecture', 'nombre_etages', 'etages']
            : ['type_architecture', 'nombre_ailes', 'config_niveaux', 'nombre_niveaux_global', 'ailes'];
    };

    const validerEtape = async (index: number) => {
        const id = etapes[index];
        let ok = await trigger(champsEtape(id), { shouldFocus: true });
        if (id === 'structure' && !isEdit) {
            const erreur = total < 1 ? 'Indiquez au moins une chambre dans la structure du bâtiment.' : null;
            setErreurStructure(erreur);
            ok = ok && !erreur;
        }
        if (id === 'services') {
            const erreur = erreurPlan(plan, PLAN_MAX_MO);
            setPlanError(erreur);
            ok = ok && !erreur;
        }
        return ok;
    };

    const allerA = async (cible: number) => {
        for (let index = wizard.current; index < cible; index++) {
            if (!(await validerEtape(index))) {
                wizard.goTo(index);
                return;
            }
        }
        wizard.goTo(cible);
    };

    const envoyer = async (v: FormOutput) => {
        if (!submitUrl) return;
        const donnees: Record<string, unknown> = {
            code_campus: campusId,
            code_cite: v.code_cite,
            name: v.name,
            type_occupation: v.type_occupation,
            responsable_id: v.responsable_id || null,
            etat_initial: v.etat_initial,
            latitude: v.latitude ? v.latitude.replace(',', '.') : null,
            longitude: v.longitude ? v.longitude.replace(',', '.') : null,
            adresse: v.adresse,
            services: { acces_handicape: v.acces_handicape ? 1 : 0, ascenseur: v.ascenseur ? 1 : 0, gardiennage: v.gardiennage ? 1 : 0 },
            description: v.description,
        };
        if (isEdit) {
            // Seules les ailes existantes ou dotées de niveaux sont envoyées (une aile vide ne crée rien).
            donnees.structure_data = {
                ailes: v.existant
                    .filter((aile) => aile.code || aile.niveaux.length)
                    .map((aile) => ({
                        code: aile.code,
                        nom: aile.nom,
                        niveaux_retires: aile.retires.map((n) => n.code),
                        niveaux: aile.niveaux.map((n) => ({ code: n.code, numero: n.numero, chambres: Number(n.chambres), type_occupation: n.type })),
                    })),
            };
        } else {
            donnees.type_architecture = v.type_architecture;
            if (v.type_architecture === 'simple') {
                const etages = v.etages.map((niveau, index) => ({
                    etage_numero: index,
                    chambres: Number(niveau.chambres),
                    total_chambres: Number(niveau.chambres),
                    type_occupation: niveau.type,
                }));
                donnees.structure_data = { nombre_etages: Number(v.nombre_etages), type: 'simple', etages };
                donnees.total_chambres = totalNiveaux(v.etages);
                donnees.total_etages = etages.length;
                donnees.total_ailes = 0;
            } else {
                const ailes = v.ailes.map((aile, index) => ({
                    aile_numero: index + 1,
                    aile_nom: aile.nom,
                    nombre_niveaux: aile.etages.length,
                    total_chambres: totalNiveaux(aile.etages),
                    etages: aile.etages.map((niveau, numero) => ({ niveau_numero: numero, total_chambres: Number(niveau.chambres), type_occupation: niveau.type })),
                }));
                donnees.structure_data = { nombre_ailes: ailes.length, config_niveaux: v.config_niveaux, type: 'complexe', ailes };
                donnees.total_chambres = ailes.reduce((s, a) => s + a.total_chambres, 0);
                donnees.total_etages = 0;
                donnees.total_ailes = ailes.length;
            }
        }

        const formData = new FormData();
        formData.append('batiment_data', JSON.stringify(donnees));
        if (plan) formData.append('plan_file', plan);
        if (isEdit) {
            formData.append('_method', 'PUT');
            formData.append('code', item.id);
            if (removePlan) formData.append('remove_plan', '1');
        }

        setSubmitting(true);
        setErreurGenerale(null);
        try {
            const { data } = await parcHttp.post(submitUrl, formData);
            // La redirection affiche le message flash de succès.
            router.visit(data.redirect ?? cancelUrl);
        } catch (error) {
            setSubmitting(false);
            const erreurs = isAxiosError(error) ? (error.response?.data as { errors?: Record<string, string[]> } | undefined)?.errors : undefined;
            if (erreurs && Object.keys(erreurs).length) {
                let premiere = etapes.length - 1;
                Object.entries(erreurs).forEach(([champ, messages]) => {
                    const etape = ETAPE_ERREUR_SERVEUR[champ];
                    const message = messages[0];
                    if (champ === 'structure_data') setErreurStructure(message);
                    else if (champ === 'plan_file') setPlanError(message);
                    else if (etape) setError(champ as Champ, { message });
                    if (etape && etapes.includes(etape)) premiere = Math.min(premiere, etapes.indexOf(etape));
                    else setErreurGenerale(message);
                });
                wizard.goTo(premiere);
            } else {
                setErreurGenerale(messageErreur(error, 'L’enregistrement du bâtiment a échoué.'));
            }
        }
    };

    const suivant = async () => {
        if (wizard.isLast) {
            for (let index = 0; index < etapes.length - 1; index++) {
                if (!(await validerEtape(index))) {
                    wizard.goTo(index);
                    return;
                }
            }
            void handleSubmit(envoyer)();
            return;
        }
        if (await validerEtape(wizard.current)) wizard.next();
    };

    const chercherAdresse = async () => {
        const adresse = getValues('adresse').trim();
        if (!adresse) {
            toast.error('Saisissez une adresse à rechercher.');
            return;
        }
        setGeocodage(true);
        try {
            const resultat = await geocoder(adresse);
            if (resultat) {
                setValue('latitude', resultat.lat.toFixed(6), { shouldValidate: true });
                setValue('longitude', resultat.lng.toFixed(6), { shouldValidate: true });
                toast.success('Coordonnées trouvées.');
            } else {
                toast.error('Adresse introuvable : placez le point sur la carte.');
            }
        } catch {
            toast.error('La recherche d’adresse a échoué. Placez le point sur la carte.');
        } finally {
            setGeocodage(false);
        }
    };

    const describedBy = (champ: Champ) => (errors[champ as keyof typeof errors] ? `${champ}-error` : undefined);
    const responsable = users.find((u) => u.id === values.responsable_id) ?? null;
    const goToEtape = (id: EtapeId) => wizard.goTo(etapes.indexOf(id));

    /** Ligne d'un niveau : nombre de chambres + type d'occupation. */
    const ligneNiveau = (prefixe: `etages.${number}` | `ailes.${number}.etages.${number}`, index: number, erreur?: { chambres?: { message?: string }; type?: { message?: string } }) => (
        <div key={prefixe} className="grid grid-cols-1 items-start gap-3 rounded-brand border border-gray-200 bg-surface/50 p-3 sm:grid-cols-[130px_minmax(0,1fr)_minmax(0,1fr)]">
            <span className={cn('pt-2 text-sm font-medium', index === 0 ? 'text-primary' : 'text-gray-700')}>{libelleNiveau(index)}</span>
            <Field id={`${prefixe}.chambres`} label="Chambres" error={erreur?.chambres?.message}>
                <Controller
                    control={control}
                    name={`${prefixe}.chambres`}
                    render={({ field }) => (
                        <Counter
                            id={`${prefixe}.chambres`}
                            label={`chambres, ${libelleNiveau(index)}`}
                            value={field.value}
                            onChange={(valeur) => {
                                field.onChange(valeur);
                                setErreurStructure(null);
                            }}
                            onBlur={field.onBlur}
                            max={500}
                            invalid={!!erreur?.chambres}
                        />
                    )}
                />
            </Field>
            <Field id={`${prefixe}.type`} label="Occupation" error={erreur?.type?.message}>
                <Controller
                    control={control}
                    name={`${prefixe}.type`}
                    render={({ field }) => (
                        <Select value={field.value} onValueChange={field.onChange}>
                            <SelectTrigger id={`${prefixe}.type`} ref={field.ref} invalid={!!erreur?.type}>
                                <SelectValue placeholder="Type" />
                            </SelectTrigger>
                            <SelectContent>
                                {typesNiveau.map((type) => (
                                    <SelectItem key={type.value} value={type.value}>
                                        {type.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                />
            </Field>
        </div>
    );

    /** Ligne d'un niveau en modification : chambres (jamais sous l'existant) + occupation (figée si le niveau est occupé). */
    const ligneNiveauExistant = (
        indexAile: number,
        index: number,
        niveau: NiveauEdition,
        erreur?: { chambres?: { message?: string }; type?: { message?: string } },
    ) => {
        const prefixe = `existant.${indexAile}.niveaux.${index}` as const;
        const libelle = libelleNiveau(niveau.numero);
        return (
            <div
                key={niveau.code ?? `${prefixe}-nouveau`}
                className="grid grid-cols-1 items-start gap-3 rounded-brand border border-gray-200 bg-surface/50 p-3 sm:grid-cols-[130px_minmax(0,1fr)_minmax(0,1fr)]"
            >
                <span className="flex flex-wrap items-center gap-2 pt-2">
                    <span className={cn('text-sm font-medium', niveau.numero === 0 ? 'text-primary' : 'text-gray-700')}>{libelle}</span>
                    {!niveau.code && <span className="rounded-sm bg-soft px-1.5 py-0.5 text-xs text-primary">Nouveau</span>}
                </span>
                <Field
                    id={`${prefixe}.chambres`}
                    label="Chambres"
                    error={erreur?.chambres?.message}
                    hint={niveau.existantes ? `${nombre.format(niveau.existantes)} déjà créée(s) : on peut seulement en ajouter.` : undefined}
                >
                    <Controller
                        control={control}
                        name={`${prefixe}.chambres`}
                        render={({ field }) => (
                            <Counter
                                id={`${prefixe}.chambres`}
                                label={`chambres, ${libelle}`}
                                value={field.value}
                                onChange={(valeur) => {
                                    field.onChange(valeur);
                                    setErreurStructure(null);
                                }}
                                onBlur={field.onBlur}
                                min={niveau.existantes}
                                max={500}
                                invalid={!!erreur?.chambres}
                            />
                        )}
                    />
                </Field>
                <Field
                    id={`${prefixe}.type`}
                    label="Occupation"
                    error={erreur?.type?.message}
                    hint={niveau.occupees ? 'Niveau occupé : occupation non modifiable.' : undefined}
                >
                    <Controller
                        control={control}
                        name={`${prefixe}.type`}
                        render={({ field }) => (
                            <Select value={field.value} onValueChange={field.onChange} disabled={niveau.occupees > 0}>
                                <SelectTrigger id={`${prefixe}.type`} ref={field.ref} invalid={!!erreur?.type}>
                                    <SelectValue placeholder="Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    {typesNiveau.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
                    />
                </Field>
            </div>
        );
    };

    return (
        <form
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                void suivant();
            }}
        >
            <Wizard
                steps={steps}
                current={wizard.current}
                reached={wizard.reached}
                onStepClick={(index) => void allerA(index)}
                onPrevious={wizard.previous}
                onNext={() => void suivant()}
                cancelUrl={cancelUrl}
                submitting={submitting}
                submitLabel={isEdit ? 'Enregistrer les modifications' : 'Enregistrer le bâtiment'}
                submitDisabled={!submitUrl}
            >
                {erreurGenerale && <FormAlert>{erreurGenerale}</FormAlert>}
                {!submitUrl && <FormAlert>Vous n’avez pas le droit d’enregistrer ce bâtiment.</FormAlert>}

                {etapeCourante === 'identite' && (
                    <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                        <Field id="code_cite" label="Cité de rattachement" required error={errors.code_cite?.message}>
                            <Select value={values.code_cite || undefined} onValueChange={choisirCite}>
                                <SelectTrigger id="code_cite" invalid={!!errors.code_cite} aria-describedby={describedBy('code_cite')}>
                                    <SelectValue placeholder={cites.length ? 'Sélectionnez une cité' : 'Aucune cité : créez-en une d’abord'} />
                                </SelectTrigger>
                                <SelectContent>
                                    {cites.map((c) => (
                                        <SelectItem key={c.id} value={c.id}>
                                            {c.name}
                                            {!c.actif ? ' (désactivée)' : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                        <Field id="name" label="Nom / numéro du bâtiment" required error={errors.name?.message} hint="Unique dans la cité.">
                            <Input id="name" placeholder="Ex : BAT A1" invalid={!!errors.name} aria-describedby={describedBy('name')} {...register('name')} />
                        </Field>
                        <Field
                            id="type_occupation"
                            label="Type d'occupation"
                            required
                            error={errors.type_occupation?.message}
                            hint={cite && typesPermis.length === 1 ? `Imposé par la cité « ${cite.name} ».` : 'Public accueilli dans le bâtiment.'}
                        >
                            <Select
                                value={values.type_occupation || undefined}
                                onValueChange={(id) => {
                                    setValue('type_occupation', id, { shouldValidate: true, shouldDirty: true });
                                    alignerNiveaux(id);
                                }}
                                disabled={!values.code_cite}
                            >
                                <SelectTrigger id="type_occupation" invalid={!!errors.type_occupation} aria-describedby={describedBy('type_occupation')}>
                                    <SelectValue placeholder={values.code_cite ? 'Sélectionnez un type' : 'Choisissez d’abord la cité'} />
                                </SelectTrigger>
                                <SelectContent>
                                    {typesPermis.map((t) => (
                                        <SelectItem key={t.id} value={t.id}>
                                            {t.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                        <Field id="etat_initial" label="État du bâtiment" required error={errors.etat_initial?.message}>
                            <Controller
                                control={control}
                                name="etat_initial"
                                render={({ field }) => (
                                    <Select value={field.value} onValueChange={field.onChange}>
                                        <SelectTrigger id="etat_initial" ref={field.ref}>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {ETATS.map((etat) => (
                                                <SelectItem key={etat.value} value={etat.value}>
                                                    {etat.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                        </Field>
                        <Field id="responsable_id" label="Concierge" error={errors.responsable_id?.message}>
                            <Select value={values.responsable_id || undefined} onValueChange={(id) => setValue('responsable_id', id, { shouldDirty: true })}>
                                <SelectTrigger id="responsable_id">
                                    <SelectValue placeholder="Sélectionnez le concierge (facultatif)" />
                                </SelectTrigger>
                                <SelectContent>
                                    {users.map((user) => (
                                        <SelectItem key={user.id} value={user.id}>
                                            {user.name}
                                            {user.matricule ? ` — ${user.matricule}` : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                    </div>
                )}

                {etapeCourante === 'localisation' && (
                    <>
                        <Field id="adresse" label="Rechercher une adresse" hint="Sert uniquement à trouver les coordonnées.">
                            <div className="flex flex-col gap-2 sm:flex-row">
                                <div className="flex-1">
                                    <Input id="adresse" placeholder="Ex : Cité Kalilou, Bondoukou" icon={<MapPin />} {...register('adresse')} />
                                </div>
                                <Button variant="secondary" className="h-11" onClick={() => void chercherAdresse()} disabled={geocodage}>
                                    {geocodage ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <LocateFixed className="size-4" aria-hidden />}
                                    Trouver les coordonnées
                                </Button>
                            </div>
                        </Field>
                        <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                            <Field id="latitude" label="Latitude" error={errors.latitude?.message}>
                                <Input id="latitude" inputMode="decimal" placeholder="Ex : 8.040200" className="tabular-nums" invalid={!!errors.latitude} aria-describedby={describedBy('latitude')} {...register('latitude')} />
                            </Field>
                            <Field id="longitude" label="Longitude" error={errors.longitude?.message}>
                                <Input
                                    id="longitude"
                                    inputMode="decimal"
                                    placeholder="Ex : -2.800000"
                                    className="tabular-nums"
                                    invalid={!!errors.longitude}
                                    aria-describedby={describedBy('longitude')}
                                    {...register('longitude')}
                                />
                            </Field>
                        </div>
                        <Button variant="outline" className="h-11 w-full" onClick={() => setCarteOuverte(true)}>
                            <MapIcon className="size-4" aria-hidden />
                            Placer le bâtiment sur la carte
                        </Button>
                        <PositionPicker
                            open={carteOuverte}
                            onOpenChange={setCarteOuverte}
                            titre="Position du bâtiment"
                            initial={(() => {
                                const lat = lireCoordonnee(values.latitude);
                                const lng = lireCoordonnee(values.longitude);
                                return lat !== null && lng !== null ? { lat, lng } : null;
                            })()}
                            onConfirm={({ lat, lng }) => {
                                setValue('latitude', lat.toFixed(6), { shouldValidate: true });
                                setValue('longitude', lng.toFixed(6), { shouldValidate: true });
                            }}
                        />
                    </>
                )}

                {etapeCourante === 'structure' && isEdit && (
                    <>
                        <div className="flex items-start gap-3 rounded-brand border bg-surface/60 px-4 py-3 text-sm text-muted">
                            <Layers className="mt-0.5 size-4 shrink-0 text-primary" aria-hidden />
                            <span>
                                {values.type_architecture === 'complexe' ? 'Structure à ailes' : 'Structure simple'} : vous pouvez ajouter des étages
                                {values.type_architecture === 'complexe' ? ', des ailes' : ''} et des chambres, changer l’occupation d’un niveau ou retirer des
                                niveaux en commençant par le plus haut (s’ils n’ont aucune chambre occupée). Le nombre de chambres d’un niveau gardé ne peut pas
                                diminuer.
                            </span>
                        </div>

                        {values.existant.map((aile, indexAile) => {
                            const erreurAile = errors.existant?.[indexAile];
                            const dernier = aile.niveaux[aile.niveaux.length - 1];
                            const retire = aile.retires[aile.retires.length - 1];
                            const nommable = values.type_architecture === 'complexe' || values.existant.length > 1;
                            return (
                                <section key={aile.code ?? `aile-nouvelle-${indexAile}`} className="space-y-3 rounded-brand border p-4">
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                                        <div className="flex-1">
                                            {nommable ? (
                                                <Field id={`existant.${indexAile}.nom`} label={`Aile ${indexAile + 1}`} required error={erreurAile?.nom?.message}>
                                                    <Input
                                                        id={`existant.${indexAile}.nom`}
                                                        placeholder="Nom de l'aile"
                                                        invalid={!!erreurAile?.nom}
                                                        {...register(`existant.${indexAile}.nom`)}
                                                    />
                                                </Field>
                                            ) : (
                                                <SectionTitle hint={`${nombre.format(aile.niveaux.length)} niveau(x)`}>Niveaux du bâtiment</SectionTitle>
                                            )}
                                        </div>
                                        <p className="flex h-11 items-center gap-1.5 text-sm text-muted sm:justify-end">
                                            <BedDouble className="size-4 text-primary" aria-hidden />
                                            {nombre.format(totalNiveaux(aile.niveaux))} chambre(s)
                                        </p>
                                        {!aile.code && (
                                            <Button variant="ghost" className="h-11 text-red-700 hover:bg-red-50" onClick={() => retirerAile(indexAile)}>
                                                <Trash2 className="size-4" aria-hidden />
                                                Retirer l’aile
                                            </Button>
                                        )}
                                    </div>
                                    {aile.niveaux.length === 0 && <p className="text-sm text-muted">Aucun niveau enregistré pour l’instant.</p>}
                                    <div className="space-y-2">
                                        {aile.niveaux.map((niveau, index) => ligneNiveauExistant(indexAile, index, niveau, erreurAile?.niveaux?.[index]))}
                                    </div>
                                    <div className="flex flex-col gap-2 sm:flex-row">
                                        <Button variant="outline" className="h-10" onClick={() => ajouterEtage(indexAile)} disabled={(dernier?.numero ?? -1) >= MAX_NIVEAUX}>
                                            <Plus className="size-4" aria-hidden />
                                            {dernier ? 'Ajouter un étage' : 'Ajouter le rez-de-chaussée'}
                                        </Button>
                                        {dernier && (
                                            <Button
                                                variant="ghost"
                                                className="h-10 text-red-700 hover:bg-red-50"
                                                onClick={() => retirerNiveauSuperieur(indexAile)}
                                                disabled={dernier.occupees > 0}
                                            >
                                                <Trash2 className="size-4" aria-hidden />
                                                Retirer « {libelleNiveau(dernier.numero)} »
                                            </Button>
                                        )}
                                    </div>
                                    {dernier && dernier.occupees > 0 && (
                                        <p className="text-xs text-muted">
                                            « {libelleNiveau(dernier.numero)} » a des chambres occupées : libérez-les avant de retirer ce niveau.
                                        </p>
                                    )}
                                    {retire && (
                                        <div className="flex flex-col gap-2 rounded-brand border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between">
                                            <span>
                                                Niveau(x) retiré(s) : {aile.retires.map((n) => libelleNiveau(n.numero)).join(', ')}
                                                {aile.retires.some((n) => n.existantes > 0) &&
                                                    ` — ${nombre.format(aile.retires.reduce((t, n) => t + n.existantes, 0))} chambre(s) seront supprimées à l’enregistrement`}
                                                .
                                            </span>
                                            <Button
                                                variant="outline"
                                                className="h-9 shrink-0 bg-white"
                                                onClick={() => retablirNiveau(indexAile)}
                                                disabled={(dernier?.numero ?? -1) >= retire.numero}
                                            >
                                                <RotateCcw className="size-4" aria-hidden />
                                                Rétablir « {libelleNiveau(retire.numero)} »
                                            </Button>
                                        </div>
                                    )}
                                </section>
                            );
                        })}

                        {values.type_architecture === 'complexe' && (
                            <Button variant="outline" className="h-11 w-full" onClick={ajouterAile} disabled={values.existant.length >= MAX_AILES}>
                                <Plus className="size-4" aria-hidden />
                                Ajouter une aile
                            </Button>
                        )}

                        <div
                            className={cn(
                                'flex items-center justify-between gap-3 rounded-brand border px-4 py-3',
                                erreurStructure ? 'border-red-200 bg-red-50' : 'border-secondary/40 bg-soft/60',
                            )}
                        >
                            <span className="flex items-center gap-2 text-sm text-gray-900">
                                <BedDouble className="size-4 text-primary" aria-hidden />
                                Total du bâtiment
                            </span>
                            <span className="text-right">
                                <span className="block text-base font-medium text-primary tabular-nums">{nombre.format(total)} chambre(s)</span>
                                {ajoutees > 0 && <span className="block text-xs text-muted">dont {nombre.format(ajoutees)} à créer</span>}
                                {chambresSupprimees > 0 && <span className="block text-xs text-red-700">{nombre.format(chambresSupprimees)} à supprimer</span>}
                            </span>
                        </div>
                        {erreurStructure && <FormAlert>{erreurStructure}</FormAlert>}
                        <p className="text-xs text-muted">
                            Les chambres ajoutées sont créées vides : leur numéro, leur type et leurs lits se configurent ensuite dans « Nouvelle chambre ».
                        </p>
                    </>
                )}

                {etapeCourante === 'structure' && !isEdit && (
                    <>
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <ChoiceCard
                                name="type_architecture"
                                value="simple"
                                selected={values.type_architecture === 'simple'}
                                onSelect={() => setValue('type_architecture', 'simple')}
                                title="Structure simple"
                                description="Un seul bloc : tour, bâtiment linéaire."
                                icon={<Building2 className="size-5" strokeWidth={1.25} aria-hidden />}
                            />
                            <ChoiceCard
                                name="type_architecture"
                                value="complexe"
                                selected={values.type_architecture === 'complexe'}
                                onSelect={() => setValue('type_architecture', 'complexe')}
                                title="Structure à ailes"
                                description="Plusieurs ailes : forme en U, H, L, étoile…"
                                icon={<Network className="size-5" strokeWidth={1.25} aria-hidden />}
                            />
                        </div>

                        {values.type_architecture === 'simple' ? (
                            <div className="space-y-4">
                                <div className="max-w-xs">
                                    <Field id="nombre_etages" label="Nombre d'étages (R+…)" required error={errors.nombre_etages?.message} hint="0 = rez-de-chaussée seul.">
                                        <Counter
                                            id="nombre_etages"
                                            label="nombre d'étages"
                                            value={values.nombre_etages}
                                            onChange={changerNombreEtages}
                                            max={MAX_NIVEAUX}
                                            invalid={!!errors.nombre_etages}
                                        />
                                    </Field>
                                </div>
                                <div className="space-y-2">{values.etages.map((_, index) => ligneNiveau(`etages.${index}`, index, errors.etages?.[index]))}</div>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-3">
                                    <Field id="nombre_ailes" label="Nombre d'ailes" required error={errors.nombre_ailes?.message}>
                                        <Counter id="nombre_ailes" label="nombre d'ailes" value={values.nombre_ailes} onChange={changerNombreAiles} min={1} max={MAX_AILES} invalid={!!errors.nombre_ailes} />
                                    </Field>
                                    <Field id="config_niveaux" label="Hauteur des ailes" required>
                                        <Select value={values.config_niveaux} onValueChange={(v) => changerConfig(v as FormInput['config_niveaux'])}>
                                            <SelectTrigger id="config_niveaux">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="meme-hauteur">Même hauteur partout</SelectItem>
                                                <SelectItem value="hauteur-variable">Hauteur variable</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    {values.config_niveaux === 'meme-hauteur' && (
                                        <Field id="nombre_niveaux_global" label="Étages par aile (R+…)" required error={errors.nombre_niveaux_global?.message}>
                                            <Counter
                                                id="nombre_niveaux_global"
                                                label="étages par aile"
                                                value={values.nombre_niveaux_global}
                                                onChange={(v) => changerNiveauxAile('tous', v)}
                                                max={MAX_NIVEAUX}
                                                invalid={!!errors.nombre_niveaux_global}
                                            />
                                        </Field>
                                    )}
                                </div>
                                {values.ailes.map((aile, index) => {
                                    const erreurAile = errors.ailes?.[index];
                                    return (
                                        <section key={`aile-${index}`} className="space-y-3 rounded-brand border p-4">
                                            <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                                                <div className="flex-1">
                                                    <Field id={`ailes.${index}.nom`} label={`Aile ${index + 1}`} required error={erreurAile?.nom?.message}>
                                                        <Input id={`ailes.${index}.nom`} placeholder="Nom de l'aile" invalid={!!erreurAile?.nom} {...register(`ailes.${index}.nom`)} />
                                                    </Field>
                                                </div>
                                                {values.config_niveaux === 'hauteur-variable' && (
                                                    <div className="sm:w-56">
                                                        <Field id={`ailes.${index}.niveaux`} label="Étages (R+…)" required error={erreurAile?.niveaux?.message}>
                                                            <Counter
                                                                id={`ailes.${index}.niveaux`}
                                                                label={`étages de l'aile ${index + 1}`}
                                                                value={aile.niveaux}
                                                                onChange={(v) => changerNiveauxAile(index, v)}
                                                                max={MAX_NIVEAUX}
                                                                invalid={!!erreurAile?.niveaux}
                                                            />
                                                        </Field>
                                                    </div>
                                                )}
                                                <p className="flex h-11 items-center gap-1.5 text-sm text-muted sm:justify-end">
                                                    <BedDouble className="size-4 text-primary" aria-hidden />
                                                    {nombre.format(totalNiveaux(aile.etages))} chambre(s)
                                                </p>
                                            </div>
                                            <div className="space-y-2">
                                                {aile.etages.map((_, niveau) => ligneNiveau(`ailes.${index}.etages.${niveau}`, niveau, erreurAile?.etages?.[niveau]))}
                                            </div>
                                        </section>
                                    );
                                })}
                            </div>
                        )}

                        <div
                            className={cn(
                                'flex items-center justify-between gap-3 rounded-brand border px-4 py-3',
                                erreurStructure ? 'border-red-200 bg-red-50' : 'border-secondary/40 bg-soft/60',
                            )}
                        >
                            <span className="flex items-center gap-2 text-sm text-gray-900">
                                <BedDouble className="size-4 text-primary" aria-hidden />
                                Total du bâtiment
                            </span>
                            <span className="text-base font-medium text-primary tabular-nums">{nombre.format(total)} chambre(s)</span>
                        </div>
                        {erreurStructure && <FormAlert>{erreurStructure}</FormAlert>}
                        <p className="text-xs text-muted">
                            Les chambres sont créées vides : leur numéro, leur type et leurs lits se configurent ensuite dans « Nouvelle chambre ».
                        </p>
                    </>
                )}

                {etapeCourante === 'services' && (
                    <>
                        <SectionTitle>Services disponibles</SectionTitle>
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <Controller
                                control={control}
                                name="acces_handicape"
                                render={({ field }) => <ToggleCard checked={field.value} onChange={field.onChange} label="Accès PMR" icon={<Accessibility />} />}
                            />
                            <Controller
                                control={control}
                                name="ascenseur"
                                render={({ field }) => <ToggleCard checked={field.value} onChange={field.onChange} label="Ascenseur" icon={<ArrowUpDown />} />}
                            />
                            <Controller
                                control={control}
                                name="gardiennage"
                                render={({ field }) => <ToggleCard checked={field.value} onChange={field.onChange} label="Gardiennage" icon={<ShieldCheck />} />}
                            />
                        </div>
                        <SectionTitle hint="Facultatif.">Plan du bâtiment</SectionTitle>
                        <PlanUpload
                            id="plan_file"
                            file={plan}
                            onFileChange={(file) => {
                                setPlan(file);
                                setPlanError(erreurPlan(file, PLAN_MAX_MO));
                            }}
                            existingUrl={item?.plan}
                            removeExisting={removePlan}
                            onRemoveExistingChange={setRemovePlan}
                            maxMo={PLAN_MAX_MO}
                            error={planError ?? undefined}
                        />
                        <Field id="description" label="Description / notes" error={errors.description?.message}>
                            <textarea
                                id="description"
                                rows={3}
                                placeholder="Détails supplémentaires"
                                className="w-full rounded-brand border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:ring-2 focus:ring-soft focus:outline-none"
                                {...register('description')}
                            />
                        </Field>
                    </>
                )}

                {etapeCourante === 'verification' && (
                    <div className="space-y-4">
                        <RecapSection
                            title="Identification"
                            onEdit={() => goToEtape('identite')}
                            rows={[
                                ['Cité', cite?.name],
                                ['Nom', values.name],
                                ["Type d'occupation", typeNom(values.type_occupation)],
                                ['État', ETATS.find((e) => e.value === values.etat_initial)?.label],
                                ['Concierge', responsable?.name],
                            ]}
                        />
                        <RecapSection
                            title="Localisation"
                            onEdit={() => goToEtape('localisation')}
                            rows={[['Coordonnées GPS', values.latitude && values.longitude ? `${values.latitude}, ${values.longitude}` : 'Non renseignées']]}
                        />
                        {isEdit ? (
                            <RecapSection
                                title="Structure"
                                onEdit={() => goToEtape('structure')}
                                rows={[
                                    ['Architecture', values.type_architecture === 'complexe' ? `${values.existant.length} aile(s)` : 'Structure simple'],
                                    [
                                        'Niveaux',
                                        `${nombre.format(nombreNiveaux)} niveau(x)${nouveauxNiveaux ? `, dont ${nombre.format(nouveauxNiveaux)} nouveau(x)` : ''}${
                                            niveauxRetires ? ` ; ${nombre.format(niveauxRetires)} retiré(s)` : ''
                                        }`,
                                    ],
                                    [
                                        'Chambres',
                                        <span key="total">
                                            {nombre.format(total)}
                                            {ajoutees > 0 && <strong className="font-medium text-primary"> (dont {nombre.format(ajoutees)} à créer)</strong>}
                                            {chambresSupprimees > 0 && <strong className="font-medium text-red-700"> — {nombre.format(chambresSupprimees)} à supprimer</strong>}
                                        </span>,
                                    ],
                                ]}
                            />
                        ) : (
                            <RecapSection
                                title="Structure"
                                onEdit={() => goToEtape('structure')}
                                rows={[
                                    ['Architecture', values.type_architecture === 'simple' ? 'Structure simple' : `${values.ailes.length} ailes`],
                                    [
                                        'Niveaux',
                                        values.type_architecture === 'simple'
                                            ? `R+${values.nombre_etages} (${values.etages.length} niveau(x))`
                                            : values.config_niveaux === 'meme-hauteur'
                                              ? `R+${values.nombre_niveaux_global} par aile`
                                              : 'Hauteur variable',
                                    ],
                                    ['Chambres à créer', <strong key="total" className="font-medium text-primary">{nombre.format(total)}</strong>],
                                ]}
                            />
                        )}
                        <RecapSection
                            title="Services et plan"
                            onEdit={() => goToEtape('services')}
                            rows={[
                                [
                                    'Services',
                                    [values.acces_handicape && 'Accès PMR', values.ascenseur && 'Ascenseur', values.gardiennage && 'Gardiennage'].filter(Boolean).join(', ') || 'Aucun',
                                ],
                                ['Plan', plan ? plan.name : removePlan ? 'Plan actuel supprimé' : item?.plan ? 'Plan actuel conservé' : 'Aucun'],
                                ['Description', values.description],
                            ]}
                        />
                    </div>
                )}
            </Wizard>
        </form>
    );
}
