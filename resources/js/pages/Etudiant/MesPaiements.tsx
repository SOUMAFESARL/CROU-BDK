import { CalendarClock, CreditCard, Download, Eye, FileText, ReceiptText, Wallet } from "lucide-react";
import { useState } from "react";
import {
    Filtres,
    IconeLigne,
    Liste,
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

type Ton = "success" | "warning" | "danger" | "info" | "neutral";
type Statut = { libelle: string; ton: Ton };

type Loyer = {
    /** Mois « aaaa-mm ». */
    id: string;
    periode: string;
    montant: string | null;
    limite: string | null;
    datePaiement: string | null;
    paye: boolean;
    /** Payé par une avance (versée avant l'attribution de la chambre). */
    avance: boolean;
    statut: Statut;
    quittance: DocumentPdf | null;
};

/** Document PDF à voir (nouvel onglet) ou télécharger. */
type DocumentPdf = { voir: string; telecharger: string };

type Paiement = {
    id: string;
    periode: string | null;
    date: string | null;
    reference: string | null;
    /** Type de paiement (Loyer, Avance, Caution…). */
    type: string | null;
    methode: string | null;
    montant: string;
    statut: Statut;
    /** Même reçu que l'administration, pour un paiement validé ou remboursé. */
    recu: DocumentPdf | null;
};

type Onglet = "loyers" | "historique";

type MesPaiementsProps = {
    /** Onglet ouvert (`?onglet=`), « loyers » par défaut. */
    onglet: Onglet;
    reglement: {
        reseaux: Reseau[];
        telephone: string;
        indisponible: string | null;
        loyer: LoyerReglable | null;
    };
    /** Année de la campagne du séjour (« 2026-2027 »). */
    campagne: string | null;
    /** Mois écoulés du séjour (10 au plus), le plus récent en premier. */
    loyers: Loyer[];
    statut: string | null;
    statuts: { code: string; libelle: string }[];
    paiements: Paiement[];
    pagination: PaginationSimpleProps;
    urls: { payer: string; page: string; documents: string };
};

const pluriel = (n: number, singulier: string, plurielForme: string) =>
    `${n} ${n > 1 ? plurielForme : singulier}`;

/** URL de l’historique avec un autre filtre de statut (la pagination repart de la première page). */
function avecStatut(page: string, statut: string | null) {
    const url = new URL(page, window.location.origin);
    url.searchParams.set("onglet", "historique");
    if (statut) url.searchParams.set("statut", statut);
    return url.pathname + url.search;
}

/**
 * Page « Mes loyers », en deux onglets : « Loyers » (mois écoulés de la campagne, payés avec quittance
 * à voir ou télécharger, ou non payés) et « Historique » (règlements enregistrés et reçus).
 */
export default function MesPaiements({
    onglet,
    reglement,
    campagne,
    loyers,
    statut,
    statuts,
    paiements,
    pagination,
    urls,
}: MesPaiementsProps) {
    const [paiementOuvert, setPaiementOuvert] = useState(false);
    const [actif, setActif] = useState<Onglet>(onglet);
    const payable = reglement.loyer && !reglement.indisponible;
    const impayes = loyers.filter((loyer) => !loyer.paye);
    const enRetard = impayes.some((loyer) => loyer.statut.ton === "danger");

    // L'onglet ouvert reste dans l'URL (?onglet=…) pour le partage et le retour arrière.
    const changerOnglet = (valeur: string) => {
        setActif(valeur as Onglet);
        const url = new URL(window.location.href);
        url.searchParams.set("onglet", valeur);
        window.history.replaceState(window.history.state, "", url);
    };

    return (
        <EtudiantLayout title="Mes loyers">
            <div className="flex flex-col gap-5">
                <header className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                        <h1 className="page-title text-xl font-medium text-gray-900">
                            Mes loyers
                        </h1>
                        <p className="mt-2 text-sm text-muted">
                            Suivez vos loyers, réglez en ligne et téléchargez vos quittances et reçus.
                        </p>
                    </div>
                    {payable && (
                        <Button onClick={() => setPaiementOuvert(true)} className="font-medium">
                            <CreditCard {...ICON} />
                            Payer mon loyer
                        </Button>
                    )}
                </header>

                <Tabs value={actif} onValueChange={changerOnglet}>
                    <TabsList aria-label="Mes loyers">
                        <TabsTrigger value="loyers">
                            <Wallet {...ICON} aria-hidden /> Loyers
                            {impayes.length > 0 && (
                                <Badge
                                    variant={enRetard ? "danger" : "warning"}
                                    title={pluriel(impayes.length, "mois non payé", "mois non payés")}
                                >
                                    {impayes.length}
                                </Badge>
                            )}
                        </TabsTrigger>
                        <TabsTrigger value="historique">
                            <ReceiptText {...ICON} aria-hidden /> Historique
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="loyers">
                        <Panneau
                            surtitre={campagne ? `Campagne ${campagne}` : "Facturation"}
                            titre="Loyers du séjour"
                            badge={pluriel(loyers.length, "mois", "mois")}
                        >
                            {loyers.length === 0 ? (
                                <Vide icone={Wallet}>
                                    Aucun mois de loyer pour le moment : votre séjour n’a pas encore commencé.
                                </Vide>
                            ) : (
                                <>
                                    <Liste className="md:hidden">
                                        {loyers.map((loyer) => (
                                            <li
                                                key={loyer.id}
                                                className="flex min-h-14 items-center gap-3 px-2 py-2.5"
                                            >
                                                <IconeLigne
                                                    icone={CalendarClock}
                                                    ton={loyer.paye ? "vert" : "orange"}
                                                />
                                                <span className="min-w-0 flex-1">
                                                    <strong className="block truncate text-sm font-medium text-gray-900">
                                                        {loyer.periode}
                                                    </strong>
                                                    <small className="block truncate text-xs text-muted">
                                                        {loyer.paye
                                                            ? [
                                                                  loyer.montant,
                                                                  loyer.datePaiement &&
                                                                      `payé le ${loyer.datePaiement}`,
                                                                  loyer.avance && "avance",
                                                              ]
                                                                  .filter(Boolean)
                                                                  .join(" · ")
                                                            : loyer.montant}
                                                    </small>
                                                </span>
                                                <Badge variant={loyer.statut.ton}>{loyer.statut.libelle}</Badge>
                                                <ActionsDocument
                                                    pdf={loyer.quittance}
                                                    libelle={`la quittance de ${loyer.periode}`}
                                                />
                                            </li>
                                        ))}
                                    </Liste>
                                    <div className="hidden overflow-hidden rounded-brand border md:block">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Mois</TableHead>
                                                    <TableHead>Échéance</TableHead>
                                                    <TableHead className="text-right">Montant</TableHead>
                                                    <TableHead>Payé le</TableHead>
                                                    <TableHead>Statut</TableHead>
                                                    <TableHead className="text-right">Quittance</TableHead>
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
                                                            {loyer.montant ?? "—"}
                                                        </TableCell>
                                                        <TableCell className="text-muted">
                                                            {loyer.datePaiement ?? "—"}
                                                            {loyer.avance && (
                                                                <span className="block text-xs">Avance</span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge variant={loyer.statut.ton}>
                                                                {loyer.statut.libelle}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell className="py-1">
                                                            <div className="flex justify-end">
                                                                <ActionsDocument
                                                                    pdf={loyer.quittance}
                                                                    libelle={`la quittance de ${loyer.periode}`}
                                                                />
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                    {impayes.length > 0 && payable && (
                                        <p className="mt-3 text-xs text-muted">
                                            Réglez vos loyers non payés avec « Payer mon loyer » : le plus ancien
                                            est proposé en premier.
                                        </p>
                                    )}
                                </>
                            )}
                        </Panneau>
                    </TabsContent>

                    <TabsContent value="historique">
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
                                                        {[p.type, p.montant].filter(Boolean).join(" · ")}
                                                    </strong>
                                                    <small className="block truncate text-xs text-muted">
                                                        {[p.periode, p.date, p.methode, p.reference]
                                                            .filter(Boolean)
                                                            .join(" · ")}
                                                    </small>
                                                </span>
                                                <Badge variant={p.statut.ton}>{p.statut.libelle}</Badge>
                                                <ActionsDocument
                                                    pdf={p.recu}
                                                    libelle={`le reçu ${p.reference ?? ""}`.trim()}
                                                />
                                            </li>
                                        ))}
                                    </Liste>
                                    <div className="hidden overflow-hidden rounded-brand border md:block">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Date</TableHead>
                                                    <TableHead>Type</TableHead>
                                                    <TableHead>Période</TableHead>
                                                    <TableHead>Référence</TableHead>
                                                    <TableHead>Moyen</TableHead>
                                                    <TableHead className="text-right">Montant</TableHead>
                                                    <TableHead>Statut</TableHead>
                                                    <TableHead className="text-right">Reçu</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {paiements.map((p) => (
                                                    <TableRow key={p.id}>
                                                        <TableCell>{p.date ?? "—"}</TableCell>
                                                        <TableCell>{p.type ?? "—"}</TableCell>
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
                                                        <TableCell className="py-1">
                                                            <div className="flex justify-end">
                                                                <ActionsDocument
                                                                    pdf={p.recu}
                                                                    libelle={`le reçu ${p.reference ?? ""}`.trim()}
                                                                />
                                                            </div>
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
                    </TabsContent>
                </Tabs>
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

const classeIcone =
    "grid size-9 shrink-0 place-items-center rounded-brand text-muted hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none";

/** Voir (nouvel onglet) et télécharger un document : quittance d'un mois payé ou reçu d'un paiement. */
function ActionsDocument({ pdf, libelle }: { pdf: DocumentPdf | null; libelle: string }) {
    if (!pdf) return <span className="inline-block size-9 shrink-0" aria-hidden />;

    return (
        <span className="flex shrink-0 items-center">
            <a
                href={pdf.voir}
                target="_blank"
                rel="noopener noreferrer"
                title={`Voir ${libelle}`}
                aria-label={`Voir ${libelle}`}
                className={classeIcone}
            >
                <Eye {...ICON} />
            </a>
            <a
                href={pdf.telecharger}
                title={`Télécharger ${libelle}`}
                aria-label={`Télécharger ${libelle}`}
                className={classeIcone}
            >
                <Download {...ICON} />
            </a>
        </span>
    );
}
