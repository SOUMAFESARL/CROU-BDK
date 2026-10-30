import { zodResolver } from '@hookform/resolvers/zod';
import type { RequestPayload } from '@inertiajs/core';
import { ArrowLeft, ArrowRight, CalendarClock, FileEdit, Globe, Loader2, Mail, MessageSquareText, Paperclip, Save, Send, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { ICON } from '@/components/layout/app-sidebar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { MultiCombobox } from '@/components/ui/multi-combobox';
import { htmlVide, RichTextEditor, RichTextView } from '@/components/ui/rich-text-editor';
import { Stepper } from '@/components/ui/stepper';
import { ChoixCarte, dateHeure, Encadre, Lignes, maintenantLocal, repartirErreurs, type BatimentOption, type ChambreOption, type CiteOption } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';

export type Cibles = {
    cites: CiteOption[];
    batiments: BatimentOption[];
    chambres: ChambreOption[];
    etudiants: { id: string; name: string; matricule: string | null }[];
};

export type AnnonceModifiable = {
    id: string;
    titre: string;
    contenu: string;
    statut: string;
    priorite: string;
    canaux: string[];
    audience: string;
    cibles: Partial<Record<'cites' | 'batiments' | 'chambres' | 'etudiants', string[]>>;
    date_planification: string | null;
    urls: { update: string | null };
};

export const CANAUX = [
    { value: 'flux', label: 'Site web', description: 'Fil d’actualité de l’espace étudiant (+ e-mail).', icone: <Globe {...ICON} /> },
    { value: 'sms', label: 'SMS', description: 'Message court (+ copie par e-mail).', icone: <MessageSquareText {...ICON} /> },
    { value: 'email', label: 'E-mail', description: 'E-mail uniquement.', icone: <Mail {...ICON} /> },
] as const;

export const PRIORITES_ANNONCE = [
    { value: 'normale', label: 'Normale' },
    { value: 'haute', label: 'Haute' },
    { value: 'urgente', label: 'Urgente' },
] as const;

const PUBLICATIONS = [
    { value: 'brouillon', label: 'Brouillon', description: 'Enregistrée sans envoi ; publiable plus tard.', icone: <FileEdit {...ICON} /> },
    { value: 'publie', label: 'Publier maintenant', description: 'Les envois partent immédiatement.', icone: <Send {...ICON} /> },
    { value: 'planifie', label: 'Planifier', description: 'Diffusion automatique à la date choisie.', icone: <CalendarClock {...ICON} /> },
] as const;

/** Clé du tableau de cibles envoyé au serveur selon l'audience (`normalizeAudience`). */
const CLE_CIBLE: Record<string, 'cites' | 'batiments' | 'chambres' | 'etudiants' | undefined> = { cite: 'cites', batiment: 'batiments', chambre: 'chambres', specific: 'etudiants' };

const schema = z
    .object({
        canaux: z.array(z.string()).min(1, 'Choisissez au moins un canal de diffusion.'),
        audience: z.string().min(1, 'Choisissez l’audience.'),
        cites: z.array(z.string()),
        batiments: z.array(z.string()),
        chambres: z.array(z.string()),
        etudiants: z.array(z.string()),
        titre: z.string().trim().min(3, 'Le titre est obligatoire (3 caractères au moins).').max(255, '255 caractères au maximum.'),
        priorite: z.string().min(1, 'Choisissez la priorité.'),
        contenu: z.string().refine((html) => !htmlVide(html), 'Le contenu est obligatoire.'),
        fichiers: z.array(z.instanceof(File)),
        statut: z.string().min(1, 'Choisissez le mode de publication.'),
        date_planifiee: z.string(),
    })
    .superRefine((v, ctx) => {
        const cle = CLE_CIBLE[v.audience];
        if (cle && v[cle].length === 0) ctx.addIssue({ code: 'custom', path: [cle], message: 'Sélectionnez au moins un destinataire.' });
        v.fichiers.forEach((fichier) => {
            if (fichier.size > 5 * 1024 * 1024) ctx.addIssue({ code: 'custom', path: ['fichiers'], message: `« ${fichier.name} » dépasse 5 Mo.` });
            if (!/\.(pdf|docx?|jpe?g|png)$/i.test(fichier.name)) ctx.addIssue({ code: 'custom', path: ['fichiers'], message: `« ${fichier.name} » : format non accepté.` });
        });
        if (v.statut === 'planifie') {
            if (!v.date_planifiee) ctx.addIssue({ code: 'custom', path: ['date_planifiee'], message: 'Indiquez la date de publication.' });
            else if (v.date_planifiee <= maintenantLocal()) ctx.addIssue({ code: 'custom', path: ['date_planifiee'], message: 'La date doit être dans le futur.' });
        }
    });

type Valeurs = z.infer<typeof schema>;

const CHAMPS_ETAPES: (keyof Valeurs)[][] = [
    ['canaux', 'audience', 'cites', 'batiments', 'chambres', 'etudiants'],
    ['titre', 'priorite', 'contenu', 'fichiers'],
    ['statut', 'date_planifiee'],
    [],
];
const ETAPES = ['Diffusion', 'Contenu', 'Publication', 'Vérification'];

const initiales = (annonce: AnnonceModifiable | null): Valeurs => ({
    canaux: annonce?.canaux.length ? annonce.canaux : ['flux'],
    audience: annonce?.audience ?? 'all',
    cites: annonce?.cibles.cites ?? [],
    batiments: annonce?.cibles.batiments ?? [],
    chambres: annonce?.cibles.chambres ?? [],
    etudiants: annonce?.cibles.etudiants ?? [],
    titre: annonce?.titre ?? '',
    priorite: annonce?.priorite ?? 'normale',
    contenu: annonce?.contenu ?? '',
    fichiers: [],
    statut: annonce && annonce.statut !== 'annule' ? annonce.statut : 'brouillon',
    date_planifiee: annonce?.date_planification ?? '',
});

type Props = {
    open: boolean;
    onClose: () => void;
    /** Annonce à modifier ; null pour une création. */
    annonce: AnnonceModifiable | null;
    urlStore: string | null;
    audiences: { value: string; label: string }[];
    cibles: Cibles;
};

/** Créer ou modifier une annonce (actions `store` / `update` inchangées ; diffusion selon docs/diffusion-annonces.md). */
export function AnnonceDialog({ open, onClose, annonce, urlStore, audiences, cibles }: Props) {
    const modification = annonce !== null;
    const url = modification ? annonce.urls.update : urlStore;
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const {
        control,
        register,
        handleSubmit,
        reset,
        trigger,
        setValue,
        setError,
        getValues,
        formState: { errors, isSubmitting },
    } = useForm<Valeurs>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: initiales(annonce) });
    const [canaux, audience, statut, fichiers] = useWatch({ control, name: ['canaux', 'audience', 'statut', 'fichiers'] });

    useEffect(() => {
        if (open) {
            reset(initiales(annonce));
            setEtape(0);
            setAtteinte(modification ? ETAPES.length - 1 : 0);
            setErreurServeur(null);
        }
    }, [open, annonce, modification, reset]);

    const nomsCites = useMemo(() => new Map(cibles.cites.map((c) => [c.id, c.name])), [cibles.cites]);
    const nomsBatiments = useMemo(() => new Map(cibles.batiments.map((b) => [b.id, b.name])), [cibles.batiments]);
    const options = {
        cites: cibles.cites.map((c) => ({ value: c.id, label: c.name })),
        batiments: cibles.batiments.map((b) => ({ value: b.id, label: b.name, description: nomsCites.get(b.cite_id) })),
        chambres: cibles.chambres.map((c) => ({ value: c.id, label: `Chambre ${c.numero}`, description: nomsBatiments.get(c.batiment_id) })),
        etudiants: cibles.etudiants.map((e) => ({ value: e.id, label: e.name, description: e.matricule ?? undefined })),
    };

    const allerA = async (cible: number) => {
        if (cible <= etape) {
            setEtape(cible);
            return;
        }
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPES[index]))) {
                setEtape(index);
                return;
            }
        }
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const onSubmit = (valeurs: Valeurs) => {
        if (!url) return;
        setErreurServeur(null);
        const cle = CLE_CIBLE[valeurs.audience];
        const donnees: Record<string, unknown> = {
            titre: valeurs.titre,
            contenu: valeurs.contenu,
            canaux: valeurs.canaux,
            audience: valeurs.audience,
            statut: valeurs.statut,
            priorite: valeurs.priorite,
            date_planifiee: valeurs.statut === 'planifie' ? valeurs.date_planifiee : '',
            ...(cle ? { [cle]: valeurs[cle] } : {}),
            ...(!modification && valeurs.fichiers.length ? { fichiers: valeurs.fichiers } : {}),
        };
        return envoyer(url, donnees as RequestPayload, {
            method: modification ? 'put' : 'post',
            forceFormData: !modification && valeurs.fichiers.length > 0,
            onSuccess: onClose,
            onError: (erreurs) => setErreurServeur(repartirErreurs(erreurs, CHAMPS_ETAPES, setError, setEtape)),
        });
    };

    const basculerCanal = (canal: string) => setValue('canaux', canaux.includes(canal) ? canaux.filter((c) => c !== canal) : [...canaux, canal], { shouldValidate: true });

    const v = getValues();
    const cle = CLE_CIBLE[v.audience];
    const destinataires = cle
        ? v[cle].map((id) => options[cle].find((o) => o.value === id)?.label).filter(Boolean).join(', ')
        : 'Tous les étudiants';

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-3xl">
                <form
                    onSubmit={handleSubmit(onSubmit, (fautes) => setEtape(Math.max(0, CHAMPS_ETAPES.findIndex((champs) => champs.some((champ) => fautes[champ])))))}
                    noValidate
                    className="flex min-h-0 flex-col"
                >
                    <DialogHeader>
                        <DialogTitle>{modification ? 'Modifier l’annonce' : 'Nouvelle annonce'}</DialogTitle>
                        <DialogDescription>
                            Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                        </DialogDescription>
                    </DialogHeader>
                    <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} />
                    <DialogBody className="space-y-5">
                        {erreurServeur && <Encadre ton="danger">{erreurServeur}</Encadre>}

                        {etape === 0 && (
                            <>
                                <fieldset>
                                    <legend className="mb-2 text-sm font-medium text-gray-800">
                                        Canaux de diffusion <span className="text-red-600">*</span>
                                    </legend>
                                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                        {CANAUX.map((canal) => (
                                            <ChoixCarte
                                                key={canal.value}
                                                role="checkbox"
                                                actif={canaux.includes(canal.value)}
                                                onClick={() => basculerCanal(canal.value)}
                                                titre={canal.label}
                                                description={canal.description}
                                                icone={canal.icone}
                                            />
                                        ))}
                                    </div>
                                    {errors.canaux?.message && <p className="mt-1.5 text-xs font-medium text-red-600">{errors.canaux.message}</p>}
                                </fieldset>
                                <fieldset>
                                    <legend className="mb-2 text-sm font-medium text-gray-800">
                                        Audience <span className="text-red-600">*</span>
                                    </legend>
                                    <div role="radiogroup" className="grid grid-cols-2 gap-2 sm:grid-cols-5">
                                        {audiences.map((option) => (
                                            <ChoixCarte key={option.value} actif={audience === option.value} onClick={() => setValue('audience', option.value)} titre={option.label} />
                                        ))}
                                    </div>
                                </fieldset>
                                {CLE_CIBLE[audience] && (
                                    <Controller
                                        control={control}
                                        name={CLE_CIBLE[audience]!}
                                        render={({ field }) => {
                                            const cleCible = CLE_CIBLE[audience]!;
                                            const libelles = { cites: 'Cités', batiments: 'Bâtiments', chambres: 'Chambres', etudiants: 'Étudiants résidents' };
                                            return (
                                                <Field id={`an-${cleCible}`} label={libelles[cleCible]} required error={errors[cleCible]?.message} hint="Recherche sans accents ; plusieurs choix possibles.">
                                                    <MultiCombobox
                                                        id={`an-${cleCible}`}
                                                        value={field.value}
                                                        onChange={field.onChange}
                                                        onBlur={field.onBlur}
                                                        options={options[cleCible]}
                                                        placeholder="Sélectionner…"
                                                        invalid={!!errors[cleCible]}
                                                    />
                                                </Field>
                                            );
                                        }}
                                    />
                                )}
                            </>
                        )}

                        {etape === 1 && (
                            <>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-[minmax(0,1fr)_14rem]">
                                    <Field id="an-titre" label="Titre" required error={errors.titre?.message}>
                                        <Input id="an-titre" placeholder="Ex. Réunion d’information des résidents" invalid={!!errors.titre} {...register('titre')} />
                                    </Field>
                                    <fieldset>
                                        <legend className="mb-1.5 text-sm font-medium text-gray-800">
                                            Priorité <span className="text-red-600">*</span>
                                        </legend>
                                        <Controller
                                            control={control}
                                            name="priorite"
                                            render={({ field }) => (
                                                <div role="radiogroup" className="flex h-11 overflow-hidden rounded-brand border border-gray-300">
                                                    {PRIORITES_ANNONCE.map((option) => (
                                                        <button
                                                            key={option.value}
                                                            type="button"
                                                            role="radio"
                                                            aria-checked={field.value === option.value}
                                                            onClick={() => field.onChange(option.value)}
                                                            className={`flex-1 cursor-pointer border-l text-sm first:border-l-0 ${field.value === option.value ? 'bg-primary font-medium text-white' : 'bg-white text-muted hover:bg-soft'}`}
                                                        >
                                                            {option.label}
                                                        </button>
                                                    ))}
                                                </div>
                                            )}
                                        />
                                    </fieldset>
                                </div>
                                <Field id="an-contenu" label="Contenu" required error={errors.contenu?.message} hint="Les SMS et e-mails reprennent le texte sans mise en forme.">
                                    <Controller
                                        control={control}
                                        name="contenu"
                                        render={({ field }) => <RichTextEditor id="an-contenu" value={field.value} onChange={field.onChange} onBlur={field.onBlur} invalid={!!errors.contenu} />}
                                    />
                                </Field>
                                {!modification && (
                                    <Field id="an-fichiers" label="Pièces jointes" error={errors.fichiers?.message} hint="PDF, DOC, JPG ou PNG · 5 Mo maximum par fichier.">
                                        <div className="space-y-2">
                                            <label
                                                htmlFor="an-fichiers"
                                                className="flex cursor-pointer items-center gap-2 rounded-brand border border-dashed border-gray-300 bg-surface px-4 py-3 text-sm text-muted transition-colors hover:border-secondary hover:text-primary"
                                            >
                                                <Paperclip {...ICON} aria-hidden /> Ajouter des fichiers
                                            </label>
                                            <input
                                                id="an-fichiers"
                                                type="file"
                                                multiple
                                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                                className="sr-only"
                                                onChange={(event) => {
                                                    const ajouts = Array.from(event.target.files ?? []);
                                                    setValue('fichiers', [...fichiers, ...ajouts], { shouldValidate: true });
                                                    event.target.value = '';
                                                }}
                                            />
                                            {fichiers.length > 0 && (
                                                <ul className="flex flex-wrap gap-1.5">
                                                    {fichiers.map((fichier, index) => (
                                                        <li key={`${fichier.name}-${index}`} className="inline-flex items-center gap-1 rounded bg-soft py-0.5 pr-1 pl-2 text-xs text-primary">
                                                            {fichier.name}
                                                            <button
                                                                type="button"
                                                                aria-label={`Retirer ${fichier.name}`}
                                                                onClick={() => setValue('fichiers', fichiers.filter((_, i) => i !== index), { shouldValidate: true })}
                                                                className="inline-flex size-4 cursor-pointer items-center justify-center rounded hover:bg-white"
                                                            >
                                                                <X className="size-3" aria-hidden />
                                                            </button>
                                                        </li>
                                                    ))}
                                                </ul>
                                            )}
                                        </div>
                                    </Field>
                                )}
                            </>
                        )}

                        {etape === 2 && (
                            <>
                                <fieldset>
                                    <legend className="mb-2 text-sm font-medium text-gray-800">
                                        Publication <span className="text-red-600">*</span>
                                    </legend>
                                    <div role="radiogroup" className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                        {PUBLICATIONS.map((option) => (
                                            <ChoixCarte key={option.value} actif={statut === option.value} onClick={() => setValue('statut', option.value)} titre={option.label} description={option.description} icone={option.icone} />
                                        ))}
                                    </div>
                                </fieldset>
                                {statut === 'planifie' && (
                                    <Field id="an-date_planifiee" label="Date et heure de publication" required error={errors.date_planifiee?.message}>
                                        <Input id="an-date_planifiee" type="datetime-local" min={maintenantLocal()} invalid={!!errors.date_planifiee} {...register('date_planifiee')} />
                                    </Field>
                                )}
                                {modification && annonce?.statut === 'publie' && (
                                    <Encadre ton="warning">Cette annonce est déjà publiée : le titre et le texte envoyés lors de la première diffusion ne sont pas renvoyés.</Encadre>
                                )}
                            </>
                        )}

                        {etape === 3 && (
                            <>
                                <Lignes
                                    lignes={[
                                        ['Canaux', v.canaux.map((c) => CANAUX.find((canal) => canal.value === c)?.label ?? c).join(', ')],
                                        ['Audience', audiences.find((a) => a.value === v.audience)?.label ?? v.audience],
                                        ['Destinataires', destinataires],
                                        ['Priorité', PRIORITES_ANNONCE.find((p) => p.value === v.priorite)?.label ?? v.priorite],
                                        ['Publication', v.statut === 'planifie' ? `Planifiée le ${dateHeure(v.date_planifiee)}` : (PUBLICATIONS.find((p) => p.value === v.statut)?.label ?? v.statut)],
                                        ...(!modification ? ([['Pièces jointes', v.fichiers.map((f) => f.name).join(', ')]] as [string, string][]) : []),
                                    ]}
                                />
                                <div className="rounded-brand border px-4 py-3">
                                    <p className="text-base font-medium text-gray-900">{v.titre}</p>
                                    <RichTextView html={v.contenu} className="mt-1" />
                                </div>
                                {v.statut === 'publie' && <Encadre ton="warning">Les messages partiront dès l’enregistrement. « Accepté » signifie accepté par le transport, pas lu.</Encadre>}
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
                            {etape < ETAPES.length - 1 ? (
                                <Button key="suivant" size="sm" className="h-10" onClick={() => allerA(etape + 1)}>
                                    Suivant <ArrowRight className="size-4" aria-hidden />
                                </Button>
                            ) : (
                                <Button key="envoyer" type="submit" size="sm" className="h-10" disabled={isSubmitting || !url}>
                                    {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : statut === 'publie' ? <Send className="size-4" aria-hidden /> : <Save className="size-4" aria-hidden />}
                                    {statut === 'publie' ? 'Publier l’annonce' : statut === 'planifie' ? 'Planifier l’annonce' : 'Enregistrer le brouillon'}
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
