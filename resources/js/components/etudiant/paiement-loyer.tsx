import { zodResolver } from "@hookform/resolvers/zod";
import { usePage } from "@inertiajs/react";
import {
    ChevronLeft,
    ChevronRight,
    CircleCheck,
    ExternalLink,
    Info,
    Loader2,
    Lock,
    Smartphone,
} from "lucide-react";
import { useEffect, useState } from "react";
import { useForm, useWatch } from "react-hook-form";
import { toast } from "sonner";
import { Notice } from "@/components/etudiant/panneau";
import { ICON } from "@/components/layout/app-sidebar";
import { Button, buttonVariants } from "@/components/ui/button";
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
import { Stepper } from "@/components/ui/stepper";
import { montant, symboleDevise } from "@/lib/systeme";
import { cn } from "@/lib/utils";
import {
    CHAMP_SERVEUR,
    CHAMPS_ETAPE,
    paiementLoyerSchema,
    type PaiementLoyer,
} from "@/schemas/paiement-loyer";
import type { SharedProps } from "@/types/global";

export type Reseau = { code: string; nom: string };

export type LoyerReglable = {
    id: string;
    chambre: string;
    periode: string;
    montant: string;
    penalites: string | null;
    dejaPaye: string | null;
    solde: string;
    soldeValeur: number;
};

type Props = {
    ouverte: boolean;
    fermer: () => void;
    url: string;
    loyer: LoyerReglable;
    reseaux: Reseau[];
    telephone: string;
};

/** Réponse JSON de OnlinePaymentController::store. */
type Lancement = {
    status: string;
    label: string;
    checkout_url: string | null;
    show_url: string;
};

const ETAPES = ["Moyen de paiement", "Montant", "Vérification"];

const francs = (valeur: string | number) =>
    montant(valeur);

/**
 * Assistant en 3 étapes pour régler le loyer en ligne. Le loyer et son solde viennent du serveur ;
 * l'opérateur prend ensuite le relais (page de paiement ou validation sur le téléphone).
 */
export function PaiementLoyerDialog({
    ouverte,
    fermer,
    url,
    loyer,
    reseaux,
    telephone,
}: Props) {
    const { csrfToken } = usePage<SharedProps>().props;
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const [lancement, setLancement] = useState<Lancement | null>(null);
    const {
        register,
        control,
        handleSubmit,
        reset,
        trigger,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<PaiementLoyer>({
        resolver: zodResolver(
            paiementLoyerSchema(
                reseaux.map((r) => r.code),
                loyer.soldeValeur,
            ),
        ),
        mode: "onTouched",
    });
    const valeurs = useWatch({ control });
    const reseau = reseaux.find((r) => r.code === valeurs.reseau);

    useEffect(() => {
        if (!ouverte) return;
        reset({
            reseau: reseaux.length === 1 ? reseaux[0].code : "",
            montant: String(loyer.soldeValeur),
            telephone,
        });
        setEtape(0);
        setAtteinte(0);
        setLancement(null);
    }, [ouverte, reseaux, loyer.soldeValeur, telephone, reset]);

    const allerA = async (cible: number) => {
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPE[index]))) {
                setEtape(index);
                return;
            }
        }
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const payer = handleSubmit(async (donnees) => {
        try {
            const reponse = await fetch(url, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    loyer_id: loyer.id,
                    provider: donnees.reseau,
                    amount: Number(donnees.montant),
                    phone: `225${donnees.telephone.replace(/\s/g, "")}`,
                }),
            });
            if (reponse.status === 422) {
                const { message, errors: erreurs = {} } = (await reponse.json()) as {
                    message?: string;
                    errors?: Record<string, string[]>;
                };
                let fautive = -1;
                Object.entries(erreurs).forEach(([cle, messages]) => {
                    const champ = CHAMP_SERVEUR[cle];
                    if (!champ) {
                        toast.error(messages[0]);
                        return;
                    }
                    setError(champ, { type: "server", message: messages[0] });
                    const index = CHAMPS_ETAPE.findIndex((champs) => champs.includes(champ));
                    if (fautive < 0 || index < fautive) fautive = index;
                });
                if (fautive >= 0) setEtape(fautive);
                if (Object.keys(erreurs).length === 0 && message) toast.error(message);
                return;
            }
            if (reponse.status === 401 || reponse.status === 419 || reponse.redirected) {
                toast.error("Votre session a expiré. Reconnectez-vous avant de continuer.");
                return;
            }
            if (!reponse.ok) {
                toast.error(
                    "La confirmation est indisponible. Consultez le suivi de vos paiements avant de réessayer.",
                );
                return;
            }
            const resultat = (await reponse.json()) as Lancement;
            if (resultat.checkout_url) {
                // Page de paiement de l'opérateur (Wave, CinetPay) : retour automatique sur le suivi.
                window.location.assign(resultat.checkout_url);
                return;
            }
            setLancement(resultat);
        } catch {
            toast.error(
                "Connexion interrompue. Vérifiez le suivi de vos paiements avant de réessayer.",
            );
        }
    });

    return (
        <Dialog
            open={ouverte}
            onOpenChange={(valeur) => !valeur && !isSubmitting && fermer()}
        >
            <DialogContent className="max-w-xl">
                {lancement ? (
                    <Resultat lancement={lancement} fermer={fermer} />
                ) : (
                    <form onSubmit={payer} noValidate className="flex min-h-0 flex-col">
                        <DialogHeader>
                            <DialogTitle>Payer mon loyer</DialogTitle>
                            <DialogDescription>
                                Étape {etape + 1} sur {ETAPES.length} · Chambre {loyer.chambre},
                                loyer de {loyer.periode}
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
                                <fieldset className="space-y-1.5">
                                    <legend className="mb-1.5 block text-sm font-medium text-gray-800">
                                        Moyen de paiement
                                        <span className="ml-0.5 text-red-600">*</span>
                                    </legend>
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        {reseaux.map((r) => (
                                            <label
                                                key={r.code}
                                                className={cn(
                                                    "flex min-h-14 cursor-pointer items-center gap-3 rounded-brand border p-3 transition-colors has-focus-visible:ring-2 has-focus-visible:ring-secondary",
                                                    valeurs.reseau === r.code
                                                        ? "border-primary bg-soft"
                                                        : "border-gray-300 hover:bg-surface",
                                                )}
                                            >
                                                <input
                                                    type="radio"
                                                    value={r.code}
                                                    className="accent-primary"
                                                    {...register("reseau")}
                                                />
                                                <Smartphone {...ICON} className="shrink-0 text-muted" />
                                                <strong className="text-sm font-medium text-gray-900">
                                                    {r.nom}
                                                </strong>
                                            </label>
                                        ))}
                                    </div>
                                    {errors.reseau && (
                                        <p role="alert" className="text-xs font-medium text-red-600">
                                            {errors.reseau.message}
                                        </p>
                                    )}
                                </fieldset>
                            )}

                            {etape === 1 && (
                                <>
                                    <Recapitulatif
                                        lignes={[
                                            ["Loyer facturé", loyer.montant],
                                            ...(loyer.penalites
                                                ? ([["Pénalités", loyer.penalites]] as [string, string][])
                                                : []),
                                            ...(loyer.dejaPaye
                                                ? ([["Déjà réglé", loyer.dejaPaye]] as [string, string][])
                                                : []),
                                            ["Solde restant", loyer.solde],
                                        ]}
                                    />
                                    <Field
                                        id="pl-montant"
                                        label={`Montant à payer (${symboleDevise()})`}
                                        required
                                        error={errors.montant?.message}
                                        hint={`Tout ou partie du solde, sans dépasser ${loyer.solde}.${valeurs.reseau === "cinetpay" ? " CinetPay : multiples de 5 FCFA." : ""}`}
                                    >
                                        <Input
                                            id="pl-montant"
                                            inputMode="numeric"
                                            autoComplete="off"
                                            invalid={!!errors.montant}
                                            {...register("montant")}
                                        />
                                    </Field>
                                    <Field
                                        id="pl-telephone"
                                        label={`Numéro ${reseau?.nom ?? "de téléphone"}`}
                                        required
                                        error={errors.telephone?.message}
                                        hint="Le numéro qui recevra la demande de paiement. L’indicatif +225 est ajouté automatiquement."
                                    >
                                        <div className="flex">
                                            <span className="inline-flex items-center rounded-l-brand border border-r-0 bg-surface px-3 text-sm text-muted">
                                                +225
                                            </span>
                                            <Input
                                                id="pl-telephone"
                                                type="tel"
                                                inputMode="numeric"
                                                autoComplete="tel-national"
                                                placeholder="07 00 00 00 00"
                                                maxLength={14}
                                                invalid={!!errors.telephone}
                                                className="rounded-l-none"
                                                {...register("telephone")}
                                            />
                                        </div>
                                    </Field>
                                </>
                            )}

                            {etape === 2 && (
                                <>
                                    <Recapitulatif
                                        lignes={[
                                            ["Chambre", loyer.chambre],
                                            ["Période", loyer.periode],
                                            ["Moyen de paiement", reseau?.nom ?? "—"],
                                            ["Téléphone", `+225 ${valeurs.telephone ?? ""}`],
                                            ["Montant", francs(valeurs.montant ?? 0)],
                                        ]}
                                    />
                                    <Notice icone={Info}>
                                        Après validation, vous confirmerez l’opération sur votre
                                        téléphone ou sur la page de {reseau?.nom ?? "l’opérateur"}.
                                        Votre solde est mis à jour dès la confirmation.
                                    </Notice>
                                </>
                            )}
                        </DialogBody>

                        <DialogFooter className="sm:justify-between">
                            <Button
                                variant="outline"
                                onClick={etape === 0 ? fermer : () => setEtape(etape - 1)}
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
                                <Button key="suivant" onClick={() => allerA(etape + 1)} className="font-medium">
                                    Suivant
                                    <ChevronRight {...ICON} />
                                </Button>
                            ) : (
                                <Button key="envoyer" type="submit" disabled={isSubmitting} className="font-medium">
                                    {isSubmitting ? (
                                        <Loader2 {...ICON} className="animate-spin" />
                                    ) : (
                                        <Lock {...ICON} />
                                    )}
                                    Payer {francs(valeurs.montant ?? 0)}
                                </Button>
                            )}
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}

/** Demande transmise sans page de l'opérateur (ex. MTN : validation sur le téléphone). */
function Resultat({ lancement, fermer }: { lancement: Lancement; fermer: () => void }) {
    const enAttente = lancement.status === "pending" || lancement.status === "initializing";

    return (
        <>
            <DialogHeader>
                <DialogTitle>{lancement.label}</DialogTitle>
                <DialogDescription>
                    {enAttente
                        ? "Validez le paiement sur votre téléphone, puis suivez sa confirmation."
                        : "Consultez le suivi pour connaître l’état de ce paiement."}
                </DialogDescription>
            </DialogHeader>
            <DialogBody>
                <Notice icone={enAttente ? Smartphone : CircleCheck}>
                    Ne relancez pas un nouveau paiement tant que celui-ci est en cours de
                    confirmation.
                </Notice>
            </DialogBody>
            <DialogFooter className="sm:justify-between">
                <Button variant="outline" onClick={fermer}>
                    Fermer
                </Button>
                <a href={lancement.show_url} className={cn(buttonVariants(), "font-medium")}>
                    Suivre ce paiement
                    <ExternalLink {...ICON} />
                </a>
            </DialogFooter>
        </>
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
                    <dd className="min-w-0 truncate font-medium text-gray-900">{valeur}</dd>
                </div>
            ))}
        </dl>
    );
}
