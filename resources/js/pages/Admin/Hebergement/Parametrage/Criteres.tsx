import {
    CalendarDays,
    CircleCheck,
    CircleDashedCheck,
    FileText,
    History,
    Hotel,
    ListChecks,
    Pencil,
} from "lucide-react";
import { useState, type ReactNode } from "react";
import { CriteresConsultation } from "@/components/campagnes/criteres-consultation";
import {
    CriteresFormulaire,
    EtatCriteresVolet,
} from "@/components/campagnes/criteres-formulaire";
import { CriteresGenerauxFormulaire } from "@/components/campagnes/criteres-generaux-formulaire";
import {
    ETATS_CRITERES,
    JEUX,
    type Jeu,
    type PageCriteres,
    type PageGeneral,
    type PageNiveau,
} from "@/components/campagnes/criteres";
import { AdminLayout } from "@/components/layout/admin-layout";
import { ICON } from "@/components/layout/app-sidebar";
import {
    Bloc,
    ChoixCampagne,
    Vide,
    type CampagneOption,
} from "@/components/parametrage-hebergement/shared";
import { Badge } from "@/components/ui/badge";
import { buttonVariants } from "@/components/ui/button";
import { PageHeader } from "@/components/ui/page-header";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { dateFr } from "@/lib/text";
import { cn } from "@/lib/utils";

type Contexte = {
    periode_debut: string | null;
    periode_fin: string | null;
    documents: string[];
    residences: string[];
};

type Props = {
    campagnes: CampagneOption[];
    campagneId: string | null;
    contexte: Contexte | null;
    /**
     * Paramétrage général, puis un niveau par volet ouvert par la campagne (admission puis réadmission) ;
     * null sans droit de consulter.
     */
    niveaux: {
        general?: PageGeneral;
        admission?: PageCriteres;
        readmission?: PageCriteres;
    } | null;
    /** Niveau affiché à l'ouverture (`?volet=`, sinon le premier). */
    niveau: Jeu | null;
    urls: {
        index: string;
        editCampagne: string | null;
    };
};

const ORDRE: Jeu[] = ["general", "admission", "readmission"];

/** Onglet de consultation des critères en vigueur et de l'historique, à côté des niveaux de paramétrage. */
const EN_VIGUEUR = "en-vigueur";
type Onglet = Jeu | typeof EN_VIGUEUR;

/**
 * « Critères de la campagne » : tout se paramètre sur une seule page — le paramétrage général, puis les critères
 * d'admission et/ou de réadmission selon le type de campagne. Passer d'un niveau à l'autre ne change pas de page
 * et conserve la saisie en cours.
 */
export default function Criteres({
    campagnes,
    campagneId,
    contexte,
    niveaux,
    niveau,
    urls,
}: Props) {
    const volets = ORDRE.filter((volet) => niveaux?.[volet]);
    const [actif, setActif] = useState<Onglet>(
        niveau ?? volets[0] ?? "general",
    );

    return (
        <AdminLayout title="Critères de la campagne">
            <PageHeader
                title="Critères de la campagne"
                description="Paramétrage général de la campagne, puis critères d’admission et/ou de réadmission : critères de rejet, puis critères de classement de la liste provisoire."
                actions={
                    urls.editCampagne && (
                        <a
                            href={urls.editCampagne}
                            className={cn(
                                buttonVariants({
                                    variant: "outline",
                                    size: "sm",
                                }),
                                "h-10",
                            )}
                        >
                            <Pencil className="size-4" aria-hidden />{" "}
                            Modifier la campagne
                        </a>
                    )
                }
            />

            {campagnes.length === 0 || !contexte ? (
                <Vide icone={<ListChecks />}>
                    Aucune campagne n’est enregistrée : créez d’abord une
                    campagne.
                </Vide>
            ) : (
                <div className="space-y-6">
                    <ChoixCampagne
                        campagnes={campagnes}
                        valeur={campagneId}
                        url={urls.index}
                    />

                    <Tabs
                        value={actif}
                        onValueChange={(valeur) =>
                            setActif(valeur as Onglet)
                        }
                    >
                        <TabsList aria-label="Niveaux de paramétrage">
                            {volets.map((volet, index) => {
                                const code = niveaux![volet]!.etat.etat;
                                const parametre = code === "en_vigueur";
                                const Icone = parametre
                                    ? CircleCheck
                                    : CircleDashedCheck;
                                return (
                                    <TabsTrigger key={volet} value={volet}>
                                        <span className="tabular-nums">
                                            {index + 1}.
                                        </span>
                                        {JEUX[volet].titre}
                                        {/* Paramétré (critères appliqués) : coche verte ; sinon coche grise en pointillés. */}
                                        <Icone
                                            {...ICON}
                                            className={
                                                parametre
                                                    ? "text-[#006400]"
                                                    : "text-gray-400"
                                            }
                                            aria-label={
                                                ETATS_CRITERES[code].label
                                            }
                                        >
                                            <title>
                                                {ETATS_CRITERES[code].label}
                                            </title>
                                        </Icone>
                                    </TabsTrigger>
                                );
                            })}
                            {volets.length > 0 && (
                                <TabsTrigger value={EN_VIGUEUR}>
                                    <History {...ICON} aria-hidden />
                                    Critères en vigueur
                                </TabsTrigger>
                            )}
                        </TabsList>
                        {/* Les niveaux restent montés : la saisie en cours n'est pas perdue en changeant d'onglet. */}
                        {volets.map((volet) => (
                            <TabsContent
                                key={volet}
                                value={volet}
                                forceMount
                                className="data-[state=inactive]:hidden"
                            >
                                <Niveau page={niveaux![volet]!} />
                            </TabsContent>
                        ))}
                        {volets.length > 0 && (
                            <TabsContent
                                value={EN_VIGUEUR}
                                className="space-y-8"
                            >
                                {volets.map((volet) => (
                                    <section
                                        key={volet}
                                        aria-label={JEUX[volet].titre}
                                        className="space-y-3"
                                    >
                                        <EnTete volet={volet} />
                                        <CriteresConsultation
                                            {...niveaux![volet]!}
                                        />
                                    </section>
                                ))}
                            </TabsContent>
                        )}
                    </Tabs>

                    <div className="grid gap-4 lg:grid-cols-3">
                        <Bloc
                            titre="Calendrier de la campagne"
                            icone={<CalendarDays {...ICON} />}
                        >
                            <Lignes
                                lignes={[
                                    ["Ouverture", dateFr(contexte.periode_debut)],
                                    ["Clôture", dateFr(contexte.periode_fin)],
                                ]}
                            />
                        </Bloc>
                        <Bloc
                            titre="Documents demandés"
                            icone={<FileText {...ICON} />}
                        >
                            <Liste
                                elements={contexte.documents}
                                vide="Aucun document demandé."
                            />
                        </Bloc>
                        <Bloc
                            titre="Résidences ouvertes"
                            icone={<Hotel {...ICON} />}
                        >
                            <Liste
                                elements={contexte.residences}
                                vide="Aucune résidence associée."
                            />
                        </Bloc>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}

/**
 * Un niveau (paramétrage général ou volet) : le formulaire s'affiche directement, avec l'état et les actions.
 * Sans droit de modification, la version en vigueur est affichée à la place du formulaire.
 */
function Niveau({ page }: { page: PageNiveau }) {
    const actions =
        page.volet !== "general" &&
        !!((page.etat.brouillon && page.urls.appliquer) ||
            page.urls.abandonner);
    return (
        <section aria-label={JEUX[page.volet].titre} className="space-y-3">
            <EnTete volet={page.volet} />
            {/* Le paramétrage général s'applique depuis son assistant (dernière étape) : pas d'encart d'état. */}
            <div
                className={cn(
                    "grid gap-4",
                    actions
                        ? "xl:grid-cols-[minmax(0,1fr)_300px]"
                        : "max-w-5xl",
                )}
            >
                <div className="min-w-0">
                    {!page.urls.submit ? (
                        <CriteresConsultation {...page} />
                    ) : page.volet === "general" ? (
                        <CriteresGenerauxFormulaire {...page} />
                    ) : (
                        <CriteresFormulaire {...page} />
                    )}
                </div>
                {actions && (
                    <EtatCriteresVolet
                        volet={page.volet}
                        campagne={page.campagne}
                        etat={page.etat}
                        urls={page.urls}
                    />
                )}
            </div>
        </section>
    );
}

function EnTete({ volet }: { volet: Jeu }) {
    return (
        <h2 className="text-base font-medium text-gray-900">
            {JEUX[volet].titre}
        </h2>
    );
}

function Lignes({ lignes }: { lignes: [string, ReactNode][] }) {
    return (
        <dl className="divide-y">
            {lignes.map(([label, valeur]) => (
                <div
                    key={label}
                    className="flex items-center justify-between gap-3 px-4 py-2.5 text-sm"
                >
                    <dt className="text-muted">{label}</dt>
                    <dd className="text-gray-900">{valeur}</dd>
                </div>
            ))}
        </dl>
    );
}

function Liste({ elements, vide }: { elements: string[]; vide: string }) {
    if (elements.length === 0)
        return <p className="px-4 py-4 text-sm text-muted">{vide}</p>;
    return (
        <ul className="flex flex-wrap gap-1.5 p-4">
            {elements.map((e) => (
                <li key={e}>
                    <Badge variant="success">{e}</Badge>
                </li>
            ))}
        </ul>
    );
}
