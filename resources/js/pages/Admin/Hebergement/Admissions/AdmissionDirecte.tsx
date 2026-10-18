import { zodResolver } from '@hookform/resolvers/zod';
import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, BedDouble, CalendarCheck, Download, Loader2, TriangleAlert, Upload, UserCheck, UserPlus, UserRound } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { FileDrop } from '@/components/ui/file-drop';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { Stepper } from '@/components/ui/stepper';
import { envoyer } from '@/lib/inertia-form';
import { cn } from '@/lib/utils';

type Option = { id: string; name: string };

type Props = {
    campagnes: { id: string; label: string }[];
    campagneActuelle: string | null;
    genres: Option[];
    cites: Option[];
    batiments: (Option & { cite_id: string })[];
    chambres: { id: string; numero: string; cite_id: string; batiment_id: string; libres: number; disponible: boolean }[];
    aujourdhui: string;
    import: { message: string; lignes: string[] } | null;
    urls: { admissions: string | null; store: string | null; template: string | null; import: string | null };
};

const COLONNES_OBLIGATOIRES = ['nom_prenoms', 'civilite', 'matricule', 'telephone', 'cite', 'batiment', 'campagne', 'chambre', 'date_entree'];

const schema = (aujourdhui: string) =>
    z.object({
        name: z.string().trim().min(1, 'Le nom et les prénoms sont obligatoires.').max(255, '255 caractères au maximum.'),
        genre_id: z.string().min(1, 'Choisissez une civilité.'),
        matricule: z.string().trim().min(1, 'Le matricule est obligatoire.').max(100, '100 caractères au maximum.'),
        telephone1: z.string().trim().min(1, 'Le téléphone est obligatoire.').max(30, '30 caractères au maximum.'),
        cite_id: z.string().min(1, 'Choisissez une cité.'),
        batiment_id: z.string().min(1, 'Choisissez un bâtiment.'),
        chambre_id: z.string().min(1, 'Choisissez une chambre.'),
        numero_lit: z.union([z.literal(''), z.coerce.number().int('Nombre entier attendu.').min(1, 'Au moins 1.')]),
        campagne_id: z.string().min(1, 'Choisissez une année académique.'),
        date_entree: z
            .string()
            .min(1, 'Indiquez la date réelle d’entrée.')
            .refine((date) => date <= aujourdhui, 'La date d’entrée ne peut pas être future.'),
    });

type Saisie = z.input<ReturnType<typeof schema>>;
type Valeurs = z.output<ReturnType<typeof schema>>;

const ETAPES = ['Identité', 'Logement'];
const CHAMPS: (keyof Saisie)[][] = [
    ['name', 'genre_id', 'matricule', 'telephone1'],
    ['cite_id', 'batiment_id', 'chambre_id', 'numero_lit', 'campagne_id', 'date_entree'],
];

/** Admission directe d'un étudiant déjà logé + import en masse (remplace `admin/etudiant/admission-directe.blade.php`). */
export default function AdmissionDirecte(props: Props) {
    const { campagnes, chambres, urls } = props;
    const bloque = campagnes.length === 0 || !chambres.some((chambre) => chambre.disponible);

    return (
        <AdminLayout title="Admission directe">
            <PageHeader
                title="Admission directe d’un étudiant"
                description="Enregistrez un étudiant déjà installé en cité : son dossier résident et son affectation sont confirmés immédiatement, sans demande d’admission."
                actions={
                    urls.admissions && (
                        <Link
                            href={urls.admissions}
                            className="inline-flex h-10 items-center gap-2 rounded-brand border border-gray-300 bg-white px-4 text-sm font-medium text-muted hover:bg-surface hover:text-primary"
                        >
                            <ArrowLeft {...ICON} aria-hidden /> Retour aux admissions
                        </Link>
                    )
                }
            />

            {campagnes.length === 0 ? (
                <Alerte>Aucune année académique n’est configurée. Créez d’abord une campagne.</Alerte>
            ) : (
                !chambres.some((chambre) => chambre.disponible) && <Alerte>Aucune place n’est actuellement disponible dans les chambres.</Alerte>
            )}

            <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
                {urls.store ? <Formulaire {...props} url={urls.store} bloque={bloque} /> : <div />}
                <aside className="flex flex-col gap-6">
                    {urls.import && <Import url={urls.import} template={urls.template} />}
                </aside>
            </div>

            {props.import && props.import.lignes.length > 0 && (
                // `key` : un nouvel import rouvre la fenêtre même si la précédente a été fermée.
                <LignesRefusees key={props.import.message + props.import.lignes.join('|')} rapport={props.import} admissions={urls.admissions} />
            )}
        </AdminLayout>
    );
}

function Formulaire({ campagnes, campagneActuelle, genres, cites, batiments, chambres, aujourdhui, urls, url, bloque }: Props & { url: string; bloque: boolean }) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const {
        register,
        control,
        handleSubmit,
        trigger,
        setValue,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<Saisie, unknown, Valeurs>({
        resolver: zodResolver(schema(aujourdhui)),
        mode: 'onTouched',
        defaultValues: {
            name: '',
            genre_id: '',
            matricule: '',
            telephone1: '',
            cite_id: '',
            batiment_id: '',
            chambre_id: '',
            numero_lit: '',
            campagne_id: campagneActuelle ?? '',
            date_entree: aujourdhui,
        },
    });
    const [citeId, batimentId] = useWatch({ control, name: ['cite_id', 'batiment_id'] });

    const allerA = async (cible: number) => {
        if (cible > etape && !(await trigger(CHAMPS[etape]))) return;
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const onSubmit = (valeurs: Valeurs) =>
        // Étudiant enregistré : le serveur redirige vers la liste des admissions.
        envoyer(url, { ...valeurs, numero_lit: valeurs.numero_lit === '' ? '' : String(valeurs.numero_lit) }, {
            onError: (erreurs) => {
                // Erreurs de champ affichées sous le champ ; retour à l'étape « Identité » si elle est concernée.
                let premiere = -1;
                Object.entries(erreurs).forEach(([cle, message]) => {
                    const index = CHAMPS.findIndex((champs) => champs.includes(cle as keyof Saisie));
                    if (index >= 0) {
                        setError(cle as keyof Saisie, { type: 'server', message });
                        premiere = premiere < 0 ? index : Math.min(premiere, index);
                    }
                });
                if (premiere >= 0) setEtape(premiere);
            },
        });

    const combobox = (nom: 'genre_id' | 'cite_id' | 'batiment_id' | 'chambre_id' | 'campagne_id', options: { value: string; label: string; description?: string }[], placeholder: string, extra: { disabled?: boolean; onChange?: () => void; recherche?: string } = {}) => (
        <Controller
            control={control}
            name={nom}
            render={({ field }) => (
                <Combobox
                    id={`ad-${nom}`}
                    value={field.value}
                    onChange={(valeur) => {
                        field.onChange(valeur);
                        extra.onChange?.();
                    }}
                    onBlur={field.onBlur}
                    invalid={!!errors[nom]}
                    disabled={extra.disabled}
                    placeholder={placeholder}
                    searchPlaceholder={extra.recherche ?? 'Rechercher…'}
                    options={options}
                />
            )}
        />
    );

    return (
        <form onSubmit={handleSubmit(onSubmit, (fautes) => setEtape(CHAMPS[0].some((champ) => fautes[champ]) ? 0 : 1))} noValidate className="overflow-hidden rounded-brand border bg-white">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b px-5 py-4">
                <h2 className="flex items-center gap-2 text-base font-medium text-gray-900">
                    <span className="flex size-8 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                        <UserPlus size={18} strokeWidth={1.25} />
                    </span>
                    Enregistrer un étudiant
                </h2>
                <span className="text-xs text-muted">
                    <span className="text-red-600">*</span> Champs obligatoires
                </span>
            </div>
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} className="px-5" />

            <div className="flex flex-col gap-6 p-5">
                {etape === 0 && (
                    <Section icone={UserRound} titre="Identité" description="Si le matricule ou le téléphone correspond à un étudiant existant, son dossier est réutilisé.">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <Field id="ad-name" label="Nom et prénoms" required error={errors.name?.message}>
                                    <Input id="ad-name" autoComplete="name" invalid={!!errors.name} {...register('name')} />
                                </Field>
                            </div>
                            <Field id="ad-genre_id" label="Civilité" required error={errors.genre_id?.message}>
                                {combobox('genre_id', genres.map((genre) => ({ value: genre.id, label: genre.name })), 'Sélectionnez une civilité')}
                            </Field>
                            <Field id="ad-matricule" label="Matricule" required error={errors.matricule?.message}>
                                <Input id="ad-matricule" invalid={!!errors.matricule} {...register('matricule')} />
                            </Field>
                            <Field id="ad-telephone1" label="Téléphone" required error={errors.telephone1?.message}>
                                <Input id="ad-telephone1" type="tel" autoComplete="tel" invalid={!!errors.telephone1} {...register('telephone1')} />
                            </Field>
                        </div>
                    </Section>
                )}

                {etape === 1 && (
                    <>
                        <Section icone={BedDouble} titre="Logement occupé" description="Toutes les chambres du bâtiment sélectionné sont listées ; seules celles ayant encore une place libre peuvent être choisies.">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Field id="ad-cite_id" label="Cité" required error={errors.cite_id?.message}>
                                    {combobox('cite_id', cites.map((cite) => ({ value: cite.id, label: cite.name })), 'Sélectionnez une cité', {
                                        recherche: 'Rechercher une cité…',
                                        onChange: () => {
                                            setValue('batiment_id', '');
                                            setValue('chambre_id', '');
                                        },
                                    })}
                                </Field>
                                <Field id="ad-batiment_id" label="Bâtiment" required error={errors.batiment_id?.message}>
                                    {combobox(
                                        'batiment_id',
                                        batiments.filter((batiment) => batiment.cite_id === citeId).map((batiment) => ({ value: batiment.id, label: batiment.name })),
                                        citeId ? 'Sélectionnez un bâtiment' : 'Sélectionnez d’abord une cité',
                                        { disabled: !citeId, recherche: 'Rechercher un bâtiment…', onChange: () => setValue('chambre_id', '') },
                                    )}
                                </Field>
                                <Field id="ad-chambre_id" label="Chambre" required error={errors.chambre_id?.message}>
                                    {combobox(
                                        'chambre_id',
                                        chambres
                                            .filter((chambre) => chambre.cite_id === citeId && chambre.batiment_id === batimentId)
                                            .map((chambre) => ({
                                                value: chambre.id,
                                                label: `Chambre ${chambre.numero}`,
                                                description: chambre.disponible
                                                    ? `${chambre.libres} place${chambre.libres > 1 ? 's' : ''} libre${chambre.libres > 1 ? 's' : ''}`
                                                    : 'Indisponible',
                                                disabled: !chambre.disponible,
                                            })),
                                        batimentId ? 'Sélectionnez une chambre' : 'Sélectionnez d’abord un bâtiment',
                                        { disabled: !batimentId, recherche: 'Rechercher une chambre…' },
                                    )}
                                </Field>
                                <Field id="ad-numero_lit" label="Numéro du lit" error={errors.numero_lit?.message} hint="Facultatif : laissé vide, le premier lit libre est attribué.">
                                    <Input id="ad-numero_lit" type="number" min={1} placeholder="Premier lit disponible" invalid={!!errors.numero_lit} {...register('numero_lit')} />
                                </Field>
                            </div>
                        </Section>
                        <Section icone={CalendarCheck} titre="Période d’occupation" description="Année académique de rattachement et date à laquelle l’étudiant est réellement entré dans la chambre.">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Field id="ad-campagne_id" label="Année académique" required error={errors.campagne_id?.message}>
                                    {combobox('campagne_id', campagnes.map((campagne) => ({ value: campagne.id, label: campagne.label })), 'Sélectionnez une année académique')}
                                </Field>
                                <Field id="ad-date_entree" label="Date réelle d’entrée" required error={errors.date_entree?.message}>
                                    <Controller
                                        control={control}
                                        name="date_entree"
                                        render={({ field }) => <DatePicker id="ad-date_entree" value={field.value} onChange={field.onChange} max={aujourdhui} invalid={!!errors.date_entree} />}
                                    />
                                </Field>
                            </div>
                        </Section>
                    </>
                )}
            </div>

            <div className="flex flex-col gap-3 border-t bg-surface/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <span className="text-xs text-muted">Le dossier passe directement à l’état « attribué définitif ».</span>
                <div className="flex flex-col-reverse gap-2 sm:flex-row">
                    {etape === 0 ? (
                        <>
                            {urls.admissions && (
                                <Link href={urls.admissions} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10')}>
                                    Annuler
                                </Link>
                            )}
                            <Button size="sm" className="h-10" onClick={() => allerA(1)}>
                                Suivant <ArrowRight className="size-4" aria-hidden />
                            </Button>
                        </>
                    ) : (
                        <>
                            <Button variant="outline" size="sm" className="h-10" onClick={() => setEtape(0)}>
                                <ArrowLeft className="size-4" aria-hidden /> Précédent
                            </Button>
                            <Button type="submit" size="sm" className="h-10" disabled={bloque || isSubmitting}>
                                {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <UserCheck className="size-4" aria-hidden />}
                                Enregistrer et affecter
                            </Button>
                        </>
                    )}
                </div>
            </div>
        </form>
    );
}

/** Import en masse (action `admission_directe.import` inchangée). */
function Import({ url, template }: { url: string; template: string | null }) {
    const [fichier, setFichier] = useState<File | null>(null);
    const [erreur, setErreur] = useState<string | null>(null);
    const [enCours, setEnCours] = useState(false);

    const importer = async () => {
        if (!fichier) {
            setErreur('Choisissez un fichier .xlsx ou .csv.');
            return;
        }
        if (!/\.(xlsx|csv|txt)$/i.test(fichier.name)) {
            setErreur('Formats acceptés : .xlsx ou .csv.');
            return;
        }
        if (fichier.size > 10 * 1024 * 1024) {
            setErreur('Le fichier dépasse 10 Mo.');
            return;
        }
        setEnCours(true);
        const ok = await envoyer(url, { fichier }, { forceFormData: true, onError: (erreurs) => setErreur(erreurs.fichier ?? Object.values(erreurs)[0] ?? null) });
        setEnCours(false);
        if (ok) setFichier(null);
    };

    return (
        <section className="overflow-hidden rounded-brand border bg-white">
            <h2 className="flex items-center gap-2 border-b px-4 py-3 text-base font-medium text-gray-900">
                <span className="flex size-8 items-center justify-center rounded-brand bg-[#F8E4EF] text-[#6E1F4E]" aria-hidden>
                    <Upload size={18} strokeWidth={1.25} />
                </span>
                Import en masse
            </h2>
            <ol className="flex flex-col divide-y">
                <li className="flex items-center justify-between gap-3 px-4 py-3">
                    <div>
                        <p className="text-sm font-medium text-gray-900">1. Télécharger le modèle</p>
                        <p className="text-xs text-muted">Listes déroulantes préremplies depuis la base.</p>
                    </div>
                    {template && (
                        <a href={template} className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }))}>
                            <Download className="size-4" aria-hidden /> Modèle
                        </a>
                    )}
                </li>
                <li className="px-4 py-3">
                    <p className="text-sm font-medium text-gray-900">2. Remplir une ligne par étudiant</p>
                    <p className="text-xs text-muted">Colonnes obligatoires :</p>
                    <div className="mt-1.5 flex flex-wrap gap-1">
                        {COLONNES_OBLIGATOIRES.map((colonne) => (
                            <Badge key={colonne} variant="neutral">
                                <code>{colonne}</code>
                            </Badge>
                        ))}
                    </div>
                    <p className="mt-1.5 text-xs text-muted">
                        Seul <code>numero_lit</code> est facultatif.
                    </p>
                    <p className="mt-1.5 text-xs text-muted">
                        Le modèle contient une ligne d’exemple (nom commençant par « EXEMPLE ») : remplacez-la ; si elle reste, elle est ignorée.
                    </p>
                </li>
                <li className="flex flex-col gap-3 px-4 py-3">
                    <p className="text-sm font-medium text-gray-900">3. Importer le fichier</p>
                    <Field id="ad-fichier" label="Fichier à importer" error={erreur ?? undefined}>
                        <FileDrop
                            id="ad-fichier"
                            file={fichier}
                            onChange={(choisi) => {
                                setFichier(choisi);
                                setErreur(null);
                            }}
                            accept=".xlsx,.csv,.txt"
                            title="Choisissez un fichier"
                            hint="Formats .xlsx ou .csv, 10 Mo maximum."
                            invalid={!!erreur}
                        />
                    </Field>
                    <Button size="sm" className="h-10 w-full" disabled={enCours} onClick={importer}>
                        {enCours ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Upload className="size-4" aria-hidden />}
                        Importer
                    </Button>
                </li>
            </ol>
            <p className="border-t bg-surface/50 px-4 py-3 text-xs text-muted">
                Les doublons, chambres pleines et lignes invalides sont signalés sans bloquer les autres lignes. Dossiers et affectations sont validés automatiquement.
            </p>
        </section>
    );
}

/** Rapport d'import affiché en fenêtre modale dès le retour sur la page. */
function LignesRefusees({ rapport, admissions }: { rapport: NonNullable<Props['import']>; admissions: string | null }) {
    const [ouvert, setOuvert] = useState(true);
    const importes = parseInt(rapport.message, 10) || 0;

    return (
        <Dialog open={ouvert} onOpenChange={setOuvert}>
            <DialogContent className="max-w-xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <span className="flex size-8 items-center justify-center rounded-brand bg-red-50 text-red-700" aria-hidden>
                            <TriangleAlert size={18} strokeWidth={1.25} />
                        </span>
                        {rapport.lignes.length} ligne(s) non enregistrée(s)
                    </DialogTitle>
                    <DialogDescription>{rapport.message}</DialogDescription>
                </DialogHeader>
                <DialogBody className="text-sm">
                    <ul className="list-disc space-y-1.5 pl-5">
                        {rapport.lignes.map((ligne) => (
                            <li key={ligne}>{ligne}</li>
                        ))}
                    </ul>
                    <p className="mt-4 text-xs text-muted">Corrigez ces lignes dans le fichier puis importez-le de nouveau : les lignes déjà enregistrées seront signalées comme doublons.</p>
                </DialogBody>
                <DialogFooter>
                    {importes > 0 && admissions && (
                        <Link href={admissions} className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }), 'h-10')}>
                            Voir les admissions
                        </Link>
                    )}
                    <Button size="sm" className="h-10" onClick={() => setOuvert(false)}>
                        Fermer
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function Alerte({ children }: { children: ReactNode }) {
    return (
        <p role="alert" className="mb-6 flex items-start gap-2 rounded-brand border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <TriangleAlert className="mt-0.5 size-4 shrink-0" aria-hidden />
            {children}
        </p>
    );
}

function Section({ icone: Icone, titre, description, children }: { icone: typeof UserRound; titre: string; description: string; children: ReactNode }) {
    return (
        <section className="flex flex-col gap-4">
            <div className="flex items-start gap-3">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                    <Icone {...ICON} />
                </span>
                <div>
                    <h3 className="text-base font-medium text-gray-900">{titre}</h3>
                    <p className="text-sm text-muted">{description}</p>
                </div>
            </div>
            {children}
        </section>
    );
}
