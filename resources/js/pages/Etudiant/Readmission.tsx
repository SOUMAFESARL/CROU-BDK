import { zodResolver } from '@hookform/resolvers/zod';
import { ChevronLeft, ChevronRight, Info, Loader2, Send } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Controller, type FieldPath, type Resolver, useForm, useWatch } from 'react-hook-form';
import { toast } from 'sonner';
import { Notice, Panneau } from '@/components/etudiant/panneau';
import { ICON } from '@/components/layout/app-sidebar';
import { EtudiantLayout } from '@/components/layout/etudiant-layout';
import { Button, buttonVariants } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Combobox } from '@/components/ui/combobox';
import { Field } from '@/components/ui/field';
import { FileDrop } from '@/components/ui/file-drop';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { cn } from '@/lib/utils';
import type { DocumentDemande } from '@/schemas/demande-hebergement';
import { CHAMPS_ETAPE, ETAPES, MAX_SITUATION, readmissionSchema, type Readmission as ReadmissionForm } from '@/schemas/readmission';

type Option = { id: string; libelle: string };

type ReadmissionProps = {
    campagnes: (Option & { dateLimite: string | null; documents: DocumentDemande[] })[];
    niveaux: Option[];
    filieres: Option[];
    /** Filière de la dernière demande de l'étudiant, choisie par défaut. */
    filierePrecedente: string | null;
    liens: { envoyer: string; demandes: string };
};

/** Préfixe d'une filière saisie librement (absente du référentiel), comme dans la demande d'admission. */
const LIBRE = 'autre:';

/** Page « Réadmission » : nouvelle demande de logement avec le compte existant, en quatre étapes. */
export default function Readmission(props: ReadmissionProps) {
    const { campagnes, liens } = props;
    return (
        <EtudiantLayout title="Réadmission">
            <header className="mb-5">
                <h1 className="page-title text-xl font-medium text-gray-900">Demande de réadmission</h1>
                <p className="mt-1 text-sm text-muted">Votre compte et vos documents sont conservés. Votre demande sera examinée selon les règles de la campagne.</p>
            </header>

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5">
                {campagnes.length ? (
                    <Formulaire {...props} />
                ) : (
                    <Panneau surtitre="Réadmission" titre="Aucune campagne ouverte">
                        <Notice icone={Info}>Aucune campagne n’est active pour le moment. Revenez lorsque le service Logement aura ouvert les demandes.</Notice>
                        <a href={liens.demandes} className={cn(buttonVariants({ variant: 'secondary' }), 'mt-4 w-full font-medium sm:w-fit')}>
                            Suivre mes demandes
                            <ChevronRight {...ICON} />
                        </a>
                    </Panneau>
                )}
            </div>
        </EtudiantLayout>
    );
}

function Formulaire({ campagnes, niveaux, filieres, filierePrecedente, liens }: ReadmissionProps) {
    const [etape, setEtape] = useState(0);
    // Pièces demandées par la campagne choisie : le schéma de validation suit la campagne sélectionnée.
    const documentsDe = (campagneId: string | undefined) => campagnes.find((item) => item.id === campagneId)?.documents ?? [];
    const resolver: Resolver<ReadmissionForm> = (donnees, contexte, options) =>
        zodResolver(readmissionSchema(documentsDe(donnees.campagne_id)))(donnees, contexte, options) as ReturnType<Resolver<ReadmissionForm>>;
    const {
        register,
        control,
        handleSubmit,
        trigger,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<ReadmissionForm>({
        resolver,
        defaultValues: {
            campagne_id: campagnes.length === 1 ? campagnes[0].id : '',
            niveau: '',
            filiere: filierePrecedente ?? '',
            moyenne: '',
            credit_valider: '',
            boursier: false,
            documents: Object.fromEntries(campagnes.flatMap((item) => item.documents).map((document) => [document.slug, null])),
            cas_social: '',
        },
        mode: 'onTouched',
    });
    const valeurs = useWatch({ control });
    const documents = useMemo(() => campagnes.find((item) => item.id === valeurs.campagne_id)?.documents ?? [], [campagnes, valeurs.campagne_id]);

    // Champs obligatoires encore vides : tant qu'il en reste, le bouton d'envoi est désactivé.
    const manquants = [
        !valeurs.campagne_id && 'la campagne',
        !valeurs.niveau && 'le niveau d’étude',
        !valeurs.filiere && 'la filière',
        !valeurs.moyenne?.trim() && 'la moyenne',
        !valeurs.credit_valider?.trim() && 'les crédits validés',
        ...documents.filter((document) => document.requis && !valeurs.documents?.[document.slug]).map((document) => document.nom),
    ].filter((libelle): libelle is string => !!libelle);

    // Navigation libre entre les étapes : la validation complète a lieu à l'envoi.
    const soumettre = handleSubmit(async (donnees) => {
        const { documents: fichiers, ...champs } = donnees;
        const slugs = documents.map((document) => document.slug);
        await envoyer(
            liens.envoyer,
            {
                ...champs,
                // Filière saisie librement : `filiere_autre`, rattachée ou créée par le serveur.
                ...(champs.filiere.startsWith(LIBRE) ? { filiere: '', filiere_autre: champs.filiere.slice(LIBRE.length) } : {}),
                moyenne: champs.moyenne.replace(',', '.'),
                credit_valider: champs.credit_valider.replace(',', '.'),
                boursier: champs.boursier ? 1 : 0,
                // Chaque pièce de la campagne choisie est envoyée sous le slug du document, comme l'attend le serveur.
                ...Object.fromEntries(Object.entries(fichiers).filter(([slug, fichier]) => fichier && slugs.includes(slug))),
            },
            {
                forceFormData: true,
                onError: (erreurs) => {
                    // Erreurs serveur sous le champ concerné, puis retour à l'étape du premier champ fautif.
                    let fautive = -1;
                    Object.entries(erreurs).forEach(([entree, message]) => {
                        // L'erreur d'une filière saisie librement s'affiche sous la liste des filières.
                        const cle = entree === 'filiere_autre' ? 'filiere' : entree;
                        const piece = slugs.includes(cle);
                        const index = CHAMPS_ETAPE.findIndex((liste) => (liste as string[]).includes(piece ? 'documents' : cle));
                        if (index < 0) {
                            toast.error(message);
                            return;
                        }
                        setError((piece ? `documents.${cle}` : cle) as FieldPath<ReadmissionForm>, { type: 'server', message });
                        if (fautive < 0 || index < fautive) fautive = index;
                    });
                    if (fautive >= 0) setEtape(fautive);
                },
            },
        );
    }, (erreurs) => {
        // Saisie invalide : retour à la première étape qui contient une erreur.
        const fautive = CHAMPS_ETAPE.findIndex((liste) => liste.some((champ) => champ in erreurs));
        if (fautive >= 0) setEtape(fautive);
    });

    const campagne = campagnes.find((item) => item.id === valeurs.campagne_id);

    return (
        <form onSubmit={soumettre} noValidate className="rounded-brand border bg-white">
            <div className="px-4 pt-4 md:px-5 md:pt-5">
                <p className="text-xs text-muted">Réadmission</p>
                <h2 className="text-base font-medium text-gray-900">Nouvelle demande de logement</h2>
                <p className="mt-1 text-xs text-muted">
                    Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                </p>
            </div>
            <Stepper steps={ETAPES} current={etape} reached={ETAPES.length - 1} onStepClick={setEtape} className="mt-3 px-4 md:px-5" />

            <div className="flex flex-col gap-4 p-4 md:p-5">
                {etape === 0 && (
                    <>
                        <Field id="rd-campagne" label="Campagne" required error={errors.campagne_id?.message} hint={campagne?.dateLimite ? `Date limite de dépôt : ${campagne.dateLimite}` : undefined}>
                            <Controller
                                control={control}
                                name="campagne_id"
                                render={({ field }) => (
                                    <Select value={field.value} onValueChange={field.onChange}>
                                        <SelectTrigger id="rd-campagne" invalid={!!errors.campagne_id} onBlur={field.onBlur}>
                                            <SelectValue placeholder="Choisir une campagne" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {campagnes.map((item) => (
                                                <SelectItem key={item.id} value={item.id}>
                                                    {item.libelle}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                        </Field>
                        <Field id="rd-niveau" label="Niveau d’étude actuel" required error={errors.niveau?.message}>
                            <Controller
                                control={control}
                                name="niveau"
                                render={({ field }) => (
                                    <Select value={field.value} onValueChange={field.onChange}>
                                        <SelectTrigger id="rd-niveau" invalid={!!errors.niveau} onBlur={field.onBlur}>
                                            <SelectValue placeholder="Choisir un niveau" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {niveaux.map((item) => (
                                                <SelectItem key={item.id} value={item.id}>
                                                    {item.libelle}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                        </Field>
                        <Field id="rd-filiere" label="Filière" required error={errors.filiere?.message}>
                            <Controller
                                control={control}
                                name="filiere"
                                render={({ field }) => (
                                    <Combobox
                                        id="rd-filiere"
                                        value={field.value}
                                        onChange={field.onChange}
                                        onBlur={field.onBlur}
                                        options={[
                                            ...filieres.map((item) => ({ value: item.id, label: item.libelle })),
                                            ...(field.value.startsWith(LIBRE) ? [{ value: field.value, label: field.value.slice(LIBRE.length) }] : []),
                                        ]}
                                        onCreate={(texte) => field.onChange(LIBRE + texte)}
                                        createLabel="Utiliser"
                                        emptyMessage="Saisissez le nom de votre filière."
                                        placeholder="Choisir une filière"
                                        searchPlaceholder="Rechercher une filière…"
                                        invalid={!!errors.filiere}
                                    />
                                )}
                            />
                        </Field>
                    </>
                )}

                {etape === 1 && (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field id="rd-moyenne" label="Moyenne sur 20" required error={errors.moyenne?.message} hint="Moyenne de l’année écoulée.">
                                <Input id="rd-moyenne" inputMode="decimal" placeholder="Ex. : 12,50" invalid={!!errors.moyenne} {...register('moyenne')} />
                            </Field>
                            <Field id="rd-credits" label="Crédits validés" required error={errors.credit_valider?.message} hint="Crédits de l’année écoulée, sur 60.">
                                <Input id="rd-credits" inputMode="numeric" placeholder="Ex. : 60" invalid={!!errors.credit_valider} {...register('credit_valider')} />
                            </Field>
                        </div>
                        <label htmlFor="rd-boursier" className="flex cursor-pointer items-center gap-3 rounded-brand border border-gray-300 p-3 text-sm text-gray-900 hover:bg-surface">
                            <Controller
                                control={control}
                                name="boursier"
                                render={({ field }) => <Checkbox id="rd-boursier" checked={field.value} onCheckedChange={(v) => field.onChange(v === true)} />}
                            />
                            Je suis boursier
                        </label>
                    </>
                )}

                {etape === 2 &&
                    (documents.length ? (
                        documents.map((document) => {
                            const erreur = errors.documents?.[document.slug]?.message;
                            return (
                                <Field key={document.slug} id={`rd-doc-${document.slug}`} label={document.nom} required={document.requis} error={erreur}>
                                    <Controller
                                        control={control}
                                        name={`documents.${document.slug}`}
                                        render={({ field }) => (
                                            <FileDrop
                                                id={`rd-doc-${document.slug}`}
                                                file={field.value ?? null}
                                                onChange={(fichier) => {
                                                    field.onChange(fichier);
                                                    void trigger(`documents.${document.slug}`);
                                                }}
                                                accept={document.formats.map((format) => `.${format}`).join(',')}
                                                title="Choisir le fichier"
                                                hint={`${document.formats.join(', ').toUpperCase()} · 2 Mo maximum${document.requis ? '' : ' · facultatif'}`}
                                                invalid={!!erreur}
                                            />
                                        )}
                                    />
                                </Field>
                            );
                        })
                    ) : (
                        <Notice icone={Info}>Aucun document n’est demandé pour cette campagne.</Notice>
                    ))}

                {etape === 3 && (
                    <Field id="rd-situation" label="Situation sociale ou observations" error={errors.cas_social?.message}>
                        <Textarea id="rd-situation" rows={4} maxLength={MAX_SITUATION} placeholder="Précisez toute situation utile à l’examen de votre demande." invalid={!!errors.cas_social} {...register('cas_social')} />
                    </Field>
                )}
            </div>

            {etape === ETAPES.length - 1 && manquants.length > 0 && (
                <p className="border-t px-4 pt-3 text-xs text-muted md:px-5">
                    Pour soumettre votre demande, complétez : <span className="text-gray-900">{manquants.join(', ')}</span>.
                </p>
            )}
            <div className={cn('flex flex-col-reverse gap-2 p-4 sm:flex-row sm:justify-between md:px-5', !(etape === ETAPES.length - 1 && manquants.length > 0) && 'border-t')}>
                {etape === 0 ? (
                    <a href={liens.demandes} className={cn(buttonVariants({ variant: 'outline' }), 'font-medium')}>
                        Mes demandes
                    </a>
                ) : (
                    <Button variant="outline" onClick={() => setEtape(etape - 1)} disabled={isSubmitting} className="font-medium">
                        <ChevronLeft {...ICON} />
                        Précédent
                    </Button>
                )}
                {etape < ETAPES.length - 1 ? (
                    <Button key="suivant" onClick={() => setEtape(etape + 1)} className="font-medium">
                        Suivant
                        <ChevronRight {...ICON} />
                    </Button>
                ) : (
                    <Button key="envoyer" type="submit" disabled={isSubmitting || manquants.length > 0} className="font-medium">
                        {isSubmitting ? <Loader2 {...ICON} className="animate-spin" /> : <Send {...ICON} />}
                        Soumettre ma demande
                    </Button>
                )}
            </div>
        </form>
    );
}

