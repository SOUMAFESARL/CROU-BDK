import { zodResolver } from '@hookform/resolvers/zod';
import { router, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    ClipboardCheck,
    HeartPulse,
    Loader2,
    LocateFixed,
    Map as MapIcon,
    MapPin,
    Phone,
    Ruler,
    Utensils,
    Wifi,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Controller, useForm, type FieldPath } from 'react-hook-form';
import { toast } from 'sonner';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatTelephone } from '@/lib/telephone';
import type { SharedProps } from '@/types/global';
import { STATUTS } from '../labels';
import { Counter, erreurPlan, PlanUpload, RecapSection, ToggleCard } from './inputs';
import { geocoder, lireCoordonnee, PositionPicker } from './position-picker';
import {
    coordonnee,
    decimalFacultatif,
    emailFacultatif,
    entierFacultatif,
    entierObligatoire,
    PREFIXES_CI,
    telephoneFacultatif,
    texte,
    type CiteItem,
    type Option,
    type UserOption,
} from './types';
import { FormAlert, SectionTitle, useWizard, Wizard, type WizardStep } from './wizard';

const ANNEE = new Date().getFullYear();
const PLAN_MAX_MO = 10;

const schema = z.object({
    name: z.string().trim().min(1, 'Le nom de la cité est obligatoire.').max(255, '255 caractères au maximum.'),
    code_cite: z.string().trim().min(1, 'Le code de la cité est obligatoire.').max(50, '50 caractères au maximum.'),
    type_cite: z.string().min(1, 'Choisissez le type de cité.'),
    statut: z.enum(['ouvert', 'ferme', 'en_renovation', 'en_construction']),
    capacite_max: entierObligatoire('La capacité', 0, 100000),
    nombre_batiments: entierFacultatif('Le nombre de bâtiments', 0, 1000),
    superficie: decimalFacultatif('La superficie'),
    annee_construction: entierFacultatif("L'année de construction", 1900, ANNEE),
    responsable_id: z.string(),
    telephone1: telephoneFacultatif(PREFIXES_CI),
    telephone2: telephoneFacultatif(PREFIXES_CI),
    telephone3: telephoneFacultatif(PREFIXES_CI),
    email1: emailFacultatif,
    email2: emailFacultatif,
    reglement_id: z.string(),
    adresse: z.string().trim().min(1, "L'adresse géographique est obligatoire.").max(500, '500 caractères au maximum.'),
    latitude: coordonnee('La latitude', 90, true),
    longitude: coordonnee('La longitude', 180, true),
    wifi_gratuit: z.boolean(),
    salle_etude: z.boolean(),
    infirmerie: z.boolean(),
    restaurant: z.boolean(),
});

type FormInput = z.input<typeof schema>;
type FormOutput = z.output<typeof schema>;
type Champ = FieldPath<FormInput>;

const STEPS: (WizardStep & { fields: Champ[] })[] = [
    { label: 'Identification', description: 'Nom, code, type et statut', icon: Building2, fields: ['name', 'code_cite', 'type_cite', 'statut'] },
    {
        label: 'Caractéristiques',
        description: 'Capacité, bâtiments, superficie',
        icon: Ruler,
        fields: ['capacite_max', 'nombre_batiments', 'superficie', 'annee_construction'],
    },
    {
        label: 'Contacts',
        description: 'Responsable, téléphones, e-mails',
        icon: Phone,
        fields: ['responsable_id', 'telephone1', 'telephone2', 'telephone3', 'email1', 'email2', 'reglement_id'],
    },
    { label: 'Localisation', description: 'Adresse et position GPS', icon: MapPin, fields: ['adresse', 'latitude', 'longitude'] },
    { label: 'Services et plan', description: 'Services proposés, plan', icon: Wifi, fields: ['wifi_gratuit', 'salle_etude', 'infirmerie', 'restaurant'] },
    { label: 'Vérification', description: 'Relire avant d’enregistrer', icon: ClipboardCheck, fields: [] },
];

const SERVICES = [
    { name: 'wifi_gratuit', label: 'Wi-Fi gratuit', icon: <Wifi /> },
    { name: 'salle_etude', label: "Salle d'étude", icon: <BookOpen /> },
    { name: 'infirmerie', label: 'Infirmerie', icon: <HeartPulse /> },
    { name: 'restaurant', label: 'Salle de restauration', icon: <Utensils /> },
] as const;

/** Téléphone enregistré → affichage par paires (l'indicatif +225 éventuel est retiré). */
const telephone = (valeur: string | null) => formatTelephone(valeur);

const valeursInitiales = (item: CiteItem | null): FormInput => ({
    name: texte(item?.name),
    code_cite: texte(item?.code_cite),
    type_cite: texte(item?.type_cite),
    statut: (['ouvert', 'ferme', 'en_renovation', 'en_construction'].includes(texte(item?.statut)) ? item?.statut : 'ouvert') as FormInput['statut'],
    capacite_max: item ? texte(item.capacite_max ?? 0) : '',
    nombre_batiments: texte(item?.nombre_batiments),
    superficie: item?.superficie ? String(Number(item.superficie)) : '',
    annee_construction: texte(item?.annee_construction),
    responsable_id: texte(item?.responsable_id),
    telephone1: telephone(item?.telephone1 ?? null),
    telephone2: telephone(item?.telephone2 ?? null),
    telephone3: telephone(item?.telephone3 ?? null),
    email1: texte(item?.email1),
    email2: texte(item?.email2),
    reglement_id: texte(item?.reglement_id),
    adresse: texte(item?.adresse),
    latitude: texte(item?.latitude),
    longitude: texte(item?.longitude),
    wifi_gratuit: item?.wifi_gratuit ?? false,
    salle_etude: item?.salle_etude ?? false,
    infirmerie: item?.infirmerie ?? false,
    restaurant: item?.restaurant ?? false,
});

type CiteFormProps = {
    item: CiteItem | null;
    campusId: string;
    typesCite: Option[];
    users: UserOption[];
    reglements: Option[];
    submitUrl: string | null;
    cancelUrl: string;
};

export function CiteForm({ item, campusId, typesCite, users, reglements, submitUrl, cancelUrl }: CiteFormProps) {
    const isEdit = item !== null;
    const { errors: serverErrors } = usePage<SharedProps>().props;
    const wizard = useWizard(STEPS.length, { toutesAccessibles: isEdit });
    const [submitting, setSubmitting] = useState(false);
    const [plan, setPlan] = useState<File | null>(null);
    const [planError, setPlanError] = useState<string | null>(null);
    const [removePlan, setRemovePlan] = useState(false);
    const [carteOuverte, setCarteOuverte] = useState(false);
    const [geocodage, setGeocodage] = useState(false);
    const defaultValues = useMemo(() => valeursInitiales(item), [item]);

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

    // Erreurs Laravel : affichées sous les champs et retour à la première étape concernée.
    useEffect(() => {
        const champs = Object.keys(serverErrors);
        if (!champs.length) return;
        let premiere = STEPS.length - 1;
        champs.forEach((champ) => {
            const index = STEPS.findIndex((step) => step.fields.includes(champ as Champ));
            if (index >= 0) {
                setError(champ as Champ, { message: serverErrors[champ] });
                premiere = Math.min(premiere, index);
            }
        });
        if (serverErrors.plan) {
            setPlanError(serverErrors.plan);
            premiere = Math.min(premiere, 4);
        }
        wizard.goTo(premiere);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [serverErrors]);

    const validerEtape = async (index: number) => {
        const ok = await trigger(STEPS[index].fields, { shouldFocus: true });
        if (index === 4) {
            const erreur = erreurPlan(plan, PLAN_MAX_MO);
            setPlanError(erreur);
            return ok && !erreur;
        }
        return ok;
    };

    /** Aller à une étape plus loin impose de valider chacune des étapes intermédiaires. */
    const allerA = async (cible: number) => {
        for (let index = wizard.current; index < cible; index++) {
            if (!(await validerEtape(index))) {
                wizard.goTo(index);
                return;
            }
        }
        wizard.goTo(cible);
    };

    const envoyer = (values: FormOutput) => {
        if (!submitUrl) return;
        const data: Record<string, string | File> = {
            campus_id: campusId,
            type: 'cite',
            name: values.name,
            code_cite: values.code_cite,
            type_cite: values.type_cite,
            statut: values.statut,
            capacite_max: values.capacite_max,
            nombre_batiments: values.nombre_batiments,
            superficie: values.superficie.replace(',', '.'),
            annee_construction: values.annee_construction,
            responsable_id: values.responsable_id,
            telephone1: values.telephone1,
            telephone2: values.telephone2,
            telephone3: values.telephone3,
            email1: values.email1,
            email2: values.email2,
            reglement_id: values.reglement_id,
            adresse: values.adresse,
            latitude: values.latitude.replace(',', '.'),
            longitude: values.longitude.replace(',', '.'),
        };
        SERVICES.forEach(({ name }) => {
            if (values[name]) data[name] = '1';
        });
        if (plan) data.plan = plan;
        if (isEdit) {
            data._method = 'PUT';
            data.code = item.id;
            if (removePlan) data.remove_plan = '1';
        }
        router.post(submitUrl, data, {
            forceFormData: true,
            // Erreurs : la saisie est conservée. Succès : formulaire vierge (création) ou retour à la liste (modification).
            preserveState: 'errors',
            preserveScroll: true,
            onStart: () => setSubmitting(true),
            onFinish: () => setSubmitting(false),
        });
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
        if (await validerEtape(wizard.current)) wizard.next();
    };

    const responsable = users.find((user) => user.id === watch('responsable_id')) ?? null;

    /** Reprend les e-mails du responsable dans les champs encore vides ; les téléphones de la cité restent à saisir. */
    const choisirResponsable = (id: string) => {
        setValue('responsable_id', id, { shouldDirty: true });
        const user = users.find((u) => u.id === id);
        if (!user) return;
        const remplir = (champ: 'email1' | 'email2', valeur: string | null) => {
            if (valeur && !getValues(champ)) setValue(champ, valeur, { shouldDirty: true });
        };
        remplir('email1', user.email);
        remplir('email2', user.email2);
    };

    const chercherAdresse = async () => {
        const adresse = getValues('adresse').trim();
        if (!adresse) {
            setError('adresse', { message: "Saisissez l'adresse pour rechercher ses coordonnées." });
            return;
        }
        setGeocodage(true);
        try {
            const resultat = await geocoder(adresse);
            if (resultat) {
                setValue('latitude', resultat.lat.toFixed(6), { shouldValidate: true, shouldDirty: true });
                setValue('longitude', resultat.lng.toFixed(6), { shouldValidate: true, shouldDirty: true });
                toast.success('Coordonnées trouvées.');
            } else {
                toast.error('Adresse introuvable : précisez-la ou placez le point sur la carte.');
            }
        } catch {
            toast.error('La recherche d’adresse a échoué. Placez le point sur la carte.');
        } finally {
            setGeocodage(false);
        }
    };

    const nomType = (id: string) => typesCite.find((t) => t.id === id)?.name ?? '';
    const values = watch();
    const unknownError = serverErrors.error;
    const describedBy = (champ: Champ) => (errors[champ] ? `${champ}-error` : undefined);

    return (
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
                cancelUrl={cancelUrl}
                submitting={submitting}
                submitLabel={isEdit ? 'Enregistrer les modifications' : 'Enregistrer la cité'}
                submitDisabled={!submitUrl}
            >
                {unknownError && <FormAlert>{unknownError}</FormAlert>}
                {!submitUrl && <FormAlert>Vous n’avez pas le droit d’enregistrer cette cité.</FormAlert>}

                {wizard.current === 0 && (
                    <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                        <Field id="name" label="Nom de la cité" required error={errors.name?.message}>
                            <Input id="name" autoFocus placeholder="Ex : Cité Kalilou" invalid={!!errors.name} aria-describedby={describedBy('name')} {...register('name')} />
                        </Field>
                        <Field id="code_cite" label="Code de la cité" required error={errors.code_cite?.message} hint="Identifiant court et unique.">
                            <Input
                                id="code_cite"
                                placeholder="Ex : CIT-012"
                                invalid={!!errors.code_cite}
                                aria-describedby={describedBy('code_cite')}
                                {...register('code_cite')}
                            />
                        </Field>
                        <Field id="type_cite" label="Type de cité" required error={errors.type_cite?.message} hint="Détermine le public accueilli (mixte, hommes, femmes).">
                            <Controller
                                control={control}
                                name="type_cite"
                                render={({ field }) => (
                                    <Select value={field.value} onValueChange={field.onChange} onOpenChange={(open) => !open && field.onBlur()}>
                                        <SelectTrigger id="type_cite" ref={field.ref} invalid={!!errors.type_cite} aria-describedby={describedBy('type_cite')}>
                                            <SelectValue placeholder="Sélectionnez un type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {typesCite.map((type) => (
                                                <SelectItem key={type.id} value={type.id}>
                                                    {type.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                        </Field>
                        <Field id="statut" label="Statut" required error={errors.statut?.message}>
                            <Controller
                                control={control}
                                name="statut"
                                render={({ field }) => (
                                    <Select value={field.value} onValueChange={field.onChange}>
                                        <SelectTrigger id="statut" ref={field.ref}>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(['ouvert', 'ferme', 'en_renovation', 'en_construction'] as const).map((statut) => (
                                                <SelectItem key={statut} value={statut}>
                                                    {STATUTS[statut]}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                        </Field>
                    </div>
                )}

                {wizard.current === 1 && (
                    <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                        <Field id="capacite_max" label="Capacité estimée (étudiants)" required error={errors.capacite_max?.message}>
                            <Controller
                                control={control}
                                name="capacite_max"
                                render={({ field }) => (
                                    <Counter
                                        id="capacite_max"
                                        label="capacité"
                                        value={field.value}
                                        onChange={field.onChange}
                                        onBlur={field.onBlur}
                                        max={100000}
                                        invalid={!!errors.capacite_max}
                                        describedBy={describedBy('capacite_max')}
                                    />
                                )}
                            />
                        </Field>
                        <Field id="nombre_batiments" label="Nombre de bâtiments prévus" error={errors.nombre_batiments?.message}>
                            <Controller
                                control={control}
                                name="nombre_batiments"
                                render={({ field }) => (
                                    <Counter
                                        id="nombre_batiments"
                                        label="nombre de bâtiments"
                                        value={field.value}
                                        onChange={field.onChange}
                                        onBlur={field.onBlur}
                                        max={1000}
                                        invalid={!!errors.nombre_batiments}
                                        describedBy={describedBy('nombre_batiments')}
                                    />
                                )}
                            />
                        </Field>
                        <Field id="superficie" label="Superficie (m²)" error={errors.superficie?.message}>
                            <Input
                                id="superficie"
                                inputMode="decimal"
                                placeholder="Ex : 1500,50"
                                invalid={!!errors.superficie}
                                aria-describedby={describedBy('superficie')}
                                {...register('superficie')}
                            />
                        </Field>
                        <Field id="annee_construction" label="Année de construction" error={errors.annee_construction?.message}>
                            <Input
                                id="annee_construction"
                                inputMode="numeric"
                                maxLength={4}
                                placeholder={`Ex : ${ANNEE - 5}`}
                                invalid={!!errors.annee_construction}
                                aria-describedby={describedBy('annee_construction')}
                                {...register('annee_construction')}
                            />
                        </Field>
                    </div>
                )}

                {wizard.current === 2 && (
                    <>
                        <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                            <Field id="responsable_id" label="Responsable de la cité" error={errors.responsable_id?.message} hint="Ses coordonnées complètent les champs vides.">
                                <Select value={values.responsable_id || undefined} onValueChange={choisirResponsable}>
                                    <SelectTrigger id="responsable_id">
                                        <SelectValue placeholder="Sélectionnez le responsable" />
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
                            <Field id="reglement_id" label="Règlement intérieur" error={errors.reglement_id?.message}>
                                <Select value={values.reglement_id || undefined} onValueChange={(id) => setValue('reglement_id', id, { shouldDirty: true })}>
                                    <SelectTrigger id="reglement_id">
                                        <SelectValue placeholder={reglements.length ? 'Sélectionnez le règlement' : 'Aucun règlement enregistré'} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {reglements.map((reglement) => (
                                            <SelectItem key={reglement.id} value={reglement.id}>
                                                {reglement.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>
                        </div>
                        {responsable && (
                            <div className="rounded-brand border border-secondary/40 bg-soft/60 px-4 py-3 text-sm">
                                <p className="font-medium text-gray-900">
                                    {responsable.name}
                                    {responsable.matricule && <span className="font-normal text-muted"> · {responsable.matricule}</span>}
                                </p>
                                <p className="text-muted">
                                    {responsable.telephone || 'Téléphone non renseigné'} · {responsable.email || 'E-mail non renseigné'}
                                </p>
                            </div>
                        )}
                        <SectionTitle hint="Numéros ivoiriens à 10 chiffres.">Téléphones de la cité</SectionTitle>
                        <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-3">
                            {(['telephone1', 'telephone2', 'telephone3'] as const).map((champ, index) => (
                                <Field key={champ} id={champ} label={`Téléphone ${index + 1}`} error={errors[champ]?.message}>
                                    <Controller
                                        control={control}
                                        name={champ}
                                        render={({ field }) => (
                                            <Input
                                                id={champ}
                                                ref={field.ref}
                                                value={field.value}
                                                onBlur={field.onBlur}
                                                onChange={(event) => field.onChange(formatTelephone(event.target.value))}
                                                type="tel"
                                                autoComplete="off"
                                                inputMode="numeric"
                                                maxLength={14}
                                                placeholder="07 00 00 00 00"
                                                className="tabular-nums"
                                                invalid={!!errors[champ]}
                                                aria-describedby={describedBy(champ)}
                                            />
                                        )}
                                    />
                                </Field>
                            ))}
                        </div>
                        <SectionTitle>E-mails de la cité</SectionTitle>
                        <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                            <Field id="email1" label="E-mail principal" error={errors.email1?.message}>
                                <Input id="email1" type="email" placeholder="cite@exemple.ci" invalid={!!errors.email1} aria-describedby={describedBy('email1')} {...register('email1')} />
                            </Field>
                            <Field id="email2" label="E-mail secondaire" error={errors.email2?.message}>
                                <Input id="email2" type="email" placeholder="contact@exemple.ci" invalid={!!errors.email2} aria-describedby={describedBy('email2')} {...register('email2')} />
                            </Field>
                        </div>
                    </>
                )}

                {wizard.current === 3 && (
                    <>
                        <Field id="adresse" label="Adresse géographique" required error={errors.adresse?.message}>
                            <div className="flex flex-col gap-2 sm:flex-row">
                                <div className="flex-1">
                                    <Input
                                        id="adresse"
                                        placeholder="Ex : Quartier Résidentiel, Bondoukou"
                                        icon={<MapPin />}
                                        invalid={!!errors.adresse}
                                        aria-describedby={describedBy('adresse')}
                                        {...register('adresse')}
                                    />
                                </div>
                                <Button variant="secondary" className="h-11" onClick={() => void chercherAdresse()} disabled={geocodage}>
                                    {geocodage ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <LocateFixed className="size-4" aria-hidden />}
                                    Trouver les coordonnées
                                </Button>
                            </div>
                        </Field>
                        <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                            <Field id="latitude" label="Latitude" required error={errors.latitude?.message}>
                                <Input id="latitude" inputMode="decimal" placeholder="Ex : 8.040200" className="tabular-nums" invalid={!!errors.latitude} aria-describedby={describedBy('latitude')} {...register('latitude')} />
                            </Field>
                            <Field id="longitude" label="Longitude" required error={errors.longitude?.message}>
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
                            Placer la cité sur la carte
                        </Button>
                        <PositionPicker
                            open={carteOuverte}
                            onOpenChange={setCarteOuverte}
                            titre="Position de la cité"
                            initial={(() => {
                                const lat = lireCoordonnee(values.latitude);
                                const lng = lireCoordonnee(values.longitude);
                                return lat !== null && lng !== null ? { lat, lng } : null;
                            })()}
                            onConfirm={({ lat, lng }) => {
                                setValue('latitude', lat.toFixed(6), { shouldValidate: true, shouldDirty: true });
                                setValue('longitude', lng.toFixed(6), { shouldValidate: true, shouldDirty: true });
                            }}
                        />
                    </>
                )}

                {wizard.current === 4 && (
                    <>
                        <SectionTitle>Services disponibles</SectionTitle>
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            {SERVICES.map((service) => (
                                <Controller
                                    key={service.name}
                                    control={control}
                                    name={service.name}
                                    render={({ field }) => <ToggleCard checked={field.value} onChange={field.onChange} label={service.label} icon={service.icon} />}
                                />
                            ))}
                        </div>
                        <SectionTitle hint="Facultatif.">Plan de la cité</SectionTitle>
                        <PlanUpload
                            id="plan"
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
                    </>
                )}

                {wizard.current === 5 && (
                    <div className="space-y-4">
                        <RecapSection
                            title="Identification"
                            onEdit={() => wizard.goTo(0)}
                            rows={[
                                ['Nom', values.name],
                                ['Code', values.code_cite],
                                ['Type', nomType(values.type_cite)],
                                ['Statut', STATUTS[values.statut]],
                            ]}
                        />
                        <RecapSection
                            title="Caractéristiques"
                            onEdit={() => wizard.goTo(1)}
                            rows={[
                                ['Capacité', values.capacite_max ? `${values.capacite_max} étudiants` : ''],
                                ['Bâtiments prévus', values.nombre_batiments],
                                ['Superficie', values.superficie ? `${values.superficie} m²` : ''],
                                ['Année de construction', values.annee_construction],
                            ]}
                        />
                        <RecapSection
                            title="Contacts"
                            onEdit={() => wizard.goTo(2)}
                            rows={[
                                ['Responsable', responsable?.name],
                                ['Règlement intérieur', reglements.find((r) => r.id === values.reglement_id)?.name],
                                ['Téléphones', [values.telephone1, values.telephone2, values.telephone3].filter(Boolean).join(' · ')],
                                ['E-mails', [values.email1, values.email2].filter(Boolean).join(' · ')],
                            ]}
                        />
                        <RecapSection
                            title="Localisation"
                            onEdit={() => wizard.goTo(3)}
                            rows={[
                                ['Adresse', values.adresse],
                                ['Coordonnées GPS', values.latitude && values.longitude ? `${values.latitude}, ${values.longitude}` : ''],
                            ]}
                        />
                        <RecapSection
                            title="Services et plan"
                            onEdit={() => wizard.goTo(4)}
                            rows={[
                                ['Services', SERVICES.filter((s) => values[s.name]).map((s) => s.label).join(', ') || 'Aucun'],
                                ['Plan', plan ? plan.name : removePlan ? 'Plan actuel supprimé' : item?.plan ? 'Plan actuel conservé' : 'Aucun'],
                            ]}
                        />
                    </div>
                )}
            </Wizard>
        </form>
    );
}
