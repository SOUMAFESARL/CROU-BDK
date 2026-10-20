import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import {
    Activity,
    ArrowLeftRight,
    Banknote,
    Bell,
    Building2,
    CircleAlert,
    Coins,
    CreditCard,
    ExternalLink,
    Globe,
    Info,
    KeyRound,
    Landmark,
    LockKeyhole,
    Mail,
    MessageSquareText,
    Palette,
    Percent,
    Receipt,
    Settings,
    ShieldCheck,
    Smartphone,
    UserLock,
    Wallet,
    Wrench,
} from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import type { RequestPayload } from '@inertiajs/core';
import { Controller, useForm, type Control, type DefaultValues, type FieldValues, type Path, type Resolver } from 'react-hook-form';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { ErreurGenerale, Note, PiedAssistant, Recap, useAssistant } from '@/components/parametrage-hebergement/shared';
import { ToggleCard } from '@/components/parc-immobilier/form/inputs';
import { repartirErreurs } from '@/components/vie-residentielle/shared';
import { Badge } from '@/components/ui/badge';
import { Field } from '@/components/ui/field';
import { FileDrop } from '@/components/ui/file-drop';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { StatCard } from '@/components/ui/stat-card';
import { Stepper } from '@/components/ui/stepper';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { envoyer } from '@/lib/inertia-form';
import { montant, symboleDevise } from '@/lib/systeme';
import {
    AFFICHAGES_PRIX,
    DEVISES,
    DEVISES_SECONDAIRES,
    EVENEMENTS,
    FORMATS_DATE,
    FREQUENCES,
    MODES_PAIEMENT,
    POSITIONS_SYMBOLE,
    SEPARATEURS,
    FOURNISSEURS_SMS,
    FUSEAUX,
    LOCALES,
    PAYS,
    PILOTES_MAIL,
    devisesSchema,
    generalSchema,
    notificationsSchema,
    paiementsSchema,
    profilSchema,
    securiteSchema,
    taxesSchema,
    type DevisesValeurs,
    type GeneralValeurs,
    type NotificationsValeurs,
    type PaiementsValeurs,
    type ProfilValeurs,
    type SecuriteValeurs,
    type TaxesValeurs,
} from '@/schemas/configuration';

type Section = 'configuration' | 'profil' | 'notifications' | 'paiements' | 'taxes' | 'devises' | 'securite';
/** Mot de passe SMTP et clé API SMS déjà enregistrés (jamais envoyés au navigateur). */
type Secrets = { smtp: boolean; sms: boolean };

type Props = {
    section: Section;
    general: GeneralValeurs;
    profil: Omit<ProfilValeurs, 'logo'> & { logo: string | null };
    notifications: Omit<NotificationsValeurs, 'mail_password' | 'sms_api_key'>;
    paiements: PaiementsValeurs;
    taxes: TaxesValeurs;
    devises: DevisesValeurs;
    securite: SecuriteValeurs;
    /** Adresse IP et protocole de l'administrateur connecté (garde-fous de la section Sécurité). */
    contexte: { ip: string; https: boolean };
    secrets: Secrets;
    urls: { sections: Record<Section, string>; update: Record<Section, string> | null; apiPaiement: string | null };
};

const SECTIONS: { cle: Section; label: string; icone: ReactNode }[] = [
    { cle: 'configuration', label: 'Général', icone: <Settings {...ICON} /> },
    { cle: 'profil', label: 'Profil du CROU', icone: <Building2 {...ICON} /> },
    { cle: 'notifications', label: 'Notifications', icone: <Bell {...ICON} /> },
    { cle: 'paiements', label: 'Paiements', icone: <Wallet {...ICON} /> },
    { cle: 'taxes', label: 'Taxes & TVA', icone: <Receipt {...ICON} /> },
    { cle: 'devises', label: 'Devises', icone: <Coins {...ICON} /> },
    { cle: 'securite', label: 'Sécurité', icone: <ShieldCheck {...ICON} /> },
];

/**
 * Paramètres de l'application (remplace `parametrage/configurations/index.blade.php`) : général, profil, notifications,
 * paiements, taxes, devises et sécurité ; action `configuration.update` inchangée.
 */
export default function Configuration({ section, general, profil, notifications, paiements, taxes, devises, securite, contexte, secrets, urls }: Props) {
    const url = urls.update?.[section] ?? null;

    return (
        <AdminLayout title="Configuration">
            <PageHeader
                title="Configuration de l’application"
                description="Identité du CROU, coordonnées, logo, notifications, paiements, taxes, devises et sécurité : appliqués à toute l’application."
            />

            <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    tone={general.maintenance_mode ? 'amber' : 'green'}
                    label="Mode maintenance"
                    value={general.maintenance_mode ? 'Activé' : 'Désactivé'}
                    highlight={general.maintenance_mode}
                />
                <StatCard tone={general.activity_log ? 'blue' : 'neutral'} label="Journal d’activité" value={general.activity_log ? 'Activé' : 'Désactivé'} />
                <StatCard tone={notifications.email_enabled ? 'green' : 'neutral'} label="Notifications e-mail" value={notifications.email_enabled ? 'Activées' : 'Désactivées'} />
                <StatCard tone={notifications.sms_enabled ? 'green' : 'neutral'} label="Notifications SMS" value={notifications.sms_enabled ? 'Activées' : 'Désactivées'} />
            </div>

            <Tabs value={section} onValueChange={(cle) => router.get(urls.sections[cle as Section], {}, { preserveScroll: true, replace: true })}>
                <TabsList className="max-w-full overflow-x-auto">
                    {SECTIONS.map((s) => (
                        <TabsTrigger key={s.cle} value={s.cle}>
                            {s.icone} {s.label}
                        </TabsTrigger>
                    ))}
                </TabsList>
            </Tabs>

            <div className="mt-4">
                {!url && (
                    <div className="mb-4">
                        <Note icone={<Info />}>
                            <p>Vous pouvez consulter ces paramètres mais pas les modifier.</p>
                        </Note>
                    </div>
                )}
                {section === 'configuration' && <FormulaireGeneral key="configuration" valeurs={general} url={url} />}
                {section === 'profil' && <FormulaireProfil key="profil" valeurs={profil} url={url} />}
                {section === 'notifications' && <FormulaireNotifications key="notifications" valeurs={notifications} secrets={secrets} url={url} />}
                {section === 'paiements' && <FormulairePaiements key="paiements" valeurs={paiements} url={url} apiPaiement={urls.apiPaiement} />}
                {section === 'taxes' && <FormulaireTaxes key="taxes" valeurs={taxes} url={url} />}
                {section === 'devises' && <FormulaireDevises key="devises" valeurs={devises} url={url} />}
                {section === 'securite' && <FormulaireSecurite key="securite" valeurs={securite} contexte={contexte} url={url} />}
            </div>
        </AdminLayout>
    );
}

// ─── Outils communs ───────────────────────────────────────────────────────────

/** Choix d'une liste ; une valeur enregistrée absente de la liste reste proposée. */
const choix = (liste: Record<string, string> | readonly string[], actuelle: string) => {
    const entrees = Array.isArray(liste) ? liste.map((v) => [v, v] as const) : Object.entries(liste);
    return actuelle && !entrees.some(([v]) => v === actuelle) ? [[actuelle, actuelle] as const, ...entrees] : entrees;
};

const libelle = (liste: Record<string, string>, valeur: string) => liste[valeur] ?? valeur ?? '—';

const etat = (actif: boolean, feminin = false) =>
    actif ? <Badge variant="success">{feminin ? 'Activée' : 'Activé'}</Badge> : <Badge variant="neutral">{feminin ? 'Désactivée' : 'Désactivé'}</Badge>;

function ChampListe<T extends FieldValues>({
    control,
    name,
    id,
    label,
    options,
    error,
    hint,
    required,
}: {
    control: Control<T>;
    name: Path<T>;
    id: string;
    label: string;
    options: (readonly [string, string])[];
    error?: string;
    hint?: string;
    required?: boolean;
}) {
    return (
        <Controller
            control={control}
            name={name}
            render={({ field }) => (
                <Field id={id} label={label} error={error} hint={hint} required={required}>
                    <Select value={field.value || undefined} onValueChange={field.onChange} onOpenChange={(ouvert) => !ouvert && field.onBlur()}>
                        <SelectTrigger id={id} invalid={!!error}>
                            <SelectValue placeholder="Sélectionner…" />
                        </SelectTrigger>
                        <SelectContent>
                            {options.map(([valeur, texte]) => (
                                <SelectItem key={valeur} value={valeur}>
                                    {texte}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>
            )}
        />
    );
}

function Interrupteur<T extends FieldValues>({
    control,
    name,
    label,
    description,
    icone,
    onChange,
}: {
    control: Control<T>;
    name: Path<T>;
    label: string;
    description?: string;
    icone?: ReactNode;
    onChange?: (valeur: boolean) => void;
}) {
    return (
        <Controller
            control={control}
            name={name}
            render={({ field }) => (
                <ToggleCard
                    checked={!!field.value}
                    onChange={(valeur) => {
                        field.onChange(valeur);
                        onChange?.(valeur);
                    }}
                    label={label}
                    description={description}
                    icon={icone}
                />
            )}
        />
    );
}

/** Cadre commun d'un assistant : étapes, contenu, pied (Annuler / Précédent / Suivant ou Enregistrer). */
function Assistant({
    etapes,
    etape,
    atteinte,
    allerA,
    onSubmit,
    erreur,
    incomplet,
    children,
    pied,
}: {
    etapes: string[];
    etape: number;
    atteinte: number;
    allerA: (index: number) => Promise<boolean>;
    onSubmit: () => Promise<void>;
    erreur: string | null;
    incomplet: boolean;
    children: ReactNode;
    pied: ReactNode;
}) {
    const derniere = etape === etapes.length - 1;
    return (
        <form
            noValidate
            className="flex min-w-0 flex-col overflow-hidden rounded-brand border bg-white"
            onSubmit={(event) => {
                event.preventDefault();
                void onSubmit();
            }}
        >
            <Stepper steps={etapes} current={etape} reached={atteinte} onStepClick={(index) => void allerA(index)} className="overflow-x-auto" />
            <div className="space-y-4 px-6 py-5">
                <ErreurGenerale message={erreur} />
                {children}
                {derniere && incomplet && (
                    <p role="alert" className="flex items-center gap-2 text-sm font-medium text-red-600">
                        <CircleAlert className="size-4" aria-hidden /> Certaines informations sont incomplètes : revenez aux étapes précédentes.
                    </p>
                )}
            </div>
            {pied}
        </form>
    );
}

function Titre({ icone, children, description }: { icone: ReactNode; children: ReactNode; description?: string }) {
    return (
        <div>
            <h2 className="flex items-center gap-2 text-sm font-medium text-gray-900">
                <span className="text-primary" aria-hidden>
                    {icone}
                </span>
                {children}
            </h2>
            {description && <p className="mt-0.5 text-sm text-muted">{description}</p>}
        </div>
    );
}

// ─── Général ──────────────────────────────────────────────────────────────────

const ETAPES_GENERAL = ['Identité', 'Fonctionnalités', 'Vérification'];
const CHAMPS_GENERAL = [['app_name', 'company_name', 'locale', 'timezone', 'date_format'], ['maintenance_mode', 'activity_log'], []] as const;

function FormulaireGeneral({ valeurs, url }: { valeurs: GeneralValeurs; url: string | null }) {
    const initiales = useMemo<GeneralValeurs>(
        () => ({ ...valeurs, locale: valeurs.locale in LOCALES ? valeurs.locale : 'fr', date_format: valeurs.date_format in FORMATS_DATE ? valeurs.date_format : 'd/m/Y' }),
        [valeurs],
    );
    const [erreur, setErreur] = useState<string | null>(null);
    const {
        register,
        control,
        trigger,
        watch,
        reset,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<GeneralValeurs>({ resolver: zodResolver(generalSchema), mode: 'onTouched', defaultValues: initiales });
    const { etape, atteinte, aller, allerA, toutValider, derniere } = useAssistant<GeneralValeurs>(CHAMPS_GENERAL, trigger, true);
    const v = watch();
    const lecture = !url;

    const enregistrer = (donnees: GeneralValeurs) =>
        envoyer<GeneralValeurs>(
            url!,
            { ...donnees, app_name: donnees.app_name.trim(), company_name: donnees.company_name.trim(), maintenance_mode: donnees.maintenance_mode ? 1 : 0, activity_log: donnees.activity_log ? 1 : 0 },
            { method: 'put', onError: (e) => setErreur(repartirErreurs(e, CHAMPS_GENERAL, (champ, err) => setError(champ, err), aller)) },
        );

    const recap = (
        <Recap
            lignes={[
                { label: 'Nom du CROU', valeur: <span className="font-medium">{v.app_name || '—'}</span> },
                { label: 'Nom complet', valeur: v.company_name || '—' },
                { label: 'Langue par défaut', valeur: libelle(LOCALES, v.locale) },
                { label: 'Fuseau horaire', valeur: libelle(FUSEAUX, v.timezone) },
                { label: 'Format de date', valeur: libelle(FORMATS_DATE, v.date_format) },
                { label: 'Mode maintenance', valeur: etat(v.maintenance_mode) },
                { label: 'Journal d’activité', valeur: etat(v.activity_log) },
            ]}
        />
    );

    if (lecture) return recap;

    return (
        <Assistant
            etapes={ETAPES_GENERAL}
            etape={etape}
            atteinte={atteinte}
            allerA={allerA}
            erreur={erreur}
            incomplet={Object.keys(errors).length > 0}
            onSubmit={async () => {
                setErreur(null);
                if (!derniere) await allerA(etape + 1);
                else if (await toutValider()) await handleSubmit(enregistrer)();
            }}
            pied={
                <PiedAssistant
                    etape={etape}
                    derniere={derniere}
                    envoi={isSubmitting}
                    onAnnuler={() => {
                        reset(initiales);
                        aller(0);
                    }}
                    onPrecedent={() => aller(etape - 1)}
                    libelle="Enregistrer les modifications"
                />
            }
        >
            {etape === 0 && (
                <>
                    <Titre icone={<Building2 {...ICON} />} description="Identité et présentation de l’application.">
                        Informations générales
                    </Titre>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field id="cfg-app" label="Nom du CROU" required error={errors.app_name?.message} hint="Affiché dans l’en-tête.">
                            <Input id="cfg-app" maxLength={100} placeholder="Ex. CROU Bondoukou" invalid={!!errors.app_name} autoFocus {...register('app_name')} />
                        </Field>
                        <Field id="cfg-company" label="Nom complet du CROU" required error={errors.company_name?.message} hint="Affiché en bas des pages de connexion.">
                            <Input
                                id="cfg-company"
                                maxLength={255}
                                placeholder="Centre Régional des Œuvres Universitaires de Bondoukou"
                                invalid={!!errors.company_name}
                                {...register('company_name')}
                            />
                        </Field>
                    </div>
                    <div className="grid gap-4 md:grid-cols-3">
                        <ChampListe control={control} name="locale" id="cfg-locale" label="Langue par défaut" required options={choix(LOCALES, '')} error={errors.locale?.message} />
                        <ChampListe
                            control={control}
                            name="timezone"
                            id="cfg-tz"
                            label="Fuseau horaire"
                            required
                            options={choix(FUSEAUX, initiales.timezone)}
                            error={errors.timezone?.message}
                            hint="Horaires et rapports."
                        />
                        <ChampListe control={control} name="date_format" id="cfg-date" label="Format de date" required options={choix(FORMATS_DATE, '')} error={errors.date_format?.message} />
                    </div>
                </>
            )}
            {etape === 1 && (
                <>
                    <Titre icone={<Wrench {...ICON} />} description="Activer ou désactiver des fonctions de l’application.">
                        Fonctionnalités
                    </Titre>
                    <Interrupteur control={control} name="maintenance_mode" label="Mode maintenance" description="Affiche une page de maintenance aux utilisateurs." icone={<Wrench />} />
                    <Interrupteur control={control} name="activity_log" label="Journal d’activité" description="Enregistre les actions des utilisateurs." icone={<Activity />} />
                    {v.maintenance_mode && (
                        <Note icone={<CircleAlert />} ton="attention">
                            <p>En mode maintenance, les étudiants et le public n’ont plus accès à l’application tant que le mode n’est pas désactivé.</p>
                        </Note>
                    )}
                </>
            )}
            {etape === 2 && recap}
        </Assistant>
    );
}

// ─── Profil ───────────────────────────────────────────────────────────────────

const ETAPES_PROFIL = ['Coordonnées', 'Identité visuelle', 'Vérification'];
const CHAMPS_PROFIL = [['address', 'country', 'phone', 'email', 'website'], ['logo', 'brand_color'], []] as const;

function FormulaireProfil({ valeurs, url }: { valeurs: Props['profil']; url: string | null }) {
    const initiales = useMemo<ProfilValeurs>(() => ({ ...valeurs, logo: null }), [valeurs]);
    const [erreur, setErreur] = useState<string | null>(null);
    const {
        register,
        control,
        trigger,
        watch,
        reset,
        setValue,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<ProfilValeurs>({ resolver: zodResolver(profilSchema), mode: 'onTouched', defaultValues: initiales });
    const { etape, atteinte, aller, allerA, toutValider, derniere } = useAssistant<ProfilValeurs>(CHAMPS_PROFIL, trigger, true);
    const v = watch();

    const enregistrer = (donnees: ProfilValeurs) => {
        const { logo, ...reste } = donnees;
        return envoyer<ProfilValeurs>(
            url!,
            { ...Object.fromEntries(Object.entries(reste).map(([cle, valeur]) => [cle, String(valeur).trim()])), ...(logo ? { logo } : {}) },
            {
                method: 'put',
                forceFormData: true,
                onSuccess: () => setValue('logo', null),
                onError: (e) => setErreur(repartirErreurs(e, CHAMPS_PROFIL, (champ, err) => setError(champ, err), aller)),
            },
        );
    };

    const recap = (
        <Recap
            lignes={[
                { label: 'Adresse', valeur: v.address || '—' },
                { label: 'Pays', valeur: v.country || '—' },
                { label: 'Téléphone', valeur: v.phone || '—' },
                { label: 'E-mail de contact', valeur: v.email || '—' },
                { label: 'Site web', valeur: v.website || '—' },
                {
                    label: 'Logo',
                    valeur: v.logo ? (
                        v.logo.name
                    ) : valeurs.logo ? (
                        <img src={valeurs.logo} alt="Logo actuel" className="ml-auto size-12 rounded-brand border object-contain" />
                    ) : (
                        <span className="text-muted">Logo par défaut</span>
                    ),
                },
                {
                    label: 'Couleur principale',
                    valeur: (
                        <span className="inline-flex items-center gap-2">
                            <span className="size-4 rounded-sm border" style={{ backgroundColor: v.brand_color }} aria-hidden />
                            {v.brand_color.toUpperCase()}
                        </span>
                    ),
                },
            ]}
        />
    );

    if (!url) return recap;

    return (
        <Assistant
            etapes={ETAPES_PROFIL}
            etape={etape}
            atteinte={atteinte}
            allerA={allerA}
            erreur={erreur}
            incomplet={Object.keys(errors).length > 0}
            onSubmit={async () => {
                setErreur(null);
                if (!derniere) await allerA(etape + 1);
                else if (await toutValider()) await handleSubmit(enregistrer)();
            }}
            pied={
                <PiedAssistant
                    etape={etape}
                    derniere={derniere}
                    envoi={isSubmitting}
                    onAnnuler={() => {
                        reset(initiales);
                        aller(0);
                    }}
                    onPrecedent={() => aller(etape - 1)}
                    libelle="Enregistrer les modifications"
                />
            }
        >
            {etape === 0 && (
                <>
                    <Titre icone={<Building2 {...ICON} />} description="Adresse et contacts officiels du CROU.">
                        Coordonnées
                    </Titre>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field id="cfg-address" label="Adresse" error={errors.address?.message} hint="Siège social.">
                            <Input id="cfg-address" maxLength={255} placeholder="Rue, quartier, ville" invalid={!!errors.address} autoFocus {...register('address')} />
                        </Field>
                        <ChampListe control={control} name="country" id="cfg-country" label="Pays" required options={choix(PAYS, initiales.country)} error={errors.country?.message} />
                        <Field id="cfg-phone" label="Téléphone" error={errors.phone?.message} hint="Numéro principal.">
                            <Input id="cfg-phone" type="tel" maxLength={30} placeholder="+225 07 00 00 00 00" invalid={!!errors.phone} {...register('phone')} />
                        </Field>
                        <Field id="cfg-email" label="E-mail de contact" error={errors.email?.message} hint="Adresse officielle.">
                            <Input id="cfg-email" type="email" maxLength={150} placeholder="contact@crou-bondoukou.ci" invalid={!!errors.email} {...register('email')} />
                        </Field>
                        <Field id="cfg-website" label="Site web" error={errors.website?.message}>
                            <Input id="cfg-website" type="url" maxLength={200} placeholder="https://crou-bondoukou.ci" invalid={!!errors.website} {...register('website')} />
                        </Field>
                    </div>
                </>
            )}
            {etape === 1 && (
                <>
                    <Titre icone={<Palette {...ICON} />} description="Apparence sur les documents et dans l’interface.">
                        Logo et identité visuelle
                    </Titre>
                    <Controller
                        control={control}
                        name="logo"
                        render={({ field }) => (
                            <Field id="cfg-logo" label="Logo principal" error={errors.logo?.message} hint="PNG, JPG, SVG ou WEBP, 2 Mo au maximum (512 × 512 px recommandé).">
                                <FileDrop
                                    id="cfg-logo"
                                    file={field.value}
                                    onChange={(fichier) => {
                                        field.onChange(fichier);
                                        void trigger('logo');
                                    }}
                                    accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                    title="Choisir un logo"
                                    hint={valeurs.logo ? 'Le logo actuel est conservé si vous n’en choisissez pas.' : 'Aucun logo enregistré : le logo par défaut est utilisé.'}
                                    currentUrl={valeurs.logo}
                                    invalid={!!errors.logo}
                                />
                            </Field>
                        )}
                    />
                    <Controller
                        control={control}
                        name="brand_color"
                        render={({ field }) => (
                            <Field id="cfg-color" label="Couleur principale" required error={errors.brand_color?.message} hint="Utilisée pour les boutons et les éléments actifs de l’interface.">
                                <div className="flex items-center gap-3">
                                    <input
                                        type="color"
                                        aria-label="Choisir la couleur"
                                        value={/^#[0-9A-Fa-f]{6}$/.test(field.value) ? field.value : '#006400'}
                                        onChange={(event) => field.onChange(event.target.value)}
                                        className="h-10 w-14 shrink-0 cursor-pointer rounded-brand border bg-white p-1"
                                    />
                                    <div className="w-36">
                                        <Input
                                            id="cfg-color"
                                            maxLength={7}
                                            placeholder="#006400"
                                            value={field.value}
                                            onChange={(event) => field.onChange(event.target.value.trim())}
                                            onBlur={field.onBlur}
                                            invalid={!!errors.brand_color}
                                        />
                                    </div>
                                </div>
                            </Field>
                        )}
                    />
                </>
            )}
            {etape === 2 && recap}
        </Assistant>
    );
}

// ─── Notifications ────────────────────────────────────────────────────────────

const ETAPES_NOTIFICATIONS = ['E-mail', 'SMS', 'Événements', 'Vérification'];
const CHAMPS_NOTIFICATIONS = [
    ['email_enabled', 'mail_driver', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_from_name', 'mail_from_address'],
    ['sms_enabled', 'sms_provider', 'sms_api_url', 'sms_api_key', 'sms_sender'],
    EVENEMENTS.map((e) => e.cle),
    [],
] as const;

function FormulaireNotifications({ valeurs, secrets, url }: { valeurs: Props['notifications']; secrets: Secrets; url: string | null }) {
    const schema = useMemo(() => notificationsSchema(secrets), [secrets]);
    const initiales = useMemo<NotificationsValeurs>(
        () => ({ ...valeurs, mail_driver: valeurs.mail_driver in PILOTES_MAIL ? valeurs.mail_driver : 'smtp', mail_password: '', sms_api_key: '' }),
        [valeurs],
    );
    const [erreur, setErreur] = useState<string | null>(null);
    const {
        register,
        control,
        trigger,
        watch,
        reset,
        setValue,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<NotificationsValeurs>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: initiales });
    const champs = CHAMPS_NOTIFICATIONS as unknown as Path<NotificationsValeurs>[][];
    const { etape, atteinte, aller, allerA, toutValider, derniere } = useAssistant<NotificationsValeurs>(champs, trigger, true);
    const v = watch();

    const enregistrer = (donnees: NotificationsValeurs) =>
        envoyer<NotificationsValeurs>(
            url!,
            Object.fromEntries(
                Object.entries(donnees).map(([cle, valeur]) => [cle, typeof valeur === 'boolean' ? (valeur ? 1 : 0) : cle === 'mail_password' ? valeur : String(valeur).trim()]),
            ),
            {
                method: 'put',
                onSuccess: () => {
                    setValue('mail_password', '');
                    setValue('sms_api_key', '');
                },
                onError: (e) => setErreur(repartirErreurs(e, champs as unknown as string[][], (champ, err) => setError(champ as Path<NotificationsValeurs>, err), aller)),
            },
        );

    const secret = (saisi: string, enregistre: boolean) =>
        saisi ? <Badge variant="info">Nouvelle valeur</Badge> : enregistre ? <Badge variant="success">Enregistrée</Badge> : <span className="text-muted">Non renseignée</span>;

    const recap = (
        <Recap
            lignes={[
                { label: 'Notifications e-mail', valeur: etat(v.email_enabled, true) },
                ...(v.email_enabled
                    ? [
                          { label: 'Serveur d’envoi', valeur: `${libelle(PILOTES_MAIL, v.mail_driver)} · ${v.mail_host || '—'}${v.mail_port ? `:${v.mail_port}` : ''}` },
                          { label: 'Identifiant SMTP', valeur: v.mail_username || '—' },
                          { label: 'Mot de passe SMTP', valeur: secret(v.mail_password, secrets.smtp) },
                          { label: 'Expéditeur', valeur: [v.mail_from_name, v.mail_from_address && `<${v.mail_from_address}>`].filter(Boolean).join(' ') || '—' },
                      ]
                    : []),
                { label: 'Notifications SMS', valeur: etat(v.sms_enabled, true) },
                ...(v.sms_enabled
                    ? [
                          { label: 'Fournisseur SMS', valeur: libelle(FOURNISSEURS_SMS, v.sms_provider) },
                          { label: 'Passerelle', valeur: <span className="break-all">{v.sms_api_url || '—'}</span> },
                          { label: 'Clé API SMS', valeur: secret(v.sms_api_key, secrets.sms) },
                          { label: 'Expéditeur SMS', valeur: v.sms_sender || '—' },
                      ]
                    : []),
                {
                    label: 'Événements déclencheurs',
                    valeur: EVENEMENTS.filter((e) => v[e.cle]).map((e) => e.label).join(', ') || <span className="text-muted">Aucun</span>,
                },
            ]}
        />
    );

    if (!url) return recap;

    return (
        <Assistant
            etapes={ETAPES_NOTIFICATIONS}
            etape={etape}
            atteinte={atteinte}
            allerA={allerA}
            erreur={erreur}
            incomplet={Object.keys(errors).length > 0}
            onSubmit={async () => {
                setErreur(null);
                if (!derniere) await allerA(etape + 1);
                else if (await toutValider()) await handleSubmit(enregistrer)();
            }}
            pied={
                <PiedAssistant
                    etape={etape}
                    derniere={derniere}
                    envoi={isSubmitting}
                    onAnnuler={() => {
                        reset(initiales);
                        aller(0);
                    }}
                    onPrecedent={() => aller(etape - 1)}
                    libelle="Enregistrer les modifications"
                />
            }
        >
            {etape === 0 && (
                <>
                    <Titre icone={<Mail {...ICON} />} description="Serveur d’envoi et expéditeur des e-mails automatiques.">
                        Notifications par e-mail
                    </Titre>
                    <Interrupteur
                        control={control}
                        name="email_enabled"
                        label="Activer les e-mails"
                        description="Envoi des notifications par e-mail."
                        icone={<Mail />}
                        onChange={(actif) => !actif && void trigger(['mail_host', 'mail_port', 'mail_password', 'mail_from_address'])}
                    />
                    {v.email_enabled && (
                        <div className="grid gap-4 md:grid-cols-2">
                            <ChampListe control={control} name="mail_driver" id="cfg-driver" label="Pilote d’envoi" required options={choix(PILOTES_MAIL, '')} error={errors.mail_driver?.message} />
                            <Field id="cfg-host" label="Hôte SMTP" required={v.mail_driver !== 'log'} error={errors.mail_host?.message}>
                                <Input id="cfg-host" maxLength={150} placeholder="smtp.monserveur.com" invalid={!!errors.mail_host} {...register('mail_host')} />
                            </Field>
                            <Field id="cfg-port" label="Port" required={v.mail_driver !== 'log'} error={errors.mail_port?.message} hint="587 (TLS) ou 465 (SSL) en général.">
                                <Input id="cfg-port" inputMode="numeric" maxLength={5} placeholder="587" invalid={!!errors.mail_port} {...register('mail_port')} />
                            </Field>
                            <Field id="cfg-user" label="Identifiant SMTP" error={errors.mail_username?.message}>
                                <Input id="cfg-user" maxLength={150} placeholder="utilisateur@domaine.ci" autoComplete="off" invalid={!!errors.mail_username} {...register('mail_username')} />
                            </Field>
                            <Field
                                id="cfg-password"
                                label="Mot de passe SMTP"
                                error={errors.mail_password?.message}
                                hint={secrets.smtp ? 'Un mot de passe est enregistré : laissez vide pour le conserver.' : undefined}
                            >
                                <Input
                                    id="cfg-password"
                                    type="password"
                                    maxLength={255}
                                    placeholder={secrets.smtp ? '•••••••• (enregistré)' : 'Mot de passe'}
                                    autoComplete="new-password"
                                    icon={<KeyRound />}
                                    invalid={!!errors.mail_password}
                                    {...register('mail_password')}
                                />
                            </Field>
                            <Field id="cfg-from-name" label="Nom de l’expéditeur" error={errors.mail_from_name?.message} hint="Nom affiché dans la boîte de réception.">
                                <Input id="cfg-from-name" maxLength={100} placeholder="CROU Bondoukou" invalid={!!errors.mail_from_name} {...register('mail_from_name')} />
                            </Field>
                            <Field id="cfg-from" label="Adresse de l’expéditeur" required={v.mail_driver !== 'log'} error={errors.mail_from_address?.message}>
                                <Input id="cfg-from" type="email" maxLength={150} placeholder="noreply@crou-bondoukou.ci" invalid={!!errors.mail_from_address} {...register('mail_from_address')} />
                            </Field>
                        </div>
                    )}
                </>
            )}
            {etape === 1 && (
                <>
                    <Titre icone={<MessageSquareText {...ICON} />} description="Alertes envoyées par SMS via une passerelle.">
                        Notifications SMS
                    </Titre>
                    <Interrupteur
                        control={control}
                        name="sms_enabled"
                        label="Activer les SMS"
                        description="Envoi des alertes par SMS."
                        icone={<MessageSquareText />}
                        onChange={(actif) => !actif && void trigger(['sms_api_url', 'sms_api_key', 'sms_sender'])}
                    />
                    {v.sms_enabled && (
                        <>
                            {v.sms_provider !== 'helper_json' && (
                                <Note icone={<Info />} ton="attention">
                                    <p>Seule la passerelle EnvoiSMS (JSON / Bearer) est utilisée par l’application pour envoyer les SMS. Avec un autre fournisseur, aucun SMS n’est envoyé.</p>
                                </Note>
                            )}
                            <div className="grid gap-4 md:grid-cols-2">
                                <ChampListe
                                    control={control}
                                    name="sms_provider"
                                    id="cfg-sms-provider"
                                    label="Fournisseur SMS"
                                    required
                                    options={choix(FOURNISSEURS_SMS, initiales.sms_provider)}
                                    error={errors.sms_provider?.message}
                                />
                                <Field id="cfg-sms-url" label="URL de la passerelle SMS" required error={errors.sms_api_url?.message} hint="Passerelle compatible EnvoiSMS : JSON et authentification Bearer.">
                                    <Input id="cfg-sms-url" type="url" maxLength={2048} placeholder="https://passerelle.example/api/sms/send" invalid={!!errors.sms_api_url} {...register('sms_api_url')} />
                                </Field>
                                <Field
                                    id="cfg-sms-key"
                                    label="Clé API SMS"
                                    required={!secrets.sms}
                                    error={errors.sms_api_key?.message}
                                    hint={secrets.sms ? 'Une clé est enregistrée : laissez vide pour la conserver.' : undefined}
                                >
                                    <Input
                                        id="cfg-sms-key"
                                        type="password"
                                        maxLength={255}
                                        placeholder={secrets.sms ? '•••••••• (enregistrée)' : 'Clé API'}
                                        autoComplete="new-password"
                                        icon={<KeyRound />}
                                        invalid={!!errors.sms_api_key}
                                        {...register('sms_api_key')}
                                    />
                                </Field>
                                <Field id="cfg-sms-sender" label="Expéditeur SMS" required error={errors.sms_sender?.message} hint="Nom ou numéro affiché, 20 caractères au maximum.">
                                    <Input id="cfg-sms-sender" maxLength={20} placeholder="CROU BDK" invalid={!!errors.sms_sender} {...register('sms_sender')} />
                                </Field>
                            </div>
                        </>
                    )}
                </>
            )}
            {etape === 2 && (
                <>
                    <Titre icone={<Bell {...ICON} />} description="Choisissez quand envoyer des notifications automatiques.">
                        Événements déclencheurs
                    </Titre>
                    <div className="grid gap-3 md:grid-cols-2">
                        {EVENEMENTS.map((e) => (
                            <Interrupteur key={e.cle} control={control} name={e.cle} label={e.label} description={e.hint} icone={<Bell />} />
                        ))}
                    </div>
                </>
            )}
            {etape === 3 && recap}
        </Assistant>
    );
}

// ─── Sections Paiements, Taxes, Devises, Sécurité ──────────────────────────────

/** Données envoyées : booléens en 1 / 0, textes sans espaces superflus, listes telles quelles. */
const charge = (donnees: FieldValues) =>
    Object.fromEntries(Object.entries(donnees).map(([cle, valeur]) => [cle, typeof valeur === 'boolean' ? (valeur ? 1 : 0) : typeof valeur === 'string' ? valeur.trim() : valeur]));

/** Câblage commun d'une section en assistant : formulaire, étapes, envoi, erreurs serveur réparties par étape. */
function useSection<T extends FieldValues>(
    resolver: Resolver<T>,
    initiales: T,
    champs: readonly (readonly Path<T>[])[],
    url: string | null,
    preparer: (donnees: T) => FieldValues = charge,
) {
    const [erreur, setErreur] = useState<string | null>(null);
    const form = useForm<T>({ resolver, mode: 'onTouched', defaultValues: initiales as DefaultValues<T> });
    const assistant = useAssistant<T>(champs, form.trigger, true);
    const v = form.watch();

    const enregistrer = (donnees: T) =>
        envoyer<T>(url!, preparer(donnees) as RequestPayload, {
            method: 'put',
            onError: (e) => setErreur(repartirErreurs(e, champs as unknown as string[][], (champ, err) => form.setError(champ as Path<T>, err), assistant.aller)),
        });

    const cadre = (etapes: string[], contenu: ReactNode) => (
        <Assistant
            etapes={etapes}
            etape={assistant.etape}
            atteinte={assistant.atteinte}
            allerA={assistant.allerA}
            erreur={erreur}
            incomplet={Object.keys(form.formState.errors).length > 0}
            onSubmit={async () => {
                setErreur(null);
                if (!assistant.derniere) await assistant.allerA(assistant.etape + 1);
                else if (await assistant.toutValider()) await form.handleSubmit(enregistrer)();
            }}
            pied={
                <PiedAssistant
                    etape={assistant.etape}
                    derniere={assistant.derniere}
                    envoi={form.formState.isSubmitting}
                    onAnnuler={() => {
                        form.reset(initiales);
                        assistant.aller(0);
                    }}
                    onPrecedent={() => assistant.aller(assistant.etape - 1)}
                    libelle="Enregistrer les modifications"
                />
            }
        >
            {contenu}
        </Assistant>
    );

    return { form, v, etape: assistant.etape, cadre };
}

/** Cases à cocher d'une liste de valeurs (modes de paiement, devises secondaires). */
function ChoixMultiples<T extends FieldValues>({
    control,
    name,
    options,
    exclure,
}: {
    control: Control<T>;
    name: Path<T>;
    options: { cle: string; label: string; hint?: string; icone?: ReactNode }[];
    exclure?: string;
}) {
    return (
        <Controller
            control={control}
            name={name}
            render={({ field }) => {
                const valeurs = (field.value ?? []) as string[];
                return (
                    <div className="grid gap-3 md:grid-cols-2">
                        {options
                            .filter((o) => o.cle !== exclure)
                            .map((o) => (
                                <ToggleCard
                                    key={o.cle}
                                    checked={valeurs.includes(o.cle)}
                                    onChange={(coche) => {
                                        field.onChange(coche ? [...valeurs, o.cle] : valeurs.filter((x) => x !== o.cle));
                                        field.onBlur();
                                    }}
                                    label={o.label}
                                    description={o.hint}
                                    icon={o.icone}
                                />
                            ))}
                    </div>
                );
            }}
        />
    );
}

const enTexte = (valeur: string, unite: string) => (valeur === '' ? '—' : `${valeur} ${unite}`);

function ErreurChamp({ message }: { message?: string }) {
    return message ? (
        <p role="alert" className="text-sm text-red-600">
            {message}
        </p>
    ) : null;
}

// ─── Paiements ────────────────────────────────────────────────────────────────

const ETAPES_PAIEMENTS = ['Modes acceptés', 'Paiement en ligne', 'Vérification'];
const CHAMPS_PAIEMENTS = [['methods'], ['online_payment_enabled', 'auto_receipt', 'min_amount', 'daily_limit'], []] as const;
const ICONES_MODES: Record<string, ReactNode> = { cash: <Banknote />, mobile: <Smartphone />, transfer: <Landmark />, card: <CreditCard /> };

function FormulairePaiements({ valeurs, url, apiPaiement }: { valeurs: PaiementsValeurs; url: string | null; apiPaiement: string | null }) {
    const { form, v, etape, cadre } = useSection<PaiementsValeurs>(zodResolver(paiementsSchema), valeurs, CHAMPS_PAIEMENTS, url);
    const {
        register,
        control,
        formState: { errors },
    } = form;
    const plafond = (valeur: string) => (valeur === '' ? '—' : Number(valeur) === 0 ? 'Illimité' : montant(valeur));

    const recap = (
        <Recap
            lignes={[
                {
                    label: 'Modes acceptés',
                    valeur: MODES_PAIEMENT.filter((m) => v.methods.includes(m.cle))
                        .map((m) => m.label)
                        .join(', ') || <span className="text-muted">Aucun</span>,
                },
                { label: 'Paiement en ligne', valeur: etat(v.online_payment_enabled) },
                { label: 'Reçu automatique', valeur: etat(v.auto_receipt) },
                { label: 'Montant minimum en ligne', valeur: v.min_amount === '' ? '—' : montant(v.min_amount) },
                { label: 'Plafond journalier par étudiant', valeur: plafond(v.daily_limit) },
            ]}
        />
    );

    if (!url) return recap;

    return cadre(
        ETAPES_PAIEMENTS,
        <>
            {etape === 0 && (
                <>
                    <Titre icone={<Wallet {...ICON} />} description="Modes proposés au guichet et aux étudiants.">
                        Modes de paiement acceptés
                    </Titre>
                    <ChoixMultiples control={control} name="methods" options={MODES_PAIEMENT.map((m) => ({ ...m, icone: ICONES_MODES[m.cle] }))} />
                    <ErreurChamp message={errors.methods?.message} />
                    <Note icone={<Info />}>
                        <p>Les méthodes proposées lors de l’enregistrement d’un paiement au guichet suivent ces choix (Espèces, Mobile Money ; « Autre » pour le virement et la carte).</p>
                    </Note>
                </>
            )}
            {etape === 1 && (
                <>
                    <Titre icone={<Globe {...ICON} />} description="Règlement du loyer par l’étudiant depuis son espace et l’application mobile.">
                        Paiement électronique
                    </Titre>
                    <Interrupteur control={control} name="online_payment_enabled" label="Activer le paiement en ligne" description="Les étudiants règlent leur loyer par Mobile Money." icone={<Globe />} />
                    <ErreurChamp message={errors.online_payment_enabled?.message} />
                    <Interrupteur control={control} name="auto_receipt" label="Reçu automatique" description="Reçu disponible après chaque paiement confirmé." icone={<Receipt />} />
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field id="cfg-min" label={`Montant minimum (${symboleDevise()})`} required error={errors.min_amount?.message} hint="Un solde inférieur reste payable en une fois.">
                            <Input id="cfg-min" inputMode="numeric" maxLength={9} placeholder="500" invalid={!!errors.min_amount} {...register('min_amount')} />
                        </Field>
                        <Field id="cfg-plafond" label={`Plafond journalier par étudiant (${symboleDevise()})`} required error={errors.daily_limit?.message} hint="0 = illimité.">
                            <Input id="cfg-plafond" inputMode="numeric" maxLength={10} placeholder="500000" invalid={!!errors.daily_limit} {...register('daily_limit')} />
                        </Field>
                    </div>
                    <Note icone={<KeyRound />}>
                        <p>
                            Les réseaux (Wave, MTN, CinetPay…) et leurs clés se règlent dans la page API de paiement.{' '}
                            {apiPaiement && (
                                <a href={apiPaiement} className="inline-flex items-center gap-1 font-medium text-primary underline-offset-2 hover:underline">
                                    Ouvrir l’API de paiement <ExternalLink className="size-3.5" aria-hidden />
                                </a>
                            )}
                        </p>
                    </Note>
                </>
            )}
            {etape === 2 && recap}
        </>,
    );
}

// ─── Taxes ────────────────────────────────────────────────────────────────────

const ETAPES_TAXES = ['TVA', 'Taxes additionnelles', 'Vérification'];
const CHAMPS_TAXES = [['tva_enabled', 'tva_rate', 'tva_number', 'price_display', 'tva_on_services'], ['digital_tax_rate', 'service_fee'], []] as const;

function FormulaireTaxes({ valeurs, url }: { valeurs: TaxesValeurs; url: string | null }) {
    const { form, v, etape, cadre } = useSection<TaxesValeurs>(zodResolver(taxesSchema), valeurs, CHAMPS_TAXES, url, (d) => ({
        ...charge(d),
        tva_rate: d.tva_rate.trim().replace(',', '.'),
        digital_tax_rate: d.digital_tax_rate.trim().replace(',', '.'),
    }));
    const {
        register,
        control,
        formState: { errors },
    } = form;

    const recap = (
        <Recap
            lignes={[
                { label: 'TVA', valeur: etat(v.tva_enabled, true) },
                ...(v.tva_enabled
                    ? [
                          { label: 'Taux de TVA', valeur: enTexte(v.tva_rate, '%') },
                          { label: 'Numéro de TVA', valeur: v.tva_number || '—' },
                          { label: 'Affichage des prix', valeur: libelle(AFFICHAGES_PRIX, v.price_display) },
                          { label: 'TVA sur les services', valeur: etat(v.tva_on_services, true) },
                      ]
                    : []),
                { label: 'Taxe sur les paiements électroniques', valeur: enTexte(v.digital_tax_rate, '%') },
                { label: 'Frais de service', valeur: v.service_fee === '' ? '—' : montant(v.service_fee) },
            ]}
        />
    );

    if (!url) return recap;

    return cadre(
        ETAPES_TAXES,
        <>
            {etape === 0 && (
                <>
                    <Titre icone={<Receipt {...ICON} />} description="Taux et mode d’affichage de la TVA.">
                        Paramètres de la TVA
                    </Titre>
                    <Interrupteur control={control} name="tva_enabled" label="Activer la TVA" description="Appliquer la TVA sur les transactions." icone={<Percent />} />
                    {v.tva_enabled && (
                        <>
                            <div className="grid gap-4 md:grid-cols-3">
                                <Field id="cfg-tva" label="Taux de TVA (%)" required error={errors.tva_rate?.message} hint="Taux standard applicable.">
                                    <Input id="cfg-tva" inputMode="decimal" maxLength={6} placeholder="18" invalid={!!errors.tva_rate} {...register('tva_rate')} />
                                </Field>
                                <Field id="cfg-tva-num" label="Numéro de TVA" error={errors.tva_number?.message} hint="Affiché sur les factures.">
                                    <Input id="cfg-tva-num" maxLength={60} placeholder="CI-0000000-X" invalid={!!errors.tva_number} {...register('tva_number')} />
                                </Field>
                                <ChampListe
                                    control={control}
                                    name="price_display"
                                    id="cfg-prix"
                                    label="Affichage des prix"
                                    required
                                    options={choix(AFFICHAGES_PRIX, '')}
                                    error={errors.price_display?.message}
                                />
                            </div>
                            <Interrupteur control={control} name="tva_on_services" label="TVA sur les services" description="Appliquer aux prestations de service." icone={<Percent />} />
                        </>
                    )}
                </>
            )}
            {etape === 1 && (
                <>
                    <Titre icone={<Percent {...ICON} />} description="Autres taxes ou contributions applicables.">
                        Taxes additionnelles
                    </Titre>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field
                            id="cfg-taxe-num"
                            label="Taxe sur les paiements électroniques (%)"
                            required
                            error={errors.digital_tax_rate?.message}
                            hint="Pourcentage retenu sur chaque transaction en ligne."
                        >
                            <Input id="cfg-taxe-num" inputMode="decimal" maxLength={6} placeholder="0" invalid={!!errors.digital_tax_rate} {...register('digital_tax_rate')} />
                        </Field>
                        <Field id="cfg-frais" label={`Frais de service (${symboleDevise()})`} required error={errors.service_fee?.message} hint="Montant fixe par opération.">
                            <Input id="cfg-frais" inputMode="numeric" maxLength={9} placeholder="0" invalid={!!errors.service_fee} {...register('service_fee')} />
                        </Field>
                    </div>
                    <Note icone={<Info />} ton="attention">
                        <p>Ces taux sont enregistrés pour les documents comptables : ils ne modifient pas le montant des loyers fixés par type de chambre.</p>
                    </Note>
                </>
            )}
            {etape === 2 && recap}
        </>,
    );
}

// ─── Devises ──────────────────────────────────────────────────────────────────

const ETAPES_DEVISES = ['Devise principale', 'Devises secondaires', 'Vérification'];
const CHAMPS_DEVISES = [['base_currency', 'currency_symbol', 'symbol_position', 'thousands_separator'], ['secondary_currencies', 'auto_rate', 'rate_frequency'], []] as const;
const CARACTERE_SEPARATEUR: Record<string, string> = { espace: ' ', point: '.', virgule: ',' };

function FormulaireDevises({ valeurs, url }: { valeurs: DevisesValeurs; url: string | null }) {
    const { form, v, etape, cadre } = useSection<DevisesValeurs>(zodResolver(devisesSchema), valeurs, CHAMPS_DEVISES, url);
    const {
        register,
        control,
        formState: { errors },
    } = form;
    const chiffres = '1250000'.replace(/\B(?=(\d{3})+(?!\d))/g, CARACTERE_SEPARATEUR[v.thousands_separator] ?? ' ');
    const symbole = v.currency_symbol.trim() || v.base_currency;
    const exemple = v.symbol_position === 'before' ? `${symbole} ${chiffres}` : `${chiffres} ${symbole}`;

    const recap = (
        <Recap
            lignes={[
                { label: 'Devise de base', valeur: libelle(DEVISES, v.base_currency) },
                { label: 'Affichage d’un montant', valeur: <span className="font-medium">{exemple}</span> },
                { label: 'Séparateur de milliers', valeur: libelle(SEPARATEURS, v.thousands_separator) },
                {
                    label: 'Devises secondaires',
                    valeur: v.secondary_currencies.filter((d) => d !== v.base_currency).join(', ') || <span className="text-muted">Aucune</span>,
                },
                { label: 'Taux de change', valeur: v.auto_rate ? `Automatique · ${libelle(FREQUENCES, v.rate_frequency)}` : 'Manuel' },
            ]}
        />
    );

    if (!url) return recap;

    return cadre(
        ETAPES_DEVISES,
        <>
            {etape === 0 && (
                <>
                    <Titre icone={<Coins {...ICON} />} description="Monnaie de référence et format des montants affichés dans toute l’application.">
                        Devise principale
                    </Titre>
                    <div className="grid gap-4 md:grid-cols-2">
                        <ChampListe
                            control={control}
                            name="base_currency"
                            id="cfg-devise"
                            label="Devise de base"
                            required
                            options={choix(DEVISES, '')}
                            error={errors.base_currency?.message}
                            hint="Toutes les conversions partent de cette devise."
                        />
                        <Field id="cfg-symbole" label="Symbole affiché" required error={errors.currency_symbol?.message} hint="Ex. FCFA, €, $.">
                            <Input id="cfg-symbole" maxLength={10} placeholder="FCFA" invalid={!!errors.currency_symbol} {...register('currency_symbol')} />
                        </Field>
                        <ChampListe
                            control={control}
                            name="symbol_position"
                            id="cfg-position"
                            label="Position du symbole"
                            required
                            options={choix(POSITIONS_SYMBOLE, '')}
                            error={errors.symbol_position?.message}
                        />
                        <ChampListe
                            control={control}
                            name="thousands_separator"
                            id="cfg-separateur"
                            label="Séparateur de milliers"
                            required
                            options={choix(SEPARATEURS, '')}
                            error={errors.thousands_separator?.message}
                        />
                    </div>
                    <Note icone={<Info />}>
                        <p>
                            Aperçu : <span className="font-medium">{exemple}</span>. Ce format s’applique aux montants des écrans, reçus et contrats.
                        </p>
                    </Note>
                    {v.base_currency !== 'XOF' && (
                        <Note icone={<CircleAlert />} ton="attention">
                            <p>Les paiements Mobile Money (Wave, MTN, CinetPay) restent encaissés en francs CFA (XOF), quelle que soit la devise affichée.</p>
                        </Note>
                    )}
                </>
            )}
            {etape === 1 && (
                <>
                    <Titre icone={<ArrowLeftRight {...ICON} />} description="Devises acceptées en plus de la devise principale, et mise à jour des taux.">
                        Devises secondaires et taux de change
                    </Titre>
                    <ChoixMultiples
                        control={control}
                        name="secondary_currencies"
                        exclure={v.base_currency}
                        options={Object.entries(DEVISES_SECONDAIRES).map(([cle, label]) => ({ cle, label, icone: <Coins /> }))}
                    />
                    <Interrupteur
                        control={control}
                        name="auto_rate"
                        label="Mise à jour automatique des taux"
                        description="Récupérer les taux via un service externe."
                        icone={<ArrowLeftRight />}
                    />
                    {v.auto_rate && (
                        <div className="md:w-1/2">
                            <ChampListe
                                control={control}
                                name="rate_frequency"
                                id="cfg-frequence"
                                label="Fréquence de mise à jour"
                                required
                                options={choix(FREQUENCES, '')}
                                error={errors.rate_frequency?.message}
                            />
                        </div>
                    )}
                </>
            )}
            {etape === 2 && recap}
        </>,
    );
}

// ─── Sécurité ─────────────────────────────────────────────────────────────────

const ETAPES_SECURITE = ['Authentification', 'Mots de passe', 'Contrôle d’accès', 'Vérification'];
const CHAMPS_SECURITE = [
    ['two_factor_auth', 'session_lifetime', 'allow_multiple_sessions'],
    ['password_min_length', 'password_require_uppercase', 'password_require_number', 'password_require_special'],
    ['max_login_attempts', 'lockout_duration', 'ip_restriction', 'allowed_ips', 'force_https'],
    [],
] as const;

function FormulaireSecurite({ valeurs, contexte, url }: { valeurs: SecuriteValeurs; contexte: Props['contexte']; url: string | null }) {
    const schema = useMemo(() => securiteSchema(contexte), [contexte]);
    const { form, v, etape, cadre } = useSection<SecuriteValeurs>(zodResolver(schema), valeurs, CHAMPS_SECURITE, url);
    const {
        register,
        control,
        trigger,
        formState: { errors },
    } = form;
    const regles = [
        `${v.password_min_length || '—'} caractères minimum`,
        v.password_require_uppercase && 'majuscule',
        v.password_require_number && 'chiffre',
        v.password_require_special && 'caractère spécial',
    ].filter(Boolean);

    const recap = (
        <Recap
            lignes={[
                { label: 'Double authentification (e-mail)', valeur: etat(v.two_factor_auth, true) },
                { label: 'Durée de session', valeur: enTexte(v.session_lifetime, 'min') },
                { label: 'Sessions simultanées', valeur: v.allow_multiple_sessions ? 'Autorisées' : 'Une seule par compte' },
                { label: 'Mots de passe', valeur: regles.join(', ') },
                { label: 'Blocage', valeur: `Après ${v.max_login_attempts || '—'} tentatives, pendant ${v.lockout_duration || '—'} min` },
                { label: 'Restriction par IP', valeur: v.ip_restriction ? <span className="break-all">{v.allowed_ips.trim() || '—'}</span> : etat(false, true) },
                { label: 'Forcer HTTPS', valeur: etat(v.force_https) },
            ]}
        />
    );

    if (!url) return recap;

    return cadre(
        ETAPES_SECURITE,
        <>
            {etape === 0 && (
                <>
                    <Titre icone={<UserLock {...ICON} />} description="Connexion des administrateurs.">
                        Authentification
                    </Titre>
                    <Interrupteur
                        control={control}
                        name="two_factor_auth"
                        label="Double authentification (2FA)"
                        description="Code envoyé par e-mail à chaque connexion d’un administrateur."
                        icone={<ShieldCheck />}
                    />
                    {v.two_factor_auth && (
                        <Note icone={<Mail />} ton="attention">
                            <p>Le code est envoyé par e-mail : vérifiez que les notifications e-mail sont configurées, sinon les administrateurs ne pourront plus se connecter.</p>
                        </Note>
                    )}
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field id="cfg-session" label="Durée de session (minutes)" required error={errors.session_lifetime?.message} hint="Déconnexion après inactivité (5 à 1440).">
                            <Input id="cfg-session" inputMode="numeric" maxLength={4} placeholder="120" invalid={!!errors.session_lifetime} {...register('session_lifetime')} />
                        </Field>
                    </div>
                    <Interrupteur control={control} name="allow_multiple_sessions" label="Sessions simultanées" description="Autoriser plusieurs connexions par compte." icone={<Activity />} />
                </>
            )}
            {etape === 1 && (
                <>
                    <Titre icone={<LockKeyhole {...ICON} />} description="Règles imposées aux administrateurs et aux étudiants.">
                        Politique de mots de passe
                    </Titre>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field id="cfg-pwd-min" label="Longueur minimale" required error={errors.password_min_length?.message} hint="6 à 32 caractères.">
                            <Input id="cfg-pwd-min" inputMode="numeric" maxLength={2} placeholder="8" invalid={!!errors.password_min_length} {...register('password_min_length')} />
                        </Field>
                    </div>
                    <div className="grid gap-3 md:grid-cols-3">
                        <Interrupteur control={control} name="password_require_uppercase" label="Majuscule obligatoire" icone={<KeyRound />} />
                        <Interrupteur control={control} name="password_require_number" label="Chiffre obligatoire" icone={<KeyRound />} />
                        <Interrupteur control={control} name="password_require_special" label="Caractère spécial obligatoire" icone={<KeyRound />} />
                    </div>
                </>
            )}
            {etape === 2 && (
                <>
                    <Titre icone={<ShieldCheck {...ICON} />} description="Protection contre les tentatives d’intrusion.">
                        Contrôle d’accès
                    </Titre>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field id="cfg-tentatives" label="Tentatives de connexion maximum" required error={errors.max_login_attempts?.message} hint="Avant blocage temporaire.">
                            <Input id="cfg-tentatives" inputMode="numeric" maxLength={3} placeholder="5" invalid={!!errors.max_login_attempts} {...register('max_login_attempts')} />
                        </Field>
                        <Field id="cfg-blocage" label="Durée de blocage (minutes)" required error={errors.lockout_duration?.message}>
                            <Input id="cfg-blocage" inputMode="numeric" maxLength={4} placeholder="15" invalid={!!errors.lockout_duration} {...register('lockout_duration')} />
                        </Field>
                    </div>
                    <Interrupteur
                        control={control}
                        name="ip_restriction"
                        label="Restriction par adresse IP"
                        description="N’autoriser que certaines adresses IP."
                        icone={<Globe />}
                        onChange={(actif) => !actif && void trigger('allowed_ips')}
                    />
                    {v.ip_restriction && (
                        <Field
                            id="cfg-ips"
                            label="Adresses IP autorisées"
                            required
                            error={errors.allowed_ips?.message}
                            hint={`Séparées par des virgules ou des retours à la ligne. Votre adresse actuelle : ${contexte.ip}.`}
                        >
                            <Textarea id="cfg-ips" rows={3} maxLength={2000} placeholder={contexte.ip} invalid={!!errors.allowed_ips} {...register('allowed_ips')} />
                        </Field>
                    )}
                    <Interrupteur control={control} name="force_https" label="Forcer HTTPS" description="Rediriger tout le trafic vers HTTPS." icone={<LockKeyhole />} />
                    <ErreurChamp message={errors.force_https?.message} />
                </>
            )}
            {etape === 3 && recap}
        </>,
    );
}
