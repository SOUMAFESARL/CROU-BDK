import { CalendarDays, FileText, Hotel, ListChecks, Pencil } from "lucide-react";
import { useState, type ReactNode } from "react";
import { CriteresConsultation } from "@/components/campagnes/criteres-consultation";
import {
    CriteresFormulaire,
    EtatCriteresVolet,
} from "@/components/campagnes/criteres-formulaire";
import {
    ETATS_CRITERES,
    VOLETS,
    type PageCriteres,
    type Volet,
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
    /** Un niveau par volet ouvert par la campagne (admission puis réadmission) ; null sans droit de consulter. */
    niveaux: Partial<Record<Volet, PageCriteres>> | null;
    /** Niveau affiché à l'ouverture (`?volet=`, sinon le premier). */
    niveau: Volet | null;
    urls: {
        index: string;
        editCampagne: string | null;
    };
};

const ORDRE: Volet[] = ["admission", "readmission"];

const DESCRIPTIONS: Record<Volet, string> = {
    admission:
        "Nouveaux étudiants : places par cité, recevabilité, mérite, éloignement et classement.",
    readmission:
        "Anciens résidents : places par cité, conditions pour postuler, catégories prioritaires et classement.",
};

/**
 * « Critères de la campagne » : les critères se paramètrent sur une seule page, en deux niveaux
 * (admission puis réadmission) pour une campagne d'admission et de réadmission, un seul niveau sinon.
 * Passer d'un niveau à l'autre ne change pas de page et conserve la saisie en cours.
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
    const [actif, setActif] = useState<Volet>(
        niveau ?? volets[0] ?? "admission",
    );

    return (
        <AdminLayout title="Critères de la campagne">
            <PageHeader
                title="Critères de la campagne"
                description="Paramétrez en une fois les critères de la campagne (places, conditions et classement) : admission et/ou réadmission selon le type de campagne."
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

                    {volets.length === 1 && (
                        <Niveau page={niveaux![volets[0]]!} />
                    )}

                    {volets.length > 1 && (
                        <Tabs
                            value={actif}
                            onValueChange={(valeur) =>
                                setActif(valeur as Volet)
                            }
                        >
                            <TabsList aria-label="Niveaux de paramétrage">
                                {volets.map((volet, index) => {
                                    const etat =
                                        ETATS_CRITERES[
                                            niveaux![volet]!.etat.etat
                                        ];
                                    return (
                                        <TabsTrigger key={volet} value={volet}>
                                            <span className="tabular-nums">
                                                {index + 1}.
                                            </span>
                                            {VOLETS[volet].titre}
                                            <Badge variant={etat.variant}>
                                                {etat.label}
                                            </Badge>
                                        </TabsTrigger>
                                    );
                                })}
                            </TabsList>
                            {/* Les deux niveaux restent montés : la saisie en cours n'est pas perdue en changeant de niveau. */}
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
                        </Tabs>
                    )}

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

/** Un niveau (volet) : définition des critères, version en vigueur et historique, état et actions. */
function Niveau({ page }: { page: PageCriteres }) {
    const [onglet, setOnglet] = useState(
        page.urls.submit ? "definir" : "consulter",
    );

    return (
        <section aria-label={VOLETS[page.volet].titre} className="space-y-3">
            <div className="flex flex-col gap-0.5">
                <h2 className="text-base font-medium text-gray-900">
                    {VOLETS[page.volet].titre}
                </h2>
                <p className="text-sm text-muted">
                    {DESCRIPTIONS[page.volet]}
                </p>
            </div>
            <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_300px]">
                <Tabs
                    value={onglet}
                    onValueChange={setOnglet}
                    className="min-w-0"
                >
                    <TabsList>
                        <TabsTrigger value="definir">
                            Définir les critères
                        </TabsTrigger>
                        <TabsTrigger value="consulter">
                            Critères en vigueur et historique
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent value="definir">
                        <CriteresFormulaire {...page} />
                    </TabsContent>
                    <TabsContent value="consulter">
                        <CriteresConsultation {...page} />
                    </TabsContent>
                </Tabs>
                <EtatCriteresVolet
                    volet={page.volet}
                    campagne={page.campagne}
                    etat={page.etat}
                    urls={page.urls}
                />
            </div>
        </section>
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
