import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Eye, MessageSquareWarning, PenLine, Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { STATUTS_RECLAMATION, StatutReclamationBadge, TraiterReclamationDialog, type Reclamation, type StatutReclamation } from '@/components/reclamations/shared';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Ligne = Reclamation & {
    /** Nom de la fiche étudiant (null si elle a été supprimée : le nom saisi est affiché). */
    etudiant: string | null;
    matricule: string | null;
    /** Date de naissance de la fiche étudiant. */
    date_naissance: string | null;
    /** Demande concernée (null si elle a été supprimée). */
    demande: { numero: string | null } | null;
    /** Fiche de la demande (tous les détails et le motif sous « Documents fournis »). */
    url_dossier: string | null;
};

type Filtres = { statut: StatutReclamation | ''; campagne: string; type: string; q: string };

type Props = {
    reclamations: Paginated<Ligne>;
    /** Nombre de réclamations par statut (clé vide : toutes). */
    compteurs: Record<StatutReclamation | '', number>;
    filters: Filtres;
    campagnes: { id: string; libelle: string }[];
    /** `index` : cette liste ; `accueil` : page « Réclamations » (cartes). */
    urls: { index: string; accueil: string };
};

const TOUS = 'tous';

const ONGLETS: { cle: StatutReclamation | ''; label: string }[] = [
    { cle: '', label: 'Toutes' },
    { cle: 'en_attente', label: STATUTS_RECLAMATION.en_attente.label },
    { cle: 'ouverte', label: 'Ouvertes' },
    { cle: 'acceptee', label: 'Acceptées' },
    { cle: 'rejetee', label: 'Rejetées' },
];

/** Réclamations des étudiants sur leur demande d'hébergement : un onglet par statut. */
export default function ReclamationsListe({ reclamations, compteurs, filters, campagnes, urls }: Props) {
    const [aTraiter, setATraiter] = useState<Ligne | null>(null);
    const [recherche, setRecherche] = useState(filters.q);
    const premierRendu = useRef(true);

    const filtrer = (valeurs: Partial<Filtres>) => {
        const suivants = { ...filters, q: recherche.trim(), ...valeurs };
        router.get(urls.index, Object.fromEntries(Object.entries(suivants).filter(([, valeur]) => valeur)), { preserveState: true, preserveScroll: true, replace: true });
    };
    const filtreActif = Boolean(filters.campagne || filters.type || filters.q);

    // Recherche lancée 350 ms après la dernière frappe.
    useEffect(() => {
        if (premierRendu.current) {
            premierRendu.current = false;
            return;
        }
        if (recherche.trim() === filters.q) return;
        const minuteur = window.setTimeout(() => filtrer({}), 350);
        return () => window.clearTimeout(minuteur);
    }, [recherche]);

    const colonnes: DataTableColumn<Ligne>[] = [
        {
            key: 'reclamation',
            header: 'Réclamation',
            cell: (r) => (
                <div className="whitespace-nowrap">
                    <p className="font-medium text-gray-900">{r.numero}</p>
                    <p className="text-xs text-muted">
                        {dateFr(r.date)} {r.date?.slice(11, 16)}
                    </p>
                </div>
            ),
        },
        {
            key: 'etudiant',
            header: 'Étudiant',
            cell: (r) => (
                <div className="min-w-40">
                    <p className="font-medium text-gray-900">{r.etudiant ?? r.nom_prenoms}</p>
                    <p className="text-xs text-muted">
                        {[r.date_naissance ? `Né(e) le ${dateFr(r.date_naissance)}` : null, r.matricule].filter(Boolean).join(' · ')}
                    </p>
                    {r.num_bac_matricule && r.num_bac_matricule !== r.matricule && <p className="text-xs text-muted">N° table bac / matricule saisi : {r.num_bac_matricule}</p>}
                </div>
            ),
        },
        {
            key: 'demande',
            header: 'Demande',
            cell: (r) => (
                <div className="min-w-44">
                    <p className="flex flex-wrap items-center gap-2">
                        <span className="font-medium text-gray-900">{r.demande?.numero ? `#${r.demande.numero}` : 'Demande supprimée'}</span>
                        <Badge variant={r.type_demande === 'readmission' ? 'info' : 'success'}>{r.type_demande === 'readmission' ? 'Réadmission' : 'Admission'}</Badge>
                    </p>
                </div>
            ),
        },
        {
            key: 'statut',
            header: 'Statut',
            cell: (r) => (
                <div className="flex flex-col gap-1">
                    <StatutReclamationBadge statut={r.statut} />
                    {r.traite_at && <p className="text-xs whitespace-nowrap text-muted">{[r.traite_par, dateFr(r.traite_at)].filter(Boolean).join(' · ')}</p>}
                </div>
            ),
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (r) => (
                <RowActions>
                    {r.url_dossier && <RowAction label="Voir les détails de la demande" icon={Eye} tone="view" onClick={() => router.visit(r.url_dossier!)} />}
                    {r.url_traiter && <RowAction label="Traiter la réclamation" icon={PenLine} tone="edit" onClick={() => setATraiter(r)} />}
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Liste des réclamations">
            <PageHeader
                title="Liste des réclamations"
                description="Réclamations déposées par les étudiants sur leur demande d’hébergement."
                actions={
                    <Link href={urls.accueil} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                        <ArrowLeft {...ICON} aria-hidden /> Retour aux réclamations
                    </Link>
                }
            />

            <Tabs value={filters.statut || TOUS} onValueChange={(valeur) => filtrer({ statut: valeur === TOUS ? '' : (valeur as StatutReclamation) })} className="mb-4">
                <TabsList aria-label="Réclamations par statut">
                    {ONGLETS.map((onglet) => (
                        <TabsTrigger key={onglet.cle || TOUS} value={onglet.cle || TOUS} aria-controls="liste-reclamations" className="focus-visible:ring-0">
                            {onglet.label}
                            <span className="rounded-sm bg-soft px-1.5 py-0.5 text-xs text-primary tabular-nums">{nombre(compteurs[onglet.cle])}</span>
                        </TabsTrigger>
                    ))}
                </TabsList>
            </Tabs>

            <div id="liste-reclamations">
                <DataTable
                    columns={colonnes}
                    data={reclamations}
                    rowKey={(r) => r.id}
                    rowHref={(r) => r.url_dossier}
                    toolbar={
                        <>
                            <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                                <Input
                                    type="search"
                                    aria-label="Rechercher une réclamation"
                                    placeholder="Nom, n° de table / matricule, de réclamation ou de demande"
                                    icon={<Search />}
                                    value={recherche}
                                    onChange={(event) => setRecherche(event.target.value)}
                                    className="h-10"
                                />
                                <Select value={filters.campagne || TOUS} onValueChange={(valeur) => filtrer({ campagne: valeur === TOUS ? '' : valeur })}>
                                    <SelectTrigger className="h-10" aria-label="Filtrer par campagne">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TOUS}>Toutes les campagnes</SelectItem>
                                        {campagnes.map((campagne) => (
                                            <SelectItem key={campagne.id} value={campagne.id}>
                                                {campagne.libelle}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={filters.type || TOUS} onValueChange={(valeur) => filtrer({ type: valeur === TOUS ? '' : valeur })}>
                                    <SelectTrigger className="h-10" aria-label="Filtrer par type de demande">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TOUS}>Tous les types de demande</SelectItem>
                                        <SelectItem value="admission">Admission</SelectItem>
                                        <SelectItem value="readmission">Réadmission</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            {filtreActif && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-10"
                                    onClick={() => {
                                        setRecherche('');
                                        filtrer({ campagne: '', type: '', q: '' });
                                    }}
                                >
                                    <X className="size-4" aria-hidden /> Réinitialiser
                                </Button>
                            )}
                        </>
                    }
                    emptyMessage={
                        <span className="flex flex-col items-center gap-2">
                            <MessageSquareWarning {...ICON} aria-hidden />
                            {filtreActif ? 'Aucune réclamation ne correspond à ces filtres.' : 'Aucune réclamation pour ce statut.'}
                        </span>
                    }
                />
            </div>

            <TraiterReclamationDialog reclamation={aTraiter} onClose={() => setATraiter(null)} />
        </AdminLayout>
    );
}
