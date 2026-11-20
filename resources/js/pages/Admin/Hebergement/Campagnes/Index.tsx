 import { Link, router } from '@inertiajs/react';
import { CalendarDays, CircleDot, Eye, Pencil, Plus, Search, SlidersHorizontal, Trash2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { Volet } from '@/components/campagnes/criteres';
import { CampagneDetailsSheet, StatutCampagneBadge } from '@/components/campagnes/details-sheet';
import { OngletsCampagnesAdmissions } from '@/components/campagnes/onglets-campagnes-admissions';
import { nomCampagne, STATUTS_CAMPAGNE, typeCampagne, type CampagneItem, type CampagnesProps, type FiltreCampagne, type StatutCampagne } from '@/components/campagnes/types';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { StatCard, type KpiTone } from '@/components/ui/stat-card';
import { cn } from '@/lib/utils';

const FILTRES: { filtre: FiltreCampagne; label: string; titre: string; stat: keyof CampagnesProps['stats']; tone: KpiTone }[] = [
    { filtre: 'tous', label: 'Toutes les campagnes', titre: 'Toutes les campagnes', stat: 'total', tone: 'blue' },
    { filtre: 'active', label: 'Actives', titre: 'Campagnes actives', stat: 'active', tone: 'green' },
    { filtre: 'planifier', label: 'Planifiées', titre: 'Campagnes planifiées', stat: 'planifier', tone: 'plum' },
    { filtre: 'terminer', label: 'Terminées', titre: 'Campagnes terminées', stat: 'terminer', tone: 'amber' },
];

/** Liste des campagnes d'admission et de réadmission (remplace la vue Blade `campagnes.blade.php`). */
export default function CampagnesIndex({ campagnes, stats, etat, filters, annees, urls }: CampagnesProps) {
    const [details, setDetails] = useState<CampagneItem | null>(null);
    const [suppression, setSuppression] = useState<CampagneItem | null>(null);
    const [traitement, setTraitement] = useState(false);
    const filtre = FILTRES.find((item) => item.filtre === etat) ?? FILTRES[0];
    const [recherche, setRecherche] = useState(filters.q);
    const premierRendu = useRef(true);

    // Recherche et année universitaire côté serveur (retour en page 1, statut conservé par le segment de l'URL).
    const appliquer = (valeurs: { q: string; annee: string }, chemin = window.location.pathname) => {
        const parametres: Record<string, string> = {};
        if (valeurs.q.trim() !== '') parametres.q = valeurs.q.trim();
        if (valeurs.annee !== '') parametres.annee = valeurs.annee;
        router.get(chemin, parametres, { preserveState: true, preserveScroll: true, replace: true });
    };
    // Les URL des statuts portent déjà la recherche et l'année : on ne garde que leur chemin.
    const cheminStatut = (statut: FiltreCampagne) => urls.filtres[statut].split('?')[0];

    // Recherche lancée 350 ms après la dernière frappe.
    useEffect(() => {
        if (premierRendu.current) {
            premierRendu.current = false;
            return;
        }
        if (recherche.trim() === filters.q) return;
        const minuteur = window.setTimeout(() => appliquer({ q: recherche, annee: filters.annee }), 350);
        return () => window.clearTimeout(minuteur);
    }, [recherche]);

    const filtreActif = filters.q !== '' || filters.annee !== '' || filtre.filtre !== 'tous';

    const colonnes: DataTableColumn<CampagneItem>[] = [
        {
            key: 'campagne',
            header: 'Campagne',
            cell: (campagne) => (
                <div className="min-w-44">
                    <p className="font-medium whitespace-nowrap text-gray-900">{nomCampagne(campagne)}</p>
                    {campagne.libelle && <p className="text-xs text-muted tabular-nums">Année {campagne.annee}</p>}
                    {campagne.description && <p className="line-clamp-1 max-w-72 text-xs text-muted">{campagne.description}</p>}
                </div>
            ),
        },
        { key: 'type', header: 'Type', cell: (campagne) => <Badge variant="success">{typeCampagne(campagne.conditions)}</Badge> },
        { key: 'statut', header: 'Statut', cell: (campagne) => <StatutCampagneBadge statut={campagne.statut} /> },
        {
            key: 'criteres',
            header: 'Critères',
            cell: (campagne) => {
                // Appliqués seulement si les critères de chaque volet ouvert par la campagne sont en vigueur.
                const etats = (['admission', 'readmission'] as Volet[]).map((volet) => campagne.criteres_etat[volet]).filter(Boolean);
                const appliques = etats.length > 0 && etats.every((etat) => etat!.etat === 'en_vigueur');
                return <Badge variant={appliques ? 'success' : 'warning'}>{appliques ? 'Appliqués' : 'Non appliqués'}</Badge>;
            },
        },
        {
            key: 'residences',
            header: 'Résidences (places libres)',
            cell: (campagne) => <Puces valeurs={campagne.residences.map((residence) => `${residence.name} (${residence.places})`)} variant="success" />,
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            headClassName: 'w-40',
            className: 'text-right',
            cell: (campagne) => (
                <RowActions>
                    <RowAction label="Voir le détail" icon={Eye} tone="view" onClick={() => setDetails(campagne)} />
                    {campagne.urls.criteres && (
                        <RowAction label="Paramétrer les critères" icon={SlidersHorizontal} tone="view" onClick={() => router.visit(campagne.urls.criteres!)} />
                    )}
                    {campagne.urls.edit && (
                        <RowAction label="Modifier la campagne" icon={Pencil} tone="edit" onClick={() => router.visit(campagne.urls.edit!)} />
                    )}
                    {campagne.urls.destroy && (
                        <RowAction label="Supprimer" icon={Trash2} tone="danger" onClick={() => setSuppression(campagne)} />
                    )}
                </RowActions>
            ),
        },
    ];

    const supprimer = () => {
        if (!suppression?.urls.destroy) return;
        router.delete(suppression.urls.destroy, {
            preserveScroll: true,
            onStart: () => setTraitement(true),
            onFinish: () => {
                setTraitement(false);
                setSuppression(null);
            },
        });
    };

    return (
        <AdminLayout title="Campagnes">
            <PageHeader title="Campagnes" description="Planifiez les campagnes d’admission et de réadmission, leurs critères et les résidences ouvertes." />

            <OngletsCampagnesAdmissions actif="campagnes" urls={{ campagnes: urls.filtres.tous, admissions: urls.admissions, validees: urls.validees }} />

            {urls.create && (
                <div className="mb-6 flex flex-wrap items-center justify-end gap-2">
                    <Link href={urls.create} className={cn(buttonVariants({ size: 'sm' }), 'h-10 px-4')}>
                        <Plus {...ICON} aria-hidden />
                        Nouvelle campagne
                    </Link>
                </div>
            )}

            {/* Indicateurs par statut, qui servent aussi de filtres. */}
            <nav aria-label="Filtrer les campagnes par statut" className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {FILTRES.map((item) => {
                    const actif = item.filtre === filtre.filtre;
                    return (
                        <Link
                            key={item.filtre}
                            href={urls.filtres[item.filtre]}
                            preserveScroll
                            aria-current={actif ? 'page' : undefined}
                            className={cn(
                                'rounded-md transition-shadow focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                                actif ? 'ring-2 ring-primary ring-offset-2' : 'hover:shadow-sm',
                            )}
                        >
                            <StatCard tone={item.tone} label={item.label} value={stats[item.stat] ?? 0} />
                        </Link>
                    );
                })}
            </nav>

            <section className="mt-6">
                <div className="mb-3 flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-medium text-gray-900">{filtre.titre}</h2>
                    <span className="text-sm text-muted">{campagnes.total} campagne(s)</span>
                </div>
                <DataTable
                    columns={colonnes}
                    data={campagnes}
                    rowKey={(campagne) => campagne.id}
                    emptyMessage={filters.q !== '' || filters.annee !== '' ? 'Aucune campagne ne correspond à votre recherche.' : 'Aucune campagne trouvée pour ce filtre.'}
                    toolbar={
                        <div className="flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                            <div className="w-full sm:max-w-sm">
                                <Input
                                    type="search"
                                    value={recherche}
                                    onChange={(event) => setRecherche(event.target.value)}
                                    placeholder="Rechercher par libellé, année ou description…"
                                    aria-label="Rechercher une campagne"
                                    icon={<Search />}
                                    className="h-10"
                                />
                            </div>
                            <Select value={filters.annee || 'toutes'} onValueChange={(valeur) => appliquer({ q: recherche, annee: valeur === 'toutes' ? '' : valeur })}>
                                <SelectTrigger aria-label="Filtrer par année universitaire" className="h-10 w-full sm:w-60">
                                    <CalendarDays {...ICON} className="text-muted" aria-hidden />
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="toutes">Toutes les années universitaires</SelectItem>
                                    {annees.map((annee) => (
                                        <SelectItem key={annee} value={annee}>
                                            {annee}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filtre.filtre}
                                onValueChange={(valeur) => appliquer({ q: recherche, annee: filters.annee }, cheminStatut(valeur as FiltreCampagne))}
                            >
                                <SelectTrigger aria-label="Filtrer par statut" className="h-10 w-full sm:w-48">
                                    <CircleDot {...ICON} className="text-muted" aria-hidden />
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="tous">Tous les statuts</SelectItem>
                                    {(Object.keys(STATUTS_CAMPAGNE) as StatutCampagne[]).map((statut) => (
                                        <SelectItem key={statut} value={statut}>
                                            {STATUTS_CAMPAGNE[statut].label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {filtreActif && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-10"
                                    onClick={() => {
                                        setRecherche('');
                                        appliquer({ q: '', annee: '' }, cheminStatut('tous'));
                                    }}
                                >
                                    <X className="size-4" aria-hidden />
                                    Réinitialiser
                                </Button>
                            )}
                        </div>
                    }
                />
            </section>

            <CampagneDetailsSheet campagne={details} onClose={() => setDetails(null)} />

            <ConfirmDialog
                open={suppression !== null}
                onOpenChange={(ouvert) => !ouvert && setSuppression(null)}
                title="Supprimer cette campagne ?"
                description={
                    suppression && (
                        <>
                            La campagne « {nomCampagne(suppression)} » ne sera plus proposée aux étudiants. Une campagne pour laquelle des demandes ont déjà été déposées
                            ne peut pas être supprimée.
                        </>
                    )
                }
                confirmLabel="Supprimer"
                confirmIcon={<Trash2 className="size-4" aria-hidden />}
                danger
                processing={traitement}
                onConfirm={supprimer}
            />
        </AdminLayout>
    );
}

/** Pastilles d'une cellule (résidences), avec « +n » au-delà. */
function Puces({ valeurs, reste = 0, variant }: { valeurs: string[]; reste?: number; variant: 'success' | 'info' }) {
    if (valeurs.length === 0) return <span className="text-muted">—</span>;

    return (
        <div className="flex max-w-80 flex-wrap gap-1">
            {valeurs.map((valeur, index) => (
                <Badge key={`${valeur}-${index}`} variant={variant}>
                    {valeur}
                </Badge>
            ))}
            {reste > 0 && <Badge variant="neutral">+{reste}</Badge>}
        </div>
    );
}
