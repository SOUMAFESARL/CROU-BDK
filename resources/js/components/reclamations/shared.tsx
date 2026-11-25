import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, Save } from 'lucide-react';
import { useEffect } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ChoixCarte } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';
import { traitementReclamationSchema, type TraitementReclamation } from '@/schemas/reclamation';

export type StatutReclamation = 'en_attente' | 'acceptee' | 'rejetee';

/** Réclamation présentée par `ReclamationService::presenter` (liste et fiche de la demande). */
export type Reclamation = {
    id: string;
    numero: string;
    nom_prenoms: string;
    /** Numéro de table au bac ou matricule saisi (null pour les réclamations antérieures à ce champ). */
    num_bac_matricule: string | null;
    type_demande: 'admission' | 'readmission';
    campagne: string | null;
    motif: string;
    statut: StatutReclamation;
    date: string | null;
    traite_par: string | null;
    traite_at: string | null;
    /** Effet d'une décision favorable sur la demande (null si la réclamation ne peut pas être traitée). */
    effet_favorable: { effet: 'admettre' | 'aucun' | 'bloque'; message: string } | null;
    /** Null sans le droit de traiter les réclamations, ou si la décision n'est plus modifiable. */
    url_traiter: string | null;
};

export const STATUTS_RECLAMATION: Record<StatutReclamation, { label: string; variante: 'warning' | 'success' | 'danger' }> = {
    en_attente: { label: 'En attente', variante: 'warning' },
    acceptee: { label: 'Favorable', variante: 'success' },
    rejetee: { label: 'Non recevable', variante: 'danger' },
};

export function StatutReclamationBadge({ statut }: { statut: StatutReclamation }) {
    return <Badge variant={STATUTS_RECLAMATION[statut]?.variante ?? 'neutral'}>{STATUTS_RECLAMATION[statut]?.label ?? statut}</Badge>;
}

const CHOIX = [
    { value: 'acceptee', label: 'Favorable : admis à loger' },
    { value: 'rejetee', label: 'Non recevable' },
] as const;

/** Fenêtre de traitement d'une réclamation (liste et fiche de la demande) : choix de la décision. */
export function TraiterReclamationDialog({ reclamation, onClose }: { reclamation: Reclamation | null; onClose: () => void }) {
    const {
        control,
        handleSubmit,
        reset,
        setValue,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<TraitementReclamation>({ resolver: zodResolver(traitementReclamationSchema), mode: 'onTouched' });
    const statut = useWatch({ control, name: 'statut' });

    // Motif qui empêche une décision favorable (droit manquant, résultat non publié…).
    const favorableBloque = reclamation?.effet_favorable?.effet === 'bloque' ? reclamation.effet_favorable.message : null;

    useEffect(() => {
        // Aucune décision présélectionnée tant que la réclamation est en attente.
        if (reclamation) reset({ statut: reclamation.statut === 'en_attente' ? undefined : reclamation.statut });
    }, [reclamation, reset]);

    const onSubmit = async (valeurs: TraitementReclamation) => {
        if (reclamation?.url_traiter) await envoyer<TraitementReclamation>(reclamation.url_traiter, valeurs, { method: 'put', setError, champs: ['statut'], onSuccess: onClose });
    };

    return (
        <Dialog open={reclamation !== null} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-lg">
                <form onSubmit={handleSubmit(onSubmit)} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Traiter la réclamation</DialogTitle>
                        <DialogDescription>
                            {reclamation?.numero} · {reclamation?.nom_prenoms}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogBody className="space-y-4">
                        <div>
                            <p className="mb-1.5 text-sm font-medium text-gray-800">Motif de l’étudiant</p>
                            <p className="max-h-40 overflow-y-auto rounded-brand bg-surface p-3 text-sm break-words whitespace-pre-line text-gray-900">{reclamation?.motif}</p>
                        </div>
                        <fieldset>
                            <legend className="mb-2 text-sm font-medium text-gray-800">
                                Décision <span className="text-red-600">*</span>
                            </legend>
                            <div role="radiogroup" className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                {CHOIX.map((option) => (
                                    <ChoixCarte
                                        key={option.value}
                                        actif={statut === option.value}
                                        disabled={option.value === 'acceptee' && favorableBloque !== null}
                                        onClick={() => setValue('statut', option.value, { shouldValidate: true })}
                                        titre={option.label}
                                    />
                                ))}
                            </div>
                            {favorableBloque && <p className="mt-1.5 text-xs text-muted">Décision favorable impossible. {favorableBloque}</p>}
                            {errors.statut?.message && <p className="mt-1.5 text-xs font-medium text-red-600">{errors.statut.message}</p>}
                        </fieldset>
                    </DialogBody>
                    <DialogFooter>
                        <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={isSubmitting}>
                            Annuler
                        </Button>
                        <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                            {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Save className="size-4" aria-hidden />}
                            Enregistrer
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
