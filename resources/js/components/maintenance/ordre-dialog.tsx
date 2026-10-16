import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Loader2, Wrench } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { libelleSpecialite, PRIORITES, TYPES_INTERVENTION, type Maintenancier } from '@/components/maintenance/shared';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { ChoixCarte, dateHeure, Encadre, Lignes, repartirErreurs, type Structure } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';

const schema = z
    .object({
        cite_id: z.string(),
        batiment_id: z.string(),
        chambre_id: z.string(),
        titre: z.string().trim().min(3, 'Donnez un titre court (3 caractères au moins).').max(255, '255 caractères au maximum.'),
        categorie: z.string().min(1, 'Choisissez le type d’intervention.'),
        type_intervention: z.enum(['corrective', 'preventive', 'urgente']),
        priorite: z.enum(['faible', 'moyenne', 'elevee', 'critique'], { error: 'Choisissez la priorité.' }),
        description: z.string().trim().min(10, 'Décrivez le problème (10 caractères au moins).').max(5000, '5 000 caractères au maximum.'),
        assigne_a: z.string(),
        date_debut_intervention: z.string(),
        date_fin_intervention: z.string(),
    })
    .superRefine((v, ctx) => {
        if (!v.cite_id) ctx.addIssue({ code: 'custom', path: ['cite_id'], message: 'Choisissez la résidence concernée.' });
        if (v.assigne_a && !v.date_debut_intervention) ctx.addIssue({ code: 'custom', path: ['date_debut_intervention'], message: 'Indiquez le début prévu de l’intervention.' });
        if (v.date_debut_intervention && v.date_fin_intervention && v.date_fin_intervention < v.date_debut_intervention)
            ctx.addIssue({ code: 'custom', path: ['date_fin_intervention'], message: 'La fin prévue doit être postérieure au début.' });
    });

type Valeurs = z.infer<typeof schema>;

const CHAMPS_ETAPES: (keyof Valeurs)[][] = [
    ['cite_id', 'batiment_id', 'chambre_id'],
    ['titre', 'categorie', 'type_intervention', 'priorite', 'description'],
    ['assigne_a', 'date_debut_intervention', 'date_fin_intervention'],
    [],
];
const ETAPES = ['Emplacement', 'Problème', 'Assignation', 'Vérification'];

const initiales = (): Valeurs => ({
    cite_id: '',
    batiment_id: '',
    chambre_id: '',
    titre: '',
    categorie: '',
    type_intervention: 'corrective',
    priorite: 'moyenne',
    description: '',
    assigne_a: '',
    date_debut_intervention: '',
    date_fin_intervention: '',
});

type Props = {
    open: boolean;
    onClose: () => void;
    url: string;
    structure: Structure;
    categories: { value: string; label: string }[];
    maintenanciers: Maintenancier[];
};

/** Nouvel ordre de travail en 4 étapes (action `store_ot` ; sans technicien, l'OT reste « en attente »). */
export function OrdreDialog({ open, onClose, url, structure, categories, maintenanciers }: Props) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const {
        control,
        register,
        handleSubmit,
        reset,
        trigger,
        setValue,
        setError,
        getValues,
        formState: { errors, isSubmitting },
    } = useForm<Valeurs>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: initiales() });
    const [citeId, batimentId, priorite, assigne] = useWatch({ control, name: ['cite_id', 'batiment_id', 'priorite', 'assigne_a'] });

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
    const technicien = maintenanciers.find((m) => m.id === assigne);

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

    const onSubmit = (valeurs: Valeurs) => {
        setErreurServeur(null);
        return envoyer(url, valeurs, {
            onSuccess: onClose,
            onError: (erreurs) => setErreurServeur(repartirErreurs(erreurs, CHAMPS_ETAPES, setError, setEtape)),
        });
    };

    const v = getValues();

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-2xl">
                <form
                    onSubmit={handleSubmit(onSubmit, (fautes) => setEtape(Math.max(0, CHAMPS_ETAPES.findIndex((champs) => champs.some((champ) => fautes[champ])))))}
                    noValidate
                    className="flex min-h-0 flex-col"
                >
                    <DialogHeader>
                        <DialogTitle>Nouvel ordre de travail</DialogTitle>
                        <DialogDescription>
                            Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                        </DialogDescription>
                    </DialogHeader>
                    <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} />
                    <DialogBody className="space-y-4">
                        {erreurServeur && <Encadre ton="danger">{erreurServeur}</Encadre>}

                        {etape === 0 && (
                            <>
                                <Field id="ot-cite_id" label="Résidence" required error={errors.cite_id?.message}>
                                    <Controller
                                        control={control}
                                        name="cite_id"
                                        render={({ field }) => (
                                            <Combobox
                                                id="ot-cite_id"
                                                value={field.value}
                                                onChange={(valeur) => {
                                                    field.onChange(valeur);
                                                    setValue('batiment_id', '');
                                                    setValue('chambre_id', '');
                                                }}
                                                onBlur={field.onBlur}
                                                options={structure.cites.map((c) => ({ value: c.id, label: c.name }))}
                                                placeholder="Sélectionner une résidence"
                                                invalid={!!errors.cite_id}
                                            />
                                        )}
                                    />
                                </Field>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field id="ot-batiment_id" label="Bâtiment" error={errors.batiment_id?.message} hint="Laisser vide si l’intervention concerne toute la cité.">
                                        <Controller
                                            control={control}
                                            name="batiment_id"
                                            render={({ field }) => (
                                                <Combobox
                                                    id="ot-batiment_id"
                                                    value={field.value}
                                                    onChange={(valeur) => {
                                                        field.onChange(valeur);
                                                        setValue('chambre_id', '');
                                                    }}
                                                    options={batiments.map((b) => ({ value: b.id, label: b.name }))}
                                                    placeholder={citeId ? 'Tout le site' : 'Choisissez d’abord la résidence'}
                                                    disabled={!citeId}
                                                    invalid={!!errors.batiment_id}
                                                />
                                            )}
                                        />
                                    </Field>
                                    <Field id="ot-chambre_id" label="Chambre" error={errors.chambre_id?.message} hint="Laisser vide pour les parties communes.">
                                        <Controller
                                            control={control}
                                            name="chambre_id"
                                            render={({ field }) => (
                                                <Combobox
                                                    id="ot-chambre_id"
                                                    value={field.value}
                                                    onChange={field.onChange}
                                                    options={chambres.map((c) => ({ value: c.id, label: `Chambre ${c.numero}` }))}
                                                    placeholder={batimentId ? 'Tout le bâtiment' : 'Choisissez d’abord le bâtiment'}
                                                    disabled={!batimentId}
                                                    invalid={!!errors.chambre_id}
                                                />
                                            )}
                                        />
                                    </Field>
                                </div>
                            </>
                        )}

                        {etape === 1 && (
                            <>
                                <Field id="ot-titre" label="Titre" required error={errors.titre?.message}>
                                    <Input id="ot-titre" placeholder="Ex. Fuite d’eau sous l’évier" invalid={!!errors.titre} {...register('titre')} />
                                </Field>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field id="ot-categorie" label="Type d’intervention" required error={errors.categorie?.message}>
                                        <Controller
                                            control={control}
                                            name="categorie"
                                            render={({ field }) => (
                                                <Combobox id="ot-categorie" value={field.value} onChange={field.onChange} onBlur={field.onBlur} options={categories} placeholder="Sélectionner" invalid={!!errors.categorie} />
                                            )}
                                        />
                                    </Field>
                                    <Field id="ot-type_intervention" label="Nature" required error={errors.type_intervention?.message}>
                                        <Controller
                                            control={control}
                                            name="type_intervention"
                                            render={({ field }) => (
                                                <Combobox id="ot-type_intervention" value={field.value} onChange={field.onChange} options={TYPES_INTERVENTION.map((t) => ({ ...t }))} invalid={!!errors.type_intervention} />
                                            )}
                                        />
                                    </Field>
                                </div>
                                <fieldset>
                                    <legend className="mb-2 text-sm font-medium text-gray-800">
                                        Priorité <span className="text-red-600">*</span>
                                    </legend>
                                    <div role="radiogroup" className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        {PRIORITES.map((option) => (
                                            <ChoixCarte key={option.value} actif={priorite === option.value} onClick={() => setValue('priorite', option.value)} titre={option.label} description={option.description} />
                                        ))}
                                    </div>
                                </fieldset>
                                <Field id="ot-description" label="Description du problème" required error={errors.description?.message}>
                                    <Textarea id="ot-description" rows={4} invalid={!!errors.description} placeholder="Décrivez précisément le problème à résoudre…" {...register('description')} />
                                </Field>
                            </>
                        )}

                        {etape === 2 && (
                            <>
                                <Field id="ot-assigne_a" label="Maintenancier" error={errors.assigne_a?.message} hint="Sans maintenancier, l’ordre reste « en attente » et pourra être assigné depuis sa fiche.">
                                    <Controller
                                        control={control}
                                        name="assigne_a"
                                        render={({ field }) => (
                                            <Combobox
                                                id="ot-assigne_a"
                                                value={field.value}
                                                onChange={field.onChange}
                                                options={[
                                                    { value: '', label: 'Non assigné' },
                                                    ...maintenanciers.map((m) => ({ value: m.id, label: m.name, description: [libelleSpecialite(m.specialite), m.cite].filter(Boolean).join(' · ') })),
                                                ]}
                                                placeholder="Non assigné"
                                                emptyMessage="Aucun maintenancier enregistré."
                                            />
                                        )}
                                    />
                                </Field>
                                {technicien && (
                                    <>
                                        <p className="text-xs text-muted">{[libelleSpecialite(technicien.specialite), technicien.tel, technicien.email].filter(Boolean).join(' · ')}</p>
                                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <Field id="ot-date_debut_intervention" label="Début prévu" required error={errors.date_debut_intervention?.message}>
                                                <Input id="ot-date_debut_intervention" type="datetime-local" invalid={!!errors.date_debut_intervention} {...register('date_debut_intervention')} />
                                            </Field>
                                            <Field id="ot-date_fin_intervention" label="Fin prévue" error={errors.date_fin_intervention?.message}>
                                                <Input id="ot-date_fin_intervention" type="datetime-local" invalid={!!errors.date_fin_intervention} {...register('date_fin_intervention')} />
                                            </Field>
                                        </div>
                                    </>
                                )}
                            </>
                        )}

                        {etape === 3 && (
                            <>
                                <Lignes
                                    lignes={[
                                        [
                                            'Emplacement',
                                            [
                                                structure.cites.find((c) => c.id === v.cite_id)?.name,
                                                batiments.find((b) => b.id === v.batiment_id)?.name ?? 'tout le site',
                                                v.chambre_id ? `Chambre ${chambres.find((c) => c.id === v.chambre_id)?.numero ?? ''}` : null,
                                            ]
                                                .filter(Boolean)
                                                .join(' · '),
                                        ],
                                        ['Titre', v.titre],
                                        ['Type', `${categories.find((c) => c.value === v.categorie)?.label ?? ''} · ${TYPES_INTERVENTION.find((t) => t.value === v.type_intervention)?.label ?? ''}`],
                                        ['Priorité', PRIORITES.find((p) => p.value === v.priorite)?.label ?? ''],
                                        ['Description', <span className="whitespace-pre-line">{v.description}</span>],
                                        ['Maintenancier', technicien ? technicien.name : 'Non assigné'],
                                        ...(technicien ? ([['Intervention prévue', `${dateHeure(v.date_debut_intervention)}${v.date_fin_intervention ? ` → ${dateHeure(v.date_fin_intervention)}` : ''}`]] as [string, string][]) : []),
                                    ]}
                                />
                                <Encadre>{technicien ? 'L’ordre sera créé « en cours » et assigné au maintenancier choisi.' : 'L’ordre sera créé « en attente » d’assignation.'}</Encadre>
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
                                <Button size="sm" className="h-10" onClick={() => allerA(etape + 1)}>
                                    Suivant <ArrowRight className="size-4" aria-hidden />
                                </Button>
                            ) : (
                                <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                                    {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Wrench className="size-4" aria-hidden />}
                                    Créer l’ordre de travail
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
