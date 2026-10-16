import { router } from '@inertiajs/react';
import { CalendarDays, Eye, Megaphone, Paperclip, Pencil, Plus, Search, Send, Trash2, User, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { AnnonceDialog, CANAUX, PRIORITES_ANNONCE, type AnnonceModifiable, type Cibles } from '@/components/annonces/annonce-dialog';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { RichTextView } from '@/components/ui/rich-text-editor';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet';
import { dateHeure, Lignes, type Variante } from '@/components/vie-residentielle/shared';
import { nombre } from '@/lib/text';
import type { Paginated } from '@/types/global';

type Annonce = AnnonceModifiable & {
    extrait: string;
    statut_label: string;
    audience_label: string;
    date_creation: string | null;
    date_publication: string | null;
    createur: string | null;
    envois: { acceptes: number; en_attente: number; echecs: number };
    vues: number;
    reactions: number;
    pieces_jointes: string[];
    urls: { update: string | null; publish: string | null; destroy: string | null };
};

type Filtres = { statut: string; canal: string; search: string; date_debut: string; date_fin: string };

type Props = {
    annonces: Paginated<Annonce>;
    stats: { total: number; publiees: number; planifiees: number; brouillons: number; annulees: number };
    filters: Filtres;
    audiences: { value: string; label: string }[];
    cibles: Cibles;
    urls: { index: string; store: string | null };
};

const TOUS = 'tous';

const VARIANTES_STATUT: Record<string, Variante> = { publie: 'success', planifie: 'info', brouillon: 'neutral', annule: 'danger' };
const VARIANTES_PRIORITE: Record<string, Variante> = { haute: 'warning', urgente: 'danger' };

/** Annonces aux résidents (remplace `vie_residentielle/annonce.blade.php` et ses partiels ; Summernote remplacé par Tiptap). */
export default function AnnoncesIndex({ annonces, stats, filters, audiences, cibles, urls }: Props) {
    const [edition, setEdition] = useState<{ annonce: Annonce | null } | null>(null);
    const [detail, setDetail] = useState<Annonce | null>(null);
    const [action, setAction] = useState<{ type: 'publier' | 'supprimer'; annonce: Annonce } | null>(null);
    const [traitement, setTraitement] = useState(false);
    const [recherche, setRecherche] = useState(filters.search);

    const filtrer = (suivants: Partial<Filtres>) => {
        const valeurs = { ...filters, ...suivants };
        router.get(urls.index, Object.fromEntries(Object.entries(valeurs).filter(([, v]) => v && v !== TOUS)), { preserveState: true, preserveScroll: true, replace: true });
    };

    useEffect(() => {
        if (recherche === filters.search) return;
        const minuterie = window.setTimeout(() => filtrer({ search: recherche.trim() }), 400);
        return () => window.clearTimeout(minuterie);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [recherche]);

    const filtreActif = Boolean(filters.statut || filters.canal || filters.search || filters.date_debut || filters.date_fin);
    const lienStatut = (statut: string) => {
        const valeurs = { ...filters, statut };
        const query = new URLSearchParams(Object.entries(valeurs).filter(([, v]) => v) as [string, string][]).toString();
        return query ? `${urls.index}?${query}` : urls.index;
    };

    const confirmer = () => {
        if (!action) return;
        const url = action.type === 'publier' ? action.annonce.urls.publish : action.annonce.urls.destroy;
        if (!url) return;
        const options = {
            preserveScroll: true,
            onStart: () => setTraitement(true),
            onFinish: () => {
                setTraitement(false);
                setAction(null);
            },
        };
        if (action.type === 'publier') router.post(url, {}, options);
        else router.delete(url, options);
    };

    return (
        <AdminLayout title="Annonces">
            <PageHeader
                title="Annonces aux résidents"
                description="Rédiger, cibler et diffuser une information sur le site, par SMS ou par e-mail. Les envois sont journalisés : acceptés, en attente ou en échec."
                actions={
                    urls.store && (
                        <Button size="sm" className="h-10 px-4" onClick={() => setEdition({ annonce: null })}>
                            <Plus {...ICON} aria-hidden /> Nouvelle annonce
                        </Button>
                    )
                }
            />

            <KpiFilters
                label="Filtrer par statut"
                active={filters.statut || 'tous'}
                items={[
                    { key: 'tous', label: 'Toutes les annonces', value: nombre(stats.total), tone: 'blue', href: lienStatut(''), extra: `${nombre(stats.annulees)} annulée(s)` },
                    { key: 'publie', label: 'Publiées', value: nombre(stats.publiees), tone: 'green', href: lienStatut('publie'), extra: 'Diffusées' },
                    { key: 'planifie', label: 'Planifiées', value: nombre(stats.planifiees), tone: 'amber', href: lienStatut('planifie'), extra: 'À venir' },
                    { key: 'brouillon', label: 'Brouillons', value: nombre(stats.brouillons), tone: 'plum', href: lienStatut('brouillon'), extra: 'Non envoyés' },
                ]}
            />

            <section className="mt-6 overflow-hidden rounded-brand border bg-white">
                <div className="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center">
                    <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <Input value={recherche} onChange={(event) => setRecherche(event.target.value)} placeholder="Titre ou contenu…" aria-label="Rechercher une annonce" icon={<Search />} className="h-10" />
                        <Select value={filters.canal || TOUS} onValueChange={(valeur) => filtrer({ canal: valeur })}>
                            <SelectTrigger className="h-10" aria-label="Filtrer par canal">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={TOUS}>Tous les canaux</SelectItem>
                                {CANAUX.map((canal) => (
                                    <SelectItem key={canal.value} value={canal.value}>
                                        {canal.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <DatePicker value={filters.date_debut} onChange={(valeur) => filtrer({ date_debut: valeur })} placeholder="Créées depuis le" max={filters.date_fin || undefined} className="h-10" aria-label="Créées à partir du" />
                        <DatePicker value={filters.date_fin} onChange={(valeur) => filtrer({ date_fin: valeur })} placeholder="Jusqu’au" min={filters.date_debut || undefined} className="h-10" aria-label="Créées jusqu’au" />
                    </div>
                    {filtreActif && (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-10"
                            onClick={() => {
                                setRecherche('');
                                router.get(urls.index, {}, { preserveScroll: true });
                            }}
                        >
                            <X className="size-4" aria-hidden /> Réinitialiser
                        </Button>
                    )}
                </div>

                {annonces.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-2 px-4 py-14 text-center text-sm text-muted">
                        <Megaphone {...ICON} aria-hidden />
                        {filtreActif ? 'Aucune annonce ne correspond à ces filtres.' : 'Aucune annonce pour le moment.'}
                    </div>
                ) : (
                    <ul className="divide-y">
                        {annonces.data.map((annonce) => (
                            <li key={annonce.id} className="flex flex-col gap-3 px-4 py-4 md:flex-row md:items-start md:gap-6 md:px-5">
                                <div className="min-w-0 flex-1 space-y-1.5">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="text-sm font-medium text-gray-900">{annonce.titre}</h3>
                                        <Badge variant={VARIANTES_STATUT[annonce.statut] ?? 'neutral'}>{annonce.statut_label}</Badge>
                                        {annonce.priorite !== 'normale' && (
                                            <Badge variant={VARIANTES_PRIORITE[annonce.priorite] ?? 'neutral'}>{PRIORITES_ANNONCE.find((p) => p.value === annonce.priorite)?.label}</Badge>
                                        )}
                                    </div>
                                    <p className="line-clamp-2 text-sm text-gray-700">{annonce.extrait}</p>
                                    <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
                                        <span className="inline-flex items-center gap-1">
                                            <CalendarDays className="size-3.5" aria-hidden />
                                            {annonce.statut === 'planifie' && annonce.date_planification
                                                ? `Prévue le ${dateHeure(annonce.date_planification)}`
                                                : annonce.date_publication
                                                  ? `Publiée le ${dateHeure(annonce.date_publication)}`
                                                  : `Créée le ${dateHeure(annonce.date_creation)}`}
                                        </span>
                                        {annonce.createur && (
                                            <span className="inline-flex items-center gap-1">
                                                <User className="size-3.5" aria-hidden /> {annonce.createur}
                                            </span>
                                        )}
                                        <span>{annonce.audience_label}</span>
                                        <span>{annonce.canaux.map((c) => CANAUX.find((canal) => canal.value === c)?.label ?? c).join(' · ')}</span>
                                        {annonce.pieces_jointes.length > 0 && (
                                            <span className="inline-flex items-center gap-1">
                                                <Paperclip className="size-3.5" aria-hidden /> {annonce.pieces_jointes.length}
                                            </span>
                                        )}
                                    </p>
                                    {(annonce.envois.acceptes > 0 || annonce.envois.en_attente > 0 || annonce.envois.echecs > 0) && (
                                        <p className="text-xs text-muted">
                                            Envois acceptés : <strong className="font-medium text-gray-800">{nombre(annonce.envois.acceptes)}</strong> · en attente : {nombre(annonce.envois.en_attente)} · échecs :{' '}
                                            <span className={annonce.envois.echecs ? 'font-medium text-red-700' : undefined}>{nombre(annonce.envois.echecs)}</span>
                                            {annonce.envois.echecs > 0 && <span className="text-red-700"> — vérifiez les paramètres e-mail et SMS.</span>}
                                        </p>
                                    )}
                                </div>
                                <RowActions>
                                    <RowAction label="Voir l’annonce" icon={Eye} tone="view" onClick={() => setDetail(annonce)} />
                                    {annonce.urls.update && <RowAction label="Modifier" icon={Pencil} tone="edit" onClick={() => setEdition({ annonce })} />}
                                    {annonce.urls.publish && <RowAction label="Publier maintenant" icon={Send} tone="success" onClick={() => setAction({ type: 'publier', annonce })} />}
                                    {annonce.urls.destroy && <RowAction label="Supprimer" icon={Trash2} tone="danger" onClick={() => setAction({ type: 'supprimer', annonce })} />}
                                </RowActions>
                            </li>
                        ))}
                    </ul>
                )}
                <DataTablePagination data={annonces} />
            </section>

            {edition && (
                <AnnonceDialog open onClose={() => setEdition(null)} annonce={edition.annonce} urlStore={urls.store} audiences={audiences} cibles={cibles} />
            )}

            <ConfirmDialog
                open={action !== null}
                onOpenChange={(ouvert) => !ouvert && setAction(null)}
                title={action?.type === 'publier' ? 'Publier cette annonce maintenant ?' : 'Supprimer cette annonce ?'}
                description={
                    action && (
                        <>
                            « {action.annonce.titre} »
                            {action.type === 'publier'
                                ? ' sera diffusée immédiatement sur les canaux choisis.'
                                : '. Les messages encore en attente ne seront plus transmis.'}
                        </>
                    )
                }
                confirmLabel={action?.type === 'publier' ? 'Publier' : 'Supprimer'}
                confirmIcon={action?.type === 'publier' ? <Send className="size-4" aria-hidden /> : <Trash2 className="size-4" aria-hidden />}
                danger={action?.type === 'supprimer'}
                processing={traitement}
                onConfirm={confirmer}
            />

            <Sheet open={detail !== null} onOpenChange={(ouvert) => !ouvert && setDetail(null)}>
                <SheetContent side="right" showCloseButton={false} className="w-full gap-0 p-0 sm:max-w-lg">
                    {detail && (
                        <>
                            <header className="flex items-start gap-3 border-b px-5 py-4">
                                <span className="flex size-11 shrink-0 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                                    <Megaphone {...ICON} />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <SheetDescription className="text-xs text-muted">Annonce</SheetDescription>
                                    <SheetTitle className="text-lg font-medium text-gray-900">{detail.titre}</SheetTitle>
                                    <div className="mt-2 flex flex-wrap gap-1.5">
                                        <Badge variant={VARIANTES_STATUT[detail.statut] ?? 'neutral'}>{detail.statut_label}</Badge>
                                        {detail.priorite !== 'normale' && <Badge variant={VARIANTES_PRIORITE[detail.priorite] ?? 'neutral'}>Priorité {detail.priorite}</Badge>}
                                    </div>
                                </div>
                                <SheetClose className="inline-flex size-8 cursor-pointer items-center justify-center rounded-brand text-muted transition-colors hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none">
                                    <X {...ICON} />
                                    <span className="sr-only">Fermer</span>
                                </SheetClose>
                            </header>
                            <div className="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5">
                                <RichTextView html={detail.contenu} />
                                {detail.pieces_jointes.length > 0 && (
                                    <div>
                                        <h3 className="mb-1.5 text-sm font-medium text-gray-900">Pièces jointes</h3>
                                        <ul className="space-y-1">
                                            {detail.pieces_jointes.map((url, index) => (
                                                <li key={url}>
                                                    <a href={url} target="_blank" rel="noopener" className="inline-flex items-center gap-1.5 text-sm text-primary hover:underline">
                                                        <Paperclip className="size-4" aria-hidden /> Pièce jointe {index + 1}
                                                    </a>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                                <Lignes
                                    lignes={[
                                        ['Créée le', dateHeure(detail.date_creation)],
                                        ['Publiée le', detail.date_publication ? dateHeure(detail.date_publication) : ''],
                                        ['Prévue le', detail.statut === 'planifie' && detail.date_planification ? dateHeure(detail.date_planification) : ''],
                                        ['Auteur', detail.createur ?? ''],
                                        ['Audience', detail.audience_label],
                                        ['Canaux', detail.canaux.map((c) => CANAUX.find((canal) => canal.value === c)?.label ?? c).join(', ')],
                                        ['Envois', `${nombre(detail.envois.acceptes)} accepté(s) · ${nombre(detail.envois.en_attente)} en attente · ${nombre(detail.envois.echecs)} échec(s)`],
                                        ['Vues', nombre(detail.vues)],
                                    ]}
                                />
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>
        </AdminLayout>
    );
}
