import { zodResolver } from '@hookform/resolvers/zod';
import { router, usePage } from '@inertiajs/react';
import { Building, CalendarDays, ClipboardCheck, FileText, Info, Plus, RefreshCw, UserPlus, Users } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { formatDate, TYPES_CAMPAGNE, type CampagneFormValues, type CreateCampagneProps, type TypeCampagne } from '@/components/campagnes/types';
import { AdminLayout } from '@/components/layout/admin-layout';
import { DocumentFormulaire } from '@/components/parametrage-hebergement/document-form';
import { ChoiceCard, RecapSection, ToggleCard } from '@/components/parc-immobilier/form/inputs';
import { FormAlert, SectionTitle, useWizard, Wizard, type WizardStep } from '@/components/parc-immobilier/form/wizard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import type { SharedProps } from '@/types/global';

const DATES = [
    { name: 'periode_debut', label: 'Ouverture de la campagne', hint: 'Les étudiants peuvent postuler à partir de cette date', article: 'la date d’ouverture' },
    { name: 'periode_fin', label: 'Clôture de la campagne', hint: 'Date limite de dépôt des candidatures', article: 'la date de clôture' },
] as const;

type ChampDate = (typeof DATES)[number]['name'];

const date = (article: string) => z.string().min(1, `Renseignez ${article}.`);

const ICONES_TYPE: Record<TypeCampagne, typeof Info> = { admission: UserPlus, readmission: RefreshCw, admission_readmission: Users };

/** Règles par champ (mêmes bornes que `CampagneRequest`) ; les règles entre champs sont dans `reglesCroisees`. */
const schema = z.object({
    libelle: z.string().trim().min(1, 'Renseignez le libellé de la campagne.').max(150, '150 caractères au maximum.'),
    annee_campagne: z
        .string()
        .trim()
        .regex(/^\d{4}-\d{4}$/, 'L’année universitaire doit respecter le format AAAA-AAAA (ex. 2025-2026).')
        .refine((v) => Number(v.slice(5)) === Number(v.slice(0, 4)) + 1, 'L’année universitaire doit couvrir deux années consécutives.'),
    type: z
        .string()
        .refine((v) => TYPES_CAMPAGNE.some((item) => item.value === v), 'Choisissez le type de campagne.')
        .transform((v) => v as TypeCampagne),
    description: z.string().trim().max(1000, '1 000 caractères au maximum.'),
    periode_debut: date(DATES[0].article),
    periode_fin: date(DATES[1].article),
    residences: z.array(z.string()).min(1, 'Sélectionnez au moins une résidence.'),
    documents: z.array(z.string()),
});

type FormInput = z.input<typeof schema>;
type FormOutput = z.output<typeof schema>;
type Champ = keyof FormInput;

const STEPS: (WizardStep & { fields: Champ[] })[] = [
    { label: 'Informations', description: 'Année, type et présentation', icon: Info, fields: ['annee_campagne', 'libelle', 'type', 'description'] },
    { label: 'Calendrier', description: 'Ouverture et clôture', icon: CalendarDays, fields: DATES.map((d) => d.name) },
    { label: 'Résidences', description: 'Cités ouvertes et documents requis', icon: Building, fields: ['residences', 'documents'] },
    { label: 'Vérification', description: 'Relire avant d’enregistrer', icon: ClipboardCheck, fields: [] },
];

/** Règles qui comparent plusieurs champs (dates successives). */
function reglesCroisees(values: FormInput): Partial<Record<Champ, string>> {
    const erreurs: Partial<Record<Champ, string>> = {};

    DATES.forEach((item, index) => {
        if (index === 0) return;
        const precedente = DATES[index - 1];
        const valeur = values[item.name as ChampDate];
        const avant = values[precedente.name as ChampDate];
        if (valeur && avant && valeur <= avant) {
            const sujet = item.article.charAt(0).toUpperCase() + item.article.slice(1);
            erreurs[item.name] = `${sujet} doit être postérieure à ${precedente.article}.`;
        }
    });

    return erreurs;
}

/** Champs obligatoires : on peut avancer sans eux, mais l'enregistrement reste grisé tant qu'il en manque un. */
const OBLIGATOIRES: { champ: Champ; label: string }[] = [
    { champ: 'annee_campagne', label: 'Année universitaire' },
    { champ: 'libelle', label: 'Libellé' },
    { champ: 'type', label: 'Type de campagne' },
    ...DATES.map((item) => ({ champ: item.name as Champ, label: item.label })),
    { champ: 'residences', label: 'Résidences concernées' },
];

/** Champ non renseigné (texte vide, liste vide). */
function estVide(champ: Champ, values: FormInput): boolean {
    const valeur = values[champ];
    if (typeof valeur === 'string') return valeur.trim() === '';
    if (Array.isArray(valeur)) return valeur.length === 0;
    return false;
}

/** Année universitaire proposée par défaut : année en cours - année suivante (2026 → 2026-2027). */
const ANNEE_EN_COURS = new Date().getFullYear();

const VALEURS_INITIALES: CampagneFormValues = {
    annee_campagne: `${ANNEE_EN_COURS}-${ANNEE_EN_COURS + 1}`,
    type: '',
    libelle: '',
    description: '',
    periode_debut: '',
    periode_fin: '',
    residences: [],
    documents: [],
};

/** Année universitaire : tiret ajouté automatiquement après les quatre premiers chiffres (2025 → 2025-). */
const formaterAnnee = (saisie: string, precedente: string) => {
    const valeur = saisie.replace(/[^\d-]/g, '').slice(0, 9);
    return /^\d{4}$/.test(valeur) && valeur.length > precedente.length ? `${valeur}-` : valeur;
};

type CampagneFormProps = CreateCampagneProps & {
    /** Campagne à modifier ; absente en création. */
    campagne?: CampagneFormValues & { id: string };
    /** Nombre de dossiers déjà rattachés à la campagne modifiée. */
    dossiers?: number;
};

/** Assistant de saisie d'une campagne d'admission, en création comme en modification. */
export function CampagneForm({ residences, documents, ordreDocumentSuivant, urls, campagne, dossiers = 0 }: CampagneFormProps) {
    const { errors: serverErrors } = usePage<SharedProps>().props;
    // Toutes les étapes sont cliquables d'emblée, en création comme en modification (les champs vides sont tolérés ;
    // `allerA` bloque seulement sur une valeur saisie invalide dans une étape intermédiaire).
    const wizard = useWizard(STEPS.length, { toutesAccessibles: true });
    const [submitting, setSubmitting] = useState(false);
    // Étapes déjà ouvertes (toutes en modification) : seules celles-ci peuvent être cochées comme terminées.
    const [visitees, setVisitees] = useState(() => new Set<number>(campagne ? STEPS.map((_, index) => index) : [0]));
    useEffect(() => setVisitees((actuelles) => (actuelles.has(wizard.current) ? actuelles : new Set(actuelles).add(wizard.current))), [wizard.current]);

    const {
        register,
        control,
        trigger,
        watch,
        setValue,
        getValues,
        setError,
        clearErrors,
        getFieldState,
        setFocus,
        handleSubmit,
        formState: { errors },
    } = useForm<FormInput, unknown, FormOutput>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: campagne ?? VALEURS_INITIALES });

    // Erreurs Laravel (`residences.0` → `residences`) : affichées sous les champs, retour à la première étape concernée.
    // Les erreurs d'un autre formulaire (ajout de document, sac « document ») ne déplacent pas l'assistant.
    useEffect(() => {
        const champs = Object.keys(serverErrors);
        if (!champs.length) return;
        let premiere = STEPS.length - 1;
        let concernee = false;
        champs.forEach((cle) => {
            const champ = cle.split('.')[0] as Champ;
            const index = STEPS.findIndex((step) => step.fields.includes(champ));
            if (index >= 0 && typeof serverErrors[cle] === 'string') {
                setError(champ, { message: serverErrors[cle] });
                premiere = Math.min(premiere, index);
                concernee = true;
            }
        });
        if (concernee || serverErrors.error || serverErrors.campagne) wizard.goTo(premiere);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [serverErrors]);

    /**
     * Vérifie une étape. `tolererVides` (navigation) : les champs laissés vides ne bloquent pas et ne sont pas
     * signalés ; seules les valeurs saisies mais invalides empêchent d'avancer. Sans lui (enregistrement), tout est exigé.
     */
    const validerEtape = async (index: number, { tolererVides = false } = {}) => {
        const champs = STEPS[index].fields;
        let ok = await trigger(champs, { shouldFocus: !tolererVides });
        const valeurs = getValues();
        const verifies = tolererVides ? champs.filter((champ) => !estVide(champ, valeurs)) : champs;
        if (tolererVides) {
            clearErrors(champs.filter((champ) => estVide(champ, valeurs)));
            ok = verifies.every((champ) => !getFieldState(champ).error);
        }
        const croisees = reglesCroisees(valeurs);
        let premiere: Champ | null = null;
        verifies.forEach((champ) => {
            const message = croisees[champ];
            if (message && !getFieldState(champ).error) {
                setError(champ, { message }, { shouldFocus: premiere === null && ok });
                premiere ??= champ;
            }
        });
        if (tolererVides && !ok) {
            const enErreur = verifies.find((champ) => getFieldState(champ).error);
            if (enErreur) setFocus(enErreur);
        }
        return ok && premiere === null;
    };

    /** Aller à une étape plus loin vérifie les valeurs saisies des étapes intermédiaires (les champs vides sont tolérés). */
    const allerA = async (cible: number) => {
        for (let index = wizard.current; index < cible; index++) {
            if (!(await validerEtape(index, { tolererVides: true }))) {
                wizard.goTo(index);
                return;
            }
        }
        wizard.goTo(cible);
    };

    const envoyer = (values: FormOutput) => {
        if (!urls.submit) return;
        router[campagne ? 'put' : 'post'](
            urls.submit,
            values,
            {
                // Erreurs : la saisie est conservée. Succès : retour à la liste des campagnes.
                preserveState: 'errors',
                preserveScroll: true,
                onStart: () => setSubmitting(true),
                onFinish: () => setSubmitting(false),
            },
        );
    };

    const suivant = async () => {
        if (wizard.isLast) {
            // Dernier contrôle global : une erreur ramène sur l'étape concernée.
            for (let index = 0; index < STEPS.length - 1; index++) {
                if (!(await validerEtape(index))) {
                    wizard.goTo(index);
                    return;
                }
            }
            void handleSubmit(envoyer)();
            return;
        }
        if (await validerEtape(wizard.current, { tolererVides: true })) wizard.next();
    };

    const values = watch();
    const manquants = OBLIGATOIRES.filter(({ champ }) => estVide(champ, values)).map((item) => ({
        ...item,
        etape: STEPS.findIndex((step) => step.fields.includes(item.champ)),
    }));
    /** Coche d'une étape : ouverte au moins une fois, sans champ obligatoire vide ni erreur. */
    const etapeTerminee = (index: number) =>
        index < STEPS.length - 1 &&
        visitees.has(index) &&
        !manquants.some((item) => item.etape === index) &&
        !STEPS[index].fields.some((champ) => errors[champ]);
    const selection = useMemo(() => residences.filter((residence) => values.residences.includes(residence.id)), [residences, values.residences]);
    const chambresSelectionnees = selection.reduce((total, residence) => total + residence.chambres, 0);
    const describedBy = (champ: Champ) => (errors[champ] ? `${champ}-error` : undefined);
    const unknownError = serverErrors.error ?? serverErrors.campagne;
    const titre = campagne ? `Modifier la campagne ${campagne.libelle || campagne.annee_campagne}` : 'Nouvelle campagne';
    const breadcrumbs = [
        { label: 'Accueil' },
        { label: 'Gestion Hébergement', href: urls.hebergement },
        { label: 'Campagnes', href: urls.index },
        { label: campagne ? 'Modifier' : 'Nouvelle campagne' },
    ];

    /** Bascule une valeur dans une liste (résidences, documents) et revalide le champ s'il était en erreur. */
    const basculer = (champ: 'residences' | 'documents', id: string, coche: boolean) => {
        const actuelles = getValues(champ);
        const suivantes = coche ? [...actuelles, id] : actuelles.filter((valeur) => valeur !== id);
        setValue(champ, suivantes, { shouldDirty: true, shouldValidate: !!errors[champ] });
    };

    const documentsChoisis = documents.filter((document) => values.documents.includes(document.id));

    // Ajout d'un document sans quitter l'assistant : `document.store` revient sur cette page avec la liste à jour,
    // et le document créé est coché d'office (la saisie de la campagne est conservée).
    const [ajoutDocument, setAjoutDocument] = useState(false);
    const documentsConnus = useRef(new Set(documents.map((document) => document.id)));
    const cocherNouveaux = useRef(false);
    useEffect(() => {
        const nouveaux = documents.filter((document) => !documentsConnus.current.has(document.id)).map((document) => document.id);
        documentsConnus.current = new Set(documents.map((document) => document.id));
        if (cocherNouveaux.current && nouveaux.length) {
            cocherNouveaux.current = false;
            setValue('documents', [...new Set([...getValues('documents'), ...nouveaux])], { shouldDirty: true });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [documents]);
    const ouvrirAjoutDocument = () => {
        cocherNouveaux.current = true;
        setAjoutDocument(true);
    };

    return (
        <AdminLayout title={titre} breadcrumbs={breadcrumbs}>
            <div className="mb-6 flex flex-col gap-1">
                <h1 className="page-title text-xl font-medium text-gray-900">{titre}</h1>
                <p className="text-sm text-muted">
                    Renseignez la campagne étape par étape. Vous pouvez avancer sans tout remplir ; l’enregistrement n’est possible qu’une fois les champs
                    obligatoires (<span className="text-red-600">*</span>) renseignés.
                </p>
            </div>

            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    void suivant();
                }}
            >
                <Wizard
                    steps={STEPS}
                    current={wizard.current}
                    reached={wizard.reached}
                    onStepClick={(index) => void allerA(index)}
                    onPrevious={wizard.previous}
                    onNext={() => void suivant()}
                    cancelUrl={urls.index}
                    submitting={submitting}
                    submitLabel={campagne ? 'Enregistrer les modifications' : 'Enregistrer la campagne'}
                    submitDisabled={!urls.submit || manquants.length > 0}
                    isDone={etapeTerminee}
                >
                    {unknownError && <FormAlert>{unknownError}</FormAlert>}
                    {!urls.submit && <FormAlert>Vous n’avez pas le droit de {campagne ? 'modifier' : 'créer'} une campagne.</FormAlert>}
                    {campagne && dossiers > 0 && (
                        <p role="status" className="rounded-brand border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            {dossiers} dossier(s) sont déjà rattachés à cette campagne ; les décisions déjà prises ne sont pas recalculées.
                        </p>
                    )}

                    {wizard.current === 0 && (
                        <div className="grid grid-cols-1 gap-x-6 gap-y-5">
                            <div className="md:max-w-xs">
                                <Field
                                    id="annee_campagne"
                                    label="Année universitaire"
                                    required
                                    error={errors.annee_campagne?.message}
                                    hint="Format AAAA-AAAA, deux années consécutives."
                                >
                                    <Controller
                                        control={control}
                                        name="annee_campagne"
                                        render={({ field }) => (
                                            <Input
                                                id="annee_campagne"
                                                ref={field.ref}
                                                value={field.value}
                                                onBlur={field.onBlur}
                                                onChange={(event) => field.onChange(formaterAnnee(event.target.value, field.value))}
                                                inputMode="numeric"
                                                autoComplete="off"
                                                maxLength={9}
                                                placeholder="2025-2026"
                                                className="tabular-nums"
                                                invalid={!!errors.annee_campagne}
                                                aria-describedby={describedBy('annee_campagne')}
                                            />
                                        )}
                                    />
                                </Field>
                            </div>
                            <Field id="libelle" label="Libellé" required error={errors.libelle?.message}>
                                <Input
                                    id="libelle"
                                    autoFocus
                                    autoComplete="off"
                                    maxLength={150}
                                    placeholder="Ex : Campagne d’admission et de réadmission 2025-2026"
                                    invalid={!!errors.libelle}
                                    aria-describedby={describedBy('libelle')}
                                    {...register('libelle')}
                                />
                            </Field>
                            <fieldset className="space-y-1.5" aria-describedby={describedBy('type')}>
                                <legend className="mb-1.5 block text-sm font-medium text-gray-800">
                                    Type de campagne<span className="ml-0.5 text-red-600">*</span>
                                </legend>
                                <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                                    {TYPES_CAMPAGNE.map((item) => {
                                        const Icone = ICONES_TYPE[item.value];
                                        return (
                                            <ChoiceCard
                                                key={item.value}
                                                name="type"
                                                value={item.value}
                                                selected={values.type === item.value}
                                                onSelect={() => setValue('type', item.value, { shouldDirty: true, shouldValidate: !!errors.type })}
                                                title={item.label}
                                                description={item.description}
                                                icon={<Icone size={18} strokeWidth={1.25} aria-hidden />}
                                            />
                                        );
                                    })}
                                </div>
                                {errors.type && (
                                    <p id="type-error" role="alert" className="text-xs font-medium text-red-600">
                                        {errors.type.message}
                                    </p>
                                )}
                            </fieldset>
                            <Field id="description" label="Description" error={errors.description?.message}>
                                <textarea
                                    id="description"
                                    rows={4}
                                    maxLength={1000}
                                    placeholder="Objectifs et modalités de la campagne…"
                                    aria-invalid={!!errors.description || undefined}
                                    aria-describedby={describedBy('description')}
                                    className={cn(
                                        'w-full rounded-brand border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 transition-colors placeholder:text-gray-400',
                                        'focus:border-primary focus:ring-2 focus:ring-soft focus:outline-none',
                                        errors.description && 'border-red-500 focus:border-red-500 focus:ring-red-100',
                                    )}
                                    {...register('description')}
                                />
                            </Field>
                        </div>
                    )}

                    {wizard.current === 1 && (
                        <>
                            <SectionTitle hint="Chaque date doit être postérieure à la précédente.">Calendrier de la campagne</SectionTitle>
                            <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                                {DATES.map((item, index) => (
                                    <Field key={item.name} id={item.name} label={item.label} required error={errors[item.name]?.message} hint={item.hint}>
                                        <Controller
                                            control={control}
                                            name={item.name}
                                            render={({ field }) => (
                                                <DatePicker
                                                    id={item.name}
                                                    value={field.value}
                                                    onChange={(valeur) => {
                                                        field.onChange(valeur);
                                                        clearErrors(item.name);
                                                    }}
                                                    min={index > 0 ? values[DATES[index - 1].name] || undefined : undefined}
                                                    invalid={!!errors[item.name]}
                                                    aria-describedby={describedBy(item.name)}
                                                />
                                            )}
                                        />
                                    </Field>
                                ))}
                            </div>
                        </>
                    )}

                    {wizard.current === 2 && (
                        <>
                            <SectionTitle hint="Cités ouvertes et chambres actuellement disponibles.">
                                Résidences concernées <span className="text-red-600">*</span>
                            </SectionTitle>
                            {residences.length === 0 ? (
                                <FormAlert>Aucune résidence ouverte. Ouvrez une cité dans le parc immobilier avant d’enregistrer une campagne.</FormAlert>
                            ) : (
                                <>
                                    <div className="grid grid-cols-1 gap-3 md:grid-cols-2" role="group" aria-label="Résidences concernées" aria-describedby={describedBy('residences')}>
                                        {residences.map((residence) => (
                                            <ToggleCard
                                                key={residence.id}
                                                checked={values.residences.includes(residence.id)}
                                                onChange={(coche) => basculer('residences', residence.id, coche)}
                                                label={`${residence.name} · ${residence.chambres} chambre(s)`}
                                                description={residence.chambres ? undefined : 'Aucune chambre disponible'}
                                            />
                                        ))}
                                    </div>
                                    <p className="flex flex-wrap gap-x-6 gap-y-1 rounded-brand bg-surface/60 px-4 py-2.5 text-sm text-gray-700" aria-live="polite">
                                        <span>
                                            Résidences sélectionnées : <strong className="tabular-nums">{selection.length}</strong>
                                        </span>
                                        <span>
                                            Chambres disponibles : <strong className="tabular-nums">{chambresSelectionnees}</strong>
                                        </span>
                                    </p>
                                </>
                            )}
                            {errors.residences && (
                                <p id="residences-error" role="alert" className="text-xs font-medium text-red-600">
                                    {errors.residences.message}
                                </p>
                            )}

                            <Separator />
                            <div className="flex flex-wrap items-end justify-between gap-2">
                                <SectionTitle hint="Pièces que l’étudiant devra joindre à sa demande pour cette campagne.">Documents requis</SectionTitle>
                                {documents.length > 0 && (
                                    <span className="flex flex-wrap items-center gap-2 text-sm">
                                        <span className="text-muted" aria-live="polite">
                                            {values.documents.length} sélectionné(s) sur {documents.length}
                                        </span>
                                        <span className="text-muted" aria-hidden>
                                            ·
                                        </span>
                                        <button
                                            type="button"
                                            className="cursor-pointer text-primary hover:underline"
                                            onClick={() => setValue('documents', documents.map((document) => document.id), { shouldDirty: true })}
                                        >
                                            Tout sélectionner
                                        </button>
                                        <span className="text-muted" aria-hidden>
                                            ·
                                        </span>
                                        <button type="button" className="cursor-pointer text-primary hover:underline" onClick={() => setValue('documents', [], { shouldDirty: true })}>
                                            Aucun
                                        </button>
                                    </span>
                                )}
                            </div>
                            {documents.length === 0 ? (
                                <div className="flex flex-col items-center gap-2 rounded-brand border border-dashed px-4 py-6 text-center">
                                    <FileText size={18} strokeWidth={1.25} className="text-gray-400" aria-hidden />
                                    <p className="text-sm text-muted">
                                        Aucun document à fournir n’est encore paramétré.
                                        {urls.documentStore ? ' Ajoutez-en un pour le demander dans cette campagne.' : ' Demandez à un administrateur du paramétrage d’en ajouter.'}
                                    </p>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" role="group" aria-label="Documents requis">
                                    {documents.map((document) => (
                                        <ToggleCard
                                            key={document.id}
                                            checked={values.documents.includes(document.id)}
                                            onChange={(coche) => basculer('documents', document.id, coche)}
                                            label={document.name}
                                            description={document.requis ? 'Pièce obligatoire' : 'Pièce facultative'}
                                        />
                                    ))}
                                </div>
                            )}
                            {urls.documentStore && (
                                <div>
                                    <Button type="button" variant="outline" size="sm" className="h-10" onClick={ouvrirAjoutDocument}>
                                        <Plus className="size-4" aria-hidden />
                                        Ajouter un document
                                    </Button>
                                </div>
                            )}
                        </>
                    )}

                    {wizard.current === 3 && (
                        <div className="space-y-4">
                            {manquants.length > 0 && (
                                <div role="status" className="rounded-brand border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    <p>Renseignez les champs obligatoires suivants pour pouvoir enregistrer la campagne :</p>
                                    <ul className="mt-2 list-disc space-y-1 pl-5">
                                        {manquants.map((item) => (
                                            <li key={item.champ}>
                                                <button type="button" className="cursor-pointer underline-offset-2 hover:underline" onClick={() => wizard.goTo(item.etape)}>
                                                    {item.label}
                                                </button>{' '}
                                                <span className="text-amber-700/80">· étape {STEPS[item.etape].label}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                            <RecapSection
                                title="Informations"
                                onEdit={() => wizard.goTo(0)}
                                rows={[
                                    ['Année universitaire', values.annee_campagne],
                                    ['Libellé', values.libelle],
                                    ['Type de campagne', TYPES_CAMPAGNE.find((item) => item.value === values.type)?.label],
                                    ['Description', values.description],
                                ]}
                            />
                            <RecapSection title="Calendrier" onEdit={() => wizard.goTo(1)} rows={DATES.map((item) => [item.label, formatDate(values[item.name])])} />
                            <RecapSection
                                title="Résidences et documents"
                                onEdit={() => wizard.goTo(2)}
                                rows={[
                                    [
                                        `Résidences (${chambresSelectionnees} chambre(s) disponible(s))`,
                                        selection.length ? (
                                            <span className="flex flex-wrap gap-1">
                                                {selection.map((residence) => (
                                                    <Badge key={residence.id} variant="success">
                                                        {residence.name}
                                                    </Badge>
                                                ))}
                                            </span>
                                        ) : null,
                                    ],
                                    [
                                        'Documents requis',
                                        documentsChoisis.length ? (
                                            <span className="flex flex-wrap gap-1">
                                                {documentsChoisis.map((document) => (
                                                    <Badge key={document.id} variant={document.requis ? 'warning' : 'neutral'}>
                                                        {document.name}
                                                        {document.requis ? ' · obligatoire' : ' · facultatif'}
                                                    </Badge>
                                                ))}
                                            </span>
                                        ) : null,
                                    ],
                                ]}
                            />
                        </div>
                    )}
                </Wizard>
            </form>

            {urls.documentStore && (
                <Dialog open={ajoutDocument} onOpenChange={setAjoutDocument}>
                    <DialogContent className="max-w-xl">
                        {ajoutDocument && (
                            <DocumentFormulaire
                                document={null}
                                url={urls.documentStore}
                                ordreSuivant={ordreDocumentSuivant}
                                errorBag="document"
                                onClose={() => setAjoutDocument(false)}
                            />
                        )}
                    </DialogContent>
                </Dialog>
            )}
        </AdminLayout>
    );
}
