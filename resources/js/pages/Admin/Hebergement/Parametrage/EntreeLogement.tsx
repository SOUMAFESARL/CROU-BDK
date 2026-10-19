import { zodResolver } from '@hookform/resolvers/zod';
import { BellRing, CalendarCheck, CalendarClock, CircleAlert, Info, RotateCcw } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Controller, useForm, type Path, type Resolver } from 'react-hook-form';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { ErreurGenerale, Note, PiedAssistant, Recap, useAssistant } from '@/components/parametrage-hebergement/shared';
import { ToggleCard } from '@/components/parc-immobilier/form/inputs';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { StatCard } from '@/components/ui/stat-card';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import { enNombre, entreeSchema, type EntreeValeurs } from '@/schemas/parametrage-hebergement';
import type { Paginated } from '@/types/global';

type Options = {
    enabled: boolean;
    proposal_hours: number;
    appointments_enabled: boolean;
    start_date: string;
    weekdays: number[];
    opening_time: string;
    closing_time: string;
    slot_minutes: number;
    capacity: number;
    lead_hours: number;
    location: string;
};

type RendezVous = { id: number; etudiant: string | null; date: string | null; lieu: string | null; statut: 'programme' | 'annule' };

type Props = {
    options: Options;
    terms: { key: string; label: string; value: string }[];
    appointments: Paginated<RendezVous>;
    failed: number;
    timezone: string;
    urls: { update: string | null; retry: string | null };
};

const JOURS = [
    [1, 'Lundi'],
    [2, 'Mardi'],
    [3, 'Mercredi'],
    [4, 'Jeudi'],
    [5, 'Vendredi'],
    [6, 'Samedi'],
    [7, 'Dimanche'],
] as const;

const ETAPES = ['Notifications', 'Créneaux d’entrée', 'Conditions du contrat', 'Vérification'];

/**
 * Notifications, contrat et entrée dans les lieux (remplace `admin/hebergement/entry-settings.blade.php`).
 * Actions `housing-entry.update` et `housing-entry.retry` inchangées.
 */
export default function EntreeLogement({ options, terms, appointments, failed, timezone, urls }: Props) {
    const [reprise, setReprise] = useState(false);
    const [repriseEnCours, setRepriseEnCours] = useState(false);

    const reprendre = async () => {
        if (!urls.retry) return;
        setRepriseEnCours(true);
        const ok = await envoyer(urls.retry, {});
        setRepriseEnCours(false);
        if (ok) setReprise(false);
    };

    const programmes = appointments.data.filter((r) => r.statut === 'programme').length;

    const colonnes: DataTableColumn<RendezVous>[] = [
        { key: 'etudiant', header: 'Étudiant', cell: (r) => <span className="font-medium text-gray-900">{r.etudiant ?? '—'}</span> },
        { key: 'date', header: 'Date', className: 'whitespace-nowrap tabular-nums', cell: (r) => r.date ?? '—' },
        { key: 'lieu', header: 'Lieu', cell: (r) => r.lieu ?? '—' },
        { key: 'statut', header: 'Statut', cell: (r) => (r.statut === 'programme' ? <Badge variant="success">Programmé</Badge> : <Badge variant="neutral">Annulé</Badge>) },
    ];

    return (
        <AdminLayout title="Notifications, contrat et entrée">
            <PageHeader
                title="Notifications, contrat et entrée dans les lieux"
                description="L’annonce de campagne, la proposition, le contrat et le rendez-vous sont traités automatiquement en arrière-plan. Les rendez-vous sont créés après approbation du contrat par l’étudiant."
            />

            <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard tone={options.enabled ? 'green' : 'neutral'} label="Parcours automatique" value={options.enabled ? 'Activé' : 'Désactivé'} />
                <StatCard tone={options.appointments_enabled ? 'blue' : 'neutral'} label="Rendez-vous automatiques" value={options.appointments_enabled ? 'Activés' : 'Désactivés'} />
                <StatCard tone="amber" label="Rendez-vous enregistrés" value={nombre(appointments.total)} />
                <StatCard tone={failed > 0 ? 'red' : 'neutral'} label="Envois en échec" value={nombre(failed)} highlight={failed > 0} />
            </div>

            <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
                {urls.update ? (
                    <Formulaire options={options} terms={terms} timezone={timezone} url={urls.update} />
                ) : (
                    <p className="h-fit rounded-brand border bg-white p-6 text-sm text-muted">Vous pouvez consulter ces paramètres mais pas les modifier.</p>
                )}

                <aside className="h-fit space-y-3 rounded-brand border bg-white p-4">
                    <h2 className="flex items-center gap-2 text-sm font-medium text-gray-900">
                        <BellRing {...ICON} className="text-primary" aria-hidden /> Suivi des envois
                    </h2>
                    <p className="text-sm text-muted">
                        {failed > 0 ? `${nombre(failed)} envoi(s) en échec.` : 'Aucun envoi en échec.'} Les notifications restent consultables dans l’espace étudiant.
                    </p>
                    {failed > 0 && urls.retry && (
                        <Button variant="secondary" size="sm" className="h-10 w-full" onClick={() => setReprise(true)}>
                            <RotateCcw className="size-4" aria-hidden /> Reprendre les envois en échec
                        </Button>
                    )}
                </aside>
            </div>

            <section className="mt-6" aria-labelledby="rdv-titre">
                <h2 id="rdv-titre" className="mb-3 flex items-center gap-2 text-base font-medium text-gray-900">
                    <CalendarCheck {...ICON} className="text-primary" aria-hidden /> Rendez-vous d’entrée
                    {programmes > 0 && <span className="text-sm font-normal text-muted">· {nombre(programmes)} programmé(s) sur cette page</span>}
                </h2>
                <DataTable
                    columns={colonnes}
                    data={appointments}
                    rowKey={(r) => String(r.id)}
                    emptyMessage={
                        <span className="flex flex-col items-center gap-2">
                            <CalendarClock {...ICON} className="text-gray-400" aria-hidden />
                            Aucun rendez-vous programmé.
                        </span>
                    }
                />
            </section>

            <ConfirmDialog
                open={reprise}
                onOpenChange={setReprise}
                title="Reprendre les envois en échec ?"
                description={`${nombre(failed)} notification(s) seront renvoyées par le traitement automatique.`}
                confirmLabel="Reprendre les envois"
                confirmIcon={<RotateCcw className="size-4" aria-hidden />}
                processing={repriseEnCours}
                onConfirm={() => void reprendre()}
            />
        </AdminLayout>
    );
}

function Formulaire({ options, terms, timezone, url }: { options: Options; terms: Props['terms']; timezone: string; url: string }) {
    const cles = useMemo(() => terms.map((t) => t.key), [terms]);
    const schema = useMemo(() => entreeSchema(cles), [cles]);
    const champs = useMemo(
        () =>
            [
                ['enabled', 'proposal_hours'],
                ['appointments_enabled', 'start_date', 'weekdays', 'opening_time', 'closing_time', 'slot_minutes', 'capacity', 'lead_hours', 'location'],
                ['contract'],
                [],
            ] as unknown as Path<EntreeValeurs>[][],
        [],
    );
    const valeursInitiales = useMemo<EntreeValeurs>(
        () => ({
            enabled: options.enabled,
            proposal_hours: String(options.proposal_hours),
            appointments_enabled: options.appointments_enabled,
            start_date: options.start_date,
            weekdays: options.weekdays,
            opening_time: options.opening_time,
            closing_time: options.closing_time,
            slot_minutes: String(options.slot_minutes),
            capacity: String(options.capacity),
            lead_hours: String(options.lead_hours),
            location: options.location,
            contract: Object.fromEntries(terms.map((t) => [t.key, t.value])),
        }),
        [options, terms],
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
    } = useForm<EntreeValeurs>({ resolver: zodResolver(schema) as unknown as Resolver<EntreeValeurs>, mode: 'onTouched', defaultValues: valeursInitiales });
    const { etape, atteinte, aller, allerA, toutValider, derniere } = useAssistant<EntreeValeurs>(champs, trigger, true);
    const v = watch();
    const erreurContrat = (cle: string) => (errors.contract as Record<string, { message?: string }> | undefined)?.[cle]?.message;

    const enregistrer = (valeurs: EntreeValeurs) =>
        envoyer<EntreeValeurs>(
            url,
            {
                enabled: valeurs.enabled ? 1 : 0,
                proposal_hours: enNombre(valeurs.proposal_hours),
                appointments_enabled: valeurs.appointments_enabled ? 1 : 0,
                start_date: valeurs.start_date || null,
                weekdays: valeurs.weekdays,
                opening_time: valeurs.opening_time || null,
                closing_time: valeurs.closing_time || null,
                slot_minutes: enNombre(valeurs.slot_minutes),
                capacity: enNombre(valeurs.capacity),
                lead_hours: enNombre(valeurs.lead_hours),
                location: valeurs.location.trim() || null,
                contract: Object.fromEntries(Object.entries(valeurs.contract).map(([cle, valeur]) => [cle, valeur.trim()])),
            },
            {
                onError: (erreurs) => {
                    let fautive = -1;
                    const autres: string[] = [];
                    Object.entries(erreurs).forEach(([cle, message]) => {
                        const racine = cle.split('.')[0];
                        const index = (champs as unknown as string[][]).findIndex((liste) => liste.includes(racine));
                        if (index < 0) {
                            autres.push(message);
                            return;
                        }
                        setError((racine === 'weekdays' ? 'weekdays' : cle) as Path<EntreeValeurs>, { type: 'server', message });
                        fautive = fautive < 0 ? index : Math.min(fautive, index);
                    });
                    if (fautive >= 0) aller(fautive);
                    setErreur(autres.length ? autres.join(' ') : null);
                },
            },
        );

    const jours = JOURS.filter(([n]) => v.weekdays.includes(n)).map(([, label]) => label);

    return (
        <form
            noValidate
            className="flex min-w-0 flex-col overflow-hidden rounded-brand border bg-white"
            onSubmit={async (event) => {
                event.preventDefault();
                setErreur(null);
                if (!derniere) await allerA(etape + 1);
                else if (await toutValider()) await handleSubmit(enregistrer)();
            }}
        >
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={(index) => void allerA(index)} className="overflow-x-auto" />
            <div className="space-y-4 px-6 py-5">
                <ErreurGenerale message={erreur} />

                {etape === 0 && (
                    <>
                        <Controller
                            control={control}
                            name="enabled"
                            render={({ field }) => (
                                <ToggleCard
                                    checked={field.value}
                                    onChange={field.onChange}
                                    label="Activer le parcours automatique"
                                    icon={<BellRing />}
                                    description="Pour les nouvelles attributions et l’annonce des campagnes actives."
                                />
                            )}
                        />
                        <div className="max-w-xs">
                            <Field id="ee-proposal" label="Délai de réponse à la proposition (heures)" required error={errors.proposal_hours?.message} hint="Entre 1 et 720 heures">
                                <Input id="ee-proposal" inputMode="numeric" placeholder="Ex. 72" invalid={!!errors.proposal_hours} {...register('proposal_hours')} />
                            </Field>
                        </div>
                    </>
                )}

                {etape === 1 && (
                    <>
                        <Controller
                            control={control}
                            name="appointments_enabled"
                            render={({ field }) => (
                                <ToggleCard
                                    checked={field.value}
                                    onChange={(valeur) => {
                                        field.onChange(valeur);
                                        if (!valeur) void trigger(['start_date', 'weekdays', 'opening_time', 'closing_time', 'location']);
                                    }}
                                    label="Programmer automatiquement les rendez-vous"
                                    icon={<CalendarClock />}
                                    description="Les champs ci-dessous deviennent obligatoires."
                                />
                            )}
                        />
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Controller
                                control={control}
                                name="start_date"
                                render={({ field }) => (
                                    <Field id="ee-start" label="À partir du" required={v.appointments_enabled} error={errors.start_date?.message}>
                                        <DatePicker id="ee-start" value={field.value} onChange={field.onChange} invalid={!!errors.start_date} />
                                    </Field>
                                )}
                            />
                            <Field id="ee-location" label="Lieu d’accueil" required={v.appointments_enabled} error={errors.location?.message}>
                                <Input id="ee-location" maxLength={255} placeholder="Ex. Bureau du CROU, cité A" invalid={!!errors.location} {...register('location')} />
                            </Field>
                        </div>
                        <Controller
                            control={control}
                            name="weekdays"
                            render={({ field }) => (
                                <fieldset className="space-y-1.5">
                                    <legend className="text-sm font-medium text-gray-800">
                                        Jours d’accueil {v.appointments_enabled && <span className="text-red-600">*</span>}
                                    </legend>
                                    <div className="flex flex-wrap gap-2">
                                        {JOURS.map(([n, label]) => {
                                            const actif = field.value.includes(n);
                                            return (
                                                <button
                                                    key={n}
                                                    type="button"
                                                    aria-pressed={actif}
                                                    onClick={() => field.onChange(actif ? field.value.filter((j) => j !== n) : [...field.value, n].sort())}
                                                    className={cn(
                                                        'h-10 cursor-pointer rounded-brand border px-3 text-sm transition-colors focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                                                        actif ? 'border-primary bg-soft text-primary' : 'border-gray-300 bg-white text-muted hover:border-gray-400',
                                                    )}
                                                >
                                                    {label}
                                                </button>
                                            );
                                        })}
                                    </div>
                                    {errors.weekdays && (
                                        <p role="alert" className="text-xs font-medium text-red-600">
                                            {errors.weekdays.message}
                                        </p>
                                    )}
                                </fieldset>
                            )}
                        />
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field id="ee-open" label="Ouverture" required={v.appointments_enabled} error={errors.opening_time?.message}>
                                <Input id="ee-open" type="time" invalid={!!errors.opening_time} {...register('opening_time')} />
                            </Field>
                            <Field id="ee-close" label="Fermeture" required={v.appointments_enabled} error={errors.closing_time?.message}>
                                <Input id="ee-close" type="time" invalid={!!errors.closing_time} {...register('closing_time')} />
                            </Field>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <Field id="ee-slot" label="Durée du créneau (min)" required error={errors.slot_minutes?.message} hint="5 à 240">
                                <Input id="ee-slot" inputMode="numeric" placeholder="Ex. 30" invalid={!!errors.slot_minutes} {...register('slot_minutes')} />
                            </Field>
                            <Field id="ee-capacity" label="Étudiants par créneau" required error={errors.capacity?.message} hint="1 à 100">
                                <Input id="ee-capacity" inputMode="numeric" placeholder="Ex. 2" invalid={!!errors.capacity} {...register('capacity')} />
                            </Field>
                            <Field id="ee-lead" label="Délai minimal (heures)" required error={errors.lead_hours?.message} hint="Avant le rendez-vous">
                                <Input id="ee-lead" inputMode="numeric" placeholder="Ex. 24" invalid={!!errors.lead_hours} {...register('lead_hours')} />
                            </Field>
                        </div>
                        <p className="text-xs text-muted">Fuseau horaire : {timezone}. Une modification des créneaux ne déplace pas les rendez-vous déjà annoncés.</p>
                    </>
                )}

                {etape === 2 && (
                    <>
                        <Note icone={<Info />}>
                            <p>
                                Les valeurs du modèle actuel sont reprises ci-dessous. Le préavis est celui du paramétrage des départs. Les modifications s’appliquent aux
                                contrats non approuvés ; les contrats approuvés restent conservés dans leur version acceptée.
                            </p>
                        </Note>
                        <div className="grid gap-4 md:grid-cols-2">
                            {terms.map((t) => (
                                <Field key={t.key} id={`ee-c-${t.key}`} label={t.label} required error={erreurContrat(t.key)}>
                                    <Textarea
                                        id={`ee-c-${t.key}`}
                                        rows={2}
                                        maxLength={3000}
                                        invalid={!!erreurContrat(t.key)}
                                        {...register(`contract.${t.key}` as Path<EntreeValeurs>)}
                                    />
                                </Field>
                            ))}
                        </div>
                    </>
                )}

                {etape === 3 && (
                    <div className="space-y-4">
                        <Recap
                            lignes={[
                                { label: 'Parcours automatique', valeur: v.enabled ? <Badge variant="success">Activé</Badge> : <Badge variant="neutral">Désactivé</Badge> },
                                { label: 'Délai de réponse', valeur: `${v.proposal_hours} heure(s)` },
                                {
                                    label: 'Rendez-vous automatiques',
                                    valeur: v.appointments_enabled ? <Badge variant="success">Activés</Badge> : <Badge variant="neutral">Désactivés</Badge>,
                                },
                                ...(v.appointments_enabled
                                    ? [
                                          { label: 'À partir du', valeur: dateFr(v.start_date) },
                                          { label: 'Jours d’accueil', valeur: jours.join(', ') || '—' },
                                          { label: 'Horaires', valeur: `${v.opening_time || '—'} – ${v.closing_time || '—'}` },
                                          { label: 'Créneaux', valeur: `${v.slot_minutes} min · ${v.capacity} étudiant(s) · ${v.lead_hours} h de délai` },
                                          { label: 'Lieu', valeur: v.location || '—' },
                                      ]
                                    : []),
                                { label: 'Conditions du contrat', valeur: `${nombre(terms.length)} clauses renseignées` },
                            ]}
                        />
                        {Object.keys(errors).length > 0 && (
                            <p role="alert" className="flex items-center gap-2 text-sm font-medium text-red-600">
                                <CircleAlert className="size-4" aria-hidden /> Certaines informations sont incomplètes : revenez aux étapes précédentes.
                            </p>
                        )}
                    </div>
                )}
            </div>
            <PiedAssistant
                etape={etape}
                derniere={derniere}
                envoi={isSubmitting}
                onAnnuler={() => {
                    reset(valeursInitiales);
                    aller(0);
                }}
                onPrecedent={() => aller(etape - 1)}
                libelle="Enregistrer les paramètres"
            />
        </form>
    );
}
