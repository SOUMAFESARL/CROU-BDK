import { ArrowRight, Pencil, Power, PowerOff, Trash2, UserRound, X } from 'lucide-react';
import type { ReactNode } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { Button } from '@/components/ui/button';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { peutModifier, type ParcActions } from './actions';
import { EtatBadge, estActif, nombre, StatutBadge, TYPE_ICONS, TYPE_LABELS } from './labels';
import { OccupationBar, tauxOccupation } from './occupation-bar';
import type { ParcItem, Resume } from './types';

type DetailsSheetProps = {
    item: ParcItem | null;
    onClose: () => void;
    actions: ParcActions;
};

const CONFORT: Record<string, string> = { standard: 'Standard', eleve: 'Élevé' };

/** Panneau latéral de détails (remplace l'ancienne fenêtre « Détails »). */
export function DetailsSheet({ item, onClose, actions }: DetailsSheetProps) {
    return (
        <Sheet open={item !== null} onOpenChange={(open) => !open && onClose()}>
            <SheetContent side="right" showCloseButton={false} className="w-full gap-0 p-0 sm:max-w-md">
                {item && <Contenu item={item} actions={actions} onClose={onClose} />}
            </SheetContent>
        </Sheet>
    );
}

function Contenu({ item, actions, onClose }: { item: ParcItem; actions: ParcActions; onClose: () => void }) {
    const Icon = TYPE_ICONS[item.type];
    const actif = estActif(item);
    const agir = (action: (item: ParcItem) => void) => () => {
        onClose();
        action(item);
    };

    return (
        <>
            <header className="flex items-start gap-3 border-b px-5 py-4">
                <span className="flex size-11 shrink-0 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                    <Icon {...ICON} />
                </span>
                <div className="min-w-0 flex-1">
                    <SheetDescription className="text-xs text-muted">{TYPE_LABELS[item.type]}</SheetDescription>
                    <SheetTitle className="truncate text-lg font-medium text-gray-900">
                        {item.type === 'chambre' ? `Chambre ${item.name}` : item.name}
                    </SheetTitle>
                    <div className="mt-2 flex flex-wrap gap-1.5">
                        <StatutBadge statut={item.statut} />
                        {item.type === 'chambre' && <EtatBadge etat={item.etat} />}
                    </div>
                </div>
                <SheetClose className="inline-flex size-8 cursor-pointer items-center justify-center rounded-brand text-muted transition-colors hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none">
                    <X size={18} strokeWidth={1.25} />
                    <span className="sr-only">Fermer</span>
                </SheetClose>
            </header>

            <div className="min-h-0 flex-1 space-y-6 overflow-y-auto px-5 py-5">
                {item.type !== 'chambre' && <Occupation resume={item.resume} />}

                <Section titre="Informations">
                    <dl className="divide-y rounded-brand border">
                        {lignes(item).map(([label, valeur]) => (
                            <div key={label} className="flex justify-between gap-4 px-3.5 py-2.5 text-sm">
                                <dt className="text-muted">{label}</dt>
                                <dd className="text-right font-medium text-gray-900">{valeur ?? <span className="font-normal text-muted">Non renseigné</span>}</dd>
                            </div>
                        ))}
                    </dl>
                </Section>

                {item.type === 'chambre' ? (
                    <Section titre={`Étudiants affectés (${item.etudiants.length}/${item.capacite})`}>
                        {item.etudiants.length === 0 ? (
                            <p className="rounded-brand border border-dashed px-4 py-6 text-center text-sm text-muted">Aucun étudiant affecté.</p>
                        ) : (
                            <ul className="space-y-2">
                                {item.etudiants.map((etudiant, index) => (
                                    <li key={`${etudiant.matricule}-${index}`} className="flex items-center gap-3 rounded-brand border px-3.5 py-2.5">
                                        <span className="flex size-8 items-center justify-center rounded-full bg-soft text-primary" aria-hidden>
                                            <UserRound size={16} strokeWidth={1.25} />
                                        </span>
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-gray-900">{etudiant.nom}</p>
                                            <p className="text-xs text-muted">{etudiant.matricule ?? 'Matricule non renseigné'}</p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Section>
                ) : (
                    <Section titre="Étudiants par catégorie">
                        <Categories categories={item.resume.categories} />
                    </Section>
                )}
            </div>

            <footer className="flex flex-wrap items-center gap-2 border-t px-5 py-4">
                {item.type !== 'chambre' && (
                    <Button variant="accent" size="sm" className="h-10" onClick={agir(actions.ouvrir)}>
                        {item.type === 'cite' ? 'Voir les bâtiments' : 'Voir les chambres'}
                        <ArrowRight size={16} strokeWidth={1.25} aria-hidden />
                    </Button>
                )}
                {peutModifier(item, actions) &&
                    (item.type === 'chambre' ? (
                        <Button variant="secondary" size="sm" className="h-10" onClick={agir((chambre) => actions.modifier?.(chambre))}>
                            <Pencil size={16} strokeWidth={1.25} aria-hidden />
                            Modifier
                        </Button>
                    ) : (
                        <a
                            href={item.urls.edit ?? undefined}
                            className="inline-flex h-10 items-center gap-2 rounded-brand border border-primary bg-white px-3 text-sm font-semibold text-primary transition-colors hover:bg-soft"
                        >
                            <Pencil size={16} strokeWidth={1.25} aria-hidden />
                            Modifier
                        </a>
                    ))}
                <div className="ml-auto flex gap-1">
                    {item.urls.status && (
                        <Button
                            variant="ghost"
                            size="sm"
                            className={cn('h-10', actif ? 'text-red-600 hover:bg-red-50 hover:text-red-700' : '')}
                            onClick={agir(actions.basculer)}
                        >
                            {actif ? <PowerOff size={16} strokeWidth={1.25} aria-hidden /> : <Power size={16} strokeWidth={1.25} aria-hidden />}
                            {actif ? 'Désactiver' : 'Activer'}
                        </Button>
                    )}
                    {item.urls.destroy && (
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-10 w-10 text-red-600 hover:bg-red-50 hover:text-red-700"
                            aria-label="Supprimer"
                            title="Supprimer"
                            onClick={agir(actions.supprimer)}
                        >
                            <Trash2 size={16} strokeWidth={1.25} aria-hidden />
                        </Button>
                    )}
                </div>
            </footer>
        </>
    );
}

function lignes(item: ParcItem): [string, ReactNode][] {
    switch (item.type) {
        case 'cite':
            return [
                ['Code', item.code],
                ['Type d’occupation', item.occupation],
                ['Localisation', item.localisation],
                ['Responsable', item.responsable],
                ['Bâtiments', `${item.batiments} (${item.batiments - item.batiments_desactives} actif${item.batiments - item.batiments_desactives > 1 ? 's' : ''}, ${item.batiments_desactives} désactivé${item.batiments_desactives > 1 ? 's' : ''})`],
                ['Chambres', nombre.format(item.resume.chambres)],
            ];
        case 'batiment':
            return [
                ['Cité', item.cite],
                ['Type d’occupation', item.occupation],
                ['Concierge', item.responsable],
                ['Niveaux', item.niveaux > 0 ? item.niveaux : null],
                ['Chambres', nombre.format(item.resume.chambres)],
            ];
        case 'chambre':
            return [
                ['Type de chambre', item.type_chambre],
                ['Type d’occupation', item.occupation],
                ['Niveau', item.niveau],
                ['Capacité', `${item.capacite} place${item.capacite > 1 ? 's' : ''}`],
                ['Lits', item.nb_lits || null],
                ['Superficie', item.superficie ? `${nombre.format(item.superficie)} m²` : null],
                ['Loyer', item.prix ? `${nombre.format(item.prix)} FCFA` : null],
                ['Confort', item.niveau_confort ? (CONFORT[item.niveau_confort] ?? item.niveau_confort) : null],
                ['Accès PMR', item.pmr ? 'Oui' : 'Non'],
            ];
    }
}

function Section({ titre, children }: { titre: string; children: ReactNode }) {
    return (
        <section className="space-y-2.5">
            <h3 className="text-xs font-medium tracking-wide text-muted uppercase">{titre}</h3>
            {children}
        </section>
    );
}

function Occupation({ resume }: { resume: Resume }) {
    return (
        <div className="rounded-brand border bg-surface/60 p-4">
            <div className="mb-3 flex items-end justify-between">
                <div>
                    <p className="text-xs text-muted">Taux d’occupation</p>
                    <p className="text-2xl font-medium text-gray-900">{tauxOccupation(resume)} %</p>
                </div>
                <p className="text-right text-xs text-muted">
                    <span className="block text-sm font-medium text-gray-900">
                        {nombre.format(resume.occupants)} / {nombre.format(resume.places)}
                    </span>
                    places occupées
                </p>
            </div>
            <OccupationBar resume={resume} legend />
        </div>
    );
}

function Categories({ categories }: { categories: Record<string, number> }) {
    const entrees = Object.entries(categories);
    const total = entrees.reduce((somme, [, n]) => somme + n, 0);

    if (entrees.length === 0) {
        return <p className="rounded-brand border border-dashed px-4 py-6 text-center text-sm text-muted">Aucun étudiant hébergé.</p>;
    }

    return (
        <ul className="space-y-3">
            {entrees.map(([categorie, n]) => (
                <li key={categorie} className="space-y-1.5">
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-900">{categorie}</span>
                        <span className="font-medium text-gray-900">{nombre.format(n)}</span>
                    </div>
                    <div className="h-1.5 overflow-hidden rounded-full bg-gray-100">
                        <span className="block h-full rounded-full bg-secondary" style={{ width: `${(n / total) * 100}%` }} />
                    </div>
                </li>
            ))}
        </ul>
    );
}
