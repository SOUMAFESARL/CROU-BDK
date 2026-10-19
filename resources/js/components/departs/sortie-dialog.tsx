import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, CheckCheck, ClipboardCopy, Coins, DoorOpen, Info, KeyRound, Loader2, Lock, Plus, TriangleAlert, Trash2, Wrench, Ban } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Controller, useFieldArray, useForm, useWatch, type Control, type FieldErrors, type UseFormRegister, type UseFormSetValue } from 'react-hook-form';
import { toast } from 'sonner';
import { correspondant, logementDe, RANG, type Constat, type ResidentEdl } from '@/components/departs/edl';
import { fcfa } from '@/components/departs/shared';
import { PhotosEdl } from '@/components/etudiant/photos-edl';
import { SignaturePad } from '@/components/etudiant/signature-pad';
import { ICON } from '@/components/layout/app-sidebar';
import { ErreurGenerale } from '@/components/parametrage-hebergement/shared';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { ChoixCarte, Encadre, Lignes } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';
import { dateFr, sansAccents } from '@/lib/text';
import { cn } from '@/lib/utils';
import { CHAMPS_SORTIE, ETATS_CHAMBRE, sortieSchema, type Sortie, type SortieSaisie } from '@/schemas/departs';
import { aujourdHui, ELEMENTS_PAR_DEFAUT, ETATS, MAX_PHOTOS_PIECE, PIECES_PAR_DEFAUT } from '@/schemas/etat-des-lieux';

export type RegleRetenue = { label: string; amount: number };

const ETAPES = ['Informations', 'Pièces', 'Observations et signature'];

const ICONES_CHAMBRE = { disponible: DoorOpen, maintenance: Wrench, hors_service: Ban } as const;

type ElementSaisi = SortieSaisie['pieces'][number]['elements'][number];

const elementVide = (nom = ''): ElementSaisi => ({ nom, etat: null, observation: '', photo: null });

/** Pièces de départ : celles de l'état d'entrée (états à constater de nouveau), sinon les pièces par défaut. */
function valeursInitiales(entree: Constat | null): SortieSaisie {
    return {
        date_etat: aujourdHui(),
        nbre_cle_recus: entree?.cles != null ? String(entree.cles) : '',
        etat_chambre_sortie: null,
        pieces: entree?.pieces.length
            ? entree.pieces.map((p) => ({
                  nom: p.nom,
                  photos: [],
                  elements: p.elements.length ? p.elements.map((e) => elementVide(e.nom)) : ELEMENTS_PAR_DEFAUT.map((n) => elementVide(n)),
              }))
            : PIECES_PAR_DEFAUT.map((nom) => ({ nom, photos: [], elements: ELEMENTS_PAR_DEFAUT.map((n) => elementVide(n)) })),
        observation_generale: '',
        signature_crou: null,
    };
}

/**
 * Retenue indicative de la grille pour un élément en mauvais état (même correspondance de libellés que `DepartureClosureRules`).
 * Le montant définitif est calculé par le serveur à l'enregistrement.
 */
function retenueIndicative(grille: RegleRetenue[], nom: string) {
    const n = sansAccents(nom.trim());
    if (!n) return undefined;
    return grille.find((r) => r.amount > 0 && (n.includes(sansAccents(r.label)) || sansAccents(r.label).includes(n)));
}

const nbJours = (debut: string, fin: string) => {
    const [a1, m1, j1] = debut.split('-').map(Number);
    const [a2, m2, j2] = fin.split('-').map(Number);
    return Math.max(0, Math.round((new Date(a2, m2 - 1, j2).getTime() - new Date(a1, m1 - 1, j1).getTime()) / 86_400_000));
};

type Props = {
    resident: ResidentEdl | null;
    url: string;
    grille: RegleRetenue[];
    preavisJours: number;
    onClose: () => void;
};

/** État des lieux de sortie en 3 étapes (`storeEdl`) : clôture le séjour, calcule le décompte et libère la chambre. */
export function SortieDialog({ resident, url, grille, preavisJours, onClose }: Props) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const [preparations, setPreparations] = useState(0);
    const [erreur, setErreur] = useState<string | null>(null);
    const corps = useRef<HTMLDivElement>(null);
    const entree = resident?.entree ?? null;
    const {
        register,
        control,
        handleSubmit,
        trigger,
        reset,
        setValue,
        setError,
        watch,
        formState: { errors, isSubmitting },
    } = useForm<SortieSaisie, unknown, Sortie>({ resolver: zodResolver(sortieSchema), defaultValues: valeursInitiales(entree), mode: 'onTouched' });
    const pieces = useFieldArray({ control, name: 'pieces' });

    useEffect(() => {
        if (!resident) return;
        reset(valeursInitiales(resident.entree));
        setEtape(0);
        setAtteinte(0);
        setErreur(null);
    }, [resident, reset]);

    const urlDe = (valeur: string) => (valeur.startsWith('data:') ? valeur : `/storage/${valeur}`);
    const preparation = (enCours: boolean) => setPreparations((n) => Math.max(0, n + (enCours ? 1 : -1)));
    const haut = () => corps.current?.scrollTo({ top: 0, behavior: 'smooth' });

    const allerA = async (cible: number) => {
        if (cible <= etape) {
            setEtape(cible);
            haut();
            return;
        }
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_SORTIE[index], { shouldFocus: true }))) {
                setEtape(index);
                if (index === 1) toast.error('Complétez l’état de chaque élément avant de continuer.');
                return;
            }
        }
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
        haut();
    };

    const enregistrer = handleSubmit(
        async (valeurs) => {
            if (!resident) return;
            if (preparations > 0) {
                toast.error('Veuillez attendre la préparation des photos.');
                return;
            }
            setErreur(null);
            await envoyer<SortieSaisie>(
                url,
                {
                    type_etat: 'sortie',
                    id_cite: resident.cite_id ?? '',
                    id_batiment: resident.batiment_id ?? '',
                    id_chambre: resident.chambre_id ?? '',
                    id_etudiant: resident.id,
                    date_etat: valeurs.date_etat,
                    nbre_cle_recus: Number(valeurs.nbre_cle_recus),
                    etat_chambre_sortie: valeurs.etat_chambre_sortie,
                    observation_generale: valeurs.observation_generale.trim(),
                    signature_crou: valeurs.signature_crou ?? '',
                    pieces_json: JSON.stringify(
                        valeurs.pieces.map((piece, i) => ({
                            name_piece: piece.nom.trim(),
                            ordre: i + 1,
                            photos: piece.photos,
                            elements: piece.elements.map((e, j) => ({ name_element: e.nom.trim(), etat: e.etat, observation: e.observation.trim(), photo: e.photo, ordre: j + 1 })),
                        })),
                    ),
                },
                {
                    onSuccess: onClose,
                    onError: (erreurs) => {
                        // Erreur de champ : sous le champ et retour à son étape ; règle métier (séjour, impayés…) : encadré.
                        let fautive = -1;
                        const autres: string[] = [];
                        Object.entries(erreurs).forEach(([cle, message]) => {
                            const champ = (cle.startsWith('pieces') ? 'pieces' : cle) as keyof Sortie;
                            const index = CHAMPS_SORTIE.findIndex((champs) => champs.includes(champ));
                            if (index < 0) {
                                autres.push(message);
                                return;
                            }
                            if (champ === 'pieces') autres.push(message);
                            else setError(champ, { type: 'server', message });
                            fautive = fautive < 0 ? index : Math.min(fautive, index);
                        });
                        if (fautive >= 0) setEtape(fautive);
                        setErreur(autres.length ? autres.join(' ') : null);
                        haut();
                    },
                },
            );
        },
        (fautes) => {
            const index = CHAMPS_SORTIE.findIndex((champs) => champs.some((champ) => fautes[champ]));
            if (index >= 0) setEtape(index);
        },
    );

    const v = watch();
    const elements = v.pieces.flatMap((p) => p.elements);
    const compte = (etat: string) => elements.filter((e) => e.etat === etat).length;
    const retenues = v.pieces.flatMap((p) => p.elements.filter((e) => e.etat === 'mauvais').map((e) => ({ element: e.nom, piece: p.nom, regle: retenueIndicative(grille, e.nom) }))).filter((r) => r.regle);
    const preavis = resident?.demande?.date_demande && v.date_etat ? nbJours(resident.demande.date_demande, v.date_etat) : null;

    return (
        <Dialog open={!!resident} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-5xl">
                {resident && (
                    <form noValidate onSubmit={(e) => (etape < ETAPES.length - 1 ? (e.preventDefault(), void allerA(etape + 1)) : void enregistrer(e))} className="flex min-h-0 flex-1 flex-col">
                        <DialogHeader>
                            <DialogTitle>État des lieux de sortie · {resident.name}</DialogTitle>
                            <DialogDescription>
                                {logementDe(resident)}
                                {resident.demande?.date_sortie ? ` · sortie prévue le ${dateFr(resident.demande.date_sortie)}` : ''}
                            </DialogDescription>
                        </DialogHeader>
                        <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={(i) => void allerA(i)} className="overflow-x-auto" />
                        <DialogBody ref={corps} className="space-y-4">
                            <ErreurGenerale message={erreur} />
                            <p className="text-xs text-muted">
                                Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                            </p>

                            {etape === 0 && (
                                <>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Field id="sortie-date" label="Date de l’état des lieux" required error={errors.date_etat?.message}>
                                            <Controller
                                                control={control}
                                                name="date_etat"
                                                render={({ field }) => <DatePicker id="sortie-date" value={field.value} onChange={field.onChange} max={aujourdHui()} invalid={!!errors.date_etat} />}
                                            />
                                        </Field>
                                        <Field
                                            id="sortie-cles"
                                            label="Nombre de clés rendues"
                                            required
                                            error={errors.nbre_cle_recus?.message}
                                            hint={entree?.cles != null ? `${entree.cles} clé(s) remise(s) à l’entrée.` : undefined}
                                        >
                                            <Input id="sortie-cles" inputMode="numeric" maxLength={2} placeholder="Ex. : 2" icon={<KeyRound {...ICON} />} invalid={!!errors.nbre_cle_recus} {...register('nbre_cle_recus')} />
                                        </Field>
                                    </div>
                                    <fieldset className="space-y-2">
                                        <legend className="mb-1.5 text-sm font-medium text-gray-800">
                                            État de la chambre après la sortie<span className="ml-0.5 text-red-600">*</span>
                                        </legend>
                                        <Controller
                                            control={control}
                                            name="etat_chambre_sortie"
                                            render={({ field }) => (
                                                <div role="radiogroup" aria-invalid={!!errors.etat_chambre_sortie || undefined} className="grid gap-2 sm:grid-cols-3">
                                                    {ETATS_CHAMBRE.map((option) => {
                                                        const Icone = ICONES_CHAMBRE[option.value];
                                                        return (
                                                            <ChoixCarte
                                                                key={option.value}
                                                                actif={field.value === option.value}
                                                                onClick={() => field.onChange(option.value)}
                                                                titre={option.label}
                                                                description={option.description}
                                                                icone={<Icone {...ICON} />}
                                                            />
                                                        );
                                                    })}
                                                </div>
                                            )}
                                        />
                                        {errors.etat_chambre_sortie ? (
                                            <p role="alert" className="text-xs font-medium text-red-600">
                                                {errors.etat_chambre_sortie.message}
                                            </p>
                                        ) : (
                                            <p className="text-xs text-muted">Ce statut est appliqué automatiquement à la chambre lors de la clôture du séjour.</p>
                                        )}
                                    </fieldset>
                                    {!entree && (
                                        <Encadre ton="warning" icone={<TriangleAlert {...ICON} />}>
                                            Aucun état des lieux d’entrée n’est enregistré pour cette chambre : la comparaison élément par élément ne sera pas possible.
                                        </Encadre>
                                    )}
                                </>
                            )}

                            {etape === 1 && (
                                <>
                                    <Encadre icone={<Info {...ICON} />}>
                                        Constatez l’état de chaque élément. L’état relevé à l’entrée est rappelé sous chaque ligne ; une dégradation est signalée en rouge. Un commentaire est
                                        obligatoire pour un élément en mauvais état.
                                        {grille.length > 0 && <> Un élément en mauvais état dont le nom correspond à la grille des retenues entraîne une retenue sur la caution.</>}
                                    </Encadre>
                                    {pieces.fields.map((piece, index) => (
                                        <PieceSortie
                                            key={piece.id}
                                            index={index}
                                            control={control}
                                            register={register}
                                            setValue={setValue}
                                            errors={errors}
                                            reference={entree}
                                            grille={grille}
                                            retirer={pieces.fields.length > 1 ? () => pieces.remove(index) : undefined}
                                            urlDe={urlDe}
                                            onPreparation={preparation}
                                        />
                                    ))}
                                    {errors.pieces?.root?.message && (
                                        <p role="alert" className="text-xs font-medium text-red-600">
                                            {errors.pieces.root.message}
                                        </p>
                                    )}
                                    <Button variant="secondary" size="sm" className="h-10" onClick={() => pieces.append({ nom: '', photos: [], elements: ELEMENTS_PAR_DEFAUT.map((n) => elementVide(n)) })}>
                                        <Plus {...ICON} aria-hidden /> Ajouter une pièce
                                    </Button>
                                </>
                            )}

                            {etape === 2 && (
                                <>
                                    <Field id="sortie-obs" label="Observations générales" error={errors.observation_generale?.message} hint="Facultatif : réserves, objets laissés, remarques.">
                                        <Textarea id="sortie-obs" rows={3} maxLength={5000} placeholder="Ex. : Chambre rendue propre, une ampoule manquante." invalid={!!errors.observation_generale} {...register('observation_generale')} />
                                    </Field>
                                    <dl className="grid grid-cols-2 gap-2 rounded-brand bg-surface p-3 text-sm sm:grid-cols-4">
                                        {(
                                            [
                                                ['Pièces', v.pieces.length],
                                                ['Bon état', compte('bon')],
                                                ['État moyen', compte('moyen')],
                                                ['Mauvais état', compte('mauvais')],
                                            ] as const
                                        ).map(([libelle, valeur]) => (
                                            <div key={libelle}>
                                                <dt className="text-xs text-muted">{libelle}</dt>
                                                <dd className="font-medium text-gray-900 tabular-nums">{valeur}</dd>
                                            </div>
                                        ))}
                                    </dl>
                                    <Lignes
                                        lignes={[
                                            ['Date de l’état des lieux', dateFr(v.date_etat)],
                                            ['Clés rendues', v.nbre_cle_recus || '—'],
                                            ['Chambre après la sortie', ETATS_CHAMBRE.find((o) => o.value === v.etat_chambre_sortie)?.label ?? '—'],
                                            [
                                                'Préavis',
                                                preavis === null ? (
                                                    '—'
                                                ) : (
                                                    <span className={cn(preavis < preavisJours && 'font-medium text-amber-800')}>
                                                        {preavis} jour(s) sur {preavisJours} requis{preavis < preavisJours ? ' : pénalité de préavis appliquée' : ''}
                                                    </span>
                                                ),
                                            ],
                                            [
                                                'Retenues pour dégradations (indicatif)',
                                                retenues.length === 0 ? (
                                                    'Aucune'
                                                ) : (
                                                    <ul className="space-y-0.5">
                                                        {retenues.map((r, i) => (
                                                            <li key={i}>
                                                                {r.element} : {fcfa(r.regle?.amount)}
                                                            </li>
                                                        ))}
                                                    </ul>
                                                ),
                                            ],
                                        ]}
                                    />
                                    <Field id="sortie-signature" label="Signature du CROU" required error={errors.signature_crou?.message} hint="L’étudiant signera ensuite cet état des lieux depuis son espace.">
                                        <Controller
                                            control={control}
                                            name="signature_crou"
                                            render={({ field }) => <SignaturePad id="sortie-signature" value={field.value} onChange={field.onChange} invalid={!!errors.signature_crou} />}
                                        />
                                    </Field>
                                    <Encadre ton="warning" icone={<Lock {...ICON} />}>
                                        L’enregistrement <strong className="font-medium">clôture le séjour</strong> : la chambre prend le statut choisi, le dossier est clôturé et le décompte
                                        (préavis, dégradations, caution) est calculé. Cette action est définitive.
                                    </Encadre>
                                </>
                            )}
                        </DialogBody>
                        <DialogFooter className="sm:justify-between">
                            <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={isSubmitting}>
                                Annuler
                            </Button>
                            <div className="flex flex-col-reverse gap-2 sm:flex-row">
                                {etape > 0 && (
                                    <Button variant="outline" size="sm" className="h-10" onClick={() => void allerA(etape - 1)} disabled={isSubmitting}>
                                        <ArrowLeft className="size-4" aria-hidden /> Précédent
                                    </Button>
                                )}
                                {etape < ETAPES.length - 1 ? (
                                    <Button type="submit" size="sm" className="h-10">
                                        Suivant <ArrowRight className="size-4" aria-hidden />
                                    </Button>
                                ) : (
                                    <Button type="submit" size="sm" className="h-10" disabled={isSubmitting || preparations > 0}>
                                        {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Lock className="size-4" aria-hidden />}
                                        Clôturer le séjour
                                    </Button>
                                )}
                            </div>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}

type PieceProps = {
    index: number;
    control: Control<SortieSaisie, unknown, Sortie>;
    register: UseFormRegister<SortieSaisie>;
    setValue: UseFormSetValue<SortieSaisie>;
    errors: FieldErrors<SortieSaisie>;
    reference: Constat | null;
    grille: RegleRetenue[];
    retirer?: () => void;
    urlDe: (valeur: string) => string;
    onPreparation: (enCours: boolean) => void;
};

/** Pièce de l'état de sortie : éléments, état comparé à l'entrée, commentaire et photos. */
function PieceSortie({ index, control, register, setValue, errors, reference, grille, retirer, urlDe, onPreparation }: PieceProps) {
    const elements = useFieldArray({ control, name: `pieces.${index}.elements` });
    const valeurs = useWatch({ control, name: `pieces.${index}.elements` });
    const nomPiece = useWatch({ control, name: `pieces.${index}.nom` });
    const erreurs = errors.pieces?.[index];
    const id = `sortie-piece-${index}`;
    const restants = valeurs.filter((e) => !e.etat).length;
    const refs = useMemo(() => valeurs.map((e, j) => correspondant(reference, index, nomPiece, j, e.nom)), [valeurs, reference, index, nomPiece]);

    const completer = (source: 'bon' | 'entree') =>
        valeurs.forEach((element, j) => {
            if (element.etat) return;
            const etat = source === 'bon' ? 'bon' : refs[j]?.etat;
            if (etat) setValue(`pieces.${index}.elements.${j}.etat`, etat, { shouldValidate: true });
        });

    return (
        <fieldset className="rounded-brand border bg-white">
            <legend className="sr-only">Pièce {index + 1}</legend>
            <div className="flex flex-wrap items-start gap-2 border-b bg-surface p-3">
                <div className="min-w-48 flex-1">
                    <label htmlFor={`${id}-nom`} className="sr-only">
                        Nom de la pièce {index + 1}
                    </label>
                    <Input id={`${id}-nom`} placeholder="Nom de la pièce (ex. : Chambre)" invalid={!!erreurs?.nom} className="h-10 font-medium" {...register(`pieces.${index}.nom`)} />
                    {erreurs?.nom && (
                        <p role="alert" className="mt-1 text-xs font-medium text-red-600">
                            {erreurs.nom.message}
                        </p>
                    )}
                </div>
                {restants > 0 && reference && (
                    <Button variant="ghost" size="sm" onClick={() => completer('entree')} className="h-10 text-primary">
                        <ClipboardCopy {...ICON} size={16} aria-hidden /> Comme à l’entrée
                    </Button>
                )}
                {restants > 0 && (
                    <Button variant="ghost" size="sm" onClick={() => completer('bon')} className="h-10 text-primary">
                        <CheckCheck {...ICON} size={16} aria-hidden /> Le reste en bon état
                    </Button>
                )}
                {retirer && (
                    <Button variant="ghost" size="icon" onClick={retirer} aria-label={`Retirer la pièce ${index + 1}`} className="size-10 text-red-700 hover:bg-red-50 hover:text-red-700">
                        <Trash2 {...ICON} aria-hidden />
                    </Button>
                )}
            </div>

            <ul className="divide-y">
                {elements.fields.map((element, j) => {
                    const erreur = erreurs?.elements?.[j];
                    const etat = valeurs[j]?.etat;
                    const ref = refs[j];
                    const degrade = !!(ref?.etat && etat && RANG[etat] > RANG[ref.etat]);
                    const retenue = etat === 'mauvais' ? retenueIndicative(grille, valeurs[j]?.nom ?? '') : undefined;
                    const ide = `${id}-el-${j}`;
                    return (
                        <li key={element.id} className="grid gap-2.5 p-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-start">
                            <div className="min-w-0">
                                <div className="flex items-start gap-2">
                                    <div className="min-w-0 flex-1">
                                        <label htmlFor={`${ide}-nom`} className="sr-only">
                                            Élément
                                        </label>
                                        <Input id={`${ide}-nom`} placeholder="Élément" invalid={!!erreur?.nom} className="h-10" {...register(`pieces.${index}.elements.${j}.nom`)} />
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => elements.remove(j)}
                                        disabled={elements.fields.length <= 1}
                                        aria-label="Retirer cet élément"
                                        className="size-10 shrink-0 hover:bg-red-50 hover:text-red-700"
                                    >
                                        <Trash2 {...ICON} size={16} aria-hidden />
                                    </Button>
                                </div>
                                {erreur?.nom && (
                                    <p role="alert" className="mt-1 text-xs font-medium text-red-600">
                                        {erreur.nom.message}
                                    </p>
                                )}
                                <p className={cn('mt-1 text-xs', degrade ? 'font-medium text-red-700' : 'text-muted')}>
                                    {reference ? `À l’entrée : ${ETATS.find((e) => e.code === ref?.etat)?.libelle ?? 'non renseigné'}` : 'Pas d’état d’entrée de référence'}
                                    {degrade && ' · dégradation constatée'}
                                </p>
                                {retenue && (
                                    <p className="mt-0.5 inline-flex items-center gap-1 text-xs font-medium text-amber-800">
                                        <Coins {...ICON} size={14} aria-hidden /> Retenue indicative : {retenue.label} ({fcfa(retenue.amount)})
                                    </p>
                                )}
                            </div>

                            <div role="radiogroup" aria-label={`État de ${valeurs[j]?.nom || 'l’élément'}`} aria-invalid={!!erreur?.etat || undefined} className="flex flex-col gap-1 md:row-span-2">
                                <div className="grid grid-cols-3 gap-1.5">
                                    {ETATS.map((option) => (
                                        <label
                                            key={option.code}
                                            className={cn(
                                                'flex h-10 cursor-pointer items-center justify-center rounded-brand border px-3 text-sm transition-colors has-focus-visible:ring-2 has-focus-visible:ring-secondary md:min-w-22',
                                                etat === option.code
                                                    ? option.code === 'bon'
                                                        ? 'border-primary bg-primary text-white'
                                                        : option.code === 'moyen'
                                                          ? 'border-accent bg-accent text-gray-900'
                                                          : 'border-red-700 bg-red-700 text-white'
                                                    : cn('bg-white text-muted hover:bg-surface', erreur?.etat ? 'border-red-400' : 'border-gray-300'),
                                            )}
                                        >
                                            <input type="radio" value={option.code} className="sr-only" {...register(`pieces.${index}.elements.${j}.etat`)} />
                                            {option.libelle}
                                        </label>
                                    ))}
                                </div>
                                {erreur?.etat && (
                                    <p role="alert" className="text-xs font-medium text-red-600">
                                        {erreur.etat.message}
                                    </p>
                                )}
                            </div>

                            <div className="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-start">
                                <div className="min-w-0 flex-1">
                                    <label htmlFor={`${ide}-obs`} className="sr-only">
                                        Observation
                                    </label>
                                    <Input
                                        id={`${ide}-obs`}
                                        placeholder={etat === 'mauvais' ? 'Décrivez le défaut constaté *' : 'Observation (facultatif)'}
                                        invalid={!!erreur?.observation}
                                        className="h-10"
                                        maxLength={2000}
                                        {...register(`pieces.${index}.elements.${j}.observation`)}
                                    />
                                    {erreur?.observation && (
                                        <p role="alert" className="mt-1 text-xs font-medium text-red-600">
                                            {erreur.observation.message}
                                        </p>
                                    )}
                                </div>
                                <Controller
                                    control={control}
                                    name={`pieces.${index}.elements.${j}.photo`}
                                    render={({ field }) => (
                                        <PhotosEdl
                                            compact
                                            max={1}
                                            libelle={`Photo de l’élément ${valeurs[j]?.nom || j + 1}`}
                                            valeurs={field.value ? [field.value] : []}
                                            onChange={(liste) => field.onChange(liste[0] ?? null)}
                                            urlDe={urlDe}
                                            onPreparation={onPreparation}
                                        />
                                    )}
                                />
                            </div>
                        </li>
                    );
                })}
            </ul>

            <div className="flex flex-col gap-3 border-t p-3">
                {erreurs?.elements?.root?.message && (
                    <p role="alert" className="text-xs font-medium text-red-600">
                        {erreurs.elements.root.message}
                    </p>
                )}
                <Button variant="ghost" size="sm" onClick={() => elements.append(elementVide())} className="w-fit text-primary">
                    <Plus {...ICON} size={16} aria-hidden /> Ajouter un élément
                </Button>
                <div>
                    <p className="mb-2 text-xs text-muted">Photos générales de la pièce (facultatives, {MAX_PHOTOS_PIECE} maximum)</p>
                    <Controller
                        control={control}
                        name={`pieces.${index}.photos`}
                        render={({ field }) => (
                            <PhotosEdl max={MAX_PHOTOS_PIECE} libelle="Photo de la pièce" valeurs={field.value} onChange={field.onChange} urlDe={urlDe} onPreparation={onPreparation} />
                        )}
                    />
                    {erreurs?.photos?.message && (
                        <p role="alert" className="mt-1 text-xs font-medium text-red-600">
                            {erreurs.photos.message}
                        </p>
                    )}
                </div>
            </div>
        </fieldset>
    );
}
