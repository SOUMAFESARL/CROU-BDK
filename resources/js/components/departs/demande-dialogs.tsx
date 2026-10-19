import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Check, Coins, FileDown, Info, Loader2, Save, ShieldAlert, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { fcfa, StatutDemandeBadge, type Demande, type Option } from '@/components/departs/shared';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { Encadre, Lignes } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';
import { dateFr } from '@/lib/text';
import { cn } from '@/lib/utils';
import { aujourdHui } from '@/schemas/etat-des-lieux';
import {
    CHAMPS_DEMANDE,
    demandeSortieSchema,
    refusSchema,
    restitutionSchema,
    type DemandeSortie,
    type Refus,
    type Restitution,
} from '@/schemas/departs';

export type ResidentSortie = { id: string; name: string; matricule: string | null; chambre: string | null; preavis: boolean };

const ETAPES_DEMANDE = ['Résident', 'Sortie', 'Vérification'];

const VIDE: DemandeSortie = { etudiant_id: '', date_sortie_prevue: '', motif: '', observations: '' };

/** Nouvelle demande de sortie (préavis) saisie par l'administration, en 3 étapes. */
export function NouvelleDemandeDialog({ open, onClose, url, residents, motifs }: { open: boolean; onClose: () => void; url: string; residents: ResidentSortie[]; motifs: Option[] }) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const {
        control,
        register,
        trigger,
        watch,
        reset,
        setError,
        getFieldState,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<DemandeSortie>({ resolver: zodResolver(demandeSortieSchema), mode: 'onTouched', defaultValues: VIDE });

    useEffect(() => {
        if (open) {
            reset(VIDE);
            setEtape(0);
            setAtteinte(0);
        }
    }, [open, reset]);

    const v = watch();
    const resident = residents.find((r) => r.id === v.etudiant_id);

    const allerA = async (cible: number) => {
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_DEMANDE[index], { shouldFocus: true }))) {
                setEtape(index);
                return;
            }
        }
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const enregistrer = handleSubmit(async (valeurs) => {
        const ok = await envoyer<DemandeSortie>(
            url,
            { ...valeurs, observations: valeurs.observations.trim() || null },
            {
                setError,
                champs: ['etudiant_id', 'date_sortie_prevue', 'motif', 'observations'],
                onError: () => undefined,
            },
        );
        if (ok) onClose();
        else {
            // Retour à la première étape fautive (erreurs serveur placées sous les champs).
            const index = CHAMPS_DEMANDE.findIndex((champs) => champs.some((champ) => getFieldState(champ).error));
            if (index >= 0) setEtape(index);
        }
    });

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-xl">
                <DialogHeader>
                    <DialogTitle>Nouvelle demande de sortie</DialogTitle>
                    <DialogDescription>Enregistre le préavis d’un résident. La demande devra ensuite être approuvée avant l’état des lieux de sortie.</DialogDescription>
                </DialogHeader>
                <Stepper steps={ETAPES_DEMANDE} current={etape} reached={atteinte} onStepClick={(i) => void allerA(i)} />
                <form
                    noValidate
                    className="flex min-h-0 flex-1 flex-col"
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (etape < ETAPES_DEMANDE.length - 1) void allerA(etape + 1);
                        else void enregistrer();
                    }}
                >
                    <DialogBody className="space-y-4">
                        {etape === 0 && (
                            <>
                                <Field id="ds-resident" label="Résident" required error={errors.etudiant_id?.message} hint="Seuls les résidents ayant un séjour en cours sont proposés.">
                                    <Controller
                                        control={control}
                                        name="etudiant_id"
                                        render={({ field }) => (
                                            <Combobox
                                                id="ds-resident"
                                                value={field.value}
                                                onChange={field.onChange}
                                                onBlur={field.onBlur}
                                                invalid={!!errors.etudiant_id}
                                                placeholder="Choisir le résident"
                                                searchPlaceholder="Nom, matricule ou chambre…"
                                                emptyMessage="Aucun résident en séjour."
                                                options={residents.map((r) => ({
                                                    value: r.id,
                                                    label: r.name,
                                                    description: [r.matricule, r.chambre ? `Chambre ${r.chambre}` : null, r.preavis ? 'préavis déjà enregistré' : null].filter(Boolean).join(' · '),
                                                }))}
                                            />
                                        )}
                                    />
                                </Field>
                                {resident?.preavis && (
                                    <Encadre ton="warning" icone={<Info {...ICON} />}>
                                        Une demande est déjà en attente pour ce résident : elle sera remplacée par celle-ci.
                                    </Encadre>
                                )}
                            </>
                        )}

                        {etape === 1 && (
                            <>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field id="ds-date" label="Date de sortie prévue" required error={errors.date_sortie_prevue?.message}>
                                        <Controller
                                            control={control}
                                            name="date_sortie_prevue"
                                            render={({ field }) => (
                                                <DatePicker id="ds-date" value={field.value} onChange={field.onChange} min={aujourdHui()} invalid={!!errors.date_sortie_prevue} />
                                            )}
                                        />
                                    </Field>
                                    <Field id="ds-motif" label="Motif" required error={errors.motif?.message}>
                                        <Controller
                                            control={control}
                                            name="motif"
                                            render={({ field }) => (
                                                <Select value={field.value || undefined} onValueChange={field.onChange}>
                                                    <SelectTrigger id="ds-motif" invalid={!!errors.motif}>
                                                        <SelectValue placeholder="Choisir le motif" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {motifs.map((m) => (
                                                            <SelectItem key={m.value} value={m.value}>
                                                                {m.label}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            )}
                                        />
                                    </Field>
                                </div>
                                <Field id="ds-obs" label="Observations" error={errors.observations?.message} hint="Facultatif : informations complémentaires.">
                                    <Textarea id="ds-obs" rows={3} maxLength={2000} placeholder="Ex. : Départ en stage à Abidjan." invalid={!!errors.observations} {...register('observations')} />
                                </Field>
                            </>
                        )}

                        {etape === 2 && (
                            <>
                                <Lignes
                                    lignes={[
                                        ['Résident', resident ? `${resident.name}${resident.matricule ? ` (${resident.matricule})` : ''}` : '—'],
                                        ['Chambre', resident?.chambre ?? '—'],
                                        ['Sortie prévue', dateFr(v.date_sortie_prevue)],
                                        ['Motif', motifs.find((m) => m.value === v.motif)?.label ?? '—'],
                                        ['Observations', v.observations.trim() || '—'],
                                    ]}
                                />
                                <Encadre icone={<Info {...ICON} />}>Le préavis est compté à partir d’aujourd’hui ; une pénalité s’applique s’il est inférieur au délai paramétré.</Encadre>
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
                            <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                                {etape < ETAPES_DEMANDE.length - 1 ? (
                                    <>
                                        Suivant <ArrowRight className="size-4" aria-hidden />
                                    </>
                                ) : (
                                    <>
                                        {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Save className="size-4" aria-hidden />}
                                        Enregistrer la demande
                                    </>
                                )}
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** Approbation d'une demande : l'état des lieux de sortie devient possible. */
export function ApprobationDialog({ demande, onClose }: { demande: Demande | null; onClose: () => void }) {
    const [envoi, setEnvoi] = useState(false);
    const approuver = async () => {
        if (!demande?.urls.decision) return;
        setEnvoi(true);
        const ok = await envoyer(demande.urls.decision, { decision: 'approuvee' }, { method: 'put' });
        setEnvoi(false);
        if (ok) onClose();
    };

    return (
        <ConfirmDialog
            open={!!demande}
            onOpenChange={(ouvert) => !ouvert && onClose()}
            title="Approuver la demande de sortie ?"
            description={
                demande && (
                    <>
                        Sortie de <strong className="font-medium text-gray-900">{demande.etudiant}</strong> prévue le {dateFr(demande.date_sortie)}. L’état des lieux de sortie pourra
                        ensuite être réalisé ; il clôturera le séjour.
                    </>
                )
            }
            confirmLabel="Approuver"
            confirmIcon={<Check className="size-4" aria-hidden />}
            processing={envoi}
            onConfirm={() => void approuver()}
        />
    );
}

/** Refus d'une demande : motif obligatoire, communiqué dans l'historique. */
export function RefusDialog({ demande, onClose }: { demande: Demande | null; onClose: () => void }) {
    const {
        register,
        reset,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<Refus>({ resolver: zodResolver(refusSchema), defaultValues: { motif_decision: '' } });

    useEffect(() => {
        if (demande) reset({ motif_decision: '' });
    }, [demande, reset]);

    const refuser = handleSubmit(async (valeurs) => {
        if (!demande?.urls.decision) return;
        const ok = await envoyer<Refus>(
            demande.urls.decision,
            { decision: 'refusee', motif_decision: valeurs.motif_decision.trim() },
            { method: 'put', setError, champs: ['motif_decision'], onError: (autres) => Object.values(autres)[0] && setError('motif_decision', { type: 'server', message: Object.values(autres)[0] }) },
        );
        if (ok) onClose();
    });

    return (
        <ConfirmDialog
            open={!!demande}
            onOpenChange={(ouvert) => !ouvert && onClose()}
            title="Refuser la demande de sortie ?"
            description={demande && <>Le séjour de {demande.etudiant} se poursuit ; la demande passe dans l’historique.</>}
            confirmLabel="Refuser la demande"
            confirmIcon={<X className="size-4" aria-hidden />}
            danger
            processing={isSubmitting}
            onConfirm={() => void refuser()}
        >
            <Field id="refus-motif" label="Motif du refus" required error={errors.motif_decision?.message}>
                <Textarea id="refus-motif" rows={3} maxLength={2000} placeholder="Ex. : Préavis incomplet, loyers à régulariser." invalid={!!errors.motif_decision} {...register('motif_decision')} />
            </Field>
        </ConfirmDialog>
    );
}

/** Restitution de la caution restante après clôture du séjour. */
export function RestitutionDialog({ demande, modes, onClose }: { demande: Demande | null; modes: Option[]; onClose: () => void }) {
    const {
        control,
        reset,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<Restitution>({ resolver: zodResolver(restitutionSchema), defaultValues: { methode_paiement: '' } });

    useEffect(() => {
        if (demande) reset({ methode_paiement: '' });
    }, [demande, reset]);

    const restituer = handleSubmit(async (valeurs) => {
        if (!demande?.urls.restitution) return;
        const ok = await envoyer<Restitution>(demande.urls.restitution, valeurs, {
            setError,
            champs: ['methode_paiement'],
            onError: (autres) => Object.values(autres)[0] && setError('methode_paiement', { type: 'server', message: Object.values(autres)[0] }),
        });
        if (ok) onClose();
    });

    return (
        <ConfirmDialog
            open={!!demande}
            onOpenChange={(ouvert) => !ouvert && onClose()}
            title="Restituer la caution"
            description={
                demande && (
                    <>
                        <strong className="font-medium text-gray-900">{fcfa(demande.decompte?.caution_a_restituer)}</strong> à restituer à {demande.etudiant}. Un paiement « remboursé » est
                        enregistré.
                    </>
                )
            }
            confirmLabel="Enregistrer la restitution"
            confirmIcon={<Coins className="size-4" aria-hidden />}
            processing={isSubmitting}
            onConfirm={() => void restituer()}
        >
            <Field id="restitution-mode" label="Mode de restitution" required error={errors.methode_paiement?.message}>
                <Controller
                    control={control}
                    name="methode_paiement"
                    render={({ field }) => (
                        <Select value={field.value || undefined} onValueChange={field.onChange}>
                            <SelectTrigger id="restitution-mode" invalid={!!errors.methode_paiement}>
                                <SelectValue placeholder="Choisir le mode" />
                            </SelectTrigger>
                            <SelectContent>
                                {modes.map((m) => (
                                    <SelectItem key={m.value} value={m.value}>
                                        {m.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                />
            </Field>
        </ConfirmDialog>
    );
}

/** Fiche d'une demande : informations, décision et décompte final. */
export function DetailDemandeDialog({ demande, onClose }: { demande: Demande | null; onClose: () => void }) {
    const d = demande?.decompte;

    return (
        <Dialog open={!!demande} onOpenChange={(ouvert) => !ouvert && onClose()}>
            <DialogContent className="max-w-2xl">
                {demande && (
                    <>
                        <DialogHeader>
                            <DialogTitle className="flex flex-wrap items-center gap-2">
                                Demande de sortie de {demande.etudiant} <StatutDemandeBadge statut={demande.statut} />
                            </DialogTitle>
                            <DialogDescription>{demande.matricule ?? 'Matricule non renseigné'}</DialogDescription>
                        </DialogHeader>
                        <DialogBody className="space-y-4">
                            {demande.expulsion && (
                                <Encadre ton="danger" icone={<ShieldAlert {...ICON} />}>
                                    Sortie consécutive à une expulsion{demande.expulsion_active ? ' (sanction active : la demande ne peut pas être refusée ici)' : ''}.
                                </Encadre>
                            )}
                            <Lignes
                                lignes={[
                                    ['Logement', [demande.chambre && `Chambre ${demande.chambre}`, demande.batiment, demande.cite].filter(Boolean).join(' · ')],
                                    ['Demande du', dateFr(demande.date_demande)],
                                    ['Sortie prévue le', dateFr(demande.date_sortie)],
                                    ['Motif', demande.motif],
                                    ['Observations', demande.observations],
                                    ...(demande.motif_decision ? [['Motif de la décision', demande.motif_decision] as [string, string]] : []),
                                ]}
                            />
                            {d && (
                                <section aria-labelledby="decompte-titre" className="space-y-2">
                                    <h3 id="decompte-titre" className="text-sm font-medium text-gray-900">
                                        Décompte final
                                    </h3>
                                    <Lignes
                                        lignes={[
                                            [`Pénalité de préavis (${d.preavis_effectif} / ${d.preavis_requis} jours)`, fcfa(d.penalite_preavis)],
                                            ...d.degradations.map((r) => [`Dégradation : ${r.element}${r.piece ? ` (${r.piece})` : ''}`, fcfa(r.montant)] as [string, string]),
                                            ['Total des retenues', <strong key="t" className="font-medium">{fcfa(d.total_retenues)}</strong>],
                                            ['Imputé sur la caution', fcfa(d.impute_caution)],
                                            ['Solde débiteur de l’étudiant', <span key="s" className={cn(d.solde_debiteur > 0 && 'font-medium text-red-700')}>{fcfa(d.solde_debiteur)}</span>],
                                            ['Caution à restituer', fcfa(d.caution_a_restituer)],
                                            [
                                                'Restitution',
                                                d.date_restitution ? (
                                                    <Badge key="r" variant="success">
                                                        {fcfa(d.caution_restituee)} le {dateFr(d.date_restitution)}
                                                        {d.mode_restitution ? ` · ${d.mode_restitution}` : ''}
                                                    </Badge>
                                                ) : d.caution_a_restituer > 0 ? (
                                                    <Badge key="r" variant="warning">En attente</Badge>
                                                ) : (
                                                    'Aucune caution à restituer'
                                                ),
                                            ],
                                        ]}
                                    />
                                </section>
                            )}
                        </DialogBody>
                        <DialogFooter>
                            {demande.urls.pdf && (
                                <a href={demande.urls.pdf} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10')}>
                                    <FileDown {...ICON} aria-hidden /> Décompte PDF
                                </a>
                            )}
                            <Button size="sm" className="h-10" onClick={onClose}>
                                Fermer
                            </Button>
                        </DialogFooter>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}
