import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Loader2, Send, TriangleAlert } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { nombre } from '@/lib/text';

const MODES = [
    { value: 'sms', label: 'SMS' },
    { value: 'email', label: 'E-mail' },
    { value: 'both', label: 'SMS + e-mail' },
] as const;

const VARIABLES = ['{nom}', '{montant}', '{mois}', '{residence}', '{chambre}', '{solde_restant}', '{date_limite}'];

const MESSAGE_DEFAUT = `Bonjour {nom},

Votre paiement de loyer de {montant} pour le mois de {mois} est en retard. Merci de régulariser votre situation dans les plus brefs délais.

Cordialement,
Le service hébergement`;

const schema = z.object({
    mode_envoi: z.enum(['sms', 'email', 'both'], { error: 'Choisissez un mode d’envoi.' }),
    message_relance: z.string().trim().min(10, 'Le message doit contenir au moins 10 caractères.'),
});

type Valeurs = z.infer<typeof schema>;

const ETAPES = ['Message', 'Confirmation'];

/** Relance des loyers impayés (action `relances.envoyer` inchangée). */
export function RelanceDialog({ open, onClose, url, nombreRetard }: { open: boolean; onClose: () => void; url: string; nombreRetard: number }) {
    const [etape, setEtape] = useState(0);
    const {
        register,
        control,
        handleSubmit,
        reset,
        trigger,
        getValues,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<Valeurs>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: { mode_envoi: 'sms', message_relance: MESSAGE_DEFAUT } });

    useEffect(() => {
        if (open) {
            reset({ mode_envoi: 'sms', message_relance: MESSAGE_DEFAUT });
            setEtape(0);
        }
    }, [open, reset]);

    const suivant = async () => {
        if (await trigger()) setEtape(1);
    };

    const onSubmit = (valeurs: Valeurs) =>
        envoyer<Valeurs>(url, valeurs, {
            setError,
            champs: ['mode_envoi', 'message_relance'],
            onSuccess: onClose,
            onError: (erreurs) => {
                if (Object.keys(erreurs).length === 0) setEtape(0);
            },
        });

    const valeurs = getValues();

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-xl">
                <form onSubmit={handleSubmit(onSubmit, () => setEtape(0))} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Relance des impayés</DialogTitle>
                        <DialogDescription>
                            Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                        </DialogDescription>
                    </DialogHeader>
                    <Stepper steps={ETAPES} current={etape} reached={etape} onStepClick={(index) => index < etape && setEtape(index)} />
                    <DialogBody className="space-y-4">
                        <p className="flex items-start gap-2 rounded-brand border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <TriangleAlert className="mt-0.5 size-4 shrink-0" aria-hidden />
                            <span>
                                <strong className="font-medium">{nombre(nombreRetard)} loyer(s) en retard.</strong> Une relance est envoyée pour chaque loyer non soldé et notée dans son historique.
                            </span>
                        </p>

                        {etape === 0 && (
                            <>
                                <Field id="rl-mode" label="Mode d’envoi" required error={errors.mode_envoi?.message}>
                                    <Controller
                                        control={control}
                                        name="mode_envoi"
                                        render={({ field }) => (
                                            <Select value={field.value} onValueChange={field.onChange}>
                                                <SelectTrigger id="rl-mode" invalid={!!errors.mode_envoi}>
                                                    <SelectValue placeholder="Sélectionner un mode" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {MODES.map((mode) => (
                                                        <SelectItem key={mode.value} value={mode.value}>
                                                            {mode.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        )}
                                    />
                                </Field>
                                <Field
                                    id="rl-message"
                                    label="Message de relance"
                                    required
                                    error={errors.message_relance?.message}
                                    hint={
                                        <span className="flex flex-wrap items-center gap-1">
                                            Variables :
                                            {VARIABLES.map((variable) => (
                                                <code key={variable} className="rounded bg-surface px-1.5 py-0.5 font-mono text-[11px]">
                                                    {variable}
                                                </code>
                                            ))}
                                        </span>
                                    }
                                >
                                    <Textarea id="rl-message" rows={7} invalid={!!errors.message_relance} {...register('message_relance')} />
                                </Field>
                            </>
                        )}

                        {etape === 1 && (
                            <dl className="divide-y rounded-brand border text-sm">
                                <div className="flex flex-col gap-0.5 px-4 py-2.5 sm:flex-row sm:justify-between sm:gap-4">
                                    <dt className="text-muted">Mode d’envoi</dt>
                                    <dd className="font-medium text-gray-900">{MODES.find((mode) => mode.value === valeurs.mode_envoi)?.label}</dd>
                                </div>
                                <div className="flex flex-col gap-1.5 px-4 py-2.5">
                                    <dt className="text-muted">Message</dt>
                                    <dd className="whitespace-pre-line text-gray-900">{valeurs.message_relance}</dd>
                                </div>
                            </dl>
                        )}
                    </DialogBody>
                    <DialogFooter className="sm:justify-between">
                        <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={isSubmitting}>
                            Annuler
                        </Button>
                        <div className="flex flex-col-reverse gap-2 sm:flex-row">
                            {etape > 0 && (
                                <Button variant="outline" size="sm" className="h-10" onClick={() => setEtape(0)} disabled={isSubmitting}>
                                    <ArrowLeft className="size-4" aria-hidden /> Précédent
                                </Button>
                            )}
                            {etape === 0 ? (
                                <Button size="sm" className="h-10" onClick={suivant}>
                                    Suivant <ArrowRight className="size-4" aria-hidden />
                                </Button>
                            ) : (
                                <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                                    {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Send className="size-4" aria-hidden />}
                                    Envoyer les relances
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
