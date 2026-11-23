import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, Ban, Gavel, Loader2, LogOut, MinusCircle, Pencil, Plus, Save, ShieldCheck, Trash2 } from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Bloc, ErreurGenerale, Note, PiedAssistant, Recap, useAssistant } from '@/components/parametrage-hebergement/shared';
import { ChoiceCard, ToggleCard } from '@/components/parc-immobilier/form/inputs';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { ChampsManquants } from '@/components/ui/champs-manquants';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { StatCard } from '@/components/ui/stat-card';
import { Stepper } from '@/components/ui/stepper';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { repartirErreurs } from '@/components/vie-residentielle/shared';
import { champsManquants } from '@/lib/champs-manquants';
import { envoyer } from '@/lib/inertia-form';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import {
    enNombre,
    ETAPES_TYPE,
    noteInitialeSchema,
    points,
    typeSanctionSchema,
    type Effet,
    type NoteInitialeValeurs,
    type TypeSanctionValeurs,
} from '@/schemas/parametrage-sanctions';

type TypeSanction = {
    id: string | null;
    code: string;
    libelle: string;
    points_retires: number;
    effet: Effet;
    effet_label: string;
    description: string | null;
    actif: boolean;
    utilisations: number;
    urls: { update: string | null; destroy: string | null };
};

type Props = {
    noteInitiale: number;
    types: TypeSanction[];
    effets: { value: Effet; label: string }[];
    stats: { total: number; actifs: number; bloquants: number };
    /** Borne de la note initiale et des points retirés. */
    max: number;
    urls: { parametrage: string; note: string | null; store: string | null };
};

/** Effet d'un type de sanction : conséquence rappelée à l'administrateur et ton du badge. */
const EFFETS: Record<Effet, { aide: string; variante: 'neutral' | 'warning' | 'danger'; icone: ReactNode }> = {
    aucun: { aide: 'Seuls les points sont retirés de la note de conduite.', variante: 'neutral', icone: <MinusCircle {...ICON} aria-hidden /> },
    suspension: {
        aide: 'Bloque les nouvelles admissions, attributions et confirmations jusqu’à une date de fin, obligatoire à la décision.',
        variante: 'warning',
        icone: <Ban {...ICON} aria-hidden />,
    },
    expulsion: {
        aide: 'Bloque, annule les réservations et prépare la sortie du logement. Décidée uniquement depuis un rapport disciplinaire.',
        variante: 'danger',
        icone: <LogOut {...ICON} aria-hidden />,
    },
};

/**
 * Paramétrage des sanctions : note de conduite initiale des étudiants et types de sanction (points retirés, effet),
 * utilisés par les décisions des incidents disciplinaires et les sanctions directes. La note minimale de conduite
 * exigée en réadmission se fixe dans les critères de la campagne.
 */
export default function ParametrageSanctions({ noteInitiale, types, effets, stats, max, urls }: Props) {
    const [edition, setEdition] = useState<TypeSanction | 'nouveau' | null>(null);
    const [suppression, setSuppression] = useState<TypeSanction | null>(null);
    const [suppressionEnCours, setSuppressionEnCours] = useState(false);

    const supprimer = async () => {
        if (!suppression?.urls.destroy) return;
        setSuppressionEnCours(true);
        const ok = await envoyer(suppression.urls.destroy, {}, { method: 'delete' });
        setSuppressionEnCours(false);
        if (ok) setSuppression(null);
    };

    return (
        <AdminLayout title="Paramétrage des sanctions">
            <PageHeader
                title="Paramétrage des sanctions"
                description="Note de conduite attribuée à chaque étudiant et points retirés par chaque sanction. Ces sanctions sont proposées dans tout le système, notamment lors de la décision d’un incident disciplinaire."
                actions={
                    <>
                        <a href={urls.parametrage} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                            <ArrowLeft className="size-4" aria-hidden /> Paramétrage
                        </a>
                        {urls.store && (
                            <Button size="sm" className="h-10" onClick={() => setEdition('nouveau')}>
                                <Plus className="size-4" aria-hidden /> Ajouter une sanction
                            </Button>
                        )}
                    </>
                }
            />

            <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <StatCard tone="green" label="Note de conduite initiale" value={`${points(noteInitiale)} pts`} extra="Attribuée à chaque étudiant" />
                <StatCard tone="blue" label="Sanctions proposées" value={nombre(stats.actifs)} extra={`${nombre(stats.total)} enregistrée(s)`} />
                <StatCard tone="amber" label="Sanctions bloquantes" value={nombre(stats.bloquants)} extra="Suspension ou expulsion" />
            </div>

            <div className="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,22rem)_minmax(0,1fr)]">
                <Bloc titre="Note de conduite initiale" icone={<ShieldCheck {...ICON} />}>
                    <NoteInitiale valeur={noteInitiale} max={max} url={urls.note} />
                </Bloc>

                <Bloc titre="Types de sanction" icone={<Gavel {...ICON} />}>
                    {types.length === 0 ? (
                        <p className="px-4 py-10 text-center text-sm text-muted">Aucune sanction enregistrée.</p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Sanction</TableHead>
                                    <TableHead className="whitespace-nowrap">Points retirés</TableHead>
                                    <TableHead>Effet</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead className="whitespace-nowrap">Appliquée</TableHead>
                                    <TableHead>
                                        <span className="sr-only">Actions</span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {types.map((type) => (
                                    <TableRow key={type.code}>
                                        <TableCell className="max-w-xs">
                                            <span className="block font-medium text-gray-900">{type.libelle}</span>
                                            {type.description && <span className="line-clamp-2 text-xs text-muted">{type.description}</span>}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-gray-900">−{points(type.points_retires)} pts</TableCell>
                                        <TableCell>
                                            <Badge variant={EFFETS[type.effet].variante}>{type.effet_label}</Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={type.actif ? 'success' : 'neutral'}>{type.actif ? 'Proposée' : 'Désactivée'}</Badge>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted">{nombre(type.utilisations)} fois</TableCell>
                                        <TableCell className="text-right">
                                            <RowActions>
                                                {type.urls.update && <RowAction label="Modifier" icon={Pencil} tone="edit" onClick={() => setEdition(type)} />}
                                                {type.urls.destroy && <RowAction label="Supprimer" icon={Trash2} tone="danger" onClick={() => setSuppression(type)} />}
                                            </RowActions>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </Bloc>
            </div>

            <Dialog open={edition !== null} onOpenChange={(ouvert) => !ouvert && setEdition(null)}>
                <DialogContent className="max-w-xl">
                    {edition !== null && (
                        <FormulaireType
                            key={edition === 'nouveau' ? 'nouveau' : edition.code}
                            type={edition === 'nouveau' ? null : edition}
                            url={edition === 'nouveau' ? urls.store : edition.urls.update}
                            effets={effets}
                            max={max}
                            onClose={() => setEdition(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={suppression !== null}
                onOpenChange={(ouvert) => !ouvert && setSuppression(null)}
                title={`Supprimer « ${suppression?.libelle ?? ''} » ?`}
                description={
                    suppression && suppression.utilisations > 0
                        ? `Cette sanction a déjà été appliquée ${nombre(suppression.utilisations)} fois : désactivez-la pour ne plus la proposer, son historique reste lisible.`
                        : 'Cette suppression est définitive.'
                }
                confirmLabel="Supprimer"
                confirmIcon={<Trash2 className="size-4" aria-hidden />}
                danger
                processing={suppressionEnCours}
                confirmDisabled={(suppression?.utilisations ?? 0) > 0}
                onConfirm={() => void supprimer()}
            />
        </AdminLayout>
    );
}

/** Note initiale de chaque étudiant ; la note courante = note initiale − points retirés par ses sanctions. */
function NoteInitiale({ valeur, max, url }: { valeur: number; max: number; url: string | null }) {
    const schema = useMemo(() => noteInitialeSchema(max), [max]);
    const {
        register,
        handleSubmit,
        setError,
        formState: { errors, isSubmitting, isDirty },
    } = useForm<NoteInitialeValeurs>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: { note_initiale: points(valeur) } });

    return (
        <form
            noValidate
            className="space-y-4 p-4"
            onSubmit={handleSubmit((v) => envoyer<NoteInitialeValeurs>(url!, { note_initiale: enNombre(v.note_initiale) }, { method: 'put', setError, champs: ['note_initiale'] }))}
        >
            <fieldset disabled={!url || isSubmitting} className="space-y-4">
                <Field id="note_initiale" label="Note de départ" required error={errors.note_initiale?.message} hint={`Entre 1 et ${max} points.`}>
                    <Input
                        id="note_initiale"
                        inputMode="decimal"
                        placeholder="Ex. 20"
                        invalid={!!errors.note_initiale}
                        trailing={<span className="pointer-events-none px-3 text-xs text-muted">points</span>}
                        className="pr-20"
                        {...register('note_initiale')}
                    />
                </Field>
                {url && (
                    <Button type="submit" size="sm" className="h-10 w-full" disabled={isSubmitting || !isDirty}>
                        {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Save className="size-4" aria-hidden />}
                        Enregistrer la note
                    </Button>
                )}
            </fieldset>
            {!url && <p className="text-xs text-muted">Consultation seule : la modification est réservée au droit « Paramétrer les sanctions ».</p>}
        </form>
    );
}

const ETAPES = ['Sanction', 'Effet', 'Vérification'];

function FormulaireType({
    type,
    url,
    effets,
    max,
    onClose,
}: {
    type: TypeSanction | null;
    url: string | null;
    effets: Props['effets'];
    max: number;
    onClose: () => void;
}) {
    const schema = useMemo(() => typeSanctionSchema(max), [max]);
    const [erreur, setErreur] = useState<string | null>(null);
    const {
        register,
        control,
        trigger,
        setValue,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<TypeSanctionValeurs>({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues: {
            libelle: type?.libelle ?? '',
            points_retires: type ? points(type.points_retires) : '',
            description: type?.description ?? '',
            effet: type?.effet ?? 'aucun',
            actif: type?.actif ?? true,
        },
    });
    const { etape, atteinte, aller, allerA, toutValider, derniere } = useAssistant<TypeSanctionValeurs>(ETAPES_TYPE, trigger, type !== null);
    const v = useWatch({ control }) as TypeSanctionValeurs;
    const manquants = champsManquants(schema, v, ETAPES_TYPE, { libelle: 'Libellé', points_retires: 'Points retirés', effet: 'Effet' });
    // L'effet d'une sanction déjà appliquée est figé : les sanctions existantes en dépendent.
    const effetFige = (type?.utilisations ?? 0) > 0;

    const enregistrer = (valeurs: TypeSanctionValeurs) =>
        envoyer<TypeSanctionValeurs>(
            url!,
            { ...valeurs, points_retires: enNombre(valeurs.points_retires), actif: valeurs.actif ? 1 : 0 },
            {
                method: type ? 'put' : 'post',
                onSuccess: onClose,
                onError: (erreurs) => setErreur(repartirErreurs(erreurs, ETAPES_TYPE, (champ, e) => setError(champ, e), aller)),
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
                <DialogTitle>{type ? `Modifier « ${type.libelle} »` : 'Ajouter une sanction'}</DialogTitle>
                <DialogDescription>Étape {etape + 1} sur {ETAPES.length}</DialogDescription>
            </DialogHeader>
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={(index) => void allerA(index)} />
            <DialogBody className="space-y-4">
                <ErreurGenerale message={erreur} />
                {etape === 0 && (
                    <>
                        <Field id="ts-libelle" label="Libellé" required error={errors.libelle?.message}>
                            <Input id="ts-libelle" maxLength={100} placeholder="Ex. Avertissement" autoFocus invalid={!!errors.libelle} {...register('libelle')} />
                        </Field>
                        <Field
                            id="ts-points"
                            label="Points retirés à la note de conduite"
                            required
                            error={errors.points_retires?.message}
                            hint={type ? 'Le nouveau barème s’applique aux prochaines sanctions ; les sanctions passées gardent leurs points.' : `Entre 0 et ${max} points.`}
                        >
                            <Input
                                id="ts-points"
                                inputMode="decimal"
                                placeholder="Ex. 2"
                                invalid={!!errors.points_retires}
                                trailing={<span className="pointer-events-none px-3 text-xs text-muted">points</span>}
                                className="pr-20"
                                {...register('points_retires')}
                            />
                        </Field>
                        <Field id="ts-description" label="Description" error={errors.description?.message} hint="Facultative : faits visés, conditions d’application…">
                            <Textarea id="ts-description" rows={3} maxLength={1000} placeholder="Description" invalid={!!errors.description} {...register('description')} />
                        </Field>
                    </>
                )}
                {etape === 1 && (
                    <>
                        <Field id="ts-effet" label="Effet de la sanction" required error={errors.effet?.message}>
                            <div className="grid grid-cols-1 gap-3">
                                {effets.map((effet) => (
                                    <ChoiceCard
                                        key={effet.value}
                                        name="effet"
                                        value={effet.value}
                                        selected={v.effet === effet.value}
                                        onSelect={() => !effetFige && setValue('effet', effet.value, { shouldDirty: true, shouldValidate: true })}
                                        title={effet.label}
                                        description={EFFETS[effet.value].aide}
                                        icon={EFFETS[effet.value].icone}
                                    />
                                ))}
                            </div>
                        </Field>
                        {effetFige && <Note ton="attention">Cette sanction a déjà été appliquée : son effet ne peut plus être modifié.</Note>}
                        <ToggleCard
                            checked={v.actif}
                            onChange={(actif) => setValue('actif', actif, { shouldDirty: true })}
                            label="Proposer cette sanction dans les décisions"
                            description="Décochée, elle n’est plus proposée mais reste lisible dans l’historique."
                        />
                    </>
                )}
                {etape === 2 && (
                    <>
                        <ChampsManquants manquants={manquants} etapes={ETAPES} onAller={aller} />
                        <Recap
                            lignes={[
                                { label: 'Libellé', valeur: <span className="font-medium">{v.libelle}</span> },
                                { label: 'Points retirés', valeur: `−${v.points_retires} pts` },
                                { label: 'Effet', valeur: effets.find((e) => e.value === v.effet)?.label ?? '—' },
                                { label: 'Proposée', valeur: v.actif ? 'Oui' : 'Non' },
                                { label: 'Description', valeur: v.description?.trim() || <span className="text-muted">Aucune</span> },
                            ]}
                        />
                    </>
                )}
            </DialogBody>
            <PiedAssistant
                etape={etape}
                derniere={derniere}
                envoi={isSubmitting}
                desactive={!url || manquants.length > 0}
                onAnnuler={onClose}
                onPrecedent={() => aller(etape - 1)}
                libelle={type ? 'Enregistrer les modifications' : 'Ajouter'}
            />
        </form>
    );
}
