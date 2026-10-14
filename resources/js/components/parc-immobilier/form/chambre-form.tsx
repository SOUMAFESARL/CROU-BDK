import { zodResolver } from '@hookform/resolvers/zod';
import {
    BedDouble,
    Building,
    CheckCircle2,
    ClipboardCheck,
    DoorClosed,
    DoorOpen,
    Hash,
    Layers,
    ListPlus,
    Loader2,
    Pencil,
    Plus,
    Trash2,
    Wand2,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Controller, useForm, useWatch, type Control, type FieldPath, type FieldErrors } from 'react-hook-form';
import { toast } from 'sonner';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { ChambreDialog, type ChambreModifiable } from '../chambre-dialog';
import { messageErreur, parcHttp } from '../http';
import { nombre } from '../labels';
import { ChoiceCard, Counter, RecapSection } from './inputs';
import { entierObligatoire, estMixte, type BatimentOption, type CiteOption, type FormUrls, type Option, type TypeChambreOption } from './types';
import { FormAlert, SectionTitle, useWizard, Wizard, type WizardStep } from './wizard';

/* ───────────── Réponses des actions existantes (getChambresByBatiment / getChambresByNiveau) ───────────── */

type NiveauBatiment = {
    niveau_aile_code: string;
    aile_name: string;
    aile_numero: number | null;
    niveau_numero: number;
    label: string;
    total_chambres: number;
    chambres_crees: number;
    chambres_non_crees: number;
};

type ChambreCreee = {
    code: string;
    numero: string;
    type: string;
    type_code: string;
    niveau_confort: string | null;
    pmr: boolean | number | null;
    nb_lits: number | null;
    etat: string | null;
};

type DetailNiveau = {
    crees: ChambreCreee[];
    nonCrees: { code: string }[];
};

const STEPS: WizardStep[] = [
    { label: 'Emplacement', description: 'Cité et bâtiment', icon: Building },
    { label: 'Niveau', description: 'Étage où configurer', icon: Layers },
    { label: 'Chambres', description: 'Numéros, types, lits', icon: BedDouble },
    { label: 'Vérification', description: 'Relire avant d’enregistrer', icon: ClipboardCheck },
];

const CONFORTS = [
    { value: 'standard', label: 'Standard' },
    { value: 'eleve', label: 'Élevé' },
] as const;

/** Règles d'une configuration : nombre de lits limité par la capacité du type (et à 3, comme `massCreateChambres`). */
function buildSchema(typesChambre: TypeChambreOption[]) {
    const capacite = (id: string) => Math.min(3, typesChambre.find((t) => t.id === id)?.capacite ?? 3);
    const communs = {
        type_chambre_code: z.string().min(1, 'Choisissez le type de chambre.'),
        typeOccupation: z.string().min(1, "Choisissez le type d'occupation."),
        confort: z.enum(['standard', 'eleve']),
        pmr: z.enum(['0', '1']),
        nb_lits: entierObligatoire('Le nombre de lits', 1, 3),
    };
    const lits = (valeur: { type_chambre_code: string; nb_lits: string }, ctx: z.RefinementCtx) => {
        const max = capacite(valeur.type_chambre_code);
        if (valeur.type_chambre_code && Number(valeur.nb_lits) > max) {
            ctx.addIssue({ code: 'custom', path: ['nb_lits'], message: `Ce type de chambre accepte ${max} lit(s) au maximum.` });
        }
    };

    return z.object({
        cite: z.string().min(1, 'Choisissez la cité.'),
        batiment: z.string().min(1, 'Choisissez le bâtiment.'),
        niveau: z.string().min(1, 'Choisissez le niveau à configurer.'),
        mode: z.enum(['lot', 'unitaire']),
        lignes: z.array(
            z
                .object({
                    ...communs,
                    prefix: z
                        .string()
                        .trim()
                        .max(20, '20 caractères au maximum.')
                        .refine((v) => /^[A-Za-z0-9_-]*$/.test(v), 'Lettres, chiffres, tiret ou souligné uniquement.'),
                    quantity: entierObligatoire('La quantité', 1, 500),
                    equipements: z.array(z.object({ name: z.string(), quantity: entierObligatoire('La quantité', 0, 20) })),
                })
                .superRefine(lits),
        ),
        unitaire: z
            .object({
                ...communs,
                numero_chambre: z.string().trim().min(1, 'Le numéro de porte est obligatoire.').max(250, '250 caractères au maximum.'),
            })
            .superRefine(lits),
    });
}

type FormInput = z.input<ReturnType<typeof buildSchema>>;
type Ligne = FormInput['lignes'][number];

type ChambreFormProps = {
    cites: CiteOption[];
    batiments: BatimentOption[];
    typesCite: Option[];
    typesChambre: TypeChambreOption[];
    selection: { cite: string | null; batiment: string | null };
    urls: FormUrls;
};

export function ChambreForm({ cites, batiments, typesCite, typesChambre, selection, urls }: ChambreFormProps) {
    const wizard = useWizard(STEPS.length);
    const schema = useMemo(() => buildSchema(typesChambre), [typesChambre]);
    const batimentInitial = batiments.find((b) => b.id === selection.batiment) ?? null;
    const citeInitiale = batimentInitial?.cite_id ?? (cites.some((c) => c.id === selection.cite) ? selection.cite! : '');

    const {
        control,
        register,
        trigger,
        watch,
        setValue,
        getValues,
        setError,
        clearErrors,
        formState: { errors },
    } = useForm<FormInput>({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues: {
            cite: citeInitiale,
            batiment: batimentInitial?.id ?? '',
            niveau: '',
            mode: 'lot',
            lignes: [],
            unitaire: { numero_chambre: '', type_chambre_code: '', typeOccupation: '', confort: 'standard', pmr: '0', nb_lits: '1' },
        },
    });

    const values = watch();
    const batiment = batiments.find((b) => b.id === values.batiment) ?? null;
    const cite = cites.find((c) => c.id === values.cite) ?? null;
    const batimentsCite = batiments.filter((b) => b.cite_id === values.cite);

    const [niveaux, setNiveaux] = useState<NiveauBatiment[] | null>(null);
    const [chargementNiveaux, setChargementNiveaux] = useState(false);
    const [detail, setDetail] = useState<DetailNiveau | null>(null);
    const [chargementDetail, setChargementDetail] = useState(false);
    const [erreurEtape, setErreurEtape] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [succes, setSucces] = useState<string | null>(null);
    const [aModifier, setAModifier] = useState<ChambreModifiable | null>(null);

    /* ───────────── Types d'occupation : tous si le bâtiment est mixte, sinon celui du bâtiment ───────────── */
    const typesOccupation = useMemo(() => {
        const nom = typesCite.find((t) => t.id === batiment?.type_occupation_id)?.name;
        return !batiment?.type_occupation_id || estMixte(nom) ? typesCite : typesCite.filter((t) => t.id === batiment.type_occupation_id);
    }, [batiment, typesCite]);
    const occupationDefaut = typesOccupation.length === 1 ? typesOccupation[0].id : (batiment?.type_occupation_id ?? '');

    const nouvelleLigne = useCallback(
        (): Ligne => ({
            prefix: '',
            type_chambre_code: '',
            quantity: '1',
            typeOccupation: occupationDefaut,
            confort: 'standard',
            pmr: '0',
            nb_lits: '1',
            equipements: [],
        }),
        [occupationDefaut],
    );

    /* ───────────── Chargements ───────────── */
    const chargerNiveaux = useCallback(
        async (batimentId: string) => {
            if (!urls.chambresBatiment) return;
            setChargementNiveaux(true);
            try {
                const { data } = await parcHttp.get(urls.chambresBatiment, { params: { batiment_code: batimentId } });
                setNiveaux(data.etages ?? []);
            } catch (error) {
                setNiveaux([]);
                toast.error(messageErreur(error, 'Impossible de charger les niveaux du bâtiment.'));
            } finally {
                setChargementNiveaux(false);
            }
        },
        [urls.chambresBatiment],
    );

    const chargerDetail = useCallback(
        async (niveauCode: string, batimentId: string) => {
            if (!urls.chambresNiveau) return;
            setChargementDetail(true);
            try {
                const { data } = await parcHttp.get(urls.chambresNiveau, { params: { niveau_code: niveauCode, batiment_code: batimentId } });
                setDetail({ crees: data.chambres_crees?.items ?? [], nonCrees: data.chambres_non_crees?.items ?? [] });
            } catch (error) {
                setDetail(null);
                toast.error(messageErreur(error, 'Impossible de charger les chambres du niveau.'));
            } finally {
                setChargementDetail(false);
            }
        },
        [urls.chambresNiveau],
    );

    useEffect(() => {
        setNiveaux(null);
        setDetail(null);
        if (values.batiment) void chargerNiveaux(values.batiment);
    }, [values.batiment, chargerNiveaux]);

    useEffect(() => {
        setDetail(null);
        if (values.niveau && values.batiment) void chargerDetail(values.niveau, values.batiment);
    }, [values.niveau, values.batiment, chargerDetail]);

    const niveau = niveaux?.find((n) => n.niveau_aile_code === values.niveau) ?? null;
    const restant = detail?.nonCrees.length ?? niveau?.chambres_non_crees ?? 0;
    const totalLot = values.lignes.reduce((s, l) => s + (Number.parseInt(l.quantity, 10) || 0), 0);
    const nomType = (id: string) => typesChambre.find((t) => t.id === id)?.name ?? '';
    const nomOccupation = (id: string) => typesCite.find((t) => t.id === id)?.name ?? '';

    /** Niveaux groupés par aile (bâtiment à ailes) ; une seule aile = liste simple. */
    const groupes = useMemo(() => {
        const map = new Map<string, NiveauBatiment[]>();
        (niveaux ?? []).forEach((n) => {
            const cle = n.aile_name || 'Aile principale';
            map.set(cle, [...(map.get(cle) ?? []), n]);
        });
        return [...map.entries()];
    }, [niveaux]);

    /* ───────────── Validation par étape ───────────── */
    const validerEtape = async (index: number): Promise<boolean> => {
        setErreurEtape(null);
        if (index === 0) return trigger(['cite', 'batiment'], { shouldFocus: true });
        if (index === 1) {
            if (!(await trigger('niveau'))) return false;
            if (chargementDetail || !detail) {
                setErreurEtape('Patientez pendant le chargement des chambres du niveau.');
                return false;
            }
            if (detail.nonCrees.length === 0) {
                setErreurEtape('Toutes les chambres de ce niveau sont déjà configurées. Choisissez un autre niveau ou modifiez une chambre existante.');
                return false;
            }
            return true;
        }
        if (index === 2) {
            if (values.mode === 'lot') {
                if (values.lignes.length === 0) {
                    setErreurEtape('Ajoutez au moins une configuration de chambres.');
                    return false;
                }
                if (!(await trigger('lignes', { shouldFocus: true }))) return false;
                if (totalLot > restant) {
                    setErreurEtape(`Vous demandez ${totalLot} chambre(s) alors qu'il en reste ${restant} à configurer sur ce niveau.`);
                    return false;
                }
                return true;
            }
            if (!(await trigger('unitaire', { shouldFocus: true }))) return false;
            const numero = getValues('unitaire.numero_chambre').trim().toLowerCase();
            if (detail?.crees.some((c) => (c.numero ?? '').trim().toLowerCase() === numero)) {
                setError('unitaire.numero_chambre', { message: 'Ce numéro de porte existe déjà sur ce niveau.' });
                return false;
            }
            return true;
        }
        return true;
    };

    const allerA = async (cible: number) => {
        for (let index = wizard.current; index < cible; index++) {
            if (!(await validerEtape(index))) {
                wizard.goTo(index);
                return;
            }
        }
        setErreurEtape(null);
        wizard.goTo(cible);
    };

    /* ───────────── Envoi : création en lot (massCreateChambres) ou unitaire (quickCreateChambre) ───────────── */
    const envoyer = async () => {
        const v = getValues();
        setSubmitting(true);
        setErreurEtape(null);
        try {
            let message: string;
            if (v.mode === 'lot') {
                if (!urls.massCreate) return;
                const { data } = await parcHttp.put(urls.massCreate, {
                    niveau_code: v.niveau,
                    batiment_code: v.batiment,
                    configurations: v.lignes.map((ligne) => ({
                        prefix: ligne.prefix.trim(),
                        type_chambre_code: ligne.type_chambre_code,
                        quantity: Number(ligne.quantity),
                        confort: ligne.confort,
                        typeOccupation: ligne.typeOccupation,
                        pmr: ligne.pmr,
                        nb_lits: Number(ligne.nb_lits),
                        equipements: ligne.equipements.map((e) => ({ name: e.name, quantity: Number(e.quantity) })),
                    })),
                });
                message = data.message ?? 'Chambres créées.';
            } else {
                if (!urls.quickCreate || !detail?.nonCrees[0]) return;
                const { data } = await parcHttp.post(urls.quickCreate, {
                    code: detail.nonCrees[0].code,
                    numero_chambre: v.unitaire.numero_chambre.trim(),
                    type_chambre_code: v.unitaire.type_chambre_code,
                    niveau_confort: v.unitaire.confort,
                    typeOccupation: v.unitaire.typeOccupation,
                    pmr: v.unitaire.pmr,
                    nb_lits: Number(v.unitaire.nb_lits),
                });
                message = data.message ?? 'Chambre créée.';
            }
            toast.success(message);
            setSucces(message);
            setValue('lignes', []);
            setValue('unitaire.numero_chambre', '');
            await Promise.all([chargerNiveaux(v.batiment), chargerDetail(v.niveau, v.batiment)]);
            // Retour au choix du niveau : la progression est visible et un autre niveau peut être configuré.
            wizard.goTo(1);
        } catch (error) {
            setErreurEtape(messageErreur(error, 'La configuration des chambres a échoué.'));
        } finally {
            setSubmitting(false);
        }
    };

    const suivant = async () => {
        setSucces(null);
        if (wizard.isLast) {
            for (let index = 0; index < STEPS.length - 1; index++) {
                if (!(await validerEtape(index))) {
                    wizard.goTo(index);
                    return;
                }
            }
            await envoyer();
            return;
        }
        if (await validerEtape(wizard.current)) {
            // Première visite de l'étape « Chambres » : une configuration prête à remplir.
            if (wizard.current === 1 && getValues('lignes').length === 0) setValue('lignes', [nouvelleLigne()]);
            if (wizard.current === 1 && !getValues('unitaire.typeOccupation')) setValue('unitaire.typeOccupation', occupationDefaut);
            wizard.next();
        }
    };

    /* ───────────── Modifications des configurations ───────────── */
    const changerTypeLigne = (index: number, typeId: string) => {
        const type = typesChambre.find((t) => t.id === typeId);
        const lignes = getValues('lignes').map((ligne, i) =>
            i === index
                ? {
                      ...ligne,
                      type_chambre_code: typeId,
                      nb_lits: String(Math.min(3, Math.max(1, type?.capacite ?? 1))),
                      equipements: (type?.equipements ?? []).map((name) => ({ name, quantity: '1' })),
                  }
                : ligne,
        );
        setValue('lignes', lignes, { shouldValidate: !!errors.lignes?.[index] });
    };

    const ajouterLigne = () => setValue('lignes', [...getValues('lignes'), nouvelleLigne()]);
    const retirerLigne = (index: number) => {
        clearErrors('lignes');
        setValue(
            'lignes',
            getValues('lignes').filter((_, i) => i !== index),
        );
    };

    const goTo = (index: number) => {
        setErreurEtape(null);
        wizard.goTo(index);
    };

    return (
        <form
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                void suivant();
            }}
        >
            <Wizard
                steps={STEPS}
                current={wizard.current}
                reached={wizard.reached}
                onStepClick={(index) => void allerA(index)}
                onPrevious={() => goTo(wizard.current - 1)}
                onNext={() => void suivant()}
                cancelUrl={urls.index}
                submitting={submitting}
                submitLabel={values.mode === 'lot' ? `Créer ${nombre.format(totalLot)} chambre(s)` : 'Créer la chambre'}
                submitDisabled={values.mode === 'lot' ? !urls.massCreate : !urls.quickCreate}
            >
                {succes && (
                    <p role="status" className="flex items-start gap-2 rounded-brand border border-secondary/50 bg-soft px-4 py-3 text-sm text-primary">
                        <CheckCircle2 className="mt-0.5 size-4 shrink-0" aria-hidden />
                        <span>
                            {succes} Choisissez un autre niveau ou{' '}
                            <a href={`${urls.index}?batiment=${encodeURIComponent(values.batiment)}`} className="underline">
                                voyez le plan du bâtiment
                            </a>
                            .
                        </span>
                    </p>
                )}
                {erreurEtape && <FormAlert>{erreurEtape}</FormAlert>}

                {wizard.current === 0 && (
                    <div className="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                        <Field id="cite" label="Cité" required error={errors.cite?.message}>
                            <Select
                                value={values.cite || undefined}
                                onValueChange={(id) => {
                                    setValue('cite', id, { shouldValidate: true });
                                    setValue('batiment', '');
                                    setValue('niveau', '');
                                }}
                            >
                                <SelectTrigger id="cite" invalid={!!errors.cite}>
                                    <SelectValue placeholder="Sélectionnez une cité" />
                                </SelectTrigger>
                                <SelectContent>
                                    {cites.map((c) => (
                                        <SelectItem key={c.id} value={c.id}>
                                            {c.name}
                                            {!c.actif ? ' (désactivée)' : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                        <Field
                            id="batiment"
                            label="Bâtiment"
                            required
                            error={errors.batiment?.message}
                            hint={values.cite && batimentsCite.length === 0 ? 'Aucun bâtiment dans cette cité : créez-en un d’abord.' : undefined}
                        >
                            <Select
                                value={values.batiment || undefined}
                                onValueChange={(id) => {
                                    setValue('batiment', id, { shouldValidate: true });
                                    setValue('niveau', '');
                                }}
                                disabled={!values.cite || batimentsCite.length === 0}
                            >
                                <SelectTrigger id="batiment" invalid={!!errors.batiment}>
                                    <SelectValue placeholder={values.cite ? 'Sélectionnez un bâtiment' : 'Choisissez d’abord la cité'} />
                                </SelectTrigger>
                                <SelectContent>
                                    {batimentsCite.map((b) => (
                                        <SelectItem key={b.id} value={b.id}>
                                            {b.name}
                                            {!b.actif ? ' (désactivé)' : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                        {batiment && (
                            <div className="rounded-brand border bg-surface/60 px-4 py-3 text-sm md:col-span-2">
                                {chargementNiveaux ? (
                                    <span className="flex items-center gap-2 text-muted">
                                        <Loader2 className="size-4 animate-spin" aria-hidden /> Chargement de la structure…
                                    </span>
                                ) : niveaux ? (
                                    <span className="text-gray-900">
                                        {niveaux.length} niveau(x) ·{' '}
                                        <strong className="font-medium text-primary">{nombre.format(niveaux.reduce((s, n) => s + n.chambres_non_crees, 0))}</strong> chambre(s) à
                                        configurer · {nombre.format(niveaux.reduce((s, n) => s + n.chambres_crees, 0))} déjà configurée(s)
                                    </span>
                                ) : null}
                            </div>
                        )}
                    </div>
                )}

                {wizard.current === 1 && (
                    <>
                        {chargementNiveaux && !niveaux ? (
                            <p className="flex items-center gap-2 text-sm text-muted">
                                <Loader2 className="size-4 animate-spin" aria-hidden /> Chargement des niveaux…
                            </p>
                        ) : !niveaux?.length ? (
                            <FormAlert>Ce bâtiment n’a aucun niveau. Vérifiez sa structure.</FormAlert>
                        ) : (
                            <fieldset className="space-y-4" aria-describedby={errors.niveau ? 'niveau-error' : undefined}>
                                <legend className="sr-only">Niveau à configurer</legend>
                                {groupes.map(([aile, liste]) => (
                                    <div key={aile} className="space-y-2">
                                        {groupes.length > 1 && <SectionTitle>{aile}</SectionTitle>}
                                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                            {liste.map((n) => {
                                                const choisi = values.niveau === n.niveau_aile_code;
                                                const complet = n.chambres_non_crees === 0;
                                                const total = n.chambres_crees + n.chambres_non_crees;
                                                return (
                                                    <label
                                                        key={n.niveau_aile_code}
                                                        className={cn(
                                                            'flex cursor-pointer flex-col gap-2 rounded-brand border p-3 transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-secondary',
                                                            choisi ? 'border-primary bg-soft' : 'border-gray-200 hover:border-gray-300',
                                                        )}
                                                    >
                                                        <input
                                                            type="radio"
                                                            name="niveau"
                                                            className="sr-only"
                                                            checked={choisi}
                                                            onChange={() => {
                                                                setValue('niveau', n.niveau_aile_code, { shouldValidate: true });
                                                                setErreurEtape(null);
                                                                setSucces(null);
                                                            }}
                                                        />
                                                        <span className="flex items-center justify-between gap-2">
                                                            <span className={cn('text-sm font-medium', choisi ? 'text-primary' : 'text-gray-900')}>
                                                                {n.niveau_numero === 0 ? 'Rez-de-chaussée' : `Étage ${n.niveau_numero}`}
                                                            </span>
                                                            {complet ? (
                                                                <span className="rounded-full bg-soft px-2 py-0.5 text-xs text-primary">Complet</span>
                                                            ) : (
                                                                <span className="rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-800">{n.chambres_non_crees} à configurer</span>
                                                            )}
                                                        </span>
                                                        <span className="h-1.5 overflow-hidden rounded-full bg-gray-100" aria-hidden>
                                                            <span className="block h-full rounded-full bg-secondary" style={{ width: `${total ? (n.chambres_crees / total) * 100 : 0}%` }} />
                                                        </span>
                                                        <span className="text-xs text-muted">
                                                            {n.chambres_crees} / {total} chambre(s) configurée(s)
                                                        </span>
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ))}
                                {errors.niveau && (
                                    <p id="niveau-error" role="alert" className="text-xs font-medium text-red-600">
                                        {errors.niveau.message}
                                    </p>
                                )}
                            </fieldset>
                        )}

                        {values.niveau && (
                            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <section className="rounded-brand border">
                                    <h3 className="flex items-center gap-2 border-b px-4 py-2.5 text-sm font-medium text-gray-900">
                                        <DoorOpen className="size-4 text-primary" aria-hidden />
                                        Chambres configurées ({detail?.crees.length ?? '…'})
                                    </h3>
                                    <div className="max-h-72 overflow-y-auto p-2">
                                        {chargementDetail ? (
                                            <p className="flex items-center gap-2 p-2 text-sm text-muted">
                                                <Loader2 className="size-4 animate-spin" aria-hidden /> Chargement…
                                            </p>
                                        ) : detail?.crees.length ? (
                                            <ul className="divide-y">
                                                {detail.crees.map((chambre) => (
                                                    <li key={chambre.code} className="flex items-center gap-3 px-2 py-2">
                                                        <span className="min-w-0 flex-1">
                                                            <span className="block text-sm text-gray-900">{chambre.numero}</span>
                                                            <span className="block text-xs text-muted">
                                                                {chambre.type} · {chambre.nb_lits ?? 1} lit(s){chambre.pmr ? ' · PMR' : ''}
                                                            </span>
                                                        </span>
                                                        {urls.updateChambre && (
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Modifier la chambre ${chambre.numero}`}
                                                                onClick={() =>
                                                                    setAModifier({
                                                                        id: chambre.code,
                                                                        name: chambre.numero,
                                                                        type_chambre_id: chambre.type_code,
                                                                        niveau_confort: chambre.niveau_confort,
                                                                        pmr: !!Number(chambre.pmr),
                                                                        nb_lits: chambre.nb_lits ?? 1,
                                                                    })
                                                                }
                                                            >
                                                                <Pencil className="size-4" aria-hidden />
                                                            </Button>
                                                        )}
                                                    </li>
                                                ))}
                                            </ul>
                                        ) : (
                                            <p className="p-2 text-sm text-muted">Aucune chambre configurée sur ce niveau.</p>
                                        )}
                                    </div>
                                </section>
                                <section className="rounded-brand border">
                                    <h3 className="flex items-center gap-2 border-b px-4 py-2.5 text-sm font-medium text-gray-900">
                                        <DoorClosed className="size-4 text-amber-600" aria-hidden />
                                        À configurer ({detail?.nonCrees.length ?? '…'})
                                    </h3>
                                    <div className="p-4 text-sm text-muted">
                                        {detail && detail.nonCrees.length > 0
                                            ? `${detail.nonCrees.length} emplacement(s) prévu(s) à la création du bâtiment attendent un numéro, un type et des lits. Continuez pour les configurer.`
                                            : detail
                                              ? 'Toutes les chambres prévues sur ce niveau sont configurées.'
                                              : '…'}
                                    </div>
                                </section>
                            </div>
                        )}
                    </>
                )}

                {wizard.current === 2 && (
                    <>
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <ChoiceCard
                                name="mode"
                                value="lot"
                                selected={values.mode === 'lot'}
                                onSelect={() => setValue('mode', 'lot')}
                                title="Plusieurs chambres"
                                description="Numérotation automatique à partir d’un préfixe (ex. CH-01, CH-02…)."
                                icon={<Wand2 className="size-5" strokeWidth={1.25} aria-hidden />}
                            />
                            <ChoiceCard
                                name="mode"
                                value="unitaire"
                                selected={values.mode === 'unitaire'}
                                onSelect={() => setValue('mode', 'unitaire')}
                                title="Une seule chambre"
                                description="Numéro de porte libre (ex. A-12)."
                                icon={<Hash className="size-5" strokeWidth={1.25} aria-hidden />}
                            />
                        </div>
                        <p className="text-sm text-muted">
                            {niveau ? (niveau.niveau_numero === 0 ? 'Rez-de-chaussée' : `Étage ${niveau.niveau_numero}`) : 'Niveau'}
                            {niveau?.aile_name && groupes.length > 1 ? ` · ${niveau.aile_name}` : ''} ·{' '}
                            <strong className="font-medium text-gray-900">{restant}</strong> chambre(s) à configurer
                        </p>

                        {values.mode === 'lot' ? (
                            <div className="space-y-4">
                                {values.lignes.map((ligne, index) => (
                                    <LigneConfiguration
                                        key={`ligne-${index}`}
                                        index={index}
                                        ligne={ligne}
                                        control={control}
                                        register={register}
                                        errors={errors}
                                        typesChambre={typesChambre}
                                        typesOccupation={typesOccupation}
                                        onTypeChange={(typeId) => changerTypeLigne(index, typeId)}
                                        onRemove={values.lignes.length > 1 ? () => retirerLigne(index) : undefined}
                                    />
                                ))}
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <Button variant="secondary" className="h-10" onClick={ajouterLigne} disabled={values.lignes.length >= 10}>
                                        <ListPlus className="size-4" aria-hidden />
                                        Ajouter un autre type
                                    </Button>
                                    <p className={cn('text-sm', totalLot > restant ? 'font-medium text-red-600' : 'text-gray-900')}>
                                        Total : {nombre.format(totalLot)} / {restant} chambre(s)
                                    </p>
                                </div>
                            </div>
                        ) : (
                            <ChambreUnitaire control={control} register={register} errors={errors} typesChambre={typesChambre} typesOccupation={typesOccupation} setValue={setValue} />
                        )}
                    </>
                )}

                {wizard.current === 3 && (
                    <div className="space-y-4">
                        <RecapSection
                            title="Emplacement"
                            onEdit={() => goTo(0)}
                            rows={[
                                ['Cité', cite?.name],
                                ['Bâtiment', batiment?.name],
                                ['Niveau', niveau ? `${niveau.niveau_numero === 0 ? 'Rez-de-chaussée' : `Étage ${niveau.niveau_numero}`}${groupes.length > 1 ? ` · ${niveau.aile_name}` : ''}` : ''],
                                ['Restant à configurer', `${restant} chambre(s)`],
                            ]}
                        />
                        {values.mode === 'lot' ? (
                            values.lignes.map((ligne, index) => (
                                <RecapSection
                                    key={`recap-${index}`}
                                    title={`Configuration ${index + 1} — ${ligne.quantity} chambre(s)`}
                                    onEdit={() => goTo(2)}
                                    rows={[
                                        ['Numérotation', `${ligne.prefix ? `${ligne.prefix}01, ${ligne.prefix}02…` : '1, 2, 3…'} (à la suite des numéros existants)`],
                                        ['Type', nomType(ligne.type_chambre_code)],
                                        ['Occupation', nomOccupation(ligne.typeOccupation)],
                                        ['Lits', ligne.nb_lits],
                                        ['Confort', CONFORTS.find((c) => c.value === ligne.confort)?.label],
                                        ['Accès PMR', ligne.pmr === '1' ? 'Oui' : 'Non'],
                                        [
                                            'Équipements',
                                            ligne.equipements.filter((e) => Number(e.quantity) > 0).map((e) => `${e.name} ×${e.quantity}`).join(', ') || 'Par défaut du type',
                                        ],
                                    ]}
                                />
                            ))
                        ) : (
                            <RecapSection
                                title="Chambre"
                                onEdit={() => goTo(2)}
                                rows={[
                                    ['Numéro de porte', values.unitaire.numero_chambre],
                                    ['Type', nomType(values.unitaire.type_chambre_code)],
                                    ['Occupation', nomOccupation(values.unitaire.typeOccupation)],
                                    ['Lits', values.unitaire.nb_lits],
                                    ['Confort', CONFORTS.find((c) => c.value === values.unitaire.confort)?.label],
                                    ['Accès PMR', values.unitaire.pmr === '1' ? 'Oui' : 'Non'],
                                ]}
                            />
                        )}
                        <p className="text-xs text-muted">Le prix, la caution, l’avance et la superficie sont repris du type de chambre.</p>
                    </div>
                )}
            </Wizard>

            {urls.updateChambre && (
                <ChambreDialog
                    chambre={aModifier}
                    onClose={() => setAModifier(null)}
                    url={urls.updateChambre}
                    typesChambre={typesChambre}
                    onSaved={() => {
                        if (values.niveau && values.batiment) void chargerDetail(values.niveau, values.batiment);
                    }}
                />
            )}
        </form>
    );
}

/* ───────────── Sous-composants ───────────── */

type FormControl = Control<FormInput>;
type Register = ReturnType<typeof useForm<FormInput>>['register'];

function SelectChamp({
    id,
    value,
    onChange,
    placeholder,
    options,
    invalid,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    placeholder: string;
    options: { value: string; label: string }[];
    invalid?: boolean;
}) {
    return (
        <Select value={value || undefined} onValueChange={onChange}>
            <SelectTrigger id={id} invalid={invalid}>
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

const optionsTypes = (types: TypeChambreOption[]) =>
    types.map((t) => ({ value: t.id, label: `${t.name}${t.montant ? ` — ${nombre.format(t.montant)} FCFA` : ''}` }));

function LigneConfiguration({
    index,
    ligne,
    control,
    register,
    errors,
    typesChambre,
    typesOccupation,
    onTypeChange,
    onRemove,
}: {
    index: number;
    ligne: Ligne;
    control: FormControl;
    register: Register;
    errors: FieldErrors<FormInput>;
    typesChambre: TypeChambreOption[];
    typesOccupation: Option[];
    onTypeChange: (typeId: string) => void;
    onRemove?: () => void;
}) {
    const erreur = errors.lignes?.[index];
    const base = `lignes.${index}` as const;
    const [equipementsOuverts, setEquipementsOuverts] = useState(false);
    const equipements = useWatch({ control, name: `${base}.equipements` });

    return (
        <section className="space-y-4 rounded-brand border p-4">
            <div className="flex items-center justify-between gap-3">
                <h3 className="text-sm font-medium text-gray-900">Configuration {index + 1}</h3>
                {onRemove && (
                    <Button variant="ghost" size="sm" className="h-8 text-red-600 hover:bg-red-50 hover:text-red-700" onClick={onRemove}>
                        <Trash2 className="size-4" aria-hidden />
                        Retirer
                    </Button>
                )}
            </div>
            <div className="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2 xl:grid-cols-3">
                <Field id={`${base}.type_chambre_code`} label="Type de chambre" required error={erreur?.type_chambre_code?.message}>
                    <SelectChamp
                        id={`${base}.type_chambre_code`}
                        value={ligne.type_chambre_code}
                        onChange={onTypeChange}
                        placeholder="Sélectionnez un type"
                        options={optionsTypes(typesChambre)}
                        invalid={!!erreur?.type_chambre_code}
                    />
                </Field>
                <Field id={`${base}.quantity`} label="Nombre de chambres" required error={erreur?.quantity?.message}>
                    <Controller
                        control={control}
                        name={`${base}.quantity`}
                        render={({ field }) => (
                            <Counter id={`${base}.quantity`} label="nombre de chambres" value={field.value} onChange={field.onChange} onBlur={field.onBlur} min={1} max={500} invalid={!!erreur?.quantity} />
                        )}
                    />
                </Field>
                <Field id={`${base}.prefix`} label="Préfixe des numéros" error={erreur?.prefix?.message} hint="Vide : numéros 1, 2, 3…">
                    <Input id={`${base}.prefix`} placeholder="Ex : CH-" maxLength={20} invalid={!!erreur?.prefix} {...register(`${base}.prefix`)} />
                </Field>
                <ChampsCommuns base={base} control={control} erreur={erreur} typesOccupation={typesOccupation} />
            </div>

            {ligne.type_chambre_code && (
                <div className="rounded-brand border bg-surface/50">
                    <button
                        type="button"
                        onClick={() => setEquipementsOuverts((v) => !v)}
                        aria-expanded={equipementsOuverts}
                        className="flex w-full cursor-pointer items-center justify-between gap-2 px-3 py-2.5 text-left text-sm text-gray-900"
                    >
                        <span>Équipements par chambre ({equipements?.length ?? 0})</span>
                        <span className="text-xs text-primary">{equipementsOuverts ? 'Masquer' : 'Ajuster les quantités'}</span>
                    </button>
                    {equipementsOuverts && (
                        <div className="grid grid-cols-1 gap-3 border-t p-3 sm:grid-cols-2 lg:grid-cols-3">
                            {equipements?.length ? (
                                equipements.map((equipement, i) => (
                                    <Field key={equipement.name} id={`${base}.equipements.${i}.quantity`} label={equipement.name} error={erreur?.equipements?.[i]?.quantity?.message}>
                                        <Controller
                                            control={control}
                                            name={`${base}.equipements.${i}.quantity`}
                                            render={({ field }) => (
                                                <Counter id={`${base}.equipements.${i}.quantity`} label={equipement.name} value={field.value} onChange={field.onChange} onBlur={field.onBlur} max={20} />
                                            )}
                                        />
                                    </Field>
                                ))
                            ) : (
                                <p className="text-sm text-muted">Aucun équipement défini pour ce type.</p>
                            )}
                        </div>
                    )}
                </div>
            )}
        </section>
    );
}

/** Occupation, lits, confort et PMR : communs à la création en lot et unitaire. */
function ChampsCommuns({
    base,
    control,
    erreur,
    typesOccupation,
}: {
    base: `lignes.${number}` | 'unitaire';
    control: FormControl;
    erreur?: { typeOccupation?: { message?: string }; nb_lits?: { message?: string } };
    typesOccupation: Option[];
}) {
    const champ = <K extends 'typeOccupation' | 'nb_lits' | 'confort' | 'pmr'>(nom: K) => `${base}.${nom}` as FieldPath<FormInput>;

    return (
        <>
            <Field id={champ('typeOccupation')} label="Occupation" required error={erreur?.typeOccupation?.message} hint={typesOccupation.length === 1 ? 'Imposée par le bâtiment.' : undefined}>
                <Controller
                    control={control}
                    name={champ('typeOccupation')}
                    render={({ field }) => (
                        <SelectChamp
                            id={champ('typeOccupation')}
                            value={String(field.value ?? '')}
                            onChange={field.onChange}
                            placeholder="Sélectionnez"
                            options={typesOccupation.map((t) => ({ value: t.id, label: t.name }))}
                            invalid={!!erreur?.typeOccupation}
                        />
                    )}
                />
            </Field>
            <Field id={champ('nb_lits')} label="Lits par chambre" required error={erreur?.nb_lits?.message}>
                <Controller
                    control={control}
                    name={champ('nb_lits')}
                    render={({ field }) => (
                        <Counter id={champ('nb_lits')} label="lits par chambre" value={String(field.value ?? '')} onChange={field.onChange} onBlur={field.onBlur} min={1} max={3} invalid={!!erreur?.nb_lits} />
                    )}
                />
            </Field>
            <Field id={champ('confort')} label="Niveau de confort">
                <Controller
                    control={control}
                    name={champ('confort')}
                    render={({ field }) => (
                        <SelectChamp id={champ('confort')} value={String(field.value ?? '')} onChange={field.onChange} placeholder="Confort" options={CONFORTS.map((c) => ({ ...c }))} />
                    )}
                />
            </Field>
            <Field id={champ('pmr')} label="Accès PMR">
                <Controller
                    control={control}
                    name={champ('pmr')}
                    render={({ field }) => (
                        <SelectChamp
                            id={champ('pmr')}
                            value={String(field.value ?? '')}
                            onChange={field.onChange}
                            placeholder="PMR"
                            options={[
                                { value: '0', label: 'Non' },
                                { value: '1', label: 'Oui' },
                            ]}
                        />
                    )}
                />
            </Field>
        </>
    );
}

function ChambreUnitaire({
    control,
    register,
    errors,
    typesChambre,
    typesOccupation,
    setValue,
}: {
    control: FormControl;
    register: Register;
    errors: FieldErrors<FormInput>;
    typesChambre: TypeChambreOption[];
    typesOccupation: Option[];
    setValue: ReturnType<typeof useForm<FormInput>>['setValue'];
}) {
    const erreur = errors.unitaire;
    const type = useWatch({ control, name: 'unitaire.type_chambre_code' });

    return (
        <section className="space-y-4 rounded-brand border p-4">
            <div className="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2 xl:grid-cols-3">
                <Field id="unitaire.numero_chambre" label="Numéro de porte" required error={erreur?.numero_chambre?.message} hint="Unique sur le niveau.">
                    <Input id="unitaire.numero_chambre" placeholder="Ex : A-12" icon={<Hash />} invalid={!!erreur?.numero_chambre} {...register('unitaire.numero_chambre')} />
                </Field>
                <Field id="unitaire.type_chambre_code" label="Type de chambre" required error={erreur?.type_chambre_code?.message}>
                    <SelectChamp
                        id="unitaire.type_chambre_code"
                        value={type}
                        onChange={(id) => {
                            setValue('unitaire.type_chambre_code', id, { shouldValidate: true });
                            const capacite = typesChambre.find((t) => t.id === id)?.capacite ?? 1;
                            setValue('unitaire.nb_lits', String(Math.min(3, Math.max(1, capacite))), { shouldValidate: true });
                        }}
                        placeholder="Sélectionnez un type"
                        options={optionsTypes(typesChambre)}
                        invalid={!!erreur?.type_chambre_code}
                    />
                </Field>
                <ChampsCommuns base="unitaire" control={control} erreur={erreur} typesOccupation={typesOccupation} />
            </div>
            <p className="flex items-center gap-2 text-xs text-muted">
                <Plus className="size-3.5" aria-hidden />
                Les équipements par défaut du type sont attribués à la chambre.
            </p>
        </section>
    );
}
