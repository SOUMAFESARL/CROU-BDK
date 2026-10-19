import { zodResolver } from '@hookform/resolvers/zod';
import { BedDouble, Check, Info, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Controller, useForm, type Path } from 'react-hook-form';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { fcfa } from '@/components/paiements/shared';
import { ChampRecherche, ErreurGenerale, Note, PiedAssistant, Recap, useAssistant, useRecherche } from '@/components/parametrage-hebergement/shared';
import { repartirErreurs } from '@/components/vie-residentielle/shared';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { nombre, saisieMilliers } from '@/lib/text';
import { cn } from '@/lib/utils';
import { enNombre, typeChambreSchema, type TypeChambreValeurs } from '@/schemas/parametrage-hebergement';
import type { Paginated } from '@/types/global';

type TypeChambre = {
    id: string;
    name: string;
    description: string | null;
    superficie: number | null;
    montant: number;
    avance: number;
    caution: number;
    equipements: string[];
    chambres: number;
    urls: { update: string | null; destroy: string | null };
};

type Props = {
    types: Paginated<TypeChambre>;
    stats: { total: number; chambres: number; loyer_min: number | null; loyer_max: number | null };
    equipementsDisponibles: string[];
    filters: { q: string };
    urls: { index: string; store: string | null };
};

const mois = (n: number) => `${nombre(n)} mois`;

/** Types de chambre (remplace `parametrage-herb/type-chambre/index.blade.php`) ; actions `store`, `update`, `destroy` inchangées. */
export default function TypesChambre({ types, stats, equipementsDisponibles, filters, urls }: Props) {
    const [recherche, setRecherche] = useRecherche(urls.index, filters.q, {}, ['types', 'stats', 'filters']);
    const [edition, setEdition] = useState<TypeChambre | 'nouveau' | null>(null);
    const [suppression, setSuppression] = useState<TypeChambre | null>(null);
    const [suppressionEnCours, setSuppressionEnCours] = useState(false);

    const supprimer = async () => {
        if (!suppression?.urls.destroy) return;
        setSuppressionEnCours(true);
        const ok = await envoyer(suppression.urls.destroy, {}, { method: 'delete' });
        setSuppressionEnCours(false);
        if (ok) setSuppression(null);
    };

    const colonnes: DataTableColumn<TypeChambre>[] = [
        {
            key: 'type',
            header: 'Type',
            cell: (t) => (
                <div className="min-w-40">
                    <p className="font-medium text-gray-900">{t.name}</p>
                    {t.description && <p className="line-clamp-1 text-xs text-muted">{t.description}</p>}
                </div>
            ),
        },
        { key: 'montant', header: 'Loyer mensuel', className: 'whitespace-nowrap tabular-nums', cell: (t) => fcfa(t.montant) },
        {
            key: 'conditions',
            header: 'Caution · avance',
            className: 'whitespace-nowrap text-sm',
            cell: (t) => (
                <>
                    {mois(t.caution)} · {mois(t.avance)}
                </>
            ),
        },
        { key: 'superficie', header: 'Superficie', className: 'whitespace-nowrap', cell: (t) => (t.superficie ? `${nombre(t.superficie)} m²` : <span className="text-gray-400">—</span>) },
        {
            key: 'equipements',
            header: 'Équipements',
            cell: (t) =>
                t.equipements.length === 0 ? (
                    <span className="text-muted">Aucun</span>
                ) : (
                    <div className="flex max-w-64 flex-wrap gap-1" title={t.equipements.join(', ')}>
                        {t.equipements.slice(0, 3).map((e) => (
                            <Badge key={e} variant="success">
                                {e}
                            </Badge>
                        ))}
                        {t.equipements.length > 3 && <Badge variant="neutral">+{t.equipements.length - 3}</Badge>}
                    </div>
                ),
        },
        { key: 'chambres', header: 'Chambres', className: 'tabular-nums', cell: (t) => nombre(t.chambres) },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (t) => (
                <RowActions>
                    {t.urls.update && <RowAction label="Modifier" icon={Pencil} tone="edit" onClick={() => setEdition(t)} />}
                    {t.urls.destroy && <RowAction label="Supprimer" icon={Trash2} tone="danger" onClick={() => setSuppression(t)} />}
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Types de chambre">
            <PageHeader
                title="Types de chambre"
                description="Loyer, caution, avance et équipements de base proposés à la création des chambres."
                actions={
                    urls.store && (
                        <Button size="sm" className="h-10" onClick={() => setEdition('nouveau')}>
                            <Plus className="size-4" aria-hidden /> Ajouter un type de chambre
                        </Button>
                    )
                }
            />

            <KpiFilters
                label="Indicateurs des types de chambre"
                className="mb-4 xl:grid-cols-3"
                items={[
                    { key: 'total', label: 'Types de chambre', value: nombre(stats.total), tone: 'green' },
                    { key: 'chambres', label: 'Chambres rattachées', value: nombre(stats.chambres), tone: 'blue' },
                    {
                        key: 'loyers',
                        label: 'Loyers mensuels',
                        value: stats.loyer_min === null ? '—' : stats.loyer_min === stats.loyer_max ? fcfa(stats.loyer_min) : `${nombre(stats.loyer_min)} à ${fcfa(stats.loyer_max ?? 0)}`,
                        tone: 'amber',
                    },
                ]}
            />

            <DataTable
                columns={colonnes}
                data={types}
                rowKey={(t) => t.id}
                toolbar={
                    <>
                        <ChampRecherche value={recherche} onChange={setRecherche} placeholder="Rechercher un type de chambre" />
                        <span className="text-sm text-muted sm:ml-auto">{nombre(types.total)} résultat(s)</span>
                    </>
                }
                emptyMessage={
                    <span className="flex flex-col items-center gap-2">
                        <BedDouble {...ICON} className="text-gray-400" aria-hidden />
                        {filters.q ? 'Aucun résultat pour cette recherche.' : 'Aucun type de chambre enregistré.'}
                    </span>
                }
            />

            <Dialog open={edition !== null} onOpenChange={(ouvert) => !ouvert && setEdition(null)}>
                <DialogContent className="max-w-2xl">
                    {edition !== null && (
                        <Formulaire
                            key={edition === 'nouveau' ? 'nouveau' : edition.id}
                            type={edition === 'nouveau' ? null : edition}
                            url={edition === 'nouveau' ? urls.store : edition.urls.update}
                            disponibles={equipementsDisponibles}
                            onClose={() => setEdition(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={suppression !== null}
                onOpenChange={(ouvert) => !ouvert && setSuppression(null)}
                title={`Supprimer le type « ${suppression?.name ?? ''} » ?`}
                description={
                    suppression && suppression.chambres > 0
                        ? `${nombre(suppression.chambres)} chambre(s) utilisent ce type (loyer, caution, capacité). Changez leur type avant de le supprimer.`
                        : 'Cette suppression est définitive.'
                }
                confirmLabel="Supprimer"
                confirmIcon={<Trash2 className="size-4" aria-hidden />}
                danger
                processing={suppressionEnCours}
                confirmDisabled={(suppression?.chambres ?? 0) > 0}
                onConfirm={() => void supprimer()}
            />
        </AdminLayout>
    );
}

const ETAPES = ['Identification', 'Tarifs', 'Équipements', 'Vérification'];
const CHAMPS: Path<TypeChambreValeurs>[][] = [['name', 'description', 'superficie'], ['montant', 'caution', 'avance'], ['equipements'], []];

function Formulaire({ type, url, disponibles, onClose }: { type: TypeChambre | null; url: string | null; disponibles: string[]; onClose: () => void }) {
    const [erreur, setErreur] = useState<string | null>(null);
    const {
        register,
        control,
        trigger,
        watch,
        setValue,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<TypeChambreValeurs>({
        resolver: zodResolver(typeChambreSchema),
        mode: 'onTouched',
        defaultValues: {
            name: type?.name ?? '',
            description: type?.description ?? '',
            superficie: type?.superficie ? String(type.superficie) : '',
            montant: type ? String(type.montant) : '',
            caution: type ? String(type.caution) : '',
            avance: type ? String(type.avance) : '',
            equipements: type?.equipements ?? [],
        },
    });
    const { etape, atteinte, aller, allerA, toutValider, derniere } = useAssistant<TypeChambreValeurs>(CHAMPS, trigger, type !== null);
    const v = watch();
    // Équipements enregistrés hors de la liste proposée : affichés pour ne pas les perdre à l'enregistrement.
    const liste = [...disponibles, ...v.equipements.filter((e) => !disponibles.includes(e))];

    const enregistrer = (valeurs: TypeChambreValeurs) =>
        envoyer<TypeChambreValeurs>(
            url!,
            {
                name: valeurs.name.trim(),
                description: valeurs.description.trim(),
                superficie: enNombre(valeurs.superficie),
                montant: enNombre(valeurs.montant),
                caution: enNombre(valeurs.caution),
                avance: enNombre(valeurs.avance),
                // Même forme que l'ancien formulaire : equipement[Chaise]=Chaise.
                equipement: Object.fromEntries(valeurs.equipements.map((e) => [e, e])),
            },
            {
                method: type ? 'put' : 'post',
                onSuccess: onClose,
                onError: (erreurs) =>
                    setErreur(
                        repartirErreurs(
                            Object.fromEntries(Object.entries(erreurs).map(([cle, message]) => [cle.startsWith('equipement') ? 'equipements' : cle, message])),
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
                <DialogTitle>{type ? `Modifier le type « ${type.name} »` : 'Ajouter un type de chambre'}</DialogTitle>
                <DialogDescription>
                    Étape {etape + 1} sur {ETAPES.length}
                    {type && type.chambres > 0 && ` · ${nombre(type.chambres)} chambre(s) utilisent ce type`}
                </DialogDescription>
            </DialogHeader>
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={(index) => void allerA(index)} />
            <DialogBody className="space-y-4">
                <ErreurGenerale message={erreur} />

                {etape === 0 && (
                    <>
                        <Field
                            id="tc-name"
                            label="Nom du type"
                            required
                            error={errors.name?.message}
                            hint="« Individuelle », « Double » et « Triple » fixent la capacité des chambres (1, 2 ou 3 places) ; tout autre nom compte pour 1 place."
                        >
                            <Input id="tc-name" maxLength={100} placeholder="Ex. Individuelle, Double…" invalid={!!errors.name} autoFocus {...register('name')} />
                        </Field>
                        <Field id="tc-description" label="Description" error={errors.description?.message}>
                            <Textarea id="tc-description" rows={3} maxLength={2000} placeholder="Ex. Pour deux personnes, deux lits" invalid={!!errors.description} {...register('description')} />
                        </Field>
                        <Field id="tc-superficie" label="Superficie (m²)" error={errors.superficie?.message} hint="Facultative">
                            <Input id="tc-superficie" inputMode="numeric" placeholder="Ex. 12" invalid={!!errors.superficie} {...register('superficie')} />
                        </Field>
                    </>
                )}

                {etape === 1 && (
                    <>
                        <Controller
                            control={control}
                            name="montant"
                            render={({ field }) => (
                                <Field id="tc-montant" label="Loyer mensuel (FCFA)" required error={errors.montant?.message}>
                                    <Input
                                        id="tc-montant"
                                        inputMode="numeric"
                                        placeholder="Ex. 6 000"
                                        invalid={!!errors.montant}
                                        value={saisieMilliers(field.value)}
                                        onChange={(event) => field.onChange(event.target.value.replace(/\D/g, ''))}
                                        onBlur={field.onBlur}
                                        ref={field.ref}
                                    />
                                </Field>
                            )}
                        />
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field id="tc-caution" label="Caution (nombre de mois)" required error={errors.caution?.message}>
                                <Input id="tc-caution" inputMode="numeric" placeholder="Ex. 1" invalid={!!errors.caution} {...register('caution')} />
                            </Field>
                            <Field id="tc-avance" label="Avance (nombre de mois)" required error={errors.avance?.message}>
                                <Input id="tc-avance" inputMode="numeric" placeholder="Ex. 6" invalid={!!errors.avance} {...register('avance')} />
                            </Field>
                        </div>
                        <Note icone={<Info />} ton={type && type.chambres > 0 ? 'attention' : 'info'}>
                            <p>
                                Le loyer mensuel sert au calcul des loyers de toutes les chambres de ce type, y compris celles déjà occupées. La caution et l’avance
                                sont reprises à la création des chambres.
                            </p>
                        </Note>
                    </>
                )}

                {etape === 2 && (
                    <fieldset className="space-y-3">
                        <legend className="flex w-full items-center justify-between gap-3 text-sm font-medium text-gray-800">
                            <span>
                                Équipements de base <span className="text-red-600">*</span>
                            </span>
                            <button
                                type="button"
                                className="cursor-pointer text-sm font-normal text-primary hover:underline"
                                onClick={() =>
                                    setValue('equipements', v.equipements.length === liste.length ? [] : liste, { shouldValidate: true, shouldDirty: true })
                                }
                            >
                                {v.equipements.length === liste.length ? 'Tout décocher' : 'Tout cocher'}
                            </button>
                        </legend>
                        <Controller
                            control={control}
                            name="equipements"
                            render={({ field }) => (
                                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    {liste.map((equipement) => {
                                        const coche = field.value.includes(equipement);
                                        const id = `tc-eq-${equipement}`;
                                        return (
                                            <label
                                                key={equipement}
                                                htmlFor={id}
                                                className={cn(
                                                    'flex cursor-pointer items-center gap-2.5 rounded-brand border px-3 py-2.5 text-sm transition-colors',
                                                    coche ? 'border-primary bg-soft text-gray-900' : 'border-gray-200 bg-white text-muted hover:border-gray-300',
                                                )}
                                            >
                                                <Checkbox
                                                    id={id}
                                                    checked={coche}
                                                    onCheckedChange={(valeur) =>
                                                        field.onChange(valeur === true ? [...field.value, equipement] : field.value.filter((e) => e !== equipement))
                                                    }
                                                />
                                                {equipement}
                                            </label>
                                        );
                                    })}
                                </div>
                            )}
                        />
                        {errors.equipements && (
                            <p role="alert" className="text-xs font-medium text-red-600">
                                {errors.equipements.message}
                            </p>
                        )}
                    </fieldset>
                )}

                {etape === 3 && (
                    <Recap
                        lignes={[
                            { label: 'Nom', valeur: <span className="font-medium">{v.name.trim()}</span> },
                            { label: 'Description', valeur: v.description.trim() || <span className="text-muted">Aucune</span> },
                            { label: 'Superficie', valeur: v.superficie ? `${nombre(enNombre(v.superficie))} m²` : <span className="text-muted">Non renseignée</span> },
                            { label: 'Loyer mensuel', valeur: <span className="font-medium">{fcfa(enNombre(v.montant) ?? 0)}</span> },
                            { label: 'Caution · avance', valeur: `${mois(enNombre(v.caution) ?? 0)} · ${mois(enNombre(v.avance) ?? 0)}` },
                            {
                                label: `Équipements (${v.equipements.length})`,
                                valeur: (
                                    <span className="flex flex-wrap gap-1 sm:justify-end">
                                        {v.equipements.map((e) => (
                                            <Badge key={e} variant="success">
                                                <Check className="size-3" aria-hidden /> {e}
                                            </Badge>
                                        ))}
                                    </span>
                                ),
                            },
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
                libelle={type ? 'Enregistrer les modifications' : 'Ajouter le type'}
            />
        </form>
    );
}
