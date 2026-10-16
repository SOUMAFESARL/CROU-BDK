import {
    CalendarClock,
    CreditCard,
    Download,
    FileText,
    Globe,
    Info,
    ReceiptText,
    TriangleAlert,
    Wallet,
} from "lucide-react";
import { useState } from "react";
import {
    Filtres,
    Ligne,
    Liste,
    Notice,
    PaginationSimple,
    Panneau,
    Vide,
    type PaginationSimpleProps,
} from "@/components/etudiant/panneau";
import {
    PaiementLoyerDialog,
    type LoyerReglable,
    type Reseau,
} from "@/components/etudiant/paiement-loyer";
import { ICON } from "@/components/layout/app-sidebar";
import { EtudiantLayout } from "@/components/layout/etudiant-layout";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { StatCard, type KpiTone } from "@/components/ui/stat-card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { cn } from "@/lib/utils";

type Ton = "success" | "warning" | "danger" | "info" | "neutral";
type Statut = { libelle: string; ton: Ton };

type Situation = {
    totalPaye: string;
    reste: string;
    penalites: string | null;
    recouvrement: number | null;
    nbImpayes: number;
    nbRetards: number;
    prochaine: { periode: string; montant: string; limite: string | null } | null;
    etat: { libelle: string; ton: KpiTone };
};

type Loyer = {
    id: string;
    periode: string;
    montant: string;
    paye: string;
    solde: string;
    limite: string | null;
    statut: Statut;
};

type Paiement = {
    id: string;
    periode: string | null;
    date: string | null;
    reference: string | null;
    methode: string | null;
    montant: string;
    statut: Statut;
    recu: string | null;
};

type PaiementEnLigne = {
    id: string;
    date: string | null;
    reseau: string;
    montant: string;
    statut: Statut;
    url: string;
};

type MesPaiementsProps = {
    situation: Situation;
    reglement: {
        reseaux: Reseau[];
        telephone: string;
        indisponible: string | null;
        loyer: LoyerReglable | null;
    };
    loyers: Loyer[];
    statut: string | null;
    statuts: { code: string; libelle: string }[];
    paiements: Paiement[];
    pagination: PaginationSimpleProps;
    enLigne: PaiementEnLigne[];
    urls: { payer: string; page: string; documents: string };
};

const pluriel = (n: number, singulier: string, plurielForme: string) =>
    `${n} ${n > 1 ? plurielForme : singulier}`;

/** URL de la page avec un autre filtre de statut (la pagination repart de la première page). */
function avecStatut(page: string, statut: string | null) {
    const url = new URL(page, window.location.origin);
    if (statut) url.searchParams.set("statut", statut);
    return url.pathname + url.search;
}

/** Page « Mes paiements » : situation des loyers, paiement en ligne, historique et reçus. */
export default function MesPaiements({
    situation,
    reglement,
    loyers,
    statut,
    statuts,
    paiements,
    pagination,
    enLigne,
    urls,
}: MesPaiementsProps) {
    const [paiementOuvert, setPaiementOuvert] = useState(false);
    const payable = reglement.loyer && !reglement.indisponible;

    return (
        <EtudiantLayout title="Mes paiements">
            <div className="flex flex-col gap-5">
                <header className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                        <h1 className="page-title text-xl font-medium text-gray-900">
                            Mes paiements
                        </h1>
                        <p className="mt-2 text-sm text-muted">
                            Suivez vos loyers, réglez en ligne et téléchargez vos reçus.
                        </p>
                    </div>
                    {payable && (
                        <Button onClick={() => setPaiementOuvert(true)} className="font-medium">
                            <CreditCard {...ICON} />
                            Payer mon loyer
                        </Button>
                    )}
                </header>

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <StatCard
                        label="Total payé"
                        value={situation.totalPaye}
                        tone="green"
                        extra={
                            situation.recouvrement !== null
                                ? `${situation.recouvrement} % réglé`
                                : undefined
                        }
                    />
                    <StatCard
                        label="Reste à payer"
                        value={situation.reste}
                        tone={situation.nbImpayes > 0 ? "red" : "neutral"}
                        extra={pluriel(situation.nbImpayes, "impayé", "impayés")}
                        highlight={situation.nbRetards > 0}
                    />
                    <StatCard
                        label="Prochaine échéance"
                        value={situation.prochaine?.periode ?? "Aucune"}
                        tone="blue"
                        extra={situation.prochaine?.montant}
                    />
                    <StatCard
                        label="Statut du compte"
                        value={situation.etat.libelle}
                        tone={situation.etat.ton}
                    />
                </div>

                {situation.nbRetards > 0 && (
                    <Notice icone={TriangleAlert} className="bg-red-50 text-red-700">
                        {pluriel(situation.nbRetards, "loyer est", "loyers sont")} en retard de
                        paiement
                        {situation.penalites
                            ? `, avec ${situation.penalites} de pénalités`
                            : ""}
                        . Réglez-les au plus vite pour éviter de nouvelles pénalités.
                    </Notice>
                )}

                <div className="grid gap-5 lg:grid-cols-2">
                    <Panneau surtitre="Paiement en ligne" titre="Payer mon loyer">
                        {reglement.loyer && !reglement.indisponible ? (
                            <div className="flex flex-col gap-3">
                                <dl className="divide-y rounded-brand border">
                                    {(
                                        [
                                            ["Chambre", reglement.loyer.chambre],
                                            ["Période à régler", reglement.loyer.periode],
                                            ["Loyer facturé", reglement.loyer.montant],
                                            ["Pénalités", reglement.loyer.penalites],
                                            ["Déjà réglé", reglement.loyer.dejaPaye],
                                        ] as [string, string | null][]
                                    )
                                        .filter(([, valeur]) => valeur)
                                        .map(([libelle, valeur]) => (
                                            <div
                                                key={libelle}
                                                className="flex justify-between gap-4 px-3 py-2.5 text-sm"
                                            >
                                                <dt className="text-muted">{libelle}</dt>
                                                <dd className="font-medium text-gray-900">
                                                    {valeur}
                                                </dd>
                                            </div>
                                        ))}
                                    <div className="flex items-center justify-between gap-4 bg-soft px-3 py-3">
                                        <dt className="text-sm font-medium text-primary">
                                            Solde restant
                                        </dt>
                                        <dd className="text-lg font-bold text-primary tabular-nums">
                                            {reglement.loyer.solde}
                                        </dd>
                                    </div>
                                </dl>
                                <p className="text-xs text-muted">
                                    Le loyer le plus ancien est proposé en premier. Vous pouvez
                                    régler tout ou partie du solde avec{" "}
                                    {reglement.reseaux.map((r) => r.nom).join(", ")}.
                                </p>
                                <Button
                                    onClick={() => setPaiementOuvert(true)}
                                    className="w-full font-medium sm:w-fit"
                                >
                                    <CreditCard {...ICON} />
                                    Payer {reglement.loyer.solde}
                                </Button>
                            </div>
                        ) : (
                            <Vide icone={Info}>{reglement.indisponible}</Vide>
                        )}
                    </Panneau>

                    <Panneau
                        surtitre="Suivi"
                        titre="Mes paiements en ligne"
                        badge={pluriel(enLigne.length, "récent", "récents")}
                    >
                        {enLigne.length === 0 ? (
                            <Vide icone={Globe}>
                                Aucun paiement en ligne pour le moment.
                            </Vide>
                        ) : (
                            <Liste>
                                {enLigne.map((p) => (
                                    <Ligne
                                        key={p.id}
                                        href={p.url}
                                        icone={Globe}
                                        ton={p.statut.ton === "success" ? "vert" : undefined}
                                        titre={`${p.montant} · ${p.reseau}`}
                                        detail={p.date}
                                        complement={
                                            <Badge
                                                variant={p.statut.ton}
                                                className="max-w-36 truncate"
                                                title={p.statut.libelle}
                                            >
                                                {p.statut.libelle}
                                            </Badge>
                                        }
                                    />
                                ))}
                            </Liste>
                        )}
                    </Panneau>
                </div>

                <Panneau
                    surtitre="Facturation"
                    titre="Mes loyers"
                    badge={pluriel(loyers.length, "loyer", "loyers")}
                >
                    {loyers.length === 0 ? (
                        <Vide icone={Wallet}>
                            Aucun loyer n’a encore été établi pour votre chambre.
                        </Vide>
                    ) : (
                        <>
                            <Liste className="md:hidden">
                                {loyers.map((loyer) => (
                                    <Ligne
                                        key={loyer.id}
                                        icone={CalendarClock}
                                        ton={loyer.statut.ton === "success" ? "vert" : "orange"}
                                        titre={loyer.periode}
                                        detail={`Payé ${loyer.paye} · Reste ${loyer.solde}`}
                                        complement={
                                            <Badge variant={loyer.statut.ton}>
                                                {loyer.statut.libelle}
                                            </Badge>
                                        }
                                    />
                                ))}
                            </Liste>
                            <div className="hidden overflow-hidden rounded-brand border md:block">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Période</TableHead>
                                            <TableHead>Échéance</TableHead>
                                            <TableHead className="text-right">Montant</TableHead>
                                            <TableHead className="text-right">Payé</TableHead>
                                            <TableHead className="text-right">Reste</TableHead>
                                            <TableHead>Statut</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {loyers.map((loyer) => (
                                            <TableRow key={loyer.id}>
                                                <TableCell className="font-medium">
                                                    {loyer.periode}
                                                </TableCell>
                                                <TableCell className="text-muted">
                                                    {loyer.limite ?? "—"}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {loyer.montant}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {loyer.paye}
                                                </TableCell>
                                                <TableCell className="text-right font-medium tabular-nums">
                                                    {loyer.solde}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={loyer.statut.ton}>
                                                        {loyer.statut.libelle}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </>
                    )}
                </Panneau>

                <Panneau
                    id="historique"
                    surtitre="Règlements enregistrés"
                    titre="Historique des paiements"
                    badge={pluriel(pagination.total, "paiement", "paiements")}
                >
                    <Filtres
                        libelle="Filtrer les paiements par statut"
                        actif={statut ?? "tous"}
                        filtres={[
                            { cle: "tous", libelle: "Tous", href: avecStatut(urls.page, null) },
                            ...statuts.map((s) => ({
                                cle: s.code,
                                libelle: s.libelle,
                                href: avecStatut(urls.page, s.code),
                            })),
                        ]}
                    />
                    {paiements.length === 0 ? (
                        <Vide icone={ReceiptText}>
                            {statut
                                ? "Aucun paiement avec ce statut."
                                : "Aucun paiement enregistré pour le moment."}
                        </Vide>
                    ) : (
                        <>
                            <Liste className="md:hidden">
                                {paiements.map((p) => (
                                    <li
                                        key={p.id}
                                        className="flex min-h-14 items-center gap-3 px-2 py-2.5"
                                    >
                                        <span className="min-w-0 flex-1">
                                            <strong className="block truncate text-sm font-medium text-gray-900">
                                                {p.montant}
                                                {p.periode ? ` · ${p.periode}` : ""}
                                            </strong>
                                            <small className="block truncate text-xs text-muted">
                                                {[p.date, p.methode, p.reference]
                                                    .filter(Boolean)
                                                    .join(" · ")}
                                            </small>
                                        </span>
                                        <Badge variant={p.statut.ton}>{p.statut.libelle}</Badge>
                                        <LienRecu url={p.recu} />
                                    </li>
                                ))}
                            </Liste>
                            <div className="hidden overflow-hidden rounded-brand border md:block">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Date</TableHead>
                                            <TableHead>Période</TableHead>
                                            <TableHead>Référence</TableHead>
                                            <TableHead>Moyen</TableHead>
                                            <TableHead className="text-right">Montant</TableHead>
                                            <TableHead>Statut</TableHead>
                                            <TableHead>
                                                <span className="sr-only">Reçu</span>
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {paiements.map((p) => (
                                            <TableRow key={p.id}>
                                                <TableCell>{p.date ?? "—"}</TableCell>
                                                <TableCell className="max-w-48 truncate">
                                                    {p.periode ?? "—"}
                                                </TableCell>
                                                <TableCell className="font-mono text-xs text-muted">
                                                    {p.reference ?? "—"}
                                                </TableCell>
                                                <TableCell>{p.methode ?? "—"}</TableCell>
                                                <TableCell className="text-right font-medium tabular-nums">
                                                    {p.montant}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={p.statut.ton}>
                                                        {p.statut.libelle}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="w-12 py-1">
                                                    <LienRecu url={p.recu} />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </>
                    )}
                    <PaginationSimple {...pagination} />
                    <a
                        href={urls.documents}
                        className="mt-3 inline-flex w-fit items-center gap-1.5 text-sm font-medium text-primary hover:underline"
                    >
                        <FileText {...ICON} size={16} />
                        Tous mes reçus dans « Mes documents »
                    </a>
                </Panneau>
            </div>

            {reglement.loyer && payable && (
                <PaiementLoyerDialog
                    ouverte={paiementOuvert}
                    fermer={() => setPaiementOuvert(false)}
                    url={urls.payer}
                    loyer={reglement.loyer}
                    reseaux={reglement.reseaux}
                    telephone={reglement.telephone}
                />
            )}
        </EtudiantLayout>
    );
}

function LienRecu({ url }: { url: string | null }) {
    if (!url) return <span className="inline-block size-9 shrink-0" aria-hidden />;

    return (
        <a
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            title="Télécharger le reçu"
            aria-label="Télécharger le reçu"
            className={cn(
                "grid size-9 shrink-0 place-items-center rounded-brand text-muted hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none",
            )}
        >
            <Download {...ICON} />
        </a>
    );
}
