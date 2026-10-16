import { zodResolver } from '@hookform/resolvers/zod';
import { ChevronLeft, ChevronRight, Loader2, Send, TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { toast } from 'sonner';
import { Notice } from '@/components/etudiant/panneau';
import { ICON } from '@/components/layout/app-sidebar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DatePicker } from '@/components/ui/date-picker';
import { Field } from '@/components/ui/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { dateFr } from '@/lib/text';
import { cn } from '@/lib/utils';
import { CHAMPS_ETAPE_SORTIE, MAX_OBSERVATIONS, sortieSchema, type Sortie } from '@/schemas/demandes';

export type FormulaireSortie = {
    url: string;
    nom: string | null;
    matricule: string | null;
    chambre: string | null;
    residence: string | null;
    dateMin: string;
    dateDefaut: string;
    motifs: { code: string; libelle: string }[];
};

const ETAPES = ['Date et motif', 'Précisions', 'Conditions'];

const CONDITIONS = [
    'La sortie de chambre est effective après validation définitive par l’administration.',
    'Vous devez restituer les clés et l’ensemble des équipements fournis.',
    'Un état des lieux de sortie sera réalisé en votre présence.',
    'Toute dégradation constatée sera facturée.',
];

/** Assistant en 3 étapes de demande de sortie de chambre (route etudiants.departure-requests.store). */
export function DemandeSortie({ formulaire }: { formulaire: FormulaireSortie }) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const {
        register,
        control,
        handleSubmit,
        trigger,
        setError,
        reset,
        formState: { errors, isSubmitting },
    } = useForm<Sortie>({
        resolver: zodResolver(sortieSchema(formulaire.dateMin, formulaire.motifs.map((m) => m.code))),
        defaultValues: { date_sortie_prevue: formulaire.dateDefaut, motif: '', observations: '', conditions_acceptees: false },
        mode: 'onTouched',
    });
    const valeurs = useWatch({ control });

    const allerA = async (cible: number) => {
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPE_SORTIE[index]))) {
                setEtape(index);
                return;
            }
        }
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const soumettre = handleSubmit(async (donnees) => {
        await envoyer(
            formulaire.url,
            { ...donnees, conditions_acceptees: donnees.conditions_acceptees ? 1 : 0 },
            {
                onError: (erreurs) => {
                    // Erreurs serveur sous le champ concerné, puis retour à l'étape du premier champ fautif.
                    let fautive = -1;
                    Object.entries(erreurs).forEach(([cle, message]) => {
                        const index = CHAMPS_ETAPE_SORTIE.findIndex((champs) => (champs as string[]).includes(cle));
                        if (index < 0) {
                            toast.error(message);
                            return;
                        }
                        setError(cle as keyof Sortie, { type: 'server', message });
                        if (fautive < 0 || index < fautive) fautive = index;
                    });
                    if (fautive >= 0) setEtape(fautive);
                },
                onSuccess: () => reset(),
            },
        );
    });

    const motif = formulaire.motifs.find((m) => m.code === valeurs.motif)?.libelle;
    const longueur = (valeurs.observations ?? '').length;

    return (
        <form onSubmit={soumettre} noValidate className="rounded-brand border">
            <div className="px-4 pt-4">
                <h3 className="text-sm font-medium text-gray-900">Nouvelle demande de sortie</h3>
                <p className="mt-0.5 text-xs text-muted">
                    Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                </p>
            </div>
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} className="mt-3 px-4" />

            <div className="flex flex-col gap-4 p-4">
                {etape === 0 && (
                    <>
                        <dl className="grid gap-x-6 gap-y-2 rounded-brand bg-surface p-3 text-sm sm:grid-cols-2">
                            <Info libelle="Nom complet" valeur={formulaire.nom} />
                            <Info libelle="Numéro étudiant" valeur={formulaire.matricule} />
                            <Info libelle="Chambre actuelle" valeur={formulaire.chambre ? `Chambre ${formulaire.chambre}` : null} />
                            <Info libelle="Résidence" valeur={formulaire.residence} />
                        </dl>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field
                                id="sortie-date"
                                label="Date de sortie souhaitée"
                                required
                                error={errors.date_sortie_prevue?.message}
                                hint={`Au plus tôt le ${dateFr(formulaire.dateMin)} (préavis de 15 jours).`}
                            >
                                <Controller
                                    control={control}
                                    name="date_sortie_prevue"
                                    render={({ field }) => (
                                        <DatePicker id="sortie-date" value={field.value} onChange={field.onChange} min={formulaire.dateMin} invalid={!!errors.date_sortie_prevue} />
                                    )}
                                />
                            </Field>
                            <Field id="sortie-motif" label="Motif de la sortie" required error={errors.motif?.message}>
                                <Controller
                                    control={control}
                                    name="motif"
                                    render={({ field }) => (
                                        <Select value={field.value} onValueChange={field.onChange}>
                                            <SelectTrigger id="sortie-motif" invalid={!!errors.motif} onBlur={field.onBlur}>
                                                <SelectValue placeholder="Choisir un motif" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {formulaire.motifs.map((item) => (
                                                    <SelectItem key={item.code} value={item.code}>
                                                        {item.libelle}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                />
                            </Field>
                        </div>
                    </>
                )}

                {etape === 1 && (
                    <Field
                        id="sortie-observations"
                        label="Informations complémentaires"
                        error={errors.observations?.message}
                        hint={
                            <span className={cn(longueur > MAX_OBSERVATIONS - 100 && 'text-red-700')}>
                                Facultatif · {longueur} / {MAX_OBSERVATIONS}
                            </span>
                        }
                    >
                        <Textarea
                            id="sortie-observations"
                            rows={4}
                            maxLength={MAX_OBSERVATIONS}
                            placeholder="Ajoutez toute information utile (nouvelle adresse, disponibilités pour l’état des lieux…)."
                            invalid={!!errors.observations}
                            {...register('observations')}
                        />
                    </Field>
                )}

                {etape === 2 && (
                    <>
                        <dl className="grid gap-x-6 gap-y-2 rounded-brand bg-surface p-3 text-sm sm:grid-cols-2">
                            <Info libelle="Date de sortie" valeur={valeurs.date_sortie_prevue ? dateFr(valeurs.date_sortie_prevue) : null} />
                            <Info libelle="Motif" valeur={motif} />
                        </dl>
                        <Notice icone={TriangleAlert} className="bg-amber-50 text-amber-800">
                            <p className="font-medium">Conditions de sortie</p>
                            <ul className="mt-1 list-disc space-y-0.5 pl-4">
                                {CONDITIONS.map((condition) => (
                                    <li key={condition}>{condition}</li>
                                ))}
                            </ul>
                        </Notice>
                        <div>
                            <label
                                htmlFor="sortie-conditions"
                                className={cn(
                                    'flex cursor-pointer items-start gap-3 rounded-brand border p-3 text-sm text-gray-900',
                                    errors.conditions_acceptees ? 'border-red-300 bg-red-50/40' : 'border-gray-300 hover:bg-surface',
                                )}
                            >
                                <Controller
                                    control={control}
                                    name="conditions_acceptees"
                                    render={({ field }) => (
                                        <Checkbox
                                            id="sortie-conditions"
                                            checked={field.value}
                                            onCheckedChange={(v) => field.onChange(v === true)}
                                            aria-invalid={!!errors.conditions_acceptees}
                                            className="mt-0.5"
                                        />
                                    )}
                                />
                                <span>
                                    J’accepte les conditions de sortie et je confirme avoir pris connaissance du règlement intérieur.
                                    <span className="ml-0.5 text-red-600">*</span>
                                </span>
                            </label>
                            {errors.conditions_acceptees && (
                                <p role="alert" className="mt-1.5 text-xs font-medium text-red-600">
                                    {errors.conditions_acceptees.message}
                                </p>
                            )}
                        </div>
                    </>
                )}
            </div>

            <div className="flex flex-col-reverse gap-2 border-t p-4 sm:flex-row sm:justify-between">
                {etape > 0 ? (
                    <Button variant="outline" onClick={() => setEtape(etape - 1)} disabled={isSubmitting} className="font-medium">
                        <ChevronLeft {...ICON} />
                        Précédent
                    </Button>
                ) : (
                    <span />
                )}
                {etape < ETAPES.length - 1 ? (
                    <Button onClick={() => allerA(etape + 1)} className="font-medium">
                        Suivant
                        <ChevronRight {...ICON} />
                    </Button>
                ) : (
                    <Button type="submit" disabled={isSubmitting} className="font-medium">
                        {isSubmitting ? <Loader2 {...ICON} className="animate-spin" /> : <Send {...ICON} />}
                        Soumettre la demande de sortie
                    </Button>
                )}
            </div>
        </form>
    );
}

function Info({ libelle, valeur }: { libelle: string; valeur: string | null | undefined }) {
    return (
        <div className="min-w-0">
            <dt className="text-xs text-muted">{libelle}</dt>
            <dd className={cn('truncate', valeur ? 'text-gray-900' : 'text-gray-400')}>{valeur || 'Non renseigné'}</dd>
        </div>
    );
}
