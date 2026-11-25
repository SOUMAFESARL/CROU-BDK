import { Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    CircleCheck,
    CircleX,
    Clock,
    ExternalLink,
    FileText,
    FolderOpen,
    Gauge,
    History,
    Info,
    ListChecks,
    Loader2,
    MessageSquareWarning,
    PenLine,
    Printer,
    Scale,
    ShieldAlert,
    TriangleAlert,
    X,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { ETATS_SANS_BADGE, EtatDossierBadge } from '@/components/dossier/etat-dossier-badge';
import { FicheDemandeDialog } from '@/components/dossier/fiche-demande-dialog';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { StatutReclamationBadge, TraiterReclamationDialog, type Reclamation } from '@/components/reclamations/shared';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { dateFr, initiales } from '@/lib/text';
import { cn } from '@/lib/utils';

type Regle = { label: string; campagne: string; etudiant: string; etat: 'ok' | 'nok' | 'nd' | 'nv' | 'rejete' };
type Volet = { volet: string; poids: number; situation: string; points: number; apport: number };
type Bareme = { score: number | null; minimum: number; volets: Volet[]; erreur: string | null; score_decision: number | null };
type Document = { id: string; type: string; url: string; statut: string; valider: string | null; rejeter: string | null };
type Rejet = { id: string; motif: string | null; auteur: string | null; automatique: boolean; date: string | null };

/** Informations saisies par l'étudiant, par étape du formulaire de demande (`CampagnesAdmissionController::profilDemande`). */
type ProfilDemande = {
    identite: {
        civilite: string | null;
        date_naissance: string | null;
        lieu_naissance: string | null;
        nationalite: string | null;
        telephone1: string | null;
        telephone2: string | null;
        email1: string | null;
        email2: string | null;
        type_piece: string | null;
        numero_piece: string | null;
        date_expiration: string | null;
        groupe_sanguin: string | null;
    };
    etudes: {
        campagne: string | null;
        niveau_precedent: string | null;
        niveau: string | null;
        redoublant: boolean | null;
        /** Niveau précédent = Terminale : note au bac (/400) et ville du bac au lieu de la moyenne et des crédits. */
        bachelier: boolean;
        matricule: string | null;
        filiere: string | null;
        /** Moyenne enregistrée, sur 20 (note au bac ramenée sur 20 pour un nouveau bachelier). */
        moyenne: number | null;
        note_bac: number | null;
        credits: number | null;
        credits_max: number;
        ville_bac: string | null;
        num_table_bac: string | null;
    };
    situation: { boursier: boolean; observations: string | null };
    adresse: { region: string | null; ville: string | null; adresse: string | null; distance: number | null };
};

type Props = {
    etudiant: { id: string; name: string; matricule: string | null; statut_inscription: string | null; is_validation: boolean; is_rejet: boolean };
    demande: { id: string; numero: string | null; etat: string | null; date: string | null } | null;
    discipline: { label: string; bloquant: boolean } | null;
    /** Critères de rejet du volet de la demande, comparés aux valeurs saisies (`ConformiteDemande`). */
    eligibilite: {
        volet: 'admission' | 'readmission';
        volet_libelle: string;
        campagne: string | null;
        /** `en_vigueur` : critères appliqués de la campagne ; `modele` : modèle CROU (critères pas encore appliqués). */
        origine: 'en_vigueur' | 'modele';
        version: number | null;
        regles: Regle[];
        conformes: number;
        non_conformes: number;
        score: number;
        lien: string | null;
        /** Demande dérogée (bouton « Déroger ») : irrecevable ou refusée, elle est rendue recevable. */
        /** Demande conforme, mais classée au-delà de la liste élargie : irrecevable. */
        hors_liste: boolean;
        derogation: { auteur: string | null; date: string | null; motif: string | null } | null;
    } | null;
    /** Rejets de la demande courante (passages en « Refusé »), du plus récent au plus ancien. */
    rejets: Rejet[];
    /** Score du barème de scoring actif (seul score comparé au minimum de la décision automatique). */
    bareme: Bareme | null;
    documents: Document[] | null;
    /** Réclamations de l'étudiant, de la plus récente à la plus ancienne (vide sans le droit de les consulter). */
    reclamations: Reclamation[];
    profil: ProfilDemande | null;
    historique: { id: string; numero: string | null; campagne_id: string | null; annee: string | null; date: string | null; etat: string | null }[] | null;
    /**
     * `deroger` : bouton « Déroger » (demande des listes « Demandes irrecevables » ou « Demandes refusées », liste des
     * demandes validées de son volet pas encore approuvée en commission) ; null sinon ou sans le droit de déroger.
     * `fiche` : fiche de demande (PDF) identique à celle de l'étudiant ; null pour un brouillon ou sans les droits.
     */
    urls: { admissions: string | null; deroger: string | null; fiche: string | null; reclamations: string | null; resultats: string | null };
};

const ETATS_REGLE = {
    ok: { label: 'Conforme', variant: 'success', icone: CircleCheck },
    nok: { label: 'Non conforme', variant: 'danger', icone: CircleX },
    nd: { label: 'Non renseigné', variant: 'warning', icone: TriangleAlert },
    nv: { label: 'Non vérifiable', variant: 'warning', icone: Clock },
    rejete: { label: 'Rejeté', variant: 'danger', icone: CircleX },
} as const;

const STATUTS_DOCUMENT: Record<string, { label: string; variant: 'success' | 'danger' | 'warning'; icone: typeof Clock }> = {
    valide: { label: 'Validé', variant: 'success', icone: CircleCheck },
    rejete: { label: 'Rejeté', variant: 'danger', icone: CircleX },
};

const STATUTS_INSCRIPTION: Record<string, [string, 'success' | 'warning' | 'info' | 'danger']> = {
    residents: ['Résident', 'success'],
    en_attente: ['En attente', 'warning'],
    demande: ['Nouvelle demande', 'info'],
    annuler: ['Annulé', 'danger'],
};

/** Traitement d'un dossier de candidature (remplace `admission/traiter-demande.blade.php`, `derogations.blade.php` et le mode admin de `dossier-cycle`). */
export default function AdmissionShow(props: Props) {
    const { etudiant, demande, discipline, eligibilite, rejets, bareme, documents, reclamations, profil, historique, urls } = props;
    const retour = urls.reclamations ?? urls.resultats ?? urls.admissions;
    const libelleRetour = urls.reclamations ? 'Retour aux réclamations' : urls.resultats ? 'Retour aux résultats d’admission' : 'Retour aux admissions';
    const [aDeroger, setADeroger] = useState(false);
    const [ficheOuverte, setFicheOuverte] = useState(false);

    return (
        <AdminLayout title="Traiter la demande">
            <PageHeader
                title={`Demande #${demande?.numero ?? 'N/D'}`}
                description={
                    <div className="flex flex-col gap-2">
                        <span>
                            {etudiant.name}
                            {etudiant.matricule && ` · ${etudiant.matricule}`}
                            {demande?.date && ` · déposée le ${dateFr(demande.date)}`}
                        </span>
                        {!ETATS_SANS_BADGE.includes(demande?.etat ?? '') && (
                            <span>
                                <EtatDossierBadge etat={demande?.etat} />
                            </span>
                        )}
                    </div>
                }
                actions={
                    (retour || urls.fiche || urls.deroger) && (
                        <>
                            {retour && (
                                <Link
                                    href={retour}
                                    className="inline-flex h-10 items-center gap-2 rounded-brand border border-gray-300 bg-white px-4 text-sm font-medium text-muted hover:bg-surface hover:text-primary"
                                >
                                    <ArrowLeft {...ICON} aria-hidden /> {libelleRetour}
                                </Link>
                            )}
                            {urls.fiche && (
                                <Button variant="secondary" className="h-10 px-4 font-medium" onClick={() => setFicheOuverte(true)} title="Voir la fiche de demande avant de l’imprimer">
                                    <Printer {...ICON} aria-hidden /> Imprimer la fiche
                                </Button>
                            )}
                            {urls.deroger && (
                                <Button className="h-10 px-4" onClick={() => setADeroger(true)} title="Rendre la demande recevable par dérogation">
                                    <Scale {...ICON} aria-hidden /> Déroger
                                </Button>
                            )}
                        </>
                    )
                }
            />

            {discipline && (
                <p role="alert" className="mb-6 flex items-start gap-2 rounded-brand border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <ShieldAlert className="mt-0.5 size-4 shrink-0" aria-hidden />
                    <span>
                        Statut disciplinaire : <strong className="font-medium">{discipline.label}</strong>.{discipline.bloquant && ' Admission et attribution bloquées tant que la sanction est applicable.'}
                    </span>
                </p>
            )}

            <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex min-w-0 flex-col gap-6">
                    {bareme && <ScoreBareme {...bareme} />}
                    {eligibilite && <Eligibilite {...eligibilite} />}
                    {documents && <Documents documents={documents} />}
                    {reclamations.length > 0 && <MotifsReclamation reclamations={reclamations} />}
                    {eligibilite?.derogation && <MotifDerogation {...eligibilite.derogation} />}
                    {rejets.length > 0 && <MotifsRejet rejets={rejets} />}
                </div>

                <aside className="flex flex-col gap-6">
                    {profil && <Profil etudiant={etudiant} profil={profil} documents={documents} />}
                    {historique && <Historique demandes={historique} />}
                </aside>
            </div>

            {urls.fiche && (
                <FicheDemandeDialog
                    url={urls.fiche}
                    description={[demande?.numero && `N° ${demande.numero}`, etudiant.name].filter(Boolean).join(' · ')}
                    open={ficheOuverte}
                    onOpenChange={setFicheOuverte}
                />
            )}
            {urls.deroger && <DialogueDerogation url={urls.deroger} nom={etudiant.name} open={aDeroger} onOpenChange={setADeroger} />}
        </AdminLayout>
    );
}

function Carte({ id, icone, teinte, titre, meta, children }: { id?: string; icone: ReactNode; teinte: string; titre: string; meta?: ReactNode; children: ReactNode }) {
    return (
        <section id={id} className="scroll-mt-6 overflow-hidden rounded-brand border bg-white">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b px-4 py-3">
                <h2 className="flex items-center gap-2 text-base font-medium text-gray-900">
                    <span className={cn('flex size-8 items-center justify-center rounded-brand', teinte)} aria-hidden>
                        {icone}
                    </span>
                    {titre}
                </h2>
                {meta && <span className="text-sm text-muted">{meta}</span>}
            </div>
            <div className="p-4">{children}</div>
        </section>
    );
}

function ScoreBareme({ score, minimum, volets, erreur, score_decision }: Bareme) {
    const atteint = score !== null && score >= minimum;

    return (
        <Carte icone={<Gauge size={18} strokeWidth={1.25} />} teinte="bg-soft text-primary" titre="Score d’éligibilité (barème)" meta={`Minimum requis : ${scoreFr(minimum)}/100`}>
            {score === null ? (
                <p className="flex items-start gap-2 text-sm text-[#B54708]">
                    <TriangleAlert className="mt-0.5 size-4 shrink-0" aria-hidden /> Score non calculable : {erreur} Le dossier relève d’une décision manuelle.
                </p>
            ) : (
                <>
                    <div className="mb-4 flex flex-col gap-1">
                        <p className="flex flex-wrap items-center gap-3">
                            <span className={cn('text-2xl font-medium tabular-nums', atteint ? 'text-[#067647]' : 'text-[#B42318]')}>{scoreFr(score)}/100</span>
                            <Badge variant={atteint ? 'success' : 'danger'}>
                                {atteint ? <CircleCheck className="size-3.5" aria-hidden /> : <CircleX className="size-3.5" aria-hidden />}
                                {atteint ? 'Minimum atteint' : 'Inférieur au minimum'}
                            </Badge>
                        </p>
                        {score_decision !== null && (
                            <p className="flex items-start gap-2 text-sm text-muted">
                                <Info className="mt-0.5 size-4 shrink-0" aria-hidden /> Score retenu lors de la décision automatique : {scoreFr(score_decision)}/100 (les informations du dossier ont été modifiées depuis).
                            </p>
                        )}
                    </div>
                    <div className="overflow-hidden rounded-brand border">
                        <Table>
                            <TableHeader>
                                <TableRow className="hover:bg-transparent">
                                    <TableHead>Volet</TableHead>
                                    <TableHead className="text-right">Poids</TableHead>
                                    <TableHead>Situation de l’étudiant</TableHead>
                                    <TableHead className="text-right">Points</TableHead>
                                    <TableHead className="text-right">Apport</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {volets.map((volet) => (
                                    <TableRow key={volet.volet}>
                                        <TableCell className="font-medium text-gray-900">{volet.volet}</TableCell>
                                        <TableCell className="text-right text-muted tabular-nums">{scoreFr(volet.poids)} %</TableCell>
                                        <TableCell>{volet.situation}</TableCell>
                                        <TableCell className="text-right tabular-nums">{scoreFr(volet.points)}/100</TableCell>
                                        <TableCell className="text-right tabular-nums">{scoreFr(volet.apport)}</TableCell>
                                    </TableRow>
                                ))}
                                <TableRow className="bg-[#F8F8F8] hover:bg-[#F8F8F8]">
                                    <TableCell colSpan={4} className="font-medium text-gray-900">
                                        Total
                                    </TableCell>
                                    <TableCell className={cn('text-right font-medium tabular-nums', atteint ? 'text-[#067647]' : 'text-[#B42318]')}>{scoreFr(score)}/100</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </>
            )}
        </Carte>
    );
}

function Eligibilite({ volet_libelle, campagne, origine, regles, conformes, non_conformes, score, lien, hors_liste, derogation }: NonNullable<Props['eligibilite']>) {
    const couleur = non_conformes > 0 ? '#B42318' : score === 100 ? '#067647' : '#B54708';
    const circonference = 2 * Math.PI * 26;
    const aVerifier = regles.length - conformes - non_conformes;

    return (
        <Carte
            icone={<ListChecks size={18} strokeWidth={1.25} />}
            teinte="bg-soft text-primary"
            titre="Critères d’éligibilité"
            meta={
                <span className="flex flex-wrap items-center gap-2">
                    <Badge variant={volet_libelle === 'Réadmission' ? 'info' : 'neutral'}>{volet_libelle}</Badge>
                    Campagne {campagne ?? 'N/D'}
                </span>
            }
        >
            {origine === 'modele' && (
                <p className="mb-4 flex items-start gap-2 text-sm text-muted">
                    <Info className="mt-0.5 size-4 shrink-0" aria-hidden />
                    <span className="min-w-0 flex-1">
                        Les critères {volet_libelle === 'Réadmission' ? 'de réadmission' : 'd’admission'} de la campagne ne sont pas encore appliqués : comparaison avec le modèle CROU par défaut.
                    </span>
                </p>
            )}
            <div className="mb-4 flex items-center gap-4">
                <div className="relative size-16 shrink-0" role="img" aria-label={`${score} % des critères conformes`}>
                    <svg viewBox="0 0 64 64" className="size-16 -rotate-90">
                        <circle cx="32" cy="32" r="26" fill="none" stroke="#E8EBE4" strokeWidth="7" />
                        <circle
                            cx="32"
                            cy="32"
                            r="26"
                            fill="none"
                            stroke={couleur}
                            strokeWidth="7"
                            strokeLinecap="round"
                            strokeDasharray={`${(circonference * score) / 100} ${circonference}`}
                        />
                    </svg>
                    <span className="absolute inset-0 flex items-center justify-center text-sm font-medium" style={{ color: couleur }}>
                        {conformes}/{regles.length}
                    </span>
                </div>
                <div>
                    <p className="font-medium text-gray-900">
                        {non_conformes > 0
                            ? `${non_conformes} critère${non_conformes > 1 ? 's' : ''} non conforme${non_conformes > 1 ? 's' : ''} : ${derogation ? 'la demande est rendue recevable par dérogation.' : 'la demande est irrecevable.'}`
                            : aVerifier > 0
                              ? 'Aucun critère non conforme, mais des informations sont manquantes ou à vérifier.'
                              : hors_liste
                                ? 'Tous les critères de recevabilité sont conformes, mais la liste élargie est complète : la demande est irrecevable.'
                                : 'Tous les critères de recevabilité sont conformes : la demande est recevable.'}
                    </p>
                    <p className="text-sm text-muted">
                        {conformes} critère(s) conforme(s) sur {regles.length}.
                        {non_conformes > 0 &&
                            (derogation
                                ? ` Demande dérogée${derogation.auteur ? ` par ${derogation.auteur}` : ''}${derogation.date ? ` le ${dateFr(derogation.date)}` : ''}.`
                                : '')}
                    </p>
                </div>
            </div>
            <div className="overflow-hidden rounded-brand border">
                <Table>
                    <TableHeader>
                        <TableRow className="hover:bg-transparent">
                            <TableHead>Critère</TableHead>
                            <TableHead>Exigence de la campagne</TableHead>
                            <TableHead>Saisie de l’étudiant</TableHead>
                            <TableHead className="text-right">Statut</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {regles.map((regle) => {
                            const etat = ETATS_REGLE[regle.etat];
                            return (
                                <TableRow key={regle.label} className={regle.etat === 'nok' || regle.etat === 'rejete' ? 'bg-red-50/40' : undefined}>
                                    <TableCell className="font-medium text-gray-900">{regle.label}</TableCell>
                                    <TableCell className="text-muted tabular-nums">{regle.campagne}</TableCell>
                                    <TableCell className={regle.etat === 'nok' || regle.etat === 'rejete' ? 'text-red-700' : undefined}>{regle.etudiant}</TableCell>
                                    <TableCell className="text-right">
                                        <Badge variant={etat.variant}>
                                            <etat.icone className="size-3.5" aria-hidden /> {etat.label}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </div>
            {lien && (
                <div className="mt-3 flex justify-end">
                    <a href={lien} className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
                        <ExternalLink className="size-3.5" aria-hidden /> Voir les critères
                    </a>
                </div>
            )}
        </Carte>
    );
}

function Documents({ documents }: { documents: Document[] }) {
    const [aRejeter, setARejeter] = useState<Document | null>(null);
    const [enCours, setEnCours] = useState<string | null>(null);
    const valides = documents.filter((document) => document.statut === 'valide').length;

    // Action existante en GET (`validate_document`), qui revient à la page avec `back()`.
    const appliquer = (url: string, id: string, fermer?: () => void) =>
        router.visit(url, {
            preserveScroll: true,
            onStart: () => setEnCours(id),
            onFinish: () => {
                setEnCours(null);
                fermer?.();
            },
        });

    return (
        <Carte id="documents-fournis" icone={<FileText size={18} strokeWidth={1.25} />} teinte="bg-[#E3EEFA] text-[#0B3A6E]" titre="Documents fournis" meta={`${valides} / ${documents.length} validé(s)`}>
            {documents.length > 0 && <Progress value={(valides / documents.length) * 100} className="mb-4" label="Documents validés" />}
            {documents.length === 0 ? (
                <p className="flex flex-col items-center gap-2 py-6 text-sm text-muted">
                    <FileText {...ICON} aria-hidden /> Aucun document fourni.
                </p>
            ) : (
                <ul className="divide-y">
                    {documents.map((document) => {
                        const statut = STATUTS_DOCUMENT[document.statut] ?? { label: 'En vérification', variant: 'warning' as const, icone: Clock };
                        return (
                            <li key={document.id} className="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div className="flex min-w-0 items-center gap-3">
                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-brand bg-surface text-muted" aria-hidden>
                                        <FileText size={18} strokeWidth={1.25} />
                                    </span>
                                    <div className="min-w-0">
                                        <p className="font-medium text-gray-900">{document.type}</p>
                                        <a href={document.url} target="_blank" rel="noopener" className="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">
                                            <ExternalLink className="size-3.5" aria-hidden /> Ouvrir le document
                                        </a>
                                    </div>
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    {document.valider && (
                                        <Button variant="secondary" size="sm" disabled={enCours !== null} onClick={() => appliquer(document.valider!, document.id)}>
                                            {enCours === document.id ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />} Valider
                                        </Button>
                                    )}
                                    {document.rejeter && (
                                        <Button variant="outline" size="sm" className="border-red-200 text-red-700 hover:bg-red-50" disabled={enCours !== null} onClick={() => setARejeter(document)}>
                                            <X className="size-4" aria-hidden /> Rejeter
                                        </Button>
                                    )}
                                    <Badge variant={statut.variant}>
                                        <statut.icone className="size-3.5" aria-hidden /> {statut.label}
                                    </Badge>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
            <ConfirmDialog
                open={aRejeter !== null}
                onOpenChange={(open) => !open && setARejeter(null)}
                title="Rejeter le document ?"
                description={`Le document « ${aRejeter?.type ?? ''} » sera marqué comme rejeté.`}
                confirmLabel="Rejeter"
                confirmIcon={<X className="size-4" aria-hidden />}
                danger
                processing={enCours !== null}
                onConfirm={() => aRejeter?.rejeter && appliquer(aRejeter.rejeter, aRejeter.id, () => setARejeter(null))}
            />
        </Carte>
    );
}

/** Motifs des réclamations de l'étudiant, affichés sous « Documents fournis », avec leur statut. */
function MotifsReclamation({ reclamations }: { reclamations: Reclamation[] }) {
    const [aTraiter, setATraiter] = useState<Reclamation | null>(null);

    return (
        <Carte
            id="motif-reclamation"
            icone={<MessageSquareWarning size={18} strokeWidth={1.25} />}
            teinte="bg-[#FFF4DB] text-[#8A5A00]"
            titre="Motif de la réclamation"
            meta={`${reclamations.length} réclamation${reclamations.length > 1 ? 's' : ''}`}
        >
            <ul className="divide-y">
                {reclamations.map((reclamation) => (
                    <li key={reclamation.id} className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <p className="text-xs text-muted">
                                {[
                                    reclamation.numero,
                                    reclamation.type_demande === 'readmission' ? 'Réadmission' : 'Admission',
                                    reclamation.campagne,
                                    reclamation.date ? `déposée le ${dateFr(reclamation.date)}` : null,
                                    reclamation.num_bac_matricule ? `n° table bac / matricule : ${reclamation.num_bac_matricule}` : null,
                                ]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                            <div className="flex flex-wrap items-center gap-2">
                                <StatutReclamationBadge statut={reclamation.statut} />
                                {reclamation.url_traiter && (
                                    <Button variant="secondary" size="sm" onClick={() => setATraiter(reclamation)}>
                                        <PenLine className="size-4" aria-hidden /> Traiter
                                    </Button>
                                )}
                            </div>
                        </div>
                        <p className="text-sm break-words whitespace-pre-line text-gray-900">{reclamation.motif}</p>
                    </li>
                ))}
            </ul>
            <TraiterReclamationDialog reclamation={aTraiter} onClose={() => setATraiter(null)} />
        </Carte>
    );
}

/** Motif saisi lors de la dérogation (demande rendue recevable par dérogation), affiché sous « Documents fournis ». */
function MotifDerogation({ auteur, date, motif }: NonNullable<NonNullable<Props['eligibilite']>['derogation']>) {
    return (
        <Carte
            id="motif-derogation"
            icone={<Scale size={18} strokeWidth={1.25} />}
            teinte="bg-[#FFF4DB] text-[#8A5A00]"
            titre="Motif de la dérogation"
            meta={(auteur || date) && `Dérogée${auteur ? ` par ${auteur}` : ''}${date ? ` le ${dateFr(date)}` : ''}`}
        >
            {motif ? <p className="text-sm whitespace-pre-line text-gray-900">{motif}</p> : <p className="text-sm text-muted">Aucun motif renseigné.</p>}
        </Carte>
    );
}

/** Motifs des rejets de la demande (passages en « Refusé » : agent, commission ou barème), affichés sous « Motif de la dérogation ». */
function MotifsRejet({ rejets }: { rejets: Rejet[] }) {
    return (
        <Carte
            id="motifs-rejet"
            icone={<CircleX size={18} strokeWidth={1.25} />}
            teinte="bg-red-50 text-[#B42318]"
            titre="Motifs de rejet"
            meta={`${rejets.length} rejet${rejets.length > 1 ? 's' : ''}`}
        >
            <ul className="divide-y">
                {rejets.map((rejet) => (
                    <li key={rejet.id} className="flex flex-col gap-1 py-3 first:pt-0 last:pb-0">
                        <p className="text-sm whitespace-pre-line text-gray-900">{rejet.motif ?? 'Aucun motif renseigné.'}</p>
                        <p className="text-xs text-muted">
                            {[rejet.automatique ? 'Refus automatique (barème)' : rejet.auteur ? `Rejetée par ${rejet.auteur}` : 'Rejetée', rejet.date ? `le ${dateFr(rejet.date)}` : null]
                                .filter(Boolean)
                                .join(' ')}
                        </p>
                    </li>
                ))}
            </ul>
        </Carte>
    );
}

/** Fenêtre du bouton « Déroger » : motif obligatoire ; la demande passe en tête de la liste provisoire (badge « Dérogé »). */
function DialogueDerogation({ url, nom, open, onOpenChange }: { url: string; nom: string; open: boolean; onOpenChange: (open: boolean) => void }) {
    const [motif, setMotif] = useState('');
    const [erreur, setErreur] = useState<string | null>(null);
    const [enCours, setEnCours] = useState(false);

    const deroger = async () => {
        if (motif.trim() === '') {
            setErreur('Le motif de la dérogation est obligatoire.');
            return;
        }
        setEnCours(true);
        const ok = await envoyer(url, { motif: motif.trim() }, { onError: (erreurs) => setErreur(erreurs.motif ?? Object.values(erreurs)[0] ?? null) });
        setEnCours(false);
        if (ok) {
            setMotif('');
            onOpenChange(false);
        }
    };

    return (
        <ConfirmDialog
            open={open}
            onOpenChange={(ouvert) => {
                onOpenChange(ouvert);
                if (!ouvert) setErreur(null);
            }}
            title="Déroger à la recevabilité ?"
            description={`La demande de ${nom} sera rendue recevable et passera en tête de la liste provisoire avec le badge « Dérogé ». Aucune chambre n’est réservée.`}
            confirmLabel="Déroger"
            confirmIcon={<Scale className="size-4" aria-hidden />}
            processing={enCours}
            onConfirm={deroger}
        >
            <Field id="derogation-motif" label="Motif de la dérogation" required error={erreur ?? undefined} hint="2 000 caractères maximum.">
                <Textarea
                    id="derogation-motif"
                    rows={4}
                    maxLength={2000}
                    invalid={!!erreur}
                    value={motif}
                    onChange={(event) => {
                        setMotif(event.target.value);
                        setErreur(null);
                    }}
                />
            </Field>
        </ConfirmDialog>
    );
}

const scoreFr = (valeur: number) => valeur.toLocaleString('fr-FR', { maximumFractionDigits: 2 });

/** Texte saisi, ou mention « non renseigné » en gris. */
const valeur = (texte: string | null | undefined, vide = 'Non renseigné') => (texte != null && texte !== '' ? texte : <span className="text-muted">{vide}</span>);
const ouiNon = (oui: boolean | null) => (oui === null ? valeur(null) : oui ? 'Oui' : 'Non');

/** Informations saisies par l'étudiant, regroupées dans l'ordre des étapes du formulaire de demande. */
function Profil({ etudiant, profil, documents }: { etudiant: Props['etudiant']; profil: ProfilDemande; documents: Document[] | null }) {
    const [libelle, variante] = STATUTS_INSCRIPTION[etudiant.statut_inscription ?? ''] ?? ['Non défini', 'neutral' as const];
    const { identite, etudes, situation, adresse } = profil;
    const valides = documents?.filter((document) => document.statut === 'valide').length ?? 0;

    return (
        <section className="rounded-brand border bg-white p-4">
            <div className="mb-3 flex items-center gap-3">
                <span className="flex size-12 shrink-0 items-center justify-center rounded-full bg-soft text-sm font-medium text-primary" aria-hidden>
                    {initiales(etudiant.name)}
                </span>
                <div className="min-w-0">
                    <p className="font-medium text-gray-900">{etudiant.name}</p>
                    <p className="text-xs text-muted">{etudiant.matricule ?? 'Matricule non renseigné'}</p>
                </div>
            </div>
            <div className="mb-2 flex flex-wrap gap-2">
                <Badge variant={variante}>{libelle}</Badge>
                {etudiant.is_validation ? (
                    <Badge variant="success">
                        <CircleCheck className="size-3.5" aria-hidden /> Validé
                    </Badge>
                ) : etudiant.is_rejet ? (
                    <Badge variant="danger">
                        <CircleX className="size-3.5" aria-hidden /> Rejeté
                    </Badge>
                ) : (
                    <Badge variant="warning">
                        <Clock className="size-3.5" aria-hidden /> Validation en cours
                    </Badge>
                )}
                {situation.boursier && <Badge variant="info">Boursier</Badge>}
            </div>

            <Groupe etape={1} titre="Identité">
                <Ligne terme="Civilité">{valeur(identite.civilite, 'Non renseignée')}</Ligne>
                <Ligne terme="Date de naissance">{identite.date_naissance ? dateFr(identite.date_naissance) : valeur(null, 'Non renseignée')}</Ligne>
                <Ligne terme="Lieu de naissance">{valeur(identite.lieu_naissance)}</Ligne>
                <Ligne terme="Nationalité">{valeur(identite.nationalite, 'Non renseignée')}</Ligne>
                <Ligne terme="Téléphone">{valeur(identite.telephone1)}</Ligne>
                {identite.telephone2 && <Ligne terme="Téléphone 2">{identite.telephone2}</Ligne>}
                <Ligne terme="E-mail">{valeur(identite.email1)}</Ligne>
                {identite.email2 && <Ligne terme="E-mail 2">{identite.email2}</Ligne>}
                <Ligne terme="Type de pièce">{valeur(identite.type_piece)}</Ligne>
                <Ligne terme="Numéro de la pièce">{valeur(identite.numero_piece)}</Ligne>
                <Ligne terme="Expiration de la pièce">{identite.date_expiration ? dateFr(identite.date_expiration) : valeur(null, 'Non renseignée')}</Ligne>
                <Ligne terme="Groupe sanguin">{valeur(identite.groupe_sanguin)}</Ligne>
            </Groupe>

            <Groupe etape={2} titre="Études">
                <Ligne terme="Campagne">{valeur(etudes.campagne, 'Non renseignée')}</Ligne>
                <Ligne terme="Niveau précédent">{valeur(etudes.niveau_precedent)}</Ligne>
                <Ligne terme="Niveau actuel">{valeur(etudes.niveau)}</Ligne>
                <Ligne terme="Redoublant">{ouiNon(etudes.redoublant)}</Ligne>
                {!etudes.bachelier && <Ligne terme="Matricule">{valeur(etudes.matricule)}</Ligne>}
                <Ligne terme="Filière">{valeur(etudes.filiere, 'Non renseignée')}</Ligne>
                {etudes.bachelier ? (
                    <>
                        <Ligne terme="Note au bac">
                            {etudes.note_bac !== null ? (
                                <>
                                    {scoreFr(etudes.note_bac)}/400 <span className="text-muted">({scoreFr(etudes.moyenne ?? 0)}/20)</span>
                                </>
                            ) : (
                                valeur(null, 'Non renseignée')
                            )}
                        </Ligne>
                        <Ligne terme="Ville du bac">{valeur(etudes.ville_bac, 'Non renseignée')}</Ligne>
                        <Ligne terme="N° de table au bac">{valeur(etudes.num_table_bac, 'Non renseigné')}</Ligne>
                    </>
                ) : (
                    <>
                        <Ligne terme="Moyenne générale">{etudes.moyenne !== null ? `${scoreFr(etudes.moyenne)}/20` : valeur(null, 'Non renseignée')}</Ligne>
                        <Ligne terme="Crédits validés">
                            {etudes.credits !== null ? (
                                <>
                                    {etudes.credits}/{etudes.credits_max} <span className="text-muted">({scoreFr((etudes.credits / etudes.credits_max) * 100)} %)</span>
                                </>
                            ) : (
                                valeur(null)
                            )}
                        </Ligne>
                    </>
                )}
            </Groupe>

            <Groupe etape={3} titre="Situation">
                <Ligne terme="Boursier">{situation.boursier ? 'Oui' : 'Non'}</Ligne>
                {situation.observations && (
                    <div className="py-1.5">
                        <dt className="text-muted">Situation sociale ou observations</dt>
                        <dd className="mt-0.5 break-words whitespace-pre-line">{situation.observations}</dd>
                    </div>
                )}
            </Groupe>

            <Groupe etape={4} titre="Adresse">
                <Ligne terme="Région">{valeur(adresse.region, 'Non renseignée')}</Ligne>
                <Ligne terme="Ville">{valeur(adresse.ville, 'Non renseignée')}</Ligne>
                <Ligne terme="Adresse complète">{valeur(adresse.adresse, 'Non renseignée')}</Ligne>
                <Ligne terme="Distance domicile – campus">{adresse.distance !== null ? `${scoreFr(adresse.distance)} km` : valeur(null, 'Non renseignée')}</Ligne>
            </Groupe>

            {documents && (
                <Groupe etape={5} titre="Documents">
                    <Ligne terme="Pièces fournies">
                        {documents.length > 0 ? (
                            <a href="#documents-fournis" className="font-medium text-primary hover:underline">
                                {documents.length} · {valides} validée{valides > 1 ? 's' : ''}
                            </a>
                        ) : (
                            valeur(null, 'Aucune')
                        )}
                    </Ligne>
                </Groupe>
            )}
        </section>
    );
}

function Groupe({ etape, titre, children }: { etape: number; titre: string; children: ReactNode }) {
    return (
        <>
            <h3 className="mt-4 mb-1 flex items-center gap-2 text-xs font-medium tracking-wide text-muted uppercase">
                <span className="flex size-5 items-center justify-center rounded-full bg-soft text-[11px] text-primary" aria-hidden>
                    {etape}
                </span>
                {titre}
            </h3>
            <dl className="divide-y text-sm">{children}</dl>
        </>
    );
}

function Ligne({ terme, children }: { terme: string; children: ReactNode }) {
    return (
        <div className="flex justify-between gap-4 py-1.5">
            <dt className="shrink-0 text-muted">{terme}</dt>
            <dd className="min-w-0 text-right break-words">{children}</dd>
        </div>
    );
}

/** Toutes les demandes de l'étudiant, toutes campagnes confondues. */
function Historique({ demandes }: { demandes: NonNullable<Props['historique']> }) {
    const campagnes = new Set(demandes.map((item) => item.campagne_id).filter(Boolean)).size;
    return (
        <section className="overflow-hidden rounded-brand border bg-white">
            <div className="flex items-center justify-between gap-2 border-b px-4 py-3">
                <h2 className="flex items-center gap-2 text-base font-medium text-gray-900">
                    <span className="flex size-8 items-center justify-center rounded-brand bg-surface text-muted" aria-hidden>
                        <History size={18} strokeWidth={1.25} />
                    </span>
                    Historique des demandes
                </h2>
                <span className="text-sm text-muted">
                    {demandes.length} demande{demandes.length > 1 ? 's' : ''} · {campagnes} campagne{campagnes > 1 ? 's' : ''}
                </span>
            </div>
            {demandes.length === 0 ? (
                <p className="flex flex-col items-center gap-2 py-8 text-sm text-muted">
                    <FolderOpen {...ICON} aria-hidden /> Aucune demande trouvée.
                </p>
            ) : (
                <ul className="divide-y">
                    {demandes.map((item, index) => (
                        <li key={item.id} className="flex items-start justify-between gap-3 px-4 py-3">
                            <div>
                                <p className="font-medium text-gray-900">
                                    Demande {item.numero ? `#${item.numero}` : ''}
                                    {index === 0 && (
                                        <Badge variant="info" className="ml-2">
                                            Dernière
                                        </Badge>
                                    )}
                                </p>
                                <p className="text-xs text-muted">{[item.annee ? `Campagne ${item.annee}` : null, dateFr(item.date)].filter(Boolean).join(' · ')}</p>
                            </div>
                            <EtatDossierBadge etat={item.etat} />
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
