import { Link } from '@inertiajs/react';
import {
    Accessibility,
    ArrowLeft,
    BedDouble,
    ChartColumn,
    CircleCheck,
    DoorOpen,
    Download,
    Hourglass,
    Plus,
    Search,
    UserCheck,
    UserX,
    X,
    Zap,
} from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import { ClassementAttribution, StatutCampagne, lienContour, pourcent, type CampagneAllocation, type Classement, type StatsAllocation } from '@/components/allocations/shared';
import type { DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { SimpleTable } from '@/components/data-table/simple-table';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { montant } from '@/lib/systeme';
import { dateFr, initiales, nombre, sansAccents } from '@/lib/text';
import { cn } from '@/lib/utils';

type Assignation = {
    id: string;
    statut: string;
    etat_dossier: string | null;
    etat_label: string;
    etudiant: string | null;
    matricule: string | null;
    niveau: string | null;
    chambre: string | null;
    lit: number | string | null;
    batiment: string | null;
    type: string;
    echeance: string | null;
    confirmable: boolean;
    annulable: boolean;
};

type EtudiantSansChambre = { id: string; name: string; matricule: string | null; email: string | null; niveau: string | null; pmr: boolean };

type ChambreDisponible = {
    id: string;
    numero: string;
    type: string;
    lieu: string;
    capacite: number;
    libres: number;
    occupation: number;
    prix: number;
    pmr: boolean;
};

type Props = {
    campagne: CampagneAllocation;
    stats: StatsAllocation | null;
    assignations: Assignation[];
    etudiants: EtudiantSansChambre[];
    chambres: ChambreDisponible[];
    classement: Classement | null;
    urls: {
        index: string | null;
        statistics: string | null;
        export: string | null;
        allocateAll: string | null;
        allocateForm: string | null;
        confirm: string | null;
        cancel: string | null;
    };
};

type Onglet = 'assignations' | 'etudiants' | 'chambres';
const ONGLETS: Onglet[] = ['assignations', 'etudiants', 'chambres'];

/** Libellé court d'une attribution (« Chambre 12 · lit 2 »). */
const nomChambre = (assignation: Assignation) => `Chambre ${assignation.chambre ?? 'N/D'}${assignation.lit ? ` · lit ${assignation.lit}` : ''}`;

/** Recherche locale sans accents sur les champs donnés. */
function useRecherche<T>(lignes: T[], texte: (ligne: T) => string) {
    const [terme, setTerme] = useState('');
    const filtrees = useMemo(() => {
        const cherche = sansAccents(terme.trim());
        return cherche ? lignes.filter((ligne) => sansAccents(texte(ligne)).includes(cherche)) : lignes;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [lignes, terme]);
    return { terme, setTerme, filtrees };
}

/** Suivi d'une campagne d'attribution (remplace `allocation_show.blade.php`, `ranking-summary.blade.php` et `allocation-chambres.js`). */
export default function AllocationShow({ campagne, stats, assignations, etudiants, chambres, classement, urls }: Props) {
    const [onglet, setOnglet] = useState<Onglet>(() => {
        const demande = new URLSearchParams(window.location.search).get('onglet') as Onglet | null;
        return demande && ONGLETS.includes(demande) ? demande : 'assignations';
    });
    const [auto, setAuto] = useState(false);
    const [aConfirmer, setAConfirmer] = useState<Assignation | null>(null);
    const [aAnnuler, setAAnnuler] = useState<Assignation | null>(null);
    const [motif, setMotif] = useState('');
    const [enCours, setEnCours] = useState(false);

    const rechercheAssignations = useRecherche(assignations, (a) => `${a.etudiant} ${a.matricule} ${a.chambre} ${a.batiment} ${a.etat_label}`);
    const rechercheEtudiants = useRecherche(etudiants, (e) => `${e.name} ${e.matricule} ${e.email}`);
    const rechercheChambres = useRecherche(chambres, (c) => `${c.numero} ${c.type} ${c.lieu}`);

    // L'onglet ouvert est conservé dans l'URL (?onglet=), sans recharger la page.
    const changerOnglet = (valeur: string) => {
        setOnglet(valeur as Onglet);
        const url = new URL(window.location.href);
        url.searchParams.set('onglet', valeur);
        window.history.replaceState(window.history.state, '', url);
    };

    const action = async (url: string | null, data: Record<string, string>, fermer: () => void) => {
        if (!url) return;
        setEnCours(true);
        await envoyer(url, data, { onSuccess: fermer });
        setEnCours(false);
    };

    const colonnesAssignations: DataTableColumn<Assignation>[] = [
        { key: 'etudiant', header: 'Étudiant', cell: (a) => <Personne nom={a.etudiant ?? 'Étudiant introuvable'} matricule={a.matricule ?? 'N/D'} /> },
        { key: 'niveau', header: 'Niveau', cell: (a) => a.niveau ?? '—' },
        {
            key: 'chambre',
            header: 'Chambre',
            cell: (a) => (
                <div>
                    <p className="font-medium text-gray-900">{a.chambre ?? 'N/D'}</p>
                    <p className="text-xs text-muted">
                        {a.lit ? `Lit ${a.lit}` : 'Lit non renseigné'}
                        {a.batiment && ` · ${a.batiment}`}
                    </p>
                </div>
            ),
        },
        { key: 'type', header: 'Type', cell: (a) => <Badge variant="info">{a.type}</Badge> },
        {
            key: 'etat',
            header: 'État du dossier',
            cell: (a) => (
                <div>
                    <Badge variant={a.statut === 'confirmee' ? 'success' : a.statut === 'en_attente' ? 'warning' : 'danger'}>
                        {a.statut === 'confirmee' ? <CircleCheck className="size-3.5" aria-hidden /> : a.statut === 'en_attente' ? <Hourglass className="size-3.5" aria-hidden /> : <X className="size-3.5" aria-hidden />}
                        {a.etat_label}
                    </Badge>
                    {a.echeance && (
                        <p className="mt-1 text-xs text-muted">
                            Réponse attendue avant le {dateFr(a.echeance)} à {a.echeance.slice(11, 16)}
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (a) => (
                <RowActions>
                    {a.confirmable && urls.confirm && <RowAction label="Valider définitivement" icon={UserCheck} tone="success" onClick={() => setAConfirmer(a)} />}
                    {a.annulable && urls.cancel && (
                        <RowAction
                            label="Annuler la proposition"
                            icon={X}
                            tone="danger"
                            onClick={() => {
                                setMotif('');
                                setAAnnuler(a);
                            }}
                        />
                    )}
                </RowActions>
            ),
        },
    ];

    const colonnesEtudiants: DataTableColumn<EtudiantSansChambre>[] = [
        { key: 'etudiant', header: 'Étudiant', cell: (e) => <Personne nom={e.name} matricule={e.matricule ?? ''} /> },
        { key: 'email', header: 'E-mail', cell: (e) => e.email || '—' },
        { key: 'niveau', header: 'Niveau', cell: (e) => e.niveau ?? '—' },
        {
            key: 'pmr',
            header: 'PMR',
            cell: (e) =>
                e.pmr ? (
                    <Badge variant="info">
                        <Accessibility className="size-3.5" aria-hidden /> Confirmé
                    </Badge>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (e) =>
                urls.allocateForm && (
                    <Link
                        href={`${urls.allocateForm}?etudiant_id=${encodeURIComponent(e.id)}`}
                        className={cn(buttonVariants({ variant: 'outline', size: 'sm' }))}
                        aria-label={`Attribuer une chambre à ${e.name}`}
                    >
                        <BedDouble className="size-4" aria-hidden /> Attribuer
                    </Link>
                ),
        },
    ];

    return (
        <AdminLayout title={`Campagne ${campagne.annee}`}>
            <PageHeader
                title={`Campagne ${campagne.annee}`}
                description={
                    <span className="flex flex-wrap items-center gap-2">
                        <StatutCampagne campagne={campagne} />
                        {campagne.description || 'Suivi des propositions et des attributions de chambres de la campagne.'}
                    </span>
                }
                actions={
                    <>
                        {urls.index && (
                            <Link href={urls.index} className={lienContour}>
                                <ArrowLeft {...ICON} aria-hidden /> Allocations
                            </Link>
                        )}
                        {urls.statistics && (
                            <Link href={urls.statistics} className={lienContour}>
                                <ChartColumn {...ICON} aria-hidden /> Statistiques
                            </Link>
                        )}
                        {urls.export && (
                            <a href={urls.export} className={lienContour}>
                                <Download {...ICON} aria-hidden /> Exporter (CSV)
                            </a>
                        )}
                        {urls.allocateAll && (
                            <Button
                                variant="secondary"
                                size="sm"
                                className="h-10 px-4"
                                disabled={etudiants.length === 0}
                                title={etudiants.length === 0 ? 'Aucun étudiant éligible sans chambre' : undefined}
                                onClick={() => setAuto(true)}
                            >
                                <Zap {...ICON} aria-hidden /> Attribution automatique
                            </Button>
                        )}
                        {urls.allocateForm && (
                            <Link href={urls.allocateForm} className={cn(buttonVariants({ size: 'sm' }), 'h-10 px-4')}>
                                <Plus {...ICON} aria-hidden /> Attribuer une chambre
                            </Link>
                        )}
                    </>
                }
            />

            <KpiFilters
                label="Indicateurs de la campagne"
                items={[
                    { key: 'sans', label: 'Éligibles sans chambre', value: nombre(etudiants.length), tone: 'blue' },
                    { key: 'attente', label: 'Propositions en attente', value: nombre(stats?.assignations_en_attente ?? 0), tone: 'amber' },
                    { key: 'confirmees', label: 'Attributions confirmées', value: nombre(stats?.assignations_confirmees ?? 0), tone: 'green' },
                    { key: 'chambres', label: 'Chambres avec place libre', value: nombre(chambres.length), tone: 'plum' },
                ]}
            />

            <ClassementAttribution classement={classement} />

            {stats && (
                <section className="mt-6 grid gap-6 rounded-brand border bg-white p-4 lg:grid-cols-2">
                    <div>
                        <p className="text-xs font-medium text-muted">Demandes attribuées définitivement</p>
                        <p className="mt-1 flex flex-wrap items-baseline gap-2">
                            <strong className="text-2xl font-medium tabular-nums">{pourcent(stats.taux_allocation)}</strong>
                            <span className="text-sm text-muted">{nombre(stats.demandes_acceptees)} demande(s) au statut « Acceptée »</span>
                        </p>
                        <Progress value={stats.taux_allocation} className="mt-2.5" />
                    </div>
                    {stats.par_type.length > 0 && (
                        <dl className="divide-y text-sm">
                            {stats.par_type.map((type) => (
                                <div key={type.nom} className="flex justify-between gap-4 py-1.5">
                                    <dt className="text-muted">Chambres {type.nom}</dt>
                                    <dd className="text-right">
                                        <span className="font-medium">{nombre(type.occupees)} attribuée(s)</span>
                                        <span className="text-muted">
                                            {' '}
                                            · {nombre(type.disponibles)} disponible(s) / {nombre(type.total)}
                                        </span>
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    )}
                </section>
            )}

            <Tabs value={onglet} onValueChange={changerOnglet} className="mt-6 overflow-hidden rounded-brand border bg-white">
                <TabsList aria-label="Suivi de la campagne" className="px-4">
                    <TabsTrigger value="assignations">
                        <BedDouble className="size-4" aria-hidden /> Attributions <Compteur valeur={assignations.length} />
                    </TabsTrigger>
                    <TabsTrigger value="etudiants">
                        <UserX className="size-4" aria-hidden /> Éligibles sans chambre <Compteur valeur={etudiants.length} />
                    </TabsTrigger>
                    <TabsTrigger value="chambres">
                        <DoorOpen className="size-4" aria-hidden /> Chambres disponibles <Compteur valeur={chambres.length} />
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="assignations">
                    {assignations.length === 0 ? (
                        <Vide icone={<BedDouble {...ICON} />}>Aucune attribution pour le moment.</Vide>
                    ) : (
                        <>
                            <BarreRecherche {...rechercheAssignations} total={assignations.length} placeholder="Nom, matricule, chambre…" />
                            <SimpleTable
                                columns={colonnesAssignations}
                                rows={rechercheAssignations.filtrees}
                                rowKey={(a) => a.id}
                                emptyMessage="Aucune attribution ne correspond à cette recherche."
                            />
                        </>
                    )}
                </TabsContent>

                <TabsContent value="etudiants">
                    {etudiants.length === 0 ? (
                        <Vide icone={<CircleCheck {...ICON} />}>Tous les dossiers éligibles ont reçu une proposition de chambre.</Vide>
                    ) : (
                        <>
                            <BarreRecherche {...rechercheEtudiants} total={etudiants.length} placeholder="Nom, matricule, e-mail…" />
                            <SimpleTable columns={colonnesEtudiants} rows={rechercheEtudiants.filtrees} rowKey={(e) => e.id} emptyMessage="Aucun étudiant ne correspond à cette recherche." />
                        </>
                    )}
                </TabsContent>

                <TabsContent value="chambres">
                    {chambres.length === 0 ? (
                        <Vide icone={<DoorOpen {...ICON} />}>Aucune chambre disponible dans les résidences de cette campagne.</Vide>
                    ) : (
                        <>
                            <BarreRecherche {...rechercheChambres} total={chambres.length} placeholder="Numéro, type, bâtiment, cité…" />
                            {rechercheChambres.filtrees.length === 0 ? (
                                <Vide icone={<Search {...ICON} />}>Aucune chambre ne correspond à cette recherche.</Vide>
                            ) : (
                                <div className="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
                                    {rechercheChambres.filtrees.map((chambre) => (
                                        <TuileChambre key={chambre.id} chambre={chambre} />
                                    ))}
                                </div>
                            )}
                        </>
                    )}
                </TabsContent>
            </Tabs>

            <ConfirmDialog
                open={auto}
                onOpenChange={setAuto}
                title="Attribution automatique"
                description={
                    <>
                        <p>
                            Une proposition de chambre sera faite à chacun des <strong className="font-medium text-gray-900">{nombre(etudiants.length)}</strong> dossier(s) éligible(s)
                            sans chambre, dans l’ordre du classement, tant qu’il reste des places compatibles.
                        </p>
                        <p className="mt-2 text-xs">Les étudiants devront accepter leur proposition avant la validation définitive.</p>
                    </>
                }
                confirmLabel="Lancer l’attribution"
                confirmIcon={<Zap className="size-4" aria-hidden />}
                processing={enCours}
                onConfirm={() => action(urls.allocateAll, {}, () => setAuto(false))}
            />
            <ConfirmDialog
                open={aConfirmer !== null}
                onOpenChange={(open) => !open && setAConfirmer(null)}
                title="Valider définitivement"
                description={
                    <>
                        <p>
                            <strong className="font-medium text-gray-900">{aConfirmer?.etudiant}</strong> a accepté la proposition :{' '}
                            <strong className="font-medium text-gray-900">{aConfirmer && nomChambre(aConfirmer)}</strong>.
                        </p>
                        <p className="mt-2 text-xs">L’attribution devient définitive et le dossier passe à l’état « Attribué définitif ».</p>
                    </>
                }
                confirmLabel="Valider"
                confirmIcon={<UserCheck className="size-4" aria-hidden />}
                processing={enCours}
                onConfirm={() => action(urls.confirm, { assignation_id: aConfirmer!.id }, () => setAConfirmer(null))}
            />
            <ConfirmDialog
                open={aAnnuler !== null}
                onOpenChange={(open) => !open && setAAnnuler(null)}
                title="Annuler la proposition"
                description={
                    <p>
                        La proposition « <strong className="font-medium text-gray-900">{aAnnuler && nomChambre(aAnnuler)}</strong> » faite à{' '}
                        <strong className="font-medium text-gray-900">{aAnnuler?.etudiant}</strong> sera annulée et la place libérée.
                    </p>
                }
                confirmLabel="Annuler la proposition"
                confirmIcon={<X className="size-4" aria-hidden />}
                danger
                processing={enCours}
                onConfirm={() => action(urls.cancel, { assignation_id: aAnnuler!.id, motif: motif.trim() }, () => setAAnnuler(null))}
            >
                <Field id="al-motif" label="Motif (facultatif)" hint="255 caractères au maximum.">
                    <Textarea id="al-motif" rows={3} maxLength={255} value={motif} onChange={(event) => setMotif(event.target.value)} placeholder="Ex. : erreur de chambre, demande de l’étudiant…" />
                </Field>
            </ConfirmDialog>
        </AdminLayout>
    );
}

function Compteur({ valeur }: { valeur: number }) {
    return <span className="rounded-full bg-surface px-2 py-0.5 text-xs font-medium text-muted tabular-nums">{nombre(valeur)}</span>;
}

function Personne({ nom, matricule }: { nom: string; matricule: string }) {
    return (
        <div className="flex min-w-44 items-center gap-3">
            <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-soft text-xs font-medium text-primary" aria-hidden>
                {initiales(nom)}
            </span>
            <div className="min-w-0">
                <p className="font-medium text-gray-900">{nom}</p>
                <p className="font-mono text-xs text-muted">{matricule}</p>
            </div>
        </div>
    );
}

function BarreRecherche({ terme, setTerme, filtrees, total, placeholder }: { terme: string; setTerme: (t: string) => void; filtrees: unknown[]; total: number; placeholder: string }) {
    return (
        <div className="flex flex-col gap-2 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="w-full sm:max-w-xs">
                <Input type="search" value={terme} onChange={(event) => setTerme(event.target.value)} placeholder={placeholder} aria-label={placeholder} icon={<Search />} className="h-10" />
            </div>
            {terme.trim() !== '' && (
                <span className="text-sm text-muted">
                    {filtrees.length} résultat(s) sur {total}
                </span>
            )}
        </div>
    );
}

function Vide({ icone, children }: { icone: ReactNode; children: ReactNode }) {
    return (
        <div className="flex flex-col items-center gap-2 px-4 py-12 text-center text-sm text-muted">
            <span aria-hidden>{icone}</span>
            {children}
        </div>
    );
}

function TuileChambre({ chambre }: { chambre: ChambreDisponible }) {
    return (
        <article className="flex flex-col gap-3 rounded-brand border p-4">
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <h3 className="font-medium text-gray-900">Chambre {chambre.numero}</h3>
                    <p className="truncate text-xs text-muted">{chambre.lieu || '—'}</p>
                </div>
                <Badge variant="info">{chambre.type}</Badge>
            </div>
            <dl className="divide-y text-sm">
                <div className="flex justify-between py-1">
                    <dt className="text-muted">Places libres</dt>
                    <dd className="font-medium text-primary">
                        {chambre.libres} / {chambre.capacite}
                    </dd>
                </div>
                <div className="flex justify-between py-1">
                    <dt className="text-muted">Loyer</dt>
                    <dd className="font-medium">{montant(chambre.prix)}</dd>
                </div>
            </dl>
            <div>
                <div className="flex justify-between text-xs text-muted">
                    <span>Occupation</span>
                    <span>{pourcent(chambre.occupation)}</span>
                </div>
                <Progress value={chambre.occupation} className="mt-1" />
            </div>
            {chambre.pmr && (
                <Badge variant="info" className="bg-[#F8E4EF] text-[#6E1F4E]">
                    <Accessibility className="size-3.5" aria-hidden /> Adaptée PMR
                </Badge>
            )}
        </article>
    );
}
