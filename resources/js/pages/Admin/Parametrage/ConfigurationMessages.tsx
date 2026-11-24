import { zodResolver } from '@hookform/resolvers/zod';
import { Link } from '@inertiajs/react';
import { CircleCheck, Clock, Info, Mail, MessageSquareText, RotateCcw, Save, XCircle, type LucideIcon } from 'lucide-react';
import { useMemo } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { htmlVide, RichTextEditor } from '@/components/ui/rich-text-editor';
import { envoyer } from '@/lib/inertia-form';
import { cn } from '@/lib/utils';

type Modeles = Record<string, string>;

type Props = {
    /** Clé du modèle → libellé (`AdmissionSmsTemplates::LABELS`). */
    labels: Record<string, string>;
    messages: Modeles;
    /** Mise en forme (HTML) des modèles déjà enregistrés avec l’éditeur. */
    formatted: Modeles;
    /** Textes par défaut, pour « Rétablir le texte par défaut ». */
    defaults: Modeles;
    /** Objet de l’e-mail de chaque modèle ; le corps est commun avec le SMS. */
    subjects: Modeles;
    /** Types d’envoi activés dans Configurations système › Notifications. */
    channels: { email: boolean; sms: boolean };
    /** `update` absent sans le droit `parametrage.configuration.configure` : page en lecture seule. */
    urls: { update: string | null; notifications: string | null };
};

/** Les `messages` contiennent du HTML, envoyé tel quel par e-mail ; le SMS en est la version texte. */
type Valeurs = { messages: Modeles; subjects: Modeles };

const corps = (html: string) => new DOMParser().parseFromString(html, 'text/html').body;

/** HTML normalisé comme celui de l’éditeur (entités décodées). */
const normaliser = (html: string) => corps(html).innerHTML;

const texteVersHtml = (texte: string) =>
    texte
        .split(/\r?\n/)
        .map((ligne) => {
            const paragraphe = document.createElement('p');
            paragraphe.textContent = ligne;
            return paragraphe.outerHTML;
        })
        .join('');

/** Version texte (SMS) : un retour à la ligne par paragraphe, listes préfixées par « - » ou leur numéro. */
function htmlVersTexte(html: string): string {
    const rendre = (noeud: Node): string => {
        if (noeud.nodeType === Node.TEXT_NODE) return (noeud.textContent ?? '').replace(/\u00a0/g, ' ');
        if (noeud.nodeName === 'BR') return '\n';
        const contenu = Array.from(noeud.childNodes).map(rendre).join('');
        if (noeud.nodeName === 'LI') {
            const liste = noeud.parentElement;
            const prefixe = liste?.nodeName === 'OL' ? `${Array.from(liste.children).indexOf(noeud as Element) + 1}. ` : '- ';
            return `${prefixe}${contenu.replace(/\n+$/, '')}\n`;
        }
        return noeud.nodeName === 'P' || noeud.nodeName === 'DIV' ? `${contenu}\n` : contenu;
    };
    return rendre(corps(html)).trim();
}

const APPARENCE: Record<string, { icone: LucideIcon; ton: string; aide: string }> = {
    definitive: { icone: CircleCheck, ton: 'bg-soft text-primary', aide: 'Envoyé aux étudiants retenus à la publication de la liste définitive.' },
    attente: { icone: Clock, ton: 'bg-amber-50 text-amber-700', aide: 'Envoyé aux étudiants placés sur la liste d’attente définitive.' },
    irrecevable_ou_refusee: { icone: XCircle, ton: 'bg-red-50 text-red-700', aide: 'Envoyé aux étudiants dont la demande est irrecevable ou refusée.' },
};

const APPARENCE_PAR_DEFAUT = { icone: MessageSquareText, ton: 'bg-soft text-primary', aide: '' };

function TypeEnvoi({ icone: Icone, label, actif }: { icone: LucideIcon; label: string; actif: boolean }) {
    return (
        <span className={cn('inline-flex items-center gap-1.5 rounded-brand px-2.5 py-1 text-xs', actif ? 'bg-soft text-primary' : 'bg-surface text-muted')}>
            <Icone {...ICON} aria-hidden /> {label} : {actif ? 'activé' : 'désactivé'}
        </span>
    );
}

/** Configuration des messages envoyés après la publication des listes définitives (remplace `parametrage/admission-sms.blade.php`). */
export default function ConfigurationMessages({ labels, messages, formatted, defaults, subjects, channels, urls }: Props) {
    const cles = Object.keys(labels);
    const lectureSeule = urls.update === null;

    const schema = useMemo(
        () =>
            z.object({
                messages: z.object(
                    Object.fromEntries(
                        cles.map((cle) => [cle, z.string().refine((html) => !htmlVide(html), 'Saisissez le texte du message.')]),
                    ),
                ),
                subjects: z.object(
                    Object.fromEntries(
                        cles.map((cle) => [cle, z.string().trim().min(1, 'Saisissez l’objet de l’e-mail.').max(180, '180 caractères au maximum.')]),
                    ),
                ),
            }),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [cles.join('|')],
    );

    const initiaux = useMemo(
        () => Object.fromEntries(cles.map((cle) => [cle, formatted[cle] ? normaliser(formatted[cle]) : texteVersHtml(messages[cle])])),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [messages, formatted],
    );

    const {
        control,
        register,
        handleSubmit,
        setError,
        setValue,
        reset,
        formState: { errors, isDirty, isSubmitting },
    } = useForm<Valeurs>({ resolver: zodResolver(schema), defaultValues: { messages: initiaux, subjects } });

    const enregistrer = handleSubmit(async (valeurs) => {
        if (!urls.update) return;
        const ok = await envoyer<Valeurs>(
            urls.update,
            {
                ...valeurs,
                messages: Object.fromEntries(cles.map((cle) => [cle, htmlVersTexte(valeurs.messages[cle])])),
                formatted: valeurs.messages,
            },
            {
                method: 'put',
                setError,
                champs: cles.flatMap((cle) => [`messages.${cle}` as const, `subjects.${cle}` as const]),
            },
        );
        if (ok) reset(valeurs);
    });

    return (
        <AdminLayout title="Configuration des messages">
            <PageHeader
                title="Configuration des messages"
            />

            {lectureSeule && (
                <p className="mb-4 flex items-start gap-2 rounded-brand border border-secondary/40 bg-soft px-3 py-2.5 text-sm text-gray-800">
                    <Info {...ICON} className="mt-0.5 shrink-0 text-primary" aria-hidden />
                    <span>Vous pouvez consulter les modèles mais pas les modifier.</span>
                </p>
            )}

            <section aria-labelledby="types-envoi" className="mb-4 rounded-brand border bg-white p-4">
                <div className="flex flex-wrap items-center gap-2">
                    <h2 id="types-envoi" className="mr-1 text-sm font-medium text-gray-900">
                        Type d’envoi
                    </h2>
                    <TypeEnvoi icone={Mail} label="E-mail" actif={channels.email} />
                    <TypeEnvoi icone={MessageSquareText} label="SMS" actif={channels.sms} />
                    {urls.notifications && (
                        <Link href={urls.notifications} className="ml-auto rounded-brand text-xs font-medium text-primary hover:underline focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none">
                            Modifier dans les notifications
                        </Link>
                    )}
                </div>
            </section>

            <form onSubmit={enregistrer} noValidate>
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    {cles.map((cle) => {
                        const { icone: Icone, ton, aide } = APPARENCE[cle] ?? APPARENCE_PAR_DEFAUT;
                        const id = `message-${cle}`;
                        const erreur = errors.messages?.[cle]?.message;
                        const erreurObjet = errors.subjects?.[cle]?.message;

                        return (
                            <section key={cle} aria-labelledby={`${id}-titre`} className="flex flex-col gap-3 rounded-brand border bg-white p-4">
                                <div className="flex items-start gap-3">
                                    <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-brand', ton)}>
                                        <Icone {...ICON} aria-hidden />
                                    </span>
                                    <div className="min-w-0">
                                        <h2 id={`${id}-titre`} className="text-sm font-medium text-gray-900">
                                            {labels[cle]}
                                        </h2>
                                        {aide && <p className="text-xs text-muted">{aide}</p>}
                                    </div>
                                </div>

                                <Field id={`objet-${cle}`} label="Objet de l’e-mail" error={erreurObjet} required>
                                    <Input
                                        id={`objet-${cle}`}
                                        {...register(`subjects.${cle}`)}
                                        readOnly={lectureSeule}
                                        maxLength={180}
                                        invalid={Boolean(erreurObjet)}
                                        aria-describedby={erreurObjet ? `objet-${cle}-error` : undefined}
                                    />
                                </Field>
                                <p id={`${id}-label`} className="-mb-1.5 text-sm font-medium text-gray-800">
                                    Message (e-mail et SMS)<span className="ml-0.5 text-red-600">*</span>
                                </p>
                                <Controller
                                    control={control}
                                    name={`messages.${cle}`}
                                    render={({ field }) => (
                                            <>
                                                <RichTextEditor
                                                    simple
                                                    id={id}
                                                    value={field.value}
                                                    onChange={field.onChange}
                                                    onBlur={field.onBlur}
                                                    readOnly={lectureSeule}
                                                    invalid={Boolean(erreur)}
                                                    aria-labelledby={`${id}-titre ${id}-label`}
                                                    aria-describedby={erreur ? `${id}-error` : undefined}
                                                />
                                                {!lectureSeule && field.value !== texteVersHtml(defaults[cle]) && (
                                                    <div className="-mt-1 flex justify-end text-xs">
                                                        <button
                                                            type="button"
                                                            onClick={() => setValue(`messages.${cle}`, texteVersHtml(defaults[cle]), { shouldDirty: true, shouldValidate: true })}
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
                                {cle === 'definitive' && (
                                    <p className="text-xs text-muted">Dans l’e-mail, les accès à l’espace étudiant sont ajoutés automatiquement.</p>
                                )}
                            </section>
                        );
                    })}
                </div>

                {!lectureSeule && (
                    <div className="sticky bottom-0 z-10 mt-6 flex rounded-b-brand flex-wrap items-center justify-end gap-2 border-t bg-white px-4 py-3">
                        <Button type="button" variant="outline" disabled={!isDirty || isSubmitting} onClick={() => reset()}>
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
