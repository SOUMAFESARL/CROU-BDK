import { Link } from '@inertiajs/react';
import { CalendarClock, CalendarDays, CirclePlay, Eye, FolderOpen, GraduationCap, Info, MapPin, UserPlus } from 'lucide-react';
import type { ReactNode } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { EtatDossierBadge } from '@/components/dossier/etat-dossier-badge';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { buttonVariants } from '@/components/ui/button';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { dateFr, initiales, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Filtre = 'nouvelles_demandes' | 'en_attente' | 'annuler' | 'en_verification' | 'residents';

type Dossier = {
    id: string;
    name: string;
    numero_demande: string | null;
    etat_dossier: string | null;
    date: string | null;
    personnel: { telephone: string | null; adresse: string | null; region: string | null; ville: string | null; date_naissance: string | null } | null;
    demandes: number | null;
    url: string | null;
};

type Props = {
    dossiers: Paginated<Dossier>;
    stats: { nouvelles_demandes: number; en_attente: number; annulees: number; residents: number; en_verification: number; total: number };
    etat: Filtre;
    colonnes: { personnel: boolean; historique: boolean };
    campagneActive: { annee: string; echeance: string | null; moyenne_min: number | string | null; distance_min: number | string | null } | null;
    urls: { campagnes: string | null; admissionDirecte: string | null; filtres: Record<Filtre, string> | null };
};

const TITRES: Record<Filtre, string> = {
    nouvelles_demandes: 'Nouvelles demandes',
    en_attente: 'Demandes en attente',
    annuler: 'Demandes rejetées',
    en_verification: 'Demandes en vérification',
    residents: 'Résidents',
};

/** Liste des dossiers de candidature (remplace `admission/admission.blade.php`). */
export default function AdmissionsIndex({ dossiers, stats, etat, colonnes, campagneActive, urls }: Props) {
    const vide = (valeur: string | null | undefined) => valeur || <span className="text-muted">—</span>;

    const colonnesTableau: DataTableColumn<Dossier>[] = [
        { key: 'num', header: '#', headClassName: 'w-12', className: 'text-muted tabular-nums', cell: (d) => (dossiers.from ?? 1) + dossiers.data.indexOf(d) },
        {
            key: 'etudiant',
            header: 'Étudiant',
            cell: (d) => (
                <div className="flex min-w-48 items-center gap-3">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-soft text-xs font-medium text-primary" aria-hidden>
                        {initiales(d.name)}
                    </span>
                    <div className="min-w-0">
                        <p className="font-medium text-gray-900">{d.name}</p>
                        {d.numero_demande && <p className="text-xs text-muted">Demande #{d.numero_demande}</p>}
                    </div>
                </div>
            ),
        },
        {
            key: 'date',
            header: 'Date de demande',
            className: 'whitespace-nowrap',
            cell: (d) => (
                <>
                    {dateFr(d.date)}
                    <p className="text-xs text-muted">{d.date?.slice(11, 16)}</p>
                </>
            ),
        },
        ...(colonnes.personnel
            ? ([
                  { key: 'telephone', header: 'Téléphone', className: 'whitespace-nowrap', cell: (d) => vide(d.personnel?.telephone) },
                  { key: 'adresse', header: 'Adresse', cell: (d) => vide(d.personnel?.adresse) },
                  {
                      key: 'region',
                      header: 'Région / Ville',
                      cell: (d) => (
                          <>
                              {vide(d.personnel?.region)}
                              {d.personnel?.ville && <p className="text-xs text-muted">{d.personnel.ville}</p>}
                          </>
                      ),
                  },
                  { key: 'naissance', header: 'Date de naissance', className: 'whitespace-nowrap', cell: (d) => dateFr(d.personnel?.date_naissance) },
              ] satisfies DataTableColumn<Dossier>[])
            : []),
        ...(colonnes.historique
            ? ([{ key: 'demandes', header: 'Demandes', className: 'text-center tabular-nums', headClassName: 'text-center', cell: (d) => nombre(d.demandes) }] satisfies DataTableColumn<Dossier>[])
            : []),
        { key: 'etat', header: 'État du dossier', cell: (d) => <EtatDossierBadge etat={d.etat_dossier} /> },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (d) =>
                d.url && (
                    <Link href={d.url} className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }), 'whitespace-nowrap')}>
                        <Eye className="size-4" aria-hidden /> Suivre le dossier
                    </Link>
                ),
        },
    ];

    return (
        <AdminLayout title="Admissions">
            <PageHeader
                title="Admissions"
                description="Suivez les demandes de logement et traitez chaque dossier de candidature."
                actions={
                    (urls.campagnes || urls.admissionDirecte) && (
                        <>
                            {urls.campagnes && (
                                <Link
                                    href={urls.campagnes}
                                    className="inline-flex h-10 items-center gap-2 rounded-brand border border-gray-300 bg-white px-4 text-sm font-medium text-muted hover:bg-surface hover:text-primary"
                                >
                                    <CalendarDays {...ICON} aria-hidden /> Campagnes
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

            {/* Bandeau de la campagne active */}
            <div className={cn('mb-6 flex flex-wrap items-center gap-x-6 gap-y-2 rounded-brand border px-4 py-3 text-sm', campagneActive ? 'border-[#C6E3A6] bg-soft text-[#004D00]' : 'bg-white text-muted')}>
                {campagneActive ? (
                    <>
                        <Element icone={<CirclePlay className="size-4" aria-hidden />}>
                            <strong className="font-medium">Campagne active {campagneActive.annee}</strong>
                        </Element>
                        {campagneActive.echeance && <Element icone={<CalendarClock className="size-4" aria-hidden />}>Échéance des demandes : {dateFr(campagneActive.echeance)}</Element>}
                        {campagneActive.moyenne_min && <Element icone={<GraduationCap className="size-4" aria-hidden />}>Moyenne min. {campagneActive.moyenne_min}/20</Element>}
                        {campagneActive.distance_min && <Element icone={<MapPin className="size-4" aria-hidden />}>Distance min. {campagneActive.distance_min} km</Element>}
                    </>
                ) : (
                    <Element icone={<Info className="size-4" aria-hidden />}>Aucune campagne active pour le moment.</Element>
                )}
            </div>

            {urls.filtres && (
                <KpiFilters
                    label="Filtrer les dossiers"
                    active={etat}
                    className="xl:grid-cols-3"
                    items={[
                        {
                            key: 'nouvelles_demandes',
                            label: 'Nouvelles demandes',
                            value: nombre(stats.nouvelles_demandes),
                            tone: 'blue',
                            href: urls.filtres.nouvelles_demandes,
                            extra: campagneActive ? `Campagne ${campagneActive.annee}` : 'Demandes reçues',
                        },
                        { key: 'en_attente', label: 'En attente', value: nombre(stats.en_attente), tone: 'amber', href: urls.filtres.en_attente, extra: 'Campagnes précédentes' },
                        { key: 'annuler', label: 'Demandes rejetées', value: nombre(stats.annulees), tone: 'plum', href: urls.filtres.annuler, extra: 'Refusées' },
                    ]}
                />
            )}

            <section className="mt-6">
                <div className="mb-3 flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-medium text-gray-900">{TITRES[etat] ?? 'Dossiers'}</h2>
                    <span className="text-sm text-muted">{dossiers.total} dossier(s)</span>
                </div>
                <DataTable
                    columns={colonnesTableau}
                    data={dossiers}
                    rowKey={(d) => d.id}
                    emptyMessage={
                        <span className="flex flex-col items-center gap-2">
                            <FolderOpen {...ICON} aria-hidden />
                            Aucun dossier trouvé pour ce filtre.
                        </span>
                    }
                />
            </section>
        </AdminLayout>
    );
}

function Element({ icone, children }: { icone: ReactNode; children: ReactNode }) {
    return (
        <span className="inline-flex items-center gap-1.5">
            {icone}
            {children}
        </span>
    );
}
