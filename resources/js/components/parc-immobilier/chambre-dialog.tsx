import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { CircleAlert, Hash, Loader2, Save } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { toast } from 'sonner';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { messageErreur, parcHttp } from './http';
import { ETATS } from './labels';
import type { ChambreItem, EtatChambre } from './types';

const ETATS_CHAMBRE = Object.keys(ETATS) as [EtatChambre, ...EtatChambre[]];

const schema = z.object({
    numero_chambre: z.string().trim().min(1, 'Le numéro est obligatoire.').max(250, '250 caractères au maximum.'),
    type_chambre_code: z.string().min(1, 'Choisissez un type de chambre.'),
    niveau_confort: z.enum(['standard', 'eleve']),
    pmr: z.enum(['0', '1']),
    nb_lits: z.coerce.number({ error: 'Indiquez un nombre.' }).int('Nombre entier attendu.').min(1, 'Au moins 1 lit.').max(20, '20 lits au maximum.'),
    etat: z.enum(ETATS_CHAMBRE).optional(),
});

type FormInput = z.input<typeof schema>;
type FormOutput = z.output<typeof schema>;

/** Champs utiles à la modification (une `ChambreItem` du parc convient, comme une chambre du formulaire d'ajout). */
export type ChambreModifiable = Pick<ChambreItem, 'id' | 'name' | 'type_chambre_id' | 'niveau_confort' | 'pmr' | 'nb_lits'> & {
    /** Statut actuel ; absent, le champ « Statut » n'est pas proposé. */
    etat?: EtatChambre | null;
};

type ChambreDialogProps = {
    chambre: ChambreModifiable | null;
    onClose: () => void;
    url: string;
    typesChambre: { id: string; name: string }[];
    /** Après enregistrement ; par défaut, recharge la page du parc. */
    onSaved?: () => void;
};

/** Modification rapide d'une chambre (même action `updateChambre` que l'ancienne fenêtre). */
export function ChambreDialog({ chambre, onClose, url, typesChambre, onSaved }: ChambreDialogProps) {
    const [serverError, setServerError] = useState<string | null>(null);
    const {
        register,
        control,
        handleSubmit,
        reset,
        formState: { errors, isSubmitting },
    } = useForm<FormInput, unknown, FormOutput>({ resolver: zodResolver(schema), mode: 'onTouched' });

    useEffect(() => {
        if (chambre) {
            setServerError(null);
            reset({
                numero_chambre: chambre.name ?? '',
                type_chambre_code: chambre.type_chambre_id,
                niveau_confort: chambre.niveau_confort === 'eleve' ? 'eleve' : 'standard',
                pmr: chambre.pmr ? '1' : '0',
                nb_lits: chambre.nb_lits || 1,
                etat: chambre.etat && ETATS[chambre.etat] ? chambre.etat : undefined,
            });
        }
    }, [chambre, reset]);

    const onSubmit = async (values: FormOutput) => {
        if (!chambre) return;
        setServerError(null);
        try {
            const { data } = await parcHttp.put(url, { code: chambre.id, ...values });
            toast.success(data.message ?? 'Chambre mise à jour.');
            onClose();
            if (onSaved) onSaved();
            else router.reload({ only: ['items', 'plan', 'stats', 'contexte'] });
        } catch (error) {
            setServerError(messageErreur(error, 'La modification de la chambre a échoué.'));
        }
    };

    return (
        <Dialog open={chambre !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-xl">
                <form onSubmit={handleSubmit(onSubmit)} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Modifier la chambre {chambre?.name}</DialogTitle>
                        <DialogDescription>Numéro de porte, type, confort, accessibilité, nombre de lits et statut.</DialogDescription>
                    </DialogHeader>
                    <DialogBody className="space-y-5">
                        {serverError && (
                            <p role="alert" className="flex items-center gap-2 rounded-brand border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                <CircleAlert className="size-4 shrink-0" aria-hidden />
                                {serverError}
                            </p>
                        )}
                        <div className="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2">
                            <Field id="numero_chambre" label="Numéro" required error={errors.numero_chambre?.message}>
                                <Input id="numero_chambre" icon={<Hash />} invalid={!!errors.numero_chambre} {...register('numero_chambre')} />
                            </Field>
                            <Field id="type_chambre_code" label="Type de chambre" required error={errors.type_chambre_code?.message}>
                                <Controller
                                    control={control}
                                    name="type_chambre_code"
                                    render={({ field }) => (
                                        <Select value={field.value} onValueChange={field.onChange} onOpenChange={(open) => !open && field.onBlur()}>
                                            <SelectTrigger id="type_chambre_code" ref={field.ref} invalid={!!errors.type_chambre_code}>
                                                <SelectValue placeholder="Sélectionnez un type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {typesChambre.map((type) => (
                                                    <SelectItem key={type.id} value={type.id}>
                                                        {type.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                />
                            </Field>
                            <Field id="niveau_confort" label="Niveau de confort" error={errors.niveau_confort?.message}>
                                <Controller
                                    control={control}
                                    name="niveau_confort"
                                    render={({ field }) => (
                                        <Select value={field.value} onValueChange={field.onChange}>
                                            <SelectTrigger id="niveau_confort" ref={field.ref}>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="standard">Standard</SelectItem>
                                                <SelectItem value="eleve">Élevé</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    )}
                                />
                            </Field>
                            <Field id="pmr" label="Accès PMR" error={errors.pmr?.message}>
                                <Controller
                                    control={control}
                                    name="pmr"
                                    render={({ field }) => (
                                        <Select value={field.value} onValueChange={field.onChange}>
                                            <SelectTrigger id="pmr" ref={field.ref}>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="0">Non</SelectItem>
                                                <SelectItem value="1">Oui</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    )}
                                />
                            </Field>
                            <Field id="nb_lits" label="Nombre de lits" required error={errors.nb_lits?.message}>
                                <Input id="nb_lits" type="number" min={1} max={20} inputMode="numeric" invalid={!!errors.nb_lits} {...register('nb_lits')} />
                            </Field>
                            {chambre?.etat && ETATS[chambre.etat] && (
                                <Field id="etat" label="Statut" error={errors.etat?.message}>
                                    <Controller
                                        control={control}
                                        name="etat"
                                        render={({ field }) => (
                                            <Select value={field.value} onValueChange={field.onChange}>
                                                <SelectTrigger id="etat" ref={field.ref} invalid={!!errors.etat}>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {ETATS_CHAMBRE.map((etat) => (
                                                        <SelectItem key={etat} value={etat}>
                                                            {ETATS[etat].label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        )}
                                    />
                                </Field>
                            )}
                        </div>
                    </DialogBody>
                    <DialogFooter>
                        <Button variant="outline" size="sm" className="h-10" onClick={onClose}>
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
