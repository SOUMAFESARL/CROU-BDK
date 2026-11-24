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

export type StatutReclamation = 'en_attente' | 'ouverte' | 'acceptee' | 'rejetee';

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
    /** Null sans le droit de traiter les réclamations. */
    url_traiter: string | null;
};

export const STATUTS_RECLAMATION: Record<StatutReclamation, { label: string; variante: 'warning' | 'neutral' | 'success' | 'danger' }> = {
    en_attente: { label: 'En attente', variante: 'warning' },
    ouverte: { label: 'Ouverte', variante: 'neutral' },
    acceptee: { label: 'Acceptée', variante: 'success' },
    rejetee: { label: 'Rejetée', variante: 'danger' },
};

export function StatutReclamationBadge({ statut }: { statut: StatutReclamation }) {
    return <Badge variant={STATUTS_RECLAMATION[statut]?.variante ?? 'neutral'}>{STATUTS_RECLAMATION[statut]?.label ?? statut}</Badge>;
}

const CHOIX = [
    { value: 'ouverte', label: 'Ouverte' },
    { value: 'en_attente', label: 'En attente' },
    { value: 'acceptee', label: 'Acceptée' },
    { value: 'rejetee', label: 'Rejetée' },
] as const;

/** Fenêtre de traitement d'une réclamation (liste et fiche de la demande) : choix du statut. */
export function TraiterReclamationDialog({ reclamation, onClose }: { reclamation: Reclamation | null; onClose: () => void }) {
    const {
        control,
        handleSubmit,
        reset,
        setValue,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<TraitementReclamation>({ resolver: zodResolver(traitementReclamationSchema), mode: 'onTouched', defaultValues: { statut: 'en_attente' } });
    const statut = useWatch({ control, name: 'statut' });

    useEffect(() => {
        if (reclamation) reset({ statut: reclamation.statut });
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
                                Statut <span className="text-red-600">*</span>
                            </legend>
                            <div role="radiogroup" className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                {CHOIX.map((option) => (
                                    <ChoixCarte
                                        key={option.value}
                                        actif={statut === option.value}
                                        onClick={() => setValue('statut', option.value, { shouldValidate: true })}
                                        titre={option.label}
                                    />
                                ))}
                            </div>
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
