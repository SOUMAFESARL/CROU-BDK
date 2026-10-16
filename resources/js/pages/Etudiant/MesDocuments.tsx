import {
    Calculator,
    ChevronRight,
    ClipboardCheck,
    Download,
    ExternalLink,
    FilePenLine,
    FileText,
    Info,
    Paperclip,
    Receipt,
    type LucideIcon,
} from "lucide-react";
import type { ReactNode } from "react";
import { ICON } from "@/components/layout/app-sidebar";
import { EtudiantLayout } from "@/components/layout/etudiant-layout";
import { Badge } from "@/components/ui/badge";
import { StatCard } from "@/components/ui/stat-card";
import { cn } from "@/lib/utils";

type MesDocumentsProps = {
    contrat: { pdfUrl: string; approbationUrl: string } | null;
    etatsDesLieux: {
        id: string;
        type: "entree" | "sortie";
        date: string | null;
        url: string;
    }[];
    recus: {
        id: string;
        restitution: boolean;
        date: string | null;
        numero: string;
        montant: string;
        url: string;
    }[];
    justificatifs: {
        id: string;
        type: string;
        date: string | null;
        statut: string;
        statutLibelle: string;
        url: string;
    }[];
    decomptes: { id: string; date: string | null; url: string }[];
};

const RUBRIQUES = [
    ["contrat", "Contrat"],
    ["etats-des-lieux", "États des lieux"],
    ["recus", "Reçus"],
    ["justificatifs", "Justificatifs"],
    ["decomptes", "Décomptes"],
] as const;

const STATUT_BADGE: Record<
    string,
    "success" | "warning" | "danger" | "neutral"
> = {
    valide: "success",
    en_verification: "warning",
    rejete: "danger",
};

/** Page « Mes documents » : contrat, états des lieux, reçus, justificatifs et décomptes de l'étudiant connecté. */
export default function MesDocuments({
    contrat,
    etatsDesLieux,
    recus,
    justificatifs,
    decomptes,
}: MesDocumentsProps) {
    const enVerification = justificatifs.filter(
        (item) => item.statut === "en_verification",
    ).length;

    return (
        <EtudiantLayout title="Mes documents">
            <header className="mb-5">
                <h1 className="page-title text-xl font-medium text-gray-900">
                    Mes documents
                </h1>
                <p className="mt-1 text-sm text-muted">
                    Consultez vos contrats, états des lieux, reçus et
                    justificatifs.
                </p>
            </header>

            <div className="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <a
                    href="#contrat"
                    className="rounded-md focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                >
                    <StatCard
                        label="Contrat"
                        value={contrat ? "Disponible" : "Aucun"}
                        tone="green"
                    />
                </a>
                <a
                    href="#etats-des-lieux"
                    className="rounded-md focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                >
                    <StatCard
                        label="États des lieux"
                        value={etatsDesLieux.length}
                        tone="blue"
                    />
                </a>
                <a
                    href="#recus"
                    className="rounded-md focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                >
                    <StatCard label="Reçus" value={recus.length} tone="amber" />
                </a>
                <a
                    href="#justificatifs"
                    className="rounded-md focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                >
                    <StatCard
                        label="Justificatifs"
                        value={justificatifs.length}
                        tone="plum"
                        extra={
                            enVerification
                                ? `${enVerification} en vérification`
                                : undefined
                        }
                        highlight={enVerification > 0}
                    />
                </a>
            </div>

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5">
                {/* Accès rapide aux rubriques : barre défilante, collée sous la barre du haut sur mobile. */}
                <nav
                    aria-label="Rubriques"
                    className="sticky top-16 z-[5] -mx-4 overflow-x-auto bg-surface px-4 py-2 md:static md:mx-0 md:px-0"
                >
                    <ul className="flex gap-2">
                        {RUBRIQUES.map(([ancre, libelle]) => (
                            <li key={ancre}>
                                <a
                                    href={`#${ancre}`}
                                    className="inline-flex h-9 items-center rounded-full border bg-white px-3.5 text-sm whitespace-nowrap text-muted hover:border-secondary hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                                >
                                    {libelle}
                                </a>
                            </li>
                        ))}
                    </ul>
                </nav>

                <Section
                    id="contrat"
                    surtitre="Logement"
                    titre="Contrat de logement"
                >
                    {contrat ? (
                        <Liste>
                            <Ligne
                                href={contrat.pdfUrl}
                                nouvelOnglet
                                icone={FileText}
                                ton="vert"
                                titre="Consulter mon contrat"
                                detail="PDF · s’ouvre dans un nouvel onglet"
                                fin={ExternalLink}
                            />
                            <Ligne
                                href={contrat.approbationUrl}
                                icone={FilePenLine}
                                titre="Approbation de mon contrat"
                                detail="Lire et approuver les conditions"
                                fin={ChevronRight}
                            />
                        </Liste>
                    ) : (
                        <Vide icone={FileText}>Aucun contrat disponible.</Vide>
                    )}
                </Section>

                <Section
                    id="etats-des-lieux"
                    surtitre="Entrée et sortie"
                    titre="États des lieux"
                    total={etatsDesLieux.length}
                >
                    {etatsDesLieux.length ? (
                        <Liste>
                            {etatsDesLieux.map((item) => (
                                <Ligne
                                    key={item.id}
                                    href={item.url}
                                    nouvelOnglet
                                    icone={ClipboardCheck}
                                    titre={`État des lieux ${item.type === "entree" ? "d’entrée" : "de sortie"}`}
                                    detail={`${item.date ?? "Date non renseignée"} · PDF`}
                                    fin={ExternalLink}
                                />
                            ))}
                        </Liste>
                    ) : (
                        <Vide icone={ClipboardCheck}>
                            Aucun état des lieux enregistré.
                        </Vide>
                    )}
                </Section>

                <Section
                    id="recus"
                    surtitre="Paiements"
                    titre="Reçus de paiement et de restitution"
                    total={recus.length}
                >
                    {recus.length ? (
                        <Liste>
                            {recus.map((item) => (
                                <Ligne
                                    key={item.id}
                                    href={item.url}
                                    icone={Receipt}
                                    ton={item.restitution ? undefined : "vert"}
                                    titre={`${item.restitution ? "Restitution" : "Paiement"} · ${item.date ?? "date non renseignée"}`}
                                    detail={`N° ${item.numero}`}
                                    complement={
                                        <span className="text-sm font-medium whitespace-nowrap text-gray-900 tabular-nums">
                                            {item.montant}
                                        </span>
                                    }
                                    fin={Download}
                                />
                            ))}
                        </Liste>
                    ) : (
                        <Vide icone={Receipt}>
                            Aucun paiement validé ni restitution enregistré.
                        </Vide>
                    )}
                </Section>

                <Section
                    id="justificatifs"
                    surtitre="Dossier"
                    titre="Pièces justificatives et documents déposés"
                    total={justificatifs.length}
                >
                    {justificatifs.length ? (
                        <Liste>
                            {justificatifs.map((item) => (
                                <Ligne
                                    key={item.id}
                                    href={item.url}
                                    nouvelOnglet
                                    icone={Paperclip}
                                    titre={item.type}
                                    detail={`Déposé le ${item.date ?? "—"}`}
                                    complement={
                                        <Badge
                                            variant={
                                                STATUT_BADGE[item.statut] ??
                                                "neutral"
                                            }
                                        >
                                            {item.statutLibelle}
                                        </Badge>
                                    }
                                />
                            ))}
                        </Liste>
                    ) : (
                        <Vide icone={Paperclip}>Aucun document déposé.</Vide>
                    )}
                </Section>

                <Section
                    id="decomptes"
                    surtitre="Départ"
                    titre="Décomptes de sortie"
                    total={decomptes.length}
                >
                    {decomptes.length ? (
                        <Liste>
                            {decomptes.map((item) => (
                                <Ligne
                                    key={item.id}
                                    href={item.url}
                                    icone={Calculator}
                                    titre={`Décompte du ${item.date ?? "—"}`}
                                    detail="Retenues et restitution de caution · PDF"
                                    fin={Download}
                                />
                            ))}
                        </Liste>
                    ) : (
                        <Vide icone={Calculator}>Aucun décompte établi.</Vide>
                    )}
                    <div className="mt-4 flex items-start gap-2.5 rounded-brand bg-soft p-3 text-sm text-primary [&_svg]:mt-0.5 [&_svg]:shrink-0">
                        <Info {...ICON} />
                        <span>
                            Le décompte présente les retenues et la restitution
                            de caution. Il ne remplace pas un quitus de sortie
                            délivré par l’administration.
                        </span>
                    </div>
                </Section>
            </div>
        </EtudiantLayout>
    );
}

function Section({
    id,
    surtitre,
    titre,
    total,
    children,
}: {
    id: string;
    surtitre: string;
    titre: string;
    total?: number;
    children: ReactNode;
}) {
    return (
        // scroll-mt : l'ancre ne passe pas sous la barre du haut et la barre des rubriques.
        <section
            id={id}
            aria-labelledby={`${id}-titre`}
            className="scroll-mt-32 rounded-brand border bg-white p-4 md:scroll-mt-20 md:p-5"
        >
            <div className="mb-3 flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-xs text-muted">{surtitre}</p>
                    <h2
                        id={`${id}-titre`}
                        className="text-base font-medium text-gray-900"
                    >
                        {titre}
                    </h2>
                </div>
                {total !== undefined && (
                    <span className="rounded-full bg-surface px-2.5 py-0.5 text-xs font-medium text-muted tabular-nums">
                        {total}
                    </span>
                )}
            </div>
            {children}
        </section>
    );
}

function Liste({ children }: { children: ReactNode }) {
    return <ul className="-mx-2 divide-y">{children}</ul>;
}

type LigneProps = {
    href: string;
    icone: LucideIcon;
    titre: string;
    detail: string;
    ton?: "vert";
    nouvelOnglet?: boolean;
    complement?: ReactNode;
    fin?: LucideIcon;
};

function Ligne({
    href,
    icone: Icone,
    titre,
    detail,
    ton,
    nouvelOnglet,
    complement,
    fin: Fin,
}: LigneProps) {
    return (
        <li>
            <a
                href={href}
                {...(nouvelOnglet
                    ? { target: "_blank", rel: "noopener noreferrer" }
                    : {})}
                className="group flex min-h-14 items-center gap-3 rounded-brand px-2 py-2.5 hover:bg-surface focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
            >
                <span
                    className={cn(
                        "grid size-10 shrink-0 place-items-center rounded-brand",
                        ton === "vert"
                            ? "bg-soft text-primary"
                            : "bg-surface text-muted",
                    )}
                >
                    <Icone {...ICON} />
                </span>
                <span className="min-w-0 flex-1">
                    <strong className="block truncate text-sm font-medium text-gray-900 group-hover:text-primary">
                        {titre}
                    </strong>
                    <small className="block truncate text-xs text-muted">
                        {detail}
                    </small>
                </span>
                {complement}
                {Fin && (
                    <Fin
                        {...ICON}
                        className="shrink-0 text-muted group-hover:text-primary"
                    />
                )}
            </a>
        </li>
    );
}

function Vide({
    icone: Icone,
    children,
}: {
    icone: LucideIcon;
    children: ReactNode;
}) {
    return (
        <p className="flex items-center gap-2.5 rounded-brand bg-surface p-3 text-sm text-muted">
            <Icone {...ICON} className="shrink-0" />
            <span>{children}</span>
        </p>
    );
}
