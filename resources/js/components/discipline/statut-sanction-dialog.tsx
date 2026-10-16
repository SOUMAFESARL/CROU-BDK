import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, Save } from 'lucide-react';
import { useEffect } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import { ChoixCarte, Encadre } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';
import type { Sanction } from '@/pages/Admin/Hebergement/Discipline/Sanctions';

const STATUTS = [
    { value: 'active', label: 'Active', description: 'La sanction s’applique.' },
    { value: 'levee', label: 'Levée', description: 'Fin anticipée de la sanction.' },
    { value: 'annulee', label: 'Annulée', description: 'La décision est retirée.' },
] as const;

const schema = z.object({
    statut: z.enum(['active', 'levee', 'annulee']),
    details: z.string().trim().min(5, 'Justifiez le changement (5 caractères au moins).').max(2000, '2 000 caractères au maximum.'),
});

type Valeurs = z.infer<typeof schema>;

/** Lever, annuler ou réactiver une sanction (action `sanctions.status` inchangée ; justification obligatoire). */
export function StatutSanctionDialog({ sanction, onClose }: { sanction: Sanction | null; onClose: () => void }) {
    const {
        register,
        control,
        handleSubmit,
        reset,
        setValue,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<Valeurs>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: { statut: 'active', details: '' } });
    const statut = useWatch({ control, name: 'statut' });

    useEffect(() => {
        if (sanction) reset({ statut: sanction.statut, details: '' });
    }, [sanction, reset]);

    const onSubmit = (valeurs: Valeurs) =>
        sanction?.url_statut && envoyer<Valeurs>(sanction.url_statut, valeurs, { method: 'patch', setError, champs: ['statut', 'details'], onSuccess: onClose });

    return (
        <Dialog open={sanction !== null} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-lg">
                <form onSubmit={handleSubmit(onSubmit)} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Modifier le statut</DialogTitle>
                        <DialogDescription>
                            Sanction {sanction?.numero} · {sanction?.cible.titre}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogBody className="space-y-4">
                        <fieldset>
                            <legend className="mb-2 text-sm font-medium text-gray-800">
                                Nouveau statut <span className="text-red-600">*</span>
                            </legend>
                            <div role="radiogroup" className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                {STATUTS.map((option) => (
                                    <ChoixCarte key={option.value} actif={statut === option.value} onClick={() => setValue('statut', option.value)} titre={option.label} description={option.description} />
                                ))}
                            </div>
                            {errors.statut?.message && <p className="mt-1.5 text-xs font-medium text-red-600">{errors.statut.message}</p>}
                        </fieldset>
                        <Field id="ss-details" label="Justification" required error={errors.details?.message}>
                            <Textarea id="ss-details" rows={4} invalid={!!errors.details} placeholder="Motif du changement, décision de référence…" {...register('details')} />
                        </Field>
                        <Encadre>Le changement et sa justification sont ajoutés à l’historique de la sanction.</Encadre>
                    </DialogBody>
                    <DialogFooter>
                        <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={isSubmitting}>
                            Annuler
                        </Button>
                        <Button type="submit" size="sm" className="h-10" disabled={isSubmitting || statut === sanction?.statut}>
                            {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Save className="size-4" aria-hidden />}
                            Enregistrer
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
