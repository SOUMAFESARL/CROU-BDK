import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeftRight, Loader2 } from 'lucide-react';
import { useEffect } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';

type Cible = { id: string; name: string; stock_actuel: number };

const schema = (stock: number) =>
    z
        .object({
            type: z.enum(['entry', 'exit'], { error: 'Choisissez une entrée ou une sortie.' }),
            quantite: z.coerce.number({ error: 'Indiquez une quantité.' }).int('Nombre entier attendu.').min(1, 'Au moins 1 unité.'),
            motif: z.string().trim().max(255, '255 caractères au maximum.'),
        })
        .refine((valeurs) => valeurs.type !== 'exit' || valeurs.quantite <= stock, {
            path: ['quantite'],
            message: `Une sortie ne peut pas dépasser le stock actuel (${nombre(stock)}).`,
        });

type Saisie = z.input<ReturnType<typeof schema>>;
type Valeurs = z.output<ReturnType<typeof schema>>;

const TYPES = [
    { value: 'entry', titre: 'Entrée', description: 'Ajoute la quantité au magasin' },
    { value: 'exit', titre: 'Sortie', description: 'Retire la quantité du magasin' },
] as const;

/** Entrée ou sortie de stock d'un équipement (action `store_mouvement` inchangée). */
export function MouvementDialog({ equipement, url, onClose }: { equipement: Cible | null; url: string; onClose: () => void }) {
    const stock = equipement?.stock_actuel ?? 0;
    const {
        register,
        control,
        handleSubmit,
        reset,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<Saisie, unknown, Valeurs>({ resolver: zodResolver(schema(stock)), mode: 'onTouched' });

    useEffect(() => {
        if (equipement) reset({ type: undefined, quantite: '', motif: '' });
    }, [equipement, reset]);

    const onSubmit = (valeurs: Valeurs) =>
        envoyer<Saisie>(url, { equipement_id: equipement!.id, ...valeurs }, { setError, champs: ['type', 'quantite', 'motif'], onSuccess: onClose });

    return (
        <Dialog open={equipement !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <form onSubmit={handleSubmit(onSubmit)} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Mouvement de stock</DialogTitle>
                        <DialogDescription>
                            {equipement?.name} · stock actuel <strong className="font-medium text-gray-900">{nombre(stock)}</strong>
                        </DialogDescription>
                    </DialogHeader>
                    <DialogBody className="space-y-4">
                        <fieldset>
                            <legend className="mb-1.5 text-sm font-medium text-gray-800">
                                Type de mouvement <span className="text-red-600">*</span>
                            </legend>
                            <Controller
                                control={control}
                                name="type"
                                render={({ field }) => (
                                    <div role="radiogroup" className="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                                        {TYPES.map((type) => (
                                            <label
                                                key={type.value}
                                                className={cn(
                                                    'flex cursor-pointer items-start gap-2.5 rounded-brand border p-3 transition-colors hover:border-primary',
                                                    field.value === type.value && 'border-primary bg-soft',
                                                )}
                                            >
                                                <input
                                                    type="radio"
                                                    name={field.name}
                                                    value={type.value}
                                                    checked={field.value === type.value}
                                                    onChange={() => field.onChange(type.value)}
                                                    onBlur={field.onBlur}
                                                    className="mt-0.5 size-4 accent-primary"
                                                />
                                                <span className="flex flex-col">
                                                    <span className="text-sm font-medium text-gray-900">{type.titre}</span>
                                                    <span className="text-xs text-muted">{type.description}</span>
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                )}
                            />
                            {errors.type && (
                                <p role="alert" className="mt-1.5 text-xs font-medium text-red-600">
                                    {errors.type.message}
                                </p>
                            )}
                        </fieldset>
                        <Field id="mv-quantite" label="Quantité" required error={errors.quantite?.message} hint="Une sortie ne peut pas dépasser le stock actuel.">
                            <Input id="mv-quantite" type="number" min={1} step={1} inputMode="numeric" placeholder="0" invalid={!!errors.quantite} {...register('quantite')} />
                        </Field>
                        <Field id="mv-motif" label="Motif" error={errors.motif?.message}>
                            <Textarea id="mv-motif" rows={2} maxLength={255} placeholder="Ex. Livraison fournisseur, matériel réformé…" {...register('motif')} />
                        </Field>
                    </DialogBody>
                    <DialogFooter>
                        <Button variant="outline" size="sm" className="h-10" onClick={onClose}>
                            Annuler
                        </Button>
                        <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                            {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <ArrowLeftRight className="size-4" aria-hidden />}
                            Enregistrer le mouvement
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
