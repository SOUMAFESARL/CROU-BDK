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
    Pencil,
    Route,
    Scale,
    ShieldAlert,
    TriangleAlert,
    X,
} from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import { EtatDossierBadge } from '@/components/dossier/etat-dossier-badge';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { PageHeader } from '@/components/ui/page-header';
import { EtudiantEditDialog, type OptionsEtudiant, type ValeursEtudiant } from '@/components/residents/etudiant-edit-dialog';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { dateFr, initiales } from '@/lib/text';
import { cn } from '@/lib/utils';

type Regle = { label: string; campagne: string; etudiant: string; etat: 'ok' | 'nok' | 'nd' | 'nv' | 'rejete' };
type Volet = { volet: string; poids: number; situation: string; points: number; apport: number };
type Bareme = { score: number | null; minimum: number; volets: Volet[]; erreur: string | null; score_decision: number | null };
type Document = { id: string; type: string; url: string; statut: string; valider: string | null; rejeter: string | null };
type Derogation = {
    id: string;
    statut: 'pending' | 'approved' | 'rejected' | string;
    demandeur: string | null;
    date: string | null;
    motif: string;
    non_respectes: string[];
    decision: { auteur: string | null; date: string; motif: string | null } | null;
    decider: string | null;
};

type Props = {
    etudiant: { id: string; name: string; matricule: string | null; statut_inscription: string | null; is_validation: boolean; is_rejet: boolean };
    demande: { id: string; numero: string | null; etat: string | null; date: string | null } | null;
    discipline: { label: string; bloquant: boolean } | null;
    eligibilite: { campagne: string | null; regles: Regle[]; conformes: number; score: number } | null;
    /** Score du barème de scoring actif (seul score comparé au minimum de la décision automatique). */
    bareme: Bareme | null;
    documents: Document[] | null;
    derogations: { liste: Derogation[]; demander: string | null } | null;
    suivi: {
        labels: Record<string, string>;
        proposition: { chambre: string | null; jusqu_au: string | null } | null;
        actions: { etat: string; label: string }[];
        transition: string | null;
        /** Levée en un clic d'un refus automatique (barème de scoring). */
        rendre_eligible: string | null;
        /** Décision du barème à l'origine de l'état actuel. */
        decision: { etat: string; score: number; minimum: number } | null;
        assignation_id: string | null;
        historique: { date: string | null; etat: string; auteur: string | null; motif: string | null }[] | null;
    } | null;
    profil: Record<string, string | boolean | null> | null;
    historique: { id: string; numero: string | null; campagne_id: string | null; annee: string | null; date: string | null; etat: string | null }[] | null;
    /** Valeurs et référentiels du formulaire de modification (`null` sans le droit de modifier). */
    edition: ValeursEtudiant | null;
    options: OptionsEtudiant | null;
    urls: { admissions: string | null; contrat: string | null; modifier: string | null };
};

const PARCOURS = ['brouillon', 'soumis', 'eligible', 'confirme', 'attribue_definitif'];

const ETATS_REGLE = {
    ok: { label: 'Conforme', variant: 'success', icone: CircleCheck },
    nok: { label: 'Non conforme', variant: 'danger', icone: CircleX },
    nd: { label: 'Non renseigné', variant: 'warning', icone: TriangleAlert },
    nv: { label: 'Non vérifié', variant: 'warning', icone: Clock },
    rejete: { label: 'Rejeté', variant: 'danger', icone: CircleX },
} as const;

const STATUTS_DOCUMENT: Record<string, { label: string; variant: 'success' | 'danger' | 'warning'; icone: typeof Clock }> = {
    valide: { label: 'Validé', variant: 'success', icone: CircleCheck },
    rejete: { label: 'Rejeté', variant: 'danger', icone: CircleX },
};

const STATUTS_DEROGATION: Record<string, { label: string; variant: 'success' | 'danger' | 'warning'; icone: typeof Clock }> = {
    pending: { label: 'En attente', variant: 'warning', icone: Clock },
    approved: { label: 'Approuvée', variant: 'success', icone: CircleCheck },
    rejected: { label: 'Refusée', variant: 'danger', icone: CircleX },
};

const STATUTS_INSCRIPTION: Record<string, [string, 'success' | 'warning' | 'info' | 'danger']> = {
    residents: ['Résident', 'success'],
    en_attente: ['En attente', 'warning'],
    demande: ['Nouvelle demande', 'info'],
    annuler: ['Annulé', 'danger'],
};

/** Traitement d'un dossier de candidature (remplace `admission/traiter-demande.blade.php`, `derogations.blade.php` et le mode admin de `dossier-cycle`). */
export default function AdmissionShow(props: Props) {
    const { etudiant, demande, discipline, eligibilite, bareme, documents, derogations, suivi, profil, historique, edition, options, urls } = props;
    const [enEdition, setEnEdition] = useState(false);
    // Objet stable : la fenêtre réinitialise son formulaire à chaque ouverture (et après rechargement des valeurs).
    const etudiantEdite = useMemo(
        () => (enEdition && urls.modifier && edition ? { name: etudiant.name, url: urls.modifier, valeurs: edition } : null),
        [enEdition, urls.modifier, edition, etudiant.name],
    );
    const modifiable = !!(urls.modifier && edition && options);

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
                        <span>
                            <EtatDossierBadge etat={demande?.etat} />
                        </span>
                    </div>
                }
                actions={
                    (urls.contrat || urls.admissions || modifiable) && (
                        <>
                            {urls.admissions && (
                                <Link
                                    href={urls.admissions}
                                    className="inline-flex h-10 items-center gap-2 rounded-brand border border-gray-300 bg-white px-4 text-sm font-medium text-muted hover:bg-surface hover:text-primary"
                                >
                                    <ArrowLeft {...ICON} aria-hidden /> Retour aux admissions
                                </Link>
                            )}
                            {modifiable && (
                                <Button className="h-10 px-4" onClick={() => setEnEdition(true)} title="Corriger les informations saisies par l’étudiant">
                                    <Pencil {...ICON} aria-hidden /> Modifier les informations
                                </Button>
                            )}
                            {urls.contrat && (
                                <a
                                    href={urls.contrat}
                                    target="_blank"
                                    rel="noopener"
                                    title="Ouvrir le contrat PDF généré depuis le dossier dans un nouvel onglet"
                                    className="inline-flex h-10 items-center gap-2 rounded-brand border border-primary bg-white px-4 text-sm font-medium text-primary hover:bg-soft"
                                >
                                    <FileText {...ICON} aria-hidden /> Consulter le contrat
                                </a>
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
                    {derogations && demande && <Derogations {...derogations} demandeId={demande.id} />}
                    {suivi && demande && <Suivi suivi={suivi} etat={demande.etat} />}
                </div>

                <aside className="flex flex-col gap-6">
                    {profil && <Profil etudiant={etudiant} profil={profil} />}
                    {historique && <Historique demandes={historique} />}
                </aside>
            </div>

            {modifiable && options && <EtudiantEditDialog etudiant={etudiantEdite} options={options} onClose={() => setEnEdition(false)} />}
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

function Eligibilite({ campagne, regles, conformes, score }: NonNullable<Props['eligibilite']>) {
    const couleur = score >= 80 ? '#067647' : score >= 50 ? '#B54708' : '#B42318';
    const circonference = 2 * Math.PI * 26;

    return (
        <Carte icone={<ListChecks size={18} strokeWidth={1.25} />} teinte="bg-soft text-primary" titre="Critères d’éligibilité" meta={`Campagne ${campagne ?? 'N/D'}`}>
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
                        {score === 100
                            ? 'Tous les critères sont conformes — étudiant éligible.'
                            : score >= 60
                              ? 'Quelques critères non conformes — vérification recommandée avant décision.'
                              : 'Plusieurs critères non conformes — étudiant probablement non éligible.'}
                    </p>
                    <p className="text-sm text-muted">
                        {conformes} critère(s) conforme(s) sur {regles.length}. Les critères sont indicatifs : seule une sanction disciplinaire bloquante empêche de rendre le dossier éligible.
                    </p>
                </div>
            </div>
            <div className="overflow-hidden rounded-brand border">
                <Table>
                    <TableHeader>
                        <TableRow className="hover:bg-transparent">
                            <TableHead>Critère</TableHead>
                            <TableHead>Règle de la campagne</TableHead>
                            <TableHead>Valeur de l’étudiant</TableHead>
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

function Derogations({ liste, demander, demandeId }: NonNullable<Props['derogations']> & { demandeId: string }) {
    const [motif, setMotif] = useState('');
    const [erreur, setErreur] = useState<string | null>(null);
    const [enCours, setEnCours] = useState(false);

    const demanderDerogation = async () => {
        if (!demander) return;
        if (motif.trim() === '') {
            setErreur('Le motif de la dérogation est obligatoire.');
            return;
        }
        setEnCours(true);
        const ok = await envoyer(demander, { demande_id: demandeId, reason: motif.trim() }, { onError: (erreurs) => setErreur(erreurs.reason ?? Object.values(erreurs)[0] ?? null) });
        setEnCours(false);
        if (ok) setMotif('');
    };

    return (
        <Carte icone={<Scale size={18} strokeWidth={1.25} />} teinte="bg-[#FEEBC0] text-[#6B3F00]" titre="Dérogation à l’éligibilité" meta={`${liste.length} enregistrée(s)`}>
            <p className="mb-4 text-sm text-muted">
                Une dérogation permet de poursuivre l’admission malgré des critères non respectés. Elle doit être motivée et validée par un autre Sous-DAL ou Chef du service logement habilité. Elle ne
                réserve aucune chambre et ne supprime aucun contrôle de disponibilité.
            </p>

            {demander && (
                <div className="mb-4 flex flex-col gap-3 rounded-brand bg-surface p-4">
                    <Field id="derogation-motif" label="Motif de la dérogation" required error={erreur ?? undefined} hint="2 000 caractères maximum.">
                        <Textarea
                            id="derogation-motif"
                            rows={3}
                            maxLength={2000}
                            invalid={!!erreur}
                            value={motif}
                            onChange={(event) => {
                                setMotif(event.target.value);
                                setErreur(null);
                            }}
                        />
                    </Field>
                    <div>
                        <Button size="sm" variant="accent" className="h-10" disabled={enCours} onClick={demanderDerogation}>
                            {enCours && <Loader2 className="size-4 animate-spin" aria-hidden />}
                            Demander une dérogation
                        </Button>
                    </div>
                </div>
            )}

            {liste.length === 0 ? (
                <p className="text-sm text-muted">Aucune dérogation enregistrée.</p>
            ) : (
                <div className="flex flex-col gap-3">
                    {liste.map((derogation) => (
                        <ArticleDerogation key={derogation.id} derogation={derogation} />
                    ))}
                </div>
            )}
        </Carte>
    );
}

function ArticleDerogation({ derogation }: { derogation: Derogation }) {
    const [motif, setMotif] = useState('');
    const [erreur, setErreur] = useState<string | null>(null);
    const [enCours, setEnCours] = useState<'approve' | 'reject' | null>(null);
    const statut = STATUTS_DEROGATION[derogation.statut] ?? { label: derogation.statut, variant: 'warning' as const, icone: Info };

    const decider = async (decision: 'approve' | 'reject') => {
        if (!derogation.decider) return;
        if (motif.trim() === '') {
            setErreur('Le motif de la décision est obligatoire.');
            return;
        }
        setEnCours(decision);
        await envoyer(derogation.decider, { decision, reason: motif.trim() }, { onError: (erreurs) => setErreur(erreurs.reason ?? Object.values(erreurs)[0] ?? null) });
        setEnCours(null);
    };

    return (
        <article className="rounded-brand border p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <Badge variant={statut.variant}>
                    <statut.icone className="size-3.5" aria-hidden /> {statut.label}
                </Badge>
                <span className="text-xs text-muted">
                    {derogation.demandeur} — {dateFr(derogation.date)} {derogation.date?.slice(11, 16)}
                </span>
            </div>
            <p className="mt-2 text-sm whitespace-pre-line">{derogation.motif}</p>
            {derogation.non_respectes.length > 0 && (
                <ul className="mt-2 list-disc pl-5 text-sm text-red-700">
                    {derogation.non_respectes.map((label) => (
                        <li key={label}>{label}</li>
                    ))}
                </ul>
            )}
            <div className="mt-3 border-t pt-3 text-sm">
                {derogation.decision ? (
                    <p className="text-muted">
                        Décision de {derogation.decision.auteur} — {dateFr(derogation.decision.date)} {derogation.decision.date.slice(11, 16)} : {derogation.decision.motif}
                    </p>
                ) : derogation.decider ? (
                    <div className="flex flex-col gap-3">
                        <Field id={`decision-${derogation.id}`} label="Motif de la décision" required error={erreur ?? undefined}>
                            <Textarea
                                id={`decision-${derogation.id}`}
                                rows={2}
                                maxLength={2000}
                                invalid={!!erreur}
                                value={motif}
                                onChange={(event) => {
                                    setMotif(event.target.value);
                                    setErreur(null);
                                }}
                            />
                        </Field>
                        <div className="flex flex-wrap gap-2">
                            <Button size="sm" disabled={enCours !== null} onClick={() => decider('approve')}>
                                {enCours === 'approve' ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />} Approuver la dérogation
                            </Button>
                            <Button size="sm" variant="outline" className="border-red-200 text-red-700 hover:bg-red-50" disabled={enCours !== null} onClick={() => decider('reject')}>
                                {enCours === 'reject' ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <X className="size-4" aria-hidden />} Refuser
                            </Button>
                        </div>
                    </div>
                ) : (
                    <p className="text-muted">Validation attendue d’un autre responsable habilité.</p>
                )}
            </div>
        </article>
    );
}

const scoreFr = (valeur: number) => valeur.toLocaleString('fr-FR', { maximumFractionDigits: 2 });

/** Transitions pour lesquelles `DossierWorkflow` exige un motif (ainsi que toute sortie de l'état « refusé »). */
const ETATS_AVEC_MOTIF = ['incomplet', 'refuse', 'annule', 'expire'];

function Suivi({ suivi, etat }: { suivi: NonNullable<Props['suivi']>; etat: string | null }) {
    const [action, setAction] = useState('');
    const [motif, setMotif] = useState('');
    const [erreurs, setErreurs] = useState<{ etat?: string; motif?: string }>({});
    const [enCours, setEnCours] = useState(false);
    const [confirmerLevee, setConfirmerLevee] = useState(false);
    // « En étude » ne subsiste que sur les dossiers de l'ancien parcours : affiché au niveau « Soumis ».
    const position = etat ? PARCOURS.indexOf(etat === 'en_etude' ? 'soumis' : etat) : -1;

    const enregistrer = async () => {
        const nouvelles: typeof erreurs = {};
        if (!action) nouvelles.etat = 'Choisissez une action.';
        // Motif exigé par le workflow (le serveur le revérifie).
        if ((ETATS_AVEC_MOTIF.includes(action) || etat === 'refuse') && motif.trim() === '') nouvelles.motif = 'Un motif est obligatoire pour cette étape.';
        setErreurs(nouvelles);
        if (Object.keys(nouvelles).length || !suivi.transition) return;

        setEnCours(true);
        const ok = await envoyer(
            suivi.transition,
            { etat: action, motif: motif.trim(), ...(suivi.assignation_id ? { assignation_id: suivi.assignation_id } : {}) },
            { onError: (retour) => setErreurs({ etat: retour.etat, motif: retour.motif ?? (!retour.etat ? Object.values(retour)[0] : undefined) }) },
        );
        setEnCours(false);
        if (ok) {
            setAction('');
            setMotif('');
        }
    };

    const leverRefus = async () => {
        if (!suivi.rendre_eligible) return;
        setEnCours(true);
        const ok = await envoyer(suivi.rendre_eligible, { etat: 'eligible' });
        setEnCours(false);
        if (ok) setConfirmerLevee(false);
    };

    return (
        <Carte icone={<Route size={18} strokeWidth={1.25} />} teinte="bg-[#F8E4EF] text-[#6E1F4E]" titre="Suivi du dossier">
            <ol aria-label="Parcours du dossier" className="mb-4 flex flex-wrap gap-2">
                {PARCOURS.map((etape, index) => {
                    const fait = position >= 0 && index < position;
                    const courant = position === index;
                    return (
                        <li
                            key={etape}
                            aria-current={courant ? 'step' : undefined}
                            className={cn(
                                'inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs',
                                fait && 'border-secondary bg-soft text-primary',
                                courant && 'border-primary bg-primary text-white',
                                !fait && !courant && 'text-muted',
                            )}
                        >
                            {fait && <Check className="size-3" aria-hidden />}
                            {suivi.labels[etape] ?? etape}
                        </li>
                    );
                })}
            </ol>

            <div className="flex flex-col gap-2 text-sm">
                <p>
                    État actuel : <strong className="font-medium">{(etat && suivi.labels[etat]) ?? etat ?? '—'}</strong>
                </p>
                {suivi.proposition && (
                    <p>
                        Proposition : chambre <strong className="font-medium">{suivi.proposition.chambre}</strong>
                        {suivi.proposition.jusqu_au && ` — valable jusqu’au ${dateFr(suivi.proposition.jusqu_au)} ${suivi.proposition.jusqu_au.slice(11, 16)}`}
                    </p>
                )}
                {etat === 'accepte' && <p className="text-muted">La proposition a été acceptée. La validation définitive reste à effectuer par le service Logement.</p>}
                {suivi.decision && (
                    <p className="text-muted">
                        Décision automatique du barème : score <strong className="font-medium text-gray-900">{scoreFr(suivi.decision.score)}/100</strong> pour un minimum de{' '}
                        {scoreFr(suivi.decision.minimum)}/100.
                    </p>
                )}
            </div>

            {suivi.rendre_eligible && (
                <div className="mt-4 flex flex-col gap-3 rounded-brand border border-[#C6E3A6] bg-soft p-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-[#004D00]">Ce dossier a été refusé automatiquement (score insuffisant). Vous pouvez le rendre éligible.</p>
                    <Button size="sm" className="h-10 shrink-0" disabled={enCours} onClick={() => setConfirmerLevee(true)}>
                        <CircleCheck className="size-4" aria-hidden /> Rendre éligible
                    </Button>
                </div>
            )}
            <ConfirmDialog
                open={confirmerLevee}
                onOpenChange={setConfirmerLevee}
                title="Rendre ce dossier éligible ?"
                description="Le refus automatique sera levé : le dossier passe en « Éligible » et l’étudiant devra confirmer son intérêt."
                confirmLabel="Rendre éligible"
                confirmIcon={<CircleCheck className="size-4" aria-hidden />}
                processing={enCours}
                onConfirm={leverRefus}
            />

            {suivi.actions.length > 0 && (
                <div className="mt-4 flex flex-col gap-3 rounded-brand bg-surface p-4">
                    <Field id="transition-etat" label="Action" required error={erreurs.etat}>
                        <Select
                            value={action || undefined}
                            onValueChange={(valeur) => {
                                setAction(valeur);
                                setErreurs({});
                            }}
                        >
                            <SelectTrigger id="transition-etat" invalid={!!erreurs.etat}>
                                <SelectValue placeholder="Choisir" />
                            </SelectTrigger>
                            <SelectContent>
                                {suivi.actions.map((item) => (
                                    <SelectItem key={item.etat} value={item.etat}>
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field
                        id="transition-motif"
                        label="Motif ou éléments à compléter"
                        error={erreurs.motif}
                        hint="Un motif est obligatoire pour déclarer le dossier incomplet, le refuser, l’annuler ou rouvrir son étude."
                    >
                        <Textarea id="transition-motif" rows={2} maxLength={5000} invalid={!!erreurs.motif} value={motif} onChange={(event) => setMotif(event.target.value)} />
                    </Field>
                    <div>
                        <Button size="sm" className="h-10" disabled={enCours} onClick={enregistrer}>
                            {enCours ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />}
                            Enregistrer cette étape
                        </Button>
                    </div>
                </div>
            )}

            {suivi.historique && (
                <details className="mt-4 text-sm">
                    <summary className="cursor-pointer font-medium text-primary">Historique du dossier</summary>
                    {suivi.historique.length === 0 ? (
                        <p className="mt-2 text-muted">Aucune transition enregistrée.</p>
                    ) : (
                        <ol className="mt-3 flex flex-col gap-3 border-l pl-4">
                            {suivi.historique.map((transition, index) => (
                                <li key={index}>
                                    <p>
                                        <span className="text-muted">
                                            {dateFr(transition.date)} {transition.date?.slice(11, 16)}
                                        </span>{' '}
                                        — <strong className="font-medium">{transition.etat}</strong> ({transition.auteur})
                                    </p>
                                    {transition.motif && <p className="mt-0.5 whitespace-pre-line text-muted">{transition.motif}</p>}
                                </li>
                            ))}
                        </ol>
                    )}
                </details>
            )}
        </Carte>
    );
}

function Profil({ etudiant, profil }: { etudiant: Props['etudiant']; profil: NonNullable<Props['profil']> }) {
    const [libelle, variante] = STATUTS_INSCRIPTION[etudiant.statut_inscription ?? ''] ?? ['Non défini', 'neutral' as const];
    const valeur = (cle: string, vide = 'Non renseigné') => {
        const v = profil[cle];
        return typeof v === 'string' && v !== '' ? v : <span className="text-muted">{vide}</span>;
    };

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
                {profil.boursier && <Badge variant="info">Boursier</Badge>}
            </div>

            <Groupe titre="Admission">
                <Ligne terme="Niveau">{valeur('niveau')}</Ligne>
                <Ligne terme="Campagne">{valeur('campagne', 'Non renseignée')}</Ligne>
                <Ligne terme="Boursier">{profil.boursier ? 'Oui' : 'Non'}</Ligne>
                <Ligne terme="Groupe sanguin">{valeur('groupe_sanguin')}</Ligne>
            </Groupe>
            <Groupe titre="Contact">
                <Ligne terme="Téléphone 1">{valeur('telephone1')}</Ligne>
                <Ligne terme="Téléphone 2">{valeur('telephone2')}</Ligne>
                <Ligne terme="E-mail 1">{valeur('email1')}</Ligne>
                <Ligne terme="E-mail 2">{valeur('email2')}</Ligne>
            </Groupe>
            <Groupe titre="Adresse">
                <Ligne terme="Région">{valeur('region', 'Non renseignée')}</Ligne>
                <Ligne terme="Ville">{valeur('ville', 'Non renseignée')}</Ligne>
                <Ligne terme="Adresse">{valeur('adresse', 'Non renseignée')}</Ligne>
                <Ligne terme="Nationalité">{valeur('nationalite', 'Non renseignée')}</Ligne>
            </Groupe>
            <Groupe titre="Pièce d’identité">
                <Ligne terme="Date de naissance">{profil.date_naissance ? dateFr(profil.date_naissance as string) : valeur('date_naissance')}</Ligne>
                <Ligne terme="Lieu de naissance">{valeur('lieu_naissance')}</Ligne>
                <Ligne terme="Type">{valeur('type_piece')}</Ligne>
                <Ligne terme="Numéro">{valeur('numero_piece')}</Ligne>
                <Ligne terme="Expiration">{profil.date_expiration ? dateFr(profil.date_expiration as string) : valeur('date_expiration', 'Non renseignée')}</Ligne>
            </Groupe>
        </section>
    );
}

function Groupe({ titre, children }: { titre: string; children: ReactNode }) {
    return (
        <>
            <h3 className="mt-4 mb-1 text-xs font-medium tracking-wide text-muted uppercase">{titre}</h3>
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
