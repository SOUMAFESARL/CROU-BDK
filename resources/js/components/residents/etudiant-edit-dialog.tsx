import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Loader2, Save } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { Controller, useForm, useWatch, type FieldPath } from 'react-hook-form';
import { z } from 'zod';
import { ICON } from '@/components/layout/app-sidebar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Stepper } from '@/components/ui/stepper';
import { aujourdhui, Encadre, repartirErreurs } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';
import { avecFormatTelephone, formatTelephone, PROPS_TELEPHONE, telephoneFacultatif, telephoneObligatoire } from '@/lib/telephone';

const requis = (message: string) => z.string().trim().min(1, message);
const optionnel = (max: number) => z.string().trim().max(max, `${max} caractères au maximum.`);
const email = z.string().trim().max(255, '255 caractères au maximum.').refine((valeur) => valeur === '' || z.email().safeParse(valeur).success, 'Adresse e-mail invalide.');
const annee = z.string().trim().refine((valeur) => valeur === '' || /^\d{4}-\d{4}$/.test(valeur), 'Format attendu : 2025-2026.');
/** Nombre facultatif saisi en texte : vide accepté, sinon borné (le serveur revérifie). */
const nombre = (min: number, max: number | null, message: string, entier = false) =>
    z
        .string()
        .trim()
        .refine((valeur) => {
            if (valeur === '') return true;
            const n = Number(valeur.replace(',', '.'));
            return Number.isFinite(n) && n >= min && (max === null || n <= max) && (!entier || Number.isInteger(n));
        }, message);

const schema = z
    .object({
        // Identité
        name: requis('Le nom et les prénoms sont obligatoires.').max(255, '255 caractères au maximum.'),
        genre_id: requis('Choisissez la civilité.'),
        date_naissance: z.string(),
        lieu_naissance: optionnel(255),
        nationalite: optionnel(100),
        groupe_sanguin_id: z.string(),
        // Pièce d'identité
        type_piece_id: z.string(),
        numero_piece: optionnel(50),
        date_expiration: z.string(),
        // Coordonnées
        telephone1: telephoneObligatoire('Le téléphone principal est obligatoire.'),
        telephone2: telephoneFacultatif(),
        email1: email,
        email2: email,
        regions_id: z.string(),
        ville_id: z.string(),
        adresse: optionnel(255),
        distance: nombre(0, null, 'La distance doit être un nombre entier positif.', true),
        // Études
        matricule: requis('Le matricule est obligatoire.').max(100, '100 caractères au maximum.'),
        numero_etudiant: optionnel(100),
        annee_academique_actuel: annee,
        annee_academique_precedent: annee,
        niveau_etude_precedent_code: z.string(),
        niveau_etude_actuel_code: z.string(),
        filiere_code: z.string(),
        moyenne: nombre(0, 20, 'La moyenne doit être comprise entre 0 et 20.'),
        credit_valider: nombre(0, 100, 'Le pourcentage doit être un entier entre 0 et 100.', true),
        // Situation et tuteur
        boursier: z.boolean(),
        type_bourse: optionnel(100),
        allergies: z.boolean(),
        maladies_chroniques: z.boolean(),
        autiste: z.boolean(),
        name_tuteur: optionnel(255),
        telephone_tuteur: telephoneFacultatif(),
        adresse_tuteur: optionnel(255),
    });

export type ValeursEtudiant = z.infer<typeof schema> & {
    /** Faux si l'étudiant n'a aucune demande : les informations académiques de la demande ne sont alors pas modifiables. */
    avec_demande: boolean;
};

type Option = { id: string; name: string };
export type OptionsEtudiant = {
    genres: Option[];
    typesPiece: Option[];
    groupesSanguins: Option[];
    regions: Option[];
    villes: (Option & { region_id: string })[];
    niveaux: Option[];
    filieres: Option[];
};

type Champ = FieldPath<z.infer<typeof schema>>;

const CHAMPS_ETAPES: Champ[][] = [
    ['name', 'genre_id', 'date_naissance', 'lieu_naissance', 'nationalite', 'groupe_sanguin_id'],
    ['type_piece_id', 'numero_piece', 'date_expiration'],
    ['telephone1', 'telephone2', 'email1', 'email2', 'regions_id', 'ville_id', 'adresse', 'distance'],
    ['matricule', 'numero_etudiant', 'annee_academique_actuel', 'annee_academique_precedent', 'niveau_etude_precedent_code', 'niveau_etude_actuel_code', 'filiere_code', 'moyenne', 'credit_valider'],
    ['boursier', 'type_bourse', 'allergies', 'maladies_chroniques', 'autiste', 'name_tuteur', 'telephone_tuteur', 'adresse_tuteur'],
];
const ETAPES = ['Identité', 'Pièce d’identité', 'Coordonnées', 'Études', 'Situation & tuteur'];

type Props = {
    /** Étudiant à modifier (fenêtre fermée si `null`). */
    etudiant: { name: string; url: string; valeurs: ValeursEtudiant } | null;
    options: OptionsEtudiant;
    onClose: () => void;
};

/**
 * Modification de toutes les informations d'un étudiant par l'administration (`EtudiantEditionService`) :
 * fiche étudiant et informations académiques de sa dernière demande. Utilisée par les résidents et le traitement d'une demande.
 */
export function EtudiantEditDialog({ etudiant, options, onClose }: Props) {
    const [etape, setEtape] = useState(0);
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const {
        control,
        register,
        handleSubmit,
        reset,
        trigger,
        setError,
        setValue,
        formState: { errors, isSubmitting },
    } = useForm<z.infer<typeof schema>>({ resolver: zodResolver(schema), mode: 'onTouched' });
    const region = useWatch({ control, name: 'regions_id' });
    const boursier = useWatch({ control, name: 'boursier' });
    const avecDemande = etudiant?.valeurs.avec_demande ?? false;

    useEffect(() => {
        if (etudiant) {
            const { avec_demande: _, ...valeurs } = etudiant.valeurs;
            reset({
                ...valeurs,
                telephone1: formatTelephone(valeurs.telephone1),
                telephone2: formatTelephone(valeurs.telephone2),
                telephone_tuteur: formatTelephone(valeurs.telephone_tuteur),
            });
            setEtape(0);
            setErreurServeur(null);
        }
    }, [etudiant, reset]);

    // Valide toutes les étapes jusqu'à `cible` (exclue) avant d'y aller.
    const allerA = async (cible: number) => {
        if (cible <= etape) {
            setEtape(cible);
            return;
        }
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPES[index]))) {
                setEtape(index);
                return;
            }
        }
        setEtape(cible);
    };

    const onSubmit = (valeurs: z.infer<typeof schema>) => {
        if (!etudiant) return;
        setErreurServeur(null);
        return envoyer(etudiant.url, valeurs, {
            method: 'put',
            onSuccess: onClose,
            onError: (erreurs) => setErreurServeur(repartirErreurs(erreurs, CHAMPS_ETAPES, setError, setEtape)),
        });
    };

    const champ = (nom: Champ, label: string, opts: { required?: boolean; type?: string; placeholder?: string; inputMode?: 'decimal' | 'numeric' } = {}) => (
        <Field id={`ed-${nom}`} label={label} required={opts.required} error={errors[nom]?.message}>
            <Input id={`ed-${nom}`} type={opts.type ?? 'text'} inputMode={opts.inputMode} placeholder={opts.placeholder} invalid={!!errors[nom]} className="h-11" {...register(nom)} />
        </Field>
    );

    const telephone = (nom: 'telephone1' | 'telephone2' | 'telephone_tuteur', label: string, required = false) => (
        <Field id={`ed-${nom}`} label={label} required={required} error={errors[nom]?.message}>
            <Input id={`ed-${nom}`} {...PROPS_TELEPHONE} invalid={!!errors[nom]} className="h-11" {...avecFormatTelephone(register(nom))} />
        </Field>
    );

    const liste = (nom: Champ, label: string, items: Option[], opts: { required?: boolean; onChange?: (valeur: string) => void } = {}) => (
        <Field id={`ed-${nom}`} label={label} required={opts.required} error={errors[nom]?.message}>
            <Controller
                control={control}
                name={nom}
                render={({ field }) => (
                    <Combobox
                        id={`ed-${nom}`}
                        value={(field.value as string) ?? ''}
                        onChange={(valeur) => {
                            field.onChange(valeur);
                            opts.onChange?.(valeur);
                        }}
                        onBlur={field.onBlur}
                        options={[...(opts.required ? [] : [{ value: '', label: 'Non renseigné' }]), ...items.map((item) => ({ value: item.id, label: item.name }))]}
                        invalid={!!errors[nom]}
                        className="h-11"
                    />
                )}
            />
        </Field>
    );

    const date = (nom: Champ, label: string, borne: { min?: string; max?: string } = {}) => (
        <Field id={`ed-${nom}`} label={label} error={errors[nom]?.message}>
            <Controller
                control={control}
                name={nom}
                render={({ field }) => (
                    <DatePicker id={`ed-${nom}`} value={(field.value as string) ?? ''} onChange={field.onChange} min={borne.min} max={borne.max} listesMoisAnnee invalid={!!errors[nom]} />
                )}
            />
        </Field>
    );

    const caseACocher = (nom: Champ, label: string, aide?: string) => (
        <label htmlFor={`ed-${nom}`} className="flex cursor-pointer items-start gap-3 rounded-brand border bg-white px-3 py-2.5 text-sm">
            <Controller
                control={control}
                name={nom}
                render={({ field }) => <Checkbox id={`ed-${nom}`} checked={field.value === true} onCheckedChange={(valeur) => field.onChange(valeur === true)} className="mt-0.5" />}
            />
            <span className="min-w-0">
                {label}
                {aide && <span className="mt-0.5 block text-xs text-muted">{aide}</span>}
            </span>
        </label>
    );

    const villes = options.villes.filter((ville) => !region || ville.region_id === region);

    return (
        <Dialog open={!!etudiant} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-3xl">
                <form
                    onSubmit={handleSubmit(onSubmit, (fautes) => setEtape(Math.max(0, CHAMPS_ETAPES.findIndex((champs) => champs.some((nom) => fautes[nom as keyof typeof fautes])))))}
                    noValidate
                    className="flex min-h-0 flex-col"
                >
                    <DialogHeader>
                        <DialogTitle>Modifier les informations de l’étudiant</DialogTitle>
                        <DialogDescription>
                            {etudiant?.name} · Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                        </DialogDescription>
                    </DialogHeader>
                    {/* Fiche déjà renseignée : toutes les étapes sont accessibles, la validation complète a lieu à l'enregistrement. */}
                    <Stepper steps={ETAPES} current={etape} reached={ETAPES.length - 1} onStepClick={setEtape} />
                    <DialogBody className="space-y-4">
                        {erreurServeur && <Encadre ton="danger">{erreurServeur}</Encadre>}

                        {etape === 0 && (
                            <Grille>
                                <div className="sm:col-span-2">{champ('name', 'Nom et prénoms', { required: true })}</div>
                                {liste('genre_id', 'Civilité', options.genres, { required: true })}
                                {date('date_naissance', 'Date de naissance', { max: aujourdhui() })}
                                {champ('lieu_naissance', 'Lieu de naissance')}
                                {champ('nationalite', 'Nationalité', { placeholder: 'Ex. IVOIRIENNE' })}
                                {liste('groupe_sanguin_id', 'Groupe sanguin', options.groupesSanguins)}
                            </Grille>
                        )}

                        {etape === 1 && (
                            <Grille>
                                {liste('type_piece_id', 'Type de pièce', options.typesPiece)}
                                {champ('numero_piece', 'Numéro de la pièce')}
                                {date('date_expiration', 'Date d’expiration')}
                            </Grille>
                        )}

                        {etape === 2 && (
                            <Grille>
                                {telephone('telephone1', 'Téléphone principal', true)}
                                {telephone('telephone2', 'Téléphone secondaire')}
                                {champ('email1', 'E-mail principal', { type: 'email' })}
                                {champ('email2', 'E-mail secondaire', { type: 'email' })}
                                {liste('regions_id', 'Région d’origine', options.regions, {
                                    // Une ville d'une autre région n'est plus valable.
                                    onChange: () => setValue('ville_id', ''),
                                })}
                                {liste('ville_id', 'Ville', villes)}
                                {champ('adresse', 'Adresse')}
                                {champ('distance', 'Distance domicile – campus (km)', { inputMode: 'numeric' })}
                            </Grille>
                        )}

                        {etape === 3 && (
                            <>
                                <Grille>
                                    {champ('matricule', 'Matricule', { required: true })}
                                    {champ('numero_etudiant', 'Numéro étudiant')}
                                    {champ('annee_academique_actuel', 'Année académique actuelle', { placeholder: 'Ex. 2025-2026' })}
                                    {avecDemande && champ('annee_academique_precedent', 'Année académique précédente', { placeholder: 'Ex. 2024-2025' })}
                                    {avecDemande && liste('niveau_etude_precedent_code', 'Niveau précédent', options.niveaux)}
                                    {avecDemande && liste('niveau_etude_actuel_code', 'Niveau actuel', options.niveaux)}
                                    {avecDemande && <div className="sm:col-span-2">{liste('filiere_code', 'Filière', options.filieres)}</div>}
                                    {avecDemande && champ('moyenne', 'Moyenne (sur 20)', { inputMode: 'decimal' })}
                                    {avecDemande && champ('credit_valider', 'Crédits validés (%)', { inputMode: 'numeric' })}
                                </Grille>
                                {!avecDemande && <Encadre ton="info">Aucune demande : niveau, filière, moyenne et crédits ne sont pas modifiables.</Encadre>}
                            </>
                        )}

                        {etape === 4 && (
                            <>
                                <Grille>
                                    {caseACocher('boursier', 'Boursier')}
                                    {boursier ? champ('type_bourse', 'Type de bourse') : <div className="hidden sm:block" />}
                                    {caseACocher('allergies', 'Allergies')}
                                    {caseACocher('maladies_chroniques', 'Maladies chroniques')}
                                    {caseACocher('autiste', 'Trouble du spectre autistique')}
                                </Grille>
                                <h3 className="pt-2 text-sm font-medium text-gray-900">Tuteur</h3>
                                <Grille>
                                    <div className="sm:col-span-2">{champ('name_tuteur', 'Nom du tuteur')}</div>
                                    {telephone('telephone_tuteur', 'Téléphone du tuteur')}
                                    {champ('adresse_tuteur', 'Adresse du tuteur')}
                                </Grille>
                            </>
                        )}
                    </DialogBody>
                    <DialogFooter className="sm:justify-between">
                        <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={isSubmitting}>
                            Annuler
                        </Button>
                        <div className="flex flex-col-reverse gap-2 sm:flex-row">
                            {etape > 0 && (
                                <Button variant="outline" size="sm" className="h-10" onClick={() => setEtape(etape - 1)} disabled={isSubmitting}>
                                    <ArrowLeft {...ICON} aria-hidden /> Précédent
                                </Button>
                            )}
                            {etape < ETAPES.length - 1 ? (
                                <Button size="sm" className="h-10" onClick={() => allerA(etape + 1)}>
                                    Suivant <ArrowRight {...ICON} aria-hidden />
                                </Button>
                            ) : (
                                <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                                    {isSubmitting ? <Loader2 {...ICON} className="animate-spin" aria-hidden /> : <Save {...ICON} aria-hidden />}
                                    Enregistrer
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function Grille({ children }: { children: ReactNode }) {
    return <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">{children}</div>;
}
