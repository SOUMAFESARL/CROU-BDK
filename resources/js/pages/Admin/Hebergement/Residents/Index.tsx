import { Link, router } from '@inertiajs/react';
import { BedDouble, CirclePlay, ClipboardCheck, Eye, FileText, ListChecks, Pencil, Search, UserPlus, Users, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Button, buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EtudiantEditDialog, type OptionsEtudiant, type ValeursEtudiant } from '@/components/residents/etudiant-edit-dialog';
import { dateFr, initiales, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Filtre = 'en_verification' | 'residents';

type Resident = {
    id: string;
    name: string;
    numero_demande: string | null;
    date: string | null;
    personnel: { telephone: string | null; adresse: string | null; region: string | null; ville: string | null; date_naissance: string | null } | null;
    demandes: number | null;
    chambre: { numero: string | null; lieu: string | null } | null;
    urls: { dossier: string | null; traiter: string | null; attribuer: string | null; contrat: string | null; modifier: string | null };
    /** Valeurs du formulaire de modification (envoyées seulement avec le droit de modifier). */
    edition: ValeursEtudiant | null;
};

type Props = {
    residents: Paginated<Resident>;
    stats: { residents: number; en_verification: number };
    etat: Filtre;
    /** `campagne` : campagne choisie dans le bandeau (`toutes` : aucun filtre). */
    filters: { q: string; campagne: string };
    colonnes: { personnel: boolean; historique: boolean };
    /** Campagnes proposées dans le bandeau, la plus récente d'abord. */
    campagnes: CampagneOption[];
    /** Campagne choisie (par défaut la campagne active ; `null` : toutes les campagnes). */
    campagneActive: { id: string; annee: string } | null;
    /** Référentiels du formulaire de modification (`null` sans le droit de modifier). */
    options: OptionsEtudiant | null;
    urls: { admissionDirecte: string | null; attributions: string | null; filtres: Record<Filtre, string> | null };
};

type CampagneOption = { id: string; libelle: string | null; annee: string | null; active: boolean };

/** Valeur du bandeau pour « Toutes les campagnes ». */
const TOUTES = 'toutes';

/** Libellé et année d'une campagne (liste du bandeau). */
const nomCampagne = (campagne: CampagneOption) => [campagne.libelle || 'Campagne', campagne.annee].filter(Boolean).join(' ');

const TITRES: Record<Filtre, string> = {
    en_verification: 'Admis à loger (liste définitive publiée)',
    residents: 'Résidents logés',
};

/** Résidents et contrats (remplace `resident_contrat/index.blade.php`). */
export default function ResidentsIndex({ residents, stats, etat, filters, colonnes, campagnes, campagneActive, options, urls }: Props) {
    const [recherche, setRecherche] = useState(filters.q);
    const [enEdition, setEnEdition] = useState<Resident | null>(null);
    // Objet stable : la fenêtre réinitialise son formulaire quand l'étudiant change.
    const etudiantEdite = useMemo(
        () => (enEdition?.urls.modifier && enEdition.edition ? { name: enEdition.name, url: enEdition.urls.modifier, valeurs: enEdition.edition } : null),
        [enEdition],
    );
    const premierRendu = useRef(true);

    // Rechargement de la liste (retour en page 1, filtre conservé) avec la recherche et la campagne choisie.
    const appliquer = (valeurs: { q: string; campagne: string }) => {
        const parametres = Object.fromEntries(Object.entries({ q: valeurs.q.trim(), campagne: valeurs.campagne }).filter(([, valeur]) => valeur !== ''));
        router.get(window.location.pathname, parametres, { preserveState: true, preserveScroll: true, replace: true });
    };

    // Recherche côté serveur, 350 ms après la dernière frappe.
    useEffect(() => {
        if (premierRendu.current) {
            premierRendu.current = false;
            return;
        }
        const minuteur = window.setTimeout(() => appliquer({ q: recherche, campagne: filters.campagne }), 350);
        return () => window.clearTimeout(minuteur);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [recherche]);

    const vide = (valeur: string | null | undefined) => valeur || <span className="text-muted">—</span>;
    // Vue « Résidents logés » allégée pour éviter le défilement horizontal du tableau.
    const vueResidents = etat === 'residents';

    const colonnesTableau: DataTableColumn<Resident>[] = [
        { key: 'num', header: '#', headClassName: 'w-12', className: 'text-muted tabular-nums', cell: (r) => (residents.from ?? 1) + residents.data.indexOf(r) },
        {
            key: 'etudiant',
            header: 'Étudiant',
            cell: (r) => (
                <div className="flex min-w-48 items-center gap-3">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-soft text-xs font-medium text-primary" aria-hidden>
                        {initiales(r.name)}
                    </span>
                    <div className="min-w-0">
                        <p className="font-medium text-gray-900">{r.name}</p>
                        {r.numero_demande && <p className="text-xs text-muted">Demande #{r.numero_demande}</p>}
                    </div>
                </div>
            ),
        },
        {
            key: 'date',
            header: 'Date de demande',
            className: 'whitespace-nowrap',
            cell: (r) => (
                <>
                    {dateFr(r.date)}
                    <p className="text-xs text-muted">{r.date?.slice(11, 16)}</p>
                </>
            ),
        },
        ...(colonnes.personnel
            ? ([
                  { key: 'telephone', header: 'Téléphone', className: 'whitespace-nowrap', cell: (r) => vide(r.personnel?.telephone) },
              ] satisfies DataTableColumn<Resident>[])
            : []),
        ...(colonnes.personnel && !vueResidents
            ? ([
                  { key: 'adresse', header: 'Adresse', cell: (r) => vide(r.personnel?.adresse) },
                  {
                      key: 'region',
                      header: 'Région / Ville',
                      cell: (r) => (
                          <>
                              {vide(r.personnel?.region)}
                              {r.personnel?.ville && <p className="text-xs text-muted">{r.personnel.ville}</p>}
                          </>
                      ),
                  },
                  { key: 'naissance', header: 'Date de naissance', className: 'whitespace-nowrap', cell: (r) => dateFr(r.personnel?.date_naissance) },
              ] satisfies DataTableColumn<Resident>[])
            : []),
        ...(colonnes.historique
            ? ([{ key: 'demandes', header: 'Demandes', className: 'text-center tabular-nums', headClassName: 'text-center', cell: (r) => nombre(r.demandes) }] satisfies DataTableColumn<Resident>[])
            : []),
        {
            key: 'chambre',
            header: 'Chambre',
            cell: (r) =>
                r.chambre ? (
                    <div className="min-w-28">
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-soft px-2.5 py-0.5 text-xs font-medium text-primary">
                            <BedDouble className="size-3.5" aria-hidden /> {r.chambre.numero ?? 'N/A'}
                        </span>
                        {r.chambre.lieu && <p className="mt-1 text-xs text-muted">{r.chambre.lieu}</p>}
                    </div>
                ) : (
                    <span className="inline-flex rounded-full bg-surface px-2.5 py-0.5 text-xs font-medium text-muted ring-1 ring-gray-200">Sans chambre</span>
                ),
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (r) => (
                <RowActions>
                    {r.urls.attribuer && <RowAction label="Attribuer une chambre" icon={BedDouble} href={r.urls.attribuer} tone="success" />}
                    {r.urls.traiter && <RowAction label="Traiter la demande" icon={ClipboardCheck} href={r.urls.traiter} tone="success" />}
                    {r.urls.modifier && r.edition && <RowAction label="Modifier les informations" icon={Pencil} onClick={() => setEnEdition(r)} tone="edit" />}
                    {r.urls.contrat && <RowAction label="Consulter le contrat (PDF)" icon={FileText} href={r.urls.contrat} newTab />}
                    {r.urls.dossier && <RowAction label="Voir le dossier" icon={Eye} href={r.urls.dossier} tone="view" />}
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Résidents & contrats">
            <PageHeader
                title="Résidents & contrats"
                description="Suivez les étudiants logés, leur chambre et leur contrat."
                aside={
                    // Les indicateurs et la liste suivent la campagne choisie (par défaut la campagne active).
                    campagnes.length > 0 && (
                        <Select value={filters.campagne || TOUTES} onValueChange={(campagne) => appliquer({ q: recherche, campagne })}>
                            <SelectTrigger aria-label="Campagne affichée" className="h-9 w-full border-[#C6E3A6] bg-soft text-[#004D00] sm:w-72">
                                <span className="flex min-w-0 items-center gap-1.5">
                                    <CirclePlay className="size-4 shrink-0" aria-hidden />
                                    <SelectValue />
                                </span>
                            </SelectTrigger>
                            <SelectContent align="end">
                                <SelectItem value={TOUTES}>Toutes les campagnes</SelectItem>
                                {campagnes.map((option) => (
                                    <SelectItem key={option.id} value={option.id}>
                                        {nomCampagne(option)}
                                        {option.active && <span className="text-muted"> · active</span>}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )
                }
                actions={
                    (urls.attributions || urls.admissionDirecte) && (
                        <>
                            {urls.attributions && (
                                <Link
                                    href={urls.attributions}
                                    className="inline-flex h-10 items-center gap-2 rounded-brand border border-gray-300 bg-white px-4 text-sm font-medium text-muted hover:bg-surface hover:text-primary"
                                >
                                    <ListChecks {...ICON} aria-hidden /> Suivi des attributions
                                </Link>
                            )}
                            {urls.admissionDirecte && (
                                <Link href={urls.admissionDirecte} className={cn(buttonVariants({ size: 'sm' }), 'h-10 px-4')}>
                                    <UserPlus {...ICON} aria-hidden /> Enregistrer un étudiant déjà logé
                                </Link>
                            )}
                        </>
                    )
                }
            />

            <KpiFilters
                label="Filtrer les étudiants"
                active={etat}
                ringActive={!vueResidents}
                className="xl:grid-cols-2"
                items={[
                    { key: 'residents', label: 'Résidents', value: nombre(stats.residents), tone: 'green', href: urls.filtres?.residents, extra: 'Déjà logés' },
                    {
                        key: 'en_verification',
                        label: 'Admis à loger',
                        value: nombre(stats.en_verification),
                        tone: 'blue',
                        href: urls.filtres?.en_verification,
                        extra: campagneActive ? `Campagne ${campagneActive.annee}` : 'Toutes campagnes',
                    },
                ]}
            />

            <section className="mt-6">
                <div className="mb-3 flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-medium text-gray-900">{TITRES[etat]}</h2>
                    <span className="text-sm text-muted">{nombre(residents.total)} étudiant(s)</span>
                </div>
                <DataTable
                    columns={colonnesTableau}
                    data={residents}
                    rowKey={(r) => r.id}
                    toolbar={
                        <>
                            <div className="w-full sm:max-w-sm">
                                <Input
                                    type="search"
                                    value={recherche}
                                    onChange={(event) => setRecherche(event.target.value)}
                                    placeholder="Rechercher par nom, n° de demande ou chambre…"
                                    aria-label="Rechercher un étudiant"
                                    icon={<Search />}
                                    className="h-10"
                                />
                            </div>
                            {filters.q && (
                                <Button variant="ghost" size="sm" className="h-10" onClick={() => setRecherche('')}>
                                    <X className="size-4" aria-hidden />
                                    Réinitialiser
                                </Button>
                            )}
                        </>
                    }
                    emptyMessage={
                        <span className="flex flex-col items-center gap-2">
                            <Users {...ICON} aria-hidden />
                            {filters.q ? 'Aucun étudiant ne correspond à cette recherche.' : 'Aucun étudiant trouvé pour ce filtre.'}
                        </span>
                    }
                />
            </section>

            {options && <EtudiantEditDialog etudiant={etudiantEdite} options={options} onClose={() => setEnEdition(null)} />}
        </AdminLayout>
    );
}
