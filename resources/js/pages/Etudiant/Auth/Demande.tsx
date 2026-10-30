import { Head, usePage } from '@inertiajs/react';
import { zodResolver } from '@hookform/resolvers/zod';
import { AlertCircle, CheckCircle2, ChevronLeft, ChevronRight, ClipboardList, FileText, Info, Loader2, LogIn, Mail, Phone, Save, Send } from 'lucide-react';
import { type ReactNode, useEffect, useMemo, useRef, useState } from 'react';
import { type Control, Controller, type FieldPath, useForm, useWatch } from 'react-hook-form';
import { toast } from 'sonner';
import { Notice } from '@/components/etudiant/panneau';
import { ICON } from '@/components/layout/app-sidebar';
import { Button, buttonVariants } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
import { Field } from '@/components/ui/field';
import { FileDrop } from '@/components/ui/file-drop';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { avecFormatTelephone, PROPS_TELEPHONE } from '@/lib/telephone';
import { cn } from '@/lib/utils';
import {
    CHAMPS_ETAPE,
    type DemandeHebergement,
    type DocumentDemande,
    ETAPES,
    MAX_SITUATION,
    demandeHebergementSchema,
} from '@/schemas/demande-hebergement';
import type { SharedProps } from '@/types/global';

type Option = { id: string; libelle: string };

type DemandeProps = {
    campagne: { id: string; annee: string; dateLimite: string | null } | null;
    documents: DocumentDemande[];
    genres: Option[];
    typesPiece: Option[];
    groupesSanguins: Option[];
    niveaux: Option[];
    filieres: Option[];
    regions: Option[];
    villes: (Option & { region: string })[];
    liens: { envoyer: string; connexion: string };
    logoUrl: string;
    year: number;
};

const aujourdhui = () => {
    const date = new Date();
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
};

/** Préfixe d'une valeur saisie librement (filière, région, ville absentes des référentiels). */
const LIBRE = 'autre:';

/** Ajoute la valeur saisie librement aux options pour qu'elle reste affichée dans la liste. */
const avecSaisieLibre = (options: { value: string; label: string }[], valeur: string) =>
    valeur.startsWith(LIBRE) ? [...options, { value: valeur, label: valeur.slice(LIBRE.length) }] : options;

/** `filiere: 'autre:Génie civil'` → `{ filiere: '', filiere_autre: 'Génie civil' }` (le serveur rattache ou crée l'entrée). */
const valeurLibre = (cle: string, valeur: string) =>
    valeur.startsWith(LIBRE) ? { [cle]: '', [`${cle}_autre`]: valeur.slice(LIBRE.length) } : { [cle]: valeur };

/** « 2026-2027 » → « 2025-2026 » (valeur proposée pour l'année précédente). */
const anneePrecedente = (annee: string) => {
    const [debut, fin] = annee.split('-').map(Number);
    return debut && fin ? `${debut - 1}-${fin - 1}` : '';
};

/** Page publique « Demande d'hébergement » : création du compte et du premier dossier pendant une campagne ouverte. */
export default function Demande(props: DemandeProps) {
    const { flash, systeme } = usePage<SharedProps>().props;
    const [demarre, setDemarre] = useState(false);

    let contenu: ReactNode;
    if (!props.campagne) {
        contenu = (
            <Carte surtitre="Demande d’hébergement" titre="Aucune campagne ouverte">
                <Notice icone={Info}>Les demandes d’hébergement ne sont pas ouvertes pour le moment. Revenez lorsque le service Logement aura lancé la campagne d’admission.</Notice>
                <LienConnexion href={props.liens.connexion} />
            </Carte>
        );
    } else if (flash.success) {
        contenu = (
            <Carte surtitre={`Campagne ${props.campagne.annee}`} titre="Demande enregistrée">
                <Notice icone={CheckCircle2}>{flash.success}</Notice>
                <p className="mt-3 text-sm text-muted">Vous pourrez suivre l’avancement de votre dossier depuis votre espace étudiant avec les identifiants reçus par e-mail.</p>
            </Carte>
        );
    } else if (!demarre) {
        contenu = <AvantDeCommencer campagne={props.campagne} documents={props.documents} onCommencer={() => setDemarre(true)} />;
    } else {
        contenu = <Formulaire {...props} campagne={props.campagne} />;
    }

    return (
        <>
            <Head title="Demande d’hébergement" />
            <div className="min-h-dvh bg-surface">
                <header className="sticky top-0 z-30 border-b bg-white/95 backdrop-blur">
                    <div className="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-3">
                        <div className="flex min-w-0 items-center gap-3">
                            <img src={props.logoUrl} alt={`Logo ${systeme.nom}`} className="size-10 shrink-0 rounded-full object-contain ring-1 ring-soft" />
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium text-gray-900">{systeme.nom}</p>
                                <p className="truncate text-xs text-muted">Hébergement universitaire</p>
                            </div>
                        </div>
                        <a href={props.liens.connexion} className={cn(buttonVariants({ variant: 'accent', size: 'sm' }), 'shrink-0 font-medium')}>
                            <LogIn {...ICON} />
                            <span className="hidden sm:inline">Déjà inscrit ? </span>Se connecter
                        </a>
                    </div>
                </header>

                <main className="mx-auto flex w-full max-w-3xl flex-col gap-5 px-4 py-6 md:py-8">
                    <div>
                        <h1 className="text-xl font-medium text-gray-900">Demande d’hébergement étudiant</h1>
                        <p className="mt-1 text-sm text-muted">
                            {props.campagne
                                ? `Campagne ${props.campagne.annee}${props.campagne.dateLimite ? ` · dépôt jusqu’au ${props.campagne.dateLimite}` : ''}`
                                : 'Première demande de logement en résidence universitaire.'}
                        </p>
                    </div>
                    {contenu}
                </main>

                <footer className="pb-6 text-center text-xs text-muted">© {props.year} {systeme.nom}</footer>
            </div>
        </>
    );
}

function AvantDeCommencer({ campagne, documents, onCommencer }: { campagne: NonNullable<DemandeProps['campagne']>; documents: DocumentDemande[]; onCommencer: () => void }) {
    const [pret, setPret] = useState(false);

    return (
        <Carte surtitre={`Campagne ${campagne.annee}`} titre="Avant de commencer">
            <p className="text-sm text-muted">
                Le formulaire compte {ETAPES.length} étapes et prend une dizaine de minutes. Préparez les documents suivants (PDF ou image, 2 Mo maximum chacun) :
            </p>
            {documents.length ? (
                <ul className="mt-3 divide-y rounded-brand border">
                    {documents.map((document) => (
                        <li key={document.slug} className="flex items-center gap-3 px-3 py-2.5 text-sm">
                            <FileText {...ICON} className="shrink-0 text-primary" />
                            <span className="min-w-0 flex-1 text-gray-900">{document.nom}</span>
                            <span className={cn('shrink-0 rounded-brand px-2 py-0.5 text-xs', document.requis ? 'bg-accent/15 text-amber-800' : 'bg-surface text-muted')}>
                                {document.requis ? 'Obligatoire' : 'Facultatif'}
                            </span>
                        </li>
                    ))}
                </ul>
            ) : (
                <Notice icone={Info} className="mt-3">
                    Aucun document n’est demandé pour cette campagne.
                </Notice>
            )}
            <p className="mt-3 text-sm text-muted">Votre e-mail, votre numéro de téléphone, votre pièce d’identité et votre matricule doivent être uniques : si vous avez déjà un compte, connectez-vous pour faire une réadmission.</p>

            <label htmlFor="dh-pret" className="mt-4 flex cursor-pointer items-center gap-3 rounded-brand border border-gray-300 p-3 text-sm text-gray-900 hover:bg-surface">
                <Checkbox id="dh-pret" checked={pret} onCheckedChange={(valeur) => setPret(valeur === true)} />
                Je dispose de tous les documents requis
            </label>
            <div className="mt-4 flex justify-end">
                <Button variant="accent" onClick={onCommencer} disabled={!pret} className="w-full font-medium sm:w-fit">
                    Commencer
                    <ChevronRight {...ICON} />
                </Button>
            </div>
        </Carte>
    );
}

function Formulaire({
    campagne,
    documents,
    genres,
    typesPiece,
    groupesSanguins,
    niveaux,
    filieres,
    regions,
    villes,
    liens,
}: DemandeProps & { campagne: NonNullable<DemandeProps['campagne']> }) {
    const { flash } = usePage<SharedProps>().props;
    const haut = useRef<HTMLFormElement>(null);
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const schema = useMemo(() => demandeHebergementSchema(documents), [documents]);

    const {
        register,
        control,
        handleSubmit,
        trigger,
        setError,
        setValue,
        formState: { errors, isSubmitting },
    } = useForm<DemandeHebergement>({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues: {
            genre: '',
            name: '',
            date_naissance: '',
            lieu_naissance: '',
            telephone: '',
            email: '',
            type_piece: '',
            num_piece: '',
            date_exp_piece: '',
            groupe_sanguin: '',
            matricule: '',
            annee_academique_precedent: anneePrecedente(campagne.annee),
            niveau_precedent: '',
            niveau: '',
            filiere: '',
            moyenne: '',
            credit_valider: '',
            redoublement: false,
            boursier: false,
            pmr: false,
            cas_social: '',
            region: '',
            ville: '',
            adresse: '',
            nationalite: 'IVOIRIENNE',
            distance: '',
            documents: Object.fromEntries(documents.map((document) => [document.slug, null])),
            certifie: false,
            save_as_draft: false,
        },
    });

    const region = useWatch({ control, name: 'region' });
    const brouillon = useWatch({ control, name: 'save_as_draft' });
    const longueur = (useWatch({ control, name: 'cas_social' }) ?? '').length;
    const filiere = useWatch({ control, name: 'filiere' });
    const ville = useWatch({ control, name: 'ville' });
    const villesRegion = useMemo(() => villes.filter((item) => item.region === region).map((item) => ({ value: item.id, label: item.libelle })), [villes, region]);

    // Changer de région invalide la ville choisie.
    const regionPrecedente = useRef(region);
    useEffect(() => {
        if (regionPrecedente.current !== region) setValue('ville', '');
        regionPrecedente.current = region;
    }, [region, setValue]);

    const afficher = (index: number) => {
        setEtape(index);
        haut.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const allerA = async (cible: number) => {
        // Avancer valide les étapes intermédiaires ; reculer est toujours permis.
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPE[index]))) {
                afficher(index);
                return;
            }
        }
        afficher(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const soumettre = handleSubmit(
        async (donnees) => {
            const { documents: fichiers, certifie: _certifie, ...champs } = donnees;
            const binaire = (valeur: boolean) => (valeur ? 1 : 0);

            await envoyer(
                liens.envoyer,
                {
                    ...champs,
                    ...valeurLibre('filiere', champs.filiere),
                    ...valeurLibre('region', champs.region),
                    ...valeurLibre('ville', champs.ville),
                    moyenne: champs.moyenne.replace(',', '.'),
                    credit_valider: champs.credit_valider.replace(',', '.'),
                    distance: champs.distance.replace(',', '.'),
                    redoublement: binaire(champs.redoublement),
                    boursier: binaire(champs.boursier),
                    pmr: binaire(champs.pmr),
                    save_as_draft: binaire(champs.save_as_draft),
                    id_campagne: campagne.id,
                    annee_academique_actuel: campagne.annee,
                    // Chaque pièce est envoyée sous le slug du document, comme l'attend le serveur.
                    ...Object.fromEntries(Object.entries(fichiers).filter(([, fichier]) => fichier)),
                },
                {
                    forceFormData: true,
                    onError: (erreurs) => {
                        // Erreurs serveur sous le champ concerné, puis retour à l'étape du premier champ fautif.
                        let fautive = -1;
                        Object.entries(erreurs).forEach(([cle, message]) => {
                            const document = documents.some((item) => item.slug === cle);
                            // Les erreurs d'une saisie libre (`filiere_autre`…) s'affichent sous le champ de la liste.
                            const nom = cle.replace(/_autre$/, '');
                            const champ = (document ? `documents.${cle}` : nom) as FieldPath<DemandeHebergement>;
                            const index = document ? ETAPES.length - 1 : CHAMPS_ETAPE.findIndex((liste) => (liste as string[]).includes(nom));
                            if (index < 0) {
                                toast.error(message);
                                return;
                            }
                            setError(champ, { type: 'server', message });
                            if (fautive < 0 || index < fautive) fautive = index;
                        });
                        if (fautive >= 0) afficher(fautive);
                    },
                },
            );
        },
        () => toast.error('Certains champs sont à corriger avant l’envoi.'),
    );

    const derniere = etape === ETAPES.length - 1;

    return (
        <form ref={haut} onSubmit={soumettre} noValidate className="scroll-mt-20 rounded-brand border bg-white">
            <div className="px-4 pt-4 md:px-5 md:pt-5">
                <p className="text-xs text-muted">Campagne {campagne.annee}</p>
                <h2 className="text-base font-medium text-gray-900">{ETAPES[etape]}</h2>
                <p className="mt-1 text-xs text-muted">
                    Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                </p>
            </div>
            <Stepper steps={[...ETAPES]} current={etape} reached={atteinte} onStepClick={allerA} className="mt-3 overflow-x-auto px-4 md:px-5 [&_li>button>span:last-child]:hidden sm:[&_li>button>span:last-child]:inline" />

            <div className="flex flex-col gap-4 p-4 md:p-5">
                {flash.error && (
                    <div role="alert" className="flex gap-2 rounded-brand border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        <AlertCircle {...ICON} className="mt-0.5 shrink-0" />
                        <span>{flash.error}</span>
                    </div>
                )}

                {etape === 0 && (
                    <Grille>
                        <ChampListe control={control} name="genre" id="dh-genre" label="Civilité" options={genres} placeholder="Choisir la civilité" erreur={errors.genre?.message} requis />
                        <Field id="dh-nom" label="Nom et prénoms" required error={errors.name?.message}>
                            <Input id="dh-nom" autoComplete="name" placeholder="Ex. : Kouassi Aya Marie" invalid={!!errors.name} {...register('name')} />
                        </Field>
                        <ChampDate control={control} name="date_naissance" id="dh-naissance" label="Date de naissance" max={aujourdhui()} erreur={errors.date_naissance?.message} />
                        <Field id="dh-lieu" label="Lieu de naissance" required error={errors.lieu_naissance?.message}>
                            <Input id="dh-lieu" placeholder="Ex. : Bondoukou" invalid={!!errors.lieu_naissance} {...register('lieu_naissance')} />
                        </Field>
                        <Field id="dh-telephone" label="Téléphone" required error={errors.telephone?.message} hint="Numéro à 10 chiffres, sans l’indicatif +225.">
                            <Input id="dh-telephone" {...PROPS_TELEPHONE} icon={<Phone />} invalid={!!errors.telephone} {...avecFormatTelephone(register('telephone'))} />
                        </Field>
                        <Field id="dh-email" label="Adresse e-mail" required error={errors.email?.message} hint="Votre mot de passe y sera envoyé ; elle servira d’identifiant de connexion.">
                            <Input id="dh-email" type="email" autoComplete="email" placeholder="votre.email@exemple.com" icon={<Mail />} invalid={!!errors.email} {...register('email')} />
                        </Field>
                        <ChampListe control={control} name="type_piece" id="dh-type-piece" label="Type de pièce" options={typesPiece} placeholder="Choisir le type de pièce" erreur={errors.type_piece?.message} requis />
                        <Field id="dh-num-piece" label="Numéro de la pièce" required error={errors.num_piece?.message}>
                            <Input id="dh-num-piece" placeholder="Numéro inscrit sur la pièce" invalid={!!errors.num_piece} {...register('num_piece')} />
                        </Field>
                        <ChampDate control={control} name="date_exp_piece" id="dh-exp-piece" label="Date d’expiration de la pièce" erreur={errors.date_exp_piece?.message} />
                        <ChampListe control={control} name="groupe_sanguin" id="dh-groupe" label="Groupe sanguin" options={groupesSanguins} placeholder="Choisir (facultatif)" erreur={errors.groupe_sanguin?.message} />
                    </Grille>
                )}

                {etape === 1 && (
                    <>
                        <Grille>
                            <Field id="dh-matricule" label="Matricule" required error={errors.matricule?.message}>
                                <Input id="dh-matricule" placeholder="Matricule universitaire" invalid={!!errors.matricule} {...register('matricule')} />
                            </Field>
                            <Field id="dh-annee-prec" label="Année académique précédente" required error={errors.annee_academique_precedent?.message}>
                                <Input id="dh-annee-prec" inputMode="numeric" placeholder="Ex. : 2025-2026" invalid={!!errors.annee_academique_precedent} {...register('annee_academique_precedent')} />
                            </Field>
                            <ChampListe control={control} name="niveau_precedent" id="dh-niveau-prec" label="Niveau d’étude précédent" options={niveaux} placeholder="Choisir un niveau" erreur={errors.niveau_precedent?.message} requis />
                            <ChampListe control={control} name="niveau" id="dh-niveau" label="Niveau d’étude actuel" options={niveaux} placeholder="Choisir un niveau" erreur={errors.niveau?.message} requis />
                            <Field id="dh-filiere" label="Filière" required error={errors.filiere?.message} hint="Absente de la liste ? Tapez son nom puis choisissez « Utiliser ».">
                                <Controller
                                    control={control}
                                    name="filiere"
                                    render={({ field }) => (
                                        <Combobox
                                            id="dh-filiere"
                                            value={field.value}
                                            onChange={field.onChange}
                                            onBlur={field.onBlur}
                                            options={avecSaisieLibre(
                                                filieres.map((item) => ({ value: item.id, label: item.libelle })),
                                                filiere,
                                            )}
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
                            <Field id="dh-moyenne" label="Moyenne générale ou note du bac" required error={errors.moyenne?.message} hint="Sur 20.">
                                <Input id="dh-moyenne" inputMode="decimal" placeholder="Ex. : 12,50" invalid={!!errors.moyenne} {...register('moyenne')} />
                            </Field>
                            <Field id="dh-credits" label="Crédits validés" error={errors.credit_valider?.message} hint="Nombre de crédits validés l’année précédente, sur 60.">
                                <Input id="dh-credits" inputMode="numeric" placeholder="Ex. : 48" invalid={!!errors.credit_valider} {...register('credit_valider')} />
                            </Field>
                        </Grille>
                        <CaseACocher control={control} name="redoublement" id="dh-redoublement" libelle="Je redouble ce niveau" aide="Laissez décoché pour une première inscription à ce niveau." />
                    </>
                )}

                {etape === 2 && (
                    <>
                        <CaseACocher control={control} name="boursier" id="dh-boursier" libelle="Je suis boursier" />
                        <CaseACocher control={control} name="pmr" id="dh-pmr" libelle="Je suis une personne à mobilité réduite (PMR)" aide="Un logement adapté sera recherché ; le service Logement pourra vous demander un justificatif." />
                        <Field
                            id="dh-situation"
                            label="Situation particulière"
                            error={errors.cas_social?.message}
                            hint={
                                <span className={cn(longueur > MAX_SITUATION - 50 && 'text-red-700')}>
                                    Facultatif · {longueur} / {MAX_SITUATION}
                                </span>
                            }
                        >
                            <Textarea id="dh-situation" rows={4} maxLength={MAX_SITUATION} placeholder="Décrivez toute situation que nous devrions connaître (santé, famille, revenus…)." invalid={!!errors.cas_social} {...register('cas_social')} />
                        </Field>
                    </>
                )}

                {etape === 3 && (
                    <Grille>
                        <Field id="dh-region" label="Région" required error={errors.region?.message} hint="Absente de la liste ? Tapez son nom puis choisissez « Utiliser ».">
                            <Controller
                                control={control}
                                name="region"
                                render={({ field }) => (
                                    <Combobox
                                        id="dh-region"
                                        value={field.value}
                                        onChange={field.onChange}
                                        onBlur={field.onBlur}
                                        options={avecSaisieLibre(
                                            regions.map((item) => ({ value: item.id, label: item.libelle })),
                                            region,
                                        )}
                                        onCreate={(texte) => field.onChange(LIBRE + texte)}
                                        createLabel="Utiliser"
                                        emptyMessage="Saisissez le nom de votre région."
                                        placeholder="Choisir une région"
                                        searchPlaceholder="Rechercher une région…"
                                        invalid={!!errors.region}
                                    />
                                )}
                            />
                        </Field>
                        <Field id="dh-ville" label="Ville" required error={errors.ville?.message} hint={region ? 'Absente de la liste ? Tapez son nom puis choisissez « Utiliser ».' : 'Choisissez d’abord la région.'}>
                            <Controller
                                control={control}
                                name="ville"
                                render={({ field }) => (
                                    <Combobox
                                        id="dh-ville"
                                        value={field.value}
                                        onChange={field.onChange}
                                        onBlur={field.onBlur}
                                        options={avecSaisieLibre(villesRegion, ville)}
                                        onCreate={(texte) => field.onChange(LIBRE + texte)}
                                        createLabel="Utiliser"
                                        placeholder="Choisir une ville"
                                        searchPlaceholder="Rechercher une ville…"
                                        emptyMessage="Saisissez le nom de votre ville."
                                        disabled={!region}
                                        invalid={!!errors.ville}
                                    />
                                )}
                            />
                        </Field>
                        <Field id="dh-adresse" label="Adresse complète" required error={errors.adresse?.message}>
                            <Input id="dh-adresse" autoComplete="street-address" placeholder="Quartier, rue, repère…" invalid={!!errors.adresse} {...register('adresse')} />
                        </Field>
                        <Field id="dh-nationalite" label="Nationalité" required error={errors.nationalite?.message}>
                            <Input id="dh-nationalite" placeholder="Ex. : IVOIRIENNE" invalid={!!errors.nationalite} {...register('nationalite')} />
                        </Field>
                        <Field id="dh-distance" label="Distance domicile – campus (km)" error={errors.distance?.message}>
                            <Input id="dh-distance" inputMode="decimal" placeholder="Ex. : 10" invalid={!!errors.distance} {...register('distance')} />
                        </Field>
                    </Grille>
                )}

                {derniere && (
                    <>
                        {documents.length ? (
                            documents.map((document) => {
                                const erreur = errors.documents?.[document.slug]?.message;
                                const formats = document.formats.length ? document.formats.join(', ').toUpperCase() : 'PDF, JPG, PNG';
                                return (
                                    <Field key={document.slug} id={`dh-doc-${document.slug}`} label={document.nom} required={document.requis} error={erreur}>
                                        <Controller
                                            control={control}
                                            name={`documents.${document.slug}`}
                                            render={({ field }) => (
                                                <FileDrop
                                                    id={`dh-doc-${document.slug}`}
                                                    file={field.value ?? null}
                                                    onChange={(fichier) => {
                                                        field.onChange(fichier);
                                                        void trigger(`documents.${document.slug}`);
                                                    }}
                                                    accept={document.formats.length ? document.formats.map((format) => `.${format}`).join(',') : undefined}
                                                    title="Choisir le fichier"
                                                    hint={`${formats} · 2 Mo maximum${document.requis ? '' : ' · facultatif'}`}
                                                    invalid={!!erreur}
                                                />
                                            )}
                                        />
                                    </Field>
                                );
                            })
                        ) : (
                            <Notice icone={Info}>Aucun document n’est demandé pour cette campagne.</Notice>
                        )}

                        <div className="flex flex-col gap-2 border-t pt-4">
                            <CaseACocher control={control} name="certifie" id="dh-certifie" libelle="Je certifie l’exactitude des informations fournies" requis erreur={errors.certifie?.message} />
                            <CaseACocher
                                control={control}
                                name="save_as_draft"
                                id="dh-brouillon"
                                libelle="Enregistrer en brouillon"
                                aide="Le dossier sera créé sans être soumis ; vous pourrez le compléter et le soumettre depuis votre espace étudiant."
                            />
                        </div>
                    </>
                )}
            </div>

            <div className="flex flex-col-reverse gap-2 border-t p-4 sm:flex-row sm:justify-between md:px-5">
                {etape === 0 ? (
                    <a href={liens.connexion} className={cn(buttonVariants({ variant: 'soft-accent' }), 'font-medium')}>
                        Annuler
                    </a>
                ) : (
                    <Button variant="soft-accent" onClick={() => afficher(etape - 1)} disabled={isSubmitting} className="font-medium">
                        <ChevronLeft {...ICON} />
                        Précédent
                    </Button>
                )}
                {/* Clés distinctes : sans elles, React transforme le bouton « Suivant » cliqué en bouton d'envoi pendant le clic, ce qui soumettait le formulaire à l'arrivée sur l'étape Documents. */}
                {derniere ? (
                    <Button key="envoyer" type="submit" variant="accent" disabled={isSubmitting} className="font-medium">
                        {isSubmitting ? <Loader2 {...ICON} className="animate-spin" /> : brouillon ? <Save {...ICON} /> : <Send {...ICON} />}
                        {brouillon ? 'Enregistrer le brouillon' : 'Soumettre ma demande'}
                    </Button>
                ) : (
                    <Button key="suivant" variant="accent" onClick={() => allerA(etape + 1)} className="font-medium">
                        Suivant
                        <ChevronRight {...ICON} />
                    </Button>
                )}
            </div>
        </form>
    );
}

type ChampProps = {
    control: Control<DemandeHebergement>;
    id: string;
    label: string;
    erreur?: string;
};

type NomTexte = 'genre' | 'type_piece' | 'groupe_sanguin' | 'niveau_precedent' | 'niveau';

function ChampListe({ control, name, id, label, options, placeholder, erreur, requis }: ChampProps & { name: NomTexte; options: Option[]; placeholder: string; requis?: boolean }) {
    return (
        <Field id={id} label={label} required={requis} error={erreur}>
            <Controller
                control={control}
                name={name}
                render={({ field }) => (
                    <Select value={field.value} onValueChange={field.onChange}>
                        <SelectTrigger id={id} invalid={!!erreur} onBlur={field.onBlur}>
                            <SelectValue placeholder={placeholder} />
                        </SelectTrigger>
                        <SelectContent>
                            {options.map((item) => (
                                <SelectItem key={item.id} value={item.id}>
                                    {item.libelle}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
            />
        </Field>
    );
}

function ChampDate({ control, name, id, label, erreur, max }: ChampProps & { name: 'date_naissance' | 'date_exp_piece'; max?: string }) {
    return (
        <Field id={id} label={label} required error={erreur}>
            <Controller
                control={control}
                name={name}
                render={({ field }) => (
                    <DatePicker
                        id={id}
                        value={field.value}
                        onChange={(valeur) => {
                            field.onChange(valeur);
                            field.onBlur();
                        }}
                        max={max}
                        listesMoisAnnee
                        invalid={!!erreur}
                    />
                )}
            />
        </Field>
    );
}

function CaseACocher({
    control,
    name,
    id,
    libelle,
    aide,
    requis,
    erreur,
}: Omit<ChampProps, 'label'> & { name: 'redoublement' | 'boursier' | 'pmr' | 'certifie' | 'save_as_draft'; libelle: string; aide?: string; requis?: boolean }) {
    return (
        <div>
            <label htmlFor={id} className={cn('flex cursor-pointer items-start gap-3 rounded-brand border border-gray-300 p-3 text-sm text-gray-900 hover:bg-surface', erreur && 'border-red-500')}>
                <Controller
                    control={control}
                    name={name}
                    render={({ field }) => <Checkbox id={id} checked={field.value} onCheckedChange={(valeur) => field.onChange(valeur === true)} className="mt-0.5" aria-invalid={!!erreur || undefined} />}
                />
                <span className="min-w-0">
                    {libelle}
                    {requis && <span className="ml-0.5 text-red-600">*</span>}
                    {aide && <span className="mt-0.5 block text-xs text-muted">{aide}</span>}
                </span>
            </label>
            {erreur && (
                <p role="alert" className="mt-1.5 text-xs font-medium text-red-600">
                    {erreur}
                </p>
            )}
        </div>
    );
}

function Grille({ children }: { children: ReactNode }) {
    return <div className="grid gap-4 sm:grid-cols-2">{children}</div>;
}

function Carte({ surtitre, titre, children }: { surtitre: string; titre: string; children: ReactNode }) {
    return (
        <section className="rounded-brand border bg-white p-4 md:p-5">
            <div className="mb-3 flex items-center gap-3">
                <span className="grid size-10 shrink-0 place-items-center rounded-brand bg-soft text-primary" aria-hidden>
                    <ClipboardList {...ICON} />
                </span>
                <div className="min-w-0">
                    <p className="text-xs text-muted">{surtitre}</p>
                    <h2 className="text-base font-medium text-gray-900">{titre}</h2>
                </div>
            </div>
            {children}
        </section>
    );
}

function LienConnexion({ href }: { href: string }) {
    return (
        <a href={href} className={cn(buttonVariants({ variant: 'accent' }), 'mt-4 w-full font-medium sm:w-fit')}>
            <LogIn {...ICON} />
            Accéder à mon espace
        </a>
    );
}
