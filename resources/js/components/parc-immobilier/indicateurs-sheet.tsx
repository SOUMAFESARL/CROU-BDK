import { BedDouble, Building, Building2, ChevronRight, Gauge, X, type LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { Categories, Occupation, Section } from './details-sheet';
import { nombre, pluriel, StatutBadge } from './labels';
import { OccupationBar, tauxOccupation } from './occupation-bar';
import type { DetailsIndicateurs, Indicateur, ParcProps, Resume } from './types';

type IndicateursSheetProps = {
    indicateur: Indicateur | null;
    onClose: () => void;
    stats: ParcProps['stats'];
    /** Absent tant que le rechargement partiel n'est pas revenu. */
    details: DetailsIndicateurs | undefined;
    naviguer: (params: Record<string, string>) => void;
};

type Aller = (params: Record<string, string>) => void;

const TITRES: Record<Indicateur, { titre: string; icon: LucideIcon }> = {
    cites: { titre: 'Cités', icon: Building2 },
    batiments: { titre: 'Bâtiments', icon: Building },
    chambres: { titre: 'Chambres', icon: BedDouble },
    occupation: { titre: 'Taux d’occupation', icon: Gauge },
};

/** Panneau de détail d'un indicateur du parc (ouvert au clic sur un KPI). */
export function IndicateursSheet({ indicateur, onClose, stats, details, naviguer }: IndicateursSheetProps) {
    const entete = indicateur ? TITRES[indicateur] : null;
    // Une ligne cliquée ouvre la cité ou le bâtiment dans le parc.
    const aller: Aller = (params) => {
        onClose();
        naviguer(params);
    };

    return (
        <Sheet open={indicateur !== null} onOpenChange={(open) => !open && onClose()}>
            <SheetContent side="right" showCloseButton={false} className="w-full gap-0 p-0 sm:max-w-md">
                {indicateur && entete && (
                    <>
                        <header className="flex items-start gap-3 border-b px-5 py-4">
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                                <entete.icon {...ICON} />
                            </span>
                            <div className="min-w-0 flex-1">
                                <SheetDescription className="text-xs text-muted">Détail de l’indicateur · parc entier</SheetDescription>
                                <SheetTitle className="truncate text-lg font-medium text-gray-900">{entete.titre}</SheetTitle>
                            </div>
                            <SheetClose className="inline-flex size-8 cursor-pointer items-center justify-center rounded-brand text-muted transition-colors hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none">
                                <X size={18} strokeWidth={1.25} />
                                <span className="sr-only">Fermer</span>
                            </SheetClose>
                        </header>

                        <div className="min-h-0 flex-1 space-y-6 overflow-y-auto px-5 py-5">
                            {!details ? (
                                <Chargement />
                            ) : indicateur === 'cites' ? (
                                <DetailCites details={details} aller={aller} />
                            ) : indicateur === 'batiments' ? (
                                <DetailBatiments details={details} aller={aller} />
                            ) : indicateur === 'chambres' ? (
                                <DetailChambres details={details} stats={stats} aller={aller} />
                            ) : (
                                <DetailOccupation details={details} stats={stats} aller={aller} />
                            )}
                        </div>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}

function DetailCites({ details, aller }: { details: DetailsIndicateurs; aller: Aller }) {
    return (
        <>
            <Section titre="Répartition par statut">
                <ParStatut statuts={details.cites.map((cite) => cite.statut)} />
            </Section>
            <Section titre={`Les cités (${details.cites.length})`}>
                <Liste vide="Aucune cité enregistrée.">
                    {details.cites.map((cite) => (
                        <Ligne
                            key={cite.id}
                            titre={cite.name}
                            sousTitre={[cite.code, pluriel(cite.batiments, 'bâtiment'), pluriel(cite.resume.chambres, 'chambre')].filter(Boolean).join(' · ')}
                            statut={cite.statut}
                            resume={cite.resume}
                            onClick={() => aller({ cite: cite.id })}
                        />
                    ))}
                </Liste>
            </Section>
        </>
    );
}

function DetailBatiments({ details, aller }: { details: DetailsIndicateurs; aller: Aller }) {
    // Bâtiments regroupés par cité, dans l'ordre des cités ; ceux sans cité connue à la fin.
    const idsCites = new Set(details.cites.map((cite) => cite.id));
    const groupes = [
        ...details.cites.map((cite) => ({ cle: cite.id, nom: cite.name, batiments: details.batiments.filter((b) => b.cite_id === cite.id) })),
        { cle: 'sans-cite', nom: 'Sans cité', batiments: details.batiments.filter((b) => !b.cite_id || !idsCites.has(b.cite_id)) },
    ].filter((groupe) => groupe.batiments.length > 0);

    return (
        <>
            <Section titre="Répartition par statut">
                <ParStatut statuts={details.batiments.map((batiment) => batiment.statut)} />
            </Section>
            {groupes.length === 0 ? (
                <Vide>Aucun bâtiment enregistré.</Vide>
            ) : (
                groupes.map((groupe) => (
                    <Section key={groupe.cle} titre={`${groupe.nom} (${groupe.batiments.length})`}>
                        <Liste vide="">
                            {groupe.batiments.map((batiment) => (
                                <Ligne
                                    key={batiment.id}
                                    titre={batiment.name}
                                    sousTitre={`${pluriel(batiment.resume.chambres, 'chambre')} · ${pluriel(batiment.resume.disponibles, 'disponible')}`}
                                    statut={batiment.statut}
                                    resume={batiment.resume}
                                    onClick={() => aller({ batiment: batiment.id })}
                                />
                            ))}
                        </Liste>
                    </Section>
                ))
            )}
        </>
    );
}

function DetailChambres({ details, stats, aller }: { details: DetailsIndicateurs; stats: ParcProps['stats']; aller: Aller }) {
    const etats = [
        { label: 'Disponibles', n: stats.disponibles, dot: 'bg-secondary/45' },
        { label: 'Occupées', n: stats.occupees, dot: 'bg-primary' },
        { label: 'Réservées', n: stats.reservees, dot: 'bg-accent' },
        { label: 'Maintenance / hors service', n: stats.maintenance, dot: 'bg-red-400' },
    ];
    const pourcentage = (n: number) => (stats.chambres > 0 ? Math.round((n / stats.chambres) * 100) : 0);

    return (
        <>
            <Section titre={`Par état (${pluriel(stats.chambres, 'chambre')})`}>
                <div className="space-y-3 rounded-brand border p-4">
                    <OccupationBar resume={{ ...stats, categories: {} }} />
                    <ul className="divide-y">
                        {etats.map((etat) => (
                            <li key={etat.label} className="flex items-center justify-between gap-3 py-2 text-sm">
                                <span className="flex items-center gap-2 text-gray-900">
                                    <span className={cn('size-2 rounded-full', etat.dot)} aria-hidden />
                                    {etat.label}
                                </span>
                                <span className="font-medium text-gray-900 tabular-nums">
                                    {nombre.format(etat.n)} <span className="font-normal text-muted">· {pourcentage(etat.n)} %</span>
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            </Section>

            <Section titre="Par type de chambre">
                {details.typesChambre.length === 0 ? (
                    <Vide>Aucune chambre configurée.</Vide>
                ) : (
                    <dl className="divide-y rounded-brand border">
                        {details.typesChambre.map((type) => (
                            <div key={type.name} className="flex items-center justify-between gap-4 px-3.5 py-2.5 text-sm">
                                <dt className="text-gray-900">{type.name}</dt>
                                <dd className="text-right">
                                    <span className="font-medium text-gray-900">{pluriel(type.chambres, 'chambre')}</span>
                                    <span className="block text-xs text-muted">
                                        {pluriel(type.disponibles, 'disponible')} · {pluriel(type.places, 'place')}
                                    </span>
                                </dd>
                            </div>
                        ))}
                    </dl>
                )}
            </Section>

            <Section titre="Par cité">
                <Liste vide="Aucune cité enregistrée.">
                    {details.cites.map((cite) => (
                        <Ligne
                            key={cite.id}
                            titre={cite.name}
                            sousTitre={`${pluriel(cite.resume.chambres, 'chambre')} · ${pluriel(cite.resume.disponibles, 'disponible')}`}
                            resume={cite.resume}
                            onClick={() => aller({ cite: cite.id })}
                        />
                    ))}
                </Liste>
            </Section>
        </>
    );
}

function DetailOccupation({ details, stats, aller }: { details: DetailsIndicateurs; stats: ParcProps['stats']; aller: Aller }) {
    return (
        <>
            <Occupation resume={{ ...stats, categories: details.categories }} />
            <Section titre="Étudiants par catégorie">
                <Categories categories={details.categories} />
            </Section>
            <Section titre="Par cité">
                <Liste vide="Aucune cité enregistrée.">
                    {details.cites.map((cite) => (
                        <Ligne
                            key={cite.id}
                            titre={cite.name}
                            sousTitre={`${nombre.format(cite.resume.occupants)} / ${pluriel(cite.resume.places, 'place')} occupée${cite.resume.occupants > 1 ? 's' : ''}`}
                            resume={cite.resume}
                            onClick={() => aller({ cite: cite.id })}
                        />
                    ))}
                </Liste>
            </Section>
        </>
    );
}

/** Nombre d'éléments par statut (badge + compteur). */
function ParStatut({ statuts }: { statuts: string[] }) {
    const compte = statuts.reduce<Record<string, number>>((acc, statut) => ({ ...acc, [statut]: (acc[statut] ?? 0) + 1 }), {});
    const entrees = Object.entries(compte).sort(([, a], [, b]) => b - a);

    if (entrees.length === 0) return <Vide>Aucun élément.</Vide>;

    return (
        <ul className="flex flex-wrap gap-2">
            {entrees.map(([statut, n]) => (
                <li key={statut} className="flex items-center gap-2 rounded-brand border px-3 py-2 text-sm">
                    <StatutBadge statut={statut} />
                    <span className="font-medium text-gray-900 tabular-nums">{nombre.format(n)}</span>
                </li>
            ))}
        </ul>
    );
}

function Liste({ vide, children }: { vide: string; children: ReactNode[] }) {
    if (children.length === 0) return <Vide>{vide}</Vide>;

    return <ul className="divide-y rounded-brand border">{children}</ul>;
}

/** Ligne cliquable : ouvre la cité ou le bâtiment dans le parc. */
function Ligne({ titre, sousTitre, statut, resume, onClick }: { titre: string; sousTitre: string; statut?: string; resume: Resume; onClick: () => void }) {
    return (
        <li>
            <button
                type="button"
                onClick={onClick}
                className="group flex w-full cursor-pointer items-center gap-3 px-3.5 py-3 text-left transition-colors hover:bg-soft focus-visible:bg-soft focus-visible:outline-none"
            >
                <div className="min-w-0 flex-1 space-y-1.5">
                    <div className="flex items-center gap-2">
                        <span className="truncate text-sm font-medium text-gray-900 group-hover:text-primary">{titre}</span>
                        {statut && <StatutBadge statut={statut} />}
                    </div>
                    <p className="truncate text-xs text-muted">{sousTitre}</p>
                    <div className="flex items-center gap-2">
                        <OccupationBar resume={resume} className="flex-1" />
                        <span className="w-10 shrink-0 text-right text-xs font-medium text-gray-900 tabular-nums">{tauxOccupation(resume)} %</span>
                    </div>
                </div>
                <ChevronRight size={16} strokeWidth={1.25} className="shrink-0 text-gray-400 group-hover:text-primary" aria-hidden />
            </button>
        </li>
    );
}

function Vide({ children }: { children: ReactNode }) {
    return <p className="rounded-brand border border-dashed px-4 py-6 text-center text-sm text-muted">{children}</p>;
}

function Chargement() {
    return (
        <div className="space-y-4" aria-busy="true" aria-label="Chargement du détail">
            <Skeleton className="h-16 w-full rounded-brand" />
            {Array.from({ length: 4 }, (_, index) => (
                <Skeleton key={index} className="h-14 w-full rounded-brand" />
            ))}
        </div>
    );
}
