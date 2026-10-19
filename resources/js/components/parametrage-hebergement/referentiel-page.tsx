import { zodResolver } from '@hookform/resolvers/zod';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import { useForm } from 'react-hook-form';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
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
import { repartirErreurs } from '@/components/vie-residentielle/shared';
import { referentielSchema, type ReferentielValeurs } from '@/schemas/parametrage-hebergement';
import type { Paginated } from '@/types/global';
import { ChampRecherche, ErreurGenerale, PiedAssistant, Recap, useAssistant, useRecherche } from './shared';

export type ElementReferentiel = {
    id: string;
    name: string;
    description: string | null;
    urls: { update: string | null; destroy: string | null };
};

type Props<T extends ElementReferentiel> = {
    titre: string;
    description: ReactNode;
    /** Libellés : « type de cité », « un type de cité »… */
    singulier: string;
    un: string;
    nameMax: number;
    elements: Paginated<T>;
    stats: { total: number; utilises: number; inutilises: number };
    filters: { q: string };
    urls: { index: string; store: string | null };
    /** Nombre d'éléments du parc qui utilisent ce type (bloque la suppression) et son libellé. */
    usage: (element: T) => { total: number; libelle: string };
    /** Colonnes ajoutées entre la description et les actions. */
    colonnes?: DataTableColumn<T>[];
    actions?: ReactNode;
    note?: ReactNode;
    icone: ReactNode;
};

const ETAPES = ['Informations', 'Vérification'];
const CHAMPS = [['name', 'description'], []] as const;

/** Liste paginée + fenêtre d'ajout / modification en 2 étapes + suppression confirmée, commune aux référentiels « nom + description ». */
export function ReferentielPage<T extends ElementReferentiel>(props: Props<T>) {
    const { titre, description, singulier, un, elements, stats, filters, urls, usage, colonnes = [], actions, note, icone } = props;
    const [recherche, setRecherche] = useRecherche(urls.index, filters.q, {}, ['elements', 'types', 'stats', 'filters']);
    const [edition, setEdition] = useState<T | 'nouveau' | null>(null);
    const [suppression, setSuppression] = useState<T | null>(null);
    const [suppressionEnCours, setSuppressionEnCours] = useState(false);

    const supprimer = async () => {
        if (!suppression?.urls.destroy) return;
        setSuppressionEnCours(true);
        const ok = await envoyer(suppression.urls.destroy, {}, { method: 'delete' });
        setSuppressionEnCours(false);
        if (ok) setSuppression(null);
    };

    const tableau: DataTableColumn<T>[] = [
        {
            key: 'name',
            header: 'Nom',
            cell: (e) => <span className="font-medium text-gray-900">{e.name}</span>,
        },
        {
            key: 'description',
            header: 'Description',
            className: 'max-w-md',
            cell: (e) => (e.description ? <span className="line-clamp-2 text-muted">{e.description}</span> : <span className="text-gray-400">—</span>),
        },
        ...colonnes,
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (e) => (
                <RowActions>
                    {e.urls.update && <RowAction label="Modifier" icon={Pencil} tone="edit" onClick={() => setEdition(e)} />}
                    {e.urls.destroy && <RowAction label="Supprimer" icon={Trash2} tone="danger" onClick={() => setSuppression(e)} />}
                </RowActions>
            ),
        },
    ];

    const utilisation = suppression ? usage(suppression) : null;

    return (
        <AdminLayout title={titre}>
            <PageHeader
                title={titre}
                description={description}
                actions={
                    (actions || urls.store) && (
                        <>
                            {actions}
                            {urls.store && (
                                <Button size="sm" className="h-10" onClick={() => setEdition('nouveau')}>
                                    <Plus className="size-4" aria-hidden /> Ajouter {un}
                                </Button>
                            )}
                        </>
                    )
                }
            />

            <KpiFilters
                label={`Indicateurs : ${titre}`}
                className="mb-4 xl:grid-cols-3"
                items={[
                    { key: 'total', label: 'Types enregistrés', value: nombre(stats.total), tone: 'green' },
                    { key: 'utilises', label: 'Utilisés dans le parc', value: nombre(stats.utilises), tone: 'blue' },
                    { key: 'inutilises', label: 'Inutilisés', value: nombre(stats.inutilises), tone: 'neutral' },
                ]}
            />

            {note && <div className="mb-4">{note}</div>}

            <DataTable
                columns={tableau}
                data={elements}
                rowKey={(e) => e.id}
                toolbar={
                    <>
                        <ChampRecherche value={recherche} onChange={setRecherche} placeholder="Rechercher un nom ou une description" />
                        <span className="text-sm text-muted sm:ml-auto">{nombre(elements.total)} résultat(s)</span>
                    </>
                }
                emptyMessage={
                    <span className="flex flex-col items-center gap-2">
                        <span className="text-gray-400" aria-hidden>
                            {icone}
                        </span>
                        {filters.q ? 'Aucun résultat pour cette recherche.' : `Aucun ${singulier} enregistré.`}
                    </span>
                }
            />

            <Dialog open={edition !== null} onOpenChange={(ouvert) => !ouvert && setEdition(null)}>
                <DialogContent className="max-w-xl">
                    {edition !== null && (
                        <Formulaire
                            key={edition === 'nouveau' ? 'nouveau' : edition.id}
                            element={edition === 'nouveau' ? null : edition}
                            url={edition === 'nouveau' ? urls.store : edition.urls.update}
                            singulier={singulier}
                            un={un}
                            nameMax={props.nameMax}
                            onClose={() => setEdition(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={suppression !== null}
                onOpenChange={(ouvert) => !ouvert && setSuppression(null)}
                title={`Supprimer « ${suppression?.name ?? ''} » ?`}
                description={
                    utilisation && utilisation.total > 0
                        ? `Ce ${singulier} est utilisé par ${nombre(utilisation.total)} ${utilisation.libelle}. Choisissez un autre type pour ces éléments avant de le supprimer.`
                        : 'Cette suppression est définitive.'
                }
                confirmLabel="Supprimer"
                confirmIcon={<Trash2 className="size-4" aria-hidden />}
                danger
                processing={suppressionEnCours}
                confirmDisabled={(utilisation?.total ?? 0) > 0}
                onConfirm={() => void supprimer()}
            />
        </AdminLayout>
    );
}

function Formulaire({
    element,
    url,
    singulier,
    un,
    nameMax,
    onClose,
}: {
    element: ElementReferentiel | null;
    url: string | null;
    singulier: string;
    un: string;
    nameMax: number;
    onClose: () => void;
}) {
    const schema = useMemo(() => referentielSchema(nameMax), [nameMax]);
    const [erreur, setErreur] = useState<string | null>(null);
    const {
        register,
        trigger,
        watch,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<ReferentielValeurs>({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues: { name: element?.name ?? '', description: element?.description ?? '' },
    });
    const { etape, atteinte, aller, allerA, toutValider, derniere } = useAssistant<ReferentielValeurs>(CHAMPS, trigger, element !== null);
    const valeurs = watch();

    const enregistrer = (v: ReferentielValeurs) =>
        envoyer<ReferentielValeurs>(
            url!,
            { name: v.name.trim(), description: v.description.trim() },
            {
                method: element ? 'put' : 'post',
                onSuccess: onClose,
                onError: (erreurs) => setErreur(repartirErreurs(erreurs, CHAMPS, (champ, e) => setError(champ, e), aller)),
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
                <DialogTitle>{element ? `Modifier « ${element.name} »` : `Ajouter ${un}`}</DialogTitle>
                <DialogDescription>
                    Étape {etape + 1} sur {ETAPES.length}
                </DialogDescription>
            </DialogHeader>
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={(index) => void allerA(index)} />
            <DialogBody className="space-y-4">
                <ErreurGenerale message={erreur} />
                {etape === 0 ? (
                    <>
                        <Field id="ref-name" label="Nom" required error={errors.name?.message}>
                            <Input id="ref-name" maxLength={nameMax} placeholder={`Nom du ${singulier}`} invalid={!!errors.name} autoFocus {...register('name')} />
                        </Field>
                        <Field id="ref-description" label="Description" error={errors.description?.message} hint="Facultative : usage, public concerné…">
                            <Textarea id="ref-description" rows={4} maxLength={2000} placeholder="Description" invalid={!!errors.description} {...register('description')} />
                        </Field>
                    </>
                ) : (
                    <Recap
                        lignes={[
                            { label: 'Nom', valeur: <span className="font-medium">{valeurs.name.trim()}</span> },
                            { label: 'Description', valeur: valeurs.description.trim() || <span className="text-muted">Aucune</span> },
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
                libelle={element ? 'Enregistrer les modifications' : 'Ajouter'}
            />
        </form>
    );
}
