import { zodResolver } from '@hookform/resolvers/zod';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { Button } from '@/components/ui/button';
import { Combobox, type ComboboxOption } from '@/components/ui/combobox';
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
    description?: string | null;
    urls: { update: string | null; destroy: string | null };
};

/** Champ du formulaire d'ajout / modification ; sa valeur initiale est lue dans l'élément (`element[cle]`). */
export type ChampReferentiel = {
    cle: string;
    label: string;
    type?: 'texte' | 'zone' | 'liste';
    max: number;
    /** Message affiché quand le champ obligatoire est vide (absent : champ facultatif). */
    requis?: string;
    placeholder?: string;
    hint?: string;
    /** Choix d'un champ `liste`. */
    options?: ComboboxOption[];
};

type Props<T extends ElementReferentiel> = {
    titre: string;
    description: ReactNode;
    /** Libellés : « type de cité », « un type de cité »… */
    singulier: string;
    un: string;
    /** Accords au féminin (« Cette filière », « Aucune filière enregistrée »). */
    feminin?: boolean;
    /** Longueur maximale du nom, pour le formulaire par défaut (nom + description). */
    nameMax?: number;
    /** Champs du formulaire, à la place du nom + description par défaut. */
    champs?: ChampReferentiel[];
    elements: Paginated<T>;
    stats: { total: number; utilises: number; inutilises: number };
    /** Libellés des trois indicateurs (par défaut : types du parc immobilier). */
    kpis?: { total: string; utilises: string; inutilises: string };
    filters: { q: string } & Record<string, string>;
    urls: { index: string; store: string | null };
    /** Nombre d'éléments qui utilisent celui-ci (bloque la suppression) et son libellé. */
    usage: (element: T) => { total: number; libelle: string };
    /** Conseil affiché quand la suppression est bloquée. */
    conseilSuppression?: string;
    /** Colonnes ajoutées avant le nom (ordre d'affichage…). */
    colonnesAvant?: DataTableColumn<T>[];
    /** Ajout en une seule étape, sans écran de vérification. */
    ajoutDirect?: boolean;
    /** Colonnes ajoutées entre la description et les actions. */
    colonnes?: DataTableColumn<T>[];
    /** Filtres ajoutés à côté de la recherche ; `filters` (hors `q`) est conservé pendant la recherche. */
    filtres?: ReactNode;
    recherche?: string;
    actions?: ReactNode;
    note?: ReactNode;
    icone: ReactNode;
};

/** Liste paginée + fenêtre d'ajout / modification en 2 étapes + suppression confirmée, commune aux référentiels simples. */
export function ReferentielPage<T extends ElementReferentiel>(props: Props<T>) {
    const { titre, description, singulier, un, feminin = false, elements, stats, filters, urls, usage, colonnesAvant = [], colonnes = [], filtres, actions, note, icone } = props;
    const champs = useMemo<ChampReferentiel[]>(
        () =>
            props.champs ?? [
                { cle: 'name', label: 'Nom', max: props.nameMax ?? 255, requis: 'Renseignez le nom.', placeholder: `Nom du ${singulier}` },
                { cle: 'description', label: 'Description', type: 'zone', max: 2000, placeholder: 'Description', hint: 'Facultative : usage, public concerné…' },
            ],
        [props.champs, props.nameMax, singulier],
    );
    const { q, ...autresFiltres } = filters;
    const [recherche, setRecherche] = useRecherche(urls.index, q, autresFiltres, ['elements', 'types', 'stats', 'filters']);
    const [edition, setEdition] = useState<T | 'nouveau' | null>(null);
    const [suppression, setSuppression] = useState<T | null>(null);
    const [suppressionEnCours, setSuppressionEnCours] = useState(false);
    const kpis = props.kpis ?? { total: 'Types enregistrés', utilises: 'Utilisés dans le parc', inutilises: 'Inutilisés' };
    const avecDescription = champs.some((c) => c.cle === 'description');

    const supprimer = async () => {
        if (!suppression?.urls.destroy) return;
        setSuppressionEnCours(true);
        const ok = await envoyer(suppression.urls.destroy, {}, { method: 'delete' });
        setSuppressionEnCours(false);
        if (ok) setSuppression(null);
    };

    const tableau: DataTableColumn<T>[] = [
        ...colonnesAvant,
        {
            key: 'name',
            header: 'Nom',
            cell: (e) => <span className="font-medium text-gray-900">{e.name}</span>,
        },
        ...(avecDescription
            ? [
                  {
                      key: 'description',
                      header: 'Description',
                      className: 'max-w-md',
                      cell: (e: T) => (e.description ? <span className="line-clamp-2 text-muted">{e.description}</span> : <span className="text-gray-400">—</span>),
                  },
              ]
            : []),
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
    const filtre = Object.values(autresFiltres).some((v) => v !== '' && v !== 'tous');

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
                    { key: 'total', label: kpis.total, value: nombre(stats.total), tone: 'green' },
                    { key: 'utilises', label: kpis.utilises, value: nombre(stats.utilises), tone: 'blue' },
                    { key: 'inutilises', label: kpis.inutilises, value: nombre(stats.inutilises), tone: 'neutral' },
                ]}
            />

            {note && <div className="mb-4">{note}</div>}

            <DataTable
                columns={tableau}
                data={elements}
                rowKey={(e) => e.id}
                toolbar={
                    <>
                        <ChampRecherche value={recherche} onChange={setRecherche} placeholder={props.recherche ?? 'Rechercher un nom ou une description'} />
                        {filtres}
                        <span className="text-sm text-muted sm:ml-auto">{nombre(elements.total)} résultat(s)</span>
                    </>
                }
                emptyMessage={
                    <span className="flex flex-col items-center gap-2">
                        <span className="text-gray-400" aria-hidden>
                            {icone}
                        </span>
                        {q || filtre
                            ? 'Aucun résultat pour cette recherche.'
                            : `${feminin ? 'Aucune' : 'Aucun'} ${singulier} ${feminin ? 'enregistrée' : 'enregistré'}.`}
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
                            un={un}
                            champs={champs}
                            direct={edition === 'nouveau' && !!props.ajoutDirect}
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
                        ? `${feminin ? 'Cette' : 'Ce'} ${singulier} est ${feminin ? 'utilisée' : 'utilisé'} par ${nombre(utilisation.total)} ${utilisation.libelle}. ${
                              props.conseilSuppression ?? 'Choisissez un autre type pour ces éléments avant de le supprimer.'
                          }`
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

const ETAPES = ['Informations', 'Vérification'];

function Formulaire({
    element,
    url,
    un,
    champs,
    direct = false,
    onClose,
}: {
    element: ElementReferentiel | null;
    url: string | null;
    un: string;
    champs: ChampReferentiel[];
    /** Une seule étape (saisie puis enregistrement), sans vérification. */
    direct?: boolean;
    onClose: () => void;
}) {
    const schema = useMemo(() => referentielSchema(champs), [champs]);
    const etapes = useMemo(() => (direct ? [champs.map((c) => c.cle)] : [champs.map((c) => c.cle), []]), [champs, direct]);
    const [erreur, setErreur] = useState<string | null>(null);
    const {
        register,
        control,
        trigger,
        watch,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<ReferentielValeurs>({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues: Object.fromEntries(champs.map((c) => [c.cle, String((element as Record<string, unknown> | null)?.[c.cle] ?? '')])),
    });
    const { etape, atteinte, aller, allerA, toutValider, derniere } = useAssistant<ReferentielValeurs>(etapes, trigger, element !== null);
    const valeurs = watch();

    const enregistrer = (v: ReferentielValeurs) =>
        envoyer<ReferentielValeurs>(url!, Object.fromEntries(champs.map((c) => [c.cle, (v[c.cle] ?? '').trim()])), {
            method: element ? 'put' : 'post',
            onSuccess: onClose,
            onError: (erreurs) => setErreur(repartirErreurs(erreurs, etapes, (champ, e) => setError(champ, e), aller)),
        });

    const apercu = (champ: ChampReferentiel) => {
        const valeur = (valeurs[champ.cle] ?? '').trim();
        if (champ.type === 'liste') return champ.options?.find((o) => o.value === valeur)?.label || <span className="text-muted">Aucun</span>;
        if (!valeur) return <span className="text-muted">{champ.cle === 'description' ? 'Aucune' : '—'}</span>;
        return champ.cle === 'name' ? <span className="font-medium">{valeur}</span> : valeur;
    };

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
                <DialogDescription>{direct ? 'Les champs marqués d’un astérisque sont obligatoires.' : `Étape ${etape + 1} sur ${ETAPES.length}`}</DialogDescription>
            </DialogHeader>
            {!direct && <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={(index) => void allerA(index)} />}
            <DialogBody className="space-y-4">
                <ErreurGenerale message={erreur} />
                {etape === 0 ? (
                    champs.map((champ, index) => {
                        const id = `ref-${champ.cle}`;
                        const message = errors[champ.cle]?.message;
                        return (
                            <Field key={champ.cle} id={id} label={champ.label} required={!!champ.requis} error={message} hint={champ.hint}>
                                {champ.type === 'liste' ? (
                                    <Controller
                                        control={control}
                                        name={champ.cle}
                                        render={({ field }) => (
                                            <Combobox
                                                id={id}
                                                value={field.value}
                                                onChange={field.onChange}
                                                onBlur={field.onBlur}
                                                options={champ.options ?? []}
                                                placeholder={champ.placeholder}
                                                invalid={!!message}
                                            />
                                        )}
                                    />
                                ) : champ.type === 'zone' ? (
                                    <Textarea id={id} rows={4} maxLength={champ.max} placeholder={champ.placeholder} invalid={!!message} {...register(champ.cle)} />
                                ) : (
                                    <Input id={id} maxLength={champ.max} placeholder={champ.placeholder} invalid={!!message} autoFocus={index === 0} {...register(champ.cle)} />
                                )}
                            </Field>
                        );
                    })
                ) : (
                    <Recap lignes={champs.map((champ) => ({ label: champ.label, valeur: apercu(champ) }))} />
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
