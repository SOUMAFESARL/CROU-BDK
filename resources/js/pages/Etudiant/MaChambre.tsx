import {
    Accessibility,
    Armchair,
    BedDouble,
    BedSingle,
    Building2,
    CalendarCheck,
    CalendarClock,
    ChevronRight,
    CircleCheck,
    ClipboardCheck,
    Compass,
    CreditCard,
    Fan,
    FileText,
    FireExtinguisher,
    Info,
    Lightbulb,
    LogOut,
    MapPin,
    Package,
    PlugZap,
    Ruler,
    ShowerHead,
    Sparkles,
    TriangleAlert,
    Users,
    Wrench,
    type LucideIcon,
} from "lucide-react";
import { useState } from "react";
import {
    IconeLigne,
    Ligne,
    Liste,
    Notice,
    Panneau,
    Vide,
} from "@/components/etudiant/panneau";
import {
    SignalementEquipement,
    type Categorie,
    type EquipementChambre,
} from "@/components/etudiant/signalement-equipement";
import { ICON } from "@/components/layout/app-sidebar";
import { EtudiantLayout } from "@/components/layout/etudiant-layout";
import { Badge } from "@/components/ui/badge";
import { Button, buttonVariants } from "@/components/ui/button";
import { StatCard } from "@/components/ui/stat-card";
import { cn } from "@/lib/utils";

type Colocataire = {
    nom: string;
    initiales: string;
    lit: string | null;
    filiere: string | null;
};

type SignalementResume = {
    id: string;
    numero: string | null;
    titre: string;
    date: string | null;
    statut: string;
    ton: "success" | "warning" | "info" | "neutral";
};

type Chambre = {
    numero: string;
    lit: string | null;
    batiment: string | null;
    residence: string | null;
    campus: string | null;
    type: string | null;
    loyer: string;
    superficie: string | null;
    occupation: { occupants: number; capacite: number };
    orientation: string | null;
    confort: string | null;
    pmr: boolean;
    description: string | null;
    entree: string | null;
    fin: string | null;
    aVenir: boolean;
    equipements: EquipementChambre[];
    colocataires: Colocataire[];
    signalements: SignalementResume[];
};

type MaChambreProps = {
    chambre: Chambre | null;
    categories: Categorie[];
    urls: {
        signalement: string;
        tableauDeBord: string;
        maintenance: string;
        etatDesLieux: string;
        paiements: string;
        sortie: string;
        contrat: string;
    };
};

/** Icône d'un équipement d'après son nom. */
function iconeEquipement(nom: string): LucideIcon {
    const n = nom.toLowerCase();
    if (/lit|matelas/.test(n)) return BedSingle;
    if (/chaise|table|bureau|armoire|placard|[ée]tag/.test(n)) return Armchair;
    if (/clim|ventil/.test(n)) return Fan;
    if (/douche|wc|toilette|lavabo|robinet/.test(n)) return ShowerHead;
    if (/lampe|ampoule|n[ée]on/.test(n)) return Lightbulb;
    if (/prise|[ée]lectri/.test(n)) return PlugZap;
    if (/extincteur|d[ée]tecteur|fum[ée]e/.test(n)) return FireExtinguisher;
    return Package;
}

const pluriel = (n: number, singulier: string, plurielForme: string) =>
    `${n} ${n > 1 ? plurielForme : singulier}`;

/** Page « Ma chambre » : chambre attribuée, équipements, colocataires et signalements. */
export default function MaChambre({
    chambre,
    categories,
    urls,
}: MaChambreProps) {
    return (
        <EtudiantLayout title="Ma chambre">
            <div className="flex flex-col gap-5">
                {chambre ? (
                    <VueChambre
                        chambre={chambre}
                        categories={categories}
                        urls={urls}
                    />
                ) : (
                    <SansChambre urls={urls} />
                )}
            </div>
        </EtudiantLayout>
    );
}

function SansChambre({ urls }: { urls: MaChambreProps["urls"] }) {
    return (
        <>
            <header>
                <h1 className="page-title text-xl font-medium text-gray-900">
                    Ma chambre
                </h1>
                <p className="mt-2 text-sm text-muted">
                    Votre logement apparaîtra ici dès qu’une chambre vous sera
                    attribuée.
                </p>
            </header>
            <Panneau surtitre="Logement" titre="Aucune chambre attribuée">
                <div className="flex flex-col gap-4">
                    <Vide icone={BedDouble}>
                        Vous n’avez pas encore de chambre confirmée pour l’année
                        académique en cours.
                    </Vide>
                    <a
                        href={urls.tableauDeBord}
                        className={cn(buttonVariants(), "w-fit font-medium")}
                    >
                        Suivre ma demande d’hébergement
                        <ChevronRight {...ICON} />
                    </a>
                </div>
            </Panneau>
        </>
    );
}

function VueChambre({
    chambre,
    categories,
    urls,
}: {
    chambre: Chambre;
    categories: Categorie[];
    urls: MaChambreProps["urls"];
}) {
    const [signalement, setSignalement] = useState<{
        ouvert: boolean;
        equipement: number | null;
    }>({ ouvert: false, equipement: null });
    const signaler = (equipement: number | null = null) =>
        setSignalement({ ouvert: true, equipement });
    const localisation = [chambre.batiment, chambre.residence, chambre.campus]
        .filter(Boolean)
        .join(" · ");
    const { occupants, capacite } = chambre.occupation;
    const nbEquipements = chambre.equipements.length;
    const nbAbimes = chambre.equipements.filter(
        (e) => e.ton === "danger" || e.ton === "warning",
    ).length;

    const caracteristiques: {
        icone: LucideIcon;
        libelle: string;
        valeur: string | null;
    }[] = [
        { icone: BedDouble, libelle: "Type de chambre", valeur: chambre.type },
        { icone: Building2, libelle: "Bâtiment", valeur: chambre.batiment },
        {
            icone: MapPin,
            libelle: "Résidence",
            valeur:
                [chambre.residence, chambre.campus]
                    .filter(Boolean)
                    .join(" · ") || null,
        },
        { icone: Ruler, libelle: "Superficie", valeur: chambre.superficie },
        { icone: Compass, libelle: "Orientation", valeur: chambre.orientation },
        {
            icone: Sparkles,
            libelle: "Niveau de confort",
            valeur: chambre.confort,
        },
        {
            icone: CalendarCheck,
            libelle: chambre.aVenir ? "Entrée prévue le" : "Date d’entrée",
            valeur: chambre.entree,
        },
        {
            icone: CalendarClock,
            libelle: "Fin d’occupation prévue",
            valeur: chambre.fin,
        },
    ];

    const actions: {
        href: string;
        icone: LucideIcon;
        titre: string;
        detail: string;
        nouvelOnglet?: boolean;
    }[] = [
        {
            href: urls.contrat,
            icone: FileText,
            titre: "Mon contrat",
            detail: "Consulter et télécharger",
            nouvelOnglet: true,
        },
        {
            href: urls.etatDesLieux,
            icone: ClipboardCheck,
            titre: "État des lieux",
            detail: "Entrée et sortie de la chambre",
        },
        {
            href: urls.paiements,
            icone: CreditCard,
            titre: "Payer mon loyer",
            detail: `Loyer mensuel : ${chambre.loyer}`,
        },
        {
            href: urls.sortie,
            icone: LogOut,
            titre: "Demande de sortie",
            detail: "Préavis de 15 jours minimum",
        },
    ];

    return (
        <>
            <header className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <h1 className="page-title text-xl font-medium text-gray-900">
                        Chambre {chambre.numero}
                    </h1>
                    {localisation && (
                        <p className="mt-2 text-sm text-muted">
                            {localisation}
                        </p>
                    )}
                </div>
                <span
                    className={cn(
                        "inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium",
                        chambre.aVenir
                            ? "bg-accent/15 text-amber-800"
                            : "bg-soft text-primary",
                    )}
                >
                    {chambre.aVenir ? (
                        <CalendarClock {...ICON} size={16} />
                    ) : (
                        <CircleCheck {...ICON} size={16} />
                    )}
                    {chambre.aVenir
                        ? `Entrée prévue le ${chambre.entree}`
                        : "Occupée"}
                </span>
            </header>

            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard
                    label="Chambre"
                    value={chambre.numero}
                    tone="blue"
                    extra={chambre.lit ?? undefined}
                />
                <StatCard
                    label="Type de chambre"
                    value={chambre.type ?? "—"}
                    tone="plum"
                />
                <StatCard
                    label="Loyer mensuel"
                    value={chambre.loyer}
                    tone="green"
                />
                <StatCard
                    label="Occupation"
                    value={`${occupants} / ${capacite}`}
                    tone="amber"
                    extra={capacite > 1 ? "places" : "place"}
                />
            </div>

            <div className="grid gap-5 lg:grid-cols-2">
                <Panneau surtitre="Logement" titre="Caractéristiques">
                    <ul className="-mx-2 grid sm:grid-cols-2">
                        {caracteristiques.map(({ icone, libelle, valeur }) => (
                            <Ligne
                                key={libelle}
                                icone={icone}
                                libelle={libelle}
                                titre={valeur ?? "Non renseigné"}
                            />
                        ))}
                    </ul>
                    {chambre.pmr && (
                        <Notice icone={Accessibility} className="mt-3">
                            Chambre adaptée aux personnes à mobilité réduite.
                        </Notice>
                    )}
                    {chambre.description && (
                        <p className="mt-3 rounded-brand bg-surface p-3 text-sm text-muted">
                            {chambre.description}
                        </p>
                    )}
                </Panneau>

                <Panneau
                    surtitre="Mobilier et matériel"
                    titre="Équipements de la chambre"
                    badge={pluriel(nbEquipements, "équipement", "équipements")}
                >
                    <div className="flex flex-col gap-3">
                        {nbEquipements === 0 ? (
                            <Vide icone={Package}>
                                Aucun équipement enregistré pour cette chambre.
                                Contactez le responsable de la résidence pour
                                plus d’informations.
                            </Vide>
                        ) : (
                            <Liste>
                                {chambre.equipements.map(
                                    (equipement, index) => (
                                        <li
                                            key={`${equipement.nom}-${index}`}
                                            className="flex min-h-14 items-center gap-3 px-2 py-2"
                                        >
                                            <IconeLigne
                                                icone={iconeEquipement(
                                                    equipement.nom,
                                                )}
                                                ton={
                                                    equipement.ton === "success"
                                                        ? "vert"
                                                        : equipement.ton ===
                                                            "neutral"
                                                          ? undefined
                                                          : "orange"
                                                }
                                            />
                                            <span className="min-w-0 flex-1">
                                                <strong className="block truncate text-sm font-medium text-gray-900">
                                                    {equipement.nom}
                                                </strong>
                                                <small className="block text-xs text-muted">
                                                    Quantité :{" "}
                                                    {equipement.quantite}
                                                </small>
                                            </span>
                                            {equipement.etat && (
                                                <Badge variant={equipement.ton}>
                                                    {equipement.etat}
                                                </Badge>
                                            )}
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => signaler(index)}
                                                aria-label={`Signaler un problème : ${equipement.nom}`}
                                                title="Signaler un problème"
                                                className="shrink-0 text-muted hover:text-primary"
                                            >
                                                <TriangleAlert {...ICON} />
                                            </Button>
                                        </li>
                                    ),
                                )}
                            </Liste>
                        )}
                        {nbAbimes > 0 && (
                            <Notice
                                icone={Info}
                                className="bg-amber-50 text-amber-800"
                            >
                                {pluriel(
                                    nbAbimes,
                                    "équipement est signalé",
                                    "équipements sont signalés",
                                )}{" "}
                                en mauvais état par le service technique.
                            </Notice>
                        )}
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-xs text-muted">
                                Un équipement abîmé ? Signalez-le : un
                                technicien interviendra.
                            </p>
                            <Button
                                onClick={() => signaler()}
                                className="shrink-0 font-medium"
                            >
                                <Wrench {...ICON} />
                                Signaler un problème
                            </Button>
                        </div>
                    </div>
                </Panneau>
            </div>

            <div className="grid gap-5 lg:grid-cols-2">
                <Panneau
                    surtitre="Chambre partagée"
                    titre="Mes colocataires"
                    badge={pluriel(
                        chambre.colocataires.length,
                        "colocataire",
                        "colocataires",
                    )}
                >
                    {chambre.colocataires.length === 0 ? (
                        <Vide icone={Users}>
                            Aucun autre occupant dans cette chambre pour le
                            moment.
                        </Vide>
                    ) : (
                        <Liste>
                            {chambre.colocataires.map((colocataire, index) => (
                                <li
                                    key={`${colocataire.nom}-${index}`}
                                    className="flex min-h-14 items-center gap-3 px-2 py-2"
                                >
                                    <span
                                        aria-hidden
                                        className="grid size-10 shrink-0 place-items-center rounded-full bg-primary text-sm font-medium text-white"
                                    >
                                        {colocataire.initiales}
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <strong className="block truncate text-sm font-medium text-gray-900">
                                            {colocataire.nom}
                                        </strong>
                                        <small className="block truncate text-xs text-muted">
                                            {[
                                                colocataire.lit,
                                                colocataire.filiere,
                                            ]
                                                .filter(Boolean)
                                                .join(" · ") ||
                                                "Occupant actif"}
                                        </small>
                                    </span>
                                </li>
                            ))}
                        </Liste>
                    )}
                </Panneau>

                <Panneau
                    surtitre="Maintenance"
                    titre="Mes signalements"
                    badge={pluriel(
                        chambre.signalements.length,
                        "récent",
                        "récents",
                    )}
                >
                    <div className="flex flex-col gap-3">
                        {chambre.signalements.length === 0 ? (
                            <Vide icone={Wrench}>
                                Vous n’avez encore signalé aucun problème.
                            </Vide>
                        ) : (
                            <Liste>
                                {chambre.signalements.map((s) => (
                                    <Ligne
                                        key={s.id}
                                        icone={Wrench}
                                        ton={
                                            s.ton === "success"
                                                ? "vert"
                                                : undefined
                                        }
                                        titre={s.titre}
                                        detail={[s.numero, s.date]
                                            .filter(Boolean)
                                            .join(" · ")}
                                        complement={
                                            <Badge variant={s.ton}>
                                                {s.statut}
                                            </Badge>
                                        }
                                    />
                                ))}
                            </Liste>
                        )}
                        <a
                            href={urls.maintenance}
                            className="inline-flex w-fit items-center gap-1 text-sm font-medium text-primary hover:underline"
                        >
                            Voir toutes mes demandes de maintenance
                            <ChevronRight {...ICON} size={16} />
                        </a>
                    </div>
                </Panneau>
            </div>

            <Panneau surtitre="Raccourcis" titre="Gérer mon logement">
                <ul className="-mx-2 grid gap-1 sm:grid-cols-2 xl:grid-cols-4">
                    {actions.map((action) => (
                        <li key={action.titre}>
                            <a
                                href={action.href}
                                {...(action.nouvelOnglet
                                    ? {
                                          target: "_blank",
                                          rel: "noopener noreferrer",
                                      }
                                    : {})}
                                className="group flex min-h-14 items-start gap-3 rounded-brand px-2 py-2.5 hover:bg-surface focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                            >
                                <IconeLigne icone={action.icone} />
                                <span className="min-w-0 flex-1">
                                    <strong className="block text-sm font-medium text-gray-900 group-hover:text-primary">
                                        {action.titre}
                                    </strong>
                                    <small className="block text-xs text-muted">
                                        {action.detail}
                                    </small>
                                </span>
                                <ChevronRight
                                    {...ICON}
                                    className="mt-2.5 shrink-0 text-muted group-hover:text-primary"
                                />
                            </a>
                        </li>
                    ))}
                </ul>
            </Panneau>

            <SignalementEquipement
                ouverte={signalement.ouvert}
                fermer={() => setSignalement((s) => ({ ...s, ouvert: false }))}
                url={urls.signalement}
                equipements={chambre.equipements}
                categories={categories}
                equipementInitial={signalement.equipement}
            />
        </>
    );
}
