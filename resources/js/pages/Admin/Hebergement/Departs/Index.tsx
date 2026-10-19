import { CalendarDays, Check, ClipboardCheck, Coins, Eye, FileDown, History, Inbox, ListChecks, LogOut, Plus, Search, SlidersHorizontal, X } from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import { CalendrierSorties } from '@/components/departs/calendrier-sorties';
import { ApprobationDialog, DetailDemandeDialog, NouvelleDemandeDialog, RefusDialog, RestitutionDialog, type ResidentSortie } from '@/components/departs/demande-dialogs';
import { EtatsDesLieuxPanel, type DonneesEtatsDesLieux } from '@/components/departs/etats-des-lieux-panel';
import {
    correspond,
    fcfa,
    joursAvant,
    PaginationLocale,
    STATUTS_DEMANDE,
    StatutDemandeBadge,
    usePaginationLocale,
    type CiteOption,
    type Demande,
    type Option,
    type StatutDemande,
} from '@/components/departs/shared';
import type { DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { SimpleTable } from '@/components/data-table/simple-table';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { StatCard, type KpiTone } from '@/components/ui/stat-card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';

type Inspection = {
    id: string;
    date: string | null;
    etudiant: string;
    matricule: string | null;
    cite: string | null;
    cite_id: string | null;
    batiment: string | null;
    chambre: string | null;
    type: 'entree' | 'sortie';
    observation: string | null;
    pdf: string | null;
};

type Props = {
    demandes: Demande[];
    inspections: Inspection[];
    residents: ResidentSortie[];
    cites: CiteOption[];
    motifs: Option[];
    modesRestitution: Option[];
    etatsDesLieux: DonneesEtatsDesLieux;
    urls: { store: string | null; parametres: string | null };
};

type Filtres = { recherche: string; statut: '' | StatutDemande; date: string; cite: string };

const TOUS = 'tous';
const FILTRES_VIDES: Filtres = { recherche: '', statut: '', date: '', cite: '' };

/** Départs : liste des états des lieux (affichée en premier), demandes de sortie, calendrier et historiques (remplace `depart-etat-des-lieux/index.blade.php` et ses modales). */
export default function DepartsIndex({ demandes, inspections, residents, cites, motifs, modesRestitution, etatsDesLieux, urls }: Props) {
    const [vue, setVue] = useState('etats');
    const [cibleSortie, setCibleSortie] = useState<string | null>(null);
    const [filtres, setFiltres] = useState<Filtres>(FILTRES_VIDES);
    const [onglet, setOnglet] = useState('demandes');
    const [nouvelle, setNouvelle] = useState(false);
    const [detail, setDetail] = useState<Demande | null>(null);
    const [approbation, setApprobation] = useState<Demande | null>(null);
    const [refus, setRefus] = useState<Demande | null>(null);
    const [restitution, setRestitution] = useState<Demande | null>(null);
    const filtrer = (suivants: Partial<Filtres>) => setFiltres((f) => ({ ...f, ...suivants }));
    const filtreActif = Object.values(filtres).some(Boolean);

    // Recherche, statut, date de sortie et cité communs aux demandes, au calendrier et aux historiques.
    const filtrees = useMemo(
        () =>
            demandes.filter(
                (d) =>
                    correspond(filtres.recherche, d.etudiant, d.matricule, d.chambre, d.batiment, d.cite) &&
                    (!filtres.statut || d.statut === filtres.statut) &&
                    (!filtres.date || d.date_sortie === filtres.date) &&
                    (!filtres.cite || d.cite_id === filtres.cite),
            ),
        [demandes, filtres],
    );
    const enCours = useMemo(() => filtrees.filter((d) => d.statut === 'en_attente' || d.statut === 'approuvee'), [filtrees]);
    const historique = useMemo(() => filtrees.filter((d) => d.statut === 'cloturee' || d.statut === 'refusee'), [filtrees]);
    const constats = useMemo(
        () =>
            inspections.filter(
                (i) =>
                    correspond(filtres.recherche, i.etudiant, i.matricule, i.chambre, i.batiment, i.cite) &&
                    (!filtres.date || i.date === filtres.date) &&
                    (!filtres.cite || i.cite_id === filtres.cite),
            ),
        [inspections, filtres],
    );

    const stats = useMemo(() => {
        const ouvertes = demandes.filter((d) => d.statut === 'en_attente' || d.statut === 'approuvee');
        return {
            ouvertes: ouvertes.length,
            aTraiter: ouvertes.filter((d) => d.statut === 'en_attente').length,
            approuvees: ouvertes.filter((d) => d.statut === 'approuvee').length,
            semaine: ouvertes.filter((d) => {
                const jours = joursAvant(d.date_sortie);
                return jours !== null && jours >= 0 && jours <= 7;
            }).length,
            cautions: demandes.filter((d) => d.urls.restitution || (d.decompte && d.decompte.caution_a_restituer > 0 && !d.decompte.date_restitution)).length,
        };
    }, [demandes]);

    const kpis: { cle: '' | StatutDemande; label: string; valeur: number; tone: KpiTone; extra?: string; highlight?: boolean }[] = [
        { cle: '', label: 'Demandes en cours', valeur: stats.ouvertes, tone: 'blue', extra: `${nombre(stats.semaine)} sous 7 jours`, highlight: stats.semaine > 0 },
        { cle: 'en_attente', label: 'À traiter', valeur: stats.aTraiter, tone: 'amber', extra: stats.aTraiter > 0 ? 'Décision attendue' : 'Aucune', highlight: stats.aTraiter > 0 },
        { cle: 'approuvee', label: 'Approuvées', valeur: stats.approuvees, tone: 'green', extra: 'État des lieux à faire' },
        { cle: 'cloturee', label: 'Cautions à restituer', valeur: stats.cautions, tone: 'plum', extra: 'Séjours clôturés' },
    ];

    const pagesDemandes = usePaginationLocale(enCours);
    const pagesHistorique = usePaginationLocale(historique);
    const pagesConstats = usePaginationLocale(constats);

    const etudiant = (nom: string, matricule: string | null) => (
        <div className="min-w-36">
            <p className="font-medium text-gray-900">{nom}</p>
            {matricule && <p className="text-xs text-muted">{matricule}</p>}
        </div>
    );
    const logement = (chambre: string | null, batiment: string | null, cite: string | null) => (
        <div className="min-w-32">
            <p>{chambre ? `Chambre ${chambre}` : '—'}</p>
            <p className="text-xs text-muted">{[batiment, cite].filter(Boolean).join(' · ') || '—'}</p>
        </div>
    );

    const colonnesDemandes: DataTableColumn<Demande>[] = [
        { key: 'etudiant', header: 'Étudiant', cell: (d) => etudiant(d.etudiant, d.matricule) },
        { key: 'logement', header: 'Logement', cell: (d) => logement(d.chambre, d.batiment, d.cite) },
        {
            key: 'sortie',
            header: 'Sortie prévue',
            className: 'whitespace-nowrap',
            cell: (d) => {
                const jours = joursAvant(d.date_sortie);
                return (
                    <div>
                        <p className="tabular-nums">{dateFr(d.date_sortie)}</p>
                        {jours !== null && (
                            <p className={cn('text-xs', jours < 0 ? 'font-medium text-red-700' : jours <= 7 ? 'font-medium text-amber-800' : 'text-muted')}>
                                {jours < 0 ? `Dépassée de ${-jours} j` : jours === 0 ? 'Aujourd’hui' : `Dans ${jours} j`}
                            </p>
                        )}
                    </div>
                );
            },
        },
        { key: 'motif', header: 'Motif', cell: (d) => d.motif ?? <span className="text-muted">—</span> },
        {
            key: 'statut',
            header: 'Statut',
            cell: (d) => (
                <div className="flex flex-col gap-1">
                    <StatutDemandeBadge statut={d.statut} />
                    {d.expulsion && <Badge variant="danger">Expulsion</Badge>}
                </div>
            ),
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (d) => (
                <RowActions>
                    <RowAction label="Voir la demande" icon={Eye} tone="view" onClick={() => setDetail(d)} />
                    {d.urls.decision && <RowAction label="Approuver" icon={Check} tone="success" onClick={() => setApprobation(d)} />}
                    {d.urls.decision && !d.expulsion_active && <RowAction label="Refuser" icon={X} tone="danger" onClick={() => setRefus(d)} />}
                    {d.urls.etat_des_lieux && (
                        <RowAction
                            label="Faire l’état des lieux de sortie"
                            icon={LogOut}
                            tone="edit"
                            onClick={() => {
                                setVue('etats');
                                setCibleSortie(d.etudiant_id);
                            }}
                        />
                    )}
                </RowActions>
            ),
        },
    ];

    const colonnesHistorique: DataTableColumn<Demande>[] = [
        {
            key: 'etudiant',
            header: 'Étudiant',
            cell: (d) => (
                <div className="min-w-36">
                    <p className="font-medium text-gray-900">{d.etudiant}</p>
                    <p className="text-xs text-muted">{[d.matricule, d.date_demande && `demande du ${dateFr(d.date_demande)}`].filter(Boolean).join(' · ')}</p>
                </div>
            ),
        },
        { key: 'logement', header: 'Logement', cell: (d) => logement(d.chambre, d.batiment, d.cite) },
        { key: 'statut', header: 'Statut', cell: (d) => <StatutDemandeBadge statut={d.statut} /> },
        { key: 'retenues', header: 'Retenues', className: 'text-right tabular-nums whitespace-nowrap', headClassName: 'text-right', cell: (d) => (d.decompte ? fcfa(d.decompte.total_retenues) : '—') },
        {
            key: 'caution',
            header: 'Caution',
            className: 'whitespace-nowrap',
            cell: (d) => {
                const c = d.decompte;
                if (!c) return <span className="text-muted">—</span>;
                if (c.date_restitution) return <Badge variant="success">Restituée le {dateFr(c.date_restitution)}</Badge>;
                if (c.caution_a_restituer > 0) return <Badge variant="warning">{fcfa(c.caution_a_restituer)} à restituer</Badge>;
                return <span className="text-muted">Rien à restituer</span>;
            },
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (d) => (
                <RowActions>
                    <RowAction label="Voir le détail" icon={Eye} tone="view" onClick={() => setDetail(d)} />
                    {d.urls.pdf && <RowAction label="Décompte PDF" icon={FileDown} href={d.urls.pdf} />}
                    {d.urls.restitution && <RowAction label="Restituer la caution" icon={Coins} tone="edit" onClick={() => setRestitution(d)} />}
                </RowActions>
            ),
        },
    ];

    const colonnesConstats: DataTableColumn<Inspection>[] = [
        { key: 'date', header: 'Date', className: 'whitespace-nowrap tabular-nums', cell: (i) => (i.date ? dateFr(i.date) : <span className="text-muted">Non renseignée</span>) },
        { key: 'etudiant', header: 'Étudiant', cell: (i) => etudiant(i.etudiant, i.matricule) },
        { key: 'logement', header: 'Logement', cell: (i) => logement(i.chambre, i.batiment, i.cite) },
        { key: 'type', header: 'Type', cell: (i) => <Badge variant={i.type === 'entree' ? 'info' : 'success'}>{i.type === 'entree' ? 'Entrée' : 'Sortie'}</Badge> },
        { key: 'observation', header: 'Observations', cell: (i) => <p className="line-clamp-2 max-w-80 whitespace-pre-line">{i.observation || <span className="text-muted">—</span>}</p> },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (i) => <RowActions>{i.pdf && <RowAction label="État des lieux PDF" icon={FileDown} href={i.pdf} newTab />}</RowActions>,
        },
    ];

    const vide = (icone: ReactNode, message: string) => (
        <span className="flex flex-col items-center gap-2">
            {icone}
            {filtreActif ? 'Aucun résultat pour ces filtres.' : message}
        </span>
    );

    return (
        <AdminLayout title="Départs et états des lieux">
            <PageHeader
                title="Départs et états des lieux"
                description="États des lieux d’entrée et de sortie, demandes de sortie (préavis), décision et décompte final. La sortie n’est clôturée qu’à l’enregistrement de l’état des lieux de sortie."
                actions={
                    (urls.parametres || urls.store) && (
                        <>
                            {urls.parametres && (
                                <a href={urls.parametres} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                                    <SlidersHorizontal {...ICON} aria-hidden /> Paramètres de clôture
                                </a>
                            )}
                            {urls.store && (
                                <Button size="sm" className="h-10 px-4" onClick={() => setNouvelle(true)}>
                                    <Plus {...ICON} aria-hidden /> Nouvelle demande de sortie
                                </Button>
                            )}
                        </>
                    )
                }
            />

            <Tabs value={vue} onValueChange={setVue}>
                <TabsList aria-label="Vues">
                    <TabsTrigger value="etats">
                        <ClipboardCheck {...ICON} aria-hidden /> États des lieux <Badge variant="neutral">{nombre(etatsDesLieux.residents.length)}</Badge>
                    </TabsTrigger>
                    <TabsTrigger value="demandes">
                        <LogOut {...ICON} aria-hidden /> Demandes de sortie <Badge variant="neutral">{nombre(stats.ouvertes)}</Badge>
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="etats">
                    <EtatsDesLieuxPanel {...etatsDesLieux} cites={cites} cible={cibleSortie} onCibleOuverte={() => setCibleSortie(null)} />
                </TabsContent>

                <TabsContent value="demandes">
                    <nav aria-label="Filtrer par statut" className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        {kpis.map((k) => {
                            const actif = filtres.statut === k.cle && k.cle !== '';
                            return (
                                <button
                                    key={k.label}
                                    type="button"
                                    aria-pressed={actif}
                                    onClick={() => {
                                        filtrer({ statut: actif ? '' : k.cle });
                                        setOnglet(k.cle === 'cloturee' ? 'historique' : 'demandes');
                                    }}
                                    className={cn(
                                        'cursor-pointer rounded-md text-left transition-shadow focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                                        actif ? 'ring-2 ring-primary ring-offset-2' : 'hover:shadow-sm',
                                    )}
                                >
                                    <StatCard tone={k.tone} label={k.label} value={nombre(k.valeur)} extra={k.extra} highlight={k.highlight} />
                                </button>
                            );
                        })}
                    </nav>

                    <section aria-label="Filtres" className="mt-4 flex flex-col gap-3 rounded-brand border bg-white p-4 lg:flex-row lg:items-center">
                        <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <Input
                                value={filtres.recherche}
                                onChange={(event) => filtrer({ recherche: event.target.value })}
                                placeholder="Nom, matricule, chambre…"
                                aria-label="Rechercher un étudiant"
                                icon={<Search />}
                                className="h-10"
                            />
                            <Select value={filtres.statut || TOUS} onValueChange={(v) => filtrer({ statut: v === TOUS ? '' : (v as StatutDemande) })}>
                                <SelectTrigger className="h-10" aria-label="Filtrer par statut">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={TOUS}>Tous les statuts</SelectItem>
                                    {(Object.keys(STATUTS_DEMANDE) as StatutDemande[]).map((s) => (
                                        <SelectItem key={s} value={s}>
                                            {STATUTS_DEMANDE[s].label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <DatePicker value={filtres.date} onChange={(date) => filtrer({ date })} placeholder="Date de sortie" aria-label="Filtrer par date" className="h-10" />
                            <Select value={filtres.cite || TOUS} onValueChange={(v) => filtrer({ cite: v === TOUS ? '' : v })}>
                                <SelectTrigger className="h-10" aria-label="Filtrer par cité">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={TOUS}>Toutes les cités</SelectItem>
                                    {cites.map((c) => (
                                        <SelectItem key={c.id} value={c.id}>
                                            {c.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        {filtreActif && (
                            <Button variant="ghost" size="sm" className="h-10" onClick={() => setFiltres(FILTRES_VIDES)}>
                                <X className="size-4" aria-hidden /> Réinitialiser
                            </Button>
                        )}
                    </section>

                    <Tabs value={onglet} onValueChange={setOnglet} className="mt-6">
                        <TabsList aria-label="Départs">
                            <TabsTrigger value="demandes">
                                <ListChecks {...ICON} aria-hidden /> Demandes en cours <Badge variant="neutral">{nombre(enCours.length)}</Badge>
                            </TabsTrigger>
                            <TabsTrigger value="calendrier">
                                <CalendarDays {...ICON} aria-hidden /> Calendrier
                            </TabsTrigger>
                            <TabsTrigger value="historique">
                                <History {...ICON} aria-hidden /> Sorties traitées <Badge variant="neutral">{nombre(historique.length)}</Badge>
                            </TabsTrigger>
                            <TabsTrigger value="constats">
                                <ClipboardCheck {...ICON} aria-hidden /> États des lieux réalisés <Badge variant="neutral">{nombre(constats.length)}</Badge>
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="demandes">
                            <div className="overflow-hidden rounded-brand border bg-white">
                                <SimpleTable columns={colonnesDemandes} rows={pagesDemandes.visibles} rowKey={(d) => d.id} emptyMessage={vide(<Inbox {...ICON} aria-hidden />, 'Aucune demande de sortie en cours.')} />
                                <PaginationLocale {...pagesDemandes} />
                            </div>
                        </TabsContent>

                        <TabsContent value="calendrier">
                            <p className="mb-3 text-sm text-muted">Sorties prévues selon les filtres. Choisissez une journée pour afficher ses sorties ; cliquez sur une sortie pour ouvrir la demande.</p>
                            <CalendrierSorties demandes={filtrees} date={filtres.date} onOuvrir={setDetail} />
                        </TabsContent>

                        <TabsContent value="historique">
                            <div className="overflow-hidden rounded-brand border bg-white">
                                <SimpleTable columns={colonnesHistorique} rows={pagesHistorique.visibles} rowKey={(d) => d.id} emptyMessage={vide(<History {...ICON} aria-hidden />, 'Aucune sortie clôturée ou refusée.')} />
                                <PaginationLocale {...pagesHistorique} />
                            </div>
                        </TabsContent>

                        <TabsContent value="constats">
                            <p className="mb-3 text-sm text-muted">États des lieux d’entrée et de sortie enregistrés, du plus récent au plus ancien (le filtre de statut ne s’applique pas ici).</p>
                            <div className="overflow-hidden rounded-brand border bg-white">
                                <SimpleTable columns={colonnesConstats} rows={pagesConstats.visibles} rowKey={(i) => i.id} emptyMessage={vide(<ClipboardCheck {...ICON} aria-hidden />, 'Aucun état des lieux enregistré.')} />
                                <PaginationLocale {...pagesConstats} />
                            </div>
                        </TabsContent>
                    </Tabs>
                </TabsContent>
            </Tabs>

            {urls.store && <NouvelleDemandeDialog open={nouvelle} onClose={() => setNouvelle(false)} url={urls.store} residents={residents} motifs={motifs} />}
            <DetailDemandeDialog demande={detail} onClose={() => setDetail(null)} />
            <ApprobationDialog demande={approbation} onClose={() => setApprobation(null)} />
            <RefusDialog demande={refus} onClose={() => setRefus(null)} />
            <RestitutionDialog demande={restitution} modes={modesRestitution} onClose={() => setRestitution(null)} />
        </AdminLayout>
    );
}
