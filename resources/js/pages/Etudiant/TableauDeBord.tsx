import {
    BedDouble,
    Building2,
    ChartColumn,
    ChevronRight,
    CircleCheck,
    Clock,
    CreditCard,
    DoorOpen,
    FilePenLine,
    FileText,
    GraduationCap,
    Info,
    Phone,
    UserRound,
    Wifi,
    Wrench,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { IconeLigne, Ligne, Liste, Notice, Panneau, Vide } from '@/components/etudiant/panneau';
import { ReponseProposition, type Proposition } from '@/components/etudiant/reponse-proposition';
import { ICON } from '@/components/layout/app-sidebar';
import { EtudiantLayout } from '@/components/layout/etudiant-layout';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { StatCard, type KpiTone } from '@/components/ui/stat-card';
import { cn } from '@/lib/utils';

type Activite = { date: string; icone: 'credit-card' | 'wrench' | 'file-pen-line' | 'bed-double'; fait: boolean; texte: string; statut: string };

type Resident = {
    chambre: string | null;
    batiment: string | null;
    loyer: string;
    resteAPayer: string;
    nbImpayes: number;
    prochainPaiement: string | null;
    activites: Activite[];
    infos: { residence: string | null; chambre: string | null; responsable: string | null; contact: string | null; wifi: string | null };
    urls: { paiements: string; maintenance: string; contrat: string; profil: string };
};

type Demande = {
    existe: boolean;
    numero: string | null;
    soumiseLe: string | null;
    echeance: string | null;
    echeanceCourte: string | null;
    echeanceIso: string | null;
    moyenne: string | number | null;
    residenceSouhaitee: string | null;
    progression: number;
    documentsValides: number;
    documents: { id: string; nom: string; statut: 'valide' | 'rejete' | 'en_attente' }[];
    decision: Proposition | null;
    acceptee: boolean;
};

type TableauDeBordProps = {
    anneeAcademique: string;
    aujourdHui: string;
    statut: { code: string | null; libelle: string; ton: KpiTone };
    resident: Resident | null;
    demande: Demande | null;
};

const ICONES_ACTIVITE: Record<Activite['icone'], LucideIcon> = {
    'credit-card': CreditCard,
    wrench: Wrench,
    'file-pen-line': FilePenLine,
    'bed-double': BedDouble,
};

/** Tableau de bord de l'espace étudiant : vue résident (chambre confirmée) ou suivi de la demande d'hébergement. */
export default function TableauDeBord(props: TableauDeBordProps) {
    return (
        <EtudiantLayout title="Tableau de bord">
            <div className="flex flex-col gap-5">
                {props.resident ? <VueResident {...props} resident={props.resident} /> : props.demande && <VueDemande {...props} demande={props.demande} />}
            </div>
        </EtudiantLayout>
    );
}

function EnTete({ titre, sousTitre, pastille, annee, date }: { titre: string; sousTitre?: string; pastille: { libelle: string; icone: LucideIcon; ton: 'vert' | 'orange' }; annee: string; date: string }) {
    const Icone = pastille.icone;

    return (
        <header className="flex flex-col gap-3">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <h1 className="page-title text-xl font-medium text-gray-900">{titre}</h1>
                    {sousTitre && <p className="mt-2 text-sm text-muted">{sousTitre}</p>}
                </div>
                <span
                    className={cn(
                        'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium',
                        pastille.ton === 'vert' ? 'bg-soft text-primary' : 'bg-accent/15 text-amber-800',
                    )}
                >
                    <Icone {...ICON} size={16} />
                    {pastille.libelle}
                </span>
            </div>
            <p className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted">
                <span aria-hidden className="size-2 rounded-full bg-secondary" />
                Année académique {annee}
                <span aria-hidden className="text-gray-300">|</span>
                {date}
            </p>
        </header>
    );
}

function VueResident({ resident, statut, anneeAcademique, aujourdHui }: TableauDeBordProps & { resident: Resident }) {
    const actions: { href: string; icone: LucideIcon; titre: string; detail: string; urgent?: boolean; nouvelOnglet?: boolean }[] = [
        {
            href: resident.urls.paiements,
            icone: CreditCard,
            titre: 'Payer mon loyer',
            detail: resident.prochainPaiement ? `Prochaine échéance : ${resident.prochainPaiement}` : 'Paiement en ligne',
            urgent: resident.nbImpayes > 0,
        },
        { href: resident.urls.maintenance, icone: Wrench, titre: 'Maintenance', detail: 'Signaler un problème technique' },
        { href: resident.urls.contrat, icone: FileText, titre: 'Mon contrat', detail: 'Consulter et télécharger', nouvelOnglet: true },
        { href: resident.urls.profil, icone: UserRound, titre: 'Mon profil', detail: 'Coordonnées et mot de passe' },
    ];
    const nbActivites = resident.activites.length;

    return (
        <>
            <EnTete titre="Mon tableau de bord" pastille={{ libelle: 'Résident actif', icone: CircleCheck, ton: 'vert' }} annee={anneeAcademique} date={aujourdHui} />

            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard label="Chambre attribuée" value={resident.chambre ?? '—'} tone="blue" extra={resident.batiment ?? undefined} />
                <StatCard label="Loyer mensuel" value={resident.loyer} tone="green" />
                <StatCard
                    label="Reste à payer"
                    value={resident.resteAPayer}
                    tone="amber"
                    extra={resident.nbImpayes > 0 ? `${resident.nbImpayes} ${resident.nbImpayes > 1 ? 'impayés' : 'impayé'}` : 'À jour'}
                    highlight={resident.nbImpayes > 0}
                />
                <StatCard label="État de la demande" value={statut.libelle} tone={statut.ton} />
            </div>

            <div className="grid gap-5 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                <Panneau surtitre="Activité" titre="Activité récente" badge={`${nbActivites} ${nbActivites > 1 ? 'événements' : 'événement'}`}>
                    {nbActivites === 0 ? (
                        <Vide icone={Clock}>Aucune activité récente.</Vide>
                    ) : (
                        <Liste>
                            {resident.activites.map((activite, index) => (
                                <Ligne
                                    key={`${activite.texte}-${index}`}
                                    icone={ICONES_ACTIVITE[activite.icone]}
                                    ton={activite.fait ? 'vert' : undefined}
                                    titre={activite.texte}
                                    detail={activite.date}
                                    complement={<Badge variant={activite.fait ? 'success' : 'warning'}>{activite.statut}</Badge>}
                                />
                            ))}
                        </Liste>
                    )}
                </Panneau>

                <Panneau surtitre="Résidence" titre="Informations pratiques">
                    <Liste>
                        <Ligne icone={Building2} libelle="Résidence" titre={resident.infos.residence ?? 'Non attribuée'} />
                        <Ligne icone={DoorOpen} libelle="Chambre actuelle" titre={resident.infos.chambre ?? 'Non attribuée'} />
                        <Ligne icone={UserRound} libelle="Responsable de la résidence" titre={resident.infos.responsable ?? 'Non renseigné'} />
                        <Ligne
                            icone={Phone}
                            libelle="Contact d’urgence"
                            titre={resident.infos.contact ?? 'Non renseigné'}
                            href={resident.infos.contact ? `tel:${resident.infos.contact.replace(/[^0-9+]/g, '')}` : undefined}
                        />
                        <Ligne icone={Wifi} libelle="Wi-Fi de la résidence" titre={resident.infos.wifi ?? 'Non renseigné'} />
                    </Liste>
                </Panneau>
            </div>

            <Panneau surtitre="Raccourcis" titre="Actions rapides">
                <ul className="-mx-2 grid gap-1 sm:grid-cols-2 xl:grid-cols-4">
                    {actions.map((action) => (
                        <li key={action.titre}>
                            <a
                                href={action.href}
                                {...(action.nouvelOnglet ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
                                className="group flex min-h-14 items-start gap-3 rounded-brand px-2 py-2.5 hover:bg-surface focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                            >
                                <IconeLigne icone={action.icone} ton={action.urgent ? 'orange' : undefined} />
                                <span className="min-w-0 flex-1">
                                    <strong className="block text-sm font-medium text-gray-900 group-hover:text-primary">{action.titre}</strong>
                                    <small className="block text-xs text-muted">{action.detail}</small>
                                </span>
                                <ChevronRight {...ICON} className="mt-2.5 shrink-0 text-muted group-hover:text-primary" />
                            </a>
                        </li>
                    ))}
                </ul>
            </Panneau>
        </>
    );
}

const ETAPES = [
    { libelle: 'Soumission', seuil: 0 },
    { libelle: 'Vérification', seuil: 25 },
    { libelle: 'Étude du dossier', seuil: 50 },
    { libelle: 'Attribution', seuil: 75 },
    { libelle: 'Validation', seuil: 100 },
];

/** Jours restants avant l'échéance (fin de journée), recalculés chaque minute. */
function useJoursRestants(echeanceIso: string | null): number | null {
    const [maintenant, setMaintenant] = useState(() => Date.now());

    useEffect(() => {
        const minuteur = window.setInterval(() => setMaintenant(Date.now()), 60_000);

        return () => window.clearInterval(minuteur);
    }, []);

    if (!echeanceIso) return null;
    const reste = new Date(echeanceIso).getTime() - maintenant;

    return reste <= 0 ? 0 : Math.ceil(reste / 86_400_000);
}

function VueDemande({ demande, statut, anneeAcademique, aujourdHui }: TableauDeBordProps & { demande: Demande }) {
    const jours = useJoursRestants(demande.echeanceIso);
    const nbDocuments = demande.documents.length;
    const enAttente = statut.code === 'En_attente';
    const sousTitre = demande.acceptee
        ? 'Votre demande est acceptée : l’attribution de votre chambre est en cours.'
        : demande.existe
          ? 'Votre demande est en cours de traitement par le CROU.'
          : 'Vous n’avez pas encore de demande d’hébergement pour cette année.';

    return (
        <>
            <EnTete
                titre="Ma demande d’hébergement"
                sousTitre={sousTitre}
                pastille={{ libelle: statut.libelle, icone: enAttente ? Clock : Info, ton: enAttente ? 'orange' : 'vert' }}
                annee={anneeAcademique}
                date={aujourdHui}
            />

            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard label="Numéro de demande" value={demande.numero ?? '—'} tone="blue" />
                <StatCard label="Soumise le" value={demande.soumiseLe ?? '—'} tone="green" />
                <StatCard
                    label="Délai de validation"
                    value={demande.echeance ?? '—'}
                    tone="amber"
                    extra={jours === null ? undefined : jours === 0 ? 'Délai expiré' : `${jours} ${jours > 1 ? 'jours restants' : 'jour restant'}`}
                    highlight={jours !== null && jours <= 7}
                />
                <StatCard label="Documents validés" value={`${demande.progression} %`} tone="plum" extra={`${demande.documentsValides}/${nbDocuments}`} />
            </div>

            <div className="grid gap-5 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                <Panneau surtitre="Admission" titre="Détail de la demande">
                    <Liste>
                        <Ligne icone={GraduationCap} libelle="Année académique" titre={anneeAcademique || 'Non renseignée'} />
                        <Ligne icone={FilePenLine} libelle="Type de demande" titre="Première demande" />
                        {demande.moyenne !== null && demande.moyenne !== '' && <Ligne icone={ChartColumn} libelle="Moyenne" titre={`${demande.moyenne}/20`} />}
                        <Ligne icone={Building2} libelle="Résidence souhaitée" titre={demande.residenceSouhaitee ?? 'Non spécifiée'} />
                    </Liste>

                    <div className="mt-4 flex flex-col gap-3 border-t pt-4">
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-muted">Progression du traitement</span>
                            <strong className="font-medium text-gray-900 tabular-nums">{demande.progression} %</strong>
                        </div>
                        <Progress value={demande.progression} tone="primary" label="Progression du traitement" className="h-2" />
                        <ol className="grid grid-cols-5 gap-1 text-center text-[11px] leading-tight text-muted sm:text-xs">
                            {ETAPES.map((etape, index) => {
                                const faite = demande.progression >= etape.seuil;

                                return (
                                    <li key={etape.libelle} className={cn('flex flex-col items-center gap-1', faite && 'text-primary')}>
                                        <span
                                            className={cn(
                                                'grid size-6 place-items-center rounded-full border text-xs font-medium',
                                                faite ? 'border-primary bg-primary text-white' : 'bg-white',
                                            )}
                                        >
                                            {index + 1}
                                        </span>
                                        {etape.libelle}
                                    </li>
                                );
                            })}
                        </ol>
                    </div>

                    {demande.decision && (
                        <div className="mt-4 flex flex-col gap-3 border-t pt-4">
                            <p className="text-sm text-gray-900">
                                Une chambre vous est proposée{demande.decision.chambre ? ` : ${demande.decision.chambre}` : ''}.
                            </p>
                            <ReponseProposition proposition={demande.decision} />
                        </div>
                    )}

                    <Notice icone={Info} className="mt-4">
                        <strong className="font-medium">Important :</strong> vous devez accepter ou refuser cette demande avant le{' '}
                        <strong className="font-medium">{demande.echeanceCourte ?? 'une date à préciser'}</strong>. Passé ce délai, votre demande sera traitée
                        automatiquement.
                    </Notice>
                </Panneau>

                <Panneau surtitre="Pièces" titre="Documents fournis" badge={`${demande.documentsValides}/${nbDocuments} validés`}>
                    {nbDocuments === 0 ? (
                        <Vide icone={FileText}>Aucun document fourni pour le moment.</Vide>
                    ) : (
                        <Liste>
                            {demande.documents.map((document) => (
                                <Ligne
                                    key={document.id}
                                    icone={FileText}
                                    ton={document.statut === 'valide' ? 'vert' : undefined}
                                    titre={document.nom}
                                    complement={
                                        <Badge variant={document.statut === 'valide' ? 'success' : document.statut === 'rejete' ? 'danger' : 'warning'}>
                                            {document.statut === 'valide' ? 'Validé' : document.statut === 'rejete' ? 'Rejeté' : 'En attente'}
                                        </Badge>
                                    }
                                />
                            ))}
                        </Liste>
                    )}
                </Panneau>
            </div>
        </>
    );
}
