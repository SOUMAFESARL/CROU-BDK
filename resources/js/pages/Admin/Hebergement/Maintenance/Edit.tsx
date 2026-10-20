import { zodResolver } from '@hookform/resolvers/zod';
import { ClipboardCheck, ClipboardList, MapPin, Wallet } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { AdminLayout } from '@/components/layout/admin-layout';
import { fcfa, PRIORITES, TYPES_INTERVENTION } from '@/components/maintenance/shared';
import { FormAlert, useWizard, Wizard, type WizardStep } from '@/components/parc-immobilier/form/wizard';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { Textarea } from '@/components/ui/textarea';
import { aujourdhui, ChoixCarte, Lignes, repartirErreurs, type Structure } from '@/components/vie-residentielle/shared';
import { envoyer } from '@/lib/inertia-form';
import { symboleDevise } from '@/lib/systeme';
import { dateFr, nombre } from '@/lib/text';

type Valeurs = {
    titre: string;
    description: string;
    categorie: string;
    priorite: string;
    type_intervention: string;
    date_echeance: string;
    cause: string;
    cite_id: string;
    batiment_id: string;
    chambre_id: string;
    equipement_id: string;
    cout_estime: string;
    cout_reel: string;
    temps_passe: string;
};

type Props = {
    ordre: Valeurs & { id: string; numero: string; statut_label: string };
    categories: { value: string; label: string }[];
    structure: Structure;
    equipements: { id: string; name: string }[];
    urls: { update: string | null; show: string | null };
};

const montant = (message: string) => z.string().refine((valeur) => valeur === '' || (!Number.isNaN(Number(valeur)) && Number(valeur) >= 0), message);

const construireSchema = (echeanceInitiale: string) =>
    z.object({
        titre: z.string().trim().min(3, 'Le titre est obligatoire (3 caractères au moins).').max(255, '255 caractères au maximum.'),
        description: z.string().trim().min(10, 'Décrivez le problème (10 caractères au moins).'),
        categorie: z.string().min(1, 'Choisissez le type d’intervention.'),
        priorite: z.string().refine((valeur) => PRIORITES.some((p) => p.value === valeur), 'Choisissez la priorité.'),
        type_intervention: z.string().refine((valeur) => TYPES_INTERVENTION.some((t) => t.value === valeur), 'Choisissez la nature de l’intervention.'),
        // Une échéance déjà enregistrée peut rester telle quelle ; une nouvelle doit être dans le futur (règle serveur `after:today`).
        date_echeance: z.string().refine((valeur) => valeur === '' || valeur === echeanceInitiale || valeur > aujourdhui(), 'L’échéance doit être postérieure à aujourd’hui.'),
        cause: z.string(),
        cite_id: z.string(),
        batiment_id: z.string(),
        chambre_id: z.string(),
        equipement_id: z.string(),
        cout_estime: montant('Montant positif attendu.'),
        cout_reel: montant('Montant positif attendu.'),
        temps_passe: z.string().refine((valeur) => valeur === '' || (Number.isInteger(Number(valeur)) && Number(valeur) >= 0), 'Nombre entier d’heures attendu.'),
    });

const ETAPES: WizardStep[] = [
    { label: 'Informations', description: 'Problème, type et priorité', icon: ClipboardList },
    { label: 'Emplacement', description: 'Résidence, bâtiment, chambre', icon: MapPin },
    { label: 'Coûts et analyse', description: 'Cause, coûts et temps passé', icon: Wallet },
    { label: 'Vérification', description: 'Relire avant d’enregistrer', icon: ClipboardCheck },
];

const CHAMPS_ETAPES: (keyof Valeurs)[][] = [
    ['titre', 'description', 'categorie', 'priorite', 'type_intervention', 'date_echeance'],
    ['cite_id', 'batiment_id', 'chambre_id', 'equipement_id'],
    ['cause', 'cout_estime', 'cout_reel', 'temps_passe'],
    [],
];

/** Modifier un ordre de travail (remplace `vie_residentielle/maintenance/edit.blade.php` ; action `update` inchangée). */
export default function MaintenanceEdit({ ordre, categories, structure, equipements, urls }: Props) {
    const wizard = useWizard(ETAPES.length, { toutesAccessibles: true });
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const schema = useMemo(() => construireSchema(ordre.date_echeance), [ordre.date_echeance]);
    const {
        control,
        register,
        handleSubmit,
        trigger,
        setValue,
        setError,
        getValues,
        formState: { errors, isSubmitting },
    } = useForm<Valeurs>({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues: {
            titre: ordre.titre,
            description: ordre.description,
            categorie: ordre.categorie,
            priorite: ordre.priorite,
            type_intervention: ordre.type_intervention,
            date_echeance: ordre.date_echeance,
            cause: ordre.cause,
            cite_id: ordre.cite_id,
            batiment_id: ordre.batiment_id,
            chambre_id: ordre.chambre_id,
            equipement_id: ordre.equipement_id,
            cout_estime: ordre.cout_estime,
            cout_reel: ordre.cout_reel,
            temps_passe: ordre.temps_passe,
        },
    });
    const [citeId, batimentId, priorite] = useWatch({ control, name: ['cite_id', 'batiment_id', 'priorite'] });
    const batiments = useMemo(() => structure.batiments.filter((b) => b.cite_id === citeId), [structure.batiments, citeId]);
    const chambres = useMemo(() => structure.chambres.filter((c) => c.batiment_id === batimentId), [structure.chambres, batimentId]);

    const allerA = async (cible: number) => {
        if (cible <= wizard.current) {
            wizard.goTo(cible);
            return;
        }
        for (let index = wizard.current; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPES[index]))) {
                wizard.goTo(index);
                return;
            }
        }
        wizard.goTo(cible);
    };

    const enregistrer = handleSubmit(
        (valeurs) => {
            if (!urls.update) return;
            setErreurServeur(null);
            // Échéance inchangée : non envoyée, pour ne pas bloquer un ordre dont l'échéance est passée.
            const { date_echeance, ...reste } = valeurs;
            const donnees = date_echeance === ordre.date_echeance ? reste : valeurs;
            return envoyer(urls.update, donnees, {
                method: 'put',
                onError: (erreurs) => setErreurServeur(repartirErreurs(erreurs, CHAMPS_ETAPES, setError, wizard.goTo)),
            });
        },
        (fautes) => wizard.goTo(Math.max(0, CHAMPS_ETAPES.findIndex((champs) => champs.some((champ) => fautes[champ])))),
    );

    const v = getValues();

    return (
        <AdminLayout title={`Modifier l’OT ${ordre.numero}`}>
            <PageHeader title={`Modifier l’ordre de travail ${ordre.numero}`} description={`Statut actuel : ${ordre.statut_label}. Les ordres terminés ou annulés ne sont plus modifiables.`} />
            <Wizard
                steps={ETAPES}
                current={wizard.current}
                reached={wizard.reached}
                onStepClick={allerA}
                onPrevious={wizard.previous}
                onNext={() => (wizard.isLast ? enregistrer() : allerA(wizard.current + 1))}
                cancelUrl={urls.show ?? '#'}
                submitting={isSubmitting}
                submitLabel="Enregistrer les modifications"
                submitDisabled={!urls.update}
            >
                {erreurServeur && <FormAlert>{erreurServeur}</FormAlert>}

                {wizard.current === 0 && (
                    <>
                        <Field id="ed-titre" label="Titre" required error={errors.titre?.message}>
                            <Input id="ed-titre" invalid={!!errors.titre} {...register('titre')} />
                        </Field>
                        <Field id="ed-description" label="Description" required error={errors.description?.message}>
                            <Textarea id="ed-description" rows={4} invalid={!!errors.description} {...register('description')} />
                        </Field>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <Field id="ed-categorie" label="Type d’intervention" required error={errors.categorie?.message}>
                                <Controller
                                    control={control}
                                    name="categorie"
                                    render={({ field }) => <Combobox id="ed-categorie" value={field.value} onChange={field.onChange} onBlur={field.onBlur} options={categories} invalid={!!errors.categorie} />}
                                />
                            </Field>
                            <Field id="ed-type_intervention" label="Nature" required error={errors.type_intervention?.message}>
                                <Controller
                                    control={control}
                                    name="type_intervention"
                                    render={({ field }) => (
                                        <Combobox id="ed-type_intervention" value={field.value} onChange={field.onChange} onBlur={field.onBlur} options={TYPES_INTERVENTION.map((t) => ({ ...t }))} invalid={!!errors.type_intervention} />
                                    )}
                                />
                            </Field>
                            <Field id="ed-date_echeance" label="Échéance" error={errors.date_echeance?.message}>
                                <Controller
                                    control={control}
                                    name="date_echeance"
                                    render={({ field }) => <DatePicker id="ed-date_echeance" value={field.value} onChange={field.onChange} invalid={!!errors.date_echeance} />}
                                />
                            </Field>
                        </div>
                        <fieldset>
                            <legend className="mb-2 text-sm font-medium text-gray-800">
                                Priorité <span className="text-red-600">*</span>
                            </legend>
                            <div role="radiogroup" className="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                {PRIORITES.map((option) => (
                                    <ChoixCarte key={option.value} actif={priorite === option.value} onClick={() => setValue('priorite', option.value)} titre={option.label} description={option.description} />
                                ))}
                            </div>
                        </fieldset>
                    </>
                )}

                {wizard.current === 1 && (
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <Field id="ed-cite_id" label="Résidence" error={errors.cite_id?.message}>
                            <Controller
                                control={control}
                                name="cite_id"
                                render={({ field }) => (
                                    <Combobox
                                        id="ed-cite_id"
                                        value={field.value}
                                        onChange={(valeur) => {
                                            field.onChange(valeur);
                                            setValue('batiment_id', '');
                                            setValue('chambre_id', '');
                                        }}
                                        options={[{ value: '', label: 'Non précisée' }, ...structure.cites.map((c) => ({ value: c.id, label: c.name }))]}
                                        placeholder="Non précisée"
                                    />
                                )}
                            />
                        </Field>
                        <Field id="ed-batiment_id" label="Bâtiment" error={errors.batiment_id?.message}>
                            <Controller
                                control={control}
                                name="batiment_id"
                                render={({ field }) => (
                                    <Combobox
                                        id="ed-batiment_id"
                                        value={field.value}
                                        onChange={(valeur) => {
                                            field.onChange(valeur);
                                            setValue('chambre_id', '');
                                        }}
                                        options={[{ value: '', label: 'Tout le site' }, ...batiments.map((b) => ({ value: b.id, label: b.name }))]}
                                        placeholder="Tout le site"
                                        disabled={!citeId}
                                    />
                                )}
                            />
                        </Field>
                        <Field id="ed-chambre_id" label="Chambre" error={errors.chambre_id?.message}>
                            <Controller
                                control={control}
                                name="chambre_id"
                                render={({ field }) => (
                                    <Combobox
                                        id="ed-chambre_id"
                                        value={field.value}
                                        onChange={field.onChange}
                                        options={[{ value: '', label: 'Parties communes' }, ...chambres.map((c) => ({ value: c.id, label: `Chambre ${c.numero}` }))]}
                                        placeholder="Parties communes"
                                        disabled={!batimentId}
                                    />
                                )}
                            />
                        </Field>
                        <Field id="ed-equipement_id" label="Équipement concerné" error={errors.equipement_id?.message}>
                            <Controller
                                control={control}
                                name="equipement_id"
                                render={({ field }) => (
                                    <Combobox
                                        id="ed-equipement_id"
                                        value={field.value}
                                        onChange={field.onChange}
                                        options={[{ value: '', label: 'Aucun' }, ...equipements.map((e) => ({ value: e.id, label: e.name }))]}
                                        placeholder="Aucun"
                                    />
                                )}
                            />
                        </Field>
                    </div>
                )}

                {wizard.current === 2 && (
                    <>
                        <Field id="ed-cause" label="Cause identifiée" error={errors.cause?.message}>
                            <Textarea id="ed-cause" rows={3} placeholder="Usure, mauvaise utilisation, défaut d’installation…" {...register('cause')} />
                        </Field>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <Field id="ed-cout_estime" label={`Coût estimé (${symboleDevise()})`} error={errors.cout_estime?.message}>
                                <Input id="ed-cout_estime" type="number" min={0} inputMode="numeric" invalid={!!errors.cout_estime} {...register('cout_estime')} />
                            </Field>
                            <Field id="ed-cout_reel" label={`Coût réel (${symboleDevise()})`} error={errors.cout_reel?.message} hint="Alimente le coût de maintenance des tableaux de bord.">
                                <Input id="ed-cout_reel" type="number" min={0} inputMode="numeric" invalid={!!errors.cout_reel} {...register('cout_reel')} />
                            </Field>
                            <Field id="ed-temps_passe" label="Temps passé (heures)" error={errors.temps_passe?.message}>
                                <Input id="ed-temps_passe" type="number" min={0} step={1} inputMode="numeric" invalid={!!errors.temps_passe} {...register('temps_passe')} />
                            </Field>
                        </div>
                    </>
                )}

                {wizard.current === 3 && (
                    <Lignes
                        lignes={[
                            ['Titre', v.titre],
                            ['Type', `${categories.find((c) => c.value === v.categorie)?.label ?? ''} · ${TYPES_INTERVENTION.find((t) => t.value === v.type_intervention)?.label ?? ''}`],
                            ['Priorité', PRIORITES.find((p) => p.value === v.priorite)?.label ?? ''],
                            ['Échéance', v.date_echeance ? dateFr(v.date_echeance) : ''],
                            ['Description', <span className="whitespace-pre-line">{v.description}</span>],
                            [
                                'Emplacement',
                                [
                                    structure.cites.find((c) => c.id === v.cite_id)?.name,
                                    structure.batiments.find((b) => b.id === v.batiment_id)?.name,
                                    v.chambre_id ? `Chambre ${structure.chambres.find((c) => c.id === v.chambre_id)?.numero ?? ''}` : null,
                                ]
                                    .filter(Boolean)
                                    .join(' · '),
                            ],
                            ['Équipement', equipements.find((e) => e.id === v.equipement_id)?.name ?? ''],
                            ['Cause', v.cause],
                            ['Coût estimé', v.cout_estime !== '' ? fcfa(v.cout_estime) : ''],
                            ['Coût réel', v.cout_reel !== '' ? fcfa(v.cout_reel) : ''],
                            ['Temps passé', v.temps_passe !== '' ? `${nombre(v.temps_passe)} h` : ''],
                        ]}
                    />
                )}
            </Wizard>
        </AdminLayout>
    );
}
