import { router } from "@inertiajs/react";
import {
    CalendarClock,
    CalendarX,
    ChevronRight,
    Heart,
    Link2,
    Megaphone,
    Paperclip,
    Radio,
    RotateCcw,
    Search,
    Siren,
    UserRound,
} from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import {
    PaginationSimple,
    Panneau,
    Vide,
    type PaginationSimpleProps,
} from "@/components/etudiant/panneau";
import { ICON } from "@/components/layout/app-sidebar";
import { EtudiantLayout } from "@/components/layout/etudiant-layout";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { RichTextView } from "@/components/ui/rich-text-editor";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from "@/components/ui/sheet";
import { StatCard } from "@/components/ui/stat-card";
import { cn } from "@/lib/utils";

type Ton = "danger" | "warning" | "neutral";

type Option = { cle: string; libelle: string };

type Annonce = {
    id: string;
    titre: string;
    extrait: string;
    /** HTML de l'éditeur admin, rendu par RichTextView (schéma Tiptap). */
    contenu: string;
    priorite: { cle: string; libelle: string; ton: Ton };
    canaux: string[];
    publieeLe: string | null;
    nouvelle: boolean;
    expireLe: string | null;
    auteur: string;
    piecesJointes: { url: string; nom: string }[];
    /** L'étudiant a aimé l'annonce. */
    aime: boolean;
    jaimes: number;
    urlJaime: string;
};

type FiltresAnnonces = { canal: string; priorite: string; search: string };

type AnnoncesProps = {
    compteurs: { toutes: number; urgente: number; haute: number; normale: number; nouvelles: number };
    filtres: FiltresAnnonces;
    canaux: Option[];
    priorites: Option[];
    annonces: Annonce[];
    pagination: PaginationSimpleProps;
    /** Annonce ouverte par un lien partagé (`?annonce=`). */
    selection: Annonce | null;
    urls: { page: string };
};

/** Valeur « tous » des listes (Radix Select n'accepte pas de valeur vide). */
const TOUS = "tous";

/** Page « Annonces » : actualités de la résidence adressées à l'étudiant, filtrables par canal, priorité et recherche. */
export default function Annonces({
    compteurs,
    filtres,
    canaux,
    priorites,
    annonces,
    pagination,
    selection,
    urls,
}: AnnoncesProps) {
    const [saisie, setSaisie] = useState(filtres.search);
    // Annonce ouverte, retrouvée dans les props à chaque rechargement (compteur « J'aime » à jour).
    const [detailId, setDetailId] = useState<string | null>(selection?.id ?? null);
    const detail =
        annonces.find((a) => a.id === detailId) ?? (selection?.id === detailId ? selection : null);

    /** Recharge la liste avec les filtres modifiés (la pagination repart de la première page). */
    const filtrer = (changement: Partial<FiltresAnnonces>) => {
        const url = new URL(urls.page, window.location.origin);
        Object.entries({ ...filtres, ...changement }).forEach(([cle, valeur]) => {
            if (valeur && valeur !== TOUS) url.searchParams.set(cle, valeur);
        });
        router.get(url.pathname + url.search, {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    // Recherche après une pause de frappe ; l'état de la page (saisie, panneau ouvert) est conservé.
    useEffect(() => {
        if (saisie.trim() === filtres.search) return;
        const minuterie = window.setTimeout(() => filtrer({ search: saisie.trim() }), 400);
        return () => window.clearTimeout(minuterie);
    }, [saisie]);

    const filtreActif = Boolean(filtres.canal || filtres.priorite || filtres.search);

    const reinitialiser = () => {
        setSaisie("");
        router.get(urls.page, {}, { preserveState: true, preserveScroll: true, replace: true });
        toast.info("Filtres réinitialisés.");
    };

    return (
        <EtudiantLayout title="Annonces">
            <div className="flex flex-col gap-5">
                <header className="min-w-0">
                    <h1 className="page-title text-xl font-medium text-gray-900">Actualités</h1>
                    <p className="mt-2 text-sm text-muted">
                        Restez informé des dernières annonces de la résidence.
                    </p>
                </header>

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <StatCard label="Annonces en cours" value={compteurs.toutes} tone="blue" />
                    <StatCard label="Nouvelles (7 jours)" value={compteurs.nouvelles} tone="green" />
                    <StatCard
                        label="Urgentes"
                        value={compteurs.urgente}
                        tone={compteurs.urgente > 0 ? "red" : "neutral"}
                        highlight={compteurs.urgente > 0}
                        extra={compteurs.urgente > 0 ? "À lire" : undefined}
                    />
                    <StatCard label="Priorité haute" value={compteurs.haute} tone="amber" />
                </div>

                <Panneau
                    surtitre="Vie de la résidence"
                    titre="Fil d’actualité"
                    badge={`${pagination.total} annonce${pagination.total > 1 ? "s" : ""}`}
                >
                    <div className="mb-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-[12rem_12rem_1fr_auto]">
                        <Select
                            value={filtres.canal || TOUS}
                            onValueChange={(valeur) => filtrer({ canal: valeur })}
                        >
                            <SelectTrigger className="h-10" aria-label="Filtrer par canal">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={TOUS}>Tous les canaux</SelectItem>
                                {canaux.map((canal) => (
                                    <SelectItem key={canal.cle} value={canal.cle}>
                                        {canal.libelle}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={filtres.priorite || TOUS}
                            onValueChange={(valeur) => filtrer({ priorite: valeur })}
                        >
                            <SelectTrigger className="h-10" aria-label="Filtrer par priorité">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={TOUS}>Toutes les priorités</SelectItem>
                                {priorites.map((priorite) => (
                                    <SelectItem key={priorite.cle} value={priorite.cle}>
                                        {priorite.libelle}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Input
                            value={saisie}
                            onChange={(event) => setSaisie(event.target.value)}
                            placeholder="Rechercher une annonce…"
                            aria-label="Rechercher une annonce"
                            maxLength={100}
                            icon={<Search {...ICON} />}
                            className="h-10"
                        />
                        <Button
                            variant="outline"
                            onClick={reinitialiser}
                            disabled={!filtreActif && saisie === ""}
                            className="h-10"
                        >
                            <RotateCcw {...ICON} />
                            Réinitialiser
                        </Button>
                    </div>
                    {annonces.length === 0 ? (
                        <Vide icone={Megaphone}>
                            {filtreActif
                                ? "Aucune annonce ne correspond à vos critères de recherche."
                                : "Aucune annonce ne vous est adressée pour le moment."}
                        </Vide>
                    ) : (
                        <ul className="flex flex-col gap-3">
                            {annonces.map((annonce) => (
                                <CarteAnnonce
                                    key={annonce.id}
                                    annonce={annonce}
                                    ouvrir={() => setDetailId(annonce.id)}
                                />
                            ))}
                        </ul>
                    )}
                    <PaginationSimple {...pagination} />
                </Panneau>
            </div>

            <DetailAnnonce annonce={detail} page={urls.page} fermer={() => setDetailId(null)} />
        </EtudiantLayout>
    );
}

function CarteAnnonce({ annonce, ouvrir }: { annonce: Annonce; ouvrir: () => void }) {
    return (
        <li
            className={cn(
                "rounded-brand border p-3 md:p-4",
                annonce.priorite.cle === "urgente" && "border-l-4 border-l-red-500",
                annonce.priorite.cle === "haute" && "border-l-4 border-l-accent",
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <h3 className="text-sm font-medium text-gray-900">{annonce.titre}</h3>
                    <p className="mt-0.5 truncate text-xs text-muted">
                        {[annonce.publieeLe && `Publiée le ${annonce.publieeLe}`, annonce.auteur]
                            .filter(Boolean)
                            .join(" · ")}
                    </p>
                </div>
                <span className="flex shrink-0 flex-wrap justify-end gap-1.5">
                    {annonce.nouvelle && <Badge variant="success">Nouveau</Badge>}
                    <Badge variant={annonce.priorite.ton}>{annonce.priorite.libelle}</Badge>
                </span>
            </div>
            {annonce.extrait && (
                <p className="mt-2 line-clamp-3 text-sm text-muted">{annonce.extrait}</p>
            )}
            <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                <span className="flex flex-wrap items-center gap-3 text-xs text-muted">
                    {annonce.canaux.length > 0 && (
                        <span className="inline-flex items-center gap-1">
                            <Radio {...ICON} size={14} />
                            {annonce.canaux.join(" · ")}
                        </span>
                    )}
                    {annonce.piecesJointes.length > 0 && (
                        <span className="inline-flex items-center gap-1">
                            <Paperclip {...ICON} size={14} />
                            {annonce.piecesJointes.length} pièce
                            {annonce.piecesJointes.length > 1 ? "s jointes" : " jointe"}
                        </span>
                    )}
                    {annonce.expireLe && (
                        <span className="inline-flex items-center gap-1">
                            <CalendarX {...ICON} size={14} />
                            Jusqu’au {annonce.expireLe}
                        </span>
                    )}
                </span>
                <span className="flex items-center gap-1">
                    <BoutonJaime annonce={annonce} />
                    <Button variant="ghost" size="sm" onClick={ouvrir} className="text-primary">
                        Lire l’annonce
                        <ChevronRight {...ICON} size={16} />
                    </Button>
                </span>
            </div>
        </li>
    );
}

function DetailAnnonce({
    annonce,
    page,
    fermer,
}: {
    annonce: Annonce | null;
    page: string;
    fermer: () => void;
}) {
    const partager = async (annonce: Annonce) => {
        const url = new URL(page, window.location.origin);
        url.searchParams.set("annonce", annonce.id);
        try {
            if (navigator.share) {
                await navigator.share({ title: annonce.titre, url: url.toString() });
                return;
            }
            await navigator.clipboard.writeText(url.toString());
            toast.success("Lien de l’annonce copié.");
        } catch (erreur) {
            // Partage annulé par l'utilisateur : rien à signaler.
            if (erreur instanceof DOMException && erreur.name === "AbortError") return;
            toast.error("Impossible de copier le lien de l’annonce.");
        }
    };

    return (
        <Sheet open={annonce !== null} onOpenChange={(ouvert) => !ouvert && fermer()}>
            <SheetContent className="w-full overflow-y-auto sm:max-w-lg">
                {annonce && (
                    <>
                        <SheetHeader>
                            <SheetTitle className="pr-6">{annonce.titre}</SheetTitle>
                            <SheetDescription asChild>
                                <div className="flex flex-wrap gap-2">
                                    <Badge variant={annonce.priorite.ton}>
                                        {annonce.priorite.cle === "urgente" && <Siren {...ICON} size={14} />}
                                        {annonce.priorite.libelle}
                                    </Badge>
                                    {annonce.nouvelle && <Badge variant="success">Nouveau</Badge>}
                                </div>
                            </SheetDescription>
                        </SheetHeader>
                        <div className="flex flex-col gap-4 px-4 pb-6">
                            <ul className="flex flex-col gap-1.5 text-xs text-muted">
                                {annonce.publieeLe && (
                                    <li className="inline-flex items-center gap-1.5">
                                        <CalendarClock {...ICON} size={14} />
                                        Publiée le {annonce.publieeLe}
                                    </li>
                                )}
                                <li className="inline-flex items-center gap-1.5">
                                    <UserRound {...ICON} size={14} />
                                    {annonce.auteur}
                                </li>
                                {annonce.canaux.length > 0 && (
                                    <li className="inline-flex items-center gap-1.5">
                                        <Radio {...ICON} size={14} />
                                        Diffusée par {annonce.canaux.join(", ")}
                                    </li>
                                )}
                                {annonce.expireLe && (
                                    <li className="inline-flex items-center gap-1.5">
                                        <CalendarX {...ICON} size={14} />
                                        Valable jusqu’au {annonce.expireLe}
                                    </li>
                                )}
                            </ul>
                            <RichTextView
                                html={annonce.contenu}
                                className="rounded-brand bg-surface p-3"
                            />
                            {annonce.piecesJointes.length > 0 && (
                                <section>
                                    <h3 className="mb-1.5 text-sm font-medium text-gray-900">
                                        Pièces jointes
                                    </h3>
                                    <ul className="flex flex-col gap-1">
                                        {annonce.piecesJointes.map((piece) => (
                                            <li key={piece.url}>
                                                <a
                                                    href={piece.url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex max-w-full items-center gap-1.5 text-sm text-primary hover:underline"
                                                >
                                                    <Paperclip {...ICON} size={16} className="shrink-0" />
                                                    <span className="truncate">{piece.nom}</span>
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            )}
                            <div className="flex flex-wrap items-center gap-2">
                                <BoutonJaime annonce={annonce} contour />
                                <Button variant="outline" onClick={() => partager(annonce)}>
                                    <Link2 {...ICON} />
                                    Partager le lien
                                </Button>
                            </div>
                        </div>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}

/** « J'aime » de l'étudiant sur une annonce (route etudiants.annonce.jaime) ; le compteur revient du serveur. */
function BoutonJaime({ annonce, contour = false }: { annonce: Annonce; contour?: boolean }) {
    const [envoi, setEnvoi] = useState(false);
    const basculer = () =>
        router.post(annonce.urlJaime, {}, {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setEnvoi(true),
            onFinish: () => setEnvoi(false),
        });

    return (
        <Button
            variant={contour ? "outline" : "ghost"}
            size={contour ? "default" : "sm"}
            onClick={basculer}
            disabled={envoi}
            aria-pressed={annonce.aime}
            title={annonce.aime ? "Retirer mon « J’aime »" : "J’aime"}
            className={cn(annonce.aime ? "text-red-600" : "text-muted")}
        >
            <Heart {...ICON} className={cn(annonce.aime && "fill-current")} />
            J’aime
            {annonce.jaimes > 0 && <span className="tabular-nums">· {annonce.jaimes}</span>}
        </Button>
    );
}
