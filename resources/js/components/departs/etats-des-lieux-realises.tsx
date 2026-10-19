import { ClipboardCheck, FileDown, Search, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { correspond, PaginationLocale, usePaginationLocale, type CiteOption } from '@/components/departs/shared';
import type { DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { SimpleTable } from '@/components/data-table/simple-table';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { dateFr } from '@/lib/text';

export type Inspection = {
    id: string;
    date: string | null;
    etudiant: string;
    matricule: string | null;
    cite: string | null;
    cite_id: string | null;
    batiment: string | null;
    chambre: string | null;
    type: 'entree' | 'sortie';
    observation: string | null;
    pdf: string | null;
};

type Filtres = { recherche: string; type: '' | Inspection['type']; date: string; cite: string };

const TOUS = 'tous';
const FILTRES_VIDES: Filtres = { recherche: '', type: '', date: '', cite: '' };

/** Historique des états des lieux déjà réalisés (entrée et sortie), du plus récent au plus ancien. */
export function EtatsDesLieuxRealises({ inspections, cites }: { inspections: Inspection[]; cites: CiteOption[] }) {
    const [filtres, setFiltres] = useState<Filtres>(FILTRES_VIDES);
    const filtrer = (suivants: Partial<Filtres>) => setFiltres((f) => ({ ...f, ...suivants }));
    const filtreActif = Object.values(filtres).some(Boolean);

    const lignes = useMemo(
        () =>
            inspections.filter(
                (i) =>
                    correspond(filtres.recherche, i.etudiant, i.matricule, i.chambre, i.batiment, i.cite) &&
                    (!filtres.type || i.type === filtres.type) &&
                    (!filtres.date || i.date === filtres.date) &&
                    (!filtres.cite || i.cite_id === filtres.cite),
            ),
        [inspections, filtres],
    );
    const pages = usePaginationLocale(lignes);

    const colonnes: DataTableColumn<Inspection>[] = [
        { key: 'date', header: 'Date', className: 'whitespace-nowrap tabular-nums', cell: (i) => (i.date ? dateFr(i.date) : <span className="text-muted">Non renseignée</span>) },
        {
            key: 'etudiant',
            header: 'Étudiant',
            cell: (i) => (
                <div className="min-w-36">
                    <p className="font-medium text-gray-900">{i.etudiant}</p>
                    {i.matricule && <p className="text-xs text-muted">{i.matricule}</p>}
                </div>
            ),
        },
        {
            key: 'logement',
            header: 'Logement',
            cell: (i) => (
                <div className="min-w-32">
                    <p>{i.chambre ? `Chambre ${i.chambre}` : '—'}</p>
                    <p className="text-xs text-muted">{[i.batiment, i.cite].filter(Boolean).join(' · ') || '—'}</p>
                </div>
            ),
        },
        { key: 'type', header: 'Type', cell: (i) => <Badge variant={i.type === 'entree' ? 'info' : 'success'}>{i.type === 'entree' ? 'Entrée' : 'Sortie'}</Badge> },
        { key: 'observation', header: 'Observations', cell: (i) => <p className="line-clamp-2 max-w-80 whitespace-pre-line">{i.observation || <span className="text-muted">—</span>}</p> },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (i) => <RowActions>{i.pdf && <RowAction label="État des lieux PDF" icon={FileDown} href={i.pdf} newTab />}</RowActions>,
        },
    ];

    return (
        <section className="overflow-hidden rounded-brand border bg-white" aria-label="États des lieux réalisés">
            <div className="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center">
                <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <Input
                        value={filtres.recherche}
                        onChange={(e) => filtrer({ recherche: e.target.value })}
                        placeholder="Nom, matricule, chambre…"
                        aria-label="Rechercher un état des lieux"
                        icon={<Search />}
                        className="h-10"
                    />
                    <Select value={filtres.type || TOUS} onValueChange={(v) => filtrer({ type: v === TOUS ? '' : (v as Inspection['type']) })}>
                        <SelectTrigger className="h-10" aria-label="Filtrer par type">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={TOUS}>Entrées et sorties</SelectItem>
                            <SelectItem value="entree">Entrées</SelectItem>
                            <SelectItem value="sortie">Sorties</SelectItem>
                        </SelectContent>
                    </Select>
                    <DatePicker value={filtres.date} onChange={(date) => filtrer({ date })} placeholder="Date de l’état des lieux" aria-label="Filtrer par date" className="h-10" />
                    <Select value={filtres.cite || TOUS} onValueChange={(v) => filtrer({ cite: v === TOUS ? '' : v })}>
                        <SelectTrigger className="h-10" aria-label="Filtrer par cité">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={TOUS}>Toutes les cités</SelectItem>
                            {cites.map((c) => (
                                <SelectItem key={c.id} value={c.id}>
                                    {c.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                {filtreActif && (
                    <Button variant="ghost" size="sm" className="h-10" onClick={() => setFiltres(FILTRES_VIDES)}>
                        <X className="size-4" aria-hidden /> Réinitialiser
                    </Button>
                )}
            </div>
            <SimpleTable
                columns={colonnes}
                rows={pages.visibles}
                rowKey={(i) => i.id}
                emptyMessage={
                    <span className="flex flex-col items-center gap-2">
                        <ClipboardCheck {...ICON} aria-hidden />
                        {filtreActif ? 'Aucun état des lieux ne correspond à ces filtres.' : 'Aucun état des lieux enregistré.'}
                    </span>
                }
            />
            <PaginationLocale {...pages} />
        </section>
    );
}
