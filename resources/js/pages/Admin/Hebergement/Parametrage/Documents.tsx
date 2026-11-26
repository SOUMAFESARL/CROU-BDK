import { FileText, Pencil, Plus } from 'lucide-react';
import { useState } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { DocumentFormulaire, validite, type DocumentParametre } from '@/components/parametrage-hebergement/document-form';
import { ChampRecherche, useRecherche } from '@/components/parametrage-hebergement/shared';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { nombre } from '@/lib/text';
import type { Paginated } from '@/types/global';

type Document = DocumentParametre & {
    id: string;
    campagnes: number;
    urls: { update: string | null };
};

type Filtre = 'tous' | 'actifs' | 'obligatoires' | 'inactifs';

type Props = {
    documents: Paginated<Document>;
    stats: { total: number; actifs: number; obligatoires: number; inactifs: number };
    filters: { q: string; statut: Filtre };
    urls: { index: string; store: string | null };
};

/** Documents à fournir dans les dossiers (remplace `parametrage-herb/document/document.blade.php`) ; actions `store` et `update` inchangées. */
export default function Documents({ documents, stats, filters, urls }: Props) {
    const [recherche, setRecherche] = useRecherche(urls.index, filters.q, { statut: filters.statut }, ['documents', 'stats', 'filters']);
    const [edition, setEdition] = useState<Document | 'nouveau' | null>(null);
    const lien = (statut: Filtre) => {
        const parametres = new URLSearchParams(Object.entries({ statut: statut === 'tous' ? '' : statut, q: filters.q }).filter(([, v]) => v !== ''));
        return parametres.size ? `${urls.index}?${parametres}` : urls.index;
    };

    const colonnes: DataTableColumn<Document>[] = [
        { key: 'ordre', header: 'Ordre', headClassName: 'w-16', className: 'tabular-nums text-muted', cell: (d) => d.ordre_affiche ?? '—' },
        {
            key: 'document',
            header: 'Document',
            cell: (d) => (
                <div className="min-w-48">
                    <p className="font-medium text-gray-900">{d.name}</p>
                    {d.description && <p className="line-clamp-1 text-xs text-muted">{d.description}</p>}
                </div>
            ),
        },
        { key: 'requis', header: 'Pièce', cell: (d) => (d.requis ? <Badge variant="warning">Obligatoire</Badge> : <Badge variant="neutral">Facultative</Badge>) },
        { key: 'validite', header: 'Validité', className: 'whitespace-nowrap', cell: (d) => validite(d.delai_validite_mois) },
        { key: 'campagnes', header: 'Campagnes', className: 'tabular-nums', cell: (d) => nombre(d.campagnes) },
        { key: 'statut', header: 'Statut', cell: (d) => (d.actif ? <Badge variant="success">Actif</Badge> : <Badge variant="neutral">Inactif</Badge>) },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (d) => <RowActions>{d.urls.update && <RowAction label="Modifier" icon={Pencil} tone="edit" onClick={() => setEdition(d)} />}</RowActions>,
        },
    ];

    return (
        <AdminLayout title="Documents à fournir">
            <PageHeader
                title="Documents à fournir"
                description="Pièces que les étudiants déposent dans leur dossier ; chaque campagne choisit celles qu’elle exige."
                actions={
                    urls.store && (
                        <Button size="sm" className="h-10" onClick={() => setEdition('nouveau')}>
                            <Plus className="size-4" aria-hidden /> Ajouter un document
                        </Button>
                    )
                }
            />

            <KpiFilters
                label="Filtrer les documents"
                active={filters.statut}
                ringActive={filters.statut !== 'tous'}
                className="mb-4"
                items={[
                    { key: 'tous', label: 'Documents', value: nombre(stats.total), tone: 'green', href: lien('tous') },
                    { key: 'actifs', label: 'Actifs', value: nombre(stats.actifs), tone: 'blue', href: lien('actifs') },
                    { key: 'obligatoires', label: 'Obligatoires', value: nombre(stats.obligatoires), tone: 'amber', href: lien('obligatoires') },
                    { key: 'inactifs', label: 'Inactifs', value: nombre(stats.inactifs), tone: 'neutral', href: lien('inactifs') },
                ]}
            />

            <DataTable
                columns={colonnes}
                data={documents}
                rowKey={(d) => d.id}
                toolbar={
                    <>
                        <ChampRecherche value={recherche} onChange={setRecherche} placeholder="Rechercher un document" />
                        <span className="text-sm text-muted sm:ml-auto">{nombre(documents.total)} résultat(s)</span>
                    </>
                }
                emptyMessage={
                    <span className="flex flex-col items-center gap-2">
                        <FileText {...ICON} className="text-gray-400" aria-hidden />
                        {filters.q || filters.statut !== 'tous' ? 'Aucun document pour ces filtres.' : 'Aucun document enregistré.'}
                    </span>
                }
            />

            <Dialog open={edition !== null} onOpenChange={(ouvert) => !ouvert && setEdition(null)}>
                <DialogContent className="max-w-xl">
                    {edition !== null && (
                        <DocumentFormulaire
                            key={edition === 'nouveau' ? 'nouveau' : edition.id}
                            document={edition === 'nouveau' ? null : edition}
                            url={edition === 'nouveau' ? urls.store : edition.urls.update}
                            ordreSuivant={stats.total + 1}
                            onClose={() => setEdition(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
