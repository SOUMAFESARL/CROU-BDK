import { router } from '@inertiajs/react';
import { ArrowLeft, Ban, Check, ClipboardPlus, Clock, Mail, Pause, Pencil, Phone, Play, RotateCcw, Star, UserCheck, Wrench } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { AssignerDialog, ClotureDialog, InterventionDialog, SatisfactionDialog } from '@/components/maintenance/actions-dialogs';
import { fcfa, libelleSpecialite, PrioriteBadge, RESULTATS, StatutOtBadge, STATUTS_OT, type Maintenancier } from '@/components/maintenance/shared';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { PageHeader } from '@/components/ui/page-header';
import { Chronologie, dateHeure, Lignes, Panneau, type EvenementChronologie } from '@/components/vie-residentielle/shared';
import { dateFr, initiales, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';

type Ordre = {
    id: string;
    numero: string;
    titre: string | null;
    description: string | null;
    cause: string | null;
    solution: string | null;
    instructions: string | null;
    categorie_label: string;
    type_label: string | null;
    priorite: string;
    priorite_label: string;
    statut: string;
    statut_label: string;
    cout_estime: number | null;
    cout_reel: number | null;
    temps_passe: number | null;
    date_echeance: string | null;
    residence: string | null;
    batiment: string | null;
    chambre: string | null;
    equipement: string | null;
    resident: string | null;
    resident_tel: string | null;
    auteur: string;
    created_at: string | null;
    updated_at: string | null;
    date_resolution: string | null;
    technicien: { name: string; specialite: string; tel: string | null; email: string | null } | null;
    date_debut_intervention: string | null;
    date_fin_intervention: string | null;
    satisfaction: number | null;
    commentaire_satisfaction: string | null;
    interventions: { id: string; date: string | null; description: string | null; resultat: string | null; duree: number | null; remarques: string | null; satisfaction: number | null }[];
};

type Props = {
    ordre: Ordre;
    maintenanciers: Maintenancier[];
    transitions: string[];
    urls: { index: string | null; edit: string | null; assigner: string | null; statut: string | null; annuler: string | null; intervention: string | null; satisfaction: string | null };
};

/** Libellé et icône du bouton de chaque changement de statut proposé (« terminé » ouvre la saisie de la solution). */
const TRANSITIONS: Record<string, { label: string; icone: ReactNode; confirmation: string }> = {
    en_cours: { label: 'Reprendre / démarrer', icone: <Play {...ICON} aria-hidden />, confirmation: 'L’ordre repasse « en cours ».' },
    en_attente_pieces: { label: 'En attente de pièces', icone: <Pause {...ICON} aria-hidden />, confirmation: 'L’intervention est suspendue dans l’attente de pièces.' },
    reporte: { label: 'Reporter', icone: <Clock {...ICON} aria-hidden />, confirmation: 'L’intervention est reportée ; elle pourra être reprise plus tard.' },
    en_attente: { label: 'Remettre en attente', icone: <RotateCcw {...ICON} aria-hidden />, confirmation: 'L’ordre repasse « en attente ».' },
    termine: { label: 'Marquer comme terminé', icone: <Check {...ICON} aria-hidden />, confirmation: '' },
};

/** Fiche d'un ordre de travail (remplace `vie_residentielle/maintenance/show.blade.php`). */
export default function MaintenanceShow({ ordre, maintenanciers, transitions, urls }: Props) {
    const [fenetre, setFenetre] = useState<'assigner' | 'intervention' | 'cloture' | 'avis' | 'annuler' | null>(null);
    const [transition, setTransition] = useState<string | null>(null);
    const [traitement, setTraitement] = useState(false);
    const fermer = () => setFenetre(null);

    const changerStatut = (statut: string) => {
        if (!urls.statut) return;
        router.post(urls.statut, { statut }, { preserveScroll: true, onStart: () => setTraitement(true), onFinish: () => {
            setTraitement(false);
            setTransition(null);
        } });
    };

    const annuler = () => {
        if (!urls.annuler) return;
        router.post(urls.annuler, {}, { preserveScroll: true, onStart: () => setTraitement(true), onFinish: () => {
            setTraitement(false);
            fermer();
        } });
    };

    const evenements: EvenementChronologie[] = [
        { id: 'creation', titre: 'Ordre créé', date: ordre.created_at, texte: `Par ${ordre.auteur}`, ton: 'muted' },
        ...(ordre.technicien
            ? [{ id: 'assignation', titre: 'Maintenancier assigné', date: ordre.date_debut_intervention, texte: `${ordre.technicien.name} · ${libelleSpecialite(ordre.technicien.specialite)}`, ton: 'accent' as const }]
            : []),
        ...ordre.interventions.map((intervention) => ({
            id: intervention.id,
            titre: (
                <>
                    Intervention · {RESULTATS.find((r) => r.value === intervention.resultat)?.label ?? intervention.resultat}
                    {intervention.duree ? <span className="font-normal text-muted"> ({nombre(intervention.duree)} min)</span> : null}
                </>
            ),
            date: intervention.date,
            texte: [intervention.description, intervention.remarques].filter(Boolean).join('\n'),
            ton: intervention.resultat === 'resolu' ? ('primary' as const) : ('accent' as const),
        })),
        ...(ordre.statut === 'termine' && ordre.date_resolution
            ? [{ id: 'resolution', titre: 'Ordre terminé', date: ordre.date_resolution, texte: ordre.satisfaction ? `Satisfaction du résident : ${ordre.satisfaction}/5` : undefined, ton: 'primary' as const }]
            : []),
        ...(ordre.statut === 'annule' ? [{ id: 'annulation', titre: 'Ordre annulé', date: ordre.updated_at, ton: 'danger' as const }] : []),
    ];

    const actionsDisponibles = Boolean(urls.assigner || urls.intervention || urls.satisfaction || urls.annuler || transitions.length);

    return (
        <AdminLayout title={`OT ${ordre.numero}`}>
            <PageHeader
                title={`Ordre de travail ${ordre.numero}`}
                description={
                    <span className="flex flex-wrap items-center gap-2">
                        <StatutOtBadge statut={ordre.statut} label={ordre.statut_label} />
                        <PrioriteBadge priorite={ordre.priorite} label={`Priorité ${ordre.priorite_label.toLowerCase()}`} />
                        <span>
                            Créé le {dateHeure(ordre.created_at)} par {ordre.auteur}
                        </span>
                    </span>
                }
                actions={
                    <>
                        {urls.index && (
                            <a href={urls.index} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                                <ArrowLeft {...ICON} aria-hidden /> Retour à la liste
                            </a>
                        )}
                        {urls.edit && (
                            <a href={urls.edit} className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }), 'h-10 px-4')}>
                                <Pencil {...ICON} aria-hidden /> Modifier
                            </a>
                        )}
                    </>
                }
            />

            <div className="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div className="space-y-5">
                    <Panneau titre={ordre.titre || 'Description du problème'} description={`${ordre.categorie_label}${ordre.type_label ? ` · intervention ${ordre.type_label.toLowerCase()}` : ''}`}>
                        <p className="text-sm whitespace-pre-line text-gray-800">{ordre.description || 'Aucune description.'}</p>
                        {ordre.cause && (
                            <div className="mt-4 rounded-brand border-l-3 border-accent bg-[#FFF8E6] px-4 py-2.5">
                                <p className="text-xs font-medium text-[#6B3F00]">Cause identifiée</p>
                                <p className="text-sm text-gray-800">{ordre.cause}</p>
                            </div>
                        )}
                        {ordre.solution && (
                            <div className="mt-3 rounded-brand border-l-3 border-primary bg-soft px-4 py-2.5">
                                <p className="text-xs font-medium text-primary">Solution apportée</p>
                                <p className="text-sm whitespace-pre-line text-gray-800">{ordre.solution}</p>
                            </div>
                        )}
                    </Panneau>

                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <Panneau titre="Emplacement">
                            <Lignes
                                lignes={[
                                    ['Résidence', ordre.residence ?? 'Non définie'],
                                    ['Bâtiment', ordre.batiment ?? 'Tout le site'],
                                    ['Chambre', ordre.chambre ?? 'Parties communes'],
                                    ...(ordre.equipement ? ([['Équipement', ordre.equipement]] as [string, string][]) : []),
                                    ...(ordre.resident ? ([['Demandeur', `${ordre.resident}${ordre.resident_tel ? ` · ${ordre.resident_tel}` : ''}`]] as [string, string][]) : []),
                                ]}
                            />
                        </Panneau>
                        <Panneau titre="Suivi technique et coûts">
                            <Lignes
                                lignes={[
                                    ['Statut', STATUTS_OT[ordre.statut]?.label ?? ordre.statut_label],
                                    ['Échéance', ordre.date_echeance ? dateFr(ordre.date_echeance) : ''],
                                    ['Coût estimé', ordre.cout_estime !== null ? fcfa(ordre.cout_estime) : ''],
                                    ['Coût réel', ordre.cout_reel !== null ? fcfa(ordre.cout_reel) : ''],
                                    ['Temps passé', ordre.temps_passe !== null ? `${nombre(ordre.temps_passe)} h` : ''],
                                    ['Résolu le', ordre.date_resolution ? dateHeure(ordre.date_resolution) : ''],
                                ]}
                            />
                        </Panneau>
                    </div>

                    <Panneau titre="Chronologie" actions={<Badge variant="neutral">{nombre(ordre.interventions.length)} intervention(s)</Badge>}>
                        <Chronologie evenements={evenements} />
                    </Panneau>
                </div>

                <aside className="space-y-5">
                    {actionsDisponibles && (
                        <Panneau titre="Actions" bodyClassName="flex flex-col gap-2">
                            {urls.assigner && (
                                <Button size="sm" className="h-10 w-full justify-start" onClick={() => setFenetre('assigner')}>
                                    <UserCheck {...ICON} aria-hidden /> Assigner un maintenancier
                                </Button>
                            )}
                            {urls.intervention && (
                                <Button variant="secondary" size="sm" className="h-10 w-full justify-start" onClick={() => setFenetre('intervention')}>
                                    <ClipboardPlus {...ICON} aria-hidden /> Ajouter une intervention
                                </Button>
                            )}
                            {transitions.map((statut) => (
                                <Button
                                    key={statut}
                                    variant={statut === 'termine' ? 'primary' : 'outline'}
                                    size="sm"
                                    className="h-10 w-full justify-start"
                                    onClick={() => (statut === 'termine' ? setFenetre('cloture') : setTransition(statut))}
                                >
                                    {TRANSITIONS[statut]?.icone} {TRANSITIONS[statut]?.label ?? statut}
                                </Button>
                            ))}
                            {urls.satisfaction && (
                                <Button variant="secondary" size="sm" className="h-10 w-full justify-start" onClick={() => setFenetre('avis')}>
                                    <Star {...ICON} aria-hidden /> Enregistrer l’avis du résident
                                </Button>
                            )}
                            {urls.annuler && (
                                <Button variant="ghost" size="sm" className="h-10 w-full justify-start text-red-700 hover:bg-red-50 hover:text-red-700" onClick={() => setFenetre('annuler')}>
                                    <Ban {...ICON} aria-hidden /> Annuler l’ordre de travail
                                </Button>
                            )}
                        </Panneau>
                    )}

                    <Panneau titre="Maintenancier">
                        {ordre.technicien ? (
                            <div className="space-y-3">
                                <div className="flex items-center gap-3">
                                    <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-soft text-sm font-medium text-primary" aria-hidden>
                                        {initiales(ordre.technicien.name)}
                                    </span>
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-gray-900">{ordre.technicien.name}</p>
                                        <p className="text-xs text-muted">{libelleSpecialite(ordre.technicien.specialite)}</p>
                                    </div>
                                </div>
                                <ul className="space-y-1.5 text-sm text-gray-700">
                                    {ordre.technicien.tel && (
                                        <li className="flex items-center gap-2">
                                            <Phone className="size-4 text-muted" aria-hidden />
                                            <a href={`tel:${ordre.technicien.tel}`} className="hover:text-primary">
                                                {ordre.technicien.tel}
                                            </a>
                                        </li>
                                    )}
                                    {ordre.technicien.email && (
                                        <li className="flex items-center gap-2">
                                            <Mail className="size-4 text-muted" aria-hidden />
                                            <a href={`mailto:${ordre.technicien.email}`} className="truncate hover:text-primary">
                                                {ordre.technicien.email}
                                            </a>
                                        </li>
                                    )}
                                </ul>
                                {(ordre.date_debut_intervention || ordre.date_fin_intervention) && (
                                    <Lignes
                                        lignes={[
                                            ['Début', dateHeure(ordre.date_debut_intervention)],
                                            ['Fin', ordre.date_fin_intervention ? dateHeure(ordre.date_fin_intervention) : ''],
                                        ]}
                                    />
                                )}
                                {ordre.instructions && <p className="rounded-brand bg-surface px-3 py-2 text-sm whitespace-pre-line text-gray-700">{ordre.instructions}</p>}
                            </div>
                        ) : (
                            <p className="flex items-center gap-2 text-sm text-muted">
                                <Wrench {...ICON} aria-hidden /> Aucun maintenancier assigné.
                            </p>
                        )}
                    </Panneau>

                    {(ordre.satisfaction || ordre.commentaire_satisfaction) && (
                        <Panneau titre="Avis du résident">
                            {ordre.satisfaction && (
                                <div className="flex gap-0.5" aria-label={`${ordre.satisfaction} sur 5`}>
                                    {[1, 2, 3, 4, 5].map((valeur) => (
                                        <Star key={valeur} className={cn('size-5', valeur <= (ordre.satisfaction ?? 0) ? 'fill-accent text-accent' : 'text-gray-300')} strokeWidth={1.25} aria-hidden />
                                    ))}
                                </div>
                            )}
                            {ordre.commentaire_satisfaction && <p className="mt-2 text-sm whitespace-pre-line text-gray-700">{ordre.commentaire_satisfaction}</p>}
                        </Panneau>
                    )}
                </aside>
            </div>

            {urls.assigner && <AssignerDialog open={fenetre === 'assigner'} onClose={fermer} url={urls.assigner} maintenanciers={maintenanciers} />}
            {urls.intervention && <InterventionDialog open={fenetre === 'intervention'} onClose={fermer} url={urls.intervention} ordreId={ordre.id} />}
            {urls.statut && <ClotureDialog open={fenetre === 'cloture'} onClose={fermer} url={urls.statut} />}
            {urls.satisfaction && <SatisfactionDialog open={fenetre === 'avis'} onClose={fermer} url={urls.satisfaction} ordreId={ordre.id} />}

            <ConfirmDialog
                open={transition !== null}
                onOpenChange={(ouvert) => !ouvert && setTransition(null)}
                title={`${transition ? TRANSITIONS[transition]?.label : ''} ?`}
                description={transition ? TRANSITIONS[transition]?.confirmation : undefined}
                confirmLabel="Confirmer"
                confirmIcon={<Check className="size-4" aria-hidden />}
                processing={traitement}
                onConfirm={() => transition && changerStatut(transition)}
            />
            <ConfirmDialog
                open={fenetre === 'annuler'}
                onOpenChange={(ouvert) => !ouvert && fermer()}
                title="Annuler cet ordre de travail ?"
                description={`L’ordre ${ordre.numero} sera marqué comme annulé. Cette action est définitive.`}
                confirmLabel="Annuler l’ordre"
                confirmIcon={<Ban className="size-4" aria-hidden />}
                danger
                processing={traitement}
                onConfirm={annuler}
            />
        </AdminLayout>
    );
}
