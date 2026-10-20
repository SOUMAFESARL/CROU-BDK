import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Ban, CalendarDays, Hammer, Info, Loader2, Plus, Save, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Controller, useFieldArray, useForm, type Path } from 'react-hook-form';
import { fcfa } from '@/components/departs/shared';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { ErreurGenerale, Note, Recap, useAssistant } from '@/components/parametrage-hebergement/shared';
import { Button, buttonVariants } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { StatCard } from '@/components/ui/stat-card';
import { Stepper } from '@/components/ui/stepper';
import { envoyer } from '@/lib/inertia-form';
import { montant as montantSysteme, symboleDevise } from '@/lib/systeme';
import { dateFr, nombre, saisieMilliers } from '@/lib/text';
import { cn } from '@/lib/utils';
import { enEntier, parametresSchema, type Parametres as Valeurs } from '@/schemas/departs';

type Props = {
    settings: {
        notice_days: number;
        late_notice_penalty: number;
        unpaid_blocking_threshold: number;
        damage_retention_grid: { label: string; amount: number }[];
        updated_at: string | null;
    };
    urls: { update: string | null; departs: string | null };
};

const ETAPES = ['Préavis', 'Impayés', 'Grille des dégradations', 'Vérification'];

const CHAMPS: Path<Valeurs>[][] = [['notice_days', 'late_notice_penalty'], ['unpaid_blocking_threshold'], ['grille'], []];

const enSaisie = (n: number) => saisieMilliers(String(Math.round(n)));

/** Règles de clôture des séjours (remplace `depart-etat-des-lieux/settings.blade.php`). */
export default function ParametresCloture({ settings, urls }: Props) {
    const s = settings;

    return (
        <AdminLayout title="Paramètres de clôture">
            <PageHeader
                title="Règles de clôture des séjours"
                description="Règles appliquées automatiquement à l’enregistrement de l’état des lieux de sortie : préavis, blocage pour impayés et retenues sur la caution."
                actions={
                    urls.departs && (
                        <a href={urls.departs} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                            <ArrowLeft {...ICON} aria-hidden /> Départs et états des lieux
                        </a>
                    )
                }
            />

            <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard tone="blue" label="Préavis exigé" value={`${nombre(s.notice_days)} j`} />
                <StatCard tone="amber" label="Pénalité de préavis" value={fcfa(s.late_notice_penalty)} extra={s.late_notice_penalty > 0 ? 'Si préavis insuffisant' : 'Aucune'} />
                <StatCard tone="red" label="Seuil de blocage des impayés" value={s.unpaid_blocking_threshold > 0 ? fcfa(s.unpaid_blocking_threshold) : 'Désactivé'} />
                <StatCard tone="plum" label="Dégradations dans la grille" value={nombre(s.damage_retention_grid.length)} extra={s.updated_at ? `Modifié le ${dateFr(s.updated_at)}` : undefined} />
            </div>

            {urls.update ? (
                <Formulaire settings={s} url={urls.update} departs={urls.departs} />
            ) : (
                <p className="rounded-brand border bg-white p-6 text-sm text-muted">Vous pouvez consulter ces paramètres mais pas les modifier.</p>
            )}
        </AdminLayout>
    );
}

function Formulaire({ settings, url, departs }: { settings: Props['settings']; url: string; departs: string | null }) {
    const initiales = useMemo<Valeurs>(
        () => ({
            notice_days: String(settings.notice_days),
            late_notice_penalty: enSaisie(settings.late_notice_penalty),
            unpaid_blocking_threshold: enSaisie(settings.unpaid_blocking_threshold),
            grille: settings.damage_retention_grid.map((r) => ({ label: r.label, amount: enSaisie(r.amount) })),
        }),
        [settings],
    );
    const [erreur, setErreur] = useState<string | null>(null);
    const {
        register,
        control,
        trigger,
        watch,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<Valeurs>({ resolver: zodResolver(parametresSchema), mode: 'onTouched', defaultValues: initiales });
    const grille = useFieldArray({ control, name: 'grille' });
    const { etape, atteinte, aller, allerA, toutValider, derniere } = useAssistant<Valeurs>(CHAMPS, trigger, true);
    const v = watch();

    const enregistrer = (valeurs: Valeurs) =>
        envoyer<Valeurs>(
            url,
            {
                notice_days: Number(valeurs.notice_days),
                late_notice_penalty: enEntier(valeurs.late_notice_penalty),
                unpaid_blocking_threshold: enEntier(valeurs.unpaid_blocking_threshold),
                damage_labels: valeurs.grille.map((r) => r.label.trim()),
                damage_amounts: valeurs.grille.map((r) => enEntier(r.amount)),
            },
            {
                method: 'put',
                onError: (erreurs) => {
                    // `damage_labels.2` → `grille.2.label` ; retour à l'étape du premier champ fautif.
                    let fautive = -1;
                    const autres: string[] = [];
                    Object.entries(erreurs).forEach(([cle, message]) => {
                        const [racine, rang] = cle.split('.');
                        const champ = (racine === 'damage_labels' ? `grille.${rang}.label` : racine === 'damage_amounts' ? `grille.${rang}.amount` : racine) as Path<Valeurs>;
                        const index = CHAMPS.findIndex((liste) => liste.some((c) => champ === c || champ.startsWith(`${c}.`)));
                        if (index < 0) {
                            autres.push(message);
                            return;
                        }
                        setError(champ, { type: 'server', message });
                        fautive = fautive < 0 ? index : Math.min(fautive, index);
                    });
                    if (fautive >= 0) aller(fautive);
                    setErreur(autres.length ? autres.join(' ') : null);
                },
            },
        );

    const montant = (nom: Path<Valeurs>, id: string, invalide: boolean, placeholder: string) => (
        <Controller
            control={control}
            name={nom}
            render={({ field }) => (
                <Input
                    id={id}
                    inputMode="numeric"
                    placeholder={placeholder}
                    invalid={invalide}
                    trailing={<span className="text-xs text-muted">{symboleDevise()}</span>}
                    value={field.value as string}
                    onBlur={field.onBlur}
                    onChange={(event) => field.onChange(saisieMilliers(event.target.value))}
                />
            )}
        />
    );

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
                <p className="text-xs text-muted">
                    Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                </p>

                {etape === 0 && (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field id="pc-preavis" label="Durée du préavis (jours)" required error={errors.notice_days?.message} hint="Entre 0 et 365 jours.">
                                <Input id="pc-preavis" inputMode="numeric" maxLength={3} placeholder="Ex. 30" icon={<CalendarDays />} invalid={!!errors.notice_days} {...register('notice_days')} />
                            </Field>
                            <Field id="pc-penalite" label="Pénalité de préavis" required error={errors.late_notice_penalty?.message} hint="0 pour ne pas appliquer de pénalité.">
                                {montant('late_notice_penalty', 'pc-penalite', !!errors.late_notice_penalty, 'Ex. 10 000')}
                            </Field>
                        </div>
                        <Note icone={<Info />}>
                            <p>Le préavis est compté entre la date de la demande de sortie et la date de l’état des lieux de sortie. S’il est plus court que la durée fixée, la pénalité est retenue sur la caution.</p>
                        </Note>
                    </>
                )}

                {etape === 1 && (
                    <>
                        <div className="max-w-sm">
                            <Field id="pc-seuil" label="Seuil d’impayés bloquant la clôture" required error={errors.unpaid_blocking_threshold?.message} hint="0 pour ne jamais bloquer.">
                                {montant('unpaid_blocking_threshold', 'pc-seuil', !!errors.unpaid_blocking_threshold, 'Ex. 50 000')}
                            </Field>
                        </div>
                        <Note icone={<Ban />} ton="attention">
                            <p>Quand les loyers impayés du résident atteignent ce seuil, l’état des lieux de sortie est refusé : le solde doit d’abord être régularisé.</p>
                        </Note>
                    </>
                )}

                {etape === 2 && (
                    <>
                        <Note icone={<Hammer />}>
                            <p>
                                Un élément constaté en <strong className="font-medium">mauvais état</strong> à la sortie entraîne la retenue indiquée lorsque son nom correspond à une ligne de la grille (ex. « Serrure » pour
                                l’élément « Serrure de la porte »). Les lignes à {montantSysteme(0)} ne retiennent rien.
                            </p>
                        </Note>
                        {grille.fields.length === 0 ? (
                            <p className="rounded-brand border border-dashed px-4 py-8 text-center text-sm text-muted">Aucune dégradation : aucune retenue ne sera appliquée pour les éléments en mauvais état.</p>
                        ) : (
                            <ul className="space-y-2">
                                {grille.fields.map((ligne, index) => {
                                    const e = errors.grille?.[index];
                                    return (
                                        <li key={ligne.id} className="grid gap-2 rounded-brand border p-3 sm:grid-cols-[minmax(0,1fr)_220px_auto] sm:items-start">
                                            <div>
                                                <label htmlFor={`pc-label-${index}`} className="sr-only">
                                                    Dégradation {index + 1}
                                                </label>
                                                <Input id={`pc-label-${index}`} placeholder="Ex. Serrure endommagée" maxLength={255} invalid={!!e?.label} {...register(`grille.${index}.label`)} />
                                                {e?.label && (
                                                    <p role="alert" className="mt-1 text-xs font-medium text-red-600">
                                                        {e.label.message}
                                                    </p>
                                                )}
                                            </div>
                                            <div>
                                                <label htmlFor={`pc-montant-${index}`} className="sr-only">
                                                    Montant retenu pour la dégradation {index + 1}
                                                </label>
                                                {montant(`grille.${index}.amount`, `pc-montant-${index}`, !!e?.amount, 'Montant')}
                                                {e?.amount && (
                                                    <p role="alert" className="mt-1 text-xs font-medium text-red-600">
                                                        {e.amount.message}
                                                    </p>
                                                )}
                                            </div>
                                            <Button variant="ghost" size="icon" onClick={() => grille.remove(index)} aria-label={`Retirer la dégradation ${index + 1}`} className="size-11 text-red-700 hover:bg-red-50 hover:text-red-700">
                                                <Trash2 {...ICON} aria-hidden />
                                            </Button>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                        <Button variant="secondary" size="sm" className="h-10" onClick={() => grille.append({ label: '', amount: '0' })}>
                            <Plus {...ICON} aria-hidden /> Ajouter une dégradation
                        </Button>
                    </>
                )}

                {etape === 3 && (
                    <Recap
                        lignes={[
                            { label: 'Durée du préavis', valeur: `${nombre(Number(v.notice_days))} jour(s)` },
                            { label: 'Pénalité de préavis', valeur: fcfa(enEntier(v.late_notice_penalty)) },
                            { label: 'Seuil de blocage des impayés', valeur: enEntier(v.unpaid_blocking_threshold) > 0 ? fcfa(enEntier(v.unpaid_blocking_threshold)) : 'Désactivé' },
                            {
                                label: 'Grille des dégradations',
                                valeur:
                                    v.grille.length === 0 ? (
                                        'Aucune ligne'
                                    ) : (
                                        <ul className="space-y-0.5">
                                            {v.grille.map((r, i) => (
                                                <li key={i}>
                                                    {r.label} : {fcfa(enEntier(r.amount))}
                                                </li>
                                            ))}
                                        </ul>
                                    ),
                            },
                        ]}
                    />
                )}
            </div>
            <div className="flex flex-col-reverse gap-2 border-t px-6 py-4 sm:flex-row sm:justify-between">
                {departs ? (
                    <a href={departs} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10')}>
                        Annuler
                    </a>
                ) : (
                    <span />
                )}
                <div className="flex flex-col-reverse gap-2 sm:flex-row">
                    {etape > 0 && (
                        <Button variant="outline" size="sm" className="h-10" onClick={() => aller(etape - 1)} disabled={isSubmitting}>
                            <ArrowLeft className="size-4" aria-hidden /> Précédent
                        </Button>
                    )}
                    <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                        {derniere ? (
                            <>
                                {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Save className="size-4" aria-hidden />}
                                Enregistrer les paramètres
                            </>
                        ) : (
                            <>
                                Suivant <ArrowRight className="size-4" aria-hidden />
                            </>
                        )}
                    </Button>
                </div>
            </div>
        </form>
    );
}

