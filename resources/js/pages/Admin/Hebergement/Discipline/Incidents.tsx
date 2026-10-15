import { router } from '@inertiajs/react';
import { FilePlus2, FolderOpen, Gavel, Lock as LockIcon, Scale, ShieldAlert, X } from 'lucide-react';
import { Fragment, useState } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { GRAVITES, RapportDialog } from '@/components/discipline/rapport-dialog';
import { ACTIONS, TransitionDialog, type ActionRapport } from '@/components/discipline/transition-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet';
import { StatCard } from '@/components/ui/stat-card';
import { Chronologie, dateHeure, type Structure, type Variante } from '@/components/vie-residentielle/shared';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Rapport = {
    id: string;
    numero: string;
    date_incident: string | null;
    source: string;
    confidentiel: boolean;
    resident: string | null;
    matricule: string | null;
    localisation: string;
    nature: string;
    description: string;
    lieu: string | null;
    temoins: string | null;
    gravite: string;
    statut: string;
    statut_label: string;
    attente: string | null;
    transmis_at: string | null;
    pris_en_charge_at: string | null;
    decision: string | null;
    sanction: { type: string; etudiant: string | null; statut: string; date_fin: string | null } | null;
    historique: { id: string; statut: string; date: string | null; commentaire: string | null }[];
    actions: ActionRapport[];
    url_transition: string | null;
};

type Filtres = { statut: string; gravite: string; source: string };

type Props = {
    rapports: Paginated<Rapport>;
    stats: { brouillons: number; transmis: number; en_cours: number; decides: number };
    filters: Filtres;
    structure: Structure;
    sanctions: { value: string; label: string }[];
    urls: { index: string; store: string | null; sanctions: string | null };
};

const TOUS = 'tous';

const STATUTS: { value: string; label: string; variante: Variante }[] = [
    { value: 'brouillon', label: 'Brouillon', variante: 'neutral' },
    { value: 'transmis', label: 'Transmis', variante: 'warning' },
    { value: 'en_cours', label: 'En cours', variante: 'info' },
    { value: 'retourne', label: 'Retourné', variante: 'danger' },
    { value: 'decision_prise', label: 'Décision prise', variante: 'info' },
    { value: 'cloture', label: 'Clôturé', variante: 'success' },
];

const VARIANTES_GRAVITE: Record<string, Variante> = { faible: 'success', moyenne: 'warning', grave: 'danger', critique: 'danger' };

const ETAPES_WORKFLOW = [
    { titre: 'Déclaration', texte: 'Étudiant ou administration' },
    { titre: 'Transmission', texte: 'File du Service Sécurité' },
    { titre: 'Prise en charge', texte: 'Analyse et compléments' },
    { titre: 'Décision', texte: 'Notification et clôture' },
];

/** Rapports disciplinaires et workflow du Service Sécurité (remplace `vie_residentielle/incidents/index.blade.php`). */
export default function IncidentsIndex({ rapports, stats, filters, structure, sanctions, urls }: Props) {
    const [nouveau, setNouveau] = useState(false);
    const [detail, setDetail] = useState<Rapport | null>(null);
    const [transition, setTransition] = useState<{ rapport: Rapport; action: ActionRapport } | null>(null);

    const filtrer = (cle: keyof Filtres, valeur: string) => {
        const suivants = { ...filters, [cle]: valeur === TOUS ? '' : valeur };
        router.get(urls.index, Object.fromEntries(Object.entries(suivants).filter(([, v]) => v)), { preserveState: true, preserveScroll: true, replace: true });
    };
    const filtreActif = Boolean(filters.statut || filters.gravite || filters.source);
    const statut = (valeur: string) => STATUTS.find((s) => s.value === valeur);

    const colonnes: DataTableColumn<Rapport>[] = [
        {
            key: 'rapport',
            header: 'Rapport',
            cell: (r) => (
                <div className="whitespace-nowrap">
                    <p className="font-medium text-gray-900">{r.numero}</p>
                    <p className="text-xs text-muted">{dateHeure(r.date_incident)}</p>
                    <p className="flex items-center gap-1 text-xs text-primary">
                        {r.source}
                        {r.confidentiel && (
                            <>
                                <span aria-hidden>·</span>
                                <LockIcon className="size-3" aria-hidden /> Confidentiel
                            </>
                        )}
                    </p>
                </div>
            ),
        },
        {
            key: 'resident',
            header: 'Résident',
            cell: (r) => (
                <div className="min-w-40">
                    <p className="font-medium text-gray-900">{r.resident ?? 'Inconnu'}</p>
                    <p className="text-xs text-muted">{r.localisation || '—'}</p>
                </div>
            ),
        },
        {
            key: 'incident',
            header: 'Incident',
            cell: (r) => (
                <div className="max-w-64">
                    <p className="font-medium text-gray-900">{r.nature}</p>
                    <p className="line-clamp-2 text-xs text-muted">{r.description}</p>
                </div>
            ),
        },
        { key: 'gravite', header: 'Gravité', cell: (r) => <Badge variant={VARIANTES_GRAVITE[r.gravite] ?? 'neutral'}>{GRAVITES.find((g) => g.value === r.gravite)?.label ?? r.gravite}</Badge> },
        {
            key: 'statut',
            header: 'Statut',
            cell: (r) => (
                <div className="flex flex-col gap-1">
                    <Badge variant={statut(r.statut)?.variante ?? 'neutral'}>{r.statut_label}</Badge>
                    {r.attente && <span className="text-xs font-medium text-red-600">En attente depuis {r.attente}</span>}
                </div>
            ),
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (r) => (
                <div className="flex flex-wrap items-center justify-end gap-1.5">
                    {r.actions.map((action) => {
                        const config = ACTIONS[action];
                        const Icone = config.icone;
                        return (
                            <Button
                                key={action}
                                variant={action === 'retourner' ? 'outline' : 'secondary'}
                                size="sm"
                                className="h-8 px-2.5 text-xs"
                                onClick={() => setTransition({ rapport: r, action })}
                            >
                                <Icone className="size-3.5" aria-hidden /> {config.label}
                            </Button>
                        );
                    })}
                    <RowActions>
                        <RowAction label="Voir le dossier" icon={FolderOpen} tone="view" onClick={() => setDetail(r)} />
                    </RowActions>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title="Rapports disciplinaires">
            <PageHeader
                title="Rapports disciplinaires"
                description="Rédiger, transmettre au Service Sécurité et suivre chaque rapport jusqu’à la décision. Chaque étape est tracée dans la piste d’audit."
                actions={
                    (urls.sanctions || urls.store) && (
                        <>
                            {urls.sanctions && (
                                <a href={urls.sanctions} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                                    <Scale {...ICON} aria-hidden /> Sanctions
                                </a>
                            )}
                            {urls.store && (
                                <Button size="sm" className="h-10 px-4" onClick={() => setNouveau(true)}>
                                    <FilePlus2 {...ICON} aria-hidden /> Nouveau rapport
                                </Button>
                            )}
                        </>
                    )
                }
            />

            <ol aria-label="Étapes du traitement" className="mb-4 grid grid-cols-2 gap-2 rounded-brand border bg-white p-3 lg:grid-cols-4">
                {ETAPES_WORKFLOW.map((etape, index) => (
                    <li key={etape.titre} className="flex items-center gap-2.5">
                        <span className="flex size-7 shrink-0 items-center justify-center rounded-full bg-soft text-xs font-medium text-primary">{index + 1}</span>
                        <span className="min-w-0">
                            <span className="block truncate text-sm font-medium text-gray-900">{etape.titre}</span>
                            <span className="block truncate text-xs text-muted">{etape.texte}</span>
                        </span>
                    </li>
                ))}
            </ol>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard tone="blue" label="Brouillons / retournés" value={nombre(stats.brouillons)} extra="À compléter" />
                <StatCard tone="amber" label="À prendre en charge" value={nombre(stats.transmis)} extra="File sécurité" highlight={stats.transmis > 0} />
                <StatCard tone="plum" label="En cours d’analyse" value={nombre(stats.en_cours)} extra="Décision attendue" />
                <StatCard tone="green" label="Décisions rendues" value={nombre(stats.decides)} extra="Dont clôturés" />
            </div>

            <section className="mt-6">
                <div className="mb-3 flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-medium text-gray-900">File de traitement</h2>
                    <span className="text-sm text-muted">{nombre(rapports.total)} rapport(s)</span>
                </div>
                <DataTable
                    columns={colonnes}
                    data={rapports}
                    rowKey={(r) => r.id}
                    toolbar={
                        <>
                            <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                                <Select value={filters.source || TOUS} onValueChange={(valeur) => filtrer('source', valeur)}>
                                    <SelectTrigger className="h-10" aria-label="Filtrer par source">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TOUS}>Toutes les sources</SelectItem>
                                        <SelectItem value="etudiant">Espace étudiant</SelectItem>
                                        <SelectItem value="administration">Administration</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select value={filters.statut || TOUS} onValueChange={(valeur) => filtrer('statut', valeur)}>
                                    <SelectTrigger className="h-10" aria-label="Filtrer par statut">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TOUS}>Tous les statuts</SelectItem>
                                        {STATUTS.map((s) => (
                                            <SelectItem key={s.value} value={s.value}>
                                                {s.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={filters.gravite || TOUS} onValueChange={(valeur) => filtrer('gravite', valeur)}>
                                    <SelectTrigger className="h-10" aria-label="Filtrer par gravité">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TOUS}>Toutes les gravités</SelectItem>
                                        {GRAVITES.map((g) => (
                                            <SelectItem key={g.value} value={g.value}>
                                                {g.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            {filtreActif && (
                                <Button variant="ghost" size="sm" className="h-10" onClick={() => router.get(urls.index, {}, { preserveScroll: true })}>
                                    <X className="size-4" aria-hidden /> Réinitialiser
                                </Button>
                            )}
                        </>
                    }
                    emptyMessage={
                        <span className="flex flex-col items-center gap-2">
                            <ShieldAlert {...ICON} aria-hidden />
                            {filtreActif ? 'Aucun rapport ne correspond à ces filtres.' : 'Aucun rapport disciplinaire.'}
                        </span>
                    }
                />
            </section>

            {urls.store && <RapportDialog open={nouveau} onClose={() => setNouveau(false)} url={urls.store} structure={structure} />}
            <TransitionDialog
                rapport={transition?.rapport ?? null}
                action={transition?.action ?? null}
                onClose={() => setTransition(null)}
                sanctions={sanctions}
                residents={structure.residents ?? []}
            />

            <Sheet open={detail !== null} onOpenChange={(ouvert) => !ouvert && setDetail(null)}>
                <SheetContent side="right" showCloseButton={false} className="w-full gap-0 p-0 sm:max-w-lg">
                    {detail && (
                        <>
                            <header className="flex items-start gap-3 border-b px-5 py-4">
                                <span className="flex size-11 shrink-0 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                                    <FolderOpen {...ICON} />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <SheetDescription className="text-xs text-muted">Dossier {detail.numero}</SheetDescription>
                                    <SheetTitle className="truncate text-lg font-medium text-gray-900">{detail.nature}</SheetTitle>
                                    <div className="mt-2 flex flex-wrap gap-1.5">
                                        <Badge variant={statut(detail.statut)?.variante ?? 'neutral'}>{detail.statut_label}</Badge>
                                        <Badge variant={VARIANTES_GRAVITE[detail.gravite] ?? 'neutral'}>Gravité {GRAVITES.find((g) => g.value === detail.gravite)?.label.toLowerCase()}</Badge>
                                        {detail.confidentiel && <Badge variant="neutral">Confidentiel</Badge>}
                                    </div>
                                </div>
                                <SheetClose className="inline-flex size-8 cursor-pointer items-center justify-center rounded-brand text-muted transition-colors hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none">
                                    <X {...ICON} />
                                    <span className="sr-only">Fermer</span>
                                </SheetClose>
                            </header>
                            <div className="min-h-0 flex-1 space-y-6 overflow-y-auto px-5 py-5">
                                <dl className="grid grid-cols-2 gap-2">
                                    {(
                                        [
                                            ['Incident', dateHeure(detail.date_incident)],
                                            ['Transmission', detail.transmis_at ? dateHeure(detail.transmis_at) : 'Non transmis'],
                                            ['Prise en charge', detail.pris_en_charge_at ? dateHeure(detail.pris_en_charge_at) : 'En attente'],
                                            ['Résident', detail.resident ? `${detail.resident}${detail.matricule ? ` · ${detail.matricule}` : ''}` : '—'],
                                        ] as [string, string][]
                                    ).map(([libelle, valeur]) => (
                                        <Fragment key={libelle}>
                                            <div className="rounded-brand bg-surface px-3 py-2.5">
                                                <dt className="text-xs text-muted">{libelle}</dt>
                                                <dd className="text-sm font-medium text-gray-900">{valeur}</dd>
                                            </div>
                                        </Fragment>
                                    ))}
                                </dl>
                                <div>
                                    <h3 className="mb-1.5 text-sm font-medium text-gray-900">Lieu et description</h3>
                                    <p className="text-sm text-gray-700">
                                        {detail.localisation}
                                        {detail.lieu && ` · ${detail.lieu}`}
                                    </p>
                                    <p className="mt-1.5 text-sm whitespace-pre-line text-gray-700">{detail.description}</p>
                                </div>
                                {detail.temoins && (
                                    <div>
                                        <h3 className="mb-1.5 text-sm font-medium text-gray-900">Témoins</h3>
                                        <p className="text-sm whitespace-pre-line text-gray-700">{detail.temoins}</p>
                                    </div>
                                )}
                                {detail.decision && (
                                    <div className="rounded-brand border border-[#C6E3A6] bg-soft px-4 py-3">
                                        <h3 className="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-primary">
                                            <Gavel className="size-4" aria-hidden /> Décision
                                        </h3>
                                        <p className="text-sm whitespace-pre-line text-gray-800">{detail.decision}</p>
                                        {detail.sanction && (
                                            <p className="mt-2 text-sm text-gray-800">
                                                <strong className="font-medium">{detail.sanction.type}</strong> · {detail.sanction.etudiant ?? '—'} · {detail.sanction.statut}
                                                {detail.sanction.date_fin && ` · jusqu’au ${dateFr(detail.sanction.date_fin)} inclus`}
                                                {urls.sanctions && (
                                                    <a href={urls.sanctions} className="mt-1 block text-primary underline-offset-2 hover:underline">
                                                        Consulter ou lever la sanction
                                                    </a>
                                                )}
                                            </p>
                                        )}
                                    </div>
                                )}
                                <div>
                                    <h3 className="mb-3 text-sm font-medium text-gray-900">Piste d’audit</h3>
                                    <Chronologie
                                        evenements={detail.historique.map((item) => ({ id: item.id, titre: item.statut, date: item.date, texte: item.commentaire }))}
                                    />
                                </div>
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>
        </AdminLayout>
    );
}
