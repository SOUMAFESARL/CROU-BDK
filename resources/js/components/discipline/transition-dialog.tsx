import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Check, Gavel, Loader2, Lock, Send, UserCheck, Undo2, type LucideIcon } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
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
import { aujourdhui, Encadre, Lignes, repartirErreurs, type ResidentOption } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';
import { dateFr } from '@/lib/text';

export type ActionRapport = 'transmettre' | 'prendre_en_charge' | 'retourner' | 'decider' | 'cloturer';

export const ACTIONS: Record<ActionRapport, { label: string; titre: string; aide: string; commentaire: string; commentaireRequis: boolean; icone: LucideIcon; bouton: string }> = {
    transmettre: { label: 'Transmettre', titre: 'Transmettre au Service Sécurité', aide: 'Le dossier rejoint la file de traitement du Service Sécurité.', commentaire: 'Note de transmission', commentaireRequis: false, icone: Send, bouton: 'Transmettre' },
    prendre_en_charge: { label: 'Prendre en charge', titre: 'Prendre en charge le dossier', aide: 'Le rapport passe en cours d’analyse sous votre responsabilité.', commentaire: 'Premières observations', commentaireRequis: false, icone: UserCheck, bouton: 'Prendre en charge' },
    retourner: { label: 'Retourner', titre: 'Retourner le rapport', aide: 'Le dossier est renvoyé pour correction ou complément d’information.', commentaire: 'Motif du retour', commentaireRequis: true, icone: Undo2, bouton: 'Retourner le dossier' },
    decider: { label: 'Décider', titre: 'Rendre une décision', aide: 'Formalisez la mesure retenue avant notification.', commentaire: 'Motifs et observations', commentaireRequis: true, icone: Gavel, bouton: 'Enregistrer la décision' },
    cloturer: { label: 'Clôturer', titre: 'Clôturer le dossier', aide: 'Le traitement est terminé ; l’historique reste consultable.', commentaire: 'Note de clôture', commentaireRequis: false, icone: Lock, bouton: 'Clôturer' },
};

const EFFETS: Record<string, string> = {
    expulsion: 'Expulsion : nouvelles admissions et attributions bloquées, réservations annulées et sortie préparée. La place reste occupée jusqu’à l’état des lieux de sortie.',
    suspension: 'Suspension immédiate : nouvelles admissions, attributions et confirmations bloquées jusqu’à la date indiquée incluse. Le séjour existant reste enregistré.',
};

export type RapportCible = { numero: string; nature: string; resident: string | null; url_transition: string | null };

type Props = {
    rapport: RapportCible | null;
    action: ActionRapport | null;
    onClose: () => void;
    sanctions: { value: string; label: string }[];
    residents: ResidentOption[];
};

const construireSchema = (action: ActionRapport | null) =>
    z
        .object({
            commentaire: z.string().max(5000, '5 000 caractères au maximum.'),
            decision: z.string().max(5000, '5 000 caractères au maximum.'),
            sanction: z.string(),
            etudiant_sanctionne_id: z.string(),
            sanction_fin: z.string(),
        })
        .superRefine((v, ctx) => {
            if (action && ACTIONS[action].commentaireRequis && !v.commentaire.trim()) ctx.addIssue({ code: 'custom', path: ['commentaire'], message: 'Ce champ est obligatoire.' });
            if (action !== 'decider') return;
            if (!v.sanction) ctx.addIssue({ code: 'custom', path: ['sanction'], message: 'Choisissez la décision.' });
            if (!v.decision.trim()) ctx.addIssue({ code: 'custom', path: ['decision'], message: 'Décrivez la décision retenue.' });
            if (v.sanction && v.sanction !== 'aucune' && !v.etudiant_sanctionne_id) ctx.addIssue({ code: 'custom', path: ['etudiant_sanctionne_id'], message: 'Désignez l’étudiant sanctionné.' });
            if (v.sanction === 'suspension') {
                if (!v.sanction_fin) ctx.addIssue({ code: 'custom', path: ['sanction_fin'], message: 'Indiquez la fin de la suspension.' });
                else if (v.sanction_fin < aujourdhui()) ctx.addIssue({ code: 'custom', path: ['sanction_fin'], message: 'La date ne peut pas être passée.' });
            }
        });

type Valeurs = { commentaire: string; decision: string; sanction: string; etudiant_sanctionne_id: string; sanction_fin: string };
const VIDES: Valeurs = { commentaire: '', decision: '', sanction: '', etudiant_sanctionne_id: '', sanction_fin: '' };
const CHAMPS_ETAPES: (keyof Valeurs)[][] = [['sanction', 'etudiant_sanctionne_id', 'sanction_fin', 'decision'], ['commentaire']];

/** Étape suivante du workflow d'un rapport (action `incidents.transition` : transitions vérifiées côté serveur). */
export function TransitionDialog({ rapport, action, onClose, sanctions, residents }: Props) {
    const schema = useMemo(() => construireSchema(action), [action]);
    const decider = action === 'decider';
    const etapes = decider ? ['Décision', 'Motifs'] : [];
    const [etape, setEtape] = useState(0);
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const {
        control,
        register,
        handleSubmit,
        reset,
        trigger,
        setError,
        getValues,
        formState: { errors, isSubmitting },
    } = useForm<Valeurs>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: VIDES });
    const sanction = useWatch({ control, name: 'sanction' });

    useEffect(() => {
        if (action) {
            reset(VIDES);
            setEtape(0);
            setErreurServeur(null);
        }
    }, [action, rapport, reset]);

    const config = action ? ACTIONS[action] : null;
    const Icone = config?.icone ?? Check;
    const residentsUniques = useMemo(() => Array.from(new Map(residents.map((r) => [r.id, r])).values()), [residents]);

    const onSubmit = (valeurs: Valeurs) => {
        if (!rapport?.url_transition || !action) return;
        setErreurServeur(null);
        const donnees = decider ? { action, ...valeurs } : { action, commentaire: valeurs.commentaire };
        return envoyer(rapport.url_transition, donnees, {
            method: 'patch',
            onSuccess: onClose,
            onError: (erreurs) => setErreurServeur(repartirErreurs(erreurs, decider ? CHAMPS_ETAPES : [['commentaire']], setError, setEtape)),
        });
    };

    const v = getValues();
    const derniere = !decider || etape === etapes.length - 1;

    return (
        <Dialog open={action !== null && rapport !== null} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-xl">
                <form
                    onSubmit={handleSubmit(onSubmit, (fautes) => decider && setEtape(Math.max(0, CHAMPS_ETAPES.findIndex((champs) => champs.some((champ) => fautes[champ])))))}
                    noValidate
                    className="flex min-h-0 flex-col"
                >
                    <DialogHeader>
                        <div className="flex items-start gap-3">
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                                <Icone {...ICON} />
                            </span>
                            <div className="min-w-0">
                                <DialogTitle>{config?.titre}</DialogTitle>
                                <DialogDescription>
                                    {config?.aide}
                                    {decider && ` Étape ${etape + 1} sur ${etapes.length}.`}
                                </DialogDescription>
                            </div>
                        </div>
                    </DialogHeader>
                    {decider && <Stepper steps={etapes} current={etape} reached={etape} onStepClick={(index) => index < etape && setEtape(index)} />}
                    <DialogBody className="space-y-4">
                        {erreurServeur && <Encadre ton="danger">{erreurServeur}</Encadre>}
                        {rapport && (
                            <Lignes
                                lignes={[
                                    ['Rapport', rapport.numero],
                                    ['Résident concerné', rapport.resident ?? ''],
                                    ['Nature', rapport.nature],
                                ]}
                            />
                        )}

                        {decider && etape === 0 && (
                            <>
                                <Field id="tr-sanction" label="Sanction à appliquer" required error={errors.sanction?.message}>
                                    <Controller
                                        control={control}
                                        name="sanction"
                                        render={({ field }) => (
                                            <Combobox id="tr-sanction" value={field.value} onChange={field.onChange} onBlur={field.onBlur} options={sanctions} placeholder="Choisir une décision" invalid={!!errors.sanction} />
                                        )}
                                    />
                                </Field>
                                {sanction && sanction !== 'aucune' && (
                                    <Field
                                        id="tr-etudiant_sanctionne_id"
                                        label="Étudiant sanctionné"
                                        required
                                        error={errors.etudiant_sanctionne_id?.message}
                                        hint="Désignez explicitement la personne sanctionnée : le déclarant n’est pas nécessairement la personne mise en cause."
                                    >
                                        <Controller
                                            control={control}
                                            name="etudiant_sanctionne_id"
                                            render={({ field }) => (
                                                <Combobox
                                                    id="tr-etudiant_sanctionne_id"
                                                    value={field.value}
                                                    onChange={field.onChange}
                                                    onBlur={field.onBlur}
                                                    options={residentsUniques.map((r) => ({ value: r.id, label: r.name, description: r.matricule ?? 'Sans matricule' }))}
                                                    placeholder="Sélectionner un résident"
                                                    invalid={!!errors.etudiant_sanctionne_id}
                                                />
                                            )}
                                        />
                                    </Field>
                                )}
                                {sanction === 'suspension' && (
                                    <Field id="tr-sanction_fin" label="Suspension jusqu’au (inclus)" required error={errors.sanction_fin?.message}>
                                        <Controller
                                            control={control}
                                            name="sanction_fin"
                                            render={({ field }) => <DatePicker id="tr-sanction_fin" value={field.value} onChange={field.onChange} min={aujourdhui()} invalid={!!errors.sanction_fin} />}
                                        />
                                    </Field>
                                )}
                                {sanction && <Encadre ton={EFFETS[sanction] ? 'warning' : 'info'}>{EFFETS[sanction] ?? 'La décision et son auteur sont conservés dans l’historique.'}</Encadre>}
                                <Field id="tr-decision" label="Décision disciplinaire" required error={errors.decision?.message} hint="Mesure retenue, durée et conditions d’application.">
                                    <Textarea id="tr-decision" rows={4} invalid={!!errors.decision} placeholder="Ex. Avertissement écrit, exclusion temporaire de 7 jours…" {...register('decision')} />
                                </Field>
                            </>
                        )}

                        {(!decider || etape === 1) && (
                            <>
                                {decider && (
                                    <Lignes
                                        lignes={[
                                            ['Décision', sanctions.find((s) => s.value === v.sanction)?.label ?? ''],
                                            ...(v.sanction !== 'aucune' ? ([['Étudiant sanctionné', residentsUniques.find((r) => r.id === v.etudiant_sanctionne_id)?.name ?? '']] as [string, string][]) : []),
                                            ...(v.sanction === 'suspension' ? ([['Jusqu’au', dateFr(v.sanction_fin)]] as [string, string][]) : []),
                                        ]}
                                    />
                                )}
                                {config && (
                                    <Field id="tr-commentaire" label={config.commentaire} required={config.commentaireRequis} error={errors.commentaire?.message}>
                                        <Textarea id="tr-commentaire" rows={4} invalid={!!errors.commentaire} placeholder="Éléments ayant conduit à cette étape…" {...register('commentaire')} />
                                    </Field>
                                )}
                                <Encadre>Cette étape est enregistrée dans la piste d’audit du dossier.</Encadre>
                            </>
                        )}
                    </DialogBody>
                    <DialogFooter className="sm:justify-between">
                        <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={isSubmitting}>
                            Annuler
                        </Button>
                        <div className="flex flex-col-reverse gap-2 sm:flex-row">
                            {decider && etape > 0 && (
                                <Button variant="outline" size="sm" className="h-10" onClick={() => setEtape(0)} disabled={isSubmitting}>
                                    <ArrowLeft className="size-4" aria-hidden /> Précédent
                                </Button>
                            )}
                            {!derniere ? (
                                <Button size="sm" className="h-10" onClick={async () => (await trigger(CHAMPS_ETAPES[0])) && setEtape(1)}>
                                    Suivant <ArrowRight className="size-4" aria-hidden />
                                </Button>
                            ) : (
                                <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                                    {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />}
                                    {config?.bouton}
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
