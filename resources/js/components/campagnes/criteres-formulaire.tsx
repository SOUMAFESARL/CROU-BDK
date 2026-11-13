import { zodResolver } from "@hookform/resolvers/zod";
import { router } from "@inertiajs/react";
import {
    ArrowDown,
    ArrowUp,
    CheckCheck,
    GraduationCap,
    ShieldCheck,
    Trash2,
    TriangleAlert,
} from "lucide-react";
import { useMemo, useState, type ReactNode } from "react";
import {
    Controller,
    useForm,
    type Control,
    type Path,
    type UseFormRegister,
} from "react-hook-form";
import { AppliquerCriteresDialog } from "@/components/campagnes/appliquer-criteres-dialog";
import {
    CATEGORIES,
    CODES_CATEGORIES,
    DEPARTAGE,
    JEUX,
    MERITE_ADMISSION,
    placesCasSociaux,
    type CampagneCriteres,
    type GeneralApplique,
    type Jeu,
    type MeriteAdmission,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Stepper } from "@/components/ui/stepper";
import { envoyer } from "@/lib/inertia-form";
import { cn } from "@/lib/utils";
import {
    criteresSchema,
    placesAEnvoyer,
    reglesAEnvoyer,
    reglesCroisees,
    reservationsSaisies,
    valeursInitiales,
    type CriteresFormInput,
    type CriteresFormOutput,
} from "@/schemas/criteres-campagne";

type Bloc = "places" | "regles" | "final";
type CleEtape = "places" | "rejet" | "classement" | "verification";
type Etape = {
    cle: CleEtape;
    label: string;
    bloc: Bloc;
    champs: Path<CriteresFormInput>[];
};

/** Message d'introduction selon le point de départ (aucun quand on part des critères en vigueur). */
export const SOURCES: Record<PageCriteres["source"], string | null> = {
    brouillon: "Vous reprenez le brouillon enregistré.",
    en_vigueur: null,
    modele: null,
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
            cle: "rejet",
            label: "1. Critères de rejet",
            bloc: "regles",
            champs:
                volet === "admission"
                    ? ["recevabilite", "admission.merite", "admission.seuil_actif", "admission.seuil", "admission.age_actif"]
                    : ["recevabilite", "readmission.loyers", "readmission.credits_actif", "readmission.conduite_actif", "readmission.conduite_note_min", "readmission.age_actif"],
        });
        liste.push({
            cle: "classement",
            label: "2. Critères de classement",
            bloc: "regles",
            champs:
                volet === "admission"
                    ? ["admission.ville_bac", "classement", "calendrier"]
                    : ["classement", "calendrier"],
        });
    }
    liste.push({
        cle: "verification",
        label: "Vérification",
        bloc: "final",
        champs: [],
    });
    return liste;
}

/**
 * Définition des critères d'un volet (admission ou réadmission) d'une campagne, en deux niveaux : critères de rejet
 * (un candidat qui n'en remplit pas un n'est pas classé), puis critères de classement de la liste provisoire.
 * Les crédits par filière, les rangs, les quotas et l'âge maximum par niveau viennent du paramétrage général.
 * L'enregistrement crée un brouillon, appliqué ou non.
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
    general,
    filieres,
    niveauxEtude,
    noteConduite,
    urls,
}: PageCriteres) {
    const [erreurGenerale, setErreurGenerale] = useState<string | null>(null);
    const etapes = useMemo(() => etapesDuVolet(volet, droits), [volet, droits]);
    const g = general?.regles ?? null;

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
        general: g,
        noteConduite,
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

    /** Erreurs Laravel (`regles.readmission.loyers.mois_exiges`, `places.0.places`) replacées sous les champs, retour à l'étape concernée. */
    const repartirErreurs = (erreurs: Record<string, string>) => {
        let premiere = etapes.length;
        const autres: string[] = [];
        Object.entries(erreurs).forEach(([cle, message]) => {
            const champ = cle
                .replace(/^regles\./, "")
                .replace(/^readmission\.conduite\.(actif|note_min)$/, "readmission.conduite_$1")
                .replace(/^places$/, "places.0.places")
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
        const donnees: Record<string, unknown> = {
            appliquer: valeurs.appliquer,
        };
        if (droits.configurerRegles)
            donnees.regles = reglesAEnvoyer(volet, valeurs);
        if (droits.configurerPlaces) donnees.places = placesAEnvoyer(valeurs);
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
    const merite: MeriteAdmission = admission?.merite ?? "moyenne_bac_desc";

    /** Champ numérique avec unité, enregistré dans le formulaire. */
    const champNombre = (
        chemin: Path<CriteresFormInput>,
        label: string,
        unite: string,
        options: {
            aide?: string;
            requis?: boolean;
            placeholder?: string;
            decimal?: boolean;
        } = {},
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
                    inputMode={options.decimal ? "decimal" : "numeric"}
                    placeholder={options.placeholder}
                    className={unite ? "pr-20" : undefined}
                    trailing={unite ? <Unite>{unite}</Unite> : undefined}
                    invalid={!!erreur(chemin)}
                    {...register(chemin)}
                />
            </Field>
        );
    };

    /** Interrupteur d'un critère (case cochée = actif). */
    const interrupteur = (
        nom: Path<CriteresFormInput>,
        label: string,
        description?: string,
    ) => (
        <Controller
            control={control}
            name={nom}
            render={({ field }) => (
                <ToggleCard
                    checked={!!field.value}
                    onChange={field.onChange}
                    label={label}
                    description={description}
                />
            )}
        />
    );

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
                        Campagne {campagne.verrou ?? "terminée"} : ses critères
                        sont figés et s’affichent en lecture seule.
                    </Note>
                )}
                {campagne.modifiable && !editable(courante.bloc) && (
                    <Note icone={<ShieldCheck />} ton="attention">
                        {courante.bloc === "places"
                            ? "Les places sont saisies par le comité de direction : vous les consultez en lecture seule."
                            : "Les critères sont paramétrés par la S/DAL : vous les consultez en lecture seule."}
                    </Note>
                )}
                {general && general.etat !== "en_vigueur" && courante.bloc === "regles" && (
                    <Note icone={<TriangleAlert />} ton="attention">
                        Le paramétrage général n’est pas encore appliqué : les
                        valeurs rappelées ci-dessous sont celles{" "}
                        {general.etat === "brouillon" ? "du brouillon" : "du modèle CROU"}.
                        Appliquez-le avant ces critères.
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
                            general={general}
                            register={register}
                            erreur={erreur}
                        />
                    )}

                    {courante.cle === "rejet" && readmission && (
                        <>
                            <Titre aide="Un candidat qui ne remplit pas l’un de ces critères est rejeté : il n’est pas classé. Décochez un critère pour le désactiver.">
                                Critères de rejet de la réadmission
                            </Titre>
                            {interrupteur(
                                "readmission.loyers.actif",
                                "Être à jour du loyer de l’année",
                            )}
                            {readmission.loyers.actif && (
                                <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                                    {champNombre(
                                        "readmission.loyers.mois_exiges",
                                        "Mois de loyers exigés",
                                        "mois",
                                        { requis: true },
                                    )}
                                </div>
                            )}
                            {interrupteur(
                                "readmission.credits_actif",
                                "Pourcentage de crédits validés",
                                "Pourcentages définis dans le paramétrage général.",
                            )}
                            {interrupteur(
                                "readmission.conduite_actif",
                                "Conduite en cité",
                                "Note de conduite calculée à partir du paramétrage des sanctions.",
                            )}
                            {readmission.conduite_actif && (
                                <div className="md:max-w-xs">
                                    {champNombre(
                                        "readmission.conduite_note_min",
                                        "Note de conduite minimale",
                                        noteConduite !== null ? `/ ${String(noteConduite).replace(".", ",")} pts` : "pts",
                                        {
                                            requis: true,
                                            decimal: true,
                                            placeholder: "Ex. 10",
                                            aide: "En dessous de cette note, la demande est rejetée.",
                                        },
                                    )}
                                </div>
                            )}
                            {interrupteur(
                                "readmission.age_actif",
                                "Âge maximum par niveau",
                                "Âges définis dans le paramétrage général.",
                            )}
                            {interrupteur(
                                "recevabilite.internationaux",
                                "Ouvert aux étudiants internationaux",
                            )}
                        </>
                    )}

                    {courante.cle === "rejet" && admission && (
                        <>
                            <Titre aide="Un candidat qui ne remplit pas l’un de ces critères est rejeté : il n’est pas classé.">
                                Critères de rejet de l’admission
                            </Titre>
                            <Field
                                id={`cr-${volet}-merite`}
                                label="Critère de mérite"
                                hint="Sert au seuil de rejet et au classement."
                            >
                                <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    {(
                                        Object.keys(
                                            MERITE_ADMISSION,
                                        ) as MeriteAdmission[]
                                    ).map((valeur) => (
                                        <ChoiceCard
                                            key={valeur}
                                            name="merite_admission"
                                            value={valeur}
                                            selected={merite === valeur}
                                            onSelect={() =>
                                                setValue(
                                                    "admission.merite",
                                                    valeur,
                                                    { shouldDirty: true },
                                                )
                                            }
                                            title={MERITE_ADMISSION[valeur].label}
                                            description={
                                                valeur === "moyenne_bac_desc"
                                                    ? "Note obtenue au baccalauréat, sur 20."
                                                    : "Crédits validés à l’université."
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
                            </Field>
                            {interrupteur(
                                "admission.seuil_actif",
                                `Exiger un minimum (${MERITE_ADMISSION[merite].label.toLowerCase()})`,
                                "Sinon : « Non recevable – seuil minimum non atteint ».",
                            )}
                            {admission.seuil_actif && (
                                <div className="md:max-w-xs">
                                    {champNombre(
                                        "admission.seuil",
                                        MERITE_ADMISSION[merite].seuil,
                                        MERITE_ADMISSION[merite].unite,
                                        { requis: true, decimal: true },
                                    )}
                                </div>
                            )}
                            {interrupteur(
                                "admission.age_actif",
                                "Âge maximum par niveau",
                                "Âges définis dans le paramétrage général.",
                            )}
                            {interrupteur(
                                "recevabilite.internationaux",
                                "Ouvert aux étudiants internationaux",
                                "Décoché : « Campagne non ouverte aux étudiants internationaux » (A-04).",
                            )}
                        </>
                    )}

                    {courante.cle === "classement" && readmission && (
                        <>
                            <Titre>
                                Catégories prioritaires
                            </Titre>
                            <CategoriesAppliquees general={general} />
                            {interrupteur(
                                "classement.priorite_boursiers",
                                "Priorité aux boursiers",
                            )}
                            <OrdreDepartage
                                volet={volet}
                                control={control}
                                erreur={erreur("classement.departage")?.message}
                            />
                        </>
                    )}

                    {courante.cle === "classement" && admission && (
                        <>
                            <Titre aide="Clé de classement des recevables : mérite, puis départage dans l’ordre ci-dessous.">
                                Critères de classement de l’admission
                            </Titre>
                            <Recap
                                lignes={[
                                    {
                                        label: "1. Mérite",
                                        valeur: `${MERITE_ADMISSION[merite].label} (décroissant)`,
                                    },
                                ]}
                            />
                            <div className="md:max-w-xs">
                                <Field
                                    id={`cr-${volet}-ville_bac`}
                                    label="Ville de référence du baccalauréat"
                                    required
                                    error={erreur("admission.ville_bac")?.message}
                                    hint="Un bac obtenu hors de cette ville est prioritaire."
                                >
                                    <Input
                                        id={`cr-${volet}-ville_bac`}
                                        maxLength={100}
                                        invalid={!!erreur("admission.ville_bac")}
                                        {...register("admission.ville_bac")}
                                    />
                                </Field>
                            </div>
                            <OrdreDepartage
                                volet={volet}
                                control={control}
                                erreur={erreur("classement.departage")?.message}
                                aide="Appliqué à mérite égal, dans cet ordre ; décochez un critère pour le désactiver. Une égalité persistante est arbitrée par la S/DAL."
                            />
                        </>
                    )}

                    {courante.cle === "classement" && (
                        <>
                            <Titre>
                                Liste provisoire
                            </Titre>
                            <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-3">
                                {(
                                    [
                                        [
                                            "date_liste_provisoire",
                                            "Génération de la liste provisoire",
                                            "Défaut : à la clôture des dépôts.",
                                        ],
                                    ] as const
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
                                {champNombre(
                                    "classement.k_liste_elargie",
                                    "Coefficient de la liste élargie",
                                    "×",
                                    {
                                        requis: true,
                                        aide: "2 = 10 noms proposés pour 5 places.",
                                    },
                                )}
                                {/* « Délai de complétude » masqué pour l'instant : la valeur enregistrée (7 jours par défaut) est conservée telle quelle. */}
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
                                            valeur: `${ligne.places || "—"} places ${JEUX[volet].complement}`,
                                        };
                                    })}
                                />
                            )}
                            {droits.voirRegles && (
                                <Recap
                                    lignes={[
                                        ...(readmission
                                            ? [
                                                  {
                                                      label: "Rejet",
                                                      valeur: [
                                                          readmission.loyers.actif &&
                                                              `${readmission.loyers.mois_exiges} mois de loyer soldés`,
                                                          readmission.credits_actif &&
                                                              "crédits validés",
                                                          readmission.conduite_actif &&
                                                              `conduite ≥ ${readmission.conduite_note_min || "—"} pts`,
                                                          readmission.age_actif &&
                                                              "âge maximum",
                                                      ]
                                                          .filter(Boolean)
                                                          .join(" · ") ||
                                                          "Aucun critère",
                                                  },
                                                  {
                                                      label: "Priorité boursiers",
                                                      valeur: valeurs.classement
                                                          .priorite_boursiers
                                                          ? "Oui"
                                                          : "Non",
                                                  },
                                              ]
                                            : []),
                                        ...(admission
                                            ? [
                                                  {
                                                      label: "Rejet",
                                                      valeur: [
                                                          admission.seuil_actif &&
                                                              `${MERITE_ADMISSION[merite].seuil.toLowerCase()} : ${admission.seuil} ${MERITE_ADMISSION[merite].unite}`,
                                                          admission.age_actif &&
                                                              "âge maximum",
                                                      ]
                                                          .filter(Boolean)
                                                          .join(" · ") ||
                                                          "Aucun critère",
                                                  },
                                                  {
                                                      label: "Mérite",
                                                      valeur: MERITE_ADMISSION[
                                                          merite
                                                      ].label,
                                                  },
                                                  {
                                                      label: "Ville du bac",
                                                      valeur: `Hors ${admission.ville_bac} prioritaire`,
                                                  },
                                              ]
                                            : []),
                                        {
                                            label: "Internationaux",
                                            valeur: valeurs.recevabilite
                                                .internationaux
                                                ? "Acceptés"
                                                : "Non acceptés",
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

                            <ApplicationCriteres
                                configurer={droits.configurerRegles}
                                appliquer={valeurs.appliquer}
                                onAppliquer={(valeur) =>
                                    setValue("appliquer", valeur, {
                                        shouldDirty: true,
                                    })
                                }
                            />
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

/** Dernière étape : appliquer dès l'enregistrement. */
export function ApplicationCriteres({
    configurer,
    appliquer,
    onAppliquer,
}: {
    configurer: boolean;
    appliquer: boolean;
    onAppliquer: (valeur: boolean) => void;
}) {
    if (!configurer) {
        return (
            <Note icone={<ShieldCheck />}>
                Vos modifications seront enregistrées en brouillon ; la S/DAL
                les appliquera.
            </Note>
        );
    }
    return (
        <ToggleCard
            checked={appliquer}
            onChange={onAppliquer}
            label="Appliquer les critères dès l’enregistrement"
            description="Décochée : les critères restent en brouillon, à appliquer plus tard."
        />
    );
}

/** Catégories de réadmission déduites du paramétrage général (lecture seule). */
function CategoriesAppliquees({ general }: { general: GeneralApplique | null }) {
    if (!general) return null;
    return (
        <ol className="divide-y rounded-brand border">
            {CODES_CATEGORIES.map((code) => {
                const actif = general.categories[code].actif;
                return (
                    <li
                        key={code}
                        className={cn(
                            "flex items-start gap-3 px-4 py-2.5",
                            !actif && "opacity-60",
                        )}
                    >
                        <p className="min-w-0 flex-1 text-sm text-gray-900">
                            {CATEGORIES[code].label}
                            {!actif && (
                                <span className="text-muted">
                                    {" "}
                                    · désactivée
                                </span>
                            )}
                        </p>
                    </li>
                );
            })}
        </ol>
    );
}

/** Ordre de départage du volet : critères cochables et réordonnables (au moins un actif). */
function OrdreDepartage({
    volet,
    control,
    erreur,
    aide,
}: {
    volet: Volet;
    control: Control<CriteresFormInput>;
    erreur?: string;
    aide?: string;
}) {
    return (
        <>
            <Titre aide={aide}>Ordre de départage</Titre>
            <Controller
                control={control}
                name="classement.departage"
                render={({ field }) => (
                    <ol
                        className="space-y-2"
                        aria-describedby={
                            erreur ? `cr-${volet}-departage-error` : undefined
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
                                        item.actif ? "bg-white" : "bg-surface/60",
                                    )}
                                >
                                    <Checkbox
                                        id={id}
                                        checked={item.actif}
                                        onCheckedChange={(coche) =>
                                            field.onChange(
                                                field.value.map((ligne, i) =>
                                                    i === index
                                                        ? {
                                                              ...ligne,
                                                              actif:
                                                                  coche === true,
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
                                            !item.actif && "opacity-60",
                                        )}
                                    >
                                        <span className="block text-sm text-gray-900">
                                            {index + 1}.{" "}
                                            {DEPARTAGE[item.critere].label}
                                            {!item.actif && (
                                                <span className="text-muted">
                                                    {" "}
                                                    · désactivé
                                                </span>
                                            )}
                                        </span>
                                        <span className="block text-xs text-muted">
                                            {DEPARTAGE[item.critere].description}
                                        </span>
                                    </label>
                                    <BoutonOrdre
                                        label={`Monter : ${DEPARTAGE[item.critere].label}`}
                                        disabled={index === 0}
                                        onClick={() => deplacer(index - 1)}
                                    >
                                        <ArrowUp className="size-4" aria-hidden />
                                    </BoutonOrdre>
                                    <BoutonOrdre
                                        label={`Descendre : ${DEPARTAGE[item.critere].label}`}
                                        disabled={
                                            index === field.value.length - 1
                                        }
                                        onClick={() => deplacer(index + 1)}
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
            {erreur && (
                <p
                    id={`cr-${volet}-departage-error`}
                    role="alert"
                    className="text-xs font-medium text-red-600"
                >
                    {erreur}
                </p>
            )}
        </>
    );
}

/** Encart de la page : état d'un jeu de critères, application et abandon du brouillon. */
export function EtatCriteresVolet({
    volet,
    campagne,
    etat,
    urls,
}: {
    volet: Jeu;
    campagne: CampagneCriteres;
    etat: PageCriteres["etat"];
    urls: PageCriteres["urls"];
}) {
    const [appliquer, setAppliquer] = useState(false);
    const [abandon, setAbandon] = useState(false);
    const [traitement, setTraitement] = useState(false);

    if (!urls.appliquer && !urls.abandonner) {
        return null;
    }

    return (
        <aside className="h-fit space-y-3 rounded-brand border bg-white p-4">
            {(urls.appliquer || urls.abandonner) && (
                <div className="flex flex-col gap-2">
                    {urls.appliquer && (
                        <button
                            type="button"
                            onClick={() => setAppliquer(true)}
                            className={cn(buttonVariants({ size: "sm" }), "h-10")}
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
    general: GeneralApplique | null;
    register: UseFormRegister<CriteresFormInput>;
    erreur: (chemin: string) => { message?: string } | undefined;
};

/**
 * Étape E0 : places du volet par cité, avec les contrôles de capacité en direct ; en réadmission, places réservées
 * aux cas sociaux (calculées par cité) et au comité des résidents (paramétrage général).
 */
function EtapePlaces({
    volet,
    valeurs,
    places,
    general,
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
    const g = general?.regles ?? null;
    const autreVolet = volet === "admission" ? "readmission" : "admission";
    const reservations =
        volet === "readmission"
            ? reservationsSaisies(
                  valeurs,
                  g,
                  Object.fromEntries(
                      places.map((p) => [p.cite_id, { capacite: p.capacite }]),
                  ),
              )
            : null;

    return (
        <>
            <Titre>Répartition des places</Titre>
            <div className="space-y-4">
                {places.map((cite, index) => {
                    const ligne = valeurs.places[index];
                    const propres =
                        ligne.places === "" ? null : Number(ligne.places);
                    const casSociaux =
                        volet === "readmission" && g
                            ? placesCasSociaux(g.cas_sociaux, {
                                  capacite: cite.capacite,
                                  places: propres,
                              })
                            : null;
                    const restant =
                        cite.capacite -
                        (propres ?? 0) -
                        (cite.autre_volet ?? 0);
                    const id = `cr-${volet}-places-${index}-places`;
                    const message = erreur(`places.${index}.places`)?.message;

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
                                    <Field
                                        id={id}
                                        label={`Places ${JEUX[volet].complement}`}
                                        error={message}
                                        hint={
                                            cite.autre_volet !== null &&
                                            cite.autre_volet !== undefined
                                                ? `${cite.autre_volet} places déjà réservées ${JEUX[autreVolet].complement}.`
                                                : undefined
                                        }
                                    >
                                        <Input
                                            id={id}
                                            inputMode="numeric"
                                            placeholder="À saisir"
                                            className="pr-20"
                                            trailing={<Unite>places</Unite>}
                                            invalid={!!message}
                                            {...register(
                                                `places.${index}.places`,
                                            )}
                                        />
                                    </Field>
                                </div>
                                <p
                                    className="flex flex-wrap gap-x-6 gap-y-1 rounded-brand bg-surface/60 px-3 py-2 text-xs text-gray-700"
                                    aria-live="polite"
                                >
                                    {casSociaux !== null && g && (
                                        <span>
                                            Cas sociaux :{" "}
                                            <strong className="tabular-nums">
                                                {casSociaux}
                                            </strong>{" "}
                                            ({g.cas_sociaux.taux} %)
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
            {reservations && g && (
                <p
                    className="flex flex-wrap gap-x-6 gap-y-1 rounded-brand border px-4 py-3 text-sm text-gray-700"
                    aria-live="polite"
                >
                    <span>
                        Places de réadmission :{" "}
                        <strong className="tabular-nums">
                            {reservations.total}
                        </strong>
                    </span>
                    <span>
                        Cas sociaux :{" "}
                        <strong className="tabular-nums">
                            {reservations.casSociaux}
                        </strong>
                    </span>
                    <span>
                        Comité des résidents :{" "}
                        <strong className="tabular-nums">
                            {reservations.comite}
                        </strong>
                    </span>
                    <span>
                        Solde des autres catégories :{" "}
                        <strong
                            className={cn(
                                "tabular-nums",
                                reservations.casSociaux + reservations.comite >
                                    reservations.total && "text-red-600",
                            )}
                        >
                            {reservations.total -
                                reservations.casSociaux -
                                reservations.comite}
                        </strong>
                    </span>
                </p>
            )}
        </>
    );
}

export function Choix<T extends string>({
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

export function Titre({
    children,
    aide,
}: {
    children: ReactNode;
    aide?: ReactNode;
}) {
    return (
        <div className="space-y-0.5">
            <h3 className="text-sm font-medium text-gray-900">{children}</h3>
            {aide && <p className="text-xs text-muted">{aide}</p>}
        </div>
    );
}

export function Unite({ children }: { children: ReactNode }) {
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
