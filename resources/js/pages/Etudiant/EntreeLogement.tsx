import { Bell, CalendarCheck, CalendarClock, CalendarX, ChevronRight, Clock, FilePenLine, FileSignature, Info, KeyRound, MapPin, MessageSquare, type LucideIcon } from 'lucide-react';
import { ICON } from '@/components/layout/app-sidebar';
import { EtudiantLayout } from '@/components/layout/etudiant-layout';
import { IconeLigne, Liste, Ligne, Notice, PaginationSimple, Panneau, Vide, type PaginationSimpleProps } from '@/components/etudiant/panneau';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { StatCard } from '@/components/ui/stat-card';
import { cn } from '@/lib/utils';

type RendezVous = {
    id: number;
    date: string | null;
    debut: string | null;
    fin: string | null;
    lieu: string;
    statut: 'programme' | 'passe' | 'annule';
};

type Notification = {
    id: number;
    type: 'proposal' | 'contract' | 'appointment' | 'autre';
    objet: string;
    message: string;
    date: string | null;
    annule: boolean;
};

type EntreeLogementProps = {
    prochainRendezVous: RendezVous | null;
    rendezVous: RendezVous[];
    contrat: { approuve: boolean; date: string | null } | null;
    notifications: Notification[];
    pagination: PaginationSimpleProps;
    liens: { contrat: string; demandes: string };
};

const STATUT_RDV: Record<RendezVous['statut'], { libelle: string; variant: 'success' | 'neutral' | 'danger' }> = {
    programme: { libelle: 'Programmé', variant: 'success' },
    passe: { libelle: 'Passé', variant: 'neutral' },
    annule: { libelle: 'Annulé', variant: 'danger' },
};

const ICONE_NOTIFICATION: Record<Notification['type'], LucideIcon> = {
    proposal: KeyRound,
    contract: FileSignature,
    appointment: CalendarCheck,
    autre: MessageSquare,
};

/** Page « Mon entrée et notifications » : rendez-vous d'entrée, approbation du contrat et messages du parcours d'entrée. */
export default function EntreeLogement({ prochainRendezVous, rendezVous, contrat, notifications, pagination, liens }: EntreeLogementProps) {
    const historique = rendezVous.filter((item) => item.id !== prochainRendezVous?.id);

    return (
        <EtudiantLayout title="Mon entrée et notifications">
            <header className="mb-5">
                <h1 className="page-title text-xl font-medium text-gray-900">Mon entrée dans les lieux</h1>
                <p className="mt-1 text-sm text-muted">Suivez votre rendez-vous d’entrée, l’approbation de votre contrat et les messages du service Logement.</p>
            </header>

            <div className="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-3">
                <StatCard
                    label="Rendez-vous d’entrée"
                    value={prochainRendezVous ? 'Programmé' : 'En attente'}
                    tone={prochainRendezVous ? 'green' : 'amber'}
                    extra={prochainRendezVous?.debut ?? undefined}
                />
                <StatCard
                    label="Contrat"
                    value={contrat ? (contrat.approuve ? 'Approuvé' : 'À approuver') : 'Aucun'}
                    tone={contrat?.approuve ? 'green' : contrat ? 'amber' : 'neutral'}
                    extra={contrat && !contrat.approuve ? 'Action requise' : undefined}
                    highlight={!!contrat && !contrat.approuve}
                />
                <StatCard label="Notifications" value={pagination.total} tone="blue" className="col-span-2 lg:col-span-1" />
            </div>

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5">
                <Panneau id="rendez-vous" surtitre="Accueil" titre="Mon rendez-vous d’entrée">
                    {prochainRendezVous ? (
                        <div className="rounded-brand border border-secondary/40 bg-soft p-4">
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <p className="text-xs text-primary">Prochain rendez-vous</p>
                                    <p className="text-base font-medium text-gray-900 first-letter:uppercase">{prochainRendezVous.date}</p>
                                </div>
                                <Badge variant="success" className="bg-white">
                                    Programmé
                                </Badge>
                            </div>
                            <dl className="mt-3 grid gap-2 text-sm text-gray-900 sm:grid-cols-2">
                                <div className="flex items-center gap-2">
                                    <Clock {...ICON} className="shrink-0 text-primary" />
                                    <dt className="sr-only">Horaire</dt>
                                    <dd className="tabular-nums">
                                        {prochainRendezVous.debut} – {prochainRendezVous.fin}
                                    </dd>
                                </div>
                                <div className="flex min-w-0 items-center gap-2">
                                    <MapPin {...ICON} className="shrink-0 text-primary" />
                                    <dt className="sr-only">Lieu</dt>
                                    <dd className="truncate">{prochainRendezVous.lieu}</dd>
                                </div>
                            </dl>
                            <p className="mt-3 text-xs text-muted">Présentez-vous à l’heure avec votre pièce d’identité pour l’état des lieux d’entrée et la remise des clés.</p>
                        </div>
                    ) : (
                        <Notice icone={Info}>
                            Le rendez-vous est programmé après l’attribution définitive et votre approbation du contrat, selon les créneaux d’accueil disponibles.
                        </Notice>
                    )}

                    {historique.length > 0 && (
                        <>
                            <h3 className="mt-4 mb-1 text-sm font-medium text-gray-900">Historique</h3>
                            <Liste>
                                {historique.map((item) => (
                                    <Ligne
                                        key={item.id}
                                        icone={item.statut === 'annule' ? CalendarX : CalendarClock}
                                        titre={<span className="first-letter:uppercase">{`${item.date ?? '—'} · ${item.debut ?? ''}`}</span>}
                                        detail={item.lieu}
                                        complement={<Badge variant={STATUT_RDV[item.statut].variant}>{STATUT_RDV[item.statut].libelle}</Badge>}
                                    />
                                ))}
                            </Liste>
                        </>
                    )}
                </Panneau>

                <Panneau id="contrat" surtitre="Logement" titre="Mon contrat">
                    <Liste>
                        <Ligne
                            href={liens.contrat}
                            icone={FilePenLine}
                            ton={contrat?.approuve ? 'vert' : contrat ? 'orange' : undefined}
                            titre={contrat?.approuve ? 'Contrat approuvé' : 'Consulter et approuver mon contrat'}
                            detail={contrat?.approuve ? `Approuvé le ${contrat.date}` : 'L’approbation est nécessaire pour programmer votre rendez-vous'}
                            fin={ChevronRight}
                        />
                    </Liste>
                </Panneau>

                <Panneau id="notifications" surtitre="Messages" titre="Mes notifications" badge={pagination.total}>
                    {notifications.length ? (
                        <ul className="-mx-2 divide-y">
                            {notifications.map((item) => (
                                <li key={item.id} className="flex gap-3 px-2 py-3">
                                    <IconeLigne icone={ICONE_NOTIFICATION[item.type]} ton={item.annule ? undefined : 'vert'} />
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <h3 className={cn('text-sm font-medium text-gray-900', item.annule && 'text-muted line-through')}>{item.objet}</h3>
                                            {item.annule && <Badge variant="danger">Annulé</Badge>}
                                        </div>
                                        <p className="mt-1 text-sm break-words whitespace-pre-line text-muted">{item.message}</p>
                                        <small className="mt-1 block text-xs text-muted tabular-nums">{item.date}</small>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <Vide icone={Bell}>Aucune notification pour le moment.</Vide>
                    )}
                    <PaginationSimple {...pagination} />
                    <a href={liens.demandes} className={cn(buttonVariants({ variant: 'secondary' }), 'mt-4 w-full font-medium sm:w-fit')}>
                        Suivre ma demande et répondre à la proposition
                        <ChevronRight {...ICON} />
                    </a>
                </Panneau>
            </div>
        </EtudiantLayout>
    );
}
