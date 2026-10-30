import { Link, router } from '@inertiajs/react';
import { CalendarClock, CalendarDays, CircleCheck, CirclePlay, Eye, FolderOpen, GraduationCap, Info, MapPin, Search, X } from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { EtatDossierBadge } from '@/components/dossier/etat-dossier-badge';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Button, buttonVariants } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { dateFr, initiales, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Filtre = 'nouvelles_demandes' | 'en_attente' | 'annuler';

type Dossier = {
    id: string;
    name: string;
    numero_demande: string | null;
    demande_id: string | null;
    etat_dossier: string | null;
    date: string | null;
    personnel: { telephone: string | null; adresse: string | null; region: string | null; ville: string | null; date_naissance: string | null } | null;
    demandes: number | null;
    url: string | null;
    /** Refusé par le barème de scoring : peut être rendu éligible en un clic. */
    refus_automatique: boolean;
};

type Props = {
    dossiers: Paginated<Dossier>;
    stats: { nouvelles_demandes: number; en_attente: number; annulees: number; residents: number; en_verification: number; total: number };
    etat: Filtre;
    filters: { q: string; etat_dossier: string };
    /** États possibles de `Demande.etat_dossier` (`DossierWorkflow::LABELS`). */
    etatsDossier: { value: string; label: string }[];
    colonnes: { personnel: boolean; historique: boolean };
    campagneActive: { annee: string; echeance: string | null; moyenne_min: number | string | null; distance_min: number | string | null } | null;
    urls: { campagnes: string | null; leverRefus: string | null; filtres: Record<Filtre, string> | null };
};

const TITRES: Record<Filtre, string> = {
    nouvelles_demandes: 'Nouvelles demandes',
    en_attente: 'Demandes en attente',
    annuler: 'Demandes rejetées',
};

/**
 * Liste des demandes de logement (remplace `admission/admission.blade.php`).
 * Les étudiants déjà logés sont suivis sur la page « Résidents & contrats ».
 */
export default function AdmissionsIndex({ dossiers, stats, etat, filters, etatsDossier, colonnes, campagneActive, urls }: Props) {
    const [recherche, setRecherche] = useState(filters.q);
    /** Refus automatiques cochés (identifiants de demande), à rendre éligibles ensemble. */
    const [selection, setSelection] = useState<string[]>([]);
    /** Levée de refus automatiques à confirmer : un dossier, ou la `selection`. */
    const [aLever, setALever] = useState<Dossier | 'selection' | null>(null);
    const [levee, setLevee] = useState(false);
    const [motifLevee, setMotifLevee] = useState('');
    const [erreurLevee, setErreurLevee] = useState<string | null>(null);

    // Lignes cochables : refus prononcés par le barème (un refus d'agent passe par le réexamen).
    const selectionnables = urls.leverRefus ? dossiers.data.filter((d) => d.refus_automatique && d.demande_id).map((d) => d.demande_id as string) : [];
    const toutCoche = selectionnables.length > 0 && selectionnables.every((id) => selection.includes(id));
    const basculer = (id: string, coche: boolean) => setSelection((courante) => (coche ? [...new Set([...courante, id])] : courante.filter((x) => x !== id)));

    // La sélection ne vaut que pour la page affichée (changement de page, de filtre ou levée effectuée).
    useEffect(() => setSelection([]), [dossiers]);

    const leverRefus = async () => {
        if (!urls.leverRefus || !aLever) return;
        const demandes = aLever === 'selection' ? selection : aLever.demande_id ? [aLever.demande_id] : [];
        if (demandes.length === 0) return;
        // Dérogation au barème : motif obligatoire (le serveur le revérifie).
        if (motifLevee.trim() === '') {
            setErreurLevee('Le motif de la dérogation est obligatoire.');
            return;
        }
        setLevee(true);
        const ok = await envoyer(
            urls.leverRefus,
            { demandes, motif: motifLevee.trim() },
            { onError: (retour) => setErreurLevee(retour.motif ?? Object.values(retour)[0] ?? null) },
        );
        setLevee(false);
        if (ok) {
            setALever(null);
            setMotifLevee('');
        }
    };
    const premierRendu = useRef(true);

    // Recherche et filtre côté serveur (retour en page 1, onglet KPI conservé par l'URL).
    const appliquer = (valeurs: { q: string; etat_dossier: string }) => {
        const parametres = Object.fromEntries(Object.entries(valeurs).filter(([, valeur]) => valeur.trim() !== '').map(([cle, valeur]) => [cle, valeur.trim()]));
        router.get(window.location.pathname, parametres, { preserveState: true, preserveScroll: true, replace: true });
    };

    // Recherche lancée 350 ms après la dernière frappe.
    useEffect(() => {
        if (premierRendu.current) {
            premierRendu.current = false;
            return;
        }
        if (recherche.trim() === filters.q) return;
        const minuteur = window.setTimeout(() => appliquer({ q: recherche, etat_dossier: filters.etat_dossier }), 350);
        return () => window.clearTimeout(minuteur);
    }, [recherche]);

    const filtre = filters.q !== '' || filters.etat_dossier !== '';
    const vide = (valeur: string | null | undefined) => valeur || <span className="text-muted">—</span>;

    const colonnesTableau: DataTableColumn<Dossier>[] = [
        ...(selectionnables.length > 0
            ? [
                  {
                      key: 'selection',
                      header: (
                          <Checkbox
                              aria-label="Sélectionner tous les refus automatiques de la page"
                              checked={toutCoche ? true : selection.length > 0 ? 'indeterminate' : false}
                              onCheckedChange={(coche) => setSelection(coche === true ? selectionnables : [])}
                          />
                      ),
                      headClassName: 'w-10',
                      cell: (d: Dossier) =>
                          d.refus_automatique && d.demande_id ? (
                              <Checkbox
                                  aria-label={`Sélectionner ${d.name}`}
                                  checked={selection.includes(d.demande_id)}
                                  onCheckedChange={(coche) => basculer(d.demande_id as string, coche === true)}
                              />
                          ) : null,
                  },
              ]
            : []),
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
            ? ([{ key: 'demandes', header: 'Demandes (toutes campagnes)', className: 'text-center tabular-nums', headClassName: 'text-center', cell: (d) => nombre(d.demandes) }] satisfies DataTableColumn<Dossier>[])
            : []),
        { key: 'etat', header: 'État du dossier', cell: (d) => <EtatDossierBadge etat={d.etat_dossier} /> },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (d) => (
                <div className="flex justify-end gap-2">
                    {d.refus_automatique && d.demande_id && urls.leverRefus && (
                        <Button variant="secondary" size="sm" className="whitespace-nowrap" onClick={() => setALever(d)}>
                            <CircleCheck className="size-4" aria-hidden /> Rendre éligible
                        </Button>
                    )}
                    {d.url && (
                        <Link href={d.url} className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }), 'whitespace-nowrap')}>
                            <Eye className="size-4" aria-hidden /> Suivre le dossier
                        </Link>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title="Admissions">
            <PageHeader
                title="Admissions"
                description="Suivez les demandes de logement et traitez chaque dossier de candidature."
                actions={
                    urls.campagnes && (
                        <Link
                            href={urls.campagnes}
                            className={cn(buttonVariants({ variant: 'primary' }), 'h-10 px-4 font-medium shadow-sm')}
                        >
                            <CalendarDays {...ICON} aria-hidden /> Campagnes
                        </Link>
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
                        { key: 'en_attente', label: 'En attente', value: nombre(stats.en_attente), tone: 'amber', href: urls.filtres.en_attente, extra: 'Confirmation de l’étudiant' },
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
                    toolbar={
                        <div className="flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                            <div className="w-full sm:max-w-sm">
                                <Input
                                    type="search"
                                    value={recherche}
                                    onChange={(event) => setRecherche(event.target.value)}
                                    placeholder="Rechercher par nom, matricule, téléphone ou n° de demande…"
                                    aria-label="Rechercher une demande"
                                    icon={<Search />}
                                    className="h-10"
                                />
                            </div>
                            <Select
                                value={filters.etat_dossier || 'tous'}
                                onValueChange={(valeur) => appliquer({ q: recherche, etat_dossier: valeur === 'tous' ? '' : valeur })}
                            >
                                <SelectTrigger aria-label="Filtrer par statut de la demande" className="h-10 w-full sm:w-56">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="tous">Tous les statuts</SelectItem>
                                    {etatsDossier.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {filtre && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-10"
                                    onClick={() => {
                                        setRecherche('');
                                        appliquer({ q: '', etat_dossier: '' });
                                    }}
                                >
                                    <X className="size-4" aria-hidden />
                                    Réinitialiser
                                </Button>
                            )}
                            {urls.leverRefus && selection.length > 0 && (
                                <Button size="sm" className="h-10 sm:ml-auto" onClick={() => setALever('selection')}>
                                    <CircleCheck className="size-4" aria-hidden /> Rendre éligible la sélection ({nombre(selection.length)})
                                </Button>
                            )}
                        </div>
                    }
                    emptyMessage={
                        <span className="flex flex-col items-center gap-2">
                            <FolderOpen {...ICON} aria-hidden />
                            {filtre ? 'Aucune demande ne correspond à ces critères.' : 'Aucun dossier trouvé pour ce filtre.'}
                        </span>
                    }
                />
            </section>

            <ConfirmDialog
                open={aLever !== null}
                onOpenChange={(open) => {
                    if (open) return;
                    setALever(null);
                    setErreurLevee(null);
                }}
                title={aLever === 'selection' ? 'Rendre éligibles les dossiers sélectionnés ?' : 'Rendre ce dossier éligible ?'}
                description={
                    aLever === 'selection'
                        ? `${nombre(selection.length)} dossier(s) refusé(s) automatiquement (score insuffisant) passeront en « Éligible » ; chaque étudiant devra confirmer son intérêt.`
                        : `Le refus automatique de ${aLever?.name ?? ''} sera levé : le dossier passe en « Éligible » et l’étudiant devra confirmer son intérêt.`
                }
                confirmLabel="Rendre éligible"
                confirmIcon={<CircleCheck className="size-4" aria-hidden />}
                processing={levee}
                onConfirm={leverRefus}
            >
                <Field id="levee-motif" label="Motif de la dérogation" required error={erreurLevee ?? undefined} hint="2 000 caractères maximum.">
                    <Textarea
                        id="levee-motif"
                        rows={3}
                        maxLength={2000}
                        invalid={!!erreurLevee}
                        value={motifLevee}
                        onChange={(event) => {
                            setMotifLevee(event.target.value);
                            setErreurLevee(null);
                        }}
                    />
                </Field>
            </ConfirmDialog>
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
