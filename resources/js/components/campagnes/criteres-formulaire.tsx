import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import {
    ArrowDown,
    ArrowUp,
    CheckCheck,
    GraduationCap,
    ShieldCheck,
    Trash2,
} from "lucide-react";
import { useMemo, useState, type ReactNode } from "react";
import {
    Controller,
    useForm,
    type Path,
    type UseFormRegister,
} from "react-hook-form";
import { AppliquerCriteresDialog } from "@/components/campagnes/appliquer-criteres-dialog";
import {
    BASES_C4,
    CATEGORIES,
    CODES_CATEGORIES,
    DEPARTAGE,
    ETATS_CRITERES,
    GROUPES_C2,
    HANDICAP_VISIBLE,
    MERITE_ADMISSION,
    placesCasSociaux,
    PRIORITE_BOURSIERS,
    REPORT_PLACES,
    SOURCE_BOURSIER,
    tranchesEloignement,
    VOLETS,
    type CampagneCriteres,
    type CodeCategorie,
    type PageCriteres,
    type PlacesCite,
    type Regles,
    type Volet,
} from "@/components/campagnes/criteres";
import { nomCampagne } from "@/components/campagnes/types";
import { ConfirmDialog } from "@/components/feedback/confirm-dialog";
import { ICON } from "@/components/layout/app-sidebar";
import {
    ErreurGenerale,
    Note,
    PiedAssistant,
    Recap,
} from "@/components/parametrage-hebergement/shared";
import {
    ChoiceCard,
    ToggleCard,
} from "@/components/parc-immobilier/form/inputs";
import { Badge } from "@/components/ui/badge";
import { buttonVariants } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { DatePicker } from "@/components/ui/date-picker";
import { Field } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { MultiCombobox } from "@/components/ui/multi-combobox";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Stepper } from "@/components/ui/stepper";
import { Textarea } from "@/components/ui/textarea";
import { envoyer } from "@/lib/inertia-form";
import { cn } from "@/lib/utils";
import {
    c4Saisi,
    criteresSchema,
    placesAEnvoyer,
    reglesAEnvoyer,
    reglesCroisees,
    sousQuotasSaisis,
    valeursInitiales,
    type CriteresFormInput,
    type CriteresFormOutput,
} from "@/schemas/criteres-campagne";

type Bloc = "places" | "regles" | "final";
type CleEtape =
    | "places"
    | "recevabilite"
    | "conditions"
    | "categories"
    | "merite"
    | "classement"
    | "verification";
type Etape = {
    cle: CleEtape;
    label: string;
    bloc: Bloc;
    champs: Path<CriteresFormInput>[];
};

/** Message d'introduction selon le point de départ (aucun quand on part des critères en vigueur). */
const SOURCES: Record<PageCriteres["source"], string | null> = {
    brouillon: "Vous reprenez le brouillon enregistré.",
    en_vigueur: null,
    modele: "Vous partez du modèle CROU de Bondoukou (compte rendu S/DAL) : adaptez-le, désactivez les critères inutiles, puis appliquez-le.",
};

/** Étapes de l'assistant selon le volet et les droits (un bloc non consultable n'a pas d'étape). */
function etapesDuVolet(volet: Volet, droits: PageCriteres["droits"]): Etape[] {
    const liste: Etape[] = [];
    if (droits.voirPlaces)
        liste.push({
            cle: "places",
            label: "Places par cité",
            bloc: "places",
            champs: ["places"],
        });
    if (droits.voirRegles) {
        liste.push({
            cle: "recevabilite",
            label: "Recevabilité",
            bloc: "regles",
            champs:
                volet === "admission"
                    ? ["calendrier", "recevabilite", "admission.age_max_L1"]
                    : ["calendrier", "recevabilite"],
        });
        if (volet === "readmission") {
            liste.push({
                cle: "conditions",
                label: "Conditions pour postuler",
                bloc: "regles",
                champs: [
                    "readmission.ue_actif",
                    "readmission.seuil_ue",
                    "readmission.concours_actif",
                    "readmission.seuil_concours",
                    "readmission.filieres_concours",
                    "readmission.loyers",
                    "readmission.age_max",
                ],
            });
            liste.push({
                cle: "categories",
                label: "Catégories",
                bloc: "regles",
                champs: ["readmission.categories", "readmission.report_places"],
            });
        } else {
            liste.push({
                cle: "merite",
                label: "Mérite et éloignement",
                bloc: "regles",
                champs: [
                    "admission.merite",
                    "admission.handicap_visible",
                    "admission.tranche_basse",
                    "admission.tranche_haute",
                ],
            });
        }
        liste.push({
            cle: "classement",
            label: "Classement",
            bloc: "regles",
            champs: ["classement"],
        });
    }
    liste.push({
        cle: "verification",
        label: "Vérification",
        bloc: "final",
        champs: ["motif"],
    });
    return liste;
}

/**
 * Définition des critères d'un volet (admission ou réadmission) d'une campagne, en une fois : places par cité,
 * recevabilité, conditions, catégories ou mérite, et classement. Chaque critère peut être modifié ou désactivé ;
 * l'enregistrement crée un brouillon, appliqué ou non.
 */
export function CriteresFormulaire(props: PageCriteres) {
    const { campagne, etat, source } = props;
    // Remonté après chaque enregistrement (nouvel état) pour repartir des valeurs du serveur.
    return (
        <Assistant
            key={`${campagne.id}-${props.volet}-${source}-${etat.version ?? 0}-${etat.brouillon}`}
            {...props}
        />
    );
}

function Assistant({
    volet,
    campagne,
    droits,
    regles,
    places,
    source,
    etat,
    filieres,
    urls,
}: PageCriteres) {
    const versionEnVigueur = etat.version;
    const [erreurGenerale, setErreurGenerale] = useState<string | null>(null);
    const etapes = useMemo(() => etapesDuVolet(volet, droits), [volet, droits]);

    const [etape, setEtape] = useState(0);
    const valeursDepart = useMemo(
        () => valeursInitiales(volet, regles, places, droits.configurerRegles),
        [volet, regles, places, droits.configurerRegles],
    );
    const {
        control,
        register,
        trigger,
        watch,
        setValue,
        getValues,
        setError,
        reset,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<CriteresFormInput, unknown, CriteresFormOutput>({
        resolver: zodResolver(criteresSchema),
        mode: "onTouched",
        defaultValues: valeursDepart,
    });

    const capacites = useMemo(
        () =>
            Object.fromEntries(
                places.map((ligne) => [
                    ligne.cite_id,
                    {
                        cite: ligne.cite,
                        capacite: ligne.capacite,
                        autre: ligne.autre_volet ?? null,
                    },
                ]),
            ),
        [places],
    );
    const contexte = {
        volet,
        capacites,
        cloture: campagne.periode_fin,
        controlerPlaces: droits.configurerPlaces,
        controlerRegles: droits.configurerRegles,
    };
    /** Un bloc en lecture seule (droit absent, campagne terminée) n'est ni validé ni envoyé. */
    const editable = (bloc: Bloc) =>
        !!urls.submit &&
        (bloc === "places"
            ? droits.configurerPlaces
            : bloc === "regles"
              ? droits.configurerRegles
              : true);
    const couvre = (e: Etape, cle: string) =>
        e.champs.some(
            (prefixe) => cle === prefixe || cle.startsWith(`${prefixe}.`),
        );

    /** Vérifie une étape (schéma + règles entre champs) ; les blocs en lecture seule passent toujours. */
    const validerEtape = async (index: number) => {
        const e = etapes[index];
        if (!editable(e.bloc)) return true;
        let ok = await trigger(e.champs, { shouldFocus: true });
        Object.entries(reglesCroisees(getValues(), contexte)).forEach(
            ([cle, message]) => {
                if (couvre(e, cle)) {
                    setError(
                        cle as Path<CriteresFormInput>,
                        { type: "croisee", message },
                        { shouldFocus: ok },
                    );
                    ok = false;
                }
            },
        );
        return ok;
    };

    const allerA = async (cible: number) => {
        for (let index = etape; index < cible; index++) {
            if (!(await validerEtape(index))) {
                setEtape(index);
                return false;
            }
        }
        setEtape(cible);
        return true;
    };

    /** Erreurs Laravel (`regles.readmission.seuil_ue`, `places.0.quotas`) replacées sous les champs, retour à l'étape concernée. */
    const repartirErreurs = (erreurs: Record<string, string>) => {
        let premiere = etapes.length;
        const autres: string[] = [];
        Object.entries(erreurs).forEach(([cle, message]) => {
            const champ = cle
                .replace(/^regles\./, "")
                .replace(/^(places\.\d+)\.quotas\.(C\d)$/, "$1.$2")
                .replace(/^(places\.\d+)\.quotas$/, "$1.C1")
                .replace(/eloignement\.tranches_km(\.\d+)?$/, "tranche_haute")
                .replace(/^(classement\.departage)\.\d+$/, "$1");
            const index = etapes.findIndex((e) => couvre(e, champ));
            if (index >= 0) {
                setError(champ as Path<CriteresFormInput>, {
                    type: "server",
                    message,
                });
                premiere = Math.min(premiere, index);
            } else {
                autres.push(message);
            }
        });
        setErreurGenerale(autres[0] ?? null);
        if (premiere < etapes.length) setEtape(premiere);
    };

    const enregistrer = async (valeurs: CriteresFormOutput) => {
        if (!urls.submit) return;
        if (
            valeurs.appliquer &&
            versionEnVigueur !== null &&
            valeurs.motif.trim().length < 5
        ) {
            setError(
                "motif",
                {
                    type: "manual",
                    message:
                        "Indiquez le motif du remplacement des critères en vigueur (5 caractères au minimum).",
                },
                { shouldFocus: true },
            );
            return;
        }
        const donnees: Record<string, unknown> = {
            appliquer: valeurs.appliquer,
            motif: valeurs.motif.trim() || null,
        };
        if (droits.configurerRegles)
            donnees.regles = reglesAEnvoyer(volet, valeurs);
        if (droits.configurerPlaces)
            donnees.places = placesAEnvoyer(volet, valeurs);
        await envoyer<CriteresFormInput>(urls.submit, donnees as never, {
            method: "put",
            onError: repartirErreurs,
        });
    };

    const valeurs = watch();
    const courante = etapes[etape];
    const derniere = etape === etapes.length - 1;
    const erreur = (chemin: string) =>
        chemin
            .split(".")
            .reduce<unknown>(
                (objet, cle) =>
                    (objet as Record<string, unknown> | undefined)?.[cle],
                errors,
            ) as { message?: string } | undefined;
    const readmission = valeurs.readmission;
    const admission = valeurs.admission;
    const filieresConcours = filieres
        .filter((filiere) =>
            readmission?.filieres_concours.includes(filiere.value),
        )
        .map((filiere) => filiere.label);

    /** Champ numérique avec unité, enregistré dans le formulaire. */
    const champNombre = (
        chemin: Path<CriteresFormInput>,
        label: string,
        unite: string,
        options: { aide?: string; requis?: boolean; placeholder?: string } = {},
    ) => {
        const id = `cr-${volet}-${chemin.replace(/\./g, "-")}`;
        return (
            <Field
                id={id}
                label={label}
                required={options.requis}
                error={erreur(chemin)?.message}
                hint={options.aide}
            >
                <Input
                    id={id}
                    inputMode="numeric"
                    placeholder={options.placeholder}
                    className={unite ? "pr-16" : undefined}
                    trailing={unite ? <Unite>{unite}</Unite> : undefined}
                    invalid={!!erreur(chemin)}
                    {...register(chemin)}
                />
            </Field>
        );
    };

    return (
        <form
            noValidate
            className="flex min-w-0 flex-col overflow-hidden rounded-brand border bg-white"
            onSubmit={async (event) => {
                event.preventDefault();
                setErreurGenerale(null);
                if (!derniere) {
                    await allerA(etape + 1);
                    return;
                }
                for (let index = 0; index < etapes.length - 1; index++) {
                    if (!(await validerEtape(index))) {
                        setEtape(index);
                        return;
                    }
                }
                await handleSubmit(enregistrer, () =>
                    setErreurGenerale(
                        "Certaines valeurs sont invalides : corrigez les étapes signalées.",
                    ),
                )();
            }}
        >
            <Stepper
                steps={etapes.map((e) => e.label)}
                current={etape}
                reached={etapes.length - 1}
                onStepClick={(index) => void allerA(index)}
                className="overflow-x-auto"
            />
            <div className="space-y-6 px-6 py-5">
                {SOURCES[source] && (
                    <p className="text-sm text-muted">{SOURCES[source]}</p>
                )}
                <ErreurGenerale message={erreurGenerale} />
                {!campagne.modifiable && (
                    <Note icone={<ShieldCheck />}>
                        Campagne terminée : ses critères sont figés et
                        s’affichent en lecture seule.
                    </Note>
                )}
                {campagne.modifiable && !editable(courante.bloc) && (
                    <Note icone={<ShieldCheck />} ton="attention">
                        {courante.bloc === "places"
                            ? "Les places sont saisies par le comité de direction : vous les consultez en lecture seule."
                            : "Les critères sont paramétrés par la S/DAL : vous les consultez en lecture seule."}
                    </Note>
                )}

                <fieldset
                    disabled={!editable(courante.bloc) || isSubmitting}
                    className="min-w-0 space-y-6"
                >
                    {courante.cle === "places" && (
                        <EtapePlaces
                            volet={volet}
                            valeurs={valeurs}
                            places={places}
                            register={register}
                            erreur={erreur}
                        />
                    )}

                    {courante.cle === "recevabilite" && (
                        <>
                            <Titre aide="Dates propres à la liste provisoire ; vides, elles suivent la clôture des dépôts.">
                                Calendrier
                            </Titre>
                            <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-3">
                                {(volet === "readmission"
                                    ? ([
                                          [
                                              "date_controle_prealables",
                                              "Contrôle des préalables",
                                              "Loyers et UE vérifiés à cette date (défaut : clôture).",
                                          ],
                                          [
                                              "date_liste_provisoire",
                                              "Génération de la liste provisoire",
                                              "Défaut : après le contrôle des préalables.",
                                          ],
                                      ] as const)
                                    : ([
                                          [
                                              "date_liste_provisoire",
                                              "Génération de la liste provisoire",
                                              "Défaut : à la clôture des dépôts.",
                                          ],
                                      ] as const)
                                ).map(([cle, label, aide]) => (
                                    <Field
                                        key={cle}
                                        id={`cr-${volet}-${cle}`}
                                        label={label}
                                        error={
                                            erreur(`calendrier.${cle}`)?.message
                                        }
                                        hint={aide}
                                    >
                                        <Controller
                                            control={control}
                                            name={`calendrier.${cle}`}
                                            render={({ field }) => (
                                                <div className="space-y-1">
                                                    <DatePicker
                                                        id={`cr-${volet}-${cle}`}
                                                        value={field.value}
                                                        onChange={
                                                            field.onChange
                                                        }
                                                        min={
                                                            campagne.periode_fin ??
                                                            undefined
                                                        }
                                                        invalid={
                                                            !!erreur(
                                                                `calendrier.${cle}`,
                                                            )
                                                        }
                                                    />
                                                    {field.value && (
                                                        <button
                                                            type="button"
                                                            className="cursor-pointer text-xs text-primary hover:underline"
                                                            onClick={() =>
                                                                field.onChange(
                                                                    "",
                                                                )
                                                            }
                                                        >
                                                            Effacer la date
                                                        </button>
                                                    )}
                                                </div>
                                            )}
                                        />
                                    </Field>
                                ))}
                                {/* « Délai de complétude » masqué pour l'instant : la valeur enregistrée (7 jours par défaut) est conservée telle quelle. */}
                            </div>
                            <Titre aide="Dépôt hors période, doublon, séjour non clôturé et sanction en cours (suspension, expulsion) sont déjà bloqués à la saisie.">
                                Recevabilité (E1)
                            </Titre>
                            <Controller
                                control={control}
                                name="recevabilite.internationaux"
                                render={({ field }) => (
                                    <ToggleCard
                                        checked={field.value}
                                        onChange={field.onChange}
                                        label="Ouvert aux étudiants internationaux"
                                        description={`Décoché : « Campagne non ouverte aux étudiants internationaux » (${volet === "admission" ? "A-04" : "R-05"}).`}
                                    />
                                )}
                            />
                            {volet === "admission" && (
                                <div className="md:max-w-xs">
                                    {champNombre(
                                        "admission.age_max_L1",
                                        "Âge maximum en L1",
                                        "ans",
                                        {
                                            aide: "Vide : désactivé (A-11).",
                                            placeholder: "Désactivé",
                                        },
                                    )}
                                </div>
                            )}
                        </>
                    )}

                    {courante.cle === "conditions" && readmission && (
                        <>
                            <Titre aide="Évaluées sur le PV de l’université ; une donnée absente donne « À vérifier », jamais un rejet. Décochez un critère pour le désactiver.">
                                Unités d’enseignement (R-10, R-11)
                            </Titre>
                            <Controller
                                control={control}
                                name="readmission.ue_actif"
                                render={({ field }) => (
                                    <ToggleCard
                                        checked={!!field.value}
                                        onChange={field.onChange}
                                        label="Exiger l’année validée (R-10)"
                                        description="Sinon : « Non éligible – année non validée »."
                                    />
                                )}
                            />
                            {readmission.ue_actif && (
                                <div className="md:max-w-xs">
                                    {champNombre(
                                        "readmission.seuil_ue",
                                        "Année validée exigée",
                                        "%",
                                        {
                                            requis: true,
                                            aide: "100 % = 60 crédits sur 60.",
                                        },
                                    )}
                                </div>
                            )}
                            <Controller
                                control={control}
                                name="readmission.concours_actif"
                                render={({ field }) => (
                                    <ToggleCard
                                        checked={!!field.value}
                                        onChange={field.onChange}
                                        label="Seuil propre aux écoles sur concours (R-11)"
                                        description="En dessous : retrait de chambre ; au-dessus : réadmission d’office (C1)."
                                    />
                                )}
                            />
                            {readmission.concours_actif && (
                                <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-[240px_minmax(0,1fr)]">
                                    {champNombre(
                                        "readmission.seuil_concours",
                                        "Seuil des écoles sur concours",
                                        "%",
                                        { requis: true },
                                    )}
                                    <Field
                                        id={`cr-${volet}-filieres`}
                                        label="Écoles et filières sur concours"
                                        hint="Ex. ENSAU, Architecture d’intérieur, Architecture et urbanisme."
                                    >
                                        <Controller
                                            control={control}
                                            name="readmission.filieres_concours"
                                            render={({ field }) => (
                                                <MultiCombobox
                                                    id={`cr-${volet}-filieres`}
                                                    value={field.value ?? []}
                                                    onChange={field.onChange}
                                                    options={filieres}
                                                    placeholder="Sélectionner les filières…"
                                                    searchPlaceholder="Rechercher une filière…"
                                                    emptyMessage="Aucune filière trouvée."
                                                />
                                            )}
                                        />
                                    </Field>
                                </div>
                            )}

                            <Titre aide="Contrôle informatif au dépôt, décisif à la date de contrôle, sur les paiements constatés.">
                                Loyers de l’année (R-12)
                            </Titre>
                            <Controller
                                control={control}
                                name="readmission.loyers.actif"
                                render={({ field }) => (
                                    <ToggleCard
                                        checked={!!field.value}
                                        onChange={field.onChange}
                                        label="Exiger les loyers de l’année soldés"
                                        description="Sinon : « Non éligible – loyers de l’année non soldés »."
                                    />
                                )}
                            />
                            {readmission.loyers.actif && (
                                <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                                    {champNombre(
                                        "readmission.loyers.mois_exiges",
                                        "Mois de loyers exigés",
                                        "mois",
                                        { requis: true },
                                    )}
                                    {champNombre(
                                        "readmission.loyers.tolerance_fcfa",
                                        "Tolérance sur le reste dû",
                                        "FCFA",
                                        {
                                            requis: true,
                                            aide: "0 = aucun impayé accepté.",
                                        },
                                    )}
                                </div>
                            )}

                            <Titre aide="Absent du compte rendu : vide = désactivé (R-13).">
                                Âge maximum
                            </Titre>
                            <div className="md:max-w-xs">
                                {champNombre(
                                    "readmission.age_max",
                                    "Âge maximum en réadmission",
                                    "ans",
                                    { placeholder: "Désactivé" },
                                )}
                            </div>
                        </>
                    )}

                    {courante.cle === "categories" && readmission && (
                        <>
                            <Titre aide="Examinées dans l’ordre C1 → C6 : un candidat est retenu dans la première catégorie où il entre dans le quota. Décochez une catégorie pour la désactiver.">
                                Catégories prioritaires (E4)
                            </Titre>
                            <div className="space-y-3">
                                {CODES_CATEGORIES.map((code) => (
                                    <CarteCategorie
                                        key={code}
                                        code={code}
                                        actif={
                                            readmission.categories[code].actif
                                        }
                                        onActif={(actif) =>
                                            setValue(
                                                `readmission.categories.${code}.actif`,
                                                actif,
                                                { shouldDirty: true },
                                            )
                                        }
                                    >
                                        {code === "C1" && (
                                            <p className="text-xs text-muted">
                                                Quota en places, saisi par cité
                                                à l’étape « Places par cité ».
                                            </p>
                                        )}
                                        {code === "C2" && (
                                            <div className="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
                                                {champNombre(
                                                    "readmission.categories.C2.n",
                                                    "Premiers retenus par groupe",
                                                    "",
                                                    { requis: true },
                                                )}
                                                <Field
                                                    id={`cr-${volet}-c2_groupe`}
                                                    label="Groupe de classement"
                                                >
                                                    <Controller
                                                        control={control}
                                                        name="readmission.categories.C2.groupe"
                                                        render={({ field }) => (
                                                            <Choix
                                                                id={`cr-${volet}-c2_groupe`}
                                                                value={
                                                                    field.value ??
                                                                    "filiere_niveau"
                                                                }
                                                                onChange={
                                                                    field.onChange
                                                                }
                                                                options={
                                                                    GROUPES_C2
                                                                }
                                                            />
                                                        )}
                                                    />
                                                </Field>
                                            </div>
                                        )}
                                        {code === "C3" && (
                                            <div className="md:max-w-xs">
                                                {champNombre(
                                                    "readmission.categories.C3.n",
                                                    "Premiers retenus par discipline",
                                                    "",
                                                    {
                                                        requis: true,
                                                        aide: "14 disciplines : 3 × 14 = 42 places au plus.",
                                                    },
                                                )}
                                            </div>
                                        )}
                                        {code === "C4" && (
                                            <div className="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
                                                {champNombre(
                                                    "readmission.categories.C4.taux",
                                                    "Part réservée",
                                                    "%",
                                                    {
                                                        requis: true,
                                                        aide: "Règlement intérieur : 10 % de l’effectif.",
                                                    },
                                                )}
                                                <Field
                                                    id={`cr-${volet}-c4_base`}
                                                    label="Calculée sur"
                                                >
                                                    <Controller
                                                        control={control}
                                                        name="readmission.categories.C4.base"
                                                        render={({ field }) => (
                                                            <Choix
                                                                id={`cr-${volet}-c4_base`}
                                                                value={
                                                                    field.value ??
                                                                    "places_readmission_cite"
                                                                }
                                                                onChange={
                                                                    field.onChange
                                                                }
                                                                options={
                                                                    BASES_C4
                                                                }
                                                            />
                                                        )}
                                                    />
                                                </Field>
                                            </div>
                                        )}
                                        {code === "C5" && (
                                            <Controller
                                                control={control}
                                                name="readmission.categories.C5.prealables"
                                                render={({ field }) => (
                                                    <ToggleCard
                                                        checked={!!field.value}
                                                        onChange={
                                                            field.onChange
                                                        }
                                                        label="Les membres doivent remplir les conditions pour postuler"
                                                        description="UE validées et loyers de l’année soldés."
                                                    />
                                                )}
                                            />
                                        )}
                                        {code === "C6" && (
                                            <p className="text-xs text-muted">
                                                Solde des places de réadmission
                                                de la cité, augmenté des places
                                                reportées.
                                            </p>
                                        )}
                                    </CarteCategorie>
                                ))}
                            </div>
                            <div className="md:max-w-xl">
                                <Field
                                    id={`cr-${volet}-report`}
                                    label="Places non pourvues d’une catégorie"
                                    hint="Ex. moins de 42 lauréats de clubs."
                                >
                                    <Controller
                                        control={control}
                                        name="readmission.report_places"
                                        render={({ field }) => (
                                            <Choix
                                                id={`cr-${volet}-report`}
                                                value={
                                                    field.value ??
                                                    "C6_puis_admission"
                                                }
                                                onChange={field.onChange}
                                                options={REPORT_PLACES}
                                            />
                                        )}
                                    />
                                </Field>
                            </div>
                        </>
                    )}

                    {courante.cle === "merite" && admission && (
                        <>
                            <Titre aide="Pas de critère de comportement ni de cas social sur dossier pour les nouveaux étudiants ; seule exception, le handicap visible.">
                                Mérite (E3, E4)
                            </Titre>
                            <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                                <Field id={`cr-${volet}-merite`} label="Mérite">
                                    <Controller
                                        control={control}
                                        name="admission.merite"
                                        render={({ field }) => (
                                            <Choix
                                                id={`cr-${volet}-merite`}
                                                value={
                                                    field.value ??
                                                    "moyenne_bac_desc"
                                                }
                                                onChange={field.onChange}
                                                options={MERITE_ADMISSION}
                                            />
                                        )}
                                    />
                                </Field>
                                <Field
                                    id={`cr-${volet}-handicap`}
                                    label="Handicap visible (PMR confirmé)"
                                >
                                    <Controller
                                        control={control}
                                        name="admission.handicap_visible"
                                        render={({ field }) => (
                                            <Choix
                                                id={`cr-${volet}-handicap`}
                                                value={
                                                    field.value ?? "commission"
                                                }
                                                onChange={field.onChange}
                                                options={HANDICAP_VISIBLE}
                                            />
                                        )}
                                    />
                                </Field>
                            </div>
                            <Titre aide="À mérite égal, priorité au candidat venant de loin (lieu d’obtention du baccalauréat → Bondoukou), si l’éloignement est actif dans le départage.">
                                Tranches d’éloignement
                            </Titre>
                            <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                                {champNombre(
                                    "admission.tranche_basse",
                                    "Première limite",
                                    "km",
                                    { requis: true },
                                )}
                                {champNombre(
                                    "admission.tranche_haute",
                                    "Seconde limite",
                                    "km",
                                    { requis: true },
                                )}
                            </div>
                            <TableTranches
                                basse={Number(admission.tranche_basse) || 0}
                                haute={Number(admission.tranche_haute) || 0}
                            />
                        </>
                    )}

                    {courante.cle === "classement" && (
                        <>
                            <Titre
                                aide={
                                    volet === "readmission"
                                        ? "Clé de classement dans chaque catégorie : boursier, mérite propre à la catégorie, puis départage."
                                        : "Clé de classement : boursier, mérite, puis départage."
                                }
                            >
                                Priorité aux boursiers
                            </Titre>
                            <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                                {(
                                    Object.keys(
                                        PRIORITE_BOURSIERS,
                                    ) as Regles["classement"]["priorite_boursiers"][]
                                ).map((valeur) => (
                                    <ChoiceCard
                                        key={valeur}
                                        name="priorite_boursiers"
                                        value={valeur}
                                        selected={
                                            valeurs.classement
                                                .priorite_boursiers === valeur
                                        }
                                        onSelect={() =>
                                            setValue(
                                                "classement.priorite_boursiers",
                                                valeur,
                                                { shouldDirty: true },
                                            )
                                        }
                                        title={PRIORITE_BOURSIERS[valeur].label}
                                        description={
                                            PRIORITE_BOURSIERS[valeur]
                                                .description
                                        }
                                        icon={
                                            <GraduationCap
                                                {...ICON}
                                                aria-hidden
                                            />
                                        }
                                    />
                                ))}
                            </div>
                            <div className="md:max-w-xl">
                                <Field
                                    id={`cr-${volet}-source_boursier`}
                                    label="Statut de boursier retenu"
                                    hint="Un boursier déclaré mais non justifié est classé non boursier et signalé « À vérifier »."
                                >
                                    <Controller
                                        control={control}
                                        name="classement.source_boursier"
                                        render={({ field }) => (
                                            <Choix
                                                id={`cr-${volet}-source_boursier`}
                                                value={field.value}
                                                onChange={field.onChange}
                                                options={SOURCE_BOURSIER}
                                            />
                                        )}
                                    />
                                </Field>
                            </div>
                            <Titre aide="Appliqué en cas d’égalité de mérite, dans cet ordre ; décochez un critère pour le désactiver. Une égalité persistante est arbitrée par la S/DAL (aucun tirage au sort).">
                                Ordre de départage
                            </Titre>
                            <Controller
                                control={control}
                                name="classement.departage"
                                render={({ field }) => (
                                    <ol
                                        className="space-y-2"
                                        aria-describedby={
                                            erreur("classement.departage")
                                                ? `cr-${volet}-departage-error`
                                                : undefined
                                        }
                                    >
                                        {field.value.map((item, index) => {
                                            const deplacer = (vers: number) => {
                                                const liste = [...field.value];
                                                [liste[index], liste[vers]] = [
                                                    liste[vers],
                                                    liste[index],
                                                ];
                                                field.onChange(liste);
                                            };
                                            const id = `cr-${volet}-departage-${item.critere}`;
                                            return (
                                                <li
                                                    key={item.critere}
                                                    className={cn(
                                                        "flex items-center gap-3 rounded-brand border px-3 py-2.5",
                                                        item.actif
                                                            ? "bg-white"
                                                            : "bg-surface/60",
                                                    )}
                                                >
                                                    <Checkbox
                                                        id={id}
                                                        checked={item.actif}
                                                        onCheckedChange={(
                                                            coche,
                                                        ) =>
                                                            field.onChange(
                                                                field.value.map(
                                                                    (
                                                                        ligne,
                                                                        i,
                                                                    ) =>
                                                                        i ===
                                                                        index
                                                                            ? {
                                                                                  ...ligne,
                                                                                  actif:
                                                                                      coche ===
                                                                                      true,
                                                                              }
                                                                            : ligne,
                                                                ),
                                                            )
                                                        }
                                                    />
                                                    <label
                                                        htmlFor={id}
                                                        className={cn(
                                                            "min-w-0 flex-1 cursor-pointer",
                                                            !item.actif &&
                                                                "opacity-60",
                                                        )}
                                                    >
                                                        <span className="block text-sm text-gray-900">
                                                            {index + 1}.{" "}
                                                            {
                                                                DEPARTAGE[
                                                                    item.critere
                                                                ].label
                                                            }
                                                            {!item.actif && (
                                                                <span className="text-muted">
                                                                    {" "}
                                                                    · désactivé
                                                                </span>
                                                            )}
                                                        </span>
                                                        <span className="block text-xs text-muted">
                                                            {
                                                                DEPARTAGE[
                                                                    item.critere
                                                                ].description
                                                            }
                                                        </span>
                                                    </label>
                                                    <BoutonOrdre
                                                        label={`Monter : ${DEPARTAGE[item.critere].label}`}
                                                        disabled={index === 0}
                                                        onClick={() =>
                                                            deplacer(index - 1)
                                                        }
                                                    >
                                                        <ArrowUp
                                                            className="size-4"
                                                            aria-hidden
                                                        />
                                                    </BoutonOrdre>
                                                    <BoutonOrdre
                                                        label={`Descendre : ${DEPARTAGE[item.critere].label}`}
                                                        disabled={
                                                            index ===
                                                            field.value.length -
                                                                1
                                                        }
                                                        onClick={() =>
                                                            deplacer(index + 1)
                                                        }
                                                    >
                                                        <ArrowDown
                                                            className="size-4"
                                                            aria-hidden
                                                        />
                                                    </BoutonOrdre>
                                                </li>
                                            );
                                        })}
                                    </ol>
                                )}
                            />
                            {erreur("classement.departage") && (
                                <p
                                    id={`cr-${volet}-departage-error`}
                                    role="alert"
                                    className="text-xs font-medium text-red-600"
                                >
                                    {erreur("classement.departage")?.message}
                                </p>
                            )}
                            <div className="md:max-w-xs">
                                {champNombre(
                                    "classement.k_liste_elargie",
                                    "Coefficient de la liste élargie",
                                    "×",
                                    {
                                        requis: true,
                                        aide: "2 = 10 noms proposés pour 5 places (titulaires puis complémentaires).",
                                    },
                                )}
                            </div>
                        </>
                    )}

                    {courante.cle === "verification" && (
                        <div className="space-y-4">
                            {droits.voirPlaces && (
                                <Recap
                                    lignes={valeurs.places.map((ligne) => {
                                        const info = capacites[ligne.cite_id];
                                        return {
                                            label: `${info?.cite ?? "Cité"} (capacité ${info?.capacite ?? 0})`,
                                            valeur: `${ligne.places || "—"} places ${VOLETS[volet].complement}`,
                                        };
                                    })}
                                />
                            )}
                            {droits.voirRegles && (
                                <Recap
                                    lignes={[
                                        {
                                            label: "Internationaux",
                                            valeur: valeurs.recevabilite
                                                .internationaux
                                                ? "Acceptés"
                                                : "Non acceptés",
                                        },
                                        ...(readmission
                                            ? [
                                                  {
                                                      label: "Année validée",
                                                      valeur: readmission.ue_actif
                                                          ? `${readmission.seuil_ue} % des UE`
                                                          : "Désactivé",
                                                  },
                                                  {
                                                      label: "Écoles sur concours",
                                                      valeur: readmission.concours_actif
                                                          ? `${readmission.seuil_concours} % · ${filieresConcours.join(", ") || "aucune filière"}`
                                                          : "Désactivé",
                                                  },
                                                  {
                                                      label: "Loyers",
                                                      valeur: readmission.loyers
                                                          .actif
                                                          ? `${readmission.loyers.mois_exiges} mois soldés`
                                                          : "Désactivé",
                                                  },
                                                  {
                                                      label: "Catégories actives",
                                                      valeur:
                                                          CODES_CATEGORIES.filter(
                                                              (code) =>
                                                                  readmission
                                                                      .categories[
                                                                      code
                                                                  ].actif,
                                                          ).join(" › ") ||
                                                          "Aucune",
                                                  },
                                              ]
                                            : []),
                                        ...(admission
                                            ? [
                                                  {
                                                      label: "Âge maximum en L1",
                                                      valeur: admission.age_max_L1
                                                          ? `${admission.age_max_L1} ans`
                                                          : "Désactivé",
                                                  },
                                                  {
                                                      label: "Mérite",
                                                      valeur: MERITE_ADMISSION[
                                                          admission.merite
                                                      ],
                                                  },
                                                  {
                                                      label: "Handicap visible",
                                                      valeur: HANDICAP_VISIBLE[
                                                          admission
                                                              .handicap_visible
                                                      ],
                                                  },
                                              ]
                                            : []),
                                        {
                                            label: "Priorité boursiers",
                                            valeur: PRIORITE_BOURSIERS[
                                                valeurs.classement
                                                    .priorite_boursiers
                                            ].label,
                                        },
                                        {
                                            label: "Départage",
                                            valeur:
                                                valeurs.classement.departage
                                                    .filter(
                                                        (item) => item.actif,
                                                    )
                                                    .map(
                                                        (item) =>
                                                            DEPARTAGE[
                                                                item.critere
                                                            ].label,
                                                    )
                                                    .join(" › ") || "—",
                                        },
                                        {
                                            label: "Liste élargie",
                                            valeur: `× ${valeurs.classement.k_liste_elargie}`,
                                        },
                                    ]}
                                />
                            )}

                            {droits.configurerRegles ? (
                                <>
                                    <Controller
                                        control={control}
                                        name="appliquer"
                                        render={({ field }) => (
                                            <ToggleCard
                                                checked={field.value}
                                                onChange={field.onChange}
                                                label="Appliquer les critères dès l’enregistrement"
                                                description={
                                                    versionEnVigueur !== null
                                                        ? `La version ${versionEnVigueur} en vigueur sera archivée.`
                                                        : "Décochée : les critères restent en brouillon, à appliquer plus tard."
                                                }
                                            />
                                        )}
                                    />
                                    {valeurs.appliquer &&
                                        versionEnVigueur !== null && (
                                            <Field
                                                id={`cr-${volet}-motif`}
                                                label="Motif du remplacement"
                                                required
                                                error={errors.motif?.message}
                                                hint="Inscrit dans l’historique des versions."
                                            >
                                                <Textarea
                                                    id={`cr-${volet}-motif`}
                                                    rows={3}
                                                    maxLength={1000}
                                                    placeholder="Ex : quotas arrêtés par le comité de direction"
                                                    invalid={!!errors.motif}
                                                    {...register("motif")}
                                                />
                                            </Field>
                                        )}
                                </>
                            ) : (
                                <Note icone={<ShieldCheck />}>
                                    Vos modifications seront enregistrées en
                                    brouillon ; la S/DAL les appliquera.
                                </Note>
                            )}
                        </div>
                    )}
                </fieldset>
            </div>
            <PiedAssistant
                etape={etape}
                derniere={derniere}
                envoi={isSubmitting}
                desactive={!urls.submit}
                onAnnuler={() => {
                    reset(valeursDepart);
                    setErreurGenerale(null);
                    setEtape(0);
                }}
                onPrecedent={() => setEtape(etape - 1)}
                libelle={
                    valeurs.appliquer && droits.configurerRegles
                        ? "Enregistrer et appliquer"
                        : "Enregistrer le brouillon"
                }
            />
        </form>
    );
}

/** Encart de la page : état des critères du volet, application et abandon du brouillon. */
export function EtatCriteresVolet({
    volet,
    campagne,
    etat,
    urls,
}: Pick<PageCriteres, "volet" | "campagne" | "etat" | "urls">) {
    const [appliquer, setAppliquer] = useState(false);
    const [abandon, setAbandon] = useState(false);
    const [traitement, setTraitement] = useState(false);
    const info = ETATS_CRITERES[etat.etat];

    return (
        <aside className="h-fit space-y-3 rounded-brand border bg-white p-4">
            <h2 className="text-sm font-medium text-gray-900">
                État des critères {VOLETS[volet].complement}
            </h2>
            <div className="flex flex-wrap items-center gap-2">
                <Badge variant={info.variant}>
                    {info.label}
                </Badge>
                {etat.etat === "en_vigueur" && etat.brouillon && (
                    <Badge variant="info">Brouillon en attente</Badge>
                )}
            </div>
            <p className="text-xs text-muted">
                {etat.etat === "a_parametrer"
                    ? "Aucun critère n’est encore appliqué : le modèle CROU de Bondoukou est proposé."
                    : etat.brouillon
                      ? "Des modifications attendent d’être appliquées."
                      : "Les critères en vigueur sont à jour."}
            </p>
            {(urls.appliquer || urls.abandonner) && (
                <div className="flex flex-col gap-2">
                    {urls.appliquer && (
                        <button
                            type="button"
                            onClick={() => setAppliquer(true)}
                            className={cn(
                                buttonVariants({ size: "sm" }),
                                "h-10",
                            )}
                        >
                            <CheckCheck className="size-4" aria-hidden />{" "}
                            Appliquer{" "}
                            {etat.brouillon ? "le brouillon" : "le modèle CROU"}
                        </button>
                    )}
                    {urls.abandonner && (
                        <button
                            type="button"
                            onClick={() => setAbandon(true)}
                            className={cn(
                                buttonVariants({
                                    variant: "outline",
                                    size: "sm",
                                }),
                                "h-10 text-red-600",
                            )}
                        >
                            <Trash2 className="size-4" aria-hidden /> Abandonner
                            le brouillon
                        </button>
                    )}
                </div>
            )}

            <AppliquerCriteresDialog
                url={appliquer ? urls.appliquer : null}
                volet={volet}
                nomCampagne={nomCampagne(campagne)}
                etat={etat}
                onClose={() => setAppliquer(false)}
            />
            <ConfirmDialog
                open={abandon}
                onOpenChange={setAbandon}
                title="Abandonner le brouillon ?"
                description={
                    etat.version !== null
                        ? `Les modifications non appliquées sont supprimées ; la version ${etat.version} reste en vigueur.`
                        : "Les modifications non appliquées sont supprimées."
                }
                confirmLabel="Abandonner"
                confirmIcon={<Trash2 className="size-4" aria-hidden />}
                danger
                processing={traitement}
                onConfirm={() =>
                    urls.abandonner &&
                    router.delete(urls.abandonner, {
                        preserveScroll: true,
                        onStart: () => setTraitement(true),
                        onFinish: () => {
                            setTraitement(false);
                            setAbandon(false);
                        },
                    })
                }
            />
        </aside>
    );
}

type EtapePlacesProps = {
    volet: Volet;
    valeurs: CriteresFormInput;
    places: PlacesCite[];
    register: UseFormRegister<CriteresFormInput>;
    erreur: (chemin: string) => { message?: string } | undefined;
};

/** Étape E0 : places du volet et, en réadmission, sous-quotas par cité, avec les contrôles de capacité en direct. */
function EtapePlaces({
    volet,
    valeurs,
    places,
    register,
    erreur,
}: EtapePlacesProps) {
    if (places.length === 0) {
        return (
            <Note icone={<ShieldCheck />} ton="attention">
                Aucune résidence n’est ouverte à cette campagne : ajoutez-en une
                dans le formulaire de la campagne.
            </Note>
        );
    }
    const categories = valeurs.readmission?.categories;
    const c4 = c4Saisi(valeurs);
    const autreVolet = volet === "admission" ? "readmission" : "admission";

    return (
        <>
            <Titre aide="Fixées par le comité de direction avant l’ouverture ; chaque cité (filles, garçons) a ses propres places et sa propre liste. Vide = à saisir.">
                Répartition des places (E0)
            </Titre>
            <div className="space-y-4">
                {places.map((cite, index) => {
                    const ligne = valeurs.places[index];
                    const propres =
                        ligne.places === "" ? null : Number(ligne.places);
                    const casSociaux =
                        c4 &&
                        placesCasSociaux(c4, {
                            capacite: cite.capacite,
                            places: propres,
                        });
                    const sousQuotas = sousQuotasSaisis(
                        valeurs,
                        ligne,
                        cite.capacite,
                    );
                    const restant =
                        cite.capacite -
                        (propres ?? 0) -
                        (cite.autre_volet ?? 0);
                    const champ = (
                        nom: "places" | "C1" | "C3" | "C5",
                        label: string,
                        aide?: string,
                    ) => {
                        const id = `cr-${volet}-places-${index}-${nom}`;
                        const message = erreur(
                            `places.${index}.${nom}`,
                        )?.message;
                        return (
                            <Field
                                id={id}
                                label={label}
                                error={message}
                                hint={aide}
                            >
                                <Input
                                    id={id}
                                    inputMode="numeric"
                                    placeholder="À saisir"
                                    className="pr-16"
                                    trailing={<Unite>places</Unite>}
                                    invalid={!!message}
                                    {...register(`places.${index}.${nom}`)}
                                />
                            </Field>
                        );
                    };

                    return (
                        <section
                            key={cite.cite_id}
                            className="rounded-brand border"
                        >
                            <header className="flex flex-wrap items-center gap-2 border-b bg-surface/60 px-4 py-2.5">
                                <h4 className="text-sm font-medium text-gray-900">
                                    {cite.cite}
                                </h4>
                                {cite.public && (
                                    <Badge variant="info">{cite.public}</Badge>
                                )}
                                <span className="ml-auto text-xs text-muted tabular-nums">
                                    Capacité : {cite.capacite} places
                                </span>
                            </header>
                            <div className="space-y-4 p-4">
                                <div className="md:max-w-xs">
                                    {champ(
                                        "places",
                                        `Places ${VOLETS[volet].complement}`,
                                        cite.autre_volet !== null &&
                                            cite.autre_volet !== undefined
                                            ? `${cite.autre_volet} places déjà réservées ${VOLETS[autreVolet].complement}.`
                                            : undefined,
                                    )}
                                </div>
                                {volet === "readmission" && categories && (
                                    <div className="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-3">
                                        {categories.C1.actif &&
                                            champ(
                                                "C1",
                                                "C1 · Écoles sur concours",
                                            )}
                                        {categories.C3.actif &&
                                            champ(
                                                "C3",
                                                "C3 · Clubs et associations",
                                                `${categories.C3.n || "—"} par discipline.`,
                                            )}
                                        {categories.C5.actif &&
                                            champ(
                                                "C5",
                                                "C5 · Comité des résidents",
                                            )}
                                    </div>
                                )}
                                <p
                                    className="flex flex-wrap gap-x-6 gap-y-1 rounded-brand bg-surface/60 px-3 py-2 text-xs text-gray-700"
                                    aria-live="polite"
                                >
                                    {volet === "readmission" &&
                                        c4 &&
                                        casSociaux !== null && (
                                            <span>
                                                C4 · Cas sociaux :{" "}
                                                <strong className="tabular-nums">
                                                    {casSociaux}
                                                </strong>{" "}
                                                ({c4.taux} %)
                                            </span>
                                        )}
                                    {volet === "readmission" &&
                                        propres !== null && (
                                            <span>
                                                C2 / C6 · Solde :{" "}
                                                <strong
                                                    className={cn(
                                                        "tabular-nums",
                                                        sousQuotas > propres &&
                                                            "text-red-600",
                                                    )}
                                                >
                                                    {propres - sousQuotas}
                                                </strong>
                                            </span>
                                        )}
                                    <span>
                                        Capacité non répartie :{" "}
                                        <strong
                                            className={cn(
                                                "tabular-nums",
                                                restant < 0 && "text-red-600",
                                            )}
                                        >
                                            {restant}
                                        </strong>
                                    </span>
                                </p>
                            </div>
                        </section>
                    );
                })}
            </div>
        </>
    );
}

function CarteCategorie({
    code,
    actif,
    onActif,
    children,
}: {
    code: CodeCategorie;
    actif: boolean;
    onActif: (actif: boolean) => void;
    children: ReactNode;
}) {
    const id = `cr-categorie-${code}`;
    return (
        <section
            className={cn(
                "rounded-brand border transition-colors",
                actif
                    ? "border-primary/40 bg-white"
                    : "border-gray-200 bg-surface/60",
            )}
        >
            <div className="flex items-start gap-3 px-4 py-3">
                <Checkbox
                    id={id}
                    checked={actif}
                    onCheckedChange={(valeur) => onActif(valeur === true)}
                    className="mt-0.5"
                />
                <label htmlFor={id} className="min-w-0 flex-1 cursor-pointer">
                    <span className="flex flex-wrap items-center gap-2">
                        <Badge variant={actif ? "success" : "neutral"}>
                            {code}
                        </Badge>
                        <span className="text-sm font-medium text-gray-900">
                            {CATEGORIES[code].label}
                        </span>
                        {!actif && (
                            <span className="text-xs text-muted">
                                · désactivée
                            </span>
                        )}
                    </span>
                    <span className="mt-1 block text-xs text-muted">
                        {CATEGORIES[code].condition} Mérite :{" "}
                        {CATEGORIES[code].merite}.
                    </span>
                </label>
            </div>
            {actif && <div className="border-t px-4 py-3">{children}</div>}
        </section>
    );
}

function Choix<T extends string>({
    id,
    value,
    onChange,
    options,
}: {
    id: string;
    value: T;
    onChange: (valeur: T) => void;
    options: Record<T, string>;
}) {
    return (
        <Select value={value} onValueChange={(valeur) => onChange(valeur as T)}>
            <SelectTrigger id={id}>
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {(Object.keys(options) as T[]).map((cle) => (
                    <SelectItem key={cle} value={cle}>
                        {options[cle]}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function TableTranches({ basse, haute }: { basse: number; haute: number }) {
    return (
        <div className="overflow-x-auto rounded-brand border">
            <table className="w-full text-sm">
                <thead className="bg-surface/60 text-left text-xs text-muted">
                    <tr>
                        <th className="px-3 py-2 font-medium">Tranche</th>
                        <th className="px-3 py-2 font-medium">
                            Distance entre la ville du bac et Bondoukou
                        </th>
                        <th className="px-3 py-2 font-medium">Ordre</th>
                    </tr>
                </thead>
                <tbody>
                    {tranchesEloignement([basse, haute]).map((tranche) => (
                        <tr key={tranche.code} className="border-t">
                            <td className="px-3 py-2 tabular-nums">
                                {tranche.code}
                            </td>
                            <td className="px-3 py-2">{tranche.libelle}</td>
                            <td className="px-3 py-2">{tranche.ordre}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function Titre({ children, aide }: { children: ReactNode; aide?: ReactNode }) {
    return (
        <div className="space-y-0.5">
            <h3 className="text-sm font-medium text-gray-900">{children}</h3>
            {aide && <p className="text-xs text-muted">{aide}</p>}
        </div>
    );
}

function Unite({ children }: { children: ReactNode }) {
    return (
        <span className="pointer-events-none px-3 text-xs text-muted">
            {children}
        </span>
    );
}

function BoutonOrdre({
    label,
    disabled,
    onClick,
    children,
}: {
    label: string;
    disabled: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            aria-label={label}
            disabled={disabled}
            onClick={onClick}
            className="inline-flex size-8 cursor-pointer items-center justify-center rounded-brand text-muted transition-colors hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-40"
        >
            {children}
        </button>
    );
}
