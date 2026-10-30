import {
    CalendarCheck,
    CalendarClock,
    ChevronRight,
    CircleCheck,
    Hash,
    HardHat,
    Info,
    Inbox,
    Layers,
    MapPin,
    Megaphone,
    Plus,
    Siren,
    Star,
    Wrench,
    type LucideIcon,
} from "lucide-react";
import { useState } from "react";
import {
    Filtres,
    Ligne,
    Notice,
    PaginationSimple,
    Panneau,
    Vide,
    type PaginationSimpleProps,
} from "@/components/etudiant/panneau";
import {
    SignalementEquipement,
    type Categorie,
    type EquipementChambre,
} from "@/components/etudiant/signalement-equipement";
import { ICON } from "@/components/layout/app-sidebar";
import { EtudiantLayout } from "@/components/layout/etudiant-layout";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from "@/components/ui/sheet";
import { StatCard } from "@/components/ui/stat-card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { cn } from "@/lib/utils";

type Ton = "success" | "warning" | "danger" | "info" | "neutral";

type Demande = {
    id: string;
    numero: string | null;
    titre: string;
    description: string | null;
    categorie: string | null;
    priorite: { libelle: string; ton: Ton };
    statut: { libelle: string; ton: Ton };
    /** 0 déposée, 1 prise en charge, 2 terminée ; null : annulée ou reportée. */
    etape: 0 | 1 | 2 | null;
    creeLe: string | null;
    disponibilite: string | null;
    intervention: string | null;
    resolueLe: string | null;
    technicien: string | null;
    solution: string | null;
    satisfaction: number | null;
};

/** Ordre de travail initié par l'administration pour la chambre, le bâtiment ou la cité de l'étudiant. */
type OrdreAdministration = {
    id: string;
    numero: string | null;
    titre: string;
    description: string | null;
    categorie: string | null;
    nature: string | null;
    priorite: { libelle: string; ton: Ton };
    statut: { libelle: string; ton: Ton };
    /** Emplacement visé : « Ma chambre », « Tout le bâtiment (…) » ou « Toute la cité (…) ». */
    portee: string;
    creeLe: string | null;
    intervention: string | null;
    technicien: string | null;
    resolueLe: string | null;
    solution: string | null;
};

type Filtre = "toutes" | "en_cours" | "resolues" | "urgentes";
type Onglet = "demandes" | "administration";

type MaintenanceProps = {
    onglet: Onglet;
    compteurs: Record<Filtre, number> & { tauxResolution: number };
    filtre: Filtre;
    demandes: Demande[];
    pagination: PaginationSimpleProps;
    ordresAdministration: OrdreAdministration[];
    paginationAdministration: PaginationSimpleProps;
    /** Ordres de l'administration non clôturés (badge de l'onglet). */
    administrationEnCours: number;
    chambre: string | null;
    equipements: EquipementChambre[];
    categories: Categorie[];
    urls: { signalement: string; page: string; chambre: string };
};

const FILTRES: { cle: Filtre; libelle: string; vide: string }[] = [
    { cle: "toutes", libelle: "Toutes", vide: "Vous n’avez encore déposé aucune demande de maintenance." },
    { cle: "en_cours", libelle: "En cours", vide: "Aucune demande en cours de traitement." },
    { cle: "resolues", libelle: "Résolues", vide: "Aucune demande résolue pour le moment." },
    { cle: "urgentes", libelle: "Urgentes", vide: "Aucune demande urgente en cours." },
];

const ETAPES = ["Déposée", "Prise en charge", "Terminée"];

/** URL de la page avec un autre filtre (la pagination repart de la première page). */
function avecFiltre(page: string, filtre: Filtre) {
    const url = new URL(page, window.location.origin);
    if (filtre !== "toutes") url.searchParams.set("filtre", filtre);
    return url.pathname + url.search;
}

/**
 * Page « Maintenance » : onglets « Mes demandes » (demandes d'intervention de l'étudiant, avec leur suivi)
 * et « Travaux programmés » (ordres de travail de l'administration sur sa chambre, son bâtiment ou sa cité),
 * et dépôt d'une nouvelle demande.
 */
export default function Maintenance({
    onglet,
    compteurs,
    filtre,
    demandes,
    pagination,
    ordresAdministration,
    paginationAdministration,
    administrationEnCours,
    chambre,
    equipements,
    categories,
    urls,
}: MaintenanceProps) {
    const [nouvelle, setNouvelle] = useState(false);
    const [detail, setDetail] = useState<Demande | null>(null);
    const [ordre, setOrdre] = useState<OrdreAdministration | null>(null);
    const [actif, setActif] = useState<Onglet>(onglet);

    // L'onglet ouvert reste dans l'URL (?onglet=…) pour le partage et le retour arrière.
    const changerOnglet = (valeur: string) => {
        setActif(valeur as Onglet);
        const url = new URL(window.location.href);
        url.searchParams.set("onglet", valeur);
        window.history.replaceState(window.history.state, "", url);
    };

    return (
        <EtudiantLayout title="Maintenance">
            <div className="flex flex-col gap-5">
                <header className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                        <h1 className="page-title text-xl font-medium text-gray-900">
                            Maintenance
                        </h1>
                        <p className="mt-2 text-sm text-muted">
                            {chambre
                                ? `Signalez un problème dans votre logement (${chambre}) et suivez l’intervention.`
                                : "Signalez un problème et suivez l’intervention du service technique."}
                        </p>
                    </div>
                    <Button onClick={() => setNouvelle(true)} className="font-medium">
                        <Plus {...ICON} />
                        Nouvelle demande
                    </Button>
                </header>

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <StatCard label="Mes demandes" value={compteurs.toutes} tone="blue" />
                    <StatCard label="En cours" value={compteurs.en_cours} tone="amber" />
                    <StatCard
                        label="Résolues"
                        value={compteurs.resolues}
                        tone="green"
                        extra={compteurs.toutes > 0 ? `${compteurs.tauxResolution} %` : undefined}
                    />
                    <StatCard
                        label="Urgentes en cours"
                        value={compteurs.urgentes}
                        tone={compteurs.urgentes > 0 ? "red" : "neutral"}
                        highlight={compteurs.urgentes > 0}
                        extra={compteurs.urgentes > 0 ? "À suivre" : undefined}
                    />
                </div>

                {!chambre && (
                    <Notice icone={Info} className="bg-amber-50 text-amber-800">
                        Aucune chambre confirmée n’est associée à votre dossier : précisez le lieu
                        exact du problème dans la description de votre demande.
                    </Notice>
                )}

                <Tabs value={actif} onValueChange={changerOnglet}>
                    <TabsList aria-label="Rubriques de la maintenance" className="bg-white px-1">
                        <TabsTrigger value="demandes">
                            <Wrench {...ICON} />
                            Mes demandes
                        </TabsTrigger>
                        <TabsTrigger value="administration">
                            <Megaphone {...ICON} />
                            Travaux programmés
                            {administrationEnCours > 0 && (
                                <span className="rounded-sm bg-accent px-1.5 text-xs font-medium text-white">
                                    {administrationEnCours}
                                </span>
                            )}
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="demandes">
                        <Panneau
                            surtitre="Suivi des interventions"
                            titre="Mes demandes"
                            badge={`${pagination.total} demande${pagination.total > 1 ? "s" : ""}`}
                        >
                            <Filtres
                                libelle="Filtrer les demandes"
                                actif={filtre}
                                filtres={FILTRES.map((f) => ({
                                    cle: f.cle,
                                    libelle: f.libelle,
                                    compte: compteurs[f.cle],
                                    href: avecFiltre(urls.page, f.cle),
                                }))}
                            />
                            {demandes.length === 0 ? (
                                <div className="flex flex-col gap-3">
                                    <Vide icone={Wrench}>
                                        {FILTRES.find((f) => f.cle === filtre)?.vide}
                                    </Vide>
                                    {filtre === "toutes" && (
                                        <Button
                                            variant="outline"
                                            onClick={() => setNouvelle(true)}
                                            className="w-fit"
                                        >
                                            <Plus {...ICON} />
                                            Déposer ma première demande
                                        </Button>
                                    )}
                                </div>
                            ) : (
                                <ul className="flex flex-col gap-3">
                                    {demandes.map((demande) => (
                                        <CarteDemande
                                            key={demande.id}
                                            demande={demande}
                                            ouvrir={() => setDetail(demande)}
                                        />
                                    ))}
                                </ul>
                            )}
                            <PaginationSimple {...pagination} />
                        </Panneau>
                    </TabsContent>

                    <TabsContent value="administration">
                        <Panneau
                            surtitre="Interventions de l’administration"
                            titre="Travaux programmés"
                            badge={`${paginationAdministration.total} ordre${paginationAdministration.total > 1 ? "s" : ""}`}
                        >
                            <p className="mb-3 text-sm text-muted">
                                Ordres de travail lancés par le service technique pour votre chambre, votre
                                bâtiment ou votre cité. Facilitez l’accès des techniciens aux dates indiquées.
                            </p>
                            {ordresAdministration.length === 0 ? (
                                <Vide icone={Inbox}>
                                    {chambre
                                        ? "Aucun ordre de travail de l’administration ne concerne votre logement."
                                        : "Les travaux programmés s’affichent dès qu’une chambre vous est attribuée."}
                                </Vide>
                            ) : (
                                <ul className="flex flex-col gap-3">
                                    {ordresAdministration.map((o) => (
                                        <CarteOrdre key={o.id} ordre={o} ouvrir={() => setOrdre(o)} />
                                    ))}
                                </ul>
                            )}
                            <PaginationSimple {...paginationAdministration} />
                        </Panneau>
                    </TabsContent>
                </Tabs>

                {chambre && (
                    <a
                        href={urls.chambre}
                        className="inline-flex w-fit items-center gap-1 text-sm font-medium text-primary hover:underline"
                    >
                        Voir les équipements de ma chambre
                        <ChevronRight {...ICON} size={16} />
                    </a>
                )}
            </div>

            <DetailDemande demande={detail} fermer={() => setDetail(null)} />
            <DetailOrdre ordre={ordre} fermer={() => setOrdre(null)} />

            <SignalementEquipement
                ouverte={nouvelle}
                fermer={() => setNouvelle(false)}
                url={urls.signalement}
                equipements={equipements}
                categories={categories}
                titre="Nouvelle demande de maintenance"
            />
        </EtudiantLayout>
    );
}

function CarteDemande({ demande, ouvrir }: { demande: Demande; ouvrir: () => void }) {
    return (
        <li className="rounded-brand border p-3 md:p-4">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <h3 className="truncate text-sm font-medium text-gray-900">{demande.titre}</h3>
                    <p className="mt-0.5 truncate text-xs text-muted">
                        {[demande.numero, demande.categorie, demande.creeLe && `Déposée le ${demande.creeLe}`]
                            .filter(Boolean)
                            .join(" · ")}
                    </p>
                </div>
                <Badge variant={demande.statut.ton}>{demande.statut.libelle}</Badge>
            </div>
            {demande.description && (
                <p className="mt-2 line-clamp-2 text-sm text-muted">{demande.description}</p>
            )}
            {demande.etape !== null && <Avancement etape={demande.etape} />}
            <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                <span className="flex flex-wrap items-center gap-2 text-xs text-muted">
                    <Badge variant={demande.priorite.ton}>{demande.priorite.libelle}</Badge>
                    <span className="inline-flex items-center gap-1">
                        <HardHat {...ICON} size={14} />
                        {demande.technicien ?? "En attente d’affectation"}
                    </span>
                </span>
                <Button variant="ghost" size="sm" onClick={ouvrir} className="text-primary">
                    Détails
                    <ChevronRight {...ICON} size={16} />
                </Button>
            </div>
        </li>
    );
}

function CarteOrdre({ ordre, ouvrir }: { ordre: OrdreAdministration; ouvrir: () => void }) {
    return (
        <li className="rounded-brand border p-3 md:p-4">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <h3 className="truncate text-sm font-medium text-gray-900">{ordre.titre}</h3>
                    <p className="mt-0.5 truncate text-xs text-muted">
                        {[ordre.numero, ordre.categorie, ordre.creeLe && `Émis le ${ordre.creeLe}`]
                            .filter(Boolean)
                            .join(" · ")}
                    </p>
                </div>
                <Badge variant={ordre.statut.ton}>{ordre.statut.libelle}</Badge>
            </div>
            {ordre.description && (
                <p className="mt-2 line-clamp-2 text-sm text-muted">{ordre.description}</p>
            )}
            <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                <span className="flex flex-wrap items-center gap-x-3 gap-y-2 text-xs text-muted">
                    <Badge variant={ordre.priorite.ton}>{ordre.priorite.libelle}</Badge>
                    <span className="inline-flex items-center gap-1">
                        <MapPin {...ICON} size={14} />
                        {ordre.portee}
                    </span>
                    {ordre.intervention && (
                        <span className="inline-flex items-center gap-1">
                            <CalendarClock {...ICON} size={14} />
                            {ordre.intervention}
                        </span>
                    )}
                </span>
                <Button variant="ghost" size="sm" onClick={ouvrir} className="text-primary">
                    Détails
                    <ChevronRight {...ICON} size={16} />
                </Button>
            </div>
        </li>
    );
}

function DetailOrdre({ ordre, fermer }: { ordre: OrdreAdministration | null; fermer: () => void }) {
    const lignes: { icone: LucideIcon; libelle: string; valeur: string | null }[] = ordre
        ? [
              { icone: Hash, libelle: "Numéro", valeur: ordre.numero },
              { icone: MapPin, libelle: "Emplacement concerné", valeur: ordre.portee },
              { icone: Layers, libelle: "Catégorie", valeur: [ordre.categorie, ordre.nature].filter(Boolean).join(" · ") || null },
              { icone: Siren, libelle: "Priorité", valeur: ordre.priorite.libelle },
              { icone: CalendarClock, libelle: "Émis le", valeur: ordre.creeLe },
              { icone: Wrench, libelle: "Intervention prévue", valeur: ordre.intervention },
              { icone: HardHat, libelle: "Technicien", valeur: ordre.technicien ?? "En attente d’affectation" },
              { icone: CalendarCheck, libelle: "Terminé le", valeur: ordre.resolueLe },
          ].filter((l) => l.valeur)
        : [];

    return (
        <Sheet open={ordre !== null} onOpenChange={(ouvert) => !ouvert && fermer()}>
            <SheetContent className="w-full overflow-y-auto sm:max-w-md">
                {ordre && (
                    <>
                        <SheetHeader>
                            <SheetTitle className="pr-6">{ordre.titre}</SheetTitle>
                            <SheetDescription asChild>
                                <div className="flex flex-wrap gap-2">
                                    <Badge variant={ordre.statut.ton}>{ordre.statut.libelle}</Badge>
                                    <Badge variant={ordre.priorite.ton}>{ordre.priorite.libelle}</Badge>
                                </div>
                            </SheetDescription>
                        </SheetHeader>
                        <div className="flex flex-col gap-4 px-4 pb-6">
                            {ordre.description && (
                                <section>
                                    <h3 className="mb-1 text-xs text-muted">Description</h3>
                                    <p className="rounded-brand bg-surface p-3 text-sm whitespace-pre-line text-gray-900">
                                        {ordre.description}
                                    </p>
                                </section>
                            )}
                            {ordre.solution && (
                                <Notice icone={CircleCheck}>
                                    <strong className="block font-medium">Travaux réalisés</strong>
                                    {ordre.solution}
                                </Notice>
                            )}
                            <ul className="-mx-2 grid">
                                {lignes.map((l) => (
                                    <Ligne key={l.libelle} icone={l.icone} libelle={l.libelle} titre={l.valeur} />
                                ))}
                            </ul>
                        </div>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}

/** Trois étapes : déposée, prise en charge, terminée. */
function Avancement({ etape }: { etape: 0 | 1 | 2 }) {
    return (
        <ol className="mt-3 grid grid-cols-3 gap-1.5" aria-label={`Avancement : ${ETAPES[etape]}`}>
            {ETAPES.map((libelle, index) => (
                <li key={libelle} className="min-w-0">
                    <span
                        aria-hidden
                        className={cn(
                            "block h-1.5 rounded-full",
                            index <= etape ? (etape === 2 ? "bg-primary" : "bg-accent") : "bg-gray-200",
                        )}
                    />
                    <span
                        className={cn(
                            "mt-1 block truncate text-xs",
                            index === etape ? "font-medium text-gray-900" : "text-muted",
                        )}
                    >
                        {libelle}
                    </span>
                </li>
            ))}
        </ol>
    );
}

function DetailDemande({ demande, fermer }: { demande: Demande | null; fermer: () => void }) {
    const lignes: { icone: LucideIcon; libelle: string; valeur: string | null }[] = demande
        ? [
              { icone: Hash, libelle: "Numéro de suivi", valeur: demande.numero },
              { icone: Layers, libelle: "Catégorie", valeur: demande.categorie },
              { icone: Siren, libelle: "Urgence", valeur: demande.priorite.libelle },
              { icone: CalendarClock, libelle: "Déposée le", valeur: demande.creeLe },
              { icone: MapPin, libelle: "Ma disponibilité", valeur: demande.disponibilite },
              { icone: HardHat, libelle: "Technicien", valeur: demande.technicien ?? "En attente d’affectation" },
              { icone: Wrench, libelle: "Intervention", valeur: demande.intervention },
              { icone: CalendarCheck, libelle: "Résolue le", valeur: demande.resolueLe },
              {
                  icone: Star,
                  libelle: "Satisfaction",
                  valeur: demande.satisfaction ? `${demande.satisfaction} / 5` : null,
              },
          ].filter((l) => l.valeur)
        : [];

    return (
        <Sheet open={demande !== null} onOpenChange={(ouvert) => !ouvert && fermer()}>
            <SheetContent className="w-full overflow-y-auto sm:max-w-md">
                {demande && (
                    <>
                        <SheetHeader>
                            <SheetTitle className="pr-6">{demande.titre}</SheetTitle>
                            <SheetDescription asChild>
                                <div className="flex flex-wrap gap-2">
                                    <Badge variant={demande.statut.ton}>{demande.statut.libelle}</Badge>
                                    <Badge variant={demande.priorite.ton}>{demande.priorite.libelle}</Badge>
                                </div>
                            </SheetDescription>
                        </SheetHeader>
                        <div className="flex flex-col gap-4 px-4 pb-6">
                            {demande.etape !== null && <Avancement etape={demande.etape} />}
                            {demande.description && (
                                <section>
                                    <h3 className="mb-1 text-xs text-muted">Description</h3>
                                    <p className="rounded-brand bg-surface p-3 text-sm whitespace-pre-line text-gray-900">
                                        {demande.description}
                                    </p>
                                </section>
                            )}
                            {demande.solution && (
                                <Notice icone={CircleCheck}>
                                    <strong className="block font-medium">Solution apportée</strong>
                                    {demande.solution}
                                </Notice>
                            )}
                            <ul className="-mx-2 grid">
                                {lignes.map((l) => (
                                    <Ligne key={l.libelle} icone={l.icone} libelle={l.libelle} titre={l.valeur} />
                                ))}
                            </ul>
                        </div>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}
