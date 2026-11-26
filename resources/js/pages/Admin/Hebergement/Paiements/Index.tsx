import { Link, router } from '@inertiajs/react';
import { BarChart3, CalendarDays, Check, CirclePlay, Download, Eye, Plus, Printer, Send, Wallet, X } from 'lucide-react';
import { useState } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { type CalendrierLoyers, CalendrierLoyersOnglet } from '@/components/paiements/calendrier-loyers';
import { NouveauPaiementDialog, type OptionTypePaiement } from '@/components/paiements/nouveau-paiement-dialog';
import { RelanceDialog } from '@/components/paiements/relance-dialog';
import { fcfa, MOIS, TypePaiementBadge } from '@/components/paiements/shared';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { StatCard } from '@/components/ui/stat-card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Paiement = {
    id: string;
    numero: string | null;
    resident: string | null;
    telephone: string | null;
    residence: string | null;
    chambre: string | null;
    mois: string | null;
    montant: number;
    methode: string;
    type: string;
    type_code: string;
    statut: string;
    statut_label: string;
    date_paiement: string | null;
    /** `recu` : reçu PDF, unique pour les types de paiement enregistrés ensemble. */
    urls: { show: string | null; recu: string | null; valider: string | null; annuler: string | null };
};

type Filtres = { campagne: string; residence: string; statut: string; mois: string };

type CampagneOption = { id: string; libelle: string; annee: string | null; active: boolean };

type Props = {
    paiements: Paginated<Paiement>;
    /** Aucune pénalité de retard ne s'applique : `prochaine_echeance` vient du calendrier des loyers de la campagne. */
    kpis: { total_encaisse: number; taux_recouvrement: number; impayes: number; prochaine_echeance: { date: string; mois: string } | null; nombre_paiements: number; nombre_retard: number };
    residences: { id: string; name: string }[];
    /** Types de paiement actifs (Paramétrage hébergement › Types de paiement). */
    typesPaiement: OptionTypePaiement[];
    /** Campagnes, de la plus récente à la plus ancienne ; la page affiche celle de `filters.campagne` (ou `toutes`). */
    campagnes: CampagneOption[];
    filters: Filtres;
    /** Onglet ouvert (`?onglet=calendrier` après l'enregistrement du calendrier). */
    onglet: 'paiements' | 'calendrier';
    /** Calendrier des loyers de la campagne affichée (null pour « Toutes les campagnes »). */
    calendrier: CalendrierLoyers | null;
    urls: { index: string; export: string | null; store: string | null; recherche: string | null; relances: string | null; rapport: string | null; calendrier: string | null };
};

const TOUS = 'tous';
/** Valeur du paramètre `campagne` pour afficher toutes les campagnes (comprise par l'action `index`). */
const TOUTES_CAMPAGNES = 'toutes';

const libelleCampagne = (campagne: CampagneOption) =>
    `${campagne.libelle}${campagne.annee && campagne.annee !== campagne.libelle ? ` (${campagne.annee})` : ''}${campagne.active ? ' · en cours' : ''}`;

const STATUTS = [
    { value: 'valide', label: 'Payé' },
    { value: 'en_attente', label: 'En attente' },
    { value: 'annule', label: 'Annulé' },
];

/** Les 24 derniers mois au format `aaaa-mm` (filtre « Mois » de l'action `index`). */
const moisRecents = () => {
    const maintenant = new Date();
    return Array.from({ length: 24 }, (_, index) => {
        const date = new Date(maintenant.getFullYear(), maintenant.getMonth() - index, 1);
        const mois = String(date.getMonth() + 1).padStart(2, '0');
        return { value: `${date.getFullYear()}-${mois}`, label: `${MOIS[date.getMonth()].label} ${date.getFullYear()}` };
    });
};

type Action = { type: 'valider' | 'annuler'; paiement: Paiement } | null;

/** Paiements des loyers (remplace `vie_residentielle/paiement/index.blade.php`). */
export default function PaiementsIndex({ paiements, kpis, residences, typesPaiement, campagnes, filters, onglet, calendrier, urls }: Props) {
    const [ongletActif, setOngletActif] = useState<string>(onglet);
    const [nouveau, setNouveau] = useState(false);
    const [relance, setRelance] = useState(false);
    const [action, setAction] = useState<Action>(null);
    const [traitement, setTraitement] = useState(false);
    const mois = moisRecents();
    if (filters.mois && !mois.some((item) => item.value === filters.mois)) mois.push({ value: filters.mois, label: filters.mois });

    const filtrer = (cle: keyof Filtres, valeur: string) => {
        const suivants = { ...filters, [cle]: valeur === TOUS ? '' : valeur };
        router.get(urls.index, Object.fromEntries(Object.entries(suivants).filter(([, v]) => v)), { preserveState: true, preserveScroll: true, replace: true });
    };
    // « Réinitialiser » vide les filtres de la liste, pas la campagne choisie.
    const filtreActif = Boolean(filters.residence || filters.statut || filters.mois);
    const campagne = campagnes.find((item) => item.id === filters.campagne) ?? null;
    const periode = campagne ? `Campagne ${campagne.libelle}` : 'Toutes les campagnes';

    const confirmer = () => {
        const url = action && action.paiement.urls[action.type];
        if (!url) return;
        router.post(url, {}, {
            preserveScroll: true,
            onStart: () => setTraitement(true),
            onFinish: () => {
                setTraitement(false);
                setAction(null);
            },
        });
    };

    const colonnes: DataTableColumn<Paiement>[] = [
        {
            key: 'resident',
            header: 'Résident',
            cell: (p) => (
                <div className="min-w-40">
                    <p className="font-medium text-gray-900">{p.resident ?? 'N/A'}</p>
                    <p className="text-xs text-muted">{p.telephone ?? '—'}</p>
                </div>
            ),
        },
        {
            key: 'logement',
            header: 'Logement',
            cell: (p) =>
                p.residence || p.chambre ? (
                    <div className="min-w-28">
                        <p>{p.residence ?? '—'}</p>
                        {p.chambre && <p className="text-xs text-muted">Chambre {p.chambre}</p>}
                    </div>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
        { key: 'type', header: 'Type', cell: (p) => <TypePaiementBadge code={p.type_code} label={p.type} /> },
        { key: 'mois', header: 'Mois', cell: (p) => (p.mois ? <span className="line-clamp-2 max-w-48 capitalize">{p.mois}</span> : <span className="text-muted">—</span>) },
        { key: 'montant', header: 'Montant', className: 'whitespace-nowrap font-medium tabular-nums', cell: (p) => fcfa(p.montant) },
        { key: 'methode', header: 'Méthode', cell: (p) => <Badge variant="info">{p.methode}</Badge> },
        {
            key: 'date',
            header: 'Date de paiement',
            className: 'whitespace-nowrap',
            cell: (p) =>
                p.date_paiement ? (
                    <>
                        {dateFr(p.date_paiement)}
                        <p className="text-xs text-muted">{p.date_paiement.slice(11, 16)}</p>
                    </>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (p) => (
                <RowActions>
                    {p.urls.valider && <RowAction label="Valider le paiement" icon={Check} tone="success" onClick={() => setAction({ type: 'valider', paiement: p })} />}
                    {p.urls.annuler && <RowAction label="Annuler le paiement" icon={X} tone="danger" onClick={() => setAction({ type: 'annuler', paiement: p })} />}
                    {p.urls.recu && <RowAction label="Imprimer le reçu" icon={Printer} href={p.urls.recu} newTab />}
                    {p.urls.show && <RowAction label="Voir le détail" icon={Eye} href={p.urls.show} tone="view" />}
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Paiements des loyers">
            <PageHeader
                title="Paiements des loyers"
                aside={
                    // Les indicateurs et la liste des paiements portent sur la campagne choisie.
                    campagnes.length > 0 && (
                        <Select value={filters.campagne || TOUTES_CAMPAGNES} onValueChange={(valeur) => filtrer('campagne', valeur)}>
                            <SelectTrigger aria-label="Campagne affichée" className="h-9 w-full border-[#C6E3A6] bg-soft text-[#004D00] sm:w-80">
                                <span className="flex min-w-0 items-center gap-1.5">
                                    <CirclePlay className="size-4 shrink-0" aria-hidden />
                                    <SelectValue />
                                </span>
                            </SelectTrigger>
                            <SelectContent align="end">
                                {campagnes.map((item) => (
                                    <SelectItem key={item.id} value={item.id}>
                                        {libelleCampagne(item)}
                                    </SelectItem>
                                ))}
                                <SelectItem value={TOUTES_CAMPAGNES}>Toutes les campagnes</SelectItem>
                            </SelectContent>
                        </Select>
                    )
                }
                actions={
                    (urls.rapport || urls.relances || urls.store) && (
                        <>
                            {urls.rapport && (
                                <Link href={urls.rapport} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                                    <BarChart3 {...ICON} aria-hidden /> Rapport financier
                                </Link>
                            )}
                            {urls.relances && (
                                <Button variant="secondary" size="sm" className="h-10 px-4" onClick={() => setRelance(true)}>
                                    <Send {...ICON} aria-hidden /> Relancer les impayés
                                </Button>
                            )}
                            {urls.store && urls.recherche && (
                                <Button size="sm" className="h-10 px-4" onClick={() => setNouveau(true)}>
                                    <Plus {...ICON} aria-hidden /> Nouveau paiement
                                </Button>
                            )}
                        </>
                    )
                }
            />

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard tone="green" label="Total encaissé" value={fcfa(kpis.total_encaisse)} />
                <StatCard tone="blue" label="Taux de recouvrement" value={`${nombre(kpis.taux_recouvrement, 2)} %`} highlight={kpis.taux_recouvrement < 95} />
                <StatCard tone="amber" label="Impayés" value={fcfa(kpis.impayes)} extra={`${nombre(kpis.nombre_retard)} loyer(s) en retard`} highlight={kpis.nombre_retard > 0} />
                <StatCard
                    tone="plum"
                    label="Prochaine échéance"
                    value={kpis.prochaine_echeance ? dateFr(kpis.prochaine_echeance.date) : '—'}
                    extra={kpis.prochaine_echeance ? `Loyer de ${kpis.prochaine_echeance.mois}` : calendrier ? 'Calendrier non configuré' : 'Choisissez une campagne'}
                    highlight={calendrier !== null && !calendrier.configure}
                />
            </div>

            <Tabs value={ongletActif} onValueChange={setOngletActif} className="mt-6">
                <TabsList aria-label="Paiements">
                    <TabsTrigger value="paiements">
                        <Wallet {...ICON} aria-hidden /> Paiements
                    </TabsTrigger>
                    <TabsTrigger value="calendrier">
                        <CalendarDays {...ICON} aria-hidden /> Calendrier des loyers
                    </TabsTrigger>
                </TabsList>
                <TabsContent value="calendrier">
                    <CalendrierLoyersOnglet calendrier={calendrier} url={urls.calendrier} />
                </TabsContent>
                <TabsContent value="paiements">
                    <section>
                        <div className="mb-3 flex items-baseline justify-between gap-3">
                            <div>
                                <h2 className="text-base font-medium text-gray-900">Liste des paiements</h2>
                                <p className="text-xs text-muted">{periode}</p>
                            </div>
                            <span className="text-sm text-muted">{nombre(paiements.total)} paiement(s)</span>
                        </div>
                        <DataTable
                            columns={colonnes}
                            data={paiements}
                            rowKey={(p) => p.id}
                            toolbar={
                                <>
                                    <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                                        <Select value={filters.residence || TOUS} onValueChange={(valeur) => filtrer('residence', valeur)}>
                                            <SelectTrigger className="h-10" aria-label="Filtrer par résidence">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={TOUS}>Toutes les résidences</SelectItem>
                                                {residences.map((residence) => (
                                                    <SelectItem key={residence.id} value={residence.id}>
                                                        {residence.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <Select value={filters.statut || TOUS} onValueChange={(valeur) => filtrer('statut', valeur)}>
                                            <SelectTrigger className="h-10" aria-label="Filtrer par statut">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={TOUS}>Tous les statuts</SelectItem>
                                                {STATUTS.map((statut) => (
                                                    <SelectItem key={statut.value} value={statut.value}>
                                                        {statut.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <Select value={filters.mois || TOUS} onValueChange={(valeur) => filtrer('mois', valeur)}>
                                            <SelectTrigger className="h-10" aria-label="Filtrer par mois de paiement">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={TOUS}>Tous les mois</SelectItem>
                                                {mois.map((item) => (
                                                    <SelectItem key={item.value} value={item.value}>
                                                        {item.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="flex gap-2">
                                        {filtreActif && (
                                            <Button variant="ghost" size="sm" className="h-10" onClick={() => router.get(urls.index, { campagne: filters.campagne }, { preserveScroll: true })}>
                                                <X className="size-4" aria-hidden /> Réinitialiser
                                            </Button>
                                        )}
                                        {urls.export && (
                                            <a href={urls.export} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                                                <Download {...ICON} aria-hidden /> Exporter (CSV)
                                            </a>
                                        )}
                                    </div>
                                </>
                            }
                            emptyMessage={
                                <span className="flex flex-col items-center gap-2">
                                    <Wallet {...ICON} aria-hidden />
                                    {filtreActif ? 'Aucun paiement ne correspond à ces filtres.' : campagne ? 'Aucun paiement enregistré pour cette campagne.' : 'Aucun paiement enregistré.'}
                                </span>
                            }
                        />
                    </section>
                </TabsContent>
            </Tabs>

            {urls.store && urls.recherche && <NouveauPaiementDialog open={nouveau} onClose={() => setNouveau(false)} urlStore={urls.store} urlRecherche={urls.recherche} types={typesPaiement} />}
            {urls.relances && <RelanceDialog open={relance} onClose={() => setRelance(false)} url={urls.relances} nombreRetard={kpis.nombre_retard} />}

            <ConfirmDialog
                open={action !== null}
                onOpenChange={(ouvert) => !ouvert && setAction(null)}
                title={action?.type === 'valider' ? 'Valider ce paiement ?' : 'Annuler ce paiement ?'}
                description={
                    action && (
                        <>
                            {action.paiement.resident ?? 'Résident inconnu'} · <strong className="font-medium text-gray-900">{fcfa(action.paiement.montant)}</strong>
                            {action.type === 'valider' ? '. Le paiement sera marqué comme payé à la date du jour.' : '. Le paiement sera marqué comme annulé.'}
                        </>
                    )
                }
                confirmLabel={action?.type === 'valider' ? 'Valider le paiement' : 'Annuler le paiement'}
                confirmIcon={action?.type === 'valider' ? <Check className="size-4" aria-hidden /> : <X className="size-4" aria-hidden />}
                danger={action?.type === 'annuler'}
                processing={traitement}
                onConfirm={confirmer}
            />
        </AdminLayout>
    );
}
