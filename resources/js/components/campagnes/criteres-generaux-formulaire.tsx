import { zodResolver } from "@hookform/resolvers/zod";
import { Plus, ShieldCheck, Trash2 } from "lucide-react";
import { useMemo, useState, type ReactNode } from "react";
import {
    Controller,
    useFieldArray,
    useForm,
    type Control,
    type Path,
} from "react-hook-form";
import {
    lignesCredits,
    type PageGeneral,
} from "@/components/campagnes/criteres";
import { CartesAges } from "@/components/campagnes/cartes-ages";
import {
    ApplicationCriteres,
    SOURCES,
    Titre,
    Unite,
} from "@/components/campagnes/criteres-formulaire";
import {
    ErreurGenerale,
    Note,
    PiedAssistant,
    Recap,
} from "@/components/parametrage-hebergement/shared";
import { Checkbox } from "@/components/ui/checkbox";
import { Combobox } from "@/components/ui/combobox";
import { Field } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Stepper } from "@/components/ui/stepper";
import { envoyer } from "@/lib/inertia-form";
import { cn } from "@/lib/utils";
import {
    generalAEnvoyer,
    generalSchema,
    reglesCroiseesGenerales,
    valeursGenerales,
    type GeneralFormInput,
    type GeneralFormOutput,
} from "@/schemas/criteres-campagne";

type Etape = { label: string; champs: Path<GeneralFormInput>[] };

const ETAPES: Etape[] = [
    { label: "Crédits par filière", champs: ["credits"] },
    {
        label: "Rangs et quotas",
        champs: ["rang_filiere", "clubs", "cas_sociaux", "comite"],
    },
    { label: "Âge maximum", champs: ["age_max"] },
    { label: "Vérification", champs: [] },
];

/**
 * Paramétrage général de la campagne, préalable aux critères d'admission et de réadmission : pourcentage de crédits
 * par filière (et écoles sur concours), rang par filière, rang des clubs et associations, cas sociaux, comité des
 * résidents et âge maximum par niveau d'études.
 */
export function CriteresGenerauxFormulaire(props: PageGeneral) {
    const { campagne, etat, source } = props;
    return (
        <Assistant
            key={`${campagne.id}-general-${source}-${etat.version ?? 0}-${etat.brouillon}`}
            {...props}
        />
    );
}

function Assistant({
    campagne,
    droits,
    regles,
    source,
    filieres,
    niveauxEtude,
    urls,
}: PageGeneral) {
    const [erreurGenerale, setErreurGenerale] = useState<string | null>(null);
    const [etape, setEtape] = useState(0);
    const valeursDepart = useMemo(
        () => valeursGenerales(regles, niveauxEtude, droits.configurerRegles),
        [regles, niveauxEtude, droits.configurerRegles],
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
    } = useForm<GeneralFormInput, unknown, GeneralFormOutput>({
        resolver: zodResolver(generalSchema),
        mode: "onTouched",
        defaultValues: valeursDepart,
    });
    const exceptions = useFieldArray({ control, name: "credits.filieres" });
    const editable = !!urls.submit && droits.configurerRegles;
    const couvre = (e: Etape, cle: string) =>
        e.champs.some(
            (prefixe) => cle === prefixe || cle.startsWith(`${prefixe}.`),
        );

    const validerEtape = async (index: number) => {
        if (!editable) return true;
        const e = ETAPES[index];
        let ok = await trigger(e.champs, { shouldFocus: true });
        Object.entries(reglesCroiseesGenerales(getValues())).forEach(
            ([cle, message]) => {
                if (couvre(e, cle)) {
                    setError(cle as Path<GeneralFormInput>, {
                        type: "croisee",
                        message,
                    });
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
                return;
            }
        }
        setEtape(cible);
    };

    const repartirErreurs = (erreurs: Record<string, string>) => {
        let premiere = ETAPES.length;
        const autres: string[] = [];
        Object.entries(erreurs).forEach(([cle, message]) => {
            const champ = cle.replace(/^regles\./, "");
            const index = ETAPES.findIndex((e) => couvre(e, champ));
            if (index >= 0) {
                setError(champ as Path<GeneralFormInput>, {
                    type: "server",
                    message,
                });
                premiere = Math.min(premiere, index);
            } else {
                autres.push(message);
            }
        });
        setErreurGenerale(autres[0] ?? null);
        if (premiere < ETAPES.length) setEtape(premiere);
    };

    const enregistrer = async (valeurs: GeneralFormOutput) => {
        if (!urls.submit) return;
        await envoyer<GeneralFormInput>(
            urls.submit,
            {
                regles: generalAEnvoyer(valeurs),
                appliquer: valeurs.appliquer,
            } as never,
            { method: "put", onError: repartirErreurs },
        );
    };

    const valeurs = watch();
    const derniere = etape === ETAPES.length - 1;
    const erreur = (chemin: string) =>
        chemin
            .split(".")
            .reduce<unknown>(
                (objet, cle) =>
                    (objet as Record<string, unknown> | undefined)?.[cle],
                errors,
            ) as { message?: string } | undefined;
    // Libellé seul : le code de la filière n'est pas affiché sous son nom.
    const optionsFilieres = useMemo(
        () => filieres.map(({ value, label }) => ({ value, label })),
        [filieres],
    );
    const nomNiveau = (code: string) =>
        niveauxEtude.find((n) => n.value === code)?.label ?? code;

    const champNombre = (
        chemin: Path<GeneralFormInput>,
        label: string,
        unite: string,
        options: { aide?: string; requis?: boolean; placeholder?: string } = {},
    ) => {
        const id = `cr-general-${chemin.replace(/\./g, "-")}`;
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
                    className={unite ? "pr-20" : undefined}
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
                for (let index = 0; index < ETAPES.length - 1; index++) {
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
                steps={ETAPES.map((e) => e.label)}
                current={etape}
                reached={ETAPES.length - 1}
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
                        Campagne {campagne.verrou ?? "terminée"} : son
                        paramétrage est figé et s’affiche en lecture seule.
                    </Note>
                )}
                {campagne.modifiable && !editable && (
                    <Note icone={<ShieldCheck />} ton="attention">
                        Le paramétrage général est défini par la S/DAL : vous le
                        consultez en lecture seule.
                    </Note>
                )}

                <fieldset
                    disabled={!editable || isSubmitting}
                    className="min-w-0 space-y-6"
                >
                    {etape === 0 && (
                        <>
                            <div className="md:max-w-xs">
                                {champNombre(
                                    "credits.seuil_defaut",
                                    "Pourcentage de crédits par filière",
                                    "%",
                                    {
                                        requis: true,
                                        aide: "100 % = 60 crédits sur 60.",
                                    },
                                )}
                            </div>
                            {exceptions.fields.length > 0 && (
                                <ul className="space-y-3">
                                    {exceptions.fields.map((ligne, index) => {
                                        const idFiliere = `cr-general-filiere-${index}`;
                                        const messageFiliere = erreur(
                                            `credits.filieres.${index}.filiere_id`,
                                        )?.message;
                                        return (
                                            <li
                                                key={ligne.id}
                                                className="grid grid-cols-1 items-start gap-x-4 gap-y-3 rounded-brand border p-3 md:grid-cols-[minmax(0,1fr)_160px_auto]"
                                            >
                                                <Field
                                                    id={idFiliere}
                                                    label="Filière"
                                                    required
                                                    error={messageFiliere}
                                                >
                                                    <Controller
                                                        control={control}
                                                        name={`credits.filieres.${index}.filiere_id`}
                                                        render={({ field }) => (
                                                            <Combobox
                                                                id={idFiliere}
                                                                value={
                                                                    field.value
                                                                }
                                                                onChange={
                                                                    field.onChange
                                                                }
                                                                onBlur={
                                                                    field.onBlur
                                                                }
                                                                options={
                                                                    optionsFilieres
                                                                }
                                                                placeholder="Choisir une filière…"
                                                                searchPlaceholder="Rechercher une filière…"
                                                                emptyMessage="Aucune filière trouvée."
                                                                invalid={
                                                                    !!messageFiliere
                                                                }
                                                            />
                                                        )}
                                                    />
                                                </Field>
                                                {champNombre(
                                                    `credits.filieres.${index}.seuil`,
                                                    "Crédits exigés",
                                                    "%",
                                                    { requis: true },
                                                )}
                                                <button
                                                    type="button"
                                                    aria-label="Retirer cette filière"
                                                    onClick={() =>
                                                        exceptions.remove(index)
                                                    }
                                                    className="inline-flex size-10 cursor-pointer items-center justify-center self-end rounded-brand text-red-600 transition-colors hover:bg-red-50"
                                                >
                                                    <Trash2
                                                        className="size-4"
                                                        aria-hidden
                                                    />
                                                </button>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                            <button
                                type="button"
                                onClick={() =>
                                    exceptions.append({
                                        filiere_id: "",
                                        seuil: "80",
                                        concours: true,
                                    })
                                }
                                className="inline-flex h-10 cursor-pointer items-center gap-2 rounded-brand border border-dashed px-4 text-sm text-primary transition-colors hover:bg-soft"
                            >
                                <Plus className="size-4" aria-hidden /> Ajouter
                                un seuil propre à une filière
                            </button>
                        </>
                    )}

                    {etape === 1 && (
                        <>
                            <Titre aide="Catégories prioritaires de la réadmission. Décochez une catégorie pour la désactiver.">
                                Rangs et places réservées
                            </Titre>
                            <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <Carte
                                    code="C2"
                                    titre="Rang par filière"
                                    actif={valeurs.rang_filiere.actif}
                                    control={control}
                                    nom="rang_filiere.actif"
                                >
                                    <div>
                                        {champNombre(
                                            "rang_filiere.n",
                                            "Premiers retenus",
                                            "premiers",
                                            { requis: true },
                                        )}
                                    </div>
                                </Carte>
                                <Carte
                                    code="C3"
                                    titre="Rang des clubs et associations"
                                    actif={valeurs.clubs.actif}
                                    control={control}
                                    nom="clubs.actif"
                                >
                                    <div>
                                        {champNombre(
                                            "clubs.n",
                                            "Premiers retenus par discipline",
                                            "premiers",
                                            { requis: true },
                                        )}
                                    </div>
                                </Carte>
                                <Carte
                                    code="C4"
                                    titre="Cas sociaux"
                                    actif={valeurs.cas_sociaux.actif}
                                    control={control}
                                    nom="cas_sociaux.actif"
                                >
                                    <div>
                                        {champNombre(
                                            "cas_sociaux.taux",
                                            "Part réservée",
                                            "%",
                                            { requis: true },
                                        )}
                                    </div>
                                </Carte>
                                <Carte
                                    code="C5"
                                    titre="Comité des résidents"
                                    actif={valeurs.comite.actif}
                                    control={control}
                                    nom="comite.actif"
                                >
                                    <div>
                                        {champNombre(
                                            "comite.places",
                                            "Nombre de places",
                                            "places",
                                            { requis: true },
                                        )}
                                    </div>
                                </Carte>
                            </div>
                        </>
                    )}

                    {etape === 2 && (
                        <>
                            <Titre>
                                Âge maximum par niveau d’études
                            </Titre>
                            {valeurs.age_max.length === 0 ? (
                                <Note icone={<ShieldCheck />} ton="attention">
                                    Aucun niveau d’études n’est paramétré.
                                </Note>
                            ) : (
                                <div className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                                    {valeurs.age_max.map((ligne, index) => (
                                        <div key={ligne.niveau}>
                                            {champNombre(
                                                `age_max.${index}.age`,
                                                nomNiveau(ligne.niveau),
                                                "ans",
                                                { placeholder: "Pas de limite" },
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </>
                    )}

                    {derniere && (
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <p className="text-sm text-muted">
                                    Crédits exigés
                                </p>
                                <Recap
                                    lignes={lignesCredits(
                                        valeurs.credits,
                                        filieres,
                                    ).map(([label, valeur]) => ({
                                        label,
                                        valeur,
                                    }))}
                                />
                            </div>
                            <Recap
                                lignes={[
                                    {
                                        label: "Rang par filière",
                                        valeur: valeurs.rang_filiere.actif
                                            ? `${valeurs.rang_filiere.n} premiers`
                                            : "Désactivé",
                                    },
                                    {
                                        label: "Clubs et associations",
                                        valeur: valeurs.clubs.actif
                                            ? `${valeurs.clubs.n} premiers par discipline`
                                            : "Désactivé",
                                    },
                                    {
                                        label: "Cas sociaux",
                                        valeur: valeurs.cas_sociaux.actif
                                            ? `${valeurs.cas_sociaux.taux} %`
                                            : "Désactivé",
                                    },
                                    {
                                        label: "Comité des résidents",
                                        valeur: valeurs.comite.actif
                                            ? `${valeurs.comite.places} places`
                                            : "Désactivé",
                                    },
                                ]}
                            />
                            <div className="space-y-2">
                                <p className="text-sm text-muted">
                                    Âge maximum par niveau
                                </p>
                                <CartesAges
                                    ages={valeurs.age_max
                                        .filter((l) => l.age.trim())
                                        .map((l) => ({
                                            niveau: l.niveau,
                                            age: Number(l.age),
                                        }))}
                                    niveaux={niveauxEtude}
                                />
                            </div>
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

/** Catégorie activable (case cochée = active) ; ses paramètres s'affichent quand elle est active. */
function Carte({
    code,
    titre,
    actif,
    control,
    nom,
    children,
}: {
    code: string;
    titre: string;
    actif: boolean;
    control: Control<GeneralFormInput>;
    nom: "rang_filiere.actif" | "clubs.actif" | "cas_sociaux.actif" | "comite.actif";
    children: ReactNode;
}) {
    const id = `cr-general-${code}`;
    return (
        <section
            className={cn(
                "rounded-brand border transition-colors",
                actif ? "border-primary/40 bg-white" : "border-gray-200 bg-surface/60",
            )}
        >
            <div className="flex items-start gap-3 px-4 py-3">
                <Controller
                    control={control}
                    name={nom}
                    render={({ field }) => (
                        <Checkbox
                            id={id}
                            checked={field.value}
                            onCheckedChange={(valeur) =>
                                field.onChange(valeur === true)
                            }
                            className="mt-0.5"
                        />
                    )}
                />
                <label htmlFor={id} className="min-w-0 flex-1 cursor-pointer">
                    <span className="flex flex-wrap items-center gap-2">
                        <span className="text-sm font-medium text-gray-900">
                            {titre}
                        </span>
                        {!actif && (
                            <span className="text-xs text-muted">
                                · désactivé
                            </span>
                        )}
                    </span>
                </label>
            </div>
            {/* Catégorie désactivée : le champ reste visible, grisé et non modifiable. */}
            <fieldset
                disabled={!actif}
                aria-disabled={!actif}
                className={cn(
                    "min-w-0 border-t px-4 py-3 transition-opacity",
                    !actif && "pointer-events-none opacity-50",
                )}
            >
                {children}
            </fieldset>
        </section>
    );
}
