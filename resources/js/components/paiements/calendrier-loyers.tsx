import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, CalendarCheck, CalendarDays, Check, CircleAlert, Loader2, Pencil } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { ICON } from '@/components/layout/app-sidebar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Stepper } from '@/components/ui/stepper';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { envoyer } from '@/lib/inertia-form';
import { dateFr } from '@/lib/text';
import { cn } from '@/lib/utils';

/** Mois du calendrier des loyers (`mois` : aaaa-mm, `echeance` : aaaa-mm-jj). */
export type MoisCalendrier = { mois: string; echeance: string; libelle: string; paye: boolean };

/** Échéance le jour J du mois du loyer, ou du mois qui suit. */
type PositionEcheance = 'mois_loyer' | 'mois_suivant';

/** Calendrier des loyers de la campagne affichée (`App\Services\CalendrierLoyer`) : enregistré ou, sinon, proposé. */
export type CalendrierLoyers = {
    campagne: string;
    configure: boolean;
    nombre_mois: number;
    mois: MoisCalendrier[];
    /** Mois (aaaa-mm) dont un loyer est déjà payé : ils restent dans les 10 mois. */
    mois_payes: string[];
    /** Valeurs du formulaire : premier mois de loyer, jour d'échéance et mois de l'échéance. */
    regle: { premier_mois: string; jour: number; echeance: PositionEcheance };
};

const CLE = /^\d{4}-(0[1-9]|1[0-2])$/;
const NOMS_MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

/** `aaaa-mm` → « octobre 2026 ». */
const libelleMois = (cle: string) => {
    const [annee, mois] = cle.split('-').map(Number);
    return annee && mois ? `${NOMS_MOIS[mois - 1]} ${annee}` : cle;
};

/** Première lettre en majuscule (« septembre 2026 » → « Septembre 2026 »). */
const majuscule = (texte: string) => texte.charAt(0).toUpperCase() + texte.slice(1);

/** `aaaa-mm` décalé de `n` mois. */
const decaler = (cle: string, n: number) => {
    const [annee, mois] = cle.split('-').map(Number);
    const date = new Date(annee, mois - 1 + n, 1);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
};

const joursDuMois = (cle: string) => {
    const [annee, mois] = cle.split('-').map(Number);
    return new Date(annee, mois, 0).getDate();
};

/**
 * Les `nombre` mois consécutifs à partir de `premier`, échéance le `jour` du mois du loyer ou du mois suivant
 * (ramené au dernier jour des mois plus courts) : même calcul que `CalendrierLoyer::construire`.
 */
const construire = (premier: string, jour: number, position: PositionEcheance, nombre: number) =>
    Array.from({ length: nombre }, (_, rang) => {
        const mois = decaler(premier, rang);
        const moisEcheance = position === 'mois_suivant' ? decaler(mois, 1) : mois;
        return { mois, echeance: `${moisEcheance}-${String(Math.min(jour, joursDuMois(moisEcheance))).padStart(2, '0')}` };
    });

type Props = { calendrier: CalendrierLoyers | null; url: string | null };

/** Onglet « Calendrier des loyers » de Paiements : les 10 mois de loyer de la campagne et leur échéance de paiement. */
export function CalendrierLoyersOnglet({ calendrier, url }: Props) {
    const [ouvert, setOuvert] = useState(false);

    if (!calendrier) {
        return (
            <p className="flex items-center gap-2 rounded-brand border bg-surface px-4 py-6 text-sm text-muted">
                <CalendarDays {...ICON} aria-hidden />
                Choisissez une campagne (en haut de la page) pour afficher et configurer son calendrier des loyers.
            </p>
        );
    }

    return (
        <section className="space-y-4">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 className="text-base font-medium text-gray-900">Calendrier des loyers · {calendrier.campagne}</h2>
                    <p className="text-xs text-muted">Les {calendrier.nombre_mois} mois de loyer payables de la campagne et la date d’échéance de chacun.</p>
                </div>
                {url && (
                    <Button size="sm" className="h-10 shrink-0 px-4" onClick={() => setOuvert(true)}>
                        {calendrier.configure ? <Pencil {...ICON} aria-hidden /> : <CalendarCheck {...ICON} aria-hidden />}
                        {calendrier.configure ? 'Modifier le calendrier' : 'Configurer le calendrier'}
                    </Button>
                )}
            </div>

            <TableauMois mois={calendrier.mois} />

            {url && <CalendrierLoyersDialog open={ouvert} onClose={() => setOuvert(false)} calendrier={calendrier} url={url} />}
        </section>
    );
}

/** Tableau des mois de loyer : n°, mois et date d'échéance. */
function TableauMois({ mois }: { mois: { mois: string; echeance: string }[] }) {
    return (
        <div className="overflow-hidden rounded-brand border bg-white">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="w-16">N°</TableHead>
                        <TableHead>Mois de loyer</TableHead>
                        <TableHead>Échéance de paiement</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {mois.map((entree, index) => (
                        <TableRow key={entree.mois}>
                            <TableCell className="text-muted tabular-nums">{index + 1}</TableCell>
                            <TableCell className="font-medium text-gray-900 capitalize">{libelleMois(entree.mois)}</TableCell>
                            <TableCell className="whitespace-nowrap tabular-nums">{dateFr(entree.echeance)}</TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

type Saisie = { premier_mois: string; jour: string; echeance: PositionEcheance };

const ETAPES = ['Premier mois', 'Échéance', 'Vérification'];
const CHAMPS_ETAPE: (keyof Saisie)[][] = [['premier_mois'], ['jour', 'echeance'], []];

/** Formulaire en 3 étapes : premier mois de loyer (les 10 mois se suivent), échéance, puis vérification. */
function CalendrierLoyersDialog({ open, onClose, calendrier, url }: { open: boolean; onClose: () => void; calendrier: CalendrierLoyers; url: string }) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const nombre = calendrier.nombre_mois;
    const payes = calendrier.mois_payes;

    // Mêmes règles que le serveur (App\Services\CalendrierLoyer::enregistrer).
    const schema = useMemo(
        () =>
            z.object({
                premier_mois: z
                    .string()
                    .regex(CLE, 'Choisissez le premier mois de loyer.')
                    .superRefine((premier, ctx) => {
                        const dernier = decaler(premier, nombre - 1);
                        const hors = payes.filter((cle) => cle < premier || cle > dernier);
                        if (hors.length > 0) {
                            ctx.addIssue({ code: 'custom', message: `Des loyers sont déjà payés pour ${hors.map(libelleMois).join(', ')} : ces mois doivent rester dans les ${nombre} mois.` });
                        }
                    }),
                jour: z
                    .string()
                    .min(1, 'Indiquez le jour d’échéance.')
                    .refine((valeur) => /^\d+$/.test(valeur) && Number(valeur) >= 1 && Number(valeur) <= 31, 'Le jour est compris entre 1 et 31.'),
                echeance: z.enum(['mois_loyer', 'mois_suivant'], { error: 'Choisissez le mois de l’échéance.' }),
            }),
        [nombre, payes],
    );

    const initial = useMemo<Saisie>(() => ({ premier_mois: calendrier.regle.premier_mois, jour: String(calendrier.regle.jour), echeance: calendrier.regle.echeance }), [calendrier]);
    const {
        control,
        register,
        handleSubmit,
        reset,
        trigger,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<Saisie>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: initial });
    const valeurs = useWatch({ control }) as Saisie;

    useEffect(() => {
        if (open) {
            reset(initial);
            setEtape(0);
            setAtteinte(0);
            setErreurServeur(null);
        }
    }, [open, reset, initial]);

    // Premiers mois proposés : 12 mois avant le premier mois actuel, 24 après.
    const options = useMemo(() => Array.from({ length: 36 }, (_, i) => decaler(calendrier.regle.premier_mois, i - 12)), [calendrier]);

    const jour = Number(valeurs.jour);
    const jourValide = Number.isInteger(jour) && jour >= 1 && jour <= 31;
    const premierValide = CLE.test(valeurs.premier_mois ?? '');
    const apercu = premierValide && jourValide && valeurs.echeance ? construire(valeurs.premier_mois, jour, valeurs.echeance, nombre) : [];
    const libelleJour = jourValide ? `Le ${jour}` : 'Le jour indiqué';

    const allerA = async (cible: number) => {
        if (cible > etape) {
            for (let index = etape; index < cible; index++) {
                if (!(await trigger(CHAMPS_ETAPE[index], { shouldFocus: true }))) {
                    setEtape(index);
                    return;
                }
            }
        }
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const onSubmit = (saisie: Saisie) => {
        setErreurServeur(null);
        return envoyer<Saisie>(url, { ...saisie, jour: Number(saisie.jour) }, {
            method: 'put',
            setError,
            champs: ['premier_mois', 'jour', 'echeance'],
            onSuccess: onClose,
            onError: (erreurs) => setErreurServeur(Object.values(erreurs)[0] ?? null),
        });
    };

    const ouvrirEtapeFautive = (fautes: Partial<Record<keyof Saisie, unknown>>) => setEtape(Math.max(0, CHAMPS_ETAPE.findIndex((champs) => champs.some((champ) => fautes[champ]))));

    return (
        <Dialog open={open} onOpenChange={(etat) => !etat && !isSubmitting && onClose()}>
            <DialogContent className="max-w-2xl" aria-describedby={undefined}>
                <form onSubmit={handleSubmit(onSubmit, ouvrirEtapeFautive)} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Calendrier des loyers · {calendrier.campagne}</DialogTitle>
                    </DialogHeader>
                    <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={(index) => void allerA(index)} />
                    <DialogBody className="space-y-4">
                        {erreurServeur && (
                            <p role="alert" className="flex items-center gap-2 rounded-brand border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                <CircleAlert className="size-4 shrink-0" aria-hidden />
                                {erreurServeur}
                            </p>
                        )}

                        {etape === 0 && (
                            <Field
                                id="cl-premier"
                                label="Premier mois de loyer"
                                required
                                error={errors.premier_mois?.message}
                                hint={premierValide ? `${majuscule(libelleMois(valeurs.premier_mois))} à ${libelleMois(decaler(valeurs.premier_mois, nombre - 1))}.` : undefined}
                            >
                                <Controller
                                    control={control}
                                    name="premier_mois"
                                    render={({ field }) => (
                                        <Select
                                            value={field.value}
                                            onValueChange={(valeur) => {
                                                field.onChange(valeur);
                                                void trigger('premier_mois');
                                            }}
                                        >
                                            <SelectTrigger id="cl-premier" invalid={!!errors.premier_mois} onBlur={field.onBlur} className="capitalize">
                                                <SelectValue placeholder="Choisir le premier mois" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.map((cle) => (
                                                    <SelectItem key={cle} value={cle} className="capitalize">
                                                        {libelleMois(cle)}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                />
                            </Field>
                        )}

                        {etape === 1 && (
                            <>
                                <Field id="cl-jour" label="Jour d’échéance" required error={errors.jour?.message}>
                                    <Input id="cl-jour" type="number" inputMode="numeric" min={1} max={31} className="sm:w-40" invalid={!!errors.jour} {...register('jour')} />
                                </Field>
                                <fieldset className="space-y-1.5" aria-describedby={errors.echeance ? 'cl-echeance-error' : undefined}>
                                    <legend className="mb-1.5 block text-sm font-medium text-gray-800">
                                        Mois de l’échéance<span className="ml-0.5 text-red-600">*</span>
                                    </legend>
                                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        {(
                                            [
                                                ['mois_loyer', `${libelleJour} du mois du loyer`, premierValide ? `Loyer de ${libelleMois(valeurs.premier_mois)} : échéance en ${libelleMois(valeurs.premier_mois)}` : ''],
                                                ['mois_suivant', `${libelleJour} du mois suivant`, premierValide ? `Loyer de ${libelleMois(valeurs.premier_mois)} : échéance en ${libelleMois(decaler(valeurs.premier_mois, 1))}` : ''],
                                            ] as [PositionEcheance, string, string][]
                                        ).map(([valeur, libelle, exemple]) => {
                                            const coche = valeurs.echeance === valeur;
                                            return (
                                                <label
                                                    key={valeur}
                                                    className={cn(
                                                        'flex cursor-pointer items-start gap-3 rounded-brand border px-4 py-3 text-sm transition-colors',
                                                        coche ? 'border-secondary bg-surface' : 'bg-white hover:bg-gray-50',
                                                    )}
                                                >
                                                    <input type="radio" value={valeur} className="mt-0.5 size-4 accent-primary" {...register('echeance')} />
                                                    <span className="min-w-0">
                                                        <span className="block font-medium text-gray-900">{libelle}</span>
                                                        {exemple && <span className="block text-xs text-muted">{exemple}</span>}
                                                    </span>
                                                </label>
                                            );
                                        })}
                                    </div>
                                    {errors.echeance && (
                                        <p id="cl-echeance-error" role="alert" className="text-xs font-medium text-red-600">
                                            {errors.echeance.message}
                                        </p>
                                    )}
                                </fieldset>
                            </>
                        )}

                        {etape === 2 && <TableauMois mois={apercu} />}
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
                                <Button key="suivant" size="sm" className="h-10" onClick={() => void allerA(etape + 1)}>
                                    Suivant <ArrowRight className="size-4" aria-hidden />
                                </Button>
                            ) : (
                                <Button key="envoyer" type="submit" size="sm" className="h-10" disabled={isSubmitting || apercu.length === 0}>
                                    {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />}
                                    Enregistrer le calendrier
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
