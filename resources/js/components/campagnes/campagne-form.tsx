import { zodResolver } from '@hookform/resolvers/zod';
import { router, usePage } from '@inertiajs/react';
import { Building, CalendarDays, ClipboardCheck, Info, ListChecks, Users } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { CONDITIONS, formatDate, NIVEAUX, TYPES_ADMISSION, type CampagneFormValues, type CreateCampagneProps, type Niveau } from '@/components/campagnes/types';
import { AdminLayout } from '@/components/layout/admin-layout';
import { RecapSection, ToggleCard } from '@/components/parc-immobilier/form/inputs';
import { decimalFacultatif, entierFacultatif } from '@/components/parc-immobilier/form/types';
import { FormAlert, SectionTitle, useWizard, Wizard, type WizardStep } from '@/components/parc-immobilier/form/wizard';
import { Badge } from '@/components/ui/badge';
import { DatePicker } from '@/components/ui/date-picker';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import type { SharedProps } from '@/types/global';

const CODES_NIVEAUX = Object.keys(NIVEAUX) as Niveau[];
type ChampAge = `age_${Niveau}_max`;
const champAge = (niveau: Niveau) => `age_${niveau}_max` as ChampAge;

const DATES = [
    { name: 'periode_debut', label: 'Ouverture de la campagne', hint: 'Les étudiants peuvent postuler à partir de cette date', article: 'la date d’ouverture' },
    { name: 'periode_fin', label: 'Clôture de la campagne', hint: 'Date limite de dépôt des candidatures', article: 'la date de clôture' },
    { name: 'date_echeance_demande', label: 'Échéance des demandes', hint: 'Date limite d’analyse des demandes', article: 'l’échéance des demandes' },
    { name: 'date_echeance_contrat', label: 'Échéance des contrats', hint: 'Date limite de signature des contrats', article: 'l’échéance des contrats' },
] as const;

type ChampDate = (typeof DATES)[number]['name'];

const date = (article: string) => z.string().min(1, `Renseignez ${article}.`);

/** Règles par champ (mêmes bornes que `CampagneRequest`) ; les règles entre champs sont dans `reglesCroisees`. */
const schema = z.object({
    annee_campagne: z
        .string()
        .trim()
        .regex(/^\d{4}-\d{4}$/, 'L’année universitaire doit respecter le format AAAA-AAAA (ex. 2025-2026).')
        .refine((v) => Number(v.slice(5)) === Number(v.slice(0, 4)) + 1, 'L’année universitaire doit couvrir deux années consécutives.'),
    description: z.string().trim().min(10, 'La description doit contenir au moins 10 caractères.').max(1000, '1 000 caractères au maximum.'),
    periode_debut: date(DATES[0].article),
    periode_fin: date(DATES[1].article),
    date_echeance_demande: date(DATES[2].article),
    date_echeance_contrat: date(DATES[3].article),
    moyenne_min: decimalFacultatif('La moyenne minimale').refine((v) => v === '' || Number(v.replace(',', '.')) <= 20, 'La moyenne minimale doit être comprise entre 0 et 20.'),
    pourcentage_validate: entierFacultatif('Le pourcentage de crédits validés', 0, 100),
    distance_min: decimalFacultatif('La distance'),
    age_L1_max: entierFacultatif('L’âge maximum', 0, 40),
    age_L2_max: entierFacultatif('L’âge maximum', 0, 40),
    age_L3_max: entierFacultatif('L’âge maximum', 0, 40),
    age_M1_max: entierFacultatif('L’âge maximum', 0, 40),
    age_M2_max: entierFacultatif('L’âge maximum', 0, 40),
    age_D1_max: entierFacultatif('L’âge maximum', 0, 40),
    age_D2_max: entierFacultatif('L’âge maximum', 0, 40),
    age_D3_max: entierFacultatif('L’âge maximum', 0, 40),
    admission: z.boolean(),
    readmission: z.boolean(),
    internationnaux: z.boolean(),
    redoublement: z.boolean(),
    pmr: z.boolean(),
    is_participate: z.boolean(),
    is_discipline: z.boolean(),
    residences: z.array(z.string()).min(1, 'Sélectionnez au moins une résidence.'),
    documents: z.array(z.string()),
});

type FormInput = z.input<typeof schema>;
type FormOutput = z.output<typeof schema>;
type Champ = keyof FormInput;

const STEPS: (WizardStep & { fields: Champ[] })[] = [
    { label: 'Informations', description: 'Année universitaire et présentation', icon: Info, fields: ['annee_campagne', 'description'] },
    { label: 'Calendrier', description: 'Ouverture, clôture et échéances', icon: CalendarDays, fields: DATES.map((d) => d.name) },
    {
        label: 'Critères',
        description: 'Moyenne, crédits, distance, âges',
        icon: ListChecks,
        fields: ['moyenne_min', 'pourcentage_validate', 'distance_min', ...CODES_NIVEAUX.map(champAge)],
    },
    {
        label: 'Public',
        description: 'Types d’admission et conditions',
        icon: Users,
        fields: [...TYPES_ADMISSION.map((t) => t.name), ...CONDITIONS.map((c) => c.name)],
    },
    { label: 'Résidences', description: 'Cités ouvertes et documents requis', icon: Building, fields: ['residences', 'documents'] },
    { label: 'Vérification', description: 'Relire avant d’enregistrer', icon: ClipboardCheck, fields: [] },
];

/**
 * Règles qui comparent plusieurs champs (dates successives, âges croissants, type d'admission).
 * Les âges reprennent `gt:age_<niveau précédent>_max` de `CampagneRequest`, qui refuse aussi un âge
 * dont le niveau précédent est vide : les âges se renseignent donc à partir de la Licence 1, sans trou.
 */
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

    CODES_NIVEAUX.forEach((niveau, index) => {
        if (index === 0) return;
        const valeur = values[champAge(niveau)].trim();
        if (valeur === '') return;
        const precedent = CODES_NIVEAUX[index - 1];
        const avant = values[champAge(precedent)].trim();
        if (avant === '') {
            erreurs[champAge(niveau)] = `Renseignez d’abord l’âge maximum en ${NIVEAUX[precedent]}.`;
        } else if (Number(valeur) <= Number(avant)) {
            erreurs[champAge(niveau)] = `Doit être supérieur à celui de ${NIVEAUX[precedent]} (${avant} ans).`;
        }
    });

    if (!values.admission && !values.readmission) {
        erreurs.admission = 'Choisissez au moins un type : admission ou réadmission.';
    }

    return erreurs;
}

/** Champs obligatoires : on peut avancer sans eux, mais l'enregistrement reste grisé tant qu'il en manque un. */
const OBLIGATOIRES: { champ: Champ; label: string }[] = [
    { champ: 'annee_campagne', label: 'Année universitaire' },
    { champ: 'description', label: 'Description' },
    ...DATES.map((item) => ({ champ: item.name as Champ, label: item.label })),
    { champ: 'admission', label: 'Type d’admission (admission ou réadmission)' },
    { champ: 'residences', label: 'Résidences concernées' },
];

/** Champ non renseigné (texte vide, liste vide ; pour les types d'admission : ni admission ni réadmission). */
function estVide(champ: Champ, values: FormInput): boolean {
    if (champ === 'admission' || champ === 'readmission') return !values.admission && !values.readmission;
    const valeur = values[champ];
    if (typeof valeur === 'string') return valeur.trim() === '';
    if (Array.isArray(valeur)) return valeur.length === 0;
    return false;
}

const VALEURS_INITIALES: CampagneFormValues = {
    annee_campagne: '',
    description: '',
    periode_debut: '',
    periode_fin: '',
    date_echeance_demande: '',
    date_echeance_contrat: '',
    moyenne_min: '',
    pourcentage_validate: '',
    distance_min: '',
    age_L1_max: '',
    age_L2_max: '',
    age_L3_max: '',
    age_M1_max: '',
    age_M2_max: '',
    age_D1_max: '',
    age_D2_max: '',
    age_D3_max: '',
    admission: false,
    readmission: false,
    internationnaux: false,
    redoublement: false,
    pmr: false,
    is_participate: false,
    is_discipline: false,
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
export function CampagneForm({ residences, documents, urls, campagne, dossiers = 0 }: CampagneFormProps) {
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
    useEffect(() => {
        const champs = Object.keys(serverErrors);
        if (!champs.length) return;
        let premiere = STEPS.length - 1;
        champs.forEach((cle) => {
            const champ = cle.split('.')[0] as Champ;
            const index = STEPS.findIndex((step) => step.fields.includes(champ));
            if (index >= 0) {
                setError(champ, { message: serverErrors[cle] });
                premiere = Math.min(premiere, index);
            }
        });
        wizard.goTo(premiere);
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
            {
                ...values,
                moyenne_min: values.moyenne_min.replace(',', '.'),
                distance_min: values.distance_min.replace(',', '.'),
            },
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
    const placesSelectionnees = selection.reduce((total, residence) => total + residence.places, 0);
    const describedBy = (champ: Champ) => (errors[champ] ? `${champ}-error` : undefined);
    const unknownError = serverErrors.error ?? serverErrors.campagne;
    const titre = campagne ? `Modifier la campagne ${campagne.annee_campagne}` : 'Nouvelle campagne';
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

    const nomsDocuments = documents.filter((document) => values.documents.includes(document.id)).map((document) => document.name);
    const oui = (valeur: boolean) => (valeur ? 'Oui' : 'Non');
    const unite = (valeur: string, suffixe: string) => (valeur.trim() === '' ? null : `${valeur.trim()} ${suffixe}`);

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
                            {dossiers} dossier(s) sont déjà rattachés à cette campagne. Les nouveaux critères s’appliqueront aux prochaines études de
                            dossiers ; les décisions déjà prises ne sont pas recalculées.
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
                                                autoFocus
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
                            <Field id="description" label="Description" required error={errors.description?.message} hint="Entre 10 et 1 000 caractères.">
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
                            <SectionTitle hint="Laisser vide un critère qui ne s’applique pas.">Critères académiques</SectionTitle>
                            <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-3">
                                <Field id="moyenne_min" label="Moyenne minimale" error={errors.moyenne_min?.message}>
                                    <Input
                                        id="moyenne_min"
                                        inputMode="decimal"
                                        placeholder="Ex : 10"
                                        trailing={<Unite>/ 20</Unite>}
                                        className="tabular-nums"
                                        invalid={!!errors.moyenne_min}
                                        aria-describedby={describedBy('moyenne_min')}
                                        {...register('moyenne_min')}
                                    />
                                </Field>
                                <Field
                                    id="pourcentage_validate"
                                    label="Crédits validés minimum"
                                    error={errors.pourcentage_validate?.message}
                                    hint="Ne s’applique pas aux étudiants de L1."
                                >
                                    <Input
                                        id="pourcentage_validate"
                                        inputMode="numeric"
                                        placeholder="Ex : 75"
                                        trailing={<Unite>%</Unite>}
                                        className="tabular-nums"
                                        invalid={!!errors.pourcentage_validate}
                                        aria-describedby={describedBy('pourcentage_validate')}
                                        {...register('pourcentage_validate')}
                                    />
                                </Field>
                                <Field id="distance_min" label="Distance domicile – campus" error={errors.distance_min?.message} hint="Distance minimale exigée.">
                                    <Input
                                        id="distance_min"
                                        inputMode="decimal"
                                        placeholder="Ex : 25"
                                        trailing={<Unite>km</Unite>}
                                        className="tabular-nums"
                                        invalid={!!errors.distance_min}
                                        aria-describedby={describedBy('distance_min')}
                                        {...register('distance_min')}
                                    />
                                </Field>
                            </div>
                            <Separator />
                            <SectionTitle hint="De 0 à 40 ans, en partant de la Licence 1 ; chaque niveau doit dépasser le précédent.">Âge maximum par niveau</SectionTitle>
                            <div className="grid grid-cols-2 gap-x-6 gap-y-5 md:grid-cols-4">
                                {CODES_NIVEAUX.map((niveau) => {
                                    const champ = champAge(niveau);
                                    return (
                                        <Field key={niveau} id={champ} label={NIVEAUX[niveau]} error={errors[champ]?.message}>
                                            <Input
                                                id={champ}
                                                inputMode="numeric"
                                                maxLength={2}
                                                trailing={<Unite>ans</Unite>}
                                                className="tabular-nums"
                                                invalid={!!errors[champ]}
                                                aria-describedby={describedBy(champ)}
                                                {...register(champ)}
                                            />
                                        </Field>
                                    );
                                })}
                            </div>
                        </>
                    )}

                    {wizard.current === 3 && (
                        <>
                            <SectionTitle hint="Au moins un type : admission ou réadmission.">Types d’admission</SectionTitle>
                            <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                                {TYPES_ADMISSION.map((type) => (
                                    <Controller
                                        key={type.name}
                                        control={control}
                                        name={type.name}
                                        render={({ field }) => (
                                            <ToggleCard
                                                checked={field.value}
                                                onChange={(coche) => {
                                                    field.onChange(coche);
                                                    clearErrors('admission');
                                                }}
                                                label={type.label}
                                                description={type.description}
                                            />
                                        )}
                                    />
                                ))}
                            </div>
                            {errors.admission && (
                                <p id="admission-error" role="alert" className="text-xs font-medium text-red-600">
                                    {errors.admission.message}
                                </p>
                            )}
                            <Separator />
                            <SectionTitle>Conditions</SectionTitle>
                            <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                                {CONDITIONS.map((condition) => (
                                    <Controller
                                        key={condition.name}
                                        control={control}
                                        name={condition.name}
                                        render={({ field }) => (
                                            <ToggleCard checked={field.value} onChange={field.onChange} label={condition.label} description={condition.description} />
                                        )}
                                    />
                                ))}
                            </div>
                        </>
                    )}

                    {wizard.current === 4 && (
                        <>
                            <SectionTitle hint="Cités ouvertes et places actuellement libres.">
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
                                                label={`${residence.name} · ${residence.places} place(s)`}
                                                description={
                                                    residence.chambres ? `${residence.chambres} chambre(s) disponible(s)` : 'Aucune chambre disponible'
                                                }
                                            />
                                        ))}
                                    </div>
                                    <p className="flex flex-wrap gap-x-6 gap-y-1 rounded-brand bg-surface/60 px-4 py-2.5 text-sm text-gray-700" aria-live="polite">
                                        <span>
                                            Résidences sélectionnées : <strong className="tabular-nums">{selection.length}</strong>
                                        </span>
                                        <span>
                                            Places libres : <strong className="tabular-nums">{placesSelectionnees}</strong>
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
                                <SectionTitle hint="Pièces que l’étudiant doit fournir avec sa demande.">Documents requis</SectionTitle>
                                {documents.length > 0 && (
                                    <span className="flex items-center gap-2 text-sm">
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
                                <p className="text-sm text-muted">Aucun document configuré.</p>
                            ) : (
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {documents.map((document) => (
                                        <ToggleCard
                                            key={document.id}
                                            checked={values.documents.includes(document.id)}
                                            onChange={(coche) => basculer('documents', document.id, coche)}
                                            label={document.name}
                                        />
                                    ))}
                                </div>
                            )}
                        </>
                    )}

                    {wizard.current === 5 && (
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
                                    ['Description', values.description],
                                ]}
                            />
                            <RecapSection title="Calendrier" onEdit={() => wizard.goTo(1)} rows={DATES.map((item) => [item.label, formatDate(values[item.name])])} />
                            <RecapSection
                                title="Critères"
                                onEdit={() => wizard.goTo(2)}
                                rows={[
                                    ['Moyenne minimale', unite(values.moyenne_min, '/ 20')],
                                    ['Crédits validés minimum', unite(values.pourcentage_validate, '%')],
                                    ['Distance domicile – campus', unite(values.distance_min, 'km')],
                                    [
                                        'Âges maximum',
                                        CODES_NIVEAUX.filter((niveau) => values[champAge(niveau)].trim() !== '')
                                            .map((niveau) => `${niveau} : ${values[champAge(niveau)]} ans`)
                                            .join(' · '),
                                    ],
                                ]}
                            />
                            <RecapSection
                                title="Public et conditions"
                                onEdit={() => wizard.goTo(3)}
                                rows={[...TYPES_ADMISSION, ...CONDITIONS].map((item): [string, string] => [item.label, oui(values[item.name])])}
                            />
                            <RecapSection
                                title="Résidences et documents"
                                onEdit={() => wizard.goTo(4)}
                                rows={[
                                    [
                                        `Résidences (${placesSelectionnees} place(s) libre(s))`,
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
                                    ['Documents requis', nomsDocuments.join(', ')],
                                ]}
                            />
                        </div>
                    )}
                </Wizard>
            </form>
        </AdminLayout>
    );
}

function Unite({ children }: { children: string }) {
    return <span className="pointer-events-none px-2.5 text-xs text-muted">{children}</span>;
}
