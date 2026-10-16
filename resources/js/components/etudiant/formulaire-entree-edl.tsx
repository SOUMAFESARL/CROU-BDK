import { zodResolver } from "@hookform/resolvers/zod";
import { CheckCheck, ChevronLeft, ChevronRight, Info, KeyRound, Loader2, Plus, Save, Trash2 } from "lucide-react";
import { useMemo, useRef, useState } from "react";
import { Controller, useFieldArray, useForm, useWatch, type Control, type FieldErrors, type UseFormRegister, type UseFormSetValue } from "react-hook-form";
import { toast } from "sonner";
import { Notice, Panneau } from "@/components/etudiant/panneau";
import { PhotosEdl } from "@/components/etudiant/photos-edl";
import { SignaturePad } from "@/components/etudiant/signature-pad";
import { ICON } from "@/components/layout/app-sidebar";
import { Button } from "@/components/ui/button";
import { DatePicker } from "@/components/ui/date-picker";
import { Field } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Stepper } from "@/components/ui/stepper";
import { Textarea } from "@/components/ui/textarea";
import { envoyer } from "@/lib/inertia-form";
import { cn } from "@/lib/utils";
import {
    aujourdHui,
    CHAMPS_ETAPE,
    ELEMENTS_PAR_DEFAUT,
    ETATS,
    entreeSchema,
    MAX_PHOTOS_PIECE,
    PIECES_PAR_DEFAUT,
    type Entree,
    type EntreeSaisie,
} from "@/schemas/etat-des-lieux";
import type { Constat, Lieu } from "@/pages/Etudiant/EtatDesLieux";

const ETAPES = ["Informations", "Pièces", "Observations et signature"];

type ElementSaisi = EntreeSaisie["pieces"][number]["elements"][number];

const elementVide = (nom = ""): ElementSaisi => ({ nom, etat: null, observation: "", photo: null });

const pieceVide = (nom: string): EntreeSaisie["pieces"][number] => ({ nom, photos: [], elements: ELEMENTS_PAR_DEFAUT.map((n) => elementVide(n)) });

function valeursInitiales(entree: Constat | null): EntreeSaisie {
    return {
        date_etat: entree?.date ?? aujourdHui(),
        nbre_cle_recus: entree?.cles != null ? String(entree.cles) : "",
        pieces: entree?.pieces.length
            ? entree.pieces.map((piece) => ({
                  nom: piece.nom,
                  photos: piece.photos.map((p) => p.chemin),
                  elements: piece.elements.length
                      ? piece.elements.map((e) => ({ nom: e.nom, etat: e.etat, observation: e.observation ?? "", photo: e.photo?.chemin ?? null }))
                      : ELEMENTS_PAR_DEFAUT.map((n) => elementVide(n)),
              }))
            : PIECES_PAR_DEFAUT.map(pieceVide),
        observation_generale: entree?.observation ?? "",
        signature_etudiant: null,
    };
}

type Props = {
    entree: Constat | null;
    lieu: Lieu;
    etudiantId: string;
    urlCreation: string;
    annuler?: () => void;
    onEnregistre: () => void;
};

/** Assistant en 3 étapes de l'état des lieux d'entrée (création ou modification tant que le CROU n'a pas signé). */
export function FormulaireEntreeEdl({ entree, lieu, etudiantId, urlCreation, annuler, onEnregistre }: Props) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(entree ? ETAPES.length - 1 : 0);
    const [preparations, setPreparations] = useState(0);
    const formulaire = useRef<HTMLFormElement>(null);
    const signatureExistante = !!entree?.signatureEtudiant;
    const {
        register,
        control,
        handleSubmit,
        trigger,
        setValue,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<EntreeSaisie, unknown, Entree>({
        resolver: zodResolver(entreeSchema(signatureExistante)),
        defaultValues: valeursInitiales(entree),
        mode: "onTouched",
    });
    const pieces = useFieldArray({ control, name: "pieces" });

    // Chemins déjà enregistrés → URL d'affichage ; une image `data:` s'affiche telle quelle.
    const urls = useMemo(() => {
        const carte = new Map<string, string>();
        entree?.pieces.forEach((piece) => {
            piece.photos.forEach((p) => p.url && carte.set(p.chemin, p.url));
            piece.elements.forEach((e) => e.photo?.url && carte.set(e.photo.chemin, e.photo.url));
        });
        return carte;
    }, [entree]);
    const urlDe = (valeur: string) => (valeur.startsWith("data:") ? valeur : (urls.get(valeur) ?? `/storage/${valeur}`));
    const preparation = (enCours: boolean) => setPreparations((n) => Math.max(0, n + (enCours ? 1 : -1)));

    const montrerPremiereErreur = () =>
        requestAnimationFrame(() => {
            const champ = formulaire.current?.querySelector<HTMLElement>('[aria-invalid="true"], [role="alert"]');
            champ?.scrollIntoView({ behavior: "smooth", block: "center" });
            if (champ instanceof HTMLInputElement || champ instanceof HTMLTextAreaElement) champ.focus({ preventScroll: true });
        });

    const allerA = async (cible: number) => {
        // Avancer valide les étapes intermédiaires ; reculer est toujours permis.
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPE[index]))) {
                setEtape(index);
                montrerPremiereErreur();
                return;
            }
        }
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
        formulaire.current?.scrollIntoView({ behavior: "smooth", block: "start" });
    };

    const enregistrer = handleSubmit(
        async (valeurs) => {
            if (preparations > 0) {
                toast.error("Veuillez attendre la préparation des photos.");
                return;
            }
            const cible = entree ? entree.lieu : lieu;
            const ok = await envoyer<EntreeSaisie>(
                entree ? entree.modifier : urlCreation,
                {
                    type_etat: "entree",
                    id_cite: cible.idCite,
                    id_batiment: cible.idBatiment,
                    id_chambre: cible.idChambre,
                    id_etudiant: etudiantId,
                    date_etat: valeurs.date_etat,
                    nbre_cle_recus: Number(valeurs.nbre_cle_recus),
                    observation_generale: valeurs.observation_generale.trim(),
                    signature_etudiant: valeurs.signature_etudiant ?? "",
                    pieces_json: JSON.stringify(
                        valeurs.pieces.map((piece, i) => ({
                            name_piece: piece.nom.trim(),
                            ordre: i + 1,
                            photos: piece.photos,
                            elements: piece.elements.map((e, j) => ({
                                name_element: e.nom.trim(),
                                etat: e.etat,
                                observation: e.observation.trim(),
                                photo: e.photo,
                                ordre: j + 1,
                            })),
                        })),
                    ),
                },
                {
                    method: entree ? "put" : "post",
                    onError: (erreurs) => {
                        // Erreurs serveur : sous le champ quand il existe, sinon en message ; retour à l'étape concernée.
                        let fautive = -1;
                        Object.entries(erreurs).forEach(([cle, message]) => {
                            const champ = (cle === "pieces_json" ? "pieces" : cle) as keyof Entree;
                            const index = CHAMPS_ETAPE.findIndex((champs) => champs.includes(champ));
                            if (champ === "date_etat" || champ === "nbre_cle_recus" || champ === "observation_generale" || champ === "signature_etudiant") {
                                setError(champ, { type: "server", message });
                            } else {
                                toast.error(message);
                            }
                            if (index >= 0 && (fautive < 0 || index < fautive)) fautive = index;
                        });
                        if (fautive >= 0) setEtape(fautive);
                    },
                },
            );
            if (ok) onEnregistre();
        },
        (erreurs) => {
            // Erreur restée sur une étape précédente (modification directe à la dernière étape).
            const index = CHAMPS_ETAPE.findIndex((champs) => champs.some((champ) => erreurs[champ]));
            if (index >= 0) setEtape(index);
            montrerPremiereErreur();
        },
    );

    return (
        <Panneau
            surtitre={entree ? "Modification du constat" : "Nouveau constat"}
            titre="État des lieux d’entrée"
            badge={`Étape ${etape + 1} sur ${ETAPES.length}`}
        >
            <form ref={formulaire} onSubmit={enregistrer} noValidate className="flex scroll-mt-20 flex-col gap-4">
                <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} className="-mx-4 px-4 md:-mx-5 md:px-5" />
                <p className="text-xs text-muted">
                    Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                </p>

                {etape === 0 && (
                    <div className="flex flex-col gap-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field id="edl-date" label="Date de l’état des lieux" required error={errors.date_etat?.message}>
                                <Controller
                                    control={control}
                                    name="date_etat"
                                    render={({ field }) => (
                                        <DatePicker id="edl-date" value={field.value} onChange={field.onChange} max={aujourdHui()} invalid={!!errors.date_etat} />
                                    )}
                                />
                            </Field>
                            <Field
                                id="edl-cles"
                                label="Nombre de clés remises"
                                required
                                error={errors.nbre_cle_recus?.message}
                                hint="Clés de la chambre reçues à votre arrivée."
                            >
                                <Input
                                    id="edl-cles"
                                    inputMode="numeric"
                                    maxLength={2}
                                    placeholder="Ex. : 2"
                                    icon={<KeyRound {...ICON} />}
                                    invalid={!!errors.nbre_cle_recus}
                                    {...register("nbre_cle_recus")}
                                />
                            </Field>
                        </div>
                        <Notice icone={Info}>
                            Faites le tour de votre logement dès votre arrivée et notez honnêtement l’état de chaque élément : ce constat sera
                            comparé à l’état des lieux de sortie. Vous pourrez le modifier tant que le CROU ne l’a pas signé.
                        </Notice>
                    </div>
                )}

                {etape === 1 && (
                    <div className="flex flex-col gap-4">
                        {pieces.fields.map((piece, index) => (
                            <CartePiece
                                key={piece.id}
                                index={index}
                                control={control}
                                register={register}
                                setValue={setValue}
                                errors={errors}
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
                        <Button variant="secondary" onClick={() => pieces.append(pieceVide(""))} className="font-medium">
                            <Plus {...ICON} />
                            Ajouter une pièce
                        </Button>
                    </div>
                )}

                {etape === 2 && (
                    <div className="flex flex-col gap-4">
                        <Field
                            id="edl-observation"
                            label="Observations générales"
                            error={errors.observation_generale?.message}
                            hint="Facultatif : réserves, accessoires fournis, remarques complémentaires."
                        >
                            <Textarea
                                id="edl-observation"
                                rows={4}
                                maxLength={5000}
                                placeholder="Ex. : Rideau de la fenêtre manquant, une étagère de l’armoire est fendue."
                                invalid={!!errors.observation_generale}
                                {...register("observation_generale")}
                            />
                        </Field>
                        <Recapitulatif control={control} />
                        <Field
                            id="edl-signature"
                            label="Votre signature"
                            required={!signatureExistante}
                            error={errors.signature_etudiant?.message}
                        >
                            {signatureExistante && entree?.signatureEtudiant && (
                                <div className="mb-2 flex items-center gap-3 rounded-brand bg-surface p-2.5 text-xs text-muted">
                                    <img src={entree.signatureEtudiant} alt="Signature enregistrée" className="h-10 rounded-sm bg-white px-2" />
                                    Signature enregistrée. Signez à nouveau seulement pour la remplacer.
                                </div>
                            )}
                            <Controller
                                control={control}
                                name="signature_etudiant"
                                render={({ field }) => (
                                    <SignaturePad id="edl-signature" value={field.value} onChange={field.onChange} invalid={!!errors.signature_etudiant} />
                                )}
                            />
                        </Field>
                    </div>
                )}

                <div className="flex flex-wrap items-center justify-between gap-2 border-t pt-4">
                    <Button
                        variant="outline"
                        onClick={etape === 0 ? annuler : () => setEtape(etape - 1)}
                        disabled={isSubmitting || (etape === 0 && !annuler)}
                        className={cn(etape === 0 && !annuler && "invisible")}
                    >
                        {etape === 0 ? (
                            "Annuler"
                        ) : (
                            <>
                                <ChevronLeft {...ICON} />
                                Précédent
                            </>
                        )}
                    </Button>
                    {etape < ETAPES.length - 1 ? (
                        <Button onClick={() => allerA(etape + 1)} className="font-medium">
                            Suivant
                            <ChevronRight {...ICON} />
                        </Button>
                    ) : (
                        <Button type="submit" disabled={isSubmitting || preparations > 0} className="font-medium">
                            {isSubmitting ? <Loader2 {...ICON} className="animate-spin" /> : <Save {...ICON} />}
                            {entree ? "Enregistrer les modifications" : "Enregistrer l’état des lieux"}
                        </Button>
                    )}
                </div>
            </form>
        </Panneau>
    );
}

type CartePieceProps = {
    index: number;
    control: Control<EntreeSaisie, unknown, Entree>;
    register: UseFormRegister<EntreeSaisie>;
    setValue: UseFormSetValue<EntreeSaisie>;
    errors: FieldErrors<EntreeSaisie>;
    retirer?: () => void;
    urlDe: (valeur: string) => string;
    onPreparation: (enCours: boolean) => void;
};

function CartePiece({ index, control, register, setValue, errors, retirer, urlDe, onPreparation }: CartePieceProps) {
    const elements = useFieldArray({ control, name: `pieces.${index}.elements` });
    const valeurs = useWatch({ control, name: `pieces.${index}.elements` });
    const erreurs = errors.pieces?.[index];
    const id = `edl-piece-${index}`;

    const toutEnBon = () =>
        valeurs.forEach((element, j) => {
            if (!element.etat) setValue(`pieces.${index}.elements.${j}.etat`, "bon", { shouldValidate: true });
        });
    const restants = valeurs.filter((e) => !e.etat).length;

    return (
        <fieldset className="rounded-brand border bg-white">
            <legend className="sr-only">Pièce {index + 1}</legend>
            <div className="flex flex-wrap items-start gap-2 border-b bg-surface p-3">
                <div className="min-w-0 flex-1">
                    <label htmlFor={`${id}-nom`} className="sr-only">
                        Nom de la pièce {index + 1}
                    </label>
                    <Input
                        id={`${id}-nom`}
                        placeholder="Nom de la pièce (ex. : Chambre)"
                        invalid={!!erreurs?.nom}
                        className="h-10 font-medium"
                        {...register(`pieces.${index}.nom`)}
                    />
                    {erreurs?.nom && (
                        <p role="alert" className="mt-1 text-xs font-medium text-red-600">
                            {erreurs.nom.message}
                        </p>
                    )}
                </div>
                {restants > 0 && (
                    <Button variant="ghost" size="sm" onClick={toutEnBon} className="h-10 text-primary">
                        <CheckCheck {...ICON} size={16} />
                        Le reste en bon état
                    </Button>
                )}
                {retirer && (
                    <Button variant="ghost" size="icon" onClick={retirer} aria-label={`Retirer la pièce ${index + 1}`} className="size-10 text-red-700 hover:bg-red-50 hover:text-red-700">
                        <Trash2 {...ICON} />
                    </Button>
                )}
            </div>

            <ul className="divide-y">
                {elements.fields.map((element, j) => {
                    const erreur = erreurs?.elements?.[j];
                    const etat = valeurs[j]?.etat;
                    const ide = `${id}-el-${j}`;
                    return (
                        <li key={element.id} className="grid gap-2.5 p-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-start">
                            <div className="flex min-w-0 items-start gap-2">
                                <div className="min-w-0 flex-1">
                                    <label htmlFor={`${ide}-nom`} className="sr-only">
                                        Élément
                                    </label>
                                    <Input id={`${ide}-nom`} placeholder="Élément" invalid={!!erreur?.nom} className="h-10" {...register(`pieces.${index}.elements.${j}.nom`)} />
                                    {erreur?.nom && (
                                        <p role="alert" className="mt-1 text-xs font-medium text-red-600">
                                            {erreur.nom.message}
                                        </p>
                                    )}
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => elements.remove(j)}
                                    disabled={elements.fields.length <= 1}
                                    aria-label="Retirer cet élément"
                                    className="size-10 shrink-0 hover:bg-red-50 hover:text-red-700"
                                >
                                    <Trash2 {...ICON} size={16} />
                                </Button>
                            </div>

                            <div role="radiogroup" aria-label="État" aria-invalid={!!erreur?.etat || undefined} className="flex flex-col gap-1 md:row-span-2">
                                <div className="grid grid-cols-3 gap-1.5">
                                    {ETATS.map((option) => (
                                        <label
                                            key={option.code}
                                            className={cn(
                                                "flex h-10 cursor-pointer items-center justify-center rounded-brand border px-3 text-sm transition-colors has-focus-visible:ring-2 has-focus-visible:ring-secondary md:min-w-22",
                                                etat === option.code
                                                    ? option.code === "bon"
                                                        ? "border-primary bg-primary text-white"
                                                        : option.code === "moyen"
                                                          ? "border-accent bg-accent text-gray-900"
                                                          : "border-red-700 bg-red-700 text-white"
                                                    : cn("bg-white text-muted hover:bg-surface", erreur?.etat ? "border-red-400" : "border-gray-300"),
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
                                        placeholder={etat === "mauvais" ? "Décrivez le défaut constaté *" : "Observation (facultatif)"}
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
                    <Plus {...ICON} size={16} />
                    Ajouter un élément
                </Button>
                <div>
                    <p className="mb-2 text-xs text-muted">Photos générales de la pièce (facultatives, {MAX_PHOTOS_PIECE} maximum)</p>
                    <Controller
                        control={control}
                        name={`pieces.${index}.photos`}
                        render={({ field }) => (
                            <PhotosEdl
                                max={MAX_PHOTOS_PIECE}
                                libelle="Photo de la pièce"
                                valeurs={field.value}
                                onChange={field.onChange}
                                urlDe={urlDe}
                                onPreparation={onPreparation}
                            />
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

/** Synthèse avant signature : pièces, éléments et défauts relevés. */
function Recapitulatif({ control }: { control: Control<EntreeSaisie, unknown, Entree> }) {
    const pieces = useWatch({ control, name: "pieces" });
    const elements = pieces.flatMap((p) => p.elements);
    const compte = (etat: string) => elements.filter((e) => e.etat === etat).length;

    return (
        <dl className="grid grid-cols-2 gap-2 rounded-brand bg-surface p-3 text-sm sm:grid-cols-4">
            {[
                ["Pièces", pieces.length],
                ["Bon état", compte("bon")],
                ["État moyen", compte("moyen")],
                ["Mauvais état", compte("mauvais")],
            ].map(([libelle, valeur]) => (
                <div key={libelle}>
                    <dt className="text-xs text-muted">{libelle}</dt>
                    <dd className="font-medium text-gray-900 tabular-nums">{valeur}</dd>
                </div>
            ))}
        </dl>
    );
}
