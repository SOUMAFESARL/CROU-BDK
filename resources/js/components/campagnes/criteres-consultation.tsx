import {
    Building,
    CalendarClock,
    Layers,
    ListChecks,
    ListOrdered,
    ShieldCheck,
    Wallet,
} from "lucide-react";
import type { ReactNode } from "react";
import {
    BASES_C4,
    CATEGORIES,
    CODES_CATEGORIES,
    DEPARTAGE,
    DEPARTAGE_VOLET,
    GROUPES_C2,
    JEUX,
    lignesCredits,
    MERITE_ADMISSION,
    REPORT_PLACES,
    type PageNiveau,
    type PlacesCite,
    type Regles,
    type ReglesGenerales,
    type Volet,
} from "@/components/campagnes/criteres";
import { CartesAges } from "@/components/campagnes/cartes-ages";
import { formatDate } from "@/components/campagnes/types";
import { ICON } from "@/components/layout/app-sidebar";
import { Note } from "@/components/parametrage-hebergement/shared";
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/components/ui/accordion";

/**
 * Consultation d'un jeu de critères (paramétrage général ou volet) : version en vigueur (ou modèle CROU proposé tant
 * que rien n'est appliqué). Le brouillon en attente se relit et se modifie dans l'onglet du niveau concerné.
 */
export function CriteresConsultation(page: PageNiveau) {
    const { volet, campagne, enVigueur, modele } = page;
    const places = enVigueur?.places ?? modele?.places ?? null;

    return (
        <section className="space-y-4">
            <div className="space-y-3">
                {!campagne.modifiable && (
                    <Note icone={<ShieldCheck />}>
                        Campagne {campagne.verrou ?? "terminée"} : ses critères
                        sont figés.
                    </Note>
                )}
                {!enVigueur && (
                    <Note ton="attention">
                        <p>
                            Aucun critère {JEUX[volet].complement} n’est encore
                            appliqué à cette campagne.
                        </p>
                    </Note>
                )}
            </div>

            <Accordion key={volet} type="multiple">
                <Detail
                    page={page}
                    regles={enVigueur?.regles ?? modele?.regles ?? null}
                    places={places}
                />
            </Accordion>
        </section>
    );
}

/** Critère défini dans le paramétrage général : le volet ne fait que l'activer, sans en répéter les valeurs. */
const SELON_GENERAL = "Activé (selon le paramétrage général)";

/** Section repliable de la consultation (élément d'accordéon). */
function Section({
    valeur,
    titre,
    icone,
    children,
}: {
    valeur: string;
    titre: ReactNode;
    icone: ReactNode;
    children: ReactNode;
}) {
    return (
        <AccordionItem value={valeur}>
            <AccordionTrigger>
                <span className="text-primary" aria-hidden>
                    {icone}
                </span>
                {titre}
            </AccordionTrigger>
            <AccordionContent>{children}</AccordionContent>
        </AccordionItem>
    );
}

type DetailProps = {
    page: PageNiveau;
    regles: Regles | ReglesGenerales | null;
    places: PlacesCite[] | null;
};

/** Lecture d'un jeu de critères : paramétrage général, ou rejet puis classement d'un volet. */
function Detail({ page, regles, places }: DetailProps) {
    const { droits, filieres, niveauxEtude } = page;

    if (page.volet === "general") {
        const g = regles as ReglesGenerales | null;
        if (!droits.voirRegles || !g) return null;
        return (
            <>
                <Section
                    valeur="credits"
                    titre="Crédits exigés"
                    icone={<ShieldCheck {...ICON} />}
                >
                    <Lignes lignes={lignesCredits(g.credits, filieres)} />
                </Section>
                <Section
                    valeur="ages"
                    titre="Âge maximum par niveau"
                    icone={<ListChecks {...ICON} />}
                >
                    <CartesAges
                        ages={g.age_max}
                        niveaux={niveauxEtude}
                        className="p-4"
                    />
                </Section>
                <Section
                    valeur="rangs"
                    titre="Rangs et places réservées"
                    icone={<Layers {...ICON} />}
                >
                    <Lignes
                        lignes={[
                            [
                                "Rang par filière",
                                g.rang_filiere.actif
                                    ? `${g.rang_filiere.n} premiers · ${GROUPES_C2[g.rang_filiere.groupe].toLowerCase()}`
                                    : "Désactivé",
                            ],
                            [
                                "Clubs et associations",
                                g.clubs.actif
                                    ? `${g.clubs.n} premiers par discipline`
                                    : "Désactivé",
                            ],
                            [
                                "Cas sociaux",
                                g.cas_sociaux.actif
                                    ? `${g.cas_sociaux.taux} % ${BASES_C4[g.cas_sociaux.base]}`
                                    : "Désactivé",
                            ],
                            [
                                "Comité des résidents",
                                g.comite.actif
                                    ? `${g.comite.places} places`
                                    : "Désactivé",
                            ],
                        ]}
                    />
                </Section>
            </>
        );
    }

    const volet = page.volet;
    const v = regles as Regles | null;
    const r = v?.readmission;
    const a = v?.admission;
    const categories = page.general?.categories ?? null;
    const ageActif = r?.age_actif ?? a?.age_actif ?? false;

    return (
        <>
            {droits.voirPlaces && places && (
                <TablePlaces volet={volet} places={places} />
            )}

            {droits.voirRegles && v && (
                <>
                    <Section
                        valeur="rejet"
                        titre="1. Critères de rejet"
                        icone={<ShieldCheck {...ICON} />}
                    >
                        <Lignes
                            lignes={[
                                ...(r
                                    ? ([
                                          [
                                              "Loyers de l’année",
                                              r.loyers.actif
                                                  ? `${r.loyers.mois_exiges} mois soldés`
                                                  : "Non contrôlés",
                                          ],
                                          [
                                              "Crédits validés",
                                              r.credits_actif
                                                  ? SELON_GENERAL
                                                  : "Désactivé",
                                          ],
                                          [
                                              "Conduite en cité",
                                              r.conduite.actif
                                                  ? `Note de conduite d’au moins ${String(r.conduite.note_min ?? "—").replace(".", ",")}${page.noteConduite !== null ? ` / ${String(page.noteConduite).replace(".", ",")}` : ""} points, sinon rejet`
                                                  : "Désactivé",
                                          ],
                                      ] as [string, ReactNode][])
                                    : []),
                                ...(a
                                    ? ([
                                          [
                                              MERITE_ADMISSION[a.merite].seuil,
                                              a.seuil_actif && a.seuil !== null
                                                  ? `${a.seuil} ${MERITE_ADMISSION[a.merite].unite}`
                                                  : "Désactivé",
                                          ],
                                      ] as [string, ReactNode][])
                                    : []),
                                [
                                    "Âge maximum par niveau",
                                    ageActif ? SELON_GENERAL : "Désactivé",
                                ],
                                [
                                    "Étudiants internationaux",
                                    v.recevabilite.internationaux
                                        ? "Acceptés"
                                        : "Non acceptés",
                                ],
                            ]}
                        />
                    </Section>

                    <Section
                        valeur="classement"
                        titre="2. Critères de classement"
                        icone={<ListOrdered {...ICON} />}
                    >
                        <Lignes
                            lignes={[
                                ...(r
                                    ? ([
                                          [
                                              "Catégories actives",
                                              categories
                                                  ? CODES_CATEGORIES.filter(
                                                        (code) =>
                                                            categories[code]
                                                                .actif,
                                                    )
                                                        .map(
                                                            (code) =>
                                                                CATEGORIES[code]
                                                                    .label,
                                                        )
                                                        .join(" › ")
                                                  : "—",
                                          ],
                                          [
                                              "Priorité aux boursiers",
                                              v.classement
                                                  .priorite_boursiers ===
                                              "aucune"
                                                  ? "Non"
                                                  : "Oui",
                                          ],
                                          [
                                              "Places non pourvues",
                                              REPORT_PLACES[r.report_places],
                                          ],
                                      ] as [string, ReactNode][])
                                    : []),
                                ...(a
                                    ? ([
                                          [
                                              "Mérite",
                                              MERITE_ADMISSION[a.merite].label,
                                          ],
                                          [
                                              "Ville du bac",
                                              `Hors ${a.ville_bac} prioritaire`,
                                          ],
                                      ] as [string, ReactNode][])
                                    : []),
                                [
                                    "Départage",
                                    libelleDepartage(
                                        volet,
                                        v.classement.departage,
                                    ),
                                ],
                                [
                                    "Liste élargie",
                                    `× ${v.classement.k_liste_elargie} (titulaires puis complémentaires)`,
                                ],
                            ]}
                        />
                    </Section>

                    <Section
                        valeur="conditions"
                        titre="3. Conditions financières"
                        icone={<Wallet {...ICON} />}
                    >
                        <Lignes
                            lignes={[
                                ["Caution", `${v.conditions.caution_mois} mois de loyer`],
                                ["Avance", `${v.conditions.avance_mois} mois de loyer`],
                            ]}
                        />
                    </Section>

                    <Section
                        valeur="liste"
                        titre="Liste provisoire"
                        icone={<CalendarClock {...ICON} />}
                    >
                        <Lignes
                            lignes={[
                                [
                                    "Génération",
                                    v.calendrier.date_liste_provisoire
                                        ? formatDate(
                                              v.calendrier
                                                  .date_liste_provisoire,
                                          )
                                        : volet === "readmission"
                                          ? "Après le contrôle des préalables"
                                          : "À la clôture des dépôts",
                                ],
                                [
                                    "Égalité persistante",
                                    "Arbitrage motivé de la S/DAL",
                                ],
                            ]}
                        />
                    </Section>
                </>
            )}
        </>
    );
}

/** « 1. Âge · 2. Excellence (désactivés : comportement) ». */
function libelleDepartage(
    volet: Volet,
    actifs: Regles["classement"]["departage"],
) {
    const desactives = DEPARTAGE_VOLET[volet].filter(
        (critere) => !actifs.includes(critere),
    );
    return (
        actifs
            .map(
                (critere, index) => `${index + 1}. ${DEPARTAGE[critere].label}`,
            )
            .join(" · ") +
        (desactives.length
            ? ` (désactivés : ${desactives.map((critere) => DEPARTAGE[critere].label.toLowerCase()).join(", ")})`
            : "")
    );
}

function TablePlaces({
    volet,
    places,
}: {
    volet: Volet;
    places: PlacesCite[];
}) {
    const readmission = volet === "readmission";
    const valeur = (nombre: number | null | undefined) =>
        nombre === null || nombre === undefined ? (
            <span className="text-muted">À saisir</span>
        ) : (
            nombre
        );

    return (
        <Section
            valeur="places"
            titre="Places par cité"
            icone={<Building {...ICON} />}
        >
            {places.length === 0 ? (
                <p className="px-4 py-3 text-sm text-muted">
                    Aucune résidence n’est ouverte à cette campagne.
                </p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm tabular-nums">
                        <thead className="bg-surface/60 text-left text-xs text-muted">
                            <tr>
                                <th className="px-4 py-2 font-medium">Cité</th>
                                <th className="px-4 py-2 font-medium">
                                    Capacité
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Places {JEUX[volet].complement}
                                </th>
                                {readmission && (
                                    <th className="px-4 py-2 font-medium">
                                        Cas sociaux
                                    </th>
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {places.map((ligne) => (
                                <tr key={ligne.cite_id} className="border-t">
                                    <td className="px-4 py-2.5">
                                        <span className="text-gray-900">
                                            {ligne.cite}
                                        </span>
                                        {ligne.public && (
                                            <span className="block text-xs text-muted">
                                                {ligne.public}
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-2.5">
                                        {ligne.capacite}
                                    </td>
                                    <td className="px-4 py-2.5">
                                        {valeur(ligne.places)}
                                    </td>
                                    {readmission && (
                                        <td className="px-4 py-2.5">
                                            {ligne.cas_sociaux ?? "—"}
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </Section>
    );
}

function Lignes({ lignes }: { lignes: [string, ReactNode][] }) {
    return (
        <dl className="divide-y">
            {lignes.map(([label, valeur]) => (
                <div
                    key={label}
                    className="grid grid-cols-1 gap-1 px-4 py-2.5 sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] sm:gap-3"
                >
                    <dt className="text-xs text-muted sm:text-sm">{label}</dt>
                    <dd className="text-sm break-words text-gray-900">
                        {valeur}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
