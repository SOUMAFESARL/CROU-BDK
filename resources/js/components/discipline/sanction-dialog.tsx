import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Building, BedDouble, Gavel, Houses, Loader2, User } from 'lucide-react';
import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { ICON } from '@/components/layout/app-sidebar';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { aujourdhui, ChoixCarte, Encadre, Lignes, repartirErreurs, type Structure } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';
import { dateFr } from '@/lib/text';

export const PORTEES = [
    { value: 'etudiant', label: 'Un résident précis', description: 'La sanction vise une seule personne.', icone: <User {...ICON} /> },
    { value: 'chambre', label: 'Une chambre', description: 'Tous les résidents de la chambre.', icone: <BedDouble {...ICON} /> },
    { value: 'batiment', label: 'Un bâtiment', description: 'Tous les résidents du bâtiment.', icone: <Building {...ICON} /> },
    { value: 'cite', label: 'Une cité', description: 'Tous les résidents de la cité.', icone: <Houses {...ICON} /> },
] as const;

/** Types proposés à la saisie directe (l'expulsion passe par la décision d'un rapport disciplinaire). */
const TYPES = [
    { value: 'avertissement', label: 'Avertissement' },
    { value: 'blame', label: 'Blâme' },
    { value: 'suspension', label: 'Suspension' },
] as const;

const requis = (message: string) => z.string().trim().min(1, message);

const schema = z
    .object({
        scope_type: z.enum(['etudiant', 'chambre', 'batiment', 'cite']),
        cite_id: requis('Choisissez la cité.'),
        batiment_id: z.string(),
        chambre_id: z.string(),
        etudiant_id: z.string(),
        type: z.enum(['avertissement', 'blame', 'suspension'], { error: 'Choisissez le type de sanction.' }),
        date_decision: requis('Indiquez la date de la décision.'),
        date_debut: z.string(),
        date_fin: z.string(),
        motif: requis('Le motif est obligatoire.').max(2000, '2 000 caractères au maximum.'),
        faits_reproches: z.string().max(5000, '5 000 caractères au maximum.'),
        commentaire: z.string().max(2000, '2 000 caractères au maximum.'),
    })
    .superRefine((valeurs, ctx) => {
        if (valeurs.scope_type !== 'cite' && !valeurs.batiment_id) ctx.addIssue({ code: 'custom', path: ['batiment_id'], message: 'Choisissez le bâtiment.' });
        if (['chambre', 'etudiant'].includes(valeurs.scope_type) && !valeurs.chambre_id) ctx.addIssue({ code: 'custom', path: ['chambre_id'], message: 'Choisissez la chambre.' });
        if (valeurs.scope_type === 'etudiant' && !valeurs.etudiant_id) ctx.addIssue({ code: 'custom', path: ['etudiant_id'], message: 'Choisissez le résident.' });
        if (valeurs.type === 'suspension') {
            if (!valeurs.date_debut) ctx.addIssue({ code: 'custom', path: ['date_debut'], message: 'Indiquez le début de la suspension.' });
            if (!valeurs.date_fin) ctx.addIssue({ code: 'custom', path: ['date_fin'], message: 'Indiquez la fin de la suspension.' });
            if (valeurs.date_debut && valeurs.date_fin && valeurs.date_fin < valeurs.date_debut)
                ctx.addIssue({ code: 'custom', path: ['date_fin'], message: 'La fin doit être postérieure ou égale au début.' });
        }
    });

type Valeurs = z.infer<typeof schema>;

const CHAMPS_ETAPES: (keyof Valeurs)[][] = [
    ['scope_type', 'cite_id', 'batiment_id', 'chambre_id', 'etudiant_id'],
    ['type', 'date_decision', 'date_debut', 'date_fin', 'motif', 'faits_reproches', 'commentaire'],
    [],
];
const ETAPES = ['Portée', 'Décision', 'Vérification'];

const valeursInitiales = (): Valeurs => ({
    scope_type: 'etudiant',
    cite_id: '',
    batiment_id: '',
    chambre_id: '',
    etudiant_id: '',
    type: 'avertissement',
    date_decision: aujourdhui(),
    date_debut: '',
    date_fin: '',
    motif: '',
    faits_reproches: '',
    commentaire: '',
});

/** Appliquer une sanction (action `sanctions.store` inchangée : contrôles de cohérence refaits côté serveur). */
export function SanctionDialog({ open, onClose, url, structure }: { open: boolean; onClose: () => void; url: string; structure: Structure }) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
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
    } = useForm<Valeurs>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: valeursInitiales() });
    const [portee, citeId, batimentId, chambreId, type] = useWatch({ control, name: ['scope_type', 'cite_id', 'batiment_id', 'chambre_id', 'type'] });

    useEffect(() => {
        if (open) {
            reset(valeursInitiales());
            setEtape(0);
            setAtteinte(0);
            setErreurServeur(null);
        }
    }, [open, reset]);

    const batiments = useMemo(() => structure.batiments.filter((b) => b.cite_id === citeId), [structure.batiments, citeId]);
    const chambres = useMemo(() => structure.chambres.filter((c) => c.batiment_id === batimentId), [structure.chambres, batimentId]);
    const residents = useMemo(() => (structure.residents ?? []).filter((r) => r.chambre_id === chambreId), [structure.residents, chambreId]);

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
        setAtteinte((max) => Math.max(max, cible));
    };

    const onSubmit = (valeurs: Valeurs) => {
        setErreurServeur(null);
        return envoyer(url, valeurs, {
            onSuccess: onClose,
            onError: (erreurs) => setErreurServeur(repartirErreurs(erreurs, CHAMPS_ETAPES, setError, setEtape)),
        });
    };

    const v = getValues();
    const cite = structure.cites.find((c) => c.id === v.cite_id)?.name;
    const batiment = structure.batiments.find((b) => b.id === v.batiment_id)?.name;
    const chambre = structure.chambres.find((c) => c.id === v.chambre_id)?.numero;
    const resident = (structure.residents ?? []).find((r) => r.id === v.etudiant_id && r.chambre_id === v.chambre_id);

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-2xl">
                <form onSubmit={handleSubmit(onSubmit, (fautes) => setEtape(Math.max(0, CHAMPS_ETAPES.findIndex((champs) => champs.some((champ) => fautes[champ])))))} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Appliquer une sanction</DialogTitle>
                        <DialogDescription>
                            Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                        </DialogDescription>
                    </DialogHeader>
                    <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} />
                    <DialogBody className="space-y-4">
                        {erreurServeur && <Encadre ton="danger">{erreurServeur}</Encadre>}

                        {etape === 0 && (
                            <>
                                <fieldset>
                                    <legend className="mb-2 text-sm font-medium text-gray-800">
                                        Portée de la sanction <span className="text-red-600">*</span>
                                    </legend>
                                    <div role="radiogroup" className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        {PORTEES.map((option) => (
                                            <ChoixCarte
                                                key={option.value}
                                                actif={portee === option.value}
                                                onClick={() => setValue('scope_type', option.value, { shouldValidate: false })}
                                                titre={option.label}
                                                description={option.description}
                                                icone={option.icone}
                                            />
                                        ))}
                                    </div>
                                </fieldset>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field id="sa-cite_id" label="Cité" required error={errors.cite_id?.message}>
                                        <Controller
                                            control={control}
                                            name="cite_id"
                                            render={({ field }) => (
                                                <Combobox
                                                    id="sa-cite_id"
                                                    value={field.value}
                                                    onChange={(valeur) => {
                                                        field.onChange(valeur);
                                                        setValue('batiment_id', '');
                                                        setValue('chambre_id', '');
                                                        setValue('etudiant_id', '');
                                                    }}
                                                    onBlur={field.onBlur}
                                                    options={structure.cites.map((c) => ({ value: c.id, label: c.name }))}
                                                    placeholder="Sélectionner une cité"
                                                    invalid={!!errors.cite_id}
                                                />
                                            )}
                                        />
                                    </Field>
                                    {portee !== 'cite' && (
                                        <Field id="sa-batiment_id" label="Bâtiment" required error={errors.batiment_id?.message} hint={!citeId ? 'Choisissez d’abord la cité.' : undefined}>
                                            <Controller
                                                control={control}
                                                name="batiment_id"
                                                render={({ field }) => (
                                                    <Combobox
                                                        id="sa-batiment_id"
                                                        value={field.value}
                                                        onChange={(valeur) => {
                                                            field.onChange(valeur);
                                                            setValue('chambre_id', '');
                                                            setValue('etudiant_id', '');
                                                        }}
                                                        onBlur={field.onBlur}
                                                        options={batiments.map((b) => ({ value: b.id, label: b.name }))}
                                                        placeholder="Sélectionner un bâtiment"
                                                        emptyMessage="Aucun bâtiment dans cette cité."
                                                        disabled={!citeId}
                                                        invalid={!!errors.batiment_id}
                                                    />
                                                )}
                                            />
                                        </Field>
                                    )}
                                    {(portee === 'chambre' || portee === 'etudiant') && (
                                        <Field id="sa-chambre_id" label="Chambre" required error={errors.chambre_id?.message} hint="Seules les chambres ayant un résident affecté sont proposées.">
                                            <Controller
                                                control={control}
                                                name="chambre_id"
                                                render={({ field }) => (
                                                    <Combobox
                                                        id="sa-chambre_id"
                                                        value={field.value}
                                                        onChange={(valeur) => {
                                                            field.onChange(valeur);
                                                            setValue('etudiant_id', '');
                                                        }}
                                                        onBlur={field.onBlur}
                                                        options={chambres.map((c) => ({ value: c.id, label: `Chambre ${c.numero}` }))}
                                                        placeholder="Sélectionner une chambre"
                                                        emptyMessage="Aucune chambre occupée dans ce bâtiment."
                                                        disabled={!batimentId}
                                                        invalid={!!errors.chambre_id}
                                                    />
                                                )}
                                            />
                                        </Field>
                                    )}
                                    {portee === 'etudiant' && (
                                        <Field id="sa-etudiant_id" label="Résident" required error={errors.etudiant_id?.message}>
                                            <Controller
                                                control={control}
                                                name="etudiant_id"
                                                render={({ field }) => (
                                                    <Combobox
                                                        id="sa-etudiant_id"
                                                        value={field.value}
                                                        onChange={field.onChange}
                                                        onBlur={field.onBlur}
                                                        options={residents.map((r) => ({ value: r.id, label: r.name, description: r.matricule ?? 'Sans matricule' }))}
                                                        placeholder="Sélectionner un résident"
                                                        emptyMessage="Aucun résident dans cette chambre."
                                                        disabled={!chambreId}
                                                        invalid={!!errors.etudiant_id}
                                                    />
                                                )}
                                            />
                                        </Field>
                                    )}
                                </div>
                            </>
                        )}

                        {etape === 1 && (
                            <>
                                <fieldset>
                                    <legend className="mb-2 text-sm font-medium text-gray-800">
                                        Type de sanction <span className="text-red-600">*</span>
                                    </legend>
                                    <div role="radiogroup" className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                        {TYPES.map((option) => (
                                            <ChoixCarte key={option.value} actif={type === option.value} onClick={() => setValue('type', option.value)} titre={option.label} />
                                        ))}
                                    </div>
                                </fieldset>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <Field id="sa-date_decision" label="Date de décision" required error={errors.date_decision?.message}>
                                        <Controller
                                            control={control}
                                            name="date_decision"
                                            render={({ field }) => <DatePicker id="sa-date_decision" value={field.value} onChange={field.onChange} invalid={!!errors.date_decision} />}
                                        />
                                    </Field>
                                    {type === 'suspension' && (
                                        <>
                                            <Field id="sa-date_debut" label="Début de suspension" required error={errors.date_debut?.message}>
                                                <Controller
                                                    control={control}
                                                    name="date_debut"
                                                    render={({ field }) => <DatePicker id="sa-date_debut" value={field.value} onChange={field.onChange} invalid={!!errors.date_debut} />}
                                                />
                                            </Field>
                                            <Field id="sa-date_fin" label="Fin de suspension" required error={errors.date_fin?.message}>
                                                <Controller
                                                    control={control}
                                                    name="date_fin"
                                                    render={({ field }) => (
                                                        <DatePicker id="sa-date_fin" value={field.value} onChange={field.onChange} min={getValues('date_debut') || undefined} invalid={!!errors.date_fin} />
                                                    )}
                                                />
                                            </Field>
                                        </>
                                    )}
                                </div>
                                {type === 'suspension' && (
                                    <Encadre ton="warning">Suspension : nouvelles admissions, attributions et confirmations bloquées pour un résident visé, jusqu’à la date de fin incluse.</Encadre>
                                )}
                                <Field id="sa-motif" label="Motif" required error={errors.motif?.message}>
                                    <Textarea id="sa-motif" rows={3} invalid={!!errors.motif} placeholder="Ex. Non-respect répété du règlement intérieur" {...register('motif')} />
                                </Field>
                                <Field id="sa-faits_reproches" label="Faits reprochés" error={errors.faits_reproches?.message}>
                                    <Textarea id="sa-faits_reproches" rows={3} invalid={!!errors.faits_reproches} placeholder="Description factuelle : date, lieu, circonstances…" {...register('faits_reproches')} />
                                </Field>
                                <Field id="sa-commentaire" label="Commentaire interne" error={errors.commentaire?.message}>
                                    <Textarea id="sa-commentaire" rows={2} invalid={!!errors.commentaire} {...register('commentaire')} />
                                </Field>
                            </>
                        )}

                        {etape === 2 && (
                            <>
                                <Lignes
                                    lignes={[
                                        ['Portée', PORTEES.find((p) => p.value === v.scope_type)?.label ?? ''],
                                        ['Cité', cite ?? ''],
                                        ...(v.scope_type !== 'cite' ? ([['Bâtiment', batiment ?? '']] as [string, string][]) : []),
                                        ...(['chambre', 'etudiant'].includes(v.scope_type) ? ([['Chambre', chambre ? `Chambre ${chambre}` : '']] as [string, string][]) : []),
                                        ...(v.scope_type === 'etudiant' ? ([['Résident', resident ? `${resident.name} · ${resident.matricule ?? 'sans matricule'}` : '']] as [string, string][]) : []),
                                        ['Type', TYPES.find((t) => t.value === v.type)?.label ?? ''],
                                        ['Date de décision', dateFr(v.date_decision)],
                                        ...(v.type === 'suspension' ? ([['Période', `${dateFr(v.date_debut)} au ${dateFr(v.date_fin)}`]] as [string, string][]) : []),
                                        ['Motif', <span className="whitespace-pre-line">{v.motif}</span>],
                                        ...(v.faits_reproches ? ([['Faits reprochés', <span className="whitespace-pre-line">{v.faits_reproches}</span>]] as [string, ReactNode][]) : []),
                                    ]}
                                />
                                <Encadre>La sanction sera enregistrée comme active et ajoutée à l’historique ; elle pourra être levée ou annulée avec justification.</Encadre>
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
                                    <ArrowLeft className="size-4" aria-hidden /> Précédent
                                </Button>
                            )}
                            {etape < ETAPES.length - 1 ? (
                                <Button size="sm" className="h-10" onClick={() => allerA(etape + 1)}>
                                    Suivant <ArrowRight className="size-4" aria-hidden />
                                </Button>
                            ) : (
                                <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                                    {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Gavel className="size-4" aria-hidden />}
                                    Enregistrer la sanction
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
