import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { ArrowLeftRight, Building2, Check, History, Loader2, Package, Pencil, Plus, Search, Tag, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { EquipementThumb, StockBadge } from '@/components/equipements/stock-badge';
import { MouvementDialog } from '@/components/equipements/mouvement-dialog';
import { STATUTS_STOCK, tauxStock, type EquipementItem, type StatutStock, type TypeEquipement } from '@/components/equipements/types';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { FileDrop } from '@/components/ui/file-drop';
import { Input } from '@/components/ui/input';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { symboleDevise } from '@/lib/systeme';
import { nombre, saisieMilliers } from '@/lib/text';
import type { Paginated } from '@/types/global';

type Ligne = EquipementItem & { urls: { mouvements: string | null; destroy: string | null } };
type Filtre = 'tous' | StatutStock;

type Props = {
    equipements: Paginated<Ligne>;
    stats: Record<Filtre, number>;
    stockTotal: number;
    filters: { statut: Filtre; q: string; type: string };
    types: TypeEquipement[];
    urls: {
        index: string;
        filtres: Record<Filtre, string>;
        affectations: string | null;
        storeType: string | null;
        store: string | null;
        update: string | null;
        storeMouvement: string | null;
    };
};

const TOUS_TYPES = '__tous__';

/** Catalogue du matériel en magasin (remplace `equipements/index.blade.php` et son script). */
export default function EquipementsIndex({ equipements, stats, stockTotal, filters, types, urls }: Props) {
    const [recherche, setRecherche] = useState(filters.q);
    const [creation, setCreation] = useState(false);
    const [nouveauType, setNouveauType] = useState(false);
    const [mouvement, setMouvement] = useState<Ligne | null>(null);
    const [edition, setEdition] = useState<Ligne | null>(null);
    const [suppression, setSuppression] = useState<Ligne | null>(null);
    const [suppressionEnCours, setSuppressionEnCours] = useState(false);
    const premierRendu = useRef(true);

    const filtrer = (changements: Partial<Props['filters']>) => {
        const valeurs = { ...filters, q: recherche, ...changements };
        router.get(
            urls.index,
            Object.fromEntries(Object.entries({ statut: valeurs.statut === 'tous' ? '' : valeurs.statut, q: valeurs.q.trim(), type: valeurs.type }).filter(([, v]) => v !== '')),
            { preserveState: true, preserveScroll: true, replace: true, only: ['equipements', 'stats', 'filters', 'urls'] },
        );
    };

    // Recherche instantanée (300 ms après la dernière frappe), comme l'ancienne page.
    useEffect(() => {
        if (premierRendu.current) {
            premierRendu.current = false;
            return;
        }
        const minuteur = setTimeout(() => filtrer({ q: recherche }), 300);
        return () => clearTimeout(minuteur);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [recherche]);

    const filtresActifs = filters.q !== '' || filters.type !== '' || filters.statut !== 'tous';

    const colonnes: DataTableColumn<Ligne>[] = [
        { key: 'num', header: '#', headClassName: 'w-12', className: 'text-muted tabular-nums', cell: (ligne) => (equipements.from ?? 1) + equipements.data.indexOf(ligne) },
        {
            key: 'equipement',
            header: 'Équipement',
            cell: (ligne) => (
                <div className="flex min-w-48 items-center gap-3">
                    <EquipementThumb photo={ligne.photo} />
                    <div className="min-w-0">
                        <p className="font-medium text-gray-900">{ligne.name}</p>
                        <p className="text-xs text-muted">{ligne.type ?? 'Type non défini'}</p>
                    </div>
                </div>
            ),
        },
        { key: 'initial', header: 'Stock initial', className: 'tabular-nums', cell: (ligne) => nombre(ligne.stock_initial) },
        {
            key: 'actuel',
            header: 'Stock actuel',
            cell: (ligne) => {
                const taux = tauxStock(ligne.stock_actuel, ligne.stock_initial);
                return (
                    <div className="flex w-32 flex-col gap-1">
                        <span className="font-medium tabular-nums">{nombre(ligne.stock_actuel)}</span>
                        <Progress value={taux} tone={STATUTS_STOCK[ligne.statut].barre} label={`${taux} % du stock initial`} />
                    </div>
                );
            },
        },
        { key: 'seuil', header: 'Seuil d’alerte', className: 'tabular-nums', cell: (ligne) => nombre(ligne.threshold) },
        { key: 'statut', header: 'Statut', cell: (ligne) => <StockBadge statut={ligne.statut} /> },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (ligne) => (
                <RowActions>
                    {ligne.urls.mouvements && <RowAction label="Historique des mouvements" icon={History} tone="view" href={ligne.urls.mouvements} />}
                    {urls.storeMouvement && <RowAction label="Entrée ou sortie de stock" icon={ArrowLeftRight} onClick={() => setMouvement(ligne)} />}
                    {urls.update && <RowAction label="Modifier" icon={Pencil} tone="edit" onClick={() => setEdition(ligne)} />}
                    {ligne.urls.destroy && <RowAction label="Supprimer" icon={Trash2} tone="danger" onClick={() => setSuppression(ligne)} />}
                </RowActions>
            ),
        },
    ];

    const supprimer = async () => {
        if (!suppression?.urls.destroy) return;
        setSuppressionEnCours(true);
        await envoyer(suppression.urls.destroy, {}, { method: 'delete', onSuccess: () => setSuppression(null) });
        setSuppressionEnCours(false);
    };

    return (
        <AdminLayout title="Équipements">
            <PageHeader
                title="Équipements"
                description="Catalogue du matériel en magasin : stock disponible, seuils d’alerte et mouvements d’entrée ou de sortie."
                actions={
                    (urls.affectations || urls.storeType || urls.store) && (
                        <>
                            {urls.affectations && (
                                <a href={urls.affectations} className="inline-flex h-10 items-center gap-2 rounded-brand border border-primary bg-white px-4 text-sm font-medium text-primary hover:bg-soft">
                                    <Building2 {...ICON} aria-hidden />
                                    Affectations au parc
                                </a>
                            )}
                            {urls.storeType && (
                                <Button variant="secondary" size="sm" className="h-10 px-4" onClick={() => setNouveauType(true)}>
                                    <Tag {...ICON} aria-hidden />
                                    Nouveau type
                                </Button>
                            )}
                            {urls.store && (
                                <Button size="sm" className="h-10 px-4" onClick={() => setCreation(true)}>
                                    <Plus {...ICON} aria-hidden />
                                    Entrée de stock
                                </Button>
                            )}
                        </>
                    )
                }
            />

            <KpiFilters
                label="Filtrer les équipements par statut de stock"
                active={filters.statut}
                items={[
                    { key: 'tous', label: 'Tous les équipements', value: nombre(stats.tous), tone: 'blue', href: urls.filtres.tous, extra: `${nombre(stockTotal)} unité(s) en magasin` },
                    { key: 'disponible', label: 'Stock suffisant', value: nombre(stats.disponible), tone: 'green', href: urls.filtres.disponible },
                    { key: 'faible', label: 'Stock faible', value: nombre(stats.faible), tone: 'amber', href: urls.filtres.faible },
                    { key: 'rupture', label: 'En rupture', value: nombre(stats.rupture), tone: 'plum', href: urls.filtres.rupture },
                ]}
            />

            <section className="mt-6">
                <div className="mb-3 flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-medium text-gray-900">Catalogue &amp; disponibilités</h2>
                    <span className="text-sm text-muted">{equipements.total} équipement(s)</span>
                </div>
                <DataTable
                    columns={colonnes}
                    data={equipements}
                    rowKey={(ligne) => ligne.id}
                    toolbar={
                        <>
                            <div className="w-full sm:max-w-xs">
                                <Input
                                    type="search"
                                    value={recherche}
                                    onChange={(event) => setRecherche(event.target.value)}
                                    placeholder="Rechercher un équipement…"
                                    aria-label="Rechercher un équipement"
                                    icon={<Search />}
                                    className="h-10"
                                />
                            </div>
                            <div className="w-full sm:w-56">
                                <Select value={filters.type || TOUS_TYPES} onValueChange={(valeur) => filtrer({ type: valeur === TOUS_TYPES ? '' : valeur })}>
                                    <SelectTrigger className="h-10" aria-label="Type d’équipement">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TOUS_TYPES}>Tous les types</SelectItem>
                                        {types.map((type) => (
                                            <SelectItem key={type.id} value={type.id}>
                                                {type.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            {filtresActifs && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-10"
                                    onClick={() => {
                                        setRecherche('');
                                        router.get(urls.index, {}, { preserveScroll: true, replace: true });
                                    }}
                                >
                                    Réinitialiser
                                </Button>
                            )}
                        </>
                    }
                    emptyMessage={
                        <span className="flex flex-col items-center gap-2">
                            <Package {...ICON} aria-hidden />
                            {filtresActifs ? 'Aucun équipement ne correspond à ces critères.' : 'Aucun équipement enregistré pour le moment.'}
                        </span>
                    }
                />
            </section>

            {urls.store && <CreationDialog open={creation} onClose={() => setCreation(false)} url={urls.store} types={types} />}
            {urls.storeType && <TypeDialog open={nouveauType} onClose={() => setNouveauType(false)} url={urls.storeType} />}
            {urls.storeMouvement && <MouvementDialog equipement={mouvement} url={urls.storeMouvement} onClose={() => setMouvement(null)} />}
            {urls.update && <EditionDialog equipement={edition} onClose={() => setEdition(null)} url={urls.update} types={types} />}
            <ConfirmDialog
                open={suppression !== null}
                onOpenChange={(open) => !open && setSuppression(null)}
                title="Supprimer l’équipement ?"
                description={
                    <>
                        <p>« {suppression?.name} » sera retiré du catalogue.</p>
                        <p className="mt-2 text-xs">La suppression n’est possible que si le stock est à zéro et qu’aucun mouvement n’est enregistré.</p>
                    </>
                }
                confirmLabel="Supprimer"
                confirmIcon={<Trash2 className="size-4" aria-hidden />}
                danger
                processing={suppressionEnCours}
                onConfirm={supprimer}
            />
        </AdminLayout>
    );
}

const PHOTOS = '.jpg,.jpeg,.png,.gif';
const entier = (message: string) => z.coerce.number({ error: message }).int('Nombre entier attendu.').min(0, 'La valeur ne peut pas être négative.');

const creationSchema = z.object({
    type_equipement_id: z.string().min(1, 'Choisissez le type d’équipement.'),
    name: z.string().trim().min(1, 'La désignation est obligatoire.').max(150, '150 caractères au maximum.'),
    stock_initial: entier('Indiquez la quantité reçue.'),
    // Saisi en texte avec séparateur de milliers (« 25 000 »), envoyé au serveur en nombre.
    prix: z
        .string()
        .trim()
        .min(1, 'Indiquez le coût unitaire.')
        .transform((valeur) => Number(valeur.replace(/\s/g, ''))),
    threshold: entier('Indiquez le seuil d’alerte.'),
    date_reception: z.string().min(1, 'Indiquez la date de réception.'),
});

const aujourdhui = () => new Date().toISOString().slice(0, 10);

/** Nouvel équipement + quantité reçue (action `store` inchangée). */
function CreationDialog({ open, onClose, url, types }: { open: boolean; onClose: () => void; url: string; types: TypeEquipement[] }) {
    const [photo, setPhoto] = useState<File | null>(null);
    const {
        register,
        control,
        handleSubmit,
        reset,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<z.input<typeof creationSchema>, unknown, z.output<typeof creationSchema>>({ resolver: zodResolver(creationSchema), mode: 'onTouched' });

    useEffect(() => {
        if (open) {
            reset({ type_equipement_id: '', name: '', stock_initial: '', prix: '0', threshold: 0, date_reception: aujourdhui() });
            setPhoto(null);
        }
    }, [open, reset]);

    const onSubmit = (valeurs: z.output<typeof creationSchema>) =>
        envoyer(url, { ...valeurs, ...(photo ? { photo } : {}) }, { forceFormData: true, setError, champs: ['type_equipement_id', 'name', 'stock_initial', 'prix', 'threshold', 'date_reception'], onSuccess: onClose });

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && onClose()}>
            <DialogContent className="max-w-2xl">
                <form onSubmit={handleSubmit(onSubmit)} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Nouvelle entrée de stock</DialogTitle>
                        <DialogDescription>Enregistre un nouvel équipement et sa quantité reçue en magasin.</DialogDescription>
                    </DialogHeader>
                    <DialogBody className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <Field id="eq-type" label="Type d’équipement" required error={errors.type_equipement_id?.message}>
                            <Controller
                                control={control}
                                name="type_equipement_id"
                                render={({ field }) => (
                                    <TypeSelect id="eq-type" value={field.value} onChange={field.onChange} onBlur={field.onBlur} types={types} invalid={!!errors.type_equipement_id} />
                                )}
                            />
                        </Field>
                        <Field id="eq-name" label="Désignation" required error={errors.name?.message}>
                            <Input id="eq-name" maxLength={150} placeholder="Ex. Matelas 1 place" invalid={!!errors.name} {...register('name')} />
                        </Field>
                        <Field id="eq-qty" label="Quantité reçue" required error={errors.stock_initial?.message}>
                            <Input id="eq-qty" type="number" min={0} step={1} placeholder="0" invalid={!!errors.stock_initial} {...register('stock_initial')} />
                        </Field>
                        <Field id="eq-prix" label={`Coût unitaire (${symboleDevise()})`} required error={errors.prix?.message}>
                            <Controller
                                control={control}
                                name="prix"
                                render={({ field }) => (
                                    <Input
                                        id="eq-prix"
                                        ref={field.ref}
                                        value={field.value ?? ''}
                                        onBlur={field.onBlur}
                                        onChange={(event) => field.onChange(saisieMilliers(event.target.value))}
                                        type="text"
                                        inputMode="numeric"
                                        autoComplete="off"
                                        placeholder="Ex. 25 000"
                                        className="tabular-nums"
                                        invalid={!!errors.prix}
                                    />
                                )}
                            />
                        </Field>
                        <Field id="eq-seuil" label="Seuil d’alerte" required error={errors.threshold?.message} hint="Statut « Stock faible » à partir de cette quantité.">
                            <Input id="eq-seuil" type="number" min={0} step={1} invalid={!!errors.threshold} {...register('threshold')} />
                        </Field>
                        <Field id="eq-date" label="Date de réception" required error={errors.date_reception?.message}>
                            <Controller
                                control={control}
                                name="date_reception"
                                render={({ field }) => <DatePicker id="eq-date" value={field.value ?? ''} onChange={field.onChange} invalid={!!errors.date_reception} />}
                            />
                        </Field>
                        <div className="sm:col-span-2">
                            <Field id="eq-photo" label="Photo" hint="Facultatif — JPG, PNG ou GIF.">
                                <FileDrop id="eq-photo" file={photo} onChange={setPhoto} accept={PHOTOS} title="Choisir une photo" hint="Facultatif — JPG, PNG ou GIF." />
                            </Field>
                        </div>
                    </DialogBody>
                    <PiedFormulaire onClose={onClose} enCours={isSubmitting} />
                </form>
            </DialogContent>
        </Dialog>
    );
}

const editionSchema = z.object({
    name: z.string().trim().min(1, 'La désignation est obligatoire.').max(150, '150 caractères au maximum.'),
    type_equipement_id: z.string().min(1, 'Choisissez le type d’équipement.'),
    threshold: entier('Indiquez le seuil d’alerte.'),
});

/** Modification d'un équipement (action `update` inchangée ; photo facultative). */
function EditionDialog({ equipement, onClose, url, types }: { equipement: Ligne | null; onClose: () => void; url: string; types: TypeEquipement[] }) {
    const [photo, setPhoto] = useState<File | null>(null);
    const {
        register,
        control,
        handleSubmit,
        reset,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<z.input<typeof editionSchema>, unknown, z.output<typeof editionSchema>>({ resolver: zodResolver(editionSchema), mode: 'onTouched' });

    useEffect(() => {
        if (equipement) {
            reset({ name: equipement.name, type_equipement_id: equipement.type_id ?? '', threshold: equipement.threshold });
            setPhoto(null);
        }
    }, [equipement, reset]);

    const onSubmit = (valeurs: z.output<typeof editionSchema>) =>
        envoyer(url, { equipement_id: equipement!.id, ...valeurs, ...(photo ? { photo } : {}) }, {
            method: 'put',
            forceFormData: true,
            setError,
            champs: ['name', 'type_equipement_id', 'threshold'],
            onSuccess: onClose,
        });

    return (
        <Dialog open={equipement !== null} onOpenChange={(ouvert) => !ouvert && onClose()}>
            <DialogContent>
                <form onSubmit={handleSubmit(onSubmit)} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Modifier l’équipement</DialogTitle>
                        <DialogDescription>{equipement?.name}</DialogDescription>
                    </DialogHeader>
                    <DialogBody className="space-y-4">
                        <Field id="ed-name" label="Désignation" required error={errors.name?.message}>
                            <Input id="ed-name" maxLength={150} invalid={!!errors.name} {...register('name')} />
                        </Field>
                        <Field id="ed-type" label="Type d’équipement" required error={errors.type_equipement_id?.message}>
                            <Controller
                                control={control}
                                name="type_equipement_id"
                                render={({ field }) => (
                                    <TypeSelect id="ed-type" value={field.value} onChange={field.onChange} onBlur={field.onBlur} types={types} invalid={!!errors.type_equipement_id} />
                                )}
                            />
                        </Field>
                        <Field id="ed-seuil" label="Seuil d’alerte" required error={errors.threshold?.message} hint="Le statut passe à « Stock faible » quand le stock atteint cette valeur.">
                            <Input id="ed-seuil" type="number" min={0} step={1} invalid={!!errors.threshold} {...register('threshold')} />
                        </Field>
                        <Field id="ed-photo" label="Nouvelle photo">
                            <FileDrop
                                id="ed-photo"
                                file={photo}
                                onChange={setPhoto}
                                accept={PHOTOS}
                                currentUrl={equipement?.photo}
                                title="Choisir une nouvelle photo"
                                hint="Facultatif — laisser vide pour conserver la photo actuelle."
                            />
                        </Field>
                    </DialogBody>
                    <PiedFormulaire onClose={onClose} enCours={isSubmitting} />
                </form>
            </DialogContent>
        </Dialog>
    );
}

const typeSchema = z.object({
    name: z.string().trim().min(1, 'Le nom est obligatoire.').max(255, '255 caractères au maximum.'),
    description: z.string().trim(),
});

/** Nouveau type d'équipement (action du paramétrage `type-equipement.store`, inchangée). */
function TypeDialog({ open, onClose, url }: { open: boolean; onClose: () => void; url: string }) {
    const {
        register,
        handleSubmit,
        reset,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<z.input<typeof typeSchema>, unknown, z.output<typeof typeSchema>>({ resolver: zodResolver(typeSchema), mode: 'onTouched' });

    useEffect(() => {
        if (open) reset({ name: '', description: '' });
    }, [open, reset]);

    const onSubmit = (valeurs: z.output<typeof typeSchema>) => envoyer(url, valeurs, { setError, champs: ['name', 'description'], onSuccess: onClose });

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && onClose()}>
            <DialogContent>
                <form onSubmit={handleSubmit(onSubmit)} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Nouveau type d’équipement</DialogTitle>
                    </DialogHeader>
                    <DialogBody className="space-y-4">
                        <Field id="type-name" label="Nom" required error={errors.name?.message}>
                            <Input id="type-name" maxLength={255} placeholder="Ex. Literie" invalid={!!errors.name} {...register('name')} />
                        </Field>
                        <Field id="type-description" label="Description" error={errors.description?.message}>
                            <Textarea id="type-description" rows={3} placeholder="Description du type d’équipement" {...register('description')} />
                        </Field>
                    </DialogBody>
                    <PiedFormulaire onClose={onClose} enCours={isSubmitting} />
                </form>
            </DialogContent>
        </Dialog>
    );
}

function TypeSelect({ id, value, onChange, onBlur, types, invalid }: { id: string; value: string; onChange: (valeur: string) => void; onBlur: () => void; types: TypeEquipement[]; invalid: boolean }) {
    return (
        <Select value={value || undefined} onValueChange={onChange} onOpenChange={(ouvert) => !ouvert && onBlur()}>
            <SelectTrigger id={id} invalid={invalid}>
                <SelectValue placeholder="Sélectionner le type" />
            </SelectTrigger>
            <SelectContent>
                {types.map((type) => (
                    <SelectItem key={type.id} value={type.id}>
                        {type.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function PiedFormulaire({ onClose, enCours }: { onClose: () => void; enCours: boolean }) {
    return (
        <DialogFooter>
            <Button variant="outline" size="sm" className="h-10" onClick={onClose}>
                Annuler
            </Button>
            <Button type="submit" size="sm" className="h-10" disabled={enCours}>
                {enCours ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />}
                Enregistrer
            </Button>
        </DialogFooter>
    );
}
