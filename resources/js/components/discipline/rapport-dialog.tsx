import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Loader2, Save, Send } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { ChoixCarte, dateHeure, Encadre, Lignes, maintenantLocal, repartirErreurs, type Structure } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';

export const GRAVITES = [
    { value: 'faible', label: 'Faible' },
    { value: 'moyenne', label: 'Moyenne' },
    { value: 'grave', label: 'Grave' },
    { value: 'critique', label: 'Critique' },
] as const;

const requis = (message: string) => z.string().trim().min(1, message);

const schema = z
    .object({
        cite_id: requis('Choisissez la cité.'),
        batiment_id: requis('Choisissez le bâtiment.'),
        chambre_id: requis('Choisissez la chambre.'),
        etudiant_id: requis('Choisissez le résident concerné.'),
        date_incident: requis('Indiquez la date et l’heure de l’incident.'),
        gravite: z.enum(['faible', 'moyenne', 'grave', 'critique'], { error: 'Choisissez la gravité.' }),
        nature: requis('Indiquez la nature de l’incident.').max(100, '100 caractères au maximum.'),
        lieu: z.string().max(255, '255 caractères au maximum.'),
        description: requis('Décrivez les faits.').max(5000, '5 000 caractères au maximum.'),
        temoins: z.string().max(2000, '2 000 caractères au maximum.'),
    })
    .refine((v) => !v.date_incident || v.date_incident <= maintenantLocal(), { path: ['date_incident'], message: 'L’incident ne peut pas être dans le futur.' });

type Valeurs = z.infer<typeof schema>;

const CHAMPS_ETAPES: (keyof Valeurs)[][] = [
    ['cite_id', 'batiment_id', 'chambre_id', 'etudiant_id'],
    ['date_incident', 'gravite', 'nature', 'lieu', 'description', 'temoins'],
    [],
];
const ETAPES = ['Résident', 'Incident', 'Vérification'];

const initiales = (): Valeurs => ({
    cite_id: '',
    batiment_id: '',
    chambre_id: '',
    etudiant_id: '',
    date_incident: maintenantLocal(),
    gravite: 'moyenne',
    nature: '',
    lieu: '',
    description: '',
    temoins: '',
});

/** Rédiger un rapport disciplinaire : brouillon ou transmission directe au Service Sécurité (action `incidents.store`). */
export function RapportDialog({ open, onClose, url, structure }: { open: boolean; onClose: () => void; url: string; structure: Structure }) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const [envoi, setEnvoi] = useState<'brouillon' | 'transmettre' | null>(null);
    const {
        control,
        register,
        handleSubmit,
        reset,
        trigger,
        setValue,
        setError,
        getValues,
        formState: { errors },
    } = useForm<Valeurs>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: initiales() });
    const [citeId, batimentId, chambreId, gravite] = useWatch({ control, name: ['cite_id', 'batiment_id', 'chambre_id', 'gravite'] });

    useEffect(() => {
        if (open) {
            reset(initiales());
            setEtape(0);
            setAtteinte(0);
            setErreurServeur(null);
        }
    }, [open, reset]);

    const batiments = useMemo(() => structure.batiments.filter((b) => b.cite_id === citeId), [structure.batiments, citeId]);
    const chambres = useMemo(() => structure.chambres.filter((c) => c.batiment_id === batimentId), [structure.chambres, batimentId]);
    const residents = useMemo(() => (structure.residents ?? []).filter((r) => r.chambre_id === chambreId), [structure.residents, chambreId]);

    const allerA = async (cible: number) => {
        if (cible <= etape) {
            setEtape(cible);
            return;
        }
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPES[index]))) {
                setEtape(index);
                return;
            }
        }
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const soumettre = (action: 'brouillon' | 'transmettre') =>
        handleSubmit(
            async (valeurs) => {
                setErreurServeur(null);
                setEnvoi(action);
                await envoyer(url, { ...valeurs, submit_action: action }, {
                    onSuccess: onClose,
                    onError: (erreurs) => setErreurServeur(repartirErreurs(erreurs, CHAMPS_ETAPES, setError, setEtape)),
                });
                setEnvoi(null);
            },
            (fautes) => setEtape(Math.max(0, CHAMPS_ETAPES.findIndex((champs) => champs.some((champ) => fautes[champ])))),
        )();

    const v = getValues();
    const resident = residents.find((r) => r.id === v.etudiant_id);
    const enCours = envoi !== null;

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && !enCours && onClose()}>
            <DialogContent className="max-w-2xl">
                <form onSubmit={(event) => event.preventDefault()} noValidate className="flex min-h-0 flex-col">
                    <DialogHeader>
                        <DialogTitle>Nouveau rapport disciplinaire</DialogTitle>
                        <DialogDescription>
                            Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                        </DialogDescription>
                    </DialogHeader>
                    <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} />
                    <DialogBody className="space-y-4">
                        {erreurServeur && <Encadre ton="danger">{erreurServeur}</Encadre>}

                        {etape === 0 && (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Field id="ra-cite_id" label="Cité" required error={errors.cite_id?.message} hint="Seules les cités ayant des résidents affectés sont proposées.">
                                    <Controller
                                        control={control}
                                        name="cite_id"
                                        render={({ field }) => (
                                            <Combobox
                                                id="ra-cite_id"
                                                value={field.value}
                                                onChange={(valeur) => {
                                                    field.onChange(valeur);
                                                    setValue('batiment_id', '');
                                                    setValue('chambre_id', '');
                                                    setValue('etudiant_id', '');
                                                }}
                                                onBlur={field.onBlur}
                                                options={structure.cites.map((c) => ({ value: c.id, label: c.name }))}
                                                placeholder="Sélectionner une cité"
                                                invalid={!!errors.cite_id}
                                            />
                                        )}
                                    />
                                </Field>
                                <Field id="ra-batiment_id" label="Bâtiment" required error={errors.batiment_id?.message}>
                                    <Controller
                                        control={control}
                                        name="batiment_id"
                                        render={({ field }) => (
                                            <Combobox
                                                id="ra-batiment_id"
                                                value={field.value}
                                                onChange={(valeur) => {
                                                    field.onChange(valeur);
                                                    setValue('chambre_id', '');
                                                    setValue('etudiant_id', '');
                                                }}
                                                onBlur={field.onBlur}
                                                options={batiments.map((b) => ({ value: b.id, label: b.name }))}
                                                placeholder={citeId ? 'Sélectionner un bâtiment' : 'Choisissez d’abord la cité'}
                                                disabled={!citeId}
                                                invalid={!!errors.batiment_id}
                                            />
                                        )}
                                    />
                                </Field>
                                <Field id="ra-chambre_id" label="Chambre" required error={errors.chambre_id?.message}>
                                    <Controller
                                        control={control}
                                        name="chambre_id"
                                        render={({ field }) => (
                                            <Combobox
                                                id="ra-chambre_id"
                                                value={field.value}
                                                onChange={(valeur) => {
                                                    field.onChange(valeur);
                                                    setValue('etudiant_id', '');
                                                }}
                                                onBlur={field.onBlur}
                                                options={chambres.map((c) => ({ value: c.id, label: `Chambre ${c.numero}` }))}
                                                placeholder={batimentId ? 'Sélectionner une chambre' : 'Choisissez d’abord le bâtiment'}
                                                disabled={!batimentId}
                                                invalid={!!errors.chambre_id}
                                            />
                                        )}
                                    />
                                </Field>
                                <Field id="ra-etudiant_id" label="Résident concerné" required error={errors.etudiant_id?.message}>
                                    <Controller
                                        control={control}
                                        name="etudiant_id"
                                        render={({ field }) => (
                                            <Combobox
                                                id="ra-etudiant_id"
                                                value={field.value}
                                                onChange={field.onChange}
                                                onBlur={field.onBlur}
                                                options={residents.map((r) => ({ value: r.id, label: r.name, description: r.matricule ?? 'Sans matricule' }))}
                                                placeholder={chambreId ? 'Sélectionner un résident' : 'Choisissez d’abord la chambre'}
                                                disabled={!chambreId}
                                                invalid={!!errors.etudiant_id}
                                            />
                                        )}
                                    />
                                </Field>
                            </div>
                        )}

                        {etape === 1 && (
                            <>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field id="ra-date_incident" label="Date et heure" required error={errors.date_incident?.message}>
                                        <Input id="ra-date_incident" type="datetime-local" max={maintenantLocal()} invalid={!!errors.date_incident} {...register('date_incident')} />
                                    </Field>
                                    <Field id="ra-nature" label="Nature de l’incident" required error={errors.nature?.message}>
                                        <Input id="ra-nature" placeholder="Ex. tapage, violence, dégradation" invalid={!!errors.nature} {...register('nature')} />
                                    </Field>
                                </div>
                                <fieldset>
                                    <legend className="mb-2 text-sm font-medium text-gray-800">
                                        Gravité <span className="text-red-600">*</span>
                                    </legend>
                                    <div role="radiogroup" className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                        {GRAVITES.map((option) => (
                                            <ChoixCarte key={option.value} actif={gravite === option.value} onClick={() => setValue('gravite', option.value)} titre={option.label} />
                                        ))}
                                    </div>
                                </fieldset>
                                <Field id="ra-lieu" label="Lieu précis" error={errors.lieu?.message}>
                                    <Input id="ra-lieu" placeholder="Ex. couloir du 2e étage, salle commune" invalid={!!errors.lieu} {...register('lieu')} />
                                </Field>
                                <Field id="ra-description" label="Description factuelle" required error={errors.description?.message}>
                                    <Textarea id="ra-description" rows={5} invalid={!!errors.description} placeholder="Faits constatés, sans appréciation personnelle." {...register('description')} />
                                </Field>
                                <Field id="ra-temoins" label="Témoins" error={errors.temoins?.message}>
                                    <Textarea id="ra-temoins" rows={2} invalid={!!errors.temoins} {...register('temoins')} />
                                </Field>
                            </>
                        )}

                        {etape === 2 && (
                            <>
                                <Lignes
                                    lignes={[
                                        ['Résident', resident ? `${resident.name} · ${resident.matricule ?? 'sans matricule'}` : ''],
                                        [
                                            'Logement',
                                            [structure.cites.find((c) => c.id === v.cite_id)?.name, batiments.find((b) => b.id === v.batiment_id)?.name, `Ch. ${chambres.find((c) => c.id === v.chambre_id)?.numero ?? ''}`]
                                                .filter(Boolean)
                                                .join(' · '),
                                        ],
                                        ['Date', dateHeure(v.date_incident)],
                                        ['Gravité', GRAVITES.find((g) => g.value === v.gravite)?.label ?? ''],
                                        ['Nature', v.nature],
                                        ['Lieu', v.lieu],
                                        ['Description', <span className="whitespace-pre-line">{v.description}</span>],
                                        ['Témoins', v.temoins],
                                    ]}
                                />
                                <Encadre>« Transmettre » place le rapport dans la file du Service Sécurité. Un brouillon reste modifiable et pourra être transmis plus tard.</Encadre>
                            </>
                        )}
                    </DialogBody>
                    <DialogFooter className="sm:justify-between">
                        <Button variant="outline" size="sm" className="h-10" onClick={onClose} disabled={enCours}>
                            Annuler
                        </Button>
                        <div className="flex flex-col-reverse gap-2 sm:flex-row">
                            {etape > 0 && (
                                <Button variant="outline" size="sm" className="h-10" onClick={() => setEtape(etape - 1)} disabled={enCours}>
                                    <ArrowLeft className="size-4" aria-hidden /> Précédent
                                </Button>
                            )}
                            {etape < ETAPES.length - 1 ? (
                                <Button key="suivant" size="sm" className="h-10" onClick={() => allerA(etape + 1)}>
                                    Suivant <ArrowRight className="size-4" aria-hidden />
                                </Button>
                            ) : (
                                <>
                                    <Button variant="secondary" size="sm" className="h-10" onClick={() => soumettre('brouillon')} disabled={enCours}>
                                        {envoi === 'brouillon' ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Save className="size-4" aria-hidden />}
                                        Enregistrer le brouillon
                                    </Button>
                                    <Button size="sm" className="h-10" onClick={() => soumettre('transmettre')} disabled={enCours}>
                                        {envoi === 'transmettre' ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Send className="size-4" aria-hidden />}
                                        Transmettre au Service Sécurité
                                    </Button>
                                </>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
