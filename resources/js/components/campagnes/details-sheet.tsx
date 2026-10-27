import { BedDouble, Building, Building2, CalendarCheck, CalendarClock, CalendarDays, CircleCheck, CircleX, FileText, FolderOpen, GraduationCap, Houses, ListChecks, X } from 'lucide-react';
import type { ReactNode } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { CONDITIONS, dureeJours, formatDate, NIVEAUX, STATUTS_CAMPAGNE, TYPES_ADMISSION, typeCampagne, type CampagneItem, type Niveau, type StatutCampagne } from './types';

type DetailsSheetProps = {
    campagne: CampagneItem | null;
    onClose: () => void;
};

/** Panneau latéral de détail d'une campagne (remplace l'ancien tiroir <dialog>). */
export function CampagneDetailsSheet({ campagne, onClose }: DetailsSheetProps) {
    return (
        <Sheet open={campagne !== null} onOpenChange={(open) => !open && onClose()}>
            <SheetContent side="right" showCloseButton={false} className="w-full gap-0 p-0 sm:max-w-md">
                {campagne && <Contenu campagne={campagne} />}
            </SheetContent>
        </Sheet>
    );
}

export function StatutCampagneBadge({ statut }: { statut: CampagneItem['statut'] }) {
    const info = STATUTS_CAMPAGNE[statut as StatutCampagne] ?? STATUTS_CAMPAGNE.terminer;
    return <Badge variant={info.variant}>{info.label}</Badge>;
}

function Contenu({ campagne }: { campagne: CampagneItem }) {
    const places = campagne.residences.reduce((total, residence) => total + residence.places, 0);
    const duree = dureeJours(campagne.periode_debut, campagne.periode_fin);
    const { criteres } = campagne;
    const ages = Object.entries(criteres.ages) as [Niveau, number][];

    return (
        <>
            <header className="flex items-start gap-3 border-b px-5 py-4">
                <span className="flex size-11 shrink-0 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                    <CalendarCheck {...ICON} />
                </span>
                <div className="min-w-0 flex-1">
                    <SheetDescription className="text-xs text-muted">Détail de la campagne</SheetDescription>
                    <SheetTitle className="truncate text-lg font-medium text-gray-900">Campagne {campagne.annee}</SheetTitle>
                    <div className="mt-2 flex flex-wrap gap-1.5">
                        <StatutCampagneBadge statut={campagne.statut} />
                        <Badge variant="success">{typeCampagne(campagne.conditions)}</Badge>
                    </div>
                    <p className="mt-2 flex items-center gap-1.5 text-sm text-gray-700">
                        <CalendarDays {...ICON} className="text-muted" aria-hidden />
                        {formatDate(campagne.periode_debut)} <span aria-hidden>→</span>
                        <span className="sr-only">au</span> {formatDate(campagne.periode_fin)}
                        {duree !== null && <span className="text-muted">· {duree} jours</span>}
                    </p>
                </div>
                <SheetClose className="inline-flex size-8 cursor-pointer items-center justify-center rounded-brand text-muted transition-colors hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none">
                    <X {...ICON} />
                    <span className="sr-only">Fermer</span>
                </SheetClose>
            </header>

            <div className="min-h-0 flex-1 space-y-6 overflow-y-auto px-5 py-5">
                <div className="grid grid-cols-3 gap-2">
                    <Chiffre icon={<Houses />} valeur={campagne.residences.length} libelle="Résidence(s)" />
                    <Chiffre icon={<BedDouble />} valeur={places} libelle="Place(s) libre(s)" />
                    <Chiffre icon={<FileText />} valeur={campagne.documents.length} libelle="Document(s)" />
                </div>

                {campagne.description && <p className="text-sm whitespace-pre-line text-gray-700">{campagne.description}</p>}

                <Section titre="Calendrier" icon={<CalendarClock />}>
                    <Lignes
                        lignes={[
                            ['Ouverture', formatDate(campagne.periode_debut)],
                            ['Échéance des demandes', formatDate(campagne.date_echeance_demande)],
                            ['Échéance des contrats', formatDate(campagne.date_echeance_contrat)],
                            ['Clôture', formatDate(campagne.periode_fin)],
                            ['Validation automatique', campagne.date_validation_auto ? `Le ${formatDate(campagne.date_validation_auto)}` : 'Dès la réception'],
                        ]}
                    />
                </Section>

                <Section titre="Critères d’éligibilité" icon={<GraduationCap />}>
                    <Lignes
                        lignes={[
                            ['Moyenne minimale', criteres.moyenne_min !== null ? `${criteres.moyenne_min}/20` : '—'],
                            ['Crédits validés', criteres.pourcentage_validate !== null ? `${criteres.pourcentage_validate} % (${Math.ceil((criteres.pourcentage_validate * 60) / 100)}/60 crédits)` : '—'],
                            ['Distance domicile – campus', criteres.distance_min !== null ? `${criteres.distance_min} km` : '—'],
                            ...ages.map(([niveau, age]): [string, string] => [`Âge maximum ${NIVEAUX[niveau]}`, `${age} ans`]),
                        ]}
                    />
                </Section>

                <Section titre="Conditions" icon={<ListChecks />}>
                    <ul className="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                        {[...TYPES_ADMISSION, ...CONDITIONS].map(({ name, label }) => {
                            const oui = campagne.conditions[name];
                            return (
                                <li key={name} className={cn('flex items-center gap-2 text-sm', oui ? 'text-gray-900' : 'text-muted')}>
                                    {oui ? (
                                        <CircleCheck {...ICON} className="shrink-0 text-primary" aria-hidden />
                                    ) : (
                                        <CircleX {...ICON} className="shrink-0 text-gray-400" aria-hidden />
                                    )}
                                    <span>{label}</span>
                                    <span className="sr-only">: {oui ? 'oui' : 'non'}</span>
                                </li>
                            );
                        })}
                    </ul>
                </Section>

                <Section titre="Résidences concernées" icon={<Building2 />}>
                    {campagne.residences.length === 0 ? (
                        <Vide>Aucune résidence associée.</Vide>
                    ) : (
                        <ul className="space-y-2">
                            {campagne.residences.map((residence, index) => (
                                <li key={`${residence.name}-${index}`} className="flex items-center gap-3 rounded-brand border px-3.5 py-2.5">
                                    <Building {...ICON} className="shrink-0 text-primary" aria-hidden />
                                    <span className="min-w-0 flex-1 truncate text-sm text-gray-900">{residence.name}</span>
                                    <Badge variant="success">{residence.places} place(s)</Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </Section>

                <Section titre="Documents requis" icon={<FolderOpen />}>
                    {campagne.documents.length === 0 ? (
                        <Vide>Aucun document requis.</Vide>
                    ) : (
                        <ul className="space-y-2">
                            {campagne.documents.map((document, index) => (
                                <li key={`${document}-${index}`} className="flex items-center gap-3 rounded-brand border px-3.5 py-2.5 text-sm text-gray-900">
                                    <FileText {...ICON} className="shrink-0 text-sky-700" aria-hidden />
                                    {document}
                                </li>
                            ))}
                        </ul>
                    )}
                </Section>
            </div>

            <footer className="flex justify-end border-t px-5 py-4">
                <SheetClose asChild>
                    <Button variant="outline" size="sm" className="h-10">
                        Fermer
                    </Button>
                </SheetClose>
            </footer>
        </>
    );
}

function Chiffre({ icon, valeur, libelle }: { icon: ReactNode; valeur: number; libelle: string }) {
    return (
        <div className="flex flex-col items-center gap-1 rounded-brand border px-2 py-3 text-center">
            <span className="text-primary [&_svg]:size-[18px] [&_svg]:stroke-[1.25]" aria-hidden>
                {icon}
            </span>
            <strong className="text-lg leading-5 font-medium text-gray-900 tabular-nums">{valeur}</strong>
            <span className="text-xs text-muted">{libelle}</span>
        </div>
    );
}

function Section({ titre, icon, children }: { titre: string; icon: ReactNode; children: ReactNode }) {
    return (
        <section className="space-y-2.5">
            <h3 className="flex items-center gap-2 text-sm font-medium text-gray-900">
                <span className="text-primary [&_svg]:size-[18px] [&_svg]:stroke-[1.25]" aria-hidden>
                    {icon}
                </span>
                {titre}
            </h3>
            {children}
        </section>
    );
}

function Lignes({ lignes }: { lignes: [string, string][] }) {
    return (
        <dl className="divide-y rounded-brand border">
            {lignes.map(([label, valeur]) => (
                <div key={label} className="flex justify-between gap-4 px-3.5 py-2.5 text-sm">
                    <dt className="text-muted">{label}</dt>
                    <dd className="text-right font-medium text-gray-900 tabular-nums">{valeur}</dd>
                </div>
            ))}
        </dl>
    );
}

function Vide({ children }: { children: ReactNode }) {
    return <p className="rounded-brand border border-dashed px-4 py-5 text-center text-sm text-muted">{children}</p>;
}
