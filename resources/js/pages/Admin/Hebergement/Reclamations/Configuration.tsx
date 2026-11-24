import { Link, router } from '@inertiajs/react';
import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, CalendarClock, Loader2, PenLine, Plus, Save, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { toast } from 'sonner';
import { DataTable, type DataTableColumn } from '@/components/data-table/data-table';
import { RowAction, RowActions } from '@/components/data-table/row-action';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { PageHeader } from '@/components/ui/page-header';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { envoyer } from '@/lib/inertia-form';
import { dateFr, nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import { periodeReclamationSchema, type PeriodeReclamation } from '@/schemas/reclamation';
import type { Paginated } from '@/types/global';

/** `fin` : date de fin de la campagne ; la réclamation commence après. */
type Campagne = { id: string; libelle: string; active: boolean; fin: string | null };

/** Lendemain d'une date `aaaa-mm-jj`. */
const lendemain = (date: string) => {
    const [annee, mois, jour] = date.split('-').map(Number);
    const suivant = new Date(annee, mois - 1, jour + 1);
    return `${suivant.getFullYear()}-${String(suivant.getMonth() + 1).padStart(2, '0')}-${String(suivant.getDate()).padStart(2, '0')}`;
};

type Periode = {
    id: string;
    campagne_id: string;
    campagne: Campagne | null;
    date_debut: string;
    date_fin: string;
    etat: 'ouverte' | 'a_venir' | 'terminee';
    reclamations: number;
    urls: { update: string; destroy: string | null };
};

type Props = {
    periodes: Paginated<Periode>;
    campagnes: Campagne[];
    urls: { index: string; store: string };
};

const ETATS = {
    ouverte: { label: 'Ouverte', variante: 'success' },
    a_venir: { label: 'À venir', variante: 'info' },
    terminee: { label: 'Terminée', variante: 'neutral' },
} as const;

/** Configuration des réclamations : dates d'ouverture et campagne à laquelle chaque période s'applique. */
export default function ReclamationsConfiguration({ periodes, campagnes, urls }: Props) {
    /** Période modifiée, `nouvelle` pour un ajout, null quand la fenêtre est fermée. */
    const [saisie, setSaisie] = useState<Periode | 'nouvelle' | null>(null);
    const [suppression, setSuppression] = useState<Periode | null>(null);
    const [enCours, setEnCours] = useState(false);

    const supprimer = () => {
        if (!suppression?.urls.destroy) return;
        router.delete(suppression.urls.destroy, {
            preserveScroll: true,
            onStart: () => setEnCours(true),
            onError: (erreurs) => Object.values(erreurs).forEach((message) => toast.error(message)),
            onFinish: () => {
                setEnCours(false);
                setSuppression(null);
            },
        });
    };

    const colonnes: DataTableColumn<Periode>[] = [
        {
            key: 'campagne',
            header: 'Campagne',
            cell: (p) => <p className="min-w-44 font-medium text-gray-900">{p.campagne?.libelle ?? 'Campagne supprimée'}</p>,
        },
        { key: 'debut', header: 'Date de début', className: 'whitespace-nowrap', cell: (p) => dateFr(p.date_debut) },
        { key: 'fin', header: 'Date de fin', className: 'whitespace-nowrap', cell: (p) => dateFr(p.date_fin) },
        { key: 'etat', header: 'État', cell: (p) => <Badge variant={ETATS[p.etat].variante}>{ETATS[p.etat].label}</Badge> },
        { key: 'reclamations', header: 'Réclamations', className: 'tabular-nums', cell: (p) => nombre(p.reclamations) },
        {
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            className: 'text-right',
            cell: (p) => (
                <RowActions>
                    <RowAction label="Modifier la période" icon={PenLine} tone="edit" onClick={() => setSaisie(p)} />
                    {p.urls.destroy && <RowAction label="Supprimer la période" icon={Trash2} tone="danger" onClick={() => setSuppression(p)} />}
                </RowActions>
            ),
        },
    ];

    return (
        <AdminLayout title="Configuration des réclamations">
            <PageHeader
                title="Configuration des réclamations"
                description="Pendant une période ouverte, « Faire une réclamation » est proposé sur la page de connexion des étudiants."
                actions={
                    <>
                        <Link href={urls.index} className={cn(buttonVariants({ variant: 'outline', size: 'sm' }), 'h-10 px-4')}>
                            <ArrowLeft {...ICON} aria-hidden /> Retour aux réclamations
                        </Link>
                        <Button size="sm" className="h-10 px-4" onClick={() => setSaisie('nouvelle')} disabled={campagnes.length === 0}>
                            <Plus {...ICON} aria-hidden /> Ajouter une période
                        </Button>
                    </>
                }
            />

            <DataTable
                columns={colonnes}
                data={periodes}
                rowKey={(p) => p.id}
                emptyMessage={
                    <span className="flex flex-col items-center gap-2">
                        <CalendarClock {...ICON} aria-hidden />
                        {campagnes.length === 0 ? 'Créez d’abord une campagne.' : 'Aucune période de réclamation configurée.'}
                    </span>
                }
            />

            <PeriodeDialog saisie={saisie} campagnes={campagnes} urlStore={urls.store} onClose={() => setSaisie(null)} />

            <ConfirmDialog
                open={suppression !== null}
                onOpenChange={(ouvert) => !ouvert && setSuppression(null)}
                title="Supprimer la période ?"
                description={suppression && `La période du ${dateFr(suppression.date_debut)} au ${dateFr(suppression.date_fin)} sera supprimée.`}
                confirmLabel="Supprimer"
                confirmIcon={<Trash2 className="size-4" aria-hidden />}
                danger
                processing={enCours}
                onConfirm={supprimer}
            />
        </AdminLayout>
    );
}

function PeriodeDialog({ saisie, campagnes, urlStore, onClose }: { saisie: Periode | 'nouvelle' | null; campagnes: Campagne[]; urlStore: string; onClose: () => void }) {
    const periode = saisie === 'nouvelle' ? null : saisie;
    const {
        control,
        handleSubmit,
        reset,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<PeriodeReclamation>({ resolver: zodResolver(periodeReclamationSchema), mode: 'onTouched', defaultValues: { campagne_id: '', date_debut: '', date_fin: '' } });
    const [debut, campagneId] = useWatch({ control, name: ['date_debut', 'campagne_id'] });

    useEffect(() => {
        if (!saisie) return;
        reset(
            saisie === 'nouvelle'
                ? { campagne_id: campagnes.find((campagne) => campagne.active)?.id ?? '', date_debut: '', date_fin: '' }
                : { campagne_id: saisie.campagne_id, date_debut: saisie.date_debut, date_fin: saisie.date_fin },
        );
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [saisie, reset]);

    // Une campagne supprimée reste affichée pour la période qui l'utilise.
    const options = periode?.campagne && !campagnes.some((campagne) => campagne.id === periode.campagne_id) ? [...campagnes, periode.campagne] : campagnes;

    const finCampagne = options.find((campagne) => campagne.id === campagneId)?.fin ?? null;
    const debutMin = finCampagne ? lendemain(finCampagne) : undefined;

    const onSubmit = async (valeurs: PeriodeReclamation) => {
        if (finCampagne && valeurs.date_debut <= finCampagne) {
            setError('date_debut', { message: `La réclamation doit commencer après la fin de la campagne (${dateFr(finCampagne)}).` });
            return;
        }
        await envoyer<PeriodeReclamation>(periode ? periode.urls.update : urlStore, valeurs, {
            method: periode ? 'put' : 'post',
            setError,
            champs: ['campagne_id', 'date_debut', 'date_fin'],
            onSuccess: onClose,
        });
    };

    return (
        <Dialog open={saisie !== null} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-lg">
                <form onSubmit={handleSubmit(onSubmit)} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>{periode ? 'Modifier la période' : 'Ajouter une période'}</DialogTitle>
                        <DialogDescription>Le premier et le dernier jour sont compris.</DialogDescription>
                    </DialogHeader>
                    <DialogBody className="space-y-4">
                        <Field
                            id="periode-campagne"
                            label="Campagne concernée"
                            required
                            error={errors.campagne_id?.message}
                            hint={periode && periode.reclamations > 0 ? 'Des réclamations ont été déposées : la campagne ne peut plus être changée.' : undefined}
                        >
                            <Controller
                                control={control}
                                name="campagne_id"
                                render={({ field }) => (
                                    <Select value={field.value} onValueChange={field.onChange} disabled={!!periode && periode.reclamations > 0}>
                                        <SelectTrigger id="periode-campagne" aria-invalid={!!errors.campagne_id || undefined}>
                                            <SelectValue placeholder="Choisir une campagne" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {options.map((campagne) => (
                                                <SelectItem key={campagne.id} value={campagne.id}>
                                                    {campagne.libelle}
                                                    {campagne.active && <span className="text-muted"> · active</span>}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                        </Field>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Field id="periode-debut" label="Date de début" required error={errors.date_debut?.message}>
                                <Controller
                                    control={control}
                                    name="date_debut"
                                    render={({ field }) => <DatePicker id="periode-debut" value={field.value} onChange={field.onChange} min={debutMin} invalid={!!errors.date_debut} />}
                                />
                            </Field>
                            <Field id="periode-fin" label="Date de fin" required error={errors.date_fin?.message}>
                                <Controller
                                    control={control}
                                    name="date_fin"
                                    render={({ field }) => <DatePicker id="periode-fin" value={field.value} onChange={field.onChange} min={debut || debutMin} invalid={!!errors.date_fin} />}
                                />
                            </Field>
                        </div>
                    </DialogBody>
                    <DialogFooter>
                        <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={isSubmitting}>
                            Annuler
                        </Button>
                        <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                            {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Save className="size-4" aria-hidden />}
                            Enregistrer
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
