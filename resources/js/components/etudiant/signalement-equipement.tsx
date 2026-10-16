import { zodResolver } from "@hookform/resolvers/zod";
import { ChevronLeft, ChevronRight, Info, Loader2, Send } from "lucide-react";
import { useEffect, useState } from "react";
import { Controller, useForm, useWatch } from "react-hook-form";
import { toast } from "sonner";
import { Notice } from "@/components/etudiant/panneau";
import { ICON } from "@/components/layout/app-sidebar";
import { Button } from "@/components/ui/button";
import { DatePicker } from "@/components/ui/date-picker";
import {
    Dialog,
    DialogBody,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
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
import { Textarea } from "@/components/ui/textarea";
import { envoyer } from "@/lib/inertia-form";
import { cn } from "@/lib/utils";
import {
    aujourdHui,
    CHAMPS_ETAPE,
    MAX_DESCRIPTION,
    PERIODES,
    PRIORITES,
    signalementSchema,
    type Signalement,
} from "@/schemas/signalement";

export type EquipementChambre = {
    id: string | null;
    nom: string;
    quantite: number;
    etat: string | null;
    ton: "success" | "warning" | "danger" | "neutral";
};

export type Categorie = { code: string; libelle: string };

type Props = {
    ouverte: boolean;
    fermer: () => void;
    url: string;
    equipements: EquipementChambre[];
    categories: Categorie[];
    /** Équipement présélectionné (index dans `equipements`) quand on signale depuis sa ligne. */
    equipementInitial?: number | null;
    /** Titre de la fenêtre (page Maintenance : nouvelle demande). */
    titre?: string;
};

const ETAPES = ["Problème", "Détails", "Disponibilité"];

/** Catégorie probable d'un équipement d'après son nom (l'étudiant peut la changer). */
function categorieProbable(nom: string, categories: Categorie[]): string {
    const n = nom.toLowerCase();
    const regles: [RegExp, string][] = [
        [/prise|lampe|ampoule|n[ée]on|interrupteur|[ée]lectri/, "electricite"],
        [/clim|ventil/, "climatisation"],
        [/douche|wc|toilette|lavabo|robinet|[ée]vier/, "sanitaire"],
        [/lit|matelas|chaise|table|bureau|armoire|placard|[ée]tag/, "mobilier"],
        [/extincteur|d[ée]tecteur|serrure|cl[ée]|porte/, "securite"],
        [/fen[êe]tre|vitre|mur|plafond|sol/, "batiment"],
    ];
    const trouvee = regles.find(([motif]) => motif.test(n))?.[1];
    return trouvee && categories.some((c) => c.code === trouvee) ? trouvee : "";
}

const VIDE: Signalement = {
    equipement: "",
    categorie: "",
    titre: "",
    description: "",
    priorite: "moyenne",
    date_disponibilite: "",
    periode_disponibilite: "",
};

/** Assistant en 3 étapes pour signaler un équipement défectueux ou un autre problème : crée une demande de maintenance (route etudiants.store_ot). Utilisé par « Ma chambre » et « Maintenance ». */
export function SignalementEquipement({
    ouverte,
    fermer,
    url,
    equipements,
    categories,
    equipementInitial = null,
    titre = "Signaler un équipement défectueux",
}: Props) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const {
        register,
        control,
        handleSubmit,
        reset,
        trigger,
        setValue,
        getValues,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<Signalement>({
        resolver: zodResolver(signalementSchema(categories.map((c) => c.code))),
        defaultValues: VIDE,
        mode: "onTouched",
    });
    const valeurs = useWatch({ control });

    useEffect(() => {
        if (!ouverte) return;
        const equipement =
            equipementInitial !== null
                ? equipements[equipementInitial]
                : undefined;
        reset(
            equipement
                ? {
                      ...VIDE,
                      equipement: String(equipementInitial),
                      categorie: categorieProbable(equipement.nom, categories),
                      titre: `${equipement.nom} défectueux`,
                  }
                : // Sans équipement listé, seul « Autre problème » est possible : il est présélectionné.
                  { ...VIDE, equipement: equipements.length === 0 ? "autre" : "" },
        );
        setEtape(0);
        setAtteinte(0);
    }, [ouverte, equipementInitial, equipements, categories, reset]);

    const choisirEquipement = (valeur: string) => {
        setValue("equipement", valeur, { shouldValidate: true });
        const equipement =
            valeur === "autre" ? undefined : equipements[Number(valeur)];
        if (!equipement) return;
        // Pré-remplissage seulement des champs encore vides.
        if (!getValues("categorie"))
            setValue(
                "categorie",
                categorieProbable(equipement.nom, categories),
            );
        if (!getValues("titre").trim())
            setValue("titre", `${equipement.nom} défectueux`);
    };

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

    const envoyerSignalement = handleSubmit(
        async ({ equipement, ...donnees }) => {
            const choisi =
                equipement === "autre"
                    ? undefined
                    : equipements[Number(equipement)];
            const ok = await envoyer(
                url,
                {
                    ...donnees,
                    type_intervention: "corrective",
                    equipement_id: choisi?.id ?? "",
                },
                {
                    onError: (erreurs) => {
                        // Erreurs serveur sous le champ concerné, puis retour à l'étape du premier champ fautif.
                        let fautive = -1;
                        Object.entries(erreurs).forEach(([cle, message]) => {
                            const champ = (
                                cle === "equipement_id" ? "equipement" : cle
                            ) as keyof Signalement;
                            const index = CHAMPS_ETAPE.findIndex((champs) =>
                                champs.includes(champ),
                            );
                            if (index < 0) {
                                toast.error(message);
                                return;
                            }
                            setError(champ, { type: "server", message });
                            if (fautive < 0 || index < fautive) fautive = index;
                        });
                        if (fautive >= 0) setEtape(fautive);
                    },
                },
            );
            if (ok) fermer();
        },
    );

    const equipementChoisi =
        valeurs.equipement && valeurs.equipement !== "autre"
            ? equipements[Number(valeurs.equipement)]
            : undefined;
    const longueur = (valeurs.description ?? "").length;

    return (
        <Dialog
            open={ouverte}
            onOpenChange={(valeur) => !valeur && !isSubmitting && fermer()}
        >
            <DialogContent className="max-w-xl">
                <form
                    onSubmit={envoyerSignalement}
                    noValidate
                    className="flex min-h-0 flex-col"
                >
                    <DialogHeader>
                        <DialogTitle>{titre}</DialogTitle>
                        <DialogDescription>
                            Étape {etape + 1} sur {ETAPES.length} · Les champs
                            marqués <span className="text-red-600">*</span> sont
                            obligatoires.
                        </DialogDescription>
                    </DialogHeader>
                    <Stepper
                        steps={ETAPES}
                        current={etape}
                        reached={atteinte}
                        onStepClick={allerA}
                        className="px-4 sm:px-6"
                    />

                    <DialogBody className="flex flex-col gap-4">
                        {etape === 0 && (
                            <>
                                <Field
                                    id="sg-equipement"
                                    label="Équipement concerné"
                                    required
                                    error={errors.equipement?.message}
                                >
                                    <Controller
                                        control={control}
                                        name="equipement"
                                        render={({ field }) => (
                                            <Select
                                                value={field.value}
                                                onValueChange={
                                                    choisirEquipement
                                                }
                                            >
                                                <SelectTrigger
                                                    id="sg-equipement"
                                                    invalid={
                                                        !!errors.equipement
                                                    }
                                                    onBlur={field.onBlur}
                                                >
                                                    <SelectValue placeholder="Choisir un équipement" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {equipements.map(
                                                        (equipement, index) => (
                                                            <SelectItem
                                                                key={`${equipement.nom}-${index}`}
                                                                value={String(
                                                                    index,
                                                                )}
                                                            >
                                                                {equipement.nom}
                                                                {equipement.quantite >
                                                                1
                                                                    ? ` (×${equipement.quantite})`
                                                                    : ""}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                    <SelectItem value="autre">
                                                        Autre problème dans la
                                                        chambre
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        )}
                                    />
                                </Field>
                                <Field
                                    id="sg-categorie"
                                    label="Catégorie du problème"
                                    required
                                    error={errors.categorie?.message}
                                >
                                    <Controller
                                        control={control}
                                        name="categorie"
                                        render={({ field }) => (
                                            <Select
                                                value={field.value}
                                                onValueChange={(v) =>
                                                    field.onChange(v)
                                                }
                                            >
                                                <SelectTrigger
                                                    id="sg-categorie"
                                                    invalid={!!errors.categorie}
                                                    onBlur={field.onBlur}
                                                >
                                                    <SelectValue placeholder="Choisir une catégorie" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {categories.map(
                                                        (categorie) => (
                                                            <SelectItem
                                                                key={
                                                                    categorie.code
                                                                }
                                                                value={
                                                                    categorie.code
                                                                }
                                                            >
                                                                {
                                                                    categorie.libelle
                                                                }
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        )}
                                    />
                                </Field>
                                <Field
                                    id="sg-titre"
                                    label="Titre"
                                    required
                                    error={errors.titre?.message}
                                    hint="Ex. : Ventilateur en panne, prise qui ne fonctionne plus."
                                >
                                    <Input
                                        id="sg-titre"
                                        maxLength={255}
                                        placeholder="Résumez le problème"
                                        invalid={!!errors.titre}
                                        {...register("titre")}
                                    />
                                </Field>
                            </>
                        )}

                        {etape === 1 && (
                            <>
                                <Field
                                    id="sg-description"
                                    label="Description du problème"
                                    required
                                    error={errors.description?.message}
                                    hint={
                                        <span
                                            className={cn(
                                                longueur >
                                                    MAX_DESCRIPTION - 100 &&
                                                    "text-red-700",
                                            )}
                                        >
                                            Depuis quand, ce qui ne fonctionne
                                            pas… {longueur} / {MAX_DESCRIPTION}
                                        </span>
                                    }
                                >
                                    <Textarea
                                        id="sg-description"
                                        rows={5}
                                        maxLength={MAX_DESCRIPTION}
                                        placeholder="Ex. : Le ventilateur ne démarre plus depuis hier soir, aucun bruit à l’allumage."
                                        invalid={!!errors.description}
                                        {...register("description")}
                                    />
                                </Field>
                                <fieldset className="space-y-1.5">
                                    <legend className="mb-1.5 block text-sm font-medium text-gray-800">
                                        Niveau d’urgence
                                        <span className="ml-0.5 text-red-600">
                                            *
                                        </span>
                                    </legend>
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        {PRIORITES.map((priorite) => (
                                            <label
                                                key={priorite.code}
                                                className={cn(
                                                    "flex cursor-pointer items-start gap-2.5 rounded-brand border p-3 transition-colors has-focus-visible:ring-2 has-focus-visible:ring-secondary",
                                                    valeurs.priorite ===
                                                        priorite.code
                                                        ? "border-primary bg-soft"
                                                        : "border-gray-300 hover:bg-surface",
                                                )}
                                            >
                                                <input
                                                    type="radio"
                                                    value={priorite.code}
                                                    className="mt-1 accent-primary"
                                                    {...register("priorite")}
                                                />
                                                <span>
                                                    <strong className="block text-sm font-medium text-gray-900">
                                                        {priorite.libelle}
                                                    </strong>
                                                    <small className="block text-xs text-muted">
                                                        {priorite.detail}
                                                    </small>
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                    {errors.priorite && (
                                        <p
                                            role="alert"
                                            className="text-xs font-medium text-red-600"
                                        >
                                            {errors.priorite.message}
                                        </p>
                                    )}
                                </fieldset>
                            </>
                        )}

                        {etape === 2 && (
                            <>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        id="sg-date"
                                        label="Disponible à partir du"
                                        error={
                                            errors.date_disponibilite?.message
                                        }
                                        hint="Facultatif"
                                    >
                                        <Controller
                                            control={control}
                                            name="date_disponibilite"
                                            render={({ field }) => (
                                                <DatePicker
                                                    id="sg-date"
                                                    value={field.value}
                                                    onChange={field.onChange}
                                                    min={aujourdHui()}
                                                    invalid={
                                                        !!errors.date_disponibilite
                                                    }
                                                />
                                            )}
                                        />
                                    </Field>
                                    <Field
                                        id="sg-periode"
                                        label="Période préférée"
                                        hint="Facultatif"
                                    >
                                        <Controller
                                            control={control}
                                            name="periode_disponibilite"
                                            render={({ field }) => (
                                                <Select
                                                    value={
                                                        field.value || undefined
                                                    }
                                                    onValueChange={
                                                        field.onChange
                                                    }
                                                >
                                                    <SelectTrigger id="sg-periode">
                                                        <SelectValue placeholder="Indifférent" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {PERIODES.map(
                                                            (periode) => (
                                                                <SelectItem
                                                                    key={
                                                                        periode
                                                                    }
                                                                    value={
                                                                        periode
                                                                    }
                                                                >
                                                                    {periode}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            )}
                                        />
                                    </Field>
                                </div>
                                <Recapitulatif
                                    lignes={[
                                        [
                                            "Équipement",
                                            equipementChoisi?.nom ??
                                                "Autre problème",
                                        ],
                                        [
                                            "Catégorie",
                                            categories.find(
                                                (c) =>
                                                    c.code ===
                                                    valeurs.categorie,
                                            )?.libelle ?? "—",
                                        ],
                                        ["Titre", valeurs.titre ?? "—"],
                                        [
                                            "Urgence",
                                            PRIORITES.find(
                                                (p) =>
                                                    p.code === valeurs.priorite,
                                            )?.libelle ?? "—",
                                        ],
                                    ]}
                                />
                                <Notice icone={Info}>
                                    Un numéro de suivi vous sera communiqué.
                                    Vous suivrez l’avancement dans « Mes
                                    signalements » et dans la rubrique
                                    Maintenance.
                                </Notice>
                            </>
                        )}
                    </DialogBody>

                    <DialogFooter className="sm:justify-between">
                        <Button
                            variant="outline"
                            onClick={
                                etape === 0 ? fermer : () => setEtape(etape - 1)
                            }
                            disabled={isSubmitting}
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
                            <Button
                                onClick={() => allerA(etape + 1)}
                                className="font-medium"
                            >
                                Suivant
                                <ChevronRight {...ICON} />
                            </Button>
                        ) : (
                            <Button
                                type="submit"
                                disabled={isSubmitting}
                                className="font-medium"
                            >
                                {isSubmitting ? (
                                    <Loader2
                                        {...ICON}
                                        className="animate-spin"
                                    />
                                ) : (
                                    <Send {...ICON} />
                                )}
                                Envoyer le signalement
                            </Button>
                        )}
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function Recapitulatif({ lignes }: { lignes: [string, string][] }) {
    return (
        <dl className="divide-y rounded-brand border">
            {lignes.map(([libelle, valeur]) => (
                <div
                    key={libelle}
                    className="flex flex-wrap justify-between gap-x-4 gap-y-0.5 px-3 py-2.5 text-sm"
                >
                    <dt className="text-muted">{libelle}</dt>
                    <dd className="min-w-0 truncate font-medium text-gray-900">
                        {valeur}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
