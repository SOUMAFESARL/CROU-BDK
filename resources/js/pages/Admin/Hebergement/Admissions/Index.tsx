import { Link, router } from '@inertiajs/react';
import { CalendarClock, CircleCheck, CirclePlay, Download, Eye, FileSpreadsheet, FolderOpen, GraduationCap, Info, MapPin, Printer, Search, X } from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { OngletsCampagnesAdmissions } from '@/components/campagnes/onglets-campagnes-admissions';
import { OngletsRecevables, TableauClassement, type Classement } from '@/components/campagnes/liste-provisoire';
import { Badge } from '@/components/ui/badge';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Button, buttonVariants } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
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

/**
 * Toutes les demandes, demandes recevables (tous les critères conformes), demandes irrecevables (au moins un critère
 * non conforme), demandes refusées (refusées ou annulées par l'administration pendant la validation), et la liste
 * validée (onglet à part : demandes validées depuis une liste provisoire).
 */
type Filtre = 'toutes' | 'recevables' | 'rejetees' | 'refusees' | 'validees';

type Dossier = {
    id: string;
    name: string;
    numero_demande: string | null;
    demande_id: string | null;
    /** Volet de la demande dans sa campagne : admission (nouvel étudiant) ou réadmission (ancien résident). */
    type_demande: 'admission' | 'readmission' | null;
    etat_dossier: string | null;
    date: string | null;
    personnel: { region: string | null; ville: string | null } | null;
    demandes: number | null;
    url: string | null;
    /** Refusé par le barème de scoring : peut être rendu éligible en un clic. */
    refus_automatique: boolean;
};

type Props = {
    dossiers: Paginated<Dossier>;
    stats: Record<Filtre, number>;
    etat: Filtre;
    /** Demandes recevables seulement : onglets liste des demandes / listes provisoires d'admission et de réadmission. */
    classement: Classement | null;
    /** `type` : type de demande (admission ou réadmission), vide pour tous. */
    filters: { q: string; etat_dossier: string; type: string };
    /** États possibles de `Demande.etat_dossier` (`DossierWorkflow::LABELS`). */
    etatsDossier: { value: string; label: string }[];
    colonnes: { personnel: boolean; historique: boolean };
    campagneActive: { annee: string; echeance: string | null; moyenne_min: number | string | null; distance_min: number | string | null } | null;
    urls: { campagnes: string | null; leverRefus: string | null; filtres: Record<Filtre, string> | null };
};

/** Filtre « Type de demande » de la liste des demandes. */
const TYPES_DEMANDE = [
    { value: 'admission', label: 'Admission' },
    { value: 'readmission', label: 'Réadmission' },
];

const TITRES: Record<Filtre, string> = {
    toutes: 'Toutes les demandes',
    recevables: 'Demandes recevables',
    rejetees: 'Demandes irrecevables',
    refusees: 'Demandes refusées',
    validees: 'Liste validée',
};

/**
 * Liste des demandes de logement (remplace `admission/admission.blade.php`).
 * Les étudiants déjà logés sont suivis sur la page « Résidents & contrats ».
 */
export default function AdmissionsIndex({ dossiers, stats, etat, classement, filters, etatsDossier, colonnes, campagneActive, urls }: Props) {
    /** Onglet « Liste validée » : demandes validées depuis les listes provisoires (sans les indicateurs de la liste des demandes). */
    const validee = etat === 'validees';
    /** Onglet « Liste provisoire » ouvert (admission ou réadmission) ou liste validée : le classement remplace la liste des demandes. */
    const listeProvisoire = classement !== null && classement.liste !== 'demandes';
    const [recherche, setRecherche] = useState(filters.q);
    /** Onglet choisi (mis à jour dès le clic) ; il diffère de `classement.liste` le temps du chargement. */
    const [ongletVise, setOngletVise] = useState(classement?.liste ?? 'demandes');
    const changement = classement !== null && ongletVise !== classement.liste;
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

    // Nouvel onglet affiché : l'onglet choisi et la recherche suivent la réponse du serveur (la page est conservée).
    useEffect(() => {
        setOngletVise(classement?.liste ?? 'demandes');
        setRecherche(filters.q);
    }, [classement?.liste]);

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
    const appliquer = (valeurs: { q: string; etat_dossier: string; type?: string }) => {
        const parametres = Object.fromEntries(Object.entries(valeurs).filter(([, valeur]) => valeur.trim() !== '').map(([cle, valeur]) => [cle, valeur.trim()]));
        // L'onglet de liste provisoire ouvert est conservé.
        if (classement && classement.liste !== 'demandes') parametres.liste = classement.liste;
        router.get(window.location.pathname, parametres, { preserveState: true, preserveScroll: true, replace: true });
    };

    // Recherche lancée 350 ms après la dernière frappe.
    useEffect(() => {
        if (premierRendu.current) {
            premierRendu.current = false;
            return;
        }
        if (recherche.trim() === filters.q) return;
        const minuteur = window.setTimeout(() => appliquer({ q: recherche, etat_dossier: filters.etat_dossier, type: filters.type }), 350);
        return () => window.clearTimeout(minuteur);
    }, [recherche]);

    const filtre = filters.q !== '' || filters.etat_dossier !== '' || filters.type !== '';
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
              ] satisfies DataTableColumn<Dossier>[])
            : []),
        ...(colonnes.historique
            ? ([{ key: 'demandes', header: 'Demandes (toutes campagnes)', className: 'text-center tabular-nums', headClassName: 'text-center', cell: (d) => nombre(d.demandes) }] satisfies DataTableColumn<Dossier>[])
            : []),
        {
            key: 'type',
            header: 'Type de demande',
            cell: (d) =>
                d.type_demande ? (
                    <Badge variant={d.type_demande === 'readmission' ? 'info' : 'success'}>{d.type_demande === 'readmission' ? 'Réadmission' : 'Admission'}</Badge>
                ) : (
                    vide(null)
                ),
        },
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
                    {/* « Toutes les demandes » : pas de bouton « Suivre le dossier » (le clic sur la ligne ouvre le dossier). */}
                    {d.url && etat !== 'toutes' && (
                        <Link href={d.url} aria-label={`Suivre le dossier de ${d.name}`} title="Suivre le dossier" className={buttonVariants({ variant: 'ghost', size: 'icon' })}>
                            <Eye {...ICON} aria-hidden />
                        </Link>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title="Admissions">
            <PageHeader title="Admissions" description="Suivez les demandes de logement et traitez chaque dossier de candidature." />

            {/* Campagne active : à droite, sur la ligne des onglets Campagnes / Admissions. */}
            <OngletsCampagnesAdmissions
                actif={validee ? 'validees' : 'admissions'}
                urls={{ campagnes: urls.campagnes, admissions: urls.filtres?.toutes ?? null, validees: urls.filtres?.validees ?? null }}
                aside={
                    campagneActive ? (
                        <>
                            <span className="inline-flex items-center gap-1.5 rounded-brand border border-[#C6E3A6] bg-soft px-3 py-1 text-[#004D00]">
                                <CirclePlay className="size-4" aria-hidden />
                                <strong className="font-medium">Campagne active {campagneActive.annee}</strong>
                            </span>
                            {campagneActive.echeance && <Element icone={<CalendarClock className="size-4" aria-hidden />}>Échéance : {dateFr(campagneActive.echeance)}</Element>}
                            {campagneActive.moyenne_min && <Element icone={<GraduationCap className="size-4" aria-hidden />}>Moyenne min. {campagneActive.moyenne_min}/20</Element>}
                            {campagneActive.distance_min && <Element icone={<MapPin className="size-4" aria-hidden />}>Distance min. {campagneActive.distance_min} km</Element>}
                        </>
                    ) : (
                        <Element icone={<Info className="size-4" aria-hidden />}>Aucune campagne active pour le moment.</Element>
                    )
                }
            />

            {urls.filtres && !validee && (
                <KpiFilters
                    label="Filtrer les dossiers"
                    active={etat}
                    items={[
                        { key: 'toutes', label: 'Toutes les demandes', value: nombre(stats.toutes), tone: 'blue', href: urls.filtres.toutes, extra: 'Demandes reçues' },
                        { key: 'recevables', label: 'Demandes recevables', value: nombre(stats.recevables), tone: 'green', href: urls.filtres.recevables, extra: 'Tous les critères conformes' },
                        { key: 'rejetees', label: 'Demandes irrecevables', value: nombre(stats.rejetees), tone: 'plum', href: urls.filtres.rejetees, extra: 'Au moins un critère non conforme' },
                        { key: 'refusees', label: 'Demandes refusées', value: nombre(stats.refusees), tone: 'red', href: urls.filtres.refusees, extra: 'Par l’administration' },
                    ]}
                />
            )}

            <section className="mt-6">
                {classement ? (
                    // Demandes recevables : onglets à droite du titre, sur la même ligne.
                    <div className="mb-4 flex flex-wrap items-end gap-x-4 gap-y-1 border-b">
                        <h2 className="py-2.5 text-base font-medium text-gray-900">{TITRES[etat]}</h2>
                        <OngletsRecevables classement={classement} actif={ongletVise} onSelect={setOngletVise} />
                    </div>
                ) : (
                    <div className="mb-3 flex items-baseline justify-between gap-3">
                        <h2 className="text-base font-medium text-gray-900">{TITRES[etat] ?? 'Dossiers'}</h2>
                        <span className="text-sm text-muted">{dossiers.total} dossier(s)</span>
                    </div>
                )}
                {/* Changement d'onglet : contenu atténué pendant le chargement, puis fondu à l'arrivée du nouvel onglet. */}
                <div
                    key={classement?.liste ?? etat}
                    aria-busy={changement}
                    className={cn(
                        'transition-opacity duration-300 animate-in fade-in-0 slide-in-from-bottom-1 motion-reduce:animate-none motion-reduce:transition-none',
                        changement && 'pointer-events-none opacity-50',
                    )}
                >
                {classement && listeProvisoire ? (
                    <TableauClassement
                        classement={classement}
                        filtre={filters.q !== ''}
                        toolbar={
                            <div className="flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                                <div className="w-full sm:max-w-sm">
                                    <Input
                                        type="search"
                                        value={recherche}
                                        onChange={(event) => setRecherche(event.target.value)}
                                        placeholder="Rechercher par nom, matricule ou n° de demande…"
                                        aria-label="Rechercher dans la liste provisoire"
                                        icon={<Search />}
                                        className="h-10"
                                    />
                                </div>
                                {filters.q !== '' && (
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
                                {classement.exports && (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="secondary" className="h-10 sm:ml-auto" disabled={classement.compteurs[classement.liste] === 0}>
                                                <Download className="size-4" aria-hidden />
                                                Télécharger
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" className="w-52">
                                            <DropdownMenuItem asChild className="cursor-pointer">
                                                <a href={classement.exports.excel} download>
                                                    <FileSpreadsheet aria-hidden />
                                                    Excel (.xlsx)
                                                </a>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild className="cursor-pointer">
                                                <a href={classement.exports.pdf} target="_blank" rel="noopener">
                                                    <Printer aria-hidden />
                                                    Imprimer (PDF)
                                                </a>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                )}
                            </div>
                        }
                    />
                ) : (
                <DataTable
                    columns={colonnesTableau}
                    data={dossiers}
                    rowKey={(d) => d.id}
                    // Clic sur la ligne = « Suivre le dossier ».
                    rowHref={(d) => d.url}
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
                                onValueChange={(valeur) => appliquer({ q: recherche, etat_dossier: valeur === 'tous' ? '' : valeur, type: filters.type })}
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
                            <Select
                                value={filters.type || 'tous'}
                                onValueChange={(valeur) => appliquer({ q: recherche, etat_dossier: filters.etat_dossier, type: valeur === 'tous' ? '' : valeur })}
                            >
                                <SelectTrigger aria-label="Filtrer par type de demande" className="h-10 w-full sm:w-48">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="tous">Tous les types</SelectItem>
                                    {TYPES_DEMANDE.map((option) => (
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
                )}
                </div>
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
                        ? `${nombre(selection.length)} dossier(s) refusé(s) automatiquement (score insuffisant) passeront en « Recevable » ; chaque étudiant devra confirmer son intérêt.`
                        : `Le refus automatique de ${aLever?.name ?? ''} sera levé : le dossier passe en « Recevable » et l’étudiant devra confirmer son intérêt.`
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
        <span className="inline-flex items-center gap-1.5 text-muted">
            {icone}
            {children}
        </span>
    );
}
