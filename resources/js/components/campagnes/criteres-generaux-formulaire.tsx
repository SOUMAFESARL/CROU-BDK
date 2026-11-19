import { zodResolver } from "@hookform/resolvers/zod";
import { ShieldCheck } from "lucide-react";
import { useMemo, useState, type ReactNode } from "react";
import { Controller, useForm, type Control, type Path } from "react-hook-form";
import { type PageGeneral } from "@/components/campagnes/criteres";
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
import { Field } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Stepper } from "@/components/ui/stepper";
import { envoyer } from "@/lib/inertia-form";
import { cn } from "@/lib/utils";
import {
    generalAEnvoyer,
    generalSchema,
    valeursGenerales,
    type GeneralFormInput,
    type GeneralFormOutput,
} from "@/schemas/criteres-campagne";

type Etape = {
    cle: "rangs" | "ages" | "boursiers" | "verification";
    label: string;
    champs: Path<GeneralFormInput>[];
};

const ETAPES: Etape[] = [
    {
        cle: "rangs",
        label: "Rangs et quotas",
        champs: ["rang_filiere", "clubs", "cas_sociaux", "comite"],
    },
    { cle: "ages", label: "Âge maximum", champs: ["age_max"] },
    { cle: "boursiers", label: "Boursiers", champs: ["priorite_boursiers"] },
    { cle: "verification", label: "Vérification", champs: [] },
];

/**
 * Étapes selon les volets de la campagne : les rangs et quotas (catégories C2 à C5) ne concernent que la réadmission
 * et sont sans objet pour une campagne d'admission seule (leurs valeurs enregistrées sont conservées, non affichées).
 */
const etapesDe = (readmission: boolean) =>
    readmission ? ETAPES : ETAPES.filter((e) => e.cle !== "rangs");

/**
 * Paramétrage général de la campagne, préalable aux critères d'admission et de réadmission : rang par filière, rang
 * des clubs et associations, cas sociaux, comité des résidents, âge maximum par niveau d'études et priorité aux
 * boursiers dans le classement des demandes recevables (cochée par défaut). Les crédits par filière, propres à la
 * réadmission, se règlent dans ses critères de rejet.
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
    niveauxEtude,
    urls,
}: PageGeneral) {
    const [erreurGenerale, setErreurGenerale] = useState<string | null>(null);
    const [etape, setEtape] = useState(0);
    const readmission = campagne.volets.readmission;
    const etapes = useMemo(() => etapesDe(readmission), [readmission]);
    const courante = etapes[etape];
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
        setError,
        reset,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<GeneralFormInput, unknown, GeneralFormOutput>({
        resolver: zodResolver(generalSchema),
        mode: "onTouched",
        defaultValues: valeursDepart,
    });
    const editable = !!urls.submit && droits.configurerRegles;
    const couvre = (e: Etape, cle: string) =>
        e.champs.some(
            (prefixe) => cle === prefixe || cle.startsWith(`${prefixe}.`),
        );

    const validerEtape = async (index: number) => {
        if (!editable) return true;
        return trigger(etapes[index].champs, { shouldFocus: true });
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
        let premiere = etapes.length;
        const autres: string[] = [];
        Object.entries(erreurs).forEach(([cle, message]) => {
            const champ = cle.replace(/^regles\./, "");
            const index = etapes.findIndex((e) => couvre(e, champ));
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
        if (premiere < etapes.length) setEtape(premiere);
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
    const derniere = etape === etapes.length - 1;
    const erreur = (chemin: string) =>
        chemin
            .split(".")
            .reduce<unknown>(
                (objet, cle) =>
                    (objet as Record<string, unknown> | undefined)?.[cle],
                errors,
            ) as { message?: string } | undefined;
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
                    {courante.cle === "rangs" && (
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

                    {courante.cle === "ages" && (
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

                    {courante.cle === "boursiers" && (
                        <>
                            <Controller
                                control={control}
                                name="priorite_boursiers"
                                render={({ field }) => (
                                    <label
                                        htmlFor="cr-general-priorite_boursiers"
                                        className={cn(
                                            "flex cursor-pointer items-start gap-3 rounded-brand border px-4 py-3 transition-colors md:max-w-xl",
                                            field.value
                                                ? "border-primary/40 bg-white"
                                                : "border-gray-200 bg-surface/60",
                                        )}
                                    >
                                        <Checkbox
                                            id="cr-general-priorite_boursiers"
                                            checked={field.value}
                                            onCheckedChange={(valeur) =>
                                                field.onChange(valeur === true)
                                            }
                                            className="mt-0.5"
                                        />
                                        <span className="min-w-0 flex-1">
                                            <span className="block text-sm font-medium text-gray-900">
                                                Priorité aux boursiers
                                            </span>
                                            <span className="block text-xs text-muted">
                                                Cochée : les boursiers sont
                                                classés devant les
                                                non-boursiers, avant le mérite.
                                            </span>
                                        </span>
                                    </label>
                                )}
                            />
                        </>
                    )}

                    {derniere && (
                        <div className="space-y-4">
                            {readmission && (
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
                            )}
                            <Recap
                                lignes={[
                                    {
                                        label: "Priorité aux boursiers",
                                        valeur: valeurs.priorite_boursiers
                                            ? "Oui"
                                            : "Non",
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
