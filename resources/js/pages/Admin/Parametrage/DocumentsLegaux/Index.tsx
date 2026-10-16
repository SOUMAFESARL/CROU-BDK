import { router } from '@inertiajs/react';
import { BookOpen, CalendarClock, FileText, FolderOpen, ShieldAlert, Trash2, Upload, X, type LucideIcon } from 'lucide-react';
import { useState } from 'react';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { ConfirmRetrait, StatutDocumentBadge } from '@/components/documents-legaux/shared';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Document = {
    id: string;
    title: string;
    type: string;
    type_label: string;
    reference: string | null;
    version_label: string;
    file_name: string | null;
    status: 'published' | 'draft';
    updated_at: string | null;
    urls: { edit: string | null; pdf: string | null; destroy: string | null };
};

type TypeDocument = {
    value: string;
    label: string;
    total: number;
    valides: number;
    create: string | null;
};

type Filtres = { type: string; statut: string };

type Props = {
    documents: Paginated<Document>;
    types: TypeDocument[];
    kpis: {
        total: number;
        valides: number;
        brouillons: number;
        types_sans_version: number;
    };
    filters: Filtres;
    urls: { index: string };
};

const TOUS = 'tous';

/** Icône de chaque type de document (`LegalDocument::TYPES`). */
const ICONES: Record<string, LucideIcon> = {
    reglement_interieur: BookOpen,
    guide_resident: FolderOpen,
    procedures_securite: ShieldAlert,
    calendrier_visites: CalendarClock,
};

/** Documents de la résidence (remplace `parametrage/legal_documents/index.blade.php`). */
export default function DocumentsLegauxIndex({ documents, types, kpis, filters, urls }: Props) {
    const [aRetirer, setARetirer] = useState<Document | null>(null);
    const [retrait, setRetrait] = useState(false);

    const retirer = () => {
        if (!aRetirer?.urls.destroy) return;
        router.delete(aRetirer.urls.destroy, {
            preserveScroll: true,
            onStart: () => setRetrait(true),
            onFinish: () => {
                setRetrait(false);
                setARetirer(null);
            },
        });
    };

    const lien = (suivants: Partial<Filtres>) => {
        const params = Object.fromEntries(Object.entries({ ...filters, ...suivants }).filter(([, valeur]) => valeur));
        const query = new URLSearchParams(params).toString();
        return query ? `${urls.index}?${query}` : urls.index;
    };
    const filtrer = (suivants: Partial<Filtres>) => router.get(lien(suivants), {}, { preserveState: true, preserveScroll: true, replace: true });
    const filtreActif = Boolean(filters.type || filters.statut);

    const colonnes: DataTableColumn<Document>[] = [
        {
            key: 'document',
            header: 'Document',
            cell: (d) => (
                <div className="min-w-48">
                    <p className="font-medium text-gray-900">{d.title}</p>
                    <p className="text-xs text-muted">{d.type_label}</p>
                </div>
            ),
        },
        {
            key: 'reference',
            header: 'Référence / version',
            cell: (d) => (
                <div className="min-w-32">
                    <p>{d.reference || <span className="text-muted">Sans référence</span>}</p>
                    <p className="text-xs text-muted">Version {d.version_label}</p>
                </div>
            ),
        },
        {
            key: 'fichier',
            header: 'Fichier',
            cell: (d) =>
                d.file_name ? (
                    <span className="flex max-w-56 items-center gap-1.5">
                        <FileText className="size-4 shrink-0 text-primary" strokeWidth={1.5} aria-hidden />
                        <span className="truncate">{d.file_name}</span>
                    </span>
                ) : (
                    <span className="text-muted">Ancien document (sans PDF)</span>
                ),
        },
        {
            key: 'etat',
            header: 'État',
            cell: (d) => <StatutDocumentBadge status={d.status} />,
        },
        {
            key: 'maj',
            header: 'Mis à jour',
            className: 'whitespace-nowrap',
            cell: (d) => dateFr(d.updated_at),
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (d) => (
                <RowActions>
                    {d.urls.pdf && <RowAction label="Consulter le PDF" icon={FileText} href={d.urls.pdf} newTab tone="view" />}
                    {d.urls.edit && <RowAction label="Ouvrir le document" icon={FolderOpen} href={d.urls.edit} tone="edit" />}
                    {d.urls.destroy && <RowAction label="Retirer le document" icon={Trash2} tone="danger" onClick={() => setARetirer(d)} />}
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Documents de la résidence">
            <PageHeader
                title="Documents de la résidence"
                description="Téléversez vos documents au format PDF, puis validez les versions à mettre à disposition des résidents. Chaque version validée reste consultable dans l’historique."
            />

            <KpiFilters
                label="Filtrer les documents par état"
                active={filters.statut || 'tous'}
                ringActive={filtreActif}
                items={[
                    {
                        key: 'tous',
                        label: 'Documents enregistrés',
                        value: nombre(kpis.total),
                        tone: 'blue',
                        href: lien({ statut: '' }),
                    },
                    {
                        key: 'published',
                        label: 'Versions validées',
                        value: nombre(kpis.valides),
                        tone: 'green',
                        href: lien({ statut: 'published' }),
                        extra: 'Visibles par les résidents',
                    },
                    {
                        key: 'draft',
                        label: 'Brouillons',
                        value: nombre(kpis.brouillons),
                        tone: 'amber',
                        href: lien({ statut: 'draft' }),
                        extra: kpis.brouillons > 0 ? 'À valider' : undefined,
                        highlight: kpis.brouillons > 0,
                    },
                    {
                        key: 'types',
                        label: 'Types sans version validée',
                        value: `${nombre(kpis.types_sans_version)} / ${nombre(types.length)}`,
                        tone: 'plum',
                        extra: kpis.types_sans_version > 0 ? 'À compléter' : 'Complet',
                        highlight: kpis.types_sans_version > 0,
                    },
                ]}
            />

            <section className="mt-6">
                <div className="mb-3 flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-medium text-gray-900">Types de documents</h2>
                    <span className="text-sm text-muted">Choisissez un type pour téléverser un nouveau document</span>
                </div>
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {types.map((type) => {
                        const Icone = ICONES[type.value] ?? FileText;
                        return (
                            <article key={type.value} className="flex flex-col gap-3 rounded-brand border bg-white p-4">
                                <div className="flex items-start gap-3">
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-brand bg-soft text-primary">
                                        <Icone {...ICON} aria-hidden />
                                    </span>
                                    <div className="min-w-0">
                                        <h3 className="text-sm font-medium text-gray-900">{type.label}</h3>
                                        <p className="text-xs text-muted">
                                            {nombre(type.total)} document(s) · {nombre(type.valides)} validé(s)
                                        </p>
                                    </div>
                                </div>
                                <div className="mt-auto flex flex-wrap items-center justify-between gap-2">
                                    {type.valides > 0 ? <Badge variant="success">En ligne</Badge> : <span />}
                                    {type.create && (
                                        <a href={type.create} className={cn(buttonVariants({ size: 'sm' }), 'h-9 px-3')}>
                                            <Upload className="size-4" aria-hidden /> Téléverser
                                        </a>
                                    )}
                                </div>
                            </article>
                        );
                    })}
                </div>
            </section>

            <section className="mt-8">
                <div className="mb-3 flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-medium text-gray-900">Documents enregistrés</h2>
                    <span className="text-sm text-muted">{nombre(documents.total)} document(s)</span>
                </div>
                <DataTable
                    columns={colonnes}
                    data={documents}
                    rowKey={(d) => d.id}
                    toolbar={
                        <>
                            <div className="w-full sm:max-w-xs">
                                <Select
                                    value={filters.type || TOUS}
                                    onValueChange={(valeur) =>
                                        filtrer({
                                            type: valeur === TOUS ? '' : valeur,
                                        })
                                    }
                                >
                                    <SelectTrigger className="h-10" aria-label="Filtrer par type de document">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TOUS}>Tous les types</SelectItem>
                                        {types.map((type) => (
                                            <SelectItem key={type.value} value={type.value}>
                                                {type.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            {filtreActif && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-10 sm:ml-auto"
                                    onClick={() => router.get(urls.index, {}, { preserveScroll: true })}
                                >
                                    <X className="size-4" aria-hidden /> Réinitialiser
                                </Button>
                            )}
                        </>
                    }
                    emptyMessage={
                        <span className="flex flex-col items-center gap-2">
                            <FileText {...ICON} aria-hidden />
                            {filtreActif
                                ? 'Aucun document ne correspond à ces filtres.'
                                : 'Aucun document enregistré. Choisissez un type ci-dessus pour commencer.'}
                        </span>
                    }
                />
            </section>

            <ConfirmRetrait document={aRetirer} onOpenChange={(ouvert) => !ouvert && setARetirer(null)} processing={retrait} onConfirm={retirer} />
        </AdminLayout>
    );
}
