import {
    Building,
    CalendarClock,
    History,
    Layers,
    ListOrdered,
    ShieldCheck,
    TriangleAlert,
    UserPlus,
} from "lucide-react";
import type { ReactNode } from "react";
import {
    BASES_C4,
    CATEGORIES,
    CODES_CATEGORIES,
    DEPARTAGE,
    DEPARTAGE_VOLET,
    formatDateHeure,
    GROUPES_C2,
    HANDICAP_VISIBLE,
    MERITE_ADMISSION,
    PRIORITE_BOURSIERS,
    REPORT_PLACES,
    SOURCE_BOURSIER,
    STATUTS_VERSION,
    tranchesEloignement,
    VOLETS,
    type DroitsCriteres,
    type FiliereOption,
    type PageCriteres,
    type PlacesCite,
    type Regles,
    type Volet,
} from "@/components/campagnes/criteres";
import { formatDate } from "@/components/campagnes/types";
import { ICON } from "@/components/layout/app-sidebar";
import { Bloc, Note } from "@/components/parametrage-hebergement/shared";
import { Badge } from "@/components/ui/badge";
import { StatCard } from "@/components/ui/stat-card";
import { cn } from "@/lib/utils";

/**
 * Consultation des critères d'un volet : version en vigueur (ou modèle CROU proposé tant que rien n'est appliqué)
 * et historique des versions. Le brouillon en attente se relit et se modifie dans l'onglet « Définir ».
 */
export function CriteresConsultation({
    volet,
    campagne,
    droits,
    enVigueur,
    etat,
    modele,
    versions,
    filieres,
}: PageCriteres) {
    const places = enVigueur?.places ?? modele?.places ?? null;
    const placesSaisies = (places ?? []).filter(
        (ligne) => ligne.places !== null,
    ).length;

    return (
        <section className="space-y-4">
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    tone={enVigueur ? "green" : "amber"}
                    label="Critères"
                    value={
                        enVigueur
                            ? "Appliqués"
                            : etat.brouillon
                              ? "Brouillon"
                              : "À appliquer"
                    }
                />
                <StatCard
                    tone="blue"
                    label="Brouillon en attente"
                    value={etat.brouillon ? "Oui" : "Aucun"}
                />
                <StatCard
                    tone="plum"
                    label="Cités avec places saisies"
                    value={
                        droits.voirPlaces
                            ? `${placesSaisies} / ${places?.length ?? 0}`
                            : "—"
                    }
                />
                <StatCard
                    tone="amber"
                    label="Public"
                    value={VOLETS[volet].public}
                />
            </div>

            <div className="space-y-3">
                {!campagne.modifiable && (
                    <Note icone={<ShieldCheck />}>
                        Campagne terminée : ses critères sont figés.
                    </Note>
                )}
                {!enVigueur && (
                    <Note icone={<TriangleAlert />} ton="attention">
                        <p>
                            Aucun critère {VOLETS[volet].complement} n’est
                            encore appliqué à cette campagne. Le modèle CROU de
                            Bondoukou ci-dessous est proposé ; adaptez-le dans
                            l’onglet « Définir les critères ».
                        </p>
                    </Note>
                )}
            </div>

            <Detail
                volet={volet}
                regles={enVigueur?.regles ?? modele?.regles ?? null}
                places={places}
                droits={droits}
                filieres={filieres}
            />

            {versions.length > 0 && (
                <Bloc
                    titre={`Historique des versions des critères ${VOLETS[volet].complement}`}
                    icone={<History {...ICON} />}
                >
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-surface/60 text-left text-xs text-muted">
                                <tr>
                                    <th className="px-4 py-2 font-medium">
                                        Version
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Statut
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Dernière modification
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Application
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Motif
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {versions.map((version) => (
                                    <tr
                                        key={version.id}
                                        className="border-t align-top"
                                    >
                                        <td className="px-4 py-2.5 tabular-nums">
                                            v{version.version}
                                        </td>
                                        <td className="px-4 py-2.5">
                                            <Badge
                                                variant={
                                                    STATUTS_VERSION[
                                                        version.statut
                                                    ].variant
                                                }
                                            >
                                                {
                                                    STATUTS_VERSION[
                                                        version.statut
                                                    ].label
                                                }
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2.5 whitespace-nowrap">
                                            {formatDateHeure(
                                                version.modifie_le,
                                            )}
                                            {version.auteur && (
                                                <span className="block text-xs text-muted">
                                                    {version.auteur}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-2.5 whitespace-nowrap">
                                            {version.applique_le
                                                ? formatDateHeure(
                                                      version.applique_le,
                                                  )
                                                : "—"}
                                            {version.applique_par && (
                                                <span className="block text-xs text-muted">
                                                    {version.applique_par}
                                                </span>
                                            )}
                                        </td>
                                        <td className="max-w-80 px-4 py-2.5 text-gray-700">
                                            {version.motif ?? "—"}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Bloc>
            )}
        </section>
    );
}

type DetailProps = {
    volet: Volet;
    regles: Regles | null;
    places: PlacesCite[] | null;
    droits: DroitsCriteres;
    filieres: FiliereOption[];
};

/** Lecture d'un jeu de critères, étage par étage de l'entonnoir du volet. */
function Detail({ volet, regles, places, droits, filieres }: DetailProps) {
    const r = regles?.readmission;
    const a = regles?.admission;

    return (
        <div className="space-y-4">
            {droits.voirPlaces && places && (
                <TablePlaces volet={volet} places={places} regles={regles} />
            )}

            {droits.voirRegles && regles && (
                <div className="grid gap-4 lg:grid-cols-2">
                    <Bloc
                        titre="E1 · Recevabilité et calendrier"
                        icone={<CalendarClock {...ICON} />}
                    >
                        <Lignes
                            lignes={[
                                ...(volet === "readmission"
                                    ? ([
                                          [
                                              "Contrôle des préalables",
                                              regles.calendrier
                                                  .date_controle_prealables
                                                  ? formatDate(
                                                        regles.calendrier
                                                            .date_controle_prealables,
                                                    )
                                                  : "À la clôture des dépôts",
                                          ],
                                      ] as [string, ReactNode][])
                                    : []),
                                [
                                    "Liste provisoire",
                                    regles.calendrier.date_liste_provisoire
                                        ? formatDate(
                                              regles.calendrier
                                                  .date_liste_provisoire,
                                          )
                                        : volet === "readmission"
                                          ? "Après le contrôle des préalables"
                                          : "À la clôture des dépôts",
                                ],
                                [
                                    "Étudiants internationaux",
                                    regles.recevabilite.internationaux
                                        ? "Acceptés"
                                        : "Non acceptés",
                                ],
                                [
                                    "Sanctions bloquantes",
                                    "Suspension, expulsion",
                                ],
                                ...(a
                                    ? ([
                                          [
                                              "Âge maximum en L1",
                                              a.age_max_L1
                                                  ? `${a.age_max_L1} ans`
                                                  : "Désactivé",
                                          ],
                                      ] as [string, ReactNode][])
                                    : []),
                            ]}
                        />
                    </Bloc>

                    {r && (
                        <Bloc
                            titre="E2 · Conditions pour postuler"
                            icone={<ShieldCheck {...ICON} />}
                        >
                            <Lignes
                                lignes={[
                                    [
                                        "Année validée exigée",
                                        r.ue_actif
                                            ? `${r.seuil_ue} % des UE (selon le PV)`
                                            : "Désactivé",
                                    ],
                                    [
                                        "Écoles sur concours",
                                        r.concours_actif
                                            ? `${r.seuil_concours} % minimum, sinon retrait de chambre`
                                            : "Désactivé",
                                    ],
                                    [
                                        "Filières sur concours",
                                        r.filieres_concours.length
                                            ? r.filieres_concours
                                                  .map(
                                                      (id) =>
                                                          filieres.find(
                                                              (filiere) =>
                                                                  filiere.value ===
                                                                  id,
                                                          )?.label ?? id,
                                                  )
                                                  .join(", ")
                                            : "Aucune sélectionnée",
                                    ],
                                    [
                                        "Loyers de l’année",
                                        r.loyers.actif
                                            ? `${r.loyers.mois_exiges} mois soldés (tolérance ${r.loyers.tolerance_fcfa.toLocaleString("fr-FR")} FCFA)`
                                            : "Non contrôlés",
                                    ],
                                    [
                                        "Âge maximum",
                                        r.age_max
                                            ? `${r.age_max} ans`
                                            : "Désactivé",
                                    ],
                                ]}
                            />
                        </Bloc>
                    )}

                    {r && (
                        <Bloc
                            titre="E4 · Catégories en cascade"
                            icone={<Layers {...ICON} />}
                        >
                            <ol className="divide-y">
                                {CODES_CATEGORIES.map((code) => {
                                    const categorie = r.categories[code];
                                    const detail =
                                        code === "C2"
                                            ? `${r.categories.C2.n} premiers · ${GROUPES_C2[r.categories.C2.groupe].toLowerCase()}`
                                            : code === "C3"
                                              ? `${r.categories.C3.n} premiers par discipline`
                                              : code === "C4"
                                                ? `${r.categories.C4.taux} % ${BASES_C4[r.categories.C4.base]}`
                                                : code === "C5"
                                                  ? r.categories.C5.prealables
                                                      ? "Conditions pour postuler exigées"
                                                      : "Sans conditions pour postuler"
                                                  : null;
                                    return (
                                        <li
                                            key={code}
                                            className={cn(
                                                "flex items-start gap-3 px-4 py-2.5",
                                                !categorie.actif &&
                                                    "opacity-60",
                                            )}
                                        >
                                            <Badge
                                                variant={
                                                    categorie.actif
                                                        ? "success"
                                                        : "neutral"
                                                }
                                            >
                                                {code}
                                            </Badge>
                                            <div className="min-w-0 flex-1 text-sm">
                                                <p className="text-gray-900">
                                                    {CATEGORIES[code].label}
                                                    {!categorie.actif && (
                                                        <span className="text-muted">
                                                            {" "}
                                                            · désactivée
                                                        </span>
                                                    )}
                                                </p>
                                                <p className="text-xs text-muted">
                                                    {detail ??
                                                        CATEGORIES[code]
                                                            .condition}
                                                </p>
                                                <p className="text-xs text-muted">
                                                    Mérite :{" "}
                                                    {CATEGORIES[code].merite}
                                                </p>
                                            </div>
                                        </li>
                                    );
                                })}
                            </ol>
                            <p className="border-t px-4 py-2.5 text-xs text-muted">
                                Places non pourvues :{" "}
                                {REPORT_PLACES[r.report_places].toLowerCase()}.
                            </p>
                        </Bloc>
                    )}

                    {a && (
                        <Bloc
                            titre="Mérite et éloignement"
                            icone={<UserPlus {...ICON} />}
                        >
                            <Lignes
                                lignes={[
                                    ["Mérite", MERITE_ADMISSION[a.merite]],
                                    [
                                        "Handicap visible",
                                        HANDICAP_VISIBLE[a.handicap_visible],
                                    ],
                                ]}
                            />
                            <ul className="border-t px-4 py-2.5 text-xs text-gray-700">
                                {tranchesEloignement(
                                    a.eloignement.tranches_km,
                                ).map((tranche) => (
                                    <li key={tranche.code} className="py-0.5">
                                        <span className="tabular-nums">
                                            {tranche.code}
                                        </span>{" "}
                                        · {tranche.libelle}{" "}
                                        <span className="text-muted">
                                            ({tranche.ordre})
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </Bloc>
                    )}

                    <Bloc
                        titre="E5 · Classement et liste élargie"
                        icone={<ListOrdered {...ICON} />}
                    >
                        <Lignes
                            lignes={[
                                [
                                    "Priorité aux boursiers",
                                    PRIORITE_BOURSIERS[
                                        regles.classement.priorite_boursiers
                                    ].label,
                                ],
                                [
                                    "Statut de boursier",
                                    SOURCE_BOURSIER[
                                        regles.classement.source_boursier
                                    ],
                                ],
                                [
                                    "Départage",
                                    libelleDepartage(
                                        volet,
                                        regles.classement.departage,
                                    ),
                                ],
                                [
                                    "Liste élargie",
                                    `× ${regles.classement.k_liste_elargie} (titulaires puis complémentaires)`,
                                ],
                                [
                                    "Égalité persistante",
                                    "Arbitrage motivé de la S/DAL",
                                ],
                            ]}
                        />
                    </Bloc>
                </div>
            )}
        </div>
    );
}

/** « 1. Âge · 2. Excellence (désactivés : comportement) ». */
function libelleDepartage(volet: Volet, actifs: Regles["classement"]["departage"]) {
    const desactives = DEPARTAGE_VOLET[volet].filter(
        (critere) => !actifs.includes(critere),
    );
    return (
        actifs
            .map((critere, index) => `${index + 1}. ${DEPARTAGE[critere].label}`)
            .join(" · ") +
        (desactives.length
            ? ` (désactivés : ${desactives.map((critere) => DEPARTAGE[critere].label.toLowerCase()).join(", ")})`
            : "")
    );
}

function TablePlaces({
    volet,
    places,
    regles,
}: {
    volet: Volet;
    places: PlacesCite[];
    regles: Regles | null;
}) {
    const readmission = volet === "readmission";
    const actif = (code: "C1" | "C3" | "C4" | "C5") =>
        regles?.readmission?.categories[code].actif ?? true;
    const valeur = (nombre: number | null | undefined) =>
        nombre === null || nombre === undefined ? (
            <span className="text-muted">À saisir</span>
        ) : (
            nombre
        );

    return (
        <Bloc titre="E0 · Places par cité" icone={<Building {...ICON} />}>
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
                                    Places {VOLETS[volet].complement}
                                </th>
                                {readmission && actif("C1") && (
                                    <th className="px-4 py-2 font-medium">
                                        C1 Concours
                                    </th>
                                )}
                                {readmission && actif("C3") && (
                                    <th className="px-4 py-2 font-medium">
                                        C3 Clubs
                                    </th>
                                )}
                                {readmission && actif("C4") && (
                                    <th className="px-4 py-2 font-medium">
                                        C4 Cas sociaux
                                    </th>
                                )}
                                {readmission && actif("C5") && (
                                    <th className="px-4 py-2 font-medium">
                                        C5 Comité
                                    </th>
                                )}
                                {readmission && (
                                    <th className="px-4 py-2 font-medium">
                                        Solde C2 / C6
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
                                    {readmission && actif("C1") && (
                                        <td className="px-4 py-2.5">
                                            {valeur(ligne.quotas?.C1)}
                                        </td>
                                    )}
                                    {readmission && actif("C3") && (
                                        <td className="px-4 py-2.5">
                                            {valeur(ligne.quotas?.C3)}
                                        </td>
                                    )}
                                    {readmission && actif("C4") && (
                                        <td className="px-4 py-2.5">
                                            {ligne.cas_sociaux ?? "—"}
                                        </td>
                                    )}
                                    {readmission && actif("C5") && (
                                        <td className="px-4 py-2.5">
                                            {valeur(ligne.quotas?.C5)}
                                        </td>
                                    )}
                                    {readmission && (
                                        <td className="px-4 py-2.5">
                                            {ligne.solde ?? "—"}
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </Bloc>
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
