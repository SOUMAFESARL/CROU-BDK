import { zodResolver } from "@hookform/resolvers/zod";
import {
    Building2,
    CalendarDays,
    ChevronRight,
    CircleCheck,
    ClipboardCheck,
    FileDown,
    Hourglass,
    Info,
    KeyRound,
    Loader2,
    LogIn,
    LogOut,
    PenLine,
    PenTool,
    TrendingDown,
} from "lucide-react";
import { type ReactNode, useState } from "react";
import { Controller, useForm } from "react-hook-form";
import { FormulaireEntreeEdl } from "@/components/etudiant/formulaire-entree-edl";
import { Ligne, Notice, Panneau, Vide } from "@/components/etudiant/panneau";
import { VignettesEdl } from "@/components/etudiant/photos-edl";
import { SignaturePad } from "@/components/etudiant/signature-pad";
import { ICON } from "@/components/layout/app-sidebar";
import { EtudiantLayout } from "@/components/layout/etudiant-layout";
import { Badge } from "@/components/ui/badge";
import { Button, buttonVariants } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Field } from "@/components/ui/field";
import { StatCard, type KpiTone } from "@/components/ui/stat-card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { envoyer } from "@/lib/inertia-form";
import { cn } from "@/lib/utils";
import { ETATS, signatureSortieSchema, type Etat, type SignatureSortie } from "@/schemas/etat-des-lieux";

export type Lieu = { idCite: string; idBatiment: string; idChambre: string };

type Photo = { chemin: string; url: string | null };

type ElementConstat = { nom: string; etat: Etat | null; observation: string | null; photo: Photo | null };

type PieceConstat = { nom: string; photos: Photo[]; elements: ElementConstat[] };

export type Constat = {
    id: string;
    lieu: Lieu;
    date: string | null;
    dateAffichee: string | null;
    cles: number | null;
    observation: string | null;
    signatureCrou: string | null;
    signatureEtudiant: string | null;
    pieces: PieceConstat[];
    pdf: string;
    modifier: string;
};

type Chambre = Lieu & { cite: string | null; batiment: string | null; numero: string | null };

type EtatDesLieuxProps = {
    etudiant: { id: string; nom: string };
    chambre: Chambre | null;
    entree: Constat | null;
    sortie: Constat | null;
    urls: { enregistrer: string; chambre: string; maintenance: string };
};

const RANG: Record<Etat, number> = { bon: 0, moyen: 1, mauvais: 2 };

/** Statut affiché d'un constat : libellé, teinte du KPI et variante du badge. */
function statutEntree(entree: Constat | null): { libelle: string; ton: KpiTone; badge: "success" | "warning" | "info" } {
    if (!entree) return { libelle: "À compléter", ton: "amber", badge: "warning" };
    if (entree.signatureCrou) return { libelle: "Validé par le CROU", ton: "green", badge: "success" };
    return { libelle: "En attente du CROU", ton: "blue", badge: "info" };
}

function statutSortie(sortie: Constat | null): { libelle: string; ton: KpiTone; badge: "success" | "warning" | "info" } {
    if (!sortie) return { libelle: "Non établi", ton: "neutral", badge: "info" };
    if (!sortie.signatureEtudiant) return { libelle: "À signer", ton: "amber", badge: "warning" };
    return { libelle: "Signé", ton: "green", badge: "success" };
}

/** Page « État des lieux » : saisie de l'état des lieux d'entrée par l'étudiant, consultation et signature de celui de sortie. */
export default function EtatDesLieux({ etudiant, chambre, entree, sortie, urls }: EtatDesLieuxProps) {
    const [edition, setEdition] = useState(false);
    const [onglet, setOnglet] = useState(sortie && !sortie.signatureEtudiant ? "sortie" : "entree");
    const se = statutEntree(entree);
    const ss = statutSortie(sortie);
    const elements = entree?.pieces.flatMap((p) => p.elements) ?? [];
    const aSurveiller = elements.filter((e) => e.etat === "moyen" || e.etat === "mauvais").length;
    const modifiable = !!chambre && (!entree || !entree.signatureCrou);
    const lieu = chambre ? [chambre.numero && `Chambre ${chambre.numero}`, chambre.batiment, chambre.cite].filter(Boolean).join(" · ") : null;

    return (
        <EtudiantLayout title="État des lieux">
            <header className="mb-5 min-w-0">
                <h1 className="page-title text-xl font-medium text-gray-900">État des lieux</h1>
                <p className="mt-1 text-sm text-muted">
                    {lieu
                        ? `Constats de votre logement (${lieu}), annexés à votre contrat de location.`
                        : "Constats de votre logement à l’entrée et à la sortie, annexés à votre contrat de location."}
                </p>
            </header>

            <div className="flex flex-col gap-5">

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <StatCard label="Entrée" value={<span className="text-base">{se.libelle}</span>} tone={se.ton} highlight={!entree && !!chambre} />
                    <StatCard label="Sortie" value={<span className="text-base">{ss.libelle}</span>} tone={ss.ton} highlight={ss.libelle === "À signer"} />
                    <StatCard label="Pièces inspectées" value={entree?.pieces.length ?? 0} tone="plum" extra={elements.length ? `${elements.length} éléments` : undefined} />
                    <StatCard label="Éléments à surveiller" value={aSurveiller} tone={aSurveiller > 0 ? "red" : "neutral"} extra={aSurveiller > 0 ? "Moyen ou mauvais" : undefined} />
                </div>

                {!chambre ? (
                    <Panneau surtitre="Logement" titre="Aucune chambre attribuée">
                        <Vide icone={ClipboardCheck}>
                            L’état des lieux se remplit une fois votre chambre attribuée et votre entrée confirmée. Revenez sur cette page le jour de
                            votre arrivée.
                        </Vide>
                    </Panneau>
                ) : (
                    <Tabs value={onglet} onValueChange={setOnglet}>
                        <TabsList>
                            <TabsTrigger value="entree">
                                <LogIn {...ICON} />
                                Entrée
                                <Badge variant={se.badge}>{se.libelle}</Badge>
                            </TabsTrigger>
                            <TabsTrigger value="sortie">
                                <LogOut {...ICON} />
                                Sortie
                                {sortie && <Badge variant={ss.badge}>{ss.libelle}</Badge>}
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="entree" className="mx-auto flex w-full max-w-3xl flex-col gap-4">
                            {!entree || (edition && modifiable) ? (
                                <FormulaireEntreeEdl
                                    key={entree?.id ?? "nouveau"}
                                    entree={entree}
                                    lieu={chambre}
                                    etudiantId={etudiant.id}
                                    urlCreation={urls.enregistrer}
                                    annuler={entree ? () => setEdition(false) : undefined}
                                    onEnregistre={() => setEdition(false)}
                                />
                            ) : (
                                <>
                                    {modifiable ? (
                                        <Notice icone={Hourglass} className="bg-sky-50 text-sky-800">
                                            Constat enregistré, en attente de la signature du CROU. Vous pouvez encore le modifier jusque-là.
                                        </Notice>
                                    ) : (
                                        <Notice icone={CircleCheck}>Constat validé par le CROU : il ne peut plus être modifié.</Notice>
                                    )}
                                    <ConsultationConstat
                                        titre="État des lieux d’entrée"
                                        constat={entree}
                                        chambre={chambre}
                                        libelleCles="Clés remises"
                                        actions={
                                            <>
                                                {modifiable && (
                                                    <Button onClick={() => setEdition(true)} className="font-medium">
                                                        <PenLine {...ICON} />
                                                        Modifier le constat
                                                    </Button>
                                                )}
                                                <a href={entree.pdf} target="_blank" rel="noopener noreferrer" className={cn(buttonVariants({ variant: "secondary" }), "font-medium")}>
                                                    <FileDown {...ICON} />
                                                    Télécharger le PDF
                                                </a>
                                            </>
                                        }
                                    />
                                </>
                            )}
                        </TabsContent>

                        <TabsContent value="sortie" className="mx-auto flex w-full max-w-3xl flex-col gap-4">
                            {!sortie ? (
                                <Panneau surtitre="Départ du logement" titre="État des lieux de sortie">
                                    <Vide icone={LogOut}>
                                        Le CROU établit l’état des lieux de sortie avec vous le jour de votre départ. Il apparaîtra ici pour que vous
                                        puissiez le relire et le signer.
                                    </Vide>
                                </Panneau>
                            ) : (
                                <>
                                    {sortie.signatureEtudiant ? (
                                        <Notice icone={CircleCheck}>Vous avez signé l’état des lieux de sortie.</Notice>
                                    ) : (
                                        <Notice icone={Info} className="bg-amber-50 text-amber-800">
                                            Le CROU a établi l’état des lieux de sortie. Relisez les constatations, comparées à votre état d’entrée, puis
                                            signez en bas de page.
                                        </Notice>
                                    )}
                                    <ConsultationConstat
                                        titre="État des lieux de sortie"
                                        constat={sortie}
                                        reference={entree}
                                        chambre={chambre}
                                        libelleCles="Clés rendues"
                                        actions={
                                            <a href={sortie.pdf} target="_blank" rel="noopener noreferrer" className={cn(buttonVariants({ variant: "secondary" }), "font-medium")}>
                                                <FileDown {...ICON} />
                                                Télécharger le PDF
                                            </a>
                                        }
                                    />
                                    {!sortie.signatureEtudiant && <SignerSortie sortie={sortie} etudiantId={etudiant.id} />}
                                </>
                            )}
                        </TabsContent>
                    </Tabs>
                )}

                {chambre && (
                    <div className="flex flex-wrap gap-x-5 gap-y-2">
                        <a href={urls.chambre} className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
                            Voir ma chambre
                            <ChevronRight {...ICON} size={16} />
                        </a>
                        <a href={urls.maintenance} className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
                            Signaler un problème au service technique
                            <ChevronRight {...ICON} size={16} />
                        </a>
                    </div>
                )}
            </div>
        </EtudiantLayout>
    );
}

/** Élément correspondant de l'état d'entrée : même pièce et même nom, à défaut même position. */
function correspondant(reference: Constat | null | undefined, pieceIndex: number, piece: PieceConstat, elementIndex: number, element: ElementConstat) {
    if (!reference) return undefined;
    const meme = (a: string, b: string) => a.trim().toLowerCase() === b.trim().toLowerCase();
    const pieceRef = reference.pieces.find((p) => meme(p.nom, piece.nom)) ?? reference.pieces[pieceIndex];
    return pieceRef?.elements.find((e) => meme(e.nom, element.nom)) ?? pieceRef?.elements[elementIndex];
}

function BadgeEtat({ etat }: { etat: Etat | null }) {
    const option = ETATS.find((e) => e.code === etat);
    return <Badge variant={option?.ton ?? "neutral"}>{option?.libelle ?? "Non renseigné"}</Badge>;
}

type ConsultationProps = {
    titre: string;
    constat: Constat;
    /** État d'entrée, pour comparer chaque élément de la sortie. */
    reference?: Constat | null;
    chambre: Chambre;
    libelleCles: string;
    actions: ReactNode;
};

/** Constat en lecture seule : informations, pièces et éléments, observations et signatures. */
function ConsultationConstat({ titre, constat, reference, chambre, libelleCles, actions }: ConsultationProps) {
    return (
        <Panneau surtitre={constat.dateAffichee ? `Constat du ${constat.dateAffichee}` : "Constat"} titre={titre}>
            <div className="flex flex-col gap-5">
                <ul className="-mx-2 grid sm:grid-cols-3">
                    <Ligne icone={Building2} libelle="Logement" titre={[chambre.numero && `Chambre ${chambre.numero}`, chambre.batiment].filter(Boolean).join(" · ") || "—"} detail={chambre.cite} />
                    <Ligne icone={CalendarDays} libelle="Date" titre={constat.dateAffichee ?? "—"} />
                    <Ligne icone={KeyRound} libelle={libelleCles} titre={constat.cles ?? "—"} />
                </ul>

                {constat.pieces.length === 0 ? (
                    <Vide icone={ClipboardCheck}>Aucune pièce n’a été détaillée dans ce constat.</Vide>
                ) : (
                    <div className="flex flex-col gap-3">
                        {constat.pieces.map((piece, i) => (
                            <section key={`${piece.nom}-${i}`} className="rounded-brand border" aria-label={piece.nom}>
                                <h3 className="flex items-center justify-between gap-2 border-b bg-surface px-3 py-2.5 text-sm font-medium text-gray-900">
                                    {piece.nom || `Pièce ${i + 1}`}
                                    <span className="text-xs font-normal text-muted">
                                        {piece.elements.length} élément{piece.elements.length > 1 ? "s" : ""}
                                    </span>
                                </h3>
                                <ul className="divide-y">
                                    {piece.elements.map((element, j) => {
                                        const ref = correspondant(reference, i, piece, j, element);
                                        const degrade = !!(ref?.etat && element.etat && RANG[element.etat] > RANG[ref.etat]);
                                        return (
                                            <li key={`${element.nom}-${j}`} className="flex items-start gap-3 px-3 py-2.5">
                                                <div className="min-w-0 flex-1">
                                                    <p className="text-sm text-gray-900">{element.nom || `Élément ${j + 1}`}</p>
                                                    {element.observation && <p className="mt-0.5 text-xs text-muted">{element.observation}</p>}
                                                    {reference !== undefined && (
                                                        <p className={cn("mt-1 inline-flex items-center gap-1 text-xs", degrade ? "font-medium text-red-700" : "text-muted")}>
                                                            {degrade && <TrendingDown {...ICON} size={14} />}
                                                            À l’entrée : {ETATS.find((e) => e.code === ref?.etat)?.libelle ?? "non renseigné"}
                                                        </p>
                                                    )}
                                                </div>
                                                {element.photo?.url && (
                                                    <VignettesEdl photos={[element.photo]} libelle={`Photo de ${element.nom}`} />
                                                )}
                                                <BadgeEtat etat={element.etat} />
                                            </li>
                                        );
                                    })}
                                </ul>
                                {piece.photos.length > 0 && (
                                    <div className="border-t p-3">
                                        <p className="mb-2 text-xs text-muted">Photos de la pièce</p>
                                        <VignettesEdl photos={piece.photos} libelle={`Photo ${piece.nom}`} />
                                    </div>
                                )}
                            </section>
                        ))}
                    </div>
                )}

                {constat.observation && (
                    <section>
                        <h3 className="mb-1 text-xs text-muted">Observations générales</h3>
                        <p className="rounded-brand bg-surface p-3 text-sm whitespace-pre-line text-gray-900">{constat.observation}</p>
                    </section>
                )}

                <section className="grid gap-3 sm:grid-cols-2" aria-label="Signatures">
                    <CadreSignature libelle="Le CROU" image={constat.signatureCrou} attente="En attente de la signature du CROU" />
                    <CadreSignature libelle="L’étudiant" image={constat.signatureEtudiant} attente="En attente de votre signature" />
                </section>

                <div className="flex flex-wrap gap-2 border-t pt-4">{actions}</div>
            </div>
        </Panneau>
    );
}

function CadreSignature({ libelle, image, attente }: { libelle: string; image: string | null; attente: string }) {
    return (
        <div className="rounded-brand border p-3">
            <p className="mb-2 flex items-center gap-1.5 text-xs text-muted">
                <PenTool {...ICON} size={14} />
                {libelle}
            </p>
            {image ? (
                <img src={image} alt={`Signature : ${libelle}`} className="h-16 w-full object-contain" />
            ) : (
                <p className="grid h-16 place-items-center rounded-brand bg-surface text-xs text-muted">{attente}</p>
            )}
        </div>
    );
}

/** Signature de l'état des lieux de sortie établi par le CROU (dashboardController::updateEdl : l'étudiant n'ajoute que sa signature). */
function SignerSortie({ sortie, etudiantId }: { sortie: Constat; etudiantId: string }) {
    const {
        control,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<SignatureSortie>({
        resolver: zodResolver(signatureSortieSchema),
        defaultValues: { signature_etudiant: null, lu: false as unknown as true },
    });

    const signer = handleSubmit(async ({ signature_etudiant }) => {
        await envoyer(
            sortie.modifier,
            {
                type_etat: "sortie",
                id_cite: sortie.lieu.idCite,
                id_batiment: sortie.lieu.idBatiment,
                id_chambre: sortie.lieu.idChambre,
                id_etudiant: etudiantId,
                signature_etudiant: signature_etudiant ?? "",
            },
            { method: "put" },
        );
    });

    return (
        <Panneau surtitre="Validation" titre="Signer l’état des lieux de sortie">
            <form onSubmit={signer} noValidate className="flex flex-col gap-4">
                <Controller
                    control={control}
                    name="lu"
                    render={({ field }) => (
                        <div className="space-y-1.5">
                            <label className="flex cursor-pointer items-start gap-2.5 text-sm text-gray-900">
                                <Checkbox checked={field.value === true} onCheckedChange={(v) => field.onChange(v === true)} aria-invalid={!!errors.lu || undefined} className="mt-0.5" />
                                J’ai relu les constatations du CROU et je confirme l’état des lieux de sortie.
                            </label>
                            {errors.lu && (
                                <p role="alert" className="text-xs font-medium text-red-600">
                                    {errors.lu.message}
                                </p>
                            )}
                        </div>
                    )}
                />
                <Field id="edl-signature-sortie" label="Votre signature" required error={errors.signature_etudiant?.message}>
                    <Controller
                        control={control}
                        name="signature_etudiant"
                        render={({ field }) => (
                            <SignaturePad id="edl-signature-sortie" value={field.value} onChange={field.onChange} invalid={!!errors.signature_etudiant} />
                        )}
                    />
                </Field>
                <Button type="submit" disabled={isSubmitting} className="w-full font-medium sm:w-fit">
                    {isSubmitting ? <Loader2 {...ICON} className="animate-spin" /> : <PenLine {...ICON} />}
                    Signer l’état des lieux de sortie
                </Button>
            </form>
        </Panneau>
    );
}
