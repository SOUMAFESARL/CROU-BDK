import { router } from '@inertiajs/react';
import { BedDouble, ChevronDown, ClipboardCheck, Download, ExternalLink, FileSpreadsheet, FileText, ListChecks, Eye, EyeOff, Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { EtatDossierBadge } from '@/components/dossier/etat-dossier-badge';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { CampagnesVisiblesDialog, type CampagneResultats } from '@/components/resultats/campagnes-visibles-dialog';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { envoyer } from '@/lib/inertia-form';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Statut = 'validees' | 'attente' | 'non_recevables';

type Ligne = {
    id: string;
    /** Null si la fiche de l'étudiant a été supprimée. */
    etudiant: string | null;
    matricule: string | null;
    num_table_bac: string | null;
    numero_demande: string | null;
    type: string;
    etat_dossier: string | null;
    /** Admission remise en cause depuis la publication : résultat plus affiché à l'étudiant. */
    remise_en_cause: boolean;
    publie_at: string | null;
    url_dossier: string | null;
    /** File d'attente : passage dans « Admis à loger » ; null sans le droit ou si la demande ne peut pas être admise. */
    url_admettre: string | null;
};

type Filtres = { statut: Statut; campagne: string; type: string; q: string };

type Props = {
    resultats: Paginated<Ligne>;
    compteurs: Record<Statut, number>;
    filters: Filtres;
    /** Campagnes dont des résultats sont publiés, la dernière publiée d'abord. */
    campagnes: CampagneResultats[];
    /** `visible` : au moins une campagne visible ; `url` : changement de visibilité (null sans le droit) ; `page` : page publique des résultats. */
    visibilite: { visible: boolean; url: string | null; page: string };
    /** Téléchargement de l'onglet affiché (filtres compris) ; null sans le droit ou sans demande. */
    exports: { excel: string; pdf: string } | null;
    urls: { index: string };
};

const TOUS = 'tous';

const ONGLETS: { cle: Statut; label: string }[] = [
    { cle: 'validees', label: 'Demandes validées' },
    { cle: 'attente', label: 'File d’attente' },
    { cle: 'non_recevables', label: 'Non recevables / rejetées' },
];

/** Résultats d'admission publiés, un onglet par statut, et visibilité de la page publique `/resultats`. */
export default function ResultatsIndex({ resultats, compteurs, filters, campagnes, visibilite, exports, urls }: Props) {
    const [recherche, setRecherche] = useState(filters.q);
    const [aConfirmer, setAConfirmer] = useState(false);
    const [choixCampagnes, setChoixCampagnes] = useState(false);
    const [envoi, setEnvoi] = useState(false);
    const [aAdmettre, setAAdmettre] = useState<Ligne | null>(null);
    const [erreurAdmission, setErreurAdmission] = useState<string | null>(null);
    const premierRendu = useRef(true);

    const filtrer = (valeurs: Partial<Filtres>) => {
        const suivants = { ...filters, q: recherche.trim(), ...valeurs };
        router.get(urls.index, Object.fromEntries(Object.entries(suivants).filter(([, valeur]) => valeur)), { preserveState: true, preserveScroll: true, replace: true });
    };
    const filtreActif = Boolean(filters.type || filters.q);

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

    const masquer = () => {
        if (!visibilite.url) return;
        router.put(
            visibilite.url,
            { visible: false },
            {
                preserveScroll: true,
                onStart: () => setEnvoi(true),
                onFinish: () => {
                    setEnvoi(false);
                    setAConfirmer(false);
                },
            },
        );
    };

    const admettre = async () => {
        if (!aAdmettre?.url_admettre) return;
        setEnvoi(true);
        const ok = await envoyer(aAdmettre.url_admettre, {}, { onError: (erreurs) => setErreurAdmission(Object.values(erreurs)[0] ?? null) });
        setEnvoi(false);
        if (ok) setAAdmettre(null);
    };

    const colonnes: DataTableColumn<Ligne>[] = [
        {
            key: 'etudiant',
            header: 'Étudiant',
            cell: (r) => (
                <div className="min-w-40">
                    <p className="font-medium text-gray-900">{r.etudiant ?? 'Étudiant supprimé'}</p>
                    <p className="text-xs text-muted">
                        {[r.matricule, r.num_table_bac && r.num_table_bac !== r.matricule ? `N° table bac : ${r.num_table_bac}` : null].filter(Boolean).join(' · ')}
                    </p>
                </div>
            ),
        },
        {
            key: 'demande',
            header: 'Demande',
            cell: (r) => (
                <p className="flex min-w-40 flex-wrap items-center gap-2">
                    <span className="font-medium text-gray-900">{r.numero_demande ? `#${r.numero_demande}` : '—'}</span>
                    <Badge variant={r.type === 'readmission' ? 'info' : 'success'}>{r.type === 'readmission' ? 'Réadmission' : 'Admission'}</Badge>
                </p>
            ),
        },
        {
            key: 'etat',
            header: 'État du dossier',
            cell: (r) => (
                <div className="flex flex-col gap-1">
                    {r.etat_dossier ? <EtatDossierBadge etat={r.etat_dossier} /> : '—'}
                    {r.remise_en_cause && <p className="text-xs text-red-700">Modifié depuis la publication : résultat non affiché à l’étudiant</p>}
                </div>
            ),
        },
        {
            key: 'publie',
            header: 'Publié le',
            cell: (r) => <span className="whitespace-nowrap">{[dateFr(r.publie_at), r.publie_at?.slice(11, 16)].filter(Boolean).join(' ')}</span>,
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (r) => (
                <RowActions>
                    {r.url_admettre && (
                        <RowAction
                            label="Valider la demande"
                            icon={BedDouble}
                            tone="success"
                            onClick={() => {
                                setErreurAdmission(null);
                                setAAdmettre(r);
                            }}
                        />
                    )}
                    {r.url_dossier && <RowAction label="Voir les détails de la demande" icon={Eye} tone="view" onClick={() => router.visit(r.url_dossier!)} />}
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Résultats d’admission">
            <PageHeader
                title="Résultats d’admission"
                actions={
                    <>
                        {exports && (
                            <DropdownMenu modal={false}>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" size="sm" className="h-10 px-4">
                                        <Download {...ICON} aria-hidden /> Exporter <ChevronDown {...ICON} aria-hidden />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-48">
                                    <DropdownMenuItem asChild className="cursor-pointer">
                                        <a href={exports.excel}>
                                            <FileSpreadsheet {...ICON} aria-hidden /> Excel
                                        </a>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild className="cursor-pointer">
                                        <a href={exports.pdf} target="_blank" rel="noreferrer">
                                            <FileText {...ICON} aria-hidden /> PDF
                                        </a>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}
                        {visibilite.visible && (
                            <a href={visibilite.page} target="_blank" rel="noreferrer" className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                                <ExternalLink {...ICON} aria-hidden /> Voir la page
                            </a>
                        )}
                        {visibilite.url && visibilite.visible && (
                            <Button variant="outline" size="sm" className="h-10 px-4" onClick={() => setChoixCampagnes(true)}>
                                <ListChecks {...ICON} aria-hidden /> Campagnes visibles
                            </Button>
                        )}
                        {visibilite.url && (
                            <Button size="sm" className="h-10 px-4" onClick={() => (visibilite.visible ? setAConfirmer(true) : setChoixCampagnes(true))}>
                                {visibilite.visible ? <EyeOff {...ICON} aria-hidden /> : <Eye {...ICON} aria-hidden />}
                                {visibilite.visible ? 'Masquer la page' : 'Rendre la page visible'}
                            </Button>
                        )}
                    </>
                }
            />

            <Tabs value={filters.statut} onValueChange={(valeur) => filtrer({ statut: valeur as Statut })} className="mb-4">
                <TabsList aria-label="Demandes par statut">
                    {ONGLETS.map((onglet) => (
                        <TabsTrigger key={onglet.cle} value={onglet.cle} aria-controls="liste-resultats" className="focus-visible:ring-0">
                            {onglet.label}
                            <span className="rounded-sm bg-soft px-1.5 py-0.5 text-xs text-primary tabular-nums">{nombre(compteurs[onglet.cle])}</span>
                        </TabsTrigger>
                    ))}
                </TabsList>
            </Tabs>

            <div id="liste-resultats">
                <DataTable
                    columns={colonnes}
                    data={resultats}
                    rowKey={(r) => r.id}
                    rowHref={(r) => r.url_dossier}
                    toolbar={
                        <>
                            <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                                <Input
                                    type="search"
                                    aria-label="Rechercher une demande"
                                    placeholder="Nom, matricule, n° de table ou de demande"
                                    icon={<Search />}
                                    value={recherche}
                                    onChange={(event) => setRecherche(event.target.value)}
                                    className="h-10"
                                />
                                {campagnes.length > 0 && (
                                    <Select value={filters.campagne} onValueChange={(valeur) => filtrer({ campagne: valeur })}>
                                        <SelectTrigger className="h-10" aria-label="Campagne">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {campagnes.map((campagne) => (
                                                <SelectItem key={campagne.id} value={campagne.id}>
                                                    {campagne.libelle}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
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
                                        filtrer({ type: '', q: '' });
                                    }}
                                >
                                    <X className="size-4" aria-hidden /> Réinitialiser
                                </Button>
                            )}
                        </>
                    }
                    emptyMessage={
                        <span className="flex flex-col items-center gap-2">
                            <ClipboardCheck {...ICON} aria-hidden />
                            {filtreActif
                                ? 'Aucune demande ne correspond à ces filtres.'
                                : campagnes.length > 0
                                  ? 'Aucune demande pour ce statut.'
                                  : 'Aucun résultat publié : les demandes apparaissent après la publication des listes définitives.'}
                        </span>
                    }
                />
            </div>

            <ConfirmDialog
                open={aConfirmer}
                onOpenChange={setAConfirmer}
                title="Masquer la page des résultats ?"
                description="Les étudiants ne pourront plus consulter leur résultat et le lien disparaîtra de la page de connexion."
                confirmLabel="Masquer la page"
                confirmIcon={<EyeOff {...ICON} aria-hidden />}
                onConfirm={masquer}
                processing={envoi}
            />
            <ConfirmDialog
                open={aAdmettre !== null}
                onOpenChange={(ouvert) => !ouvert && setAAdmettre(null)}
                title="Valider cette demande ?"
                description={
                    aAdmettre && (
                        <>
                            {aAdmettre.etudiant ?? 'L’étudiant'}
                            {aAdmettre.numero_demande && ` (demande #${aAdmettre.numero_demande})`} quitte la file d’attente et passe dans « Admis à loger » : son paiement puis
                            l’attribution d’une chambre seront possibles. Son résultat devient « Admis ».
                        </>
                    )
                }
                confirmLabel="Valider la demande"
                confirmIcon={<BedDouble {...ICON} aria-hidden />}
                onConfirm={admettre}
                processing={envoi}
            >
                {erreurAdmission && (
                    <p role="alert" className="text-sm text-red-700">
                        {erreurAdmission}
                    </p>
                )}
            </ConfirmDialog>
            {visibilite.url && <CampagnesVisiblesDialog open={choixCampagnes} onClose={() => setChoixCampagnes(false)} campagnes={campagnes} url={visibilite.url} />}
        </AdminLayout>
    );
}
