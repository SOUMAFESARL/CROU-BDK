import { ArrowUpRight, DoorOpen, Gavel, Megaphone, ShieldAlert, Wallet, Wrench, type LucideIcon } from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { AdminLayout } from '@/components/layout/admin-layout';
import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { StatCard, type KpiTone } from '@/components/ui/stat-card';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { fcfa } from '@/components/paiements/shared';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';

type Acces = Record<'occupation' | 'finance' | 'maintenance' | 'discipline' | 'contracts' | 'inspections' | 'departures' | 'announcements', boolean>;

type Kpi = { key: string; label: string; value: number; unit: string; extra: string; tone: KpiTone; highlight: boolean };

/** Ligne par cité : seules les sections autorisées sont envoyées par le serveur. */
type Residence = {
    id: string;
    name: string;
    occupation_rate?: number;
    rotation_rate?: number;
    total_chambres?: number;
    available?: number;
    maintenance?: number;
    hors_service?: number;
    reserved?: number;
    occupied_status?: number;
    occupied_lits?: number;
    recovery_rate?: number;
    unpaid_rate?: number;
    ot_total?: number;
    ot_en_cours?: number;
    ot_recurrence_rate?: number;
    ot_resolus?: number;
    ot_taux_resolution?: number;
    ot_temps_resolution?: number;
    ot_categorie_principale?: string;
    finance?: { retenue_moyenne: number; caution_restituee_rate: number; cout_reparations: number; recettes_loyers: number; rentabilite: number; loyer_moyen: number };
    discipline?: { incidents: number; avertissements: number; blames: number; suspensions: number; sanctions_actives: number; transmis_securite: number; decisions: number };
    contrats?: { total: number; confirmes: number; sejours_confirmes: number; edl_entree: number; edl_sortie: number; degradations: number; photos: number; conformite: number };
    departs?: { demandes: number; anticipes: number; disciplinaires: number; preavis_non_respecte: number; archives: number; retenues: number; cloture_rate: number };
};

type Raccourci = { key: string; label: string; description: string; icon: string; href: string; badge: string | null; alerte: boolean };

type OrdreRecent = {
    id: string;
    numero: string;
    residence: string | null;
    categorie: string;
    priorite: string;
    priorite_label: string;
    statut: string;
    statut_label: string;
    assigne: string | null;
    date_creation: string | null;
    date_resolution: string | null;
};

type Props = {
    viewAccess: Acces;
    kpis: Kpi[];
    residences: Residence[];
    raccourcis: Raccourci[];
    ordresRecents: OrdreRecent[];
    synthese: {
        ot_urgents: number | null;
        ot_en_cours: number | null;
        ot_resolution: number | null;
        readmission: number | null;
        sejours_confirmes: number | null;
        departs_clotures: number | null;
        departs_total: number | null;
    };
    periode: string;
    miseAJour: string;
};

const ICONES: Record<string, LucideIcon> = { wallet: Wallet, wrench: Wrench, megaphone: Megaphone, 'shield-alert': ShieldAlert, gavel: Gavel, 'door-open': DoorOpen };

const TOUTES = 'toutes';

const pct = (valeur: number | undefined, decimales = 1) => `${nombre(valeur ?? 0, decimales)} %`;
const somme = (lignes: Residence[], lire: (r: Residence) => number | undefined) => lignes.reduce((total, r) => total + (lire(r) ?? 0), 0);
const moyenne = (lignes: Residence[], lire: (r: Residence) => number | undefined) => (lignes.length ? somme(lignes, lire) / lignes.length : 0);

type Colonne = { key: string; header: string; cell: (r: Residence) => ReactNode; total?: ReactNode; className?: string };

/** Variante d'un taux : vert au-dessus du seuil haut, orange entre les deux, rouge sous le seuil bas. */
const varianteTaux = (taux: number, haut: number, bas: number) => (taux >= haut ? 'success' : taux >= bas ? 'warning' : 'danger');

const VARIANTES_STATUT_OT: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'neutral'> = {
    en_attente: 'danger',
    en_cours: 'warning',
    en_attente_pieces: 'info',
    termine: 'success',
    annule: 'neutral',
    reporte: 'neutral',
};

/** Pilotage de la vie résidentielle (remplace `vie_residentielle/index.blade.php`). */
export default function VieResidentielleIndex({ viewAccess, kpis, residences, raccourcis, ordresRecents, synthese, periode, miseAJour }: Props) {
    const [residence, setResidence] = useState(TOUTES);
    const lignes = useMemo(() => (residence === TOUTES ? residences : residences.filter((r) => r.id === residence)), [residence, residences]);
    const libelleTotal = residence === TOUTES ? 'Ensemble du CROU' : 'Total';

    const onglets: { value: string; label: string; visible: boolean; colonnes: Colonne[]; note?: ReactNode; ligneAlerte?: (r: Residence) => boolean }[] = [
        {
            value: 'occupation',
            label: 'Occupation & parc',
            visible: viewAccess.occupation,
            ligneAlerte: (r) => (r.occupation_rate ?? 0) < 90,
            note: `${nombre(somme(lignes, (r) => r.reserved))} chambre(s) réservée(s) · ${nombre(somme(lignes, (r) => r.occupied_status))} chambre(s) occupée(s).`,
            colonnes: [
                { key: 'occ', header: 'Occupation', cell: (r) => <Taux valeur={r.occupation_rate} variante={varianteTaux(r.occupation_rate ?? 0, 95, 90)} />, total: pct(moyenne(lignes, (r) => r.occupation_rate)) },
                { key: 'rot', header: 'Rotation', cell: (r) => pct(r.rotation_rate), total: pct(moyenne(lignes, (r) => r.rotation_rate)) },
                { key: 'tot', header: 'Chambres', cell: (r) => nombre(r.total_chambres), total: nombre(somme(lignes, (r) => r.total_chambres)), className: 'text-right' },
                { key: 'dis', header: 'Disponibles', cell: (r) => nombre(r.available), total: nombre(somme(lignes, (r) => r.available)), className: 'text-right' },
                { key: 'mai', header: 'En maintenance', cell: (r) => nombre(r.maintenance), total: nombre(somme(lignes, (r) => r.maintenance)), className: 'text-right' },
                { key: 'hs', header: 'Hors service', cell: (r) => nombre(r.hors_service), total: nombre(somme(lignes, (r) => r.hors_service)), className: 'text-right' },
                { key: 'lits', header: 'Lits occupés', cell: (r) => nombre(r.occupied_lits), total: nombre(somme(lignes, (r) => r.occupied_lits)), className: 'text-right' },
            ],
        },
        {
            value: 'finance',
            label: 'Finances & paiements',
            visible: viewAccess.finance,
            ligneAlerte: (r) => (r.unpaid_rate ?? 0) >= 10,
            note: 'Solde = paiements de loyers validés − coût réel des réparations.',
            colonnes: [
                { key: 'imp', header: 'Impayés', cell: (r) => <Badge variant={(r.unpaid_rate ?? 0) >= 10 ? 'danger' : 'success'}>{pct(r.unpaid_rate)}</Badge>, total: pct(moyenne(lignes, (r) => r.unpaid_rate)) },
                { key: 'rec', header: 'Recouvrement', cell: (r) => <Taux valeur={r.recovery_rate} variante={varianteTaux(r.recovery_rate ?? 0, 90, 75)} />, total: pct(moyenne(lignes, (r) => r.recovery_rate)) },
                { key: 'ret', header: 'Retenue caution (moy.)', cell: (r) => fcfa(r.finance?.retenue_moyenne), total: fcfa(moyenne(lignes, (r) => r.finance?.retenue_moyenne)), className: 'text-right whitespace-nowrap' },
                { key: 'cau', header: 'Caution restituée', cell: (r) => pct(r.finance?.caution_restituee_rate), total: pct(moyenne(lignes, (r) => r.finance?.caution_restituee_rate)), className: 'text-right' },
                { key: 'rep', header: 'Coût réparations', cell: (r) => fcfa(r.finance?.cout_reparations), total: fcfa(somme(lignes, (r) => r.finance?.cout_reparations)), className: 'text-right whitespace-nowrap' },
                {
                    key: 'sol',
                    header: 'Solde loyers / réparations',
                    cell: (r) => <Badge variant={(r.finance?.rentabilite ?? 0) >= 0 ? 'success' : 'danger'}>{fcfa(r.finance?.rentabilite)}</Badge>,
                    total: fcfa(somme(lignes, (r) => r.finance?.rentabilite)),
                    className: 'text-right whitespace-nowrap',
                },
                { key: 'loy', header: 'Loyer moyen', cell: (r) => fcfa(r.finance?.loyer_moyen), total: fcfa(moyenne(lignes, (r) => r.finance?.loyer_moyen)), className: 'text-right whitespace-nowrap' },
            ],
        },
        {
            value: 'maintenance',
            label: 'Incidents & maintenance',
            visible: viewAccess.maintenance,
            ligneAlerte: (r) => (r.ot_total ?? 0) > 0 && (r.ot_taux_resolution ?? 0) < 80,
            note: `${nombre(synthese.ot_urgents)} OT urgent(s) ouvert(s) · mise à jour le ${miseAJour}.`,
            colonnes: [
                { key: 'cre', header: 'OT créés', cell: (r) => nombre(r.ot_total), total: nombre(somme(lignes, (r) => r.ot_total)), className: 'text-right' },
                { key: 'res', header: 'OT résolus', cell: (r) => nombre(r.ot_resolus), total: nombre(somme(lignes, (r) => r.ot_resolus)), className: 'text-right' },
                {
                    key: 'taux',
                    header: 'Taux de résolution',
                    cell: (r) => <Taux valeur={r.ot_taux_resolution} variante={varianteTaux(r.ot_taux_resolution ?? 0, 90, 80)} />,
                    total: pct(somme(lignes, (r) => r.ot_total) ? (somme(lignes, (r) => r.ot_resolus) / somme(lignes, (r) => r.ot_total)) * 100 : 0),
                },
                {
                    key: 'tps',
                    header: 'Temps moyen',
                    cell: (r) => ((r.ot_temps_resolution ?? 0) > 0 ? <Badge variant={varianteTaux(-(r.ot_temps_resolution ?? 0), -3, -7)}>{nombre(r.ot_temps_resolution, 1)} j</Badge> : <span className="text-muted">—</span>),
                    total: (() => {
                        const avecTemps = lignes.filter((r) => (r.ot_temps_resolution ?? 0) > 0);
                        return avecTemps.length ? `${nombre(moyenne(avecTemps, (r) => r.ot_temps_resolution), 1)} j` : '—';
                    })(),
                },
                {
                    key: 'enc',
                    header: 'OT ouverts',
                    cell: (r) => <Badge variant={(r.ot_en_cours ?? 0) === 0 ? 'success' : (r.ot_en_cours ?? 0) > 5 ? 'danger' : 'warning'}>{nombre(r.ot_en_cours)}</Badge>,
                    total: nombre(somme(lignes, (r) => r.ot_en_cours)),
                },
                { key: 'cat', header: 'Catégorie principale', cell: (r) => (r.ot_categorie_principale && r.ot_categorie_principale !== '-' ? <span className="capitalize">{r.ot_categorie_principale.replace('_', ' ')}</span> : <span className="text-muted">—</span>) },
                {
                    key: 'rcr',
                    header: 'Incidents récurrents',
                    cell: (r) =>
                        (r.ot_total ?? 0) > 0 ? <Badge variant={(r.ot_recurrence_rate ?? 0) > 20 ? 'danger' : (r.ot_recurrence_rate ?? 0) > 10 ? 'warning' : 'success'}>{pct(r.ot_recurrence_rate)}</Badge> : <span className="text-muted">—</span>,
                },
            ],
        },
        {
            value: 'discipline',
            label: 'Discipline & sécurité',
            visible: viewAccess.discipline,
            ligneAlerte: (r) => (r.discipline?.incidents ?? 0) > 5,
            note: 'Toutes les décisions sont tracées dans le dossier du résident.',
            colonnes: [
                { key: 'inc', header: 'Incidents', cell: (r) => nombre(r.discipline?.incidents), total: nombre(somme(lignes, (r) => r.discipline?.incidents)), className: 'text-right' },
                { key: 'ave', header: 'Avertissements', cell: (r) => nombre(r.discipline?.avertissements), total: nombre(somme(lignes, (r) => r.discipline?.avertissements)), className: 'text-right' },
                { key: 'bla', header: 'Blâmes', cell: (r) => nombre(r.discipline?.blames), total: nombre(somme(lignes, (r) => r.discipline?.blames)), className: 'text-right' },
                { key: 'sus', header: 'Suspensions', cell: (r) => nombre(r.discipline?.suspensions), total: nombre(somme(lignes, (r) => r.discipline?.suspensions)), className: 'text-right' },
                { key: 'act', header: 'Sanctions actives', cell: (r) => nombre(r.discipline?.sanctions_actives), total: nombre(somme(lignes, (r) => r.discipline?.sanctions_actives)), className: 'text-right' },
                { key: 'dec', header: 'Décisions rendues', cell: (r) => nombre(r.discipline?.decisions), total: nombre(somme(lignes, (r) => r.discipline?.decisions)), className: 'text-right' },
                { key: 'sec', header: 'Transmis à la sécurité', cell: (r) => nombre(r.discipline?.transmis_securite), total: nombre(somme(lignes, (r) => r.discipline?.transmis_securite)), className: 'text-right' },
            ],
        },
        {
            value: 'contrats',
            label: 'Contrats & états des lieux',
            visible: viewAccess.contracts || viewAccess.inspections,
            note: 'Contrats enregistrés et approuvés par les étudiants · taux de signature des états des lieux.',
            colonnes: [
                ...(viewAccess.contracts
                    ? [
                          { key: 'gen', header: 'Contrats générés', cell: (r: Residence) => nombre(r.contrats?.total), total: nombre(somme(lignes, (r) => r.contrats?.total)), className: 'text-right' },
                          { key: 'app', header: 'Contrats approuvés', cell: (r: Residence) => nombre(r.contrats?.confirmes), total: nombre(somme(lignes, (r) => r.contrats?.confirmes)), className: 'text-right' },
                      ]
                    : []),
                ...(viewAccess.inspections
                    ? [
                          { key: 'ent', header: 'EDL d’entrée', cell: (r: Residence) => nombre(r.contrats?.edl_entree), total: nombre(somme(lignes, (r) => r.contrats?.edl_entree)), className: 'text-right' },
                          { key: 'sor', header: 'EDL de sortie', cell: (r: Residence) => nombre(r.contrats?.edl_sortie), total: nombre(somme(lignes, (r) => r.contrats?.edl_sortie)), className: 'text-right' },
                          { key: 'deg', header: 'Dégradations', cell: (r: Residence) => nombre(r.contrats?.degradations), total: nombre(somme(lignes, (r) => r.contrats?.degradations)), className: 'text-right' },
                          { key: 'pho', header: 'Photos archivées', cell: (r: Residence) => nombre(r.contrats?.photos), total: nombre(somme(lignes, (r) => r.contrats?.photos)), className: 'text-right' },
                          { key: 'sig', header: 'EDL signés', cell: (r: Residence) => pct(r.contrats?.conformite), total: pct(moyenne(lignes, (r) => r.contrats?.conformite)), className: 'text-right' },
                      ]
                    : []),
            ],
        },
        {
            value: 'departs',
            label: 'Départs & archivage',
            visible: viewAccess.departures,
            ligneAlerte: (r) => (r.departs?.preavis_non_respecte ?? 0) > 0,
            note: 'Archivage complet : contrat, états des lieux, paiements, incidents et décisions disciplinaires.',
            colonnes: [
                { key: 'dem', header: 'Demandes', cell: (r) => nombre(r.departs?.demandes), total: nombre(somme(lignes, (r) => r.departs?.demandes)), className: 'text-right' },
                { key: 'ant', header: 'Anticipés', cell: (r) => nombre(r.departs?.anticipes), total: nombre(somme(lignes, (r) => r.departs?.anticipes)), className: 'text-right' },
                { key: 'dis', header: 'Disciplinaires', cell: (r) => nombre(r.departs?.disciplinaires), total: nombre(somme(lignes, (r) => r.departs?.disciplinaires)), className: 'text-right' },
                { key: 'pre', header: 'Préavis non respecté', cell: (r) => nombre(r.departs?.preavis_non_respecte), total: nombre(somme(lignes, (r) => r.departs?.preavis_non_respecte)), className: 'text-right' },
                { key: 'arc', header: 'Dossiers clôturés', cell: (r) => nombre(r.departs?.archives), total: nombre(somme(lignes, (r) => r.departs?.archives)), className: 'text-right' },
                { key: 'ret', header: 'Retenues calculées', cell: (r) => fcfa(r.departs?.retenues), total: fcfa(somme(lignes, (r) => r.departs?.retenues)), className: 'text-right whitespace-nowrap' },
                { key: 'clo', header: 'Taux de clôture', cell: (r) => pct(r.departs?.cloture_rate), total: pct(moyenne(lignes, (r) => r.departs?.cloture_rate)), className: 'text-right' },
            ],
        },
    ];
    const visibles = onglets.filter((onglet) => onglet.visible);

    const reperes = [
        synthese.readmission !== null && { label: 'Taux de réadmission', valeur: pct(synthese.readmission) },
        synthese.sejours_confirmes !== null && { label: 'Séjours confirmés', valeur: nombre(synthese.sejours_confirmes) },
        synthese.ot_resolution !== null && { label: 'OT résolus', valeur: pct(synthese.ot_resolution) },
        synthese.departs_total !== null && { label: 'Départs clôturés', valeur: `${nombre(synthese.departs_clotures)} / ${nombre(synthese.departs_total)}` },
    ].filter(Boolean) as { label: string; valeur: string }[];

    return (
        <AdminLayout title="Vie résidentielle">
            <PageHeader
                title="Vie résidentielle"
                description={
                    <>
                        Pilotage de la vie dans les cités : occupation, finances, maintenance, discipline, contrats et départs. Année {periode} · mise à jour le {miseAJour}.
                    </>
                }
            />

            {kpis.length > 0 && (
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                    {kpis.map((kpi) => (
                        <StatCard key={kpi.key} tone={kpi.tone} label={kpi.label} value={`${nombre(kpi.value, 1)}${kpi.unit ? ` ${kpi.unit}` : ''}`} extra={kpi.extra} highlight={kpi.highlight} />
                    ))}
                </div>
            )}

            {raccourcis.length > 0 && (
                <section className="mt-6">
                    <h2 className="mb-3 text-base font-medium text-gray-900">Accès rapides</h2>
                    <nav aria-label="Modules de la vie résidentielle" className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {raccourcis.map((raccourci) => {
                            const Icone = ICONES[raccourci.icon] ?? Wrench;
                            return (
                                <a
                                    key={raccourci.key}
                                    href={raccourci.href}
                                    className="group flex items-center gap-3 rounded-brand border bg-white p-3.5 transition hover:-translate-y-0.5 hover:shadow-[0_10px_25px_rgba(30,45,75,0.09)] focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                                >
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                                        <Icone {...ICON} />
                                    </span>
                                    <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <strong className="truncate text-sm font-medium text-gray-900">{raccourci.label}</strong>
                                        <small className="truncate text-xs text-muted">{raccourci.description}</small>
                                        {raccourci.badge && (
                                            <span className={cn('mt-1 w-fit rounded px-1.5 py-0.5 text-xs font-medium', raccourci.alerte ? 'bg-accent text-gray-900' : 'bg-surface text-muted')}>
                                                {raccourci.badge}
                                            </span>
                                        )}
                                    </span>
                                    <ArrowUpRight className="size-4.5 shrink-0 text-gray-400 transition-colors group-hover:text-primary" strokeWidth={1.25} aria-hidden />
                                </a>
                            );
                        })}
                    </nav>
                </section>
            )}

            <section className="mt-8">
                <div className="mb-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 className="text-base font-medium text-gray-900">Détails par résidence</h2>
                        <p className="text-sm text-muted">Les lignes en orange signalent une résidence sous l’objectif.</p>
                    </div>
                    {visibles.length > 0 && residences.length > 1 && (
                        <Select value={residence} onValueChange={setResidence}>
                            <SelectTrigger className="h-10 sm:w-64" aria-label="Filtrer par résidence">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={TOUTES}>Toutes les résidences</SelectItem>
                                {residences.map((r) => (
                                    <SelectItem key={r.id} value={r.id}>
                                        {r.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                </div>

                {visibles.length === 0 ? (
                    <p className="rounded-brand border bg-white p-6 text-sm text-muted">Aucun bloc de cette page n’est autorisé pour votre profil.</p>
                ) : (
                    <Tabs defaultValue={visibles[0].value}>
                        <TabsList aria-label="Domaines de la vie résidentielle">
                            {visibles.map((onglet) => (
                                <TabsTrigger key={onglet.value} value={onglet.value}>
                                    {onglet.label}
                                </TabsTrigger>
                            ))}
                        </TabsList>
                        {visibles.map((onglet) => (
                            <TabsContent key={onglet.value} value={onglet.value} className="space-y-6">
                                <TableauResidences lignes={lignes} colonnes={onglet.colonnes} libelleTotal={libelleTotal} note={onglet.note} ligneAlerte={onglet.ligneAlerte} />
                                {onglet.value === 'maintenance' && ordresRecents.length > 0 && <OrdresRecents ordres={ordresRecents} />}
                            </TabsContent>
                        ))}
                    </Tabs>
                )}
            </section>

            {reperes.length > 0 && (
                <section className="mt-8 rounded-brand border border-[#C6E3A6] bg-soft px-5 py-4">
                    <h2 className="text-sm font-medium text-primary">Repères stratégiques</h2>
                    <dl className="mt-2 flex flex-wrap gap-x-8 gap-y-2">
                        {reperes.map((repere) => (
                            <div key={repere.label} className="flex items-baseline gap-2 text-sm">
                                <dt className="text-muted">{repere.label}</dt>
                                <dd className="font-medium text-gray-900 tabular-nums">{repere.valeur}</dd>
                            </div>
                        ))}
                    </dl>
                </section>
            )}
        </AdminLayout>
    );
}

function Taux({ valeur, variante }: { valeur: number | undefined; variante: 'success' | 'warning' | 'danger' }) {
    const ton = variante === 'success' ? 'primary' : variante === 'warning' ? 'accent' : 'danger';
    return (
        <div className="flex min-w-28 items-center gap-2">
            <Progress value={valeur ?? 0} tone={ton} className="w-16" />
            <span className="text-xs font-medium tabular-nums">{pct(valeur)}</span>
        </div>
    );
}

function TableauResidences({
    lignes,
    colonnes,
    libelleTotal,
    note,
    ligneAlerte,
}: {
    lignes: Residence[];
    colonnes: Colonne[];
    libelleTotal: string;
    note?: ReactNode;
    ligneAlerte?: (r: Residence) => boolean;
}) {
    return (
        <div className="overflow-hidden rounded-brand border bg-white">
            <Table>
                <TableHeader>
                    <TableRow className="hover:bg-transparent">
                        <TableHead>Résidence</TableHead>
                        {colonnes.map((colonne) => (
                            <TableHead key={colonne.key} className={colonne.className?.includes('text-right') ? 'text-right' : undefined}>
                                {colonne.header}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {lignes.length === 0 ? (
                        <TableRow className="hover:bg-transparent">
                            <TableCell colSpan={colonnes.length + 1} className="h-24 text-center text-muted">
                                Aucune résidence.
                            </TableCell>
                        </TableRow>
                    ) : (
                        lignes.map((r) => (
                            <TableRow key={r.id} className={cn(ligneAlerte?.(r) && 'bg-[#FFF8E6] hover:bg-[#FFF3D6]')}>
                                <TableCell className="font-medium whitespace-nowrap text-gray-900">{r.name}</TableCell>
                                {colonnes.map((colonne) => (
                                    <TableCell key={colonne.key} className={cn('tabular-nums', colonne.className)}>
                                        {colonne.cell(r)}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))
                    )}
                </TableBody>
                {lignes.length > 1 && (
                    <TableFooter>
                        <TableRow className="hover:bg-transparent">
                            <TableCell className="font-medium whitespace-nowrap">{libelleTotal}</TableCell>
                            {colonnes.map((colonne) => (
                                <TableCell key={colonne.key} className={cn('font-medium tabular-nums', colonne.className)}>
                                    {colonne.total ?? ''}
                                </TableCell>
                            ))}
                        </TableRow>
                    </TableFooter>
                )}
            </Table>
            {note && <p className="border-t px-4 py-2.5 text-xs text-muted">{note}</p>}
        </div>
    );
}

function OrdresRecents({ ordres }: { ordres: OrdreRecent[] }) {
    return (
        <div>
            <h3 className="mb-2 text-sm font-medium text-gray-900">Dernières demandes d’intervention</h3>
            <div className="overflow-hidden rounded-brand border bg-white">
                <Table>
                    <TableHeader>
                        <TableRow className="hover:bg-transparent">
                            <TableHead>N° OT</TableHead>
                            <TableHead>Résidence</TableHead>
                            <TableHead>Catégorie</TableHead>
                            <TableHead>Priorité</TableHead>
                            <TableHead>Statut</TableHead>
                            <TableHead>Technicien</TableHead>
                            <TableHead>Créé le</TableHead>
                            <TableHead>Résolu le</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {ordres.map((ordre) => (
                            <TableRow key={ordre.id}>
                                <TableCell className="font-medium whitespace-nowrap text-gray-900">{ordre.numero}</TableCell>
                                <TableCell>{ordre.residence ?? <span className="text-muted">—</span>}</TableCell>
                                <TableCell>{ordre.categorie}</TableCell>
                                <TableCell>
                                    <Badge variant={ordre.priorite === 'critique' ? 'danger' : ordre.priorite === 'elevee' ? 'warning' : 'info'}>{ordre.priorite_label}</Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge variant={VARIANTES_STATUT_OT[ordre.statut] ?? 'neutral'}>{ordre.statut_label}</Badge>
                                </TableCell>
                                <TableCell>{ordre.assigne ?? <span className="text-muted">Non assigné</span>}</TableCell>
                                <TableCell className="whitespace-nowrap">{dateFr(ordre.date_creation)}</TableCell>
                                <TableCell className="whitespace-nowrap">{ordre.date_resolution ? dateFr(ordre.date_resolution) : <span className="text-muted">En attente</span>}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
