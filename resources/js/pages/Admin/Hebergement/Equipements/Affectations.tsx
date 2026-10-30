import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import {
    Archive,
    ArrowLeft,
    ArrowLeftRight,
    ArrowRight,
    Building2,
    Check,
    CircleAlert,
    DoorOpen,
    Houses,
    Info,
    Loader2,
    MapPin,
    Package,
    Pencil,
    Plus,
    Search,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { KpiFilters } from '@/components/ui/kpi-filters';
import { PageHeader } from '@/components/ui/page-header';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { envoyer } from '@/lib/inertia-form';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/global';

type Etat = 'bon' | 'moyen' | 'defectueux';
type Statut = 'actives' | 'a_reparer' | 'retournees';

type Affectation = {
    id: string;
    equipement: string | null;
    notes: string | null;
    cite: string | null;
    batiment: string | null;
    chambre: string | null;
    quantite: number;
    etat: Etat;
    retourne_le: string | null;
    affecte_le: string | null;
    urls: { update: string | null; retour: string | null };
};

type Props = {
    affectations: Paginated<Affectation>;
    equipements: { id: string; name: string; stock: number }[];
    cites: { id: string; name: string }[];
    batiments: { id: string; name: string; cite_id: string }[];
    chambres: { id: string; numero: string; batiment_id: string }[];
    statut: Statut;
    filters: { q: string; cite_id: string };
    stats: { actives: number; unites: number; retournees: number; a_reparer: number; defectueux: number; magasin: number };
    urls: { index: string; catalogue: string | null; store: string | null; filtres: Record<Statut, string> };
};

const ETATS: Record<Etat, { label: string; description: string; variant: 'success' | 'warning' | 'danger' }> = {
    bon: { label: 'Bon', description: 'Utilisable, peut revenir au magasin', variant: 'success' },
    moyen: { label: 'Moyen', description: 'Usé, à surveiller', variant: 'warning' },
    defectueux: { label: 'Défectueux', description: 'Hors d’usage, à réparer', variant: 'danger' },
};

const TITRES: Record<Statut, string> = {
    actives: 'Matériel affecté au parc',
    a_reparer: 'Matériel à remettre en état',
    retournees: 'Matériel retourné au magasin',
};

const TOUTES = '__toutes__';

/** Emplacement lisible d'une affectation (cité · bâtiment · chambre). */
const lieu = (ligne: Affectation) => [ligne.cite, ligne.batiment, ligne.chambre ? `Chambre ${ligne.chambre}` : null].filter(Boolean).join(' · ');

/** Matériel affecté aux cités, bâtiments et chambres (remplace `equipements/affectations.blade.php` et `equipements-affectations.js`). */
export default function EquipementsAffectations(props: Props) {
    const { affectations, cites, statut, filters, stats, urls } = props;
    const [recherche, setRecherche] = useState(filters.q);
    const [creation, setCreation] = useState(false);
    const [edition, setEdition] = useState<Affectation | null>(null);
    const [retour, setRetour] = useState<Affectation | null>(null);
    const [retourEnCours, setRetourEnCours] = useState(false);
    const premierRendu = useRef(true);

    const filtrer = (changements: Partial<Props['filters']>) => {
        const valeurs = { ...filters, q: recherche, ...changements };
        router.get(
            urls.index,
            Object.fromEntries(Object.entries({ statut, q: valeurs.q.trim(), cite_id: valeurs.cite_id }).filter(([, v]) => v !== '')),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    useEffect(() => {
        if (premierRendu.current) {
            premierRendu.current = false;
            return;
        }
        const minuteur = setTimeout(() => filtrer({ q: recherche }), 300);
        return () => clearTimeout(minuteur);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [recherche]);

    const filtresActifs = filters.q !== '' || filters.cite_id !== '';

    const colonnes: DataTableColumn<Affectation>[] = [
        { key: 'num', header: '#', headClassName: 'w-12', className: 'text-muted tabular-nums', cell: (ligne) => (affectations.from ?? 1) + affectations.data.indexOf(ligne) },
        {
            key: 'equipement',
            header: 'Équipement',
            cell: (ligne) => (
                <div className="flex min-w-44 items-center gap-3">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                        <Package size={18} strokeWidth={1.25} />
                    </span>
                    <div className="min-w-0">
                        <p className="font-medium text-gray-900">{ligne.equipement ?? 'Équipement indisponible'}</p>
                        {ligne.notes && (
                            <p className="line-clamp-1 max-w-64 text-xs text-muted" title={ligne.notes}>
                                {ligne.notes}
                            </p>
                        )}
                    </div>
                </div>
            ),
        },
        {
            key: 'lieu',
            header: 'Emplacement',
            cell: (ligne) => (
                <div className="flex flex-col gap-0.5">
                    <span>{ligne.cite}</span>
                    <span className="flex items-center gap-1 text-xs text-muted">
                        {ligne.chambre ? (
                            <>
                                <DoorOpen className="size-3.5" aria-hidden /> {ligne.batiment ?? 'Bâtiment'} · Chambre {ligne.chambre}
                            </>
                        ) : ligne.batiment ? (
                            <>
                                <Building2 className="size-3.5" aria-hidden /> {ligne.batiment} · commun au bâtiment
                            </>
                        ) : (
                            <>
                                <Houses className="size-3.5" aria-hidden /> Commun à la cité
                            </>
                        )}
                    </span>
                </div>
            ),
        },
        { key: 'quantite', header: 'Quantité', className: 'font-medium tabular-nums', cell: (ligne) => nombre(ligne.quantite) },
        { key: 'etat', header: 'État', cell: (ligne) => <Badge variant={ETATS[ligne.etat]?.variant ?? 'neutral'}>{ETATS[ligne.etat]?.label ?? ligne.etat}</Badge> },
        {
            key: 'suivi',
            header: 'Suivi',
            cell: (ligne) =>
                ligne.retourne_le ? (
                    <div className="flex flex-col gap-1 text-xs">
                        <Badge variant="neutral">
                            <Archive className="size-3.5" aria-hidden /> Retourné
                        </Badge>
                        <span className="text-muted">
                            le {dateFr(ligne.retourne_le)} à {ligne.retourne_le.slice(11, 16)}
                        </span>
                    </div>
                ) : (
                    <div className="flex flex-col gap-1 text-xs">
                        <Badge variant="info">
                            <MapPin className="size-3.5" aria-hidden /> Affecté
                        </Badge>
                        <span className="text-muted">depuis le {dateFr(ligne.affecte_le)}</span>
                    </div>
                ),
        },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (ligne) => (
                <RowActions>
                    {ligne.urls.update && <RowAction label="Mettre à jour l’état" icon={Pencil} tone="edit" onClick={() => setEdition(ligne)} />}
                    {ligne.urls.retour &&
                        (ligne.etat === 'bon' ? (
                            <RowAction label="Retour au magasin" icon={ArrowLeftRight} onClick={() => setRetour(ligne)} />
                        ) : (
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <span tabIndex={0} className="inline-flex size-8 cursor-not-allowed items-center justify-center rounded-brand text-gray-300">
                                        <ArrowLeftRight className="size-4.5" strokeWidth={1.5} aria-label="Retour impossible : matériel à remettre en bon état" />
                                    </span>
                                </TooltipTrigger>
                                <TooltipContent sideOffset={4}>Remettez le matériel en bon état avant son retour au magasin</TooltipContent>
                            </Tooltip>
                        ))}
                </RowActions>
            ),
        },
    ];

    const confirmerRetour = async () => {
        if (!retour?.urls.retour) return;
        setRetourEnCours(true);
        await envoyer(retour.urls.retour, {}, { onSuccess: () => setRetour(null) });
        setRetourEnCours(false);
    };

    return (
        <AdminLayout title="Affectations au parc">
            <PageHeader
                title="Affectations au parc"
                description="Chaque affectation retire la quantité du magasin ; un retour la réintègre. Le matériel en état moyen ou défectueux doit être remis en bon état avant son retour."
                actions={
                    (urls.catalogue || urls.store) && (
                        <>
                            {urls.catalogue && (
                                <a href={urls.catalogue} className="inline-flex h-10 items-center gap-2 rounded-brand border border-gray-300 bg-white px-4 text-sm font-medium text-muted hover:bg-surface">
                                    <Package {...ICON} aria-hidden />
                                    Catalogue du magasin
                                </a>
                            )}
                            {urls.store && (
                                <Button size="sm" className="h-10 px-4" onClick={() => setCreation(true)}>
                                    <Plus {...ICON} aria-hidden />
                                    Affecter un équipement
                                </Button>
                            )}
                        </>
                    )
                }
            />

            <KpiFilters
                label="Filtrer les affectations"
                active={statut}
                items={[
                    { key: 'actives', label: 'Affectations en cours', value: nombre(stats.actives), tone: 'blue', href: urls.filtres.actives, extra: `${nombre(stats.unites)} unité(s)` },
                    { key: 'a_reparer', label: 'Matériel à remettre en état', value: nombre(stats.a_reparer), tone: 'amber', href: urls.filtres.a_reparer, extra: `${nombre(stats.defectueux)} défectueuse(s)` },
                    { key: 'retournees', label: 'Retours au magasin', value: nombre(stats.retournees), tone: 'plum', href: urls.filtres.retournees },
                    { key: 'magasin', label: 'Disponible en magasin', value: nombre(stats.magasin), tone: 'green', extra: 'unité(s)', highlight: true },
                ]}
            />

            <section className="mt-6">
                <div className="mb-3 flex items-baseline justify-between gap-3">
                    <h2 className="text-base font-medium text-gray-900">{TITRES[statut]}</h2>
                    <span className="text-sm text-muted">{affectations.total} affectation(s)</span>
                </div>
                <DataTable
                    columns={colonnes}
                    data={affectations}
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
                                <Combobox
                                    value={filters.cite_id || TOUTES}
                                    onChange={(valeur) => filtrer({ cite_id: valeur === TOUTES ? '' : valeur })}
                                    options={[{ value: TOUTES, label: 'Toutes les cités' }, ...cites.map((cite) => ({ value: cite.id, label: cite.name }))]}
                                    searchPlaceholder="Rechercher une cité…"
                                    className="h-10"
                                />
                            </div>
                            {filtresActifs && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-10"
                                    onClick={() => {
                                        setRecherche('');
                                        router.get(urls.index, { statut }, { preserveScroll: true, replace: true });
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
                            {filtresActifs ? 'Aucune affectation ne correspond à ces critères.' : 'Aucune affectation dans cette catégorie.'}
                        </span>
                    }
                />
            </section>

            {urls.store && <AffectationWizard open={creation} onClose={() => setCreation(false)} url={urls.store} {...props} />}
            <EtatDialog affectation={edition} onClose={() => setEdition(null)} />
            <ConfirmDialog
                open={retour !== null}
                onOpenChange={(open) => !open && setRetour(null)}
                title="Retour au magasin"
                description={
                    <>
                        <p>
                            <strong className="font-medium text-gray-900">{nombre(retour?.quantite)}</strong> × « {retour?.equipement} » sera réintégré au stock disponible.
                        </p>
                        <p className="mt-2 text-xs">Emplacement libéré : {retour ? lieu(retour) : ''}. Cette opération est définitive.</p>
                    </>
                }
                confirmLabel="Confirmer le retour"
                confirmIcon={<ArrowLeftRight className="size-4" aria-hidden />}
                processing={retourEnCours}
                onConfirm={confirmerRetour}
            />
        </AdminLayout>
    );
}

/** Cartes de choix de l'état du matériel. */
function ChoixEtat({ value, onChange, name, colonnes = 3 }: { value: string | undefined; onChange: (etat: Etat) => void; name: string; colonnes?: 1 | 3 }) {
    return (
        <div role="radiogroup" className={cn('grid grid-cols-1 gap-2.5', colonnes === 3 && 'sm:grid-cols-3')}>
            {(Object.keys(ETATS) as Etat[]).map((etat) => (
                <label
                    key={etat}
                    className={cn('flex cursor-pointer items-start gap-2.5 rounded-brand border p-3 transition-colors hover:border-primary', value === etat && 'border-primary bg-soft')}
                >
                    <input type="radio" name={name} value={etat} checked={value === etat} onChange={() => onChange(etat)} className="mt-0.5 size-4 accent-primary" />
                    <span className="flex flex-col">
                        <span className="text-sm font-medium text-gray-900">{ETATS[etat].label}</span>
                        <span className="text-xs text-muted">{ETATS[etat].description}</span>
                    </span>
                </label>
            ))}
        </div>
    );
}

/** Mise à jour de l'état d'un matériel affecté (action `update` inchangée). */
function EtatDialog({ affectation, onClose }: { affectation: Affectation | null; onClose: () => void }) {
    const [etat, setEtat] = useState<Etat>('bon');
    const [enCours, setEnCours] = useState(false);

    useEffect(() => {
        if (affectation) setEtat(affectation.etat);
    }, [affectation]);

    const enregistrer = async () => {
        if (!affectation?.urls.update) return;
        setEnCours(true);
        await envoyer(affectation.urls.update, { etat }, { method: 'patch', onSuccess: onClose });
        setEnCours(false);
    };

    return (
        <Dialog open={affectation !== null} onOpenChange={(ouvert) => !ouvert && !enCours && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>État du matériel</DialogTitle>
                    <DialogDescription>
                        <strong className="font-medium text-gray-900">{affectation?.equipement}</strong> · {affectation ? lieu(affectation) : ''}
                    </DialogDescription>
                </DialogHeader>
                <DialogBody>
                    <p className="mb-1.5 text-sm font-medium text-gray-800">
                        Nouvel état <span className="text-red-600">*</span>
                    </p>
                    <ChoixEtat name="etat-affectation" value={etat} onChange={setEtat} colonnes={1} />
                </DialogBody>
                <DialogFooter>
                    <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={enCours}>
                        Annuler
                    </Button>
                    <Button size="sm" className="h-10" onClick={enregistrer} disabled={enCours}>
                        {enCours ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />}
                        Enregistrer
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

const ETAPES = ['Matériel', 'Emplacement', 'Vérification'];
const CHAMPS_ETAPE: (keyof AffectationSaisie)[][] = [['equipement_id', 'quantite', 'etat'], ['cite_id', 'batiment_id', 'chambre_id'], ['notes']];

const affectationSchema = (stocks: Map<string, number>) =>
    z
        .object({
            equipement_id: z.string().min(1, 'Choisissez un équipement.'),
            quantite: z.coerce.number({ error: 'Indiquez une quantité.' }).int('Nombre entier attendu.').min(1, 'Au moins 1 unité.').max(1000000, 'Quantité trop élevée.'),
            etat: z.enum(['bon', 'moyen', 'defectueux'], { error: 'Choisissez l’état à la sortie.' }),
            cite_id: z.string().min(1, 'Choisissez une cité.'),
            batiment_id: z.string(),
            chambre_id: z.string(),
            notes: z.string().trim().max(2000, '2 000 caractères au maximum.'),
        })
        .superRefine((valeurs, ctx) => {
            const stock = stocks.get(valeurs.equipement_id);
            if (stock !== undefined && valeurs.quantite > stock) {
                ctx.addIssue({ code: 'custom', path: ['quantite'], message: `Seulement ${nombre(stock)} unité(s) disponible(s) en magasin.` });
            }
        });

type AffectationSaisie = z.input<ReturnType<typeof affectationSchema>>;
type AffectationValeurs = z.output<ReturnType<typeof affectationSchema>>;

/** Assistant d'affectation en 3 étapes : chaque étape est validée avant la suivante (règles serveur inchangées). */
function AffectationWizard({ open, onClose, url, equipements, cites, batiments, chambres }: Props & { open: boolean; onClose: () => void; url: string }) {
    const stocks = useMemo(() => new Map(equipements.map((equipement) => [equipement.id, equipement.stock])), [equipements]);
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const {
        register,
        control,
        handleSubmit,
        reset,
        trigger,
        setValue,
        setError,
        getValues,
        formState: { errors, isSubmitting },
    } = useForm<AffectationSaisie, unknown, AffectationValeurs>({ resolver: zodResolver(affectationSchema(stocks)), mode: 'onTouched' });

    const [equipementId, quantite, citeId, batimentId] = useWatch({ control, name: ['equipement_id', 'quantite', 'cite_id', 'batiment_id'] });

    useEffect(() => {
        if (open) {
            reset({ equipement_id: '', quantite: 1, etat: 'bon', cite_id: '', batiment_id: '', chambre_id: '', notes: '' });
            setEtape(0);
            setAtteinte(0);
            setErreurServeur(null);
        }
    }, [open, reset]);

    const batimentsCite = batiments.filter((batiment) => batiment.cite_id === citeId);
    const chambresBatiment = chambres.filter((chambre) => chambre.batiment_id === batimentId);
    const stock = stocks.get(equipementId ?? '');

    const allerA = async (cible: number) => {
        // Avancer valide toutes les étapes intermédiaires ; reculer est toujours permis.
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPE[index]))) {
                setEtape(index);
                return;
            }
        }
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const onSubmit = (valeurs: AffectationValeurs) => {
        setErreurServeur(null);
        return envoyer(url, valeurs, {
            onSuccess: onClose,
            onError: (erreurs) => {
                // Erreurs de champ sous le champ concerné, puis retour sur l'étape du premier champ fautif.
                let fautive = -1;
                Object.entries(erreurs).forEach(([cle, message]) => {
                    const index = CHAMPS_ETAPE.findIndex((champs) => champs.includes(cle as keyof AffectationSaisie));
                    if (index < 0) {
                        setErreurServeur(message);
                        return;
                    }
                    setError(cle as keyof AffectationSaisie, { type: 'server', message });
                    fautive = fautive < 0 ? index : Math.min(fautive, index);
                });
                if (fautive >= 0) setEtape(fautive);
            },
        });
    };

    const valeurs = getValues();
    const nomCite = cites.find((cite) => cite.id === valeurs.cite_id)?.name;
    const nomBatiment = batiments.find((batiment) => batiment.id === valeurs.batiment_id)?.name;
    const numeroChambre = chambres.find((chambre) => chambre.id === valeurs.chambre_id)?.numero;
    const reste = stock !== undefined ? stock - Number(quantite || 0) : undefined;

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-2xl">
                <form onSubmit={handleSubmit(onSubmit, (fautes) => setEtape(Math.max(0, CHAMPS_ETAPE.findIndex((champs) => champs.some((champ) => fautes[champ])))))} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Affecter un équipement</DialogTitle>
                        <DialogDescription>
                            Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                        </DialogDescription>
                    </DialogHeader>
                    <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} />
                    <DialogBody className="space-y-4">
                        {erreurServeur && (
                            <p role="alert" className="flex items-center gap-2 rounded-brand border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                <CircleAlert className="size-4 shrink-0" aria-hidden />
                                {erreurServeur}
                            </p>
                        )}

                        {etape === 0 && (
                            <>
                                <p className="text-sm text-muted">Seul le matériel disponible en magasin peut être affecté.</p>
                                <Field
                                    id="af-equipement"
                                    label="Équipement"
                                    required
                                    error={errors.equipement_id?.message}
                                    hint={
                                        stock !== undefined && (
                                            <span className="inline-flex items-center gap-1">
                                                <Info className="size-3.5" aria-hidden /> {nombre(stock)} unité(s) disponible(s) en magasin.
                                            </span>
                                        )
                                    }
                                >
                                    <Controller
                                        control={control}
                                        name="equipement_id"
                                        render={({ field }) => (
                                            <Combobox
                                                id="af-equipement"
                                                value={field.value}
                                                onChange={field.onChange}
                                                onBlur={field.onBlur}
                                                invalid={!!errors.equipement_id}
                                                placeholder="Sélectionner un équipement"
                                                searchPlaceholder="Rechercher un équipement…"
                                                options={equipements.map((equipement) => ({
                                                    value: equipement.id,
                                                    label: equipement.name,
                                                    description: `${nombre(equipement.stock)} disponible(s)`,
                                                    disabled: equipement.stock < 1,
                                                }))}
                                            />
                                        )}
                                    />
                                </Field>
                                <div className="sm:w-1/2">
                                    <Field id="af-quantite" label="Quantité" required error={errors.quantite?.message}>
                                        <Input id="af-quantite" type="number" min={1} step={1} placeholder="Ex. 4" invalid={!!errors.quantite} {...register('quantite')} />
                                    </Field>
                                </div>
                                <fieldset>
                                    <legend className="mb-1.5 text-sm font-medium text-gray-800">
                                        État à la sortie <span className="text-red-600">*</span>
                                    </legend>
                                    <Controller control={control} name="etat" render={({ field }) => <ChoixEtat name="af-etat" value={field.value} onChange={field.onChange} />} />
                                    {errors.etat && <p className="mt-1.5 text-xs font-medium text-red-600">{errors.etat.message}</p>}
                                </fieldset>
                            </>
                        )}

                        {etape === 1 && (
                            <>
                                <p className="text-sm text-muted">Sans bâtiment, l’équipement est commun à la cité ; sans chambre, il est commun au bâtiment.</p>
                                <Field id="af-cite" label="Cité" required error={errors.cite_id?.message}>
                                    <Controller
                                        control={control}
                                        name="cite_id"
                                        render={({ field }) => (
                                            <Combobox
                                                id="af-cite"
                                                value={field.value}
                                                onChange={(valeur) => {
                                                    field.onChange(valeur);
                                                    setValue('batiment_id', '');
                                                    setValue('chambre_id', '');
                                                }}
                                                onBlur={field.onBlur}
                                                invalid={!!errors.cite_id}
                                                placeholder="Sélectionner une cité"
                                                searchPlaceholder="Rechercher une cité…"
                                                options={cites.map((cite) => ({ value: cite.id, label: cite.name }))}
                                            />
                                        )}
                                    />
                                </Field>
                                <Field id="af-batiment" label="Bâtiment" error={errors.batiment_id?.message} hint="Facultatif — choisissez d’abord la cité.">
                                    <Controller
                                        control={control}
                                        name="batiment_id"
                                        render={({ field }) => (
                                            <Combobox
                                                id="af-batiment"
                                                value={field.value}
                                                onChange={(valeur) => {
                                                    field.onChange(valeur);
                                                    setValue('chambre_id', '');
                                                }}
                                                disabled={!citeId}
                                                placeholder="Équipement commun à la cité"
                                                searchPlaceholder="Rechercher un bâtiment…"
                                                options={[{ value: '', label: 'Équipement commun à la cité' }, ...batimentsCite.map((batiment) => ({ value: batiment.id, label: batiment.name }))]}
                                            />
                                        )}
                                    />
                                </Field>
                                <Field id="af-chambre" label="Chambre" error={errors.chambre_id?.message} hint="Facultatif — choisissez d’abord le bâtiment.">
                                    <Controller
                                        control={control}
                                        name="chambre_id"
                                        render={({ field }) => (
                                            <Combobox
                                                id="af-chambre"
                                                value={field.value}
                                                onChange={field.onChange}
                                                disabled={!batimentId}
                                                placeholder="Équipement commun au bâtiment"
                                                searchPlaceholder="Rechercher une chambre…"
                                                options={[{ value: '', label: 'Équipement commun au bâtiment' }, ...chambresBatiment.map((chambre) => ({ value: chambre.id, label: `Chambre ${chambre.numero}` }))]}
                                            />
                                        )}
                                    />
                                </Field>
                            </>
                        )}

                        {etape === 2 && (
                            <>
                                <p className="text-sm text-muted">Contrôlez l’affectation avant de l’enregistrer : la quantité sera retirée du magasin.</p>
                                <dl className="divide-y rounded-brand border text-sm">
                                    <Recap terme="Équipement" valeur={equipements.find((equipement) => equipement.id === valeurs.equipement_id)?.name} />
                                    <Recap terme="Quantité" valeur={nombre(valeurs.quantite as number)} />
                                    <Recap terme="État à la sortie" valeur={ETATS[valeurs.etat as Etat]?.label} />
                                    <Recap
                                        terme="Emplacement"
                                        valeur={[nomCite, nomBatiment ?? 'commun à la cité', valeurs.batiment_id ? (numeroChambre ? `Chambre ${numeroChambre}` : 'commun au bâtiment') : null].filter(Boolean).join(' · ')}
                                    />
                                    <Recap terme="Stock restant en magasin" valeur={reste !== undefined ? nombre(reste) : '—'} />
                                </dl>
                                <Field id="af-notes" label="Observations" error={errors.notes?.message} hint="Facultatif — 2 000 caractères maximum.">
                                    <Textarea id="af-notes" rows={3} maxLength={2000} placeholder="Ex. Remplacement des matelas usagés" {...register('notes')} />
                                </Field>
                            </>
                        )}
                    </DialogBody>
                    <DialogFooter className="sm:justify-between">
                        <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={isSubmitting}>
                            Annuler
                        </Button>
                        <div className="flex flex-col-reverse gap-2 sm:flex-row">
                            {etape > 0 && (
                                <Button variant="outline" size="sm" className="h-10" onClick={() => setEtape(etape - 1)} disabled={isSubmitting}>
                                    <ArrowLeft className="size-4" aria-hidden /> Précédent
                                </Button>
                            )}
                            {etape < ETAPES.length - 1 ? (
                                <Button key="suivant" size="sm" className="h-10" onClick={() => allerA(etape + 1)}>
                                    Suivant <ArrowRight className="size-4" aria-hidden />
                                </Button>
                            ) : (
                                <Button key="envoyer" type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                                    {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />}
                                    Confirmer l’affectation
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function Recap({ terme, valeur }: { terme: string; valeur: string | undefined | null }) {
    return (
        <div className="flex flex-col gap-0.5 px-4 py-2.5 sm:flex-row sm:justify-between sm:gap-4">
            <dt className="text-muted">{terme}</dt>
            <dd className="font-medium text-gray-900 sm:text-right">{valeur || '—'}</dd>
        </div>
    );
}
