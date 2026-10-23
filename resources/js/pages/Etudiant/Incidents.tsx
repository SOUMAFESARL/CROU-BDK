import { zodResolver } from "@hookform/resolvers/zod";
import {
    Building2,
    ChevronLeft,
    ChevronRight,
    Loader2,
    Lock,
    MapPin,
    Send,
    ShieldAlert,
    ShieldCheck,
    Siren,
    TriangleAlert,
} from "lucide-react";
import { useMemo, useState } from "react";
import { Controller, useForm, useWatch } from "react-hook-form";
import { toast } from "sonner";
import {
    Notice,
    PaginationSimple,
    Panneau,
    Vide,
    type PaginationSimpleProps,
} from "@/components/etudiant/panneau";
import { ICON } from "@/components/layout/app-sidebar";
import { EtudiantLayout } from "@/components/layout/etudiant-layout";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Field } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { StatCard } from "@/components/ui/stat-card";
import { Stepper } from "@/components/ui/stepper";
import { Textarea } from "@/components/ui/textarea";
import {
    dateHeure,
    maintenantLocal,
    repartirErreurs,
} from "@/components/vie-residentielle/shared";
import { envoyer } from "@/lib/inertia-form";
import { cn } from "@/lib/utils";
import {
    CHAMPS_ETAPE,
    GRAVITES,
    incidentSchema,
    MAX_DESCRIPTION,
    MAX_TEMOINS,
    type Incident,
} from "@/schemas/incident";

type Ton = "success" | "warning" | "danger" | "neutral" | "info";

type Signalement = {
    id: string;
    numero: string;
    nature: string;
    lieu: string | null;
    date: string | null;
    gravite: { cle: string; libelle: string };
    statut: { cle: string; libelle: string; ton: Ton };
    confidentiel: boolean;
    transmisLe: string | null;
};

type IncidentsProps = {
    statutDisciplinaire: { cle: string; libelle: string } | null;
    residence: { cite: string | null; batiment: string | null; chambre: string | null } | null;
    natures: { code: string; libelle: string }[];
    compteurs: { total: number; enCours: number; traites: number; retournes: number };
    signalements: Signalement[];
    pagination: PaginationSimpleProps;
    urls: { enregistrer: string };
};

const ETAPES = ["Incident", "Faits", "Vérification"];

const vide = (): Incident => ({
    nature: "",
    date_incident: maintenantLocal(),
    lieu: "",
    gravite: "moyenne",
    description: "",
    temoins: "",
    confidentiel: false,
    sincerite: false,
});

/** Page « Signaler un incident » : déclaration d'un incident disciplinaire au Service Sécurité et suivi des signalements de l'étudiant. */
export default function Incidents({
    statutDisciplinaire,
    residence,
    natures,
    compteurs,
    signalements,
    pagination,
    urls,
}: IncidentsProps) {
    return (
        <EtudiantLayout title="Signaler un incident">
            <div className="flex flex-col gap-5">
                <header className="min-w-0">
                    <h1 className="page-title text-xl font-medium text-gray-900">
                        Signaler un incident
                    </h1>
                    <p className="mt-2 text-sm text-muted">
                        Déclarez au Service Sécurité un incident survenu dans la résidence et suivez son traitement.
                    </p>
                </header>

                {statutDisciplinaire && (
                    <div
                        role="status"
                        className="flex items-start gap-2.5 rounded-brand border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                    >
                        <ShieldAlert {...ICON} className="mt-0.5 shrink-0" />
                        <p>
                            Votre statut disciplinaire : <strong className="font-medium">{statutDisciplinaire.libelle}</strong>.
                            {statutDisciplinaire.cle === "expulsion" &&
                                " Contactez le service Logement pour organiser l’état des lieux de sortie."}
                        </p>
                    </div>
                )}

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <StatCard label="Mes signalements" value={compteurs.total} tone="blue" />
                    <StatCard label="En cours de traitement" value={compteurs.enCours} tone="amber" />
                    <StatCard label="Traités" value={compteurs.traites} tone="green" />
                    <StatCard
                        label="Retournés"
                        value={compteurs.retournes}
                        tone={compteurs.retournes > 0 ? "red" : "neutral"}
                    />
                </div>

                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <Panneau surtitre="Traitement confidentiel" titre="Déclarer un incident disciplinaire">
                        {residence ? (
                            <FormulaireIncident natures={natures} url={urls.enregistrer} />
                        ) : (
                            <Vide icone={Building2}>
                                Aucune chambre active n’est associée à votre compte : la déclaration en ligne n’est pas
                                possible. Contactez l’administration de la résidence.
                            </Vide>
                        )}
                    </Panneau>

                    <aside className="flex flex-col gap-5">
                        <div className="flex items-start gap-2.5 rounded-brand border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <Siren {...ICON} className="mt-0.5 shrink-0" />
                            <div>
                                <strong className="block font-medium">Danger immédiat ?</strong>
                                <p className="mt-1">
                                    Éloignez-vous de la zone et contactez sans délai le poste de sécurité de votre résidence.
                                </p>
                            </div>
                        </div>
                        <Panneau surtitre="Résidence associée" titre={residence?.cite ?? "Non disponible"}>
                            <p className="flex items-center gap-2 text-sm text-muted">
                                <MapPin {...ICON} className="shrink-0" />
                                {residence
                                    ? `${residence.batiment ?? "Bâtiment non renseigné"} · Chambre ${residence.chambre ?? "—"}`
                                    : "Aucune chambre active"}
                            </p>
                        </Panneau>
                    </aside>
                </div>

                <Panneau
                    surtitre="Suivi"
                    titre="Mes signalements"
                    badge={`${pagination.total} signalement${pagination.total > 1 ? "s" : ""}`}
                >
                    {signalements.length === 0 ? (
                        <Vide icone={ShieldCheck}>Aucun signalement transmis.</Vide>
                    ) : (
                        <ul className="-mx-2 divide-y">
                            {signalements.map((signalement) => (
                                <LigneSignalement key={signalement.id} signalement={signalement} />
                            ))}
                        </ul>
                    )}
                    <PaginationSimple {...pagination} />
                </Panneau>
            </div>
        </EtudiantLayout>
    );
}

function LigneSignalement({ signalement }: { signalement: Signalement }) {
    return (
        <li className="flex flex-wrap items-center gap-3 px-2 py-3">
            <span
                className={cn(
                    "grid size-10 shrink-0 place-items-center rounded-brand",
                    signalement.statut.ton === "success" ? "bg-soft text-primary" : "bg-surface text-muted",
                )}
            >
                <TriangleAlert {...ICON} />
            </span>
            <span className="min-w-0 flex-1">
                <strong className="block truncate text-sm font-medium text-gray-900">{signalement.nature}</strong>
                <small className="block truncate text-xs text-muted">
                    {[signalement.numero, signalement.date, signalement.lieu].filter(Boolean).join(" · ")}
                </small>
            </span>
            <span className="flex shrink-0 flex-wrap justify-end gap-1.5">
                {signalement.confidentiel && (
                    <Badge variant="neutral">
                        <Lock {...ICON} size={14} />
                        Confidentiel
                    </Badge>
                )}
                <Badge variant={signalement.gravite.cle === "critique" || signalement.gravite.cle === "grave" ? "warning" : "neutral"}>
                    {signalement.gravite.libelle}
                </Badge>
                <Badge variant={signalement.statut.ton}>{signalement.statut.libelle}</Badge>
            </span>
        </li>
    );
}

/** Assistant en 3 étapes vers `etudiants.incidents.store` : l'incident, les faits, puis vérification et envoi. */
function FormulaireIncident({ natures, url }: { natures: IncidentsProps["natures"]; url: string }) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const schema = useMemo(() => incidentSchema(natures.map((n) => n.code)), [natures]);
    const {
        register,
        control,
        handleSubmit,
        reset,
        trigger,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<Incident>({ resolver: zodResolver(schema), defaultValues: vide(), mode: "onTouched" });
    const valeurs = useWatch({ control });

    const allerA = async (cible: number) => {
        // Avancer valide toutes les étapes intermédiaires ; reculer est toujours permis.
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPE[index]))) {
                setEtape(index);
                return;
            }
        }
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const transmettre = handleSubmit(async ({ sincerite: _sincerite, confidentiel, ...donnees }) => {
        const ok = await envoyer(
            url,
            { ...donnees, confidentiel: confidentiel ? 1 : 0 },
            {
                onError: (erreurs) => {
                    const autre = repartirErreurs(erreurs, CHAMPS_ETAPE, setError, setEtape);
                    if (autre) toast.error(autre);
                },
            },
        );
        if (ok) {
            reset(vide());
            setEtape(0);
            setAtteinte(0);
        }
    });

    const longueur = (valeurs.description ?? "").length;
    const gravite = GRAVITES.find((g) => g.code === valeurs.gravite);

    return (
        <form onSubmit={transmettre} noValidate className="flex flex-col gap-4">
            <p className="text-xs text-muted">
                Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont
                obligatoires.
            </p>
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} />

            {etape === 0 && (
                <>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field id="in-nature" label="Nature de l’incident" required error={errors.nature?.message}>
                            <Controller
                                control={control}
                                name="nature"
                                render={({ field }) => (
                                    <Select value={field.value} onValueChange={field.onChange}>
                                        <SelectTrigger id="in-nature" invalid={!!errors.nature} onBlur={field.onBlur}>
                                            <SelectValue placeholder="Sélectionner une catégorie" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {natures.map((nature) => (
                                                <SelectItem key={nature.code} value={nature.code}>
                                                    {nature.libelle}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                        </Field>
                        <Field id="in-date" label="Date et heure" required error={errors.date_incident?.message}>
                            <Input
                                id="in-date"
                                type="datetime-local"
                                max={maintenantLocal()}
                                invalid={!!errors.date_incident}
                                {...register("date_incident")}
                            />
                        </Field>
                    </div>
                    <Field id="in-lieu" label="Lieu précis" required error={errors.lieu?.message}>
                        <Input
                            id="in-lieu"
                            maxLength={255}
                            placeholder="Ex. couloir du bâtiment, entrée principale…"
                            invalid={!!errors.lieu}
                            {...register("lieu")}
                        />
                    </Field>
                    <fieldset className="space-y-1.5">
                        <legend className="mb-1.5 block text-sm font-medium text-gray-800">
                            Niveau d’urgence
                            <span className="ml-0.5 text-red-600">*</span>
                        </legend>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {GRAVITES.map((niveau) => (
                                <label
                                    key={niveau.code}
                                    className={cn(
                                        "flex cursor-pointer items-start gap-2.5 rounded-brand border p-3 transition-colors has-focus-visible:ring-2 has-focus-visible:ring-secondary",
                                        valeurs.gravite === niveau.code
                                            ? "border-primary bg-soft"
                                            : "border-gray-300 hover:bg-surface",
                                    )}
                                >
                                    <input
                                        type="radio"
                                        value={niveau.code}
                                        className="mt-1 accent-primary"
                                        {...register("gravite")}
                                    />
                                    <span>
                                        <strong className="block text-sm font-medium text-gray-900">{niveau.libelle}</strong>
                                        <small className="block text-xs text-muted">{niveau.detail}</small>
                                    </span>
                                </label>
                            ))}
                        </div>
                        {errors.gravite && (
                            <p role="alert" className="text-xs font-medium text-red-600">
                                {errors.gravite.message}
                            </p>
                        )}
                    </fieldset>
                </>
            )}

            {etape === 1 && (
                <>
                    <Field
                        id="in-description"
                        label="Description factuelle"
                        required
                        error={errors.description?.message}
                        hint={
                            <span className={cn(longueur > MAX_DESCRIPTION - 200 && "text-red-700")}>
                                Ce qui s’est passé, dans quel ordre, les personnes impliquées si elles sont connues.{" "}
                                {longueur} / {MAX_DESCRIPTION}
                            </span>
                        }
                    >
                        <Textarea
                            id="in-description"
                            rows={7}
                            maxLength={MAX_DESCRIPTION}
                            placeholder="Décrivez uniquement les faits dont vous avez connaissance."
                            invalid={!!errors.description}
                            {...register("description")}
                        />
                    </Field>
                    <Field id="in-temoins" label="Témoins éventuels" error={errors.temoins?.message} hint="Facultatif">
                        <Textarea
                            id="in-temoins"
                            rows={3}
                            maxLength={MAX_TEMOINS}
                            placeholder="Nom, chambre ou moyen de contact, si vous les connaissez"
                            invalid={!!errors.temoins}
                            {...register("temoins")}
                        />
                    </Field>
                </>
            )}

            {etape === 2 && (
                <>
                    <dl className="divide-y rounded-brand border">
                        {(
                            [
                                ["Nature", natures.find((n) => n.code === valeurs.nature)?.libelle ?? "—"],
                                ["Date", dateHeure(valeurs.date_incident)],
                                ["Lieu", valeurs.lieu || "—"],
                                ["Urgence", gravite?.libelle ?? "—"],
                                ["Témoins", valeurs.temoins?.trim() ? "Renseignés" : "Aucun"],
                            ] as [string, string][]
                        ).map(([libelle, valeur]) => (
                            <div key={libelle} className="flex flex-wrap justify-between gap-x-4 gap-y-0.5 px-3 py-2.5 text-sm">
                                <dt className="text-muted">{libelle}</dt>
                                <dd className="min-w-0 truncate font-medium text-gray-900">{valeur}</dd>
                            </div>
                        ))}
                    </dl>
                    <Controller
                        control={control}
                        name="confidentiel"
                        render={({ field }) => (
                            <label htmlFor="in-confidentiel" className="flex cursor-pointer items-start gap-2.5 rounded-brand border p-3">
                                <Checkbox
                                    id="in-confidentiel"
                                    checked={field.value}
                                    onCheckedChange={(v) => field.onChange(v === true)}
                                    className="mt-0.5"
                                />
                                <span>
                                    <strong className="block text-sm font-medium text-gray-900">
                                        Demander un traitement confidentiel
                                    </strong>
                                    <small className="block text-xs text-muted">
                                        Votre identité restera réservée aux agents habilités chargés du dossier.
                                    </small>
                                </span>
                            </label>
                        )}
                    />
                    <div className="space-y-1.5">
                        <Controller
                            control={control}
                            name="sincerite"
                            render={({ field }) => (
                                <label htmlFor="in-sincerite" className="flex cursor-pointer items-start gap-2.5 text-sm text-gray-800">
                                    <Checkbox
                                        id="in-sincerite"
                                        checked={field.value}
                                        onCheckedChange={(v) => field.onChange(v === true)}
                                        aria-invalid={!!errors.sincerite}
                                        className="mt-0.5"
                                    />
                                    <span>
                                        Je certifie que les informations communiquées sont sincères.
                                        <span className="ml-0.5 text-red-600">*</span>
                                    </span>
                                </label>
                            )}
                        />
                        {errors.sincerite && (
                            <p role="alert" className="text-xs font-medium text-red-600">
                                {errors.sincerite.message}
                            </p>
                        )}
                    </div>
                    <Notice icone={ShieldCheck}>
                        Un numéro de signalement vous sera communiqué. Vous suivrez son traitement dans « Mes signalements ».
                    </Notice>
                </>
            )}

            <div className="flex flex-wrap justify-between gap-2 border-t pt-4">
                {etape > 0 ? (
                    <Button variant="outline" onClick={() => setEtape(etape - 1)} disabled={isSubmitting}>
                        <ChevronLeft {...ICON} />
                        Précédent
                    </Button>
                ) : (
                    <span />
                )}
                {etape < ETAPES.length - 1 ? (
                    <Button onClick={() => allerA(etape + 1)} className="font-medium">
                        Suivant
                        <ChevronRight {...ICON} />
                    </Button>
                ) : (
                    <Button type="submit" disabled={isSubmitting} className="font-medium">
                        {isSubmitting ? <Loader2 {...ICON} className="animate-spin" /> : <Send {...ICON} />}
                        Transmettre le signalement
                    </Button>
                )}
            </div>
        </form>
    );
}
