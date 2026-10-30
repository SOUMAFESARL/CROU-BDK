import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Loader2, UserPlus } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { libelleSpecialite } from '@/components/maintenance/shared';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { Encadre, Lignes, repartirErreurs, type CiteOption } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';
import { avecFormatTelephone, PROPS_TELEPHONE, telephoneObligatoire } from '@/lib/telephone';

const schema = z.object({
    name: z.string().trim().min(2, 'Le nom est obligatoire.').max(255, '255 caractères au maximum.'),
    tel: telephoneObligatoire('Le téléphone est obligatoire.'),
    email: z.union([z.literal(''), z.email('Adresse e-mail invalide.')]),
    adresse: z.string().max(255, '255 caractères au maximum.'),
    specialite: z.string().min(1, 'Choisissez la spécialité.'),
    cite_id: z.string(),
    notes: z.string().max(2000, '2 000 caractères au maximum.'),
});

type Valeurs = z.infer<typeof schema>;

const CHAMPS_ETAPES: (keyof Valeurs)[][] = [['name', 'tel', 'email', 'adresse'], ['specialite', 'cite_id', 'notes'], []];
const ETAPES = ['Identité', 'Spécialité', 'Vérification'];
const VIDES: Valeurs = { name: '', tel: '', email: '', adresse: '', specialite: '', cite_id: '', notes: '' };

/** Enregistrer un maintenancier (action `store_maintenancier`). */
export function MaintenancierDialog({ open, onClose, url, specialites, cites }: { open: boolean; onClose: () => void; url: string; specialites: string[]; cites: CiteOption[] }) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const {
        control,
        register,
        handleSubmit,
        reset,
        trigger,
        setError,
        getValues,
        formState: { errors, isSubmitting },
    } = useForm<Valeurs>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: VIDES });

    useEffect(() => {
        if (open) {
            reset(VIDES);
            setEtape(0);
            setAtteinte(0);
            setErreurServeur(null);
        }
    }, [open, reset]);

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
            <DialogContent className="max-w-xl">
                <form
                    onSubmit={handleSubmit(onSubmit, (fautes) => setEtape(Math.max(0, CHAMPS_ETAPES.findIndex((champs) => champs.some((champ) => fautes[champ])))))}
                    noValidate
                    className="flex min-h-0 flex-col"
                >
                    <DialogHeader>
                        <DialogTitle>Nouveau maintenancier</DialogTitle>
                        <DialogDescription>
                            Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                        </DialogDescription>
                    </DialogHeader>
                    <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} />
                    <DialogBody className="space-y-4">
                        {erreurServeur && <Encadre ton="danger">{erreurServeur}</Encadre>}

                        {etape === 0 && (
                            <>
                                <Field id="mt-name" label="Nom et prénoms" required error={errors.name?.message}>
                                    <Input id="mt-name" placeholder="Ex. KOUADIO Jean" invalid={!!errors.name} {...register('name')} />
                                </Field>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field id="mt-tel" label="Téléphone" required error={errors.tel?.message}>
                                        <Input id="mt-tel" {...PROPS_TELEPHONE} invalid={!!errors.tel} {...avecFormatTelephone(register('tel'))} />
                                    </Field>
                                    <Field id="mt-email" label="E-mail" error={errors.email?.message}>
                                        <Input id="mt-email" type="email" placeholder="nom@exemple.ci" invalid={!!errors.email} {...register('email')} />
                                    </Field>
                                </div>
                                <Field id="mt-adresse" label="Adresse" error={errors.adresse?.message}>
                                    <Input id="mt-adresse" placeholder="Ex. Bondoukou, quartier Résidentiel" invalid={!!errors.adresse} {...register('adresse')} />
                                </Field>
                            </>
                        )}

                        {etape === 1 && (
                            <>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field id="mt-specialite" label="Spécialité" required error={errors.specialite?.message}>
                                        <Controller
                                            control={control}
                                            name="specialite"
                                            render={({ field }) => (
                                                <Combobox
                                                    id="mt-specialite"
                                                    value={field.value}
                                                    onChange={field.onChange}
                                                    onBlur={field.onBlur}
                                                    options={specialites.map((s) => ({ value: s, label: libelleSpecialite(s) }))}
                                                    placeholder="Sélectionner"
                                                    invalid={!!errors.specialite}
                                                />
                                            )}
                                        />
                                    </Field>
                                    <Field id="mt-cite_id" label="Résidence de rattachement" error={errors.cite_id?.message}>
                                        <Controller
                                            control={control}
                                            name="cite_id"
                                            render={({ field }) => (
                                                <Combobox
                                                    id="mt-cite_id"
                                                    value={field.value}
                                                    onChange={field.onChange}
                                                    options={[{ value: '', label: 'Toutes les résidences' }, ...cites.map((c) => ({ value: c.id, label: c.name }))]}
                                                    placeholder="Toutes les résidences"
                                                />
                                            )}
                                        />
                                    </Field>
                                </div>
                                <Field id="mt-notes" label="Notes" error={errors.notes?.message}>
                                    <Textarea id="mt-notes" rows={3} placeholder="Disponibilités, entreprise, habilitations…" invalid={!!errors.notes} {...register('notes')} />
                                </Field>
                            </>
                        )}

                        {etape === 2 && (
                            <Lignes
                                lignes={[
                                    ['Nom', v.name],
                                    ['Téléphone', v.tel],
                                    ['E-mail', v.email],
                                    ['Adresse', v.adresse],
                                    ['Spécialité', v.specialite ? libelleSpecialite(v.specialite) : ''],
                                    ['Résidence', cites.find((c) => c.id === v.cite_id)?.name ?? 'Toutes les résidences'],
                                    ['Notes', v.notes],
                                ]}
                            />
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
                                <Button key="creer" type="submit" size="sm" className="h-10" disabled={isSubmitting}>
                                    {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <UserPlus className="size-4" aria-hidden />}
                                    Enregistrer
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
