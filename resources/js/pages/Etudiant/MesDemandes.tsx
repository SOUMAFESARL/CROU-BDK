import { zodResolver } from '@hookform/resolvers/zod';
import { BedDouble, Check, ChevronRight, CircleDot, DoorOpen, FilePen, FilePlus, FolderOpen, History, Info as InfoIcone, Inbox, KeyRound, Loader2, Send, XCircle } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { DemandeSortie, type FormulaireSortie } from '@/components/etudiant/demande-sortie';
import { Liste, Ligne, Notice, Panneau, Vide } from '@/components/etudiant/panneau';
import { ReponseProposition, type Proposition } from '@/components/etudiant/reponse-proposition';
import { ICON } from '@/components/layout/app-sidebar';
import { EtudiantLayout } from '@/components/layout/etudiant-layout';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Progress } from '@/components/ui/progress';
import { StatCard } from '@/components/ui/stat-card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { cn } from '@/lib/utils';
import { MAX_MOTIF_TRANSITION, transitionSchema, type Transition } from '@/schemas/demandes';

type Onglet = 'dossier' | 'sortie';
type Ton = 'success' | 'warning' | 'danger' | 'neutral' | 'info';
type Etat = { code: string; libelle: string; ton: Ton };
type Action = { etat: 'soumis' | 'annule'; libelle: string; motifObligatoire: boolean };

type Dossier = {
    id: string;
    reference: string;
    annee: string | null;
    soumiseLe: string | null;
    echeance: string | null;
    miseAJour: string | null;
    moyenne: string | number | null;
    credits: string | number | null;
    etat: Etat;
    etapes: { code: string; libelle: string; date: string | null; statut: 'fait' | 'en_cours' | 'a_venir' }[];
    message: string | null;
    documents: { total: number; valides: number };
    proposition: { chambre: string; limite: string | null } | null;
    decision: Proposition | null;
    actions: Action[];
    transitionUrl: string;
    historique: { id: number; date: string | null; etat: string; auteur: string; motif: string | null }[];
};

type Sortie = {
    enCours: {
        id: string;
        chambre: string | null;
        residence: string | null;
        date: string | null;
        motif: string;
        deposeeLe: string | null;
        approuvee: boolean;
        decideeLe: string | null;
    } | null;
    historique: { id: string; chambre: string | null; date: string | null; statut: { libelle: string; ton: Ton }; motifDecision: string | null }[];
    formulaire: FormulaireSortie | null;
};

type MesDemandesProps = {
    dossier: Dossier | null;
    autres: { id: string; annee: string | null; reference: string; soumiseLe: string | null; etat: Etat; moyenne: string | number | null; credits: string | number | null }[];
    sortie: Sortie;
    focusSortie: boolean;
    liens: { readmission: string; entree: string };
};

/** Page « Mes demandes » : onglets « Mon dossier » (suivi + demandes précédentes) et « Sortie de chambre ». */
export default function MesDemandes({ dossier, autres, sortie, focusSortie, liens }: MesDemandesProps) {
    // « Quitter ma chambre » (focus=departure) ou ?onglet=sortie ouvrent directement l'onglet Sortie.
    const [actif, setActif] = useState<Onglet>(() =>
        focusSortie || (typeof window !== 'undefined' && new URL(window.location.href).searchParams.get('onglet') === 'sortie') ? 'sortie' : 'dossier',
    );

    // L'onglet ouvert reste dans l'URL (?onglet=…) pour le partage et le retour arrière.
    const changerOnglet = (valeur: string) => {
        setActif(valeur as Onglet);
        const url = new URL(window.location.href);
        url.searchParams.set('onglet', valeur);
        url.searchParams.delete('focus');
        window.history.replaceState(window.history.state, '', url);
    };

    const docs = dossier?.documents;

    return (
        <EtudiantLayout title="Demandes">
            <header className="mb-5">
                <h1 className="page-title text-xl font-medium text-gray-900">Mes demandes</h1>
                <p className="mt-1 text-sm text-muted">Suivez votre dossier d’hébergement, répondez à la proposition de chambre et demandez votre sortie.</p>
            </header>

            <div className="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard
                    label="Dossier en cours"
                    value={dossier ? dossier.etat.libelle : 'Aucun'}
                    tone="green"
                    extra={dossier?.decision ? 'Réponse attendue' : undefined}
                    highlight={!!dossier?.decision}
                />
                <StatCard
                    label="Documents validés"
                    value={docs?.total ? `${docs.valides}/${docs.total}` : '—'}
                    tone="blue"
                    extra={docs?.total ? `${Math.round((docs.valides / docs.total) * 100)} %` : undefined}
                />
                <StatCard label="Demandes précédentes" value={autres.length} tone="plum" />
                <StatCard
                    label="Sortie de chambre"
                    value={sortie.enCours ? (sortie.enCours.approuvee ? 'Approuvée' : 'En attente') : 'Aucune'}
                    tone="amber"
                />
            </div>

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5">
                <Tabs value={actif} onValueChange={changerOnglet}>
                    <TabsList aria-label="Rubriques des demandes" className="bg-white px-1">
                        <TabsTrigger value="dossier">
                            <FolderOpen {...ICON} />
                            Mon dossier
                        </TabsTrigger>
                        <TabsTrigger value="sortie">
                            <DoorOpen {...ICON} />
                            Sortie de chambre
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="dossier" className="flex flex-col gap-5">
                        <Panneau surtitre="Hébergement" titre="Mon dossier" badge={dossier?.miseAJour ? `Mis à jour ${dossier.miseAJour}` : undefined}>
                            {dossier ? <SuiviDossier dossier={dossier} /> : (
                                <>
                                    <Vide icone={Inbox}>Vous n’avez pas de demande d’hébergement.</Vide>
                                    <a href={liens.readmission} className={cn(buttonVariants({ variant: 'secondary' }), 'mt-4 w-full font-medium sm:w-fit')}>
                                        <FilePlus {...ICON} />
                                        Faire une demande de réadmission
                                    </a>
                                </>
                            )}
                            {dossier && ['attribue_definitif', 'accepte'].includes(dossier.etat.code) && (
                                <Liste className="mt-3">
                                    <Ligne href={liens.entree} icone={KeyRound} ton="vert" titre="Mon entrée dans les lieux" detail="Contrat, rendez-vous d’entrée et notifications" fin={ChevronRight} />
                                </Liste>
                            )}
                            {dossier && ['refuse', 'annule', 'expire', 'cloture'].includes(dossier.etat.code) && (
                                <Liste className="mt-3">
                                    <Ligne href={liens.readmission} icone={FilePlus} titre="Faire une nouvelle demande" detail="Réadmission avec votre compte existant" fin={ChevronRight} />
                                </Liste>
                            )}
                        </Panneau>

                        {autres.length > 0 && (
                            <Panneau surtitre="Historique" titre="Demandes précédentes" badge={autres.length}>
                                <ul className="-mx-2 divide-y">
                                    {autres.map((demande) => (
                                        <li key={demande.id} className="flex items-start gap-3 px-2 py-3">
                                            <span className="grid size-10 shrink-0 place-items-center rounded-brand bg-surface text-muted">
                                                <BedDouble {...ICON} />
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center justify-between gap-2">
                                                    <strong className="text-sm font-medium text-gray-900">Demande d’hébergement {demande.annee ? `· ${demande.annee}` : ''}</strong>
                                                    <Badge variant={demande.etat.ton}>{demande.etat.libelle}</Badge>
                                                </div>
                                                <p className="mt-0.5 text-xs text-muted">
                                                    {[`Réf. ${demande.reference}`, demande.soumiseLe && `soumise le ${demande.soumiseLe}`, demande.moyenne != null && `moyenne ${demande.moyenne}/20`, demande.credits != null && `${demande.credits} crédits`]
                                                        .filter(Boolean)
                                                        .join(' · ')}
                                                </p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            </Panneau>
                        )}
                    </TabsContent>

                    <TabsContent value="sortie">
                        <Panneau surtitre="Départ" titre="Sortie de chambre">
                            <SortieChambre sortie={sortie} />
                        </Panneau>
                    </TabsContent>
                </Tabs>
            </div>
        </EtudiantLayout>
    );
}

function SuiviDossier({ dossier }: { dossier: Dossier }) {
    const [action, setAction] = useState<Action | null>(null);
    const [toutHistorique, setToutHistorique] = useState(false);
    const { total, valides } = dossier.documents;
    const historique = toutHistorique ? dossier.historique : dossier.historique.slice(0, 3);

    return (
        <div className="flex flex-col gap-4">
            <div className="rounded-brand border p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <div className="min-w-0">
                        <p className="text-xs text-muted">Année académique</p>
                        <p className="text-base font-medium text-gray-900">{dossier.annee ?? 'Non renseignée'}</p>
                    </div>
                    <Badge variant={dossier.etat.ton}>{dossier.etat.libelle}</Badge>
                </div>
                <dl className="mt-3 grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-4">
                    <Info libelle="Référence" valeur={dossier.reference} />
                    <Info libelle="Soumise le" valeur={dossier.soumiseLe} />
                    <Info libelle="Date limite de dépôt" valeur={dossier.echeance} />
                    <Info libelle="Moyenne" valeur={dossier.moyenne != null ? `${dossier.moyenne}/20` : null} />
                </dl>
                {total > 0 && (
                    <div className="mt-4">
                        <div className="mb-1.5 flex justify-between text-xs text-muted">
                            <span>Documents validés</span>
                            <span className="font-medium text-gray-900 tabular-nums">
                                {valides}/{total}
                            </span>
                        </div>
                        <Progress value={(valides / total) * 100} tone={valides === total ? 'primary' : 'accent'} label="Documents validés" />
                    </div>
                )}
            </div>

            <Frise etapes={dossier.etapes} />

            {dossier.message && <Notice icone={InfoIcone}>{dossier.message}</Notice>}

            {dossier.decision?.type === 'consentement' && (
                <div className="rounded-brand border border-accent/50 bg-accent/10 p-4">
                    <p className="mb-3 text-sm text-gray-900">Votre dossier est éligible. Souhaitez-vous toujours être logé(e) cette année ?</p>
                    <ReponseProposition proposition={dossier.decision} />
                </div>
            )}

            {dossier.proposition && (
                <div className={cn('rounded-brand border p-4', dossier.decision ? 'border-accent/50 bg-accent/10' : 'bg-white')}>
                    <div className="flex items-start gap-3">
                        <span className="grid size-10 shrink-0 place-items-center rounded-brand bg-white text-amber-800">
                            <BedDouble {...ICON} />
                        </span>
                        <div className="min-w-0">
                            <p className="text-xs text-muted">Chambre proposée</p>
                            <p className="text-sm font-medium text-gray-900">{dossier.proposition.chambre}</p>
                            {dossier.proposition.limite && <p className="text-xs text-amber-800">Répondez avant le {dossier.proposition.limite}</p>}
                        </div>
                    </div>
                    {dossier.decision && (
                        <div className="mt-3">
                            <ReponseProposition proposition={dossier.decision} />
                        </div>
                    )}
                </div>
            )}

            {dossier.actions.length > 0 && (
                <div className="flex flex-col gap-2 sm:flex-row">
                    {dossier.actions.map((item) => (
                        <Button
                            key={item.etat}
                            variant={item.etat === 'soumis' ? 'primary' : 'outline'}
                            onClick={() => setAction(item)}
                            className={cn('font-medium', item.etat === 'annule' && 'text-red-700 hover:bg-red-50')}
                        >
                            {item.etat === 'soumis' ? <Send {...ICON} /> : <XCircle {...ICON} />}
                            {item.libelle}
                        </Button>
                    ))}
                </div>
            )}

            <div>
                <h3 className="mb-1 flex items-center gap-2 text-sm font-medium text-gray-900">
                    <History {...ICON} className="text-muted" />
                    Historique du dossier
                </h3>
                {dossier.historique.length ? (
                    <>
                        <ol className="flex flex-col gap-3 border-l border-gray-200 py-1 pl-4">
                            {historique.map((item) => (
                                <li key={item.id} className="relative">
                                    <span aria-hidden className="absolute top-1.5 -left-[21px] size-2.5 rounded-full border-2 border-white bg-secondary" />
                                    <p className="text-sm text-gray-900">
                                        <span className="font-medium">{item.etat}</span>
                                        <span className="text-muted"> · {item.auteur}</span>
                                    </p>
                                    <p className="text-xs text-muted tabular-nums">{item.date}</p>
                                    {item.motif && <p className="mt-1 rounded-brand bg-surface p-2 text-sm break-words whitespace-pre-line text-gray-900">{item.motif}</p>}
                                </li>
                            ))}
                        </ol>
                        {dossier.historique.length > 3 && (
                            <Button variant="ghost" size="sm" onClick={() => setToutHistorique((v) => !v)} className="mt-1 font-medium">
                                {toutHistorique ? 'Afficher moins' : `Afficher tout l’historique (${dossier.historique.length})`}
                            </Button>
                        )}
                    </>
                ) : (
                    <p className="text-sm text-muted">Aucune étape enregistrée pour le moment.</p>
                )}
            </div>

            <TransitionDialog dossier={dossier} action={action} fermer={() => setAction(null)} />
        </div>
    );
}

/** Frise du parcours : verticale sur mobile, horizontale sur écran large. */
function Frise({ etapes }: { etapes: Dossier['etapes'] }) {
    return (
        <ol aria-label="Étapes du dossier" className="grid gap-2 sm:grid-cols-6 sm:gap-1">
            {etapes.map((etape) => (
                <li key={etape.code} className="flex items-center gap-2.5 sm:flex-col sm:items-start sm:gap-1.5">
                    <span
                        className={cn(
                            'grid size-6 shrink-0 place-items-center rounded-full border text-xs sm:h-1.5 sm:w-full sm:rounded-sm sm:border-0',
                            etape.statut === 'fait' && 'border-secondary bg-soft text-primary sm:bg-secondary',
                            etape.statut === 'en_cours' && 'border-accent bg-accent/15 text-amber-800 sm:bg-accent',
                            etape.statut === 'a_venir' && 'border-gray-300 bg-white text-gray-400 sm:bg-gray-200',
                        )}
                    >
                        <span className="sm:hidden">{etape.statut === 'fait' ? <Check className="size-3.5" aria-hidden /> : etape.statut === 'en_cours' ? <CircleDot className="size-3.5" aria-hidden /> : null}</span>
                    </span>
                    <span className="min-w-0">
                        <span className={cn('block text-sm sm:text-xs', etape.statut === 'a_venir' ? 'text-muted' : 'font-medium text-gray-900')}>
                            {etape.libelle}
                            <span className="sr-only">{etape.statut === 'fait' ? ' (franchie)' : etape.statut === 'en_cours' ? ' (en cours)' : ' (à venir)'}</span>
                        </span>
                        {etape.date && <span className="block text-xs text-muted tabular-nums">{etape.date}</span>}
                    </span>
                </li>
            ))}
        </ol>
    );
}

function TransitionDialog({ dossier, action, fermer }: { dossier: Dossier; action: Action | null; fermer: () => void }) {
    const [envoi, setEnvoi] = useState(false);

    return (
        <Dialog open={!!action} onOpenChange={(valeur) => !valeur && !envoi && fermer()}>
            <DialogContent className="max-w-lg">
                {/* Un formulaire par action : le schéma (motif obligatoire ou non) suit l'action choisie. */}
                {action && <FormulaireTransition key={action.etat} url={dossier.transitionUrl} action={action} fermer={fermer} onEnvoi={setEnvoi} />}
            </DialogContent>
        </Dialog>
    );
}

function FormulaireTransition({ url, action, fermer, onEnvoi }: { url: string; action: Action; fermer: () => void; onEnvoi: (envoi: boolean) => void }) {
    const annulation = action.etat === 'annule';
    const {
        register,
        handleSubmit,
        setError,
        watch,
        formState: { errors, isSubmitting },
    } = useForm<Transition>({ resolver: zodResolver(transitionSchema(action.motifObligatoire)), defaultValues: { motif: '' } });
    const longueur = watch('motif').length;

    useEffect(() => onEnvoi(isSubmitting), [isSubmitting, onEnvoi]);

    const valider = handleSubmit(async ({ motif }) => {
        const ok = await envoyer<Transition>(url, { etat: action.etat, motif }, { setError, champs: ['motif'] });
        if (ok) fermer();
    });

    return (
        <form onSubmit={valider} noValidate className="flex min-h-0 flex-col">
            <DialogHeader>
                <DialogTitle>{annulation ? 'Annuler mon dossier' : 'Soumettre mon dossier'}</DialogTitle>
                <DialogDescription>
                    {annulation
                        ? 'Votre dossier ne sera plus étudié pour cette campagne. Cette action est définitive.'
                        : 'Votre dossier sera transmis au service Logement pour étude.'}
                </DialogDescription>
            </DialogHeader>
            <DialogBody>
                <Field
                    id="transition-motif"
                    label={annulation ? 'Motif de l’annulation' : 'Message au service Logement'}
                    required={action.motifObligatoire}
                    error={errors.motif?.message}
                    hint={`${action.motifObligatoire ? '' : 'Facultatif · '}${longueur} / ${MAX_MOTIF_TRANSITION}`}
                >
                    <Textarea
                        id="transition-motif"
                        rows={4}
                        maxLength={MAX_MOTIF_TRANSITION}
                        placeholder={annulation ? 'Expliquez pourquoi vous annulez votre dossier…' : 'Ex. : pièces complétées comme demandé.'}
                        invalid={!!errors.motif}
                        {...register('motif')}
                    />
                </Field>
            </DialogBody>
            <DialogFooter>
                <Button variant="outline" onClick={fermer} disabled={isSubmitting}>
                    Retour
                </Button>
                <Button type="submit" disabled={isSubmitting} className={cn('font-medium', annulation && 'bg-red-700 hover:bg-red-800')}>
                    {isSubmitting ? <Loader2 {...ICON} className="animate-spin" /> : annulation ? <XCircle {...ICON} /> : <Send {...ICON} />}
                    {annulation ? 'Confirmer l’annulation' : 'Soumettre'}
                </Button>
            </DialogFooter>
        </form>
    );
}

function SortieChambre({ sortie }: { sortie: Sortie }) {
    const { enCours, formulaire, historique } = sortie;

    return (
        <div className="flex flex-col gap-4">
            {enCours && (
                <div className="rounded-brand border p-4">
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <div className="min-w-0">
                            <p className="text-xs text-muted">Demande en cours</p>
                            <p className="text-base font-medium text-gray-900">
                                Chambre {enCours.chambre ?? '—'}
                                {enCours.residence && <span className="text-sm font-normal text-muted"> · {enCours.residence}</span>}
                            </p>
                        </div>
                        <Badge variant={enCours.approuvee ? 'success' : 'warning'}>{enCours.approuvee ? 'Approuvée' : 'En attente'}</Badge>
                    </div>
                    <dl className="mt-3 grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-3">
                        <Info libelle="Sortie souhaitée" valeur={enCours.date} />
                        <Info libelle="Motif" valeur={enCours.motif} />
                        <Info libelle="Référence" valeur={enCours.id} />
                    </dl>
                    <ol className="mt-4 flex flex-col gap-3 border-l border-gray-200 py-1 pl-4">
                        <EtapeSortie fait titre="Demande déposée" detail={enCours.deposeeLe} />
                        <EtapeSortie fait={enCours.approuvee} enCours={!enCours.approuvee} titre={enCours.approuvee ? 'Demande approuvée' : 'En attente de validation'} detail={enCours.decideeLe ?? 'En cours'} />
                        <EtapeSortie enCours={enCours.approuvee} titre="État des lieux et clôture" detail={enCours.approuvee ? 'À réaliser avec le service Logement' : 'En attente'} />
                    </ol>
                </div>
            )}

            {formulaire && <DemandeSortie formulaire={formulaire} />}

            {!enCours && !formulaire && (
                <Vide icone={DoorOpen}>Une chambre active est nécessaire pour déposer une demande de sortie.</Vide>
            )}

            {historique.length > 0 && (
                <div>
                    <h3 className="mb-1 text-sm font-medium text-gray-900">Sorties précédentes</h3>
                    <ul className="-mx-2 divide-y">
                        {historique.map((item) => (
                            <li key={item.id} className="flex items-start gap-3 px-2 py-2.5">
                                <span className="grid size-10 shrink-0 place-items-center rounded-brand bg-surface text-muted">
                                    <FilePen {...ICON} />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <strong className="text-sm font-medium text-gray-900">Chambre {item.chambre ?? '—'}</strong>
                                        <Badge variant={item.statut.ton}>{item.statut.libelle}</Badge>
                                    </div>
                                    <p className="text-xs text-muted">Sortie prévue le {item.date ?? '—'}</p>
                                    {item.motifDecision && <p className="mt-1 text-xs text-red-700">Motif : {item.motifDecision}</p>}
                                </div>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}

function EtapeSortie({ titre, detail, fait, enCours }: { titre: string; detail: string | null; fait?: boolean; enCours?: boolean }) {
    return (
        <li className="relative">
            <span
                aria-hidden
                className={cn('absolute top-1.5 -left-[21px] size-2.5 rounded-full border-2 border-white', fait ? 'bg-secondary' : enCours ? 'bg-accent' : 'bg-gray-300')}
            />
            <p className={cn('text-sm', fait || enCours ? 'font-medium text-gray-900' : 'text-muted')}>{titre}</p>
            {detail && <p className="text-xs text-muted">{detail}</p>}
        </li>
    );
}

function Info({ libelle, valeur }: { libelle: string; valeur: string | null | undefined }) {
    return (
        <div className="min-w-0">
            <dt className="text-xs text-muted">{libelle}</dt>
            <dd className={cn('truncate', valeur ? 'text-gray-900' : 'text-gray-400')}>{valeur || 'Non renseigné'}</dd>
        </div>
    );
}
