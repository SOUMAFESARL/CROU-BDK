import { zodResolver } from '@hookform/resolvers/zod';
import { CircleCheck, Clock, Info, MessageSquareText, RotateCcw, Save, XCircle, type LucideIcon } from 'lucide-react';
import { useMemo } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/ui/page-header';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { cn } from '@/lib/utils';

type Modeles = Record<string, string>;

type Props = {
    /** Clé du modèle → libellé (`AdmissionSmsTemplates::LABELS`). */
    labels: Record<string, string>;
    messages: Modeles;
    /** Textes par défaut, pour « Rétablir le texte par défaut ». */
    defaults: Modeles;
    /** `update` absent sans le droit `parametrage.configuration.configure` : page en lecture seule. */
    urls: { update: string | null };
};

type Valeurs = { messages: Modeles };

const APPARENCE: Record<string, { icone: LucideIcon; ton: string; aide: string }> = {
    definitive: { icone: CircleCheck, ton: 'bg-soft text-primary', aide: 'Envoyé aux étudiants retenus à la publication de la liste définitive.' },
    attente: { icone: Clock, ton: 'bg-amber-50 text-amber-700', aide: 'Envoyé aux étudiants placés sur la liste d’attente définitive.' },
    irrecevable_ou_refusee: { icone: XCircle, ton: 'bg-red-50 text-red-700', aide: 'Envoyé aux étudiants dont la demande est irrecevable ou refusée.' },
};

const APPARENCE_PAR_DEFAUT = { icone: MessageSquareText, ton: 'bg-soft text-primary', aide: '' };

/** Configuration des messages envoyés après la publication des listes définitives (remplace `parametrage/admission-sms.blade.php`). */
export default function ConfigurationMessages({ labels, messages, defaults, urls }: Props) {
    const cles = Object.keys(labels);
    const lectureSeule = urls.update === null;

    const schema = useMemo(
        () =>
            z.object({
                messages: z.object(
                    Object.fromEntries(
                        cles.map((cle) => [cle, z.string().trim().min(1, 'Saisissez le texte du message.')]),
                    ),
                ),
            }),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [cles.join('|')],
    );

    const {
        control,
        handleSubmit,
        setError,
        setValue,
        reset,
        formState: { errors, isDirty, isSubmitting },
    } = useForm<Valeurs>({ resolver: zodResolver(schema), defaultValues: { messages } });

    const enregistrer = handleSubmit(async (valeurs) => {
        if (!urls.update) return;
        const ok = await envoyer<Valeurs>(urls.update, valeurs, {
            method: 'put',
            setError,
            champs: cles.map((cle) => `messages.${cle}` as const),
        });
        if (ok) reset(valeurs);
    });

    return (
        <AdminLayout title="Configuration des messages">
            <PageHeader
                title="Configuration des messages"
                description="Personnalisez le SMS envoyé à chaque étudiant selon le résultat de sa demande de logement, après la publication de la liste définitive."
            />

            {lectureSeule && (
                <p className="mb-4 flex items-start gap-2 rounded-brand border border-secondary/40 bg-soft px-3 py-2.5 text-sm text-gray-800">
                    <Info {...ICON} className="mt-0.5 shrink-0 text-primary" aria-hidden />
                    <span>Vous pouvez consulter les modèles mais pas les modifier.</span>
                </p>
            )}

            <form onSubmit={enregistrer} noValidate>
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
                    {cles.map((cle) => {
                        const { icone: Icone, ton, aide } = APPARENCE[cle] ?? APPARENCE_PAR_DEFAUT;
                        const id = `sms-${cle}`;
                        const erreur = errors.messages?.[cle]?.message;

                        return (
                            <section key={cle} aria-labelledby={`${id}-titre`} className="flex flex-col gap-3 rounded-brand border bg-white p-4">
                                <div className="flex items-start gap-3">
                                    <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-brand', ton)}>
                                        <Icone {...ICON} aria-hidden />
                                    </span>
                                    <div className="min-w-0">
                                        <h2 id={`${id}-titre`} className="text-sm font-medium text-gray-900">
                                            <label htmlFor={id}>{labels[cle]}</label>
                                        </h2>
                                        {aide && <p className="text-xs text-muted">{aide}</p>}
                                    </div>
                                </div>

                                <Controller
                                    control={control}
                                    name={`messages.${cle}`}
                                    render={({ field }) => (
                                            <>
                                                <Textarea
                                                    {...field}
                                                    id={id}
                                                    rows={6}
                                                    readOnly={lectureSeule}
                                                    invalid={Boolean(erreur)}
                                                    aria-describedby={erreur ? `${id}-error` : undefined}
                                                    className="min-h-36 resize-y"
                                                />
                                                {!lectureSeule && field.value !== defaults[cle] && (
                                                    <div className="-mt-1 flex justify-end text-xs">
                                                        <button
                                                            type="button"
                                                            onClick={() => setValue(`messages.${cle}`, defaults[cle], { shouldDirty: true, shouldValidate: true })}
                                                            className="inline-flex cursor-pointer items-center gap-1 rounded-brand font-medium text-primary hover:underline focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                                                        >
                                                            <RotateCcw size={14} strokeWidth={1.5} aria-hidden /> Texte par défaut
                                                        </button>
                                                    </div>
                                                )}
                                                {erreur && (
                                                    <p id={`${id}-error`} role="alert" className="text-xs font-medium text-red-600">
                                                        {erreur}
                                                    </p>
                                                )}
                                            </>
                                    )}
                                />
                            </section>
                        );
                    })}
                </div>

                {!lectureSeule && (
                    <div className="mt-6 flex flex-wrap items-center justify-end gap-2 border-t pt-4">
                        <Button type="button" variant="outline" disabled={!isDirty || isSubmitting} onClick={() => reset({ messages })}>
                            Annuler les modifications
                        </Button>
                        <Button type="submit" disabled={!isDirty || isSubmitting}>
                            <Save {...ICON} aria-hidden /> {isSubmitting ? 'Enregistrement…' : 'Enregistrer les modèles'}
                        </Button>
                    </div>
                )}
            </form>
        </AdminLayout>
    );
}
