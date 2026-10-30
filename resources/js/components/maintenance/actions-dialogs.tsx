import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Check, ClipboardPlus, Loader2, Star, UserCheck } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { libelleSpecialite, RESULTATS, type Maintenancier } from '@/components/maintenance/shared';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { DateTimePicker } from '@/components/ui/date-time-picker';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { ChoixCarte, dateHeure, Encadre, Lignes, maintenantLocal, repartirErreurs } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';

/** Pied commun des assistants : Annuler | Précédent · Suivant / Valider. */
function PiedAssistant({
    etape,
    total,
    enCours,
    onClose,
    onPrecedent,
    onSuivant,
    libelle,
    icone,
}: {
    etape: number;
    total: number;
    enCours: boolean;
    onClose: () => void;
    onPrecedent: () => void;
    onSuivant: () => void;
    libelle: string;
    icone: ReactNode;
}) {
    return (
        <DialogFooter className="sm:justify-between">
            <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={enCours}>
                Annuler
            </Button>
            <div className="flex flex-col-reverse gap-2 sm:flex-row">
                {etape > 0 && (
                    <Button variant="outline" size="sm" className="h-10" onClick={onPrecedent} disabled={enCours}>
                        <ArrowLeft className="size-4" aria-hidden /> Précédent
                    </Button>
                )}
                {etape < total - 1 ? (
                    <Button key="suivant" size="sm" className="h-10" onClick={onSuivant}>
                        Suivant <ArrowRight className="size-4" aria-hidden />
                    </Button>
                ) : (
                    <Button key="valider" type="submit" size="sm" className="h-10" disabled={enCours}>
                        {enCours ? <Loader2 className="size-4 animate-spin" aria-hidden /> : icone}
                        {libelle}
                    </Button>
                )}
            </div>
        </DialogFooter>
    );
}

/* ------------------------------------------------------------------ Assignation */

const schemaAssignation = z
    .object({
        assigne_a: z.string().min(1, 'Choisissez le maintenancier.'),
        date_debut_intervention: z.string().min(1, 'Indiquez le début prévu.'),
        date_fin_intervention: z.string(),
        instructions: z.string().max(2000, '2 000 caractères au maximum.'),
        notification_envoyee: z.boolean(),
    })
    .refine((v) => !v.date_fin_intervention || v.date_fin_intervention >= v.date_debut_intervention, { path: ['date_fin_intervention'], message: 'La fin prévue doit être postérieure au début.' });

type Assignation = z.infer<typeof schemaAssignation>;
const CHAMPS_ASSIGNATION: (keyof Assignation)[][] = [['assigne_a'], ['date_debut_intervention', 'date_fin_intervention', 'instructions', 'notification_envoyee']];

/** Assigner un maintenancier (action `assigner` : l'ordre passe « en cours »). */
export function AssignerDialog({ open, onClose, url, maintenanciers }: { open: boolean; onClose: () => void; url: string; maintenanciers: Maintenancier[] }) {
    const [etape, setEtape] = useState(0);
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const {
        control,
        register,
        handleSubmit,
        reset,
        trigger,
        setValue,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<Assignation>({
        resolver: zodResolver(schemaAssignation),
        mode: 'onTouched',
        defaultValues: { assigne_a: '', date_debut_intervention: '', date_fin_intervention: '', instructions: '', notification_envoyee: true },
    });
    const [assigne, notifier, debut] = useWatch({ control, name: ['assigne_a', 'notification_envoyee', 'date_debut_intervention'] });
    const technicien = maintenanciers.find((m) => m.id === assigne);

    useEffect(() => {
        if (open) {
            reset({ assigne_a: '', date_debut_intervention: maintenantLocal(), date_fin_intervention: '', instructions: '', notification_envoyee: true });
            setEtape(0);
            setErreurServeur(null);
        }
    }, [open, reset]);

    const onSubmit = (valeurs: Assignation) => {
        setErreurServeur(null);
        return envoyer(url, { ...valeurs, notification_envoyee: valeurs.notification_envoyee ? 1 : 0 }, {
            onSuccess: onClose,
            onError: (erreurs) => setErreurServeur(repartirErreurs(erreurs, CHAMPS_ASSIGNATION, setError, setEtape)),
        });
    };

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-xl">
                <form onSubmit={handleSubmit(onSubmit, (fautes) => setEtape(fautes.assigne_a ? 0 : 1))} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Assigner un maintenancier</DialogTitle>
                        <DialogDescription>Étape {etape + 1} sur 2 · L’ordre passera « en cours ».</DialogDescription>
                    </DialogHeader>
                    <Stepper steps={['Maintenancier', 'Planning']} current={etape} reached={etape} onStepClick={(index) => index < etape && setEtape(index)} />
                    <DialogBody className="space-y-4">
                        {erreurServeur && <Encadre ton="danger">{erreurServeur}</Encadre>}
                        {etape === 0 && (
                            <>
                                <Field id="as-assigne_a" label="Maintenancier" required error={errors.assigne_a?.message}>
                                    <Controller
                                        control={control}
                                        name="assigne_a"
                                        render={({ field }) => (
                                            <Combobox
                                                id="as-assigne_a"
                                                value={field.value}
                                                onChange={field.onChange}
                                                onBlur={field.onBlur}
                                                options={maintenanciers.map((m) => ({ value: m.id, label: m.name, description: [libelleSpecialite(m.specialite), m.cite].filter(Boolean).join(' · ') }))}
                                                placeholder="Sélectionner un maintenancier"
                                                emptyMessage="Aucun maintenancier enregistré."
                                                invalid={!!errors.assigne_a}
                                            />
                                        )}
                                    />
                                </Field>
                                {technicien && (
                                    <Lignes
                                        lignes={[
                                            ['Spécialité', libelleSpecialite(technicien.specialite)],
                                            ['Téléphone', technicien.tel ?? ''],
                                            ['E-mail', technicien.email ?? ''],
                                            ['Résidence', technicien.cite ?? 'Toutes'],
                                        ]}
                                    />
                                )}
                            </>
                        )}
                        {etape === 1 && (
                            <>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field id="as-date_debut_intervention" label="Début prévu" required error={errors.date_debut_intervention?.message}>
                                        <Controller
                                            control={control}
                                            name="date_debut_intervention"
                                            render={({ field }) => (
                                                <DateTimePicker id="as-date_debut_intervention" value={field.value} onChange={field.onChange} onBlur={field.onBlur} invalid={!!errors.date_debut_intervention} />
                                            )}
                                        />
                                    </Field>
                                    <Field id="as-date_fin_intervention" label="Fin prévue" error={errors.date_fin_intervention?.message}>
                                        <Controller
                                            control={control}
                                            name="date_fin_intervention"
                                            render={({ field }) => (
                                                <DateTimePicker
                                                    id="as-date_fin_intervention"
                                                    value={field.value}
                                                    onChange={field.onChange}
                                                    onBlur={field.onBlur}
                                                    min={debut || undefined}
                                                    heureParDefaut="17:00"
                                                    invalid={!!errors.date_fin_intervention}
                                                />
                                            )}
                                        />
                                    </Field>
                                </div>
                                <Field id="as-instructions" label="Instructions" error={errors.instructions?.message}>
                                    <Textarea id="as-instructions" rows={3} placeholder="Consignes particulières pour le maintenancier…" invalid={!!errors.instructions} {...register('instructions')} />
                                </Field>
                                <ChoixCarte
                                    role="checkbox"
                                    actif={notifier}
                                    onClick={() => setValue('notification_envoyee', !notifier)}
                                    titre="Notifier le maintenancier"
                                    description="Indique que le maintenancier a été prévenu de l’intervention."
                                />
                            </>
                        )}
                    </DialogBody>
                    <PiedAssistant
                        etape={etape}
                        total={2}
                        enCours={isSubmitting}
                        onClose={onClose}
                        onPrecedent={() => setEtape(0)}
                        onSuivant={async () => (await trigger(CHAMPS_ASSIGNATION[0])) && setEtape(1)}
                        libelle="Assigner"
                        icone={<UserCheck className="size-4" aria-hidden />}
                    />
                </form>
            </DialogContent>
        </Dialog>
    );
}

/* ------------------------------------------------------------------ Intervention */

const schemaIntervention = z.object({
    date_intervention: z
        .string()
        .min(1, 'Indiquez la date de l’intervention.')
        .refine((valeur) => valeur <= maintenantLocal(), 'L’intervention ne peut pas être dans le futur.'),
    duree: z
        .string()
        .min(1, 'Indiquez la durée.')
        .refine((valeur) => Number.isInteger(Number(valeur)) && Number(valeur) >= 1, 'Durée en minutes (nombre entier ≥ 1).'),
    resultat: z.enum(['resolu', 'partiellement_resolu', 'non_resolu', 'necessite_suivi'], { error: 'Choisissez le résultat.' }),
    description: z.string().trim().min(5, 'Décrivez l’intervention réalisée.').max(1000, '1 000 caractères au maximum.'),
    remarques: z.string().max(2000, '2 000 caractères au maximum.'),
});

type Intervention = z.infer<typeof schemaIntervention>;
const CHAMPS_INTERVENTION: (keyof Intervention)[][] = [['date_intervention', 'duree', 'resultat', 'description', 'remarques'], []];

/** Ajouter une intervention à l'ordre (action `intervention.store`). */
export function InterventionDialog({ open, onClose, url, ordreId }: { open: boolean; onClose: () => void; url: string; ordreId: string }) {
    const [etape, setEtape] = useState(0);
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const {
        control,
        register,
        handleSubmit,
        reset,
        trigger,
        setValue,
        setError,
        getValues,
        formState: { errors, isSubmitting },
    } = useForm<Intervention>({ resolver: zodResolver(schemaIntervention), mode: 'onTouched', defaultValues: { date_intervention: '', duree: '', resultat: 'resolu', description: '', remarques: '' } });
    const resultat = useWatch({ control, name: 'resultat' });

    useEffect(() => {
        if (open) {
            reset({ date_intervention: maintenantLocal(), duree: '', resultat: 'resolu', description: '', remarques: '' });
            setEtape(0);
            setErreurServeur(null);
        }
    }, [open, reset]);

    const onSubmit = (valeurs: Intervention) => {
        setErreurServeur(null);
        return envoyer(url, { ...valeurs, id_ordres_travail: ordreId }, {
            onSuccess: onClose,
            onError: (erreurs) => setErreurServeur(repartirErreurs(erreurs, CHAMPS_INTERVENTION, setError, setEtape)),
        });
    };

    const v = getValues();

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-xl">
                <form onSubmit={handleSubmit(onSubmit, () => setEtape(0))} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Ajouter une intervention</DialogTitle>
                        <DialogDescription>
                            Étape {etape + 1} sur 2 · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                        </DialogDescription>
                    </DialogHeader>
                    <Stepper steps={['Intervention', 'Vérification']} current={etape} reached={etape} onStepClick={(index) => index < etape && setEtape(index)} />
                    <DialogBody className="space-y-4">
                        {erreurServeur && <Encadre ton="danger">{erreurServeur}</Encadre>}
                        {etape === 0 && (
                            <>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field id="in-date_intervention" label="Date et heure" required error={errors.date_intervention?.message}>
                                        <Controller
                                            control={control}
                                            name="date_intervention"
                                            render={({ field }) => (
                                                <DateTimePicker id="in-date_intervention" value={field.value} onChange={field.onChange} onBlur={field.onBlur} max={maintenantLocal()} invalid={!!errors.date_intervention} />
                                            )}
                                        />
                                    </Field>
                                    <Field id="in-duree" label="Durée (minutes)" required error={errors.duree?.message}>
                                        <Input id="in-duree" type="number" min={1} step={1} inputMode="numeric" placeholder="Ex. 45" invalid={!!errors.duree} {...register('duree')} />
                                    </Field>
                                </div>
                                <fieldset>
                                    <legend className="mb-2 text-sm font-medium text-gray-800">
                                        Résultat <span className="text-red-600">*</span>
                                    </legend>
                                    <div role="radiogroup" className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        {RESULTATS.map((option) => (
                                            <ChoixCarte key={option.value} actif={resultat === option.value} onClick={() => setValue('resultat', option.value)} titre={option.label} />
                                        ))}
                                    </div>
                                </fieldset>
                                <Field id="in-description" label="Travaux réalisés" required error={errors.description?.message}>
                                    <Textarea id="in-description" rows={3} placeholder="Décrivez l’intervention réalisée…" invalid={!!errors.description} {...register('description')} />
                                </Field>
                                <Field id="in-remarques" label="Remarques" error={errors.remarques?.message}>
                                    <Textarea id="in-remarques" rows={2} invalid={!!errors.remarques} {...register('remarques')} />
                                </Field>
                            </>
                        )}
                        {etape === 1 && (
                            <>
                                <Lignes
                                    lignes={[
                                        ['Date', dateHeure(v.date_intervention)],
                                        ['Durée', `${nombre(v.duree)} min`],
                                        ['Résultat', RESULTATS.find((r) => r.value === v.resultat)?.label ?? ''],
                                        ['Travaux', <span className="whitespace-pre-line">{v.description}</span>],
                                        ['Remarques', v.remarques],
                                    ]}
                                />
                                <Encadre>L’intervention est ajoutée à la chronologie. Pour clore l’ordre, utilisez ensuite « Marquer comme terminé ».</Encadre>
                            </>
                        )}
                    </DialogBody>
                    <PiedAssistant
                        etape={etape}
                        total={2}
                        enCours={isSubmitting}
                        onClose={onClose}
                        onPrecedent={() => setEtape(0)}
                        onSuivant={async () => (await trigger(CHAMPS_INTERVENTION[0])) && setEtape(1)}
                        libelle="Enregistrer l’intervention"
                        icone={<ClipboardPlus className="size-4" aria-hidden />}
                    />
                </form>
            </DialogContent>
        </Dialog>
    );
}

/* ------------------------------------------------------------------ Clôture */

const schemaCloture = z.object({ solution: z.string().trim().min(5, 'Décrivez la solution apportée.').max(5000, '5 000 caractères au maximum.') });

/** Marquer l'ordre comme terminé avec la solution apportée (action `statut`, statut `termine`). */
export function ClotureDialog({ open, onClose, url }: { open: boolean; onClose: () => void; url: string }) {
    const {
        register,
        handleSubmit,
        reset,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<{ solution: string }>({ resolver: zodResolver(schemaCloture), mode: 'onTouched', defaultValues: { solution: '' } });

    useEffect(() => {
        if (open) reset({ solution: '' });
    }, [open, reset]);

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-lg">
                <form
                    onSubmit={handleSubmit((valeurs) => envoyer(url, { statut: 'termine', ...valeurs }, { setError, champs: ['solution'], onSuccess: onClose }))}
                    noValidate
                    className="flex min-h-0 flex-col"
                >
                    <DialogHeader>
                        <DialogTitle>Marquer comme terminé</DialogTitle>
                        <DialogDescription>La date de résolution est enregistrée et la chambre est remise à disposition si aucun autre ordre n’est ouvert.</DialogDescription>
                    </DialogHeader>
                    <DialogBody className="space-y-4">
                        <Field id="cl-solution" label="Solution apportée" required error={errors.solution?.message}>
                            <Textarea id="cl-solution" rows={4} placeholder="Pièce remplacée, réglage effectué…" invalid={!!errors.solution} {...register('solution')} />
                        </Field>
                    </DialogBody>
                    <DialogFooter>
                        <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={isSubmitting}>
                            Annuler
                        </Button>
                        <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                            {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />}
                            Terminer l’ordre
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/* ------------------------------------------------------------------ Satisfaction */

const schemaAvis = z.object({
    taux_satisfaction: z.number({ error: 'Choisissez une note.' }).int().min(1, 'Choisissez une note.').max(5),
    commentaire_satisfaction: z.string().max(2000, '2 000 caractères au maximum.'),
});

type Avis = z.infer<typeof schemaAvis>;

/** Enregistrer l'avis du résident sur la dernière intervention (action `satisfaction`). */
export function SatisfactionDialog({ open, onClose, url, ordreId }: { open: boolean; onClose: () => void; url: string; ordreId: string }) {
    const {
        control,
        register,
        handleSubmit,
        reset,
        setValue,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<Avis>({ resolver: zodResolver(schemaAvis), mode: 'onTouched', defaultValues: { taux_satisfaction: 0, commentaire_satisfaction: '' } });
    const note = useWatch({ control, name: 'taux_satisfaction' });
    const [survol, setSurvol] = useState(0);

    useEffect(() => {
        if (open) reset({ taux_satisfaction: 0, commentaire_satisfaction: '' });
    }, [open, reset]);

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-lg">
                <form
                    onSubmit={handleSubmit((valeurs) =>
                        envoyer(url, { ot_id: ordreId, ...valeurs }, { setError, champs: ['taux_satisfaction', 'commentaire_satisfaction'], onSuccess: onClose }),
                    )}
                    noValidate
                    className="flex min-h-0 flex-col"
                >
                    <DialogHeader>
                        <DialogTitle>Avis du résident</DialogTitle>
                        <DialogDescription>Note de 1 à 5 recueillie auprès du résident sur l’intervention.</DialogDescription>
                    </DialogHeader>
                    <DialogBody className="space-y-4">
                        <fieldset>
                            <legend className="mb-2 text-sm font-medium text-gray-800">
                                Note <span className="text-red-600">*</span>
                            </legend>
                            <div role="radiogroup" className="flex gap-1" onMouseLeave={() => setSurvol(0)}>
                                {[1, 2, 3, 4, 5].map((valeur) => (
                                    <button
                                        key={valeur}
                                        type="button"
                                        role="radio"
                                        aria-checked={note === valeur}
                                        aria-label={`${valeur} sur 5`}
                                        onMouseEnter={() => setSurvol(valeur)}
                                        onClick={() => setValue('taux_satisfaction', valeur, { shouldValidate: true })}
                                        className="cursor-pointer rounded-brand p-1 focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                                    >
                                        <Star className={cn('size-7', valeur <= (survol || note) ? 'fill-accent text-accent' : 'text-gray-300')} strokeWidth={1.25} aria-hidden />
                                    </button>
                                ))}
                            </div>
                            {errors.taux_satisfaction?.message && <p className="mt-1.5 text-xs font-medium text-red-600">{errors.taux_satisfaction.message}</p>}
                        </fieldset>
                        <Field id="sa-commentaire" label="Commentaire" error={errors.commentaire_satisfaction?.message}>
                            <Textarea id="sa-commentaire" rows={3} placeholder="Avis du résident sur l’intervention…" invalid={!!errors.commentaire_satisfaction} {...register('commentaire_satisfaction')} />
                        </Field>
                    </DialogBody>
                    <DialogFooter>
                        <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={isSubmitting}>
                            Annuler
                        </Button>
                        <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                            {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />}
                            Enregistrer l’avis
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
