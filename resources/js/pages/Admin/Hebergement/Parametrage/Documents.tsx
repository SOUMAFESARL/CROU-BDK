import { zodResolver } from '@hookform/resolvers/zod';
import { FileCheck, FileText, Pencil, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import { Controller, useForm, type Path } from 'react-hook-form';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { ChampRecherche, ErreurGenerale, PiedAssistant, Recap, useAssistant, useRecherche } from '@/components/parametrage-hebergement/shared';
import { ToggleCard } from '@/components/parc-immobilier/form/inputs';
import { repartirErreurs } from '@/components/vie-residentielle/shared';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { nombre } from '@/lib/text';
import { documentSchema, enNombre, type DocumentValeurs } from '@/schemas/parametrage-hebergement';
import type { Paginated } from '@/types/global';

type Document = {
    id: string;
    name: string;
    description: string | null;
    ordre_affiche: number | null;
    delai_validite_mois: number | null;
    requis: boolean;
    actif: boolean;
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

const validite = (mois: number | null) => (mois ? `${nombre(mois)} mois` : 'Sans limite');

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
                        <Formulaire
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

const ETAPES = ['Informations', 'Règles', 'Vérification'];
const CHAMPS: Path<DocumentValeurs>[][] = [['name', 'description'], ['ordre_affiche', 'delai_validite_mois', 'requis', 'actif'], []];

function Formulaire({ document, url, ordreSuivant, onClose }: { document: Document | null; url: string | null; ordreSuivant: number; onClose: () => void }) {
    const [erreur, setErreur] = useState<string | null>(null);
    const {
        register,
        control,
        trigger,
        watch,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<DocumentValeurs>({
        resolver: zodResolver(documentSchema),
        mode: 'onTouched',
        defaultValues: {
            name: document?.name ?? '',
            description: document?.description ?? '',
            ordre_affiche: String(document?.ordre_affiche ?? ordreSuivant),
            delai_validite_mois: document?.delai_validite_mois ? String(document.delai_validite_mois) : '',
            requis: document?.requis ?? true,
            actif: document?.actif ?? true,
        },
    });
    const { etape, atteinte, aller, allerA, toutValider, derniere } = useAssistant<DocumentValeurs>(CHAMPS, trigger, document !== null);
    const v = watch();

    // L'action attend des cases à cocher HTML (« on ») et du texte pour l'ordre et le délai ; le statut n'existe qu'en modification.
    const enregistrer = (valeurs: DocumentValeurs) =>
        envoyer<DocumentValeurs>(
            url!,
            {
                name: valeurs.name.trim(),
                description: valeurs.description.trim(),
                ordre_affiche: String(enNombre(valeurs.ordre_affiche)),
                delai_validite_mois: valeurs.delai_validite_mois.trim() === '' ? '' : String(enNombre(valeurs.delai_validite_mois)),
                ...(valeurs.requis ? { champs_requis: 'on' } : {}),
                ...(document && valeurs.actif ? { statut: 'on' } : {}),
            },
            {
                method: document ? 'put' : 'post',
                onSuccess: onClose,
                onError: (erreurs) =>
                    setErreur(
                        repartirErreurs(
                            Object.fromEntries(
                                Object.entries(erreurs).map(([cle, message]) => [cle === 'champs_requis' ? 'requis' : cle === 'statut' ? 'actif' : cle, message]),
                            ),
                            CHAMPS,
                            (champ, e) => setError(champ, e),
                            aller,
                        ),
                    ),
            },
        );

    return (
        <form
            noValidate
            className="flex min-h-0 flex-col"
            onSubmit={async (event) => {
                event.preventDefault();
                setErreur(null);
                if (!derniere) await allerA(etape + 1);
                else if (await toutValider()) await handleSubmit(enregistrer)();
            }}
        >
            <DialogHeader>
                <DialogTitle>{document ? `Modifier « ${document.name} »` : 'Ajouter un document'}</DialogTitle>
                <DialogDescription>
                    Étape {etape + 1} sur {ETAPES.length}
                </DialogDescription>
            </DialogHeader>
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={(index) => void allerA(index)} />
            <DialogBody className="space-y-4">
                <ErreurGenerale message={erreur} />

                {etape === 0 && (
                    <>
                        <Field id="doc-name" label="Nom du document" required error={errors.name?.message}>
                            <Input id="doc-name" maxLength={100} placeholder="Ex. Relevé de notes" invalid={!!errors.name} autoFocus {...register('name')} />
                        </Field>
                        <Field id="doc-description" label="Description" error={errors.description?.message} hint="Consigne affichée à l’étudiant (facultative)">
                            <Textarea id="doc-description" rows={3} maxLength={2000} placeholder="Ex. Relevé de l’année précédente, signé" invalid={!!errors.description} {...register('description')} />
                        </Field>
                    </>
                )}

                {etape === 1 && (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field id="doc-ordre" label="Ordre d’affichage" required error={errors.ordre_affiche?.message}>
                                <Input id="doc-ordre" inputMode="numeric" placeholder="Ex. 1" invalid={!!errors.ordre_affiche} {...register('ordre_affiche')} />
                            </Field>
                            <Field id="doc-validite" label="Validité (mois)" error={errors.delai_validite_mois?.message} hint="Vide : sans limite">
                                <Input id="doc-validite" inputMode="numeric" placeholder="Ex. 12" invalid={!!errors.delai_validite_mois} {...register('delai_validite_mois')} />
                            </Field>
                        </div>
                        <Controller
                            control={control}
                            name="requis"
                            render={({ field }) => (
                                <ToggleCard
                                    checked={field.value}
                                    onChange={field.onChange}
                                    label="Pièce obligatoire"
                                    icon={<FileCheck />}
                                    description="L’étudiant doit joindre ce document à sa demande de logement."
                                />
                            )}
                        />
                        {document && (
                            <Controller
                                control={control}
                                name="actif"
                                render={({ field }) => (
                                    <ToggleCard
                                        checked={field.value}
                                        onChange={field.onChange}
                                        label="Document actif"
                                        icon={<Power />}
                                        description="Un document inactif n’est plus proposé dans les nouvelles campagnes."
                                    />
                                )}
                            />
                        )}
                    </>
                )}

                {etape === 2 && (
                    <Recap
                        lignes={[
                            { label: 'Nom', valeur: <span className="font-medium">{v.name.trim()}</span> },
                            { label: 'Description', valeur: v.description.trim() || <span className="text-muted">Aucune</span> },
                            { label: 'Ordre d’affichage', valeur: v.ordre_affiche },
                            { label: 'Validité', valeur: validite(enNombre(v.delai_validite_mois)) },
                            { label: 'Pièce', valeur: v.requis ? <Badge variant="warning">Obligatoire</Badge> : <Badge variant="neutral">Facultative</Badge> },
                            { label: 'Statut', valeur: !document || v.actif ? <Badge variant="success">Actif</Badge> : <Badge variant="neutral">Inactif</Badge> },
                        ]}
                    />
                )}
            </DialogBody>
            <PiedAssistant
                etape={etape}
                derniere={derniere}
                envoi={isSubmitting}
                desactive={!url}
                onAnnuler={onClose}
                onPrecedent={() => aller(etape - 1)}
                libelle={document ? 'Enregistrer les modifications' : 'Ajouter le document'}
            />
        </form>
    );
}
