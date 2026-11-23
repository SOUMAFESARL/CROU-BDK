import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Check, CircleAlert, Loader2, Search, TriangleAlert, UserRound, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Controller, useFieldArray, useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { fcfa } from '@/components/paiements/shared';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ChampsManquants } from '@/components/ui/champs-manquants';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { champsManquants } from '@/lib/champs-manquants';
import { envoyer } from '@/lib/inertia-form';
import { systeme } from '@/lib/systeme';
import { initiales } from '@/lib/text';
import { cn } from '@/lib/utils';

/** Résident renvoyé par `admin.hebergement.paiements.recherche` (JSON inchangé). */
type ResidentTrouve = {
    id_etudiant: string;
    name: string;
    matricule: string | null;
    telephone: string | null;
    residence: string | null;
    residence_id: string | null;
    batiment: string | null;
    batiment_id: string | null;
    chambre: string | null;
    chambre_id: string | null;
    contrat_id: string | null;
    loyer_mensuel: number;
    arriere: number;
    montant_mois: number;
    total_du: number;
    statut_paiement: string;
    /** Étudiant en réadmission : la caution ne s'applique pas. */
    readmission: boolean;
    /** Mois suivant le dernier mois payé (loyer ou avance), `mm-aaaa` : premier mois du champ « Mois ». */
    prochain_mois: string;
};

/** Type de paiement actif, paramétré dans Paramétrage hébergement › Types de paiement (`value` = code enregistré). */
export type OptionTypePaiement = { value: string; label: string };

/** Loyer et avance sont répartis sur les loyers, mois par mois ; les autres types sont enregistrés tels quels. */
const TYPE_LOYER = 'loyer';

/** Type « Avance » du paramétrage (code `avance`) : solde les loyers des mois qui suivent ceux du loyer. */
const TYPE_AVANCE = 'avance';
const TYPES_MENSUELS = [TYPE_LOYER, TYPE_AVANCE];

/** `n` mois consécutifs (`mm-aaaa`) à partir de `depart` (`mm-aaaa`). */
const moisSuivants = (depart: string, n: number): string[] => {
    const [mois, annee] = depart.split('-').map(Number);
    if (!mois || !annee) return [];
    return Array.from({ length: Math.max(0, n) }, (_, i) => {
        const date = new Date(annee, mois - 1 + i, 1);
        return `${String(date.getMonth() + 1).padStart(2, '0')}-${date.getFullYear()}`;
    });
};

/** « Décembre 2026 » pour `12-2026`. */
const libelleMois = (code: string) => {
    const [mois, annee] = code.split('-').map(Number);
    const texte = new Date(annee, mois - 1, 1).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
    return texte.charAt(0).toUpperCase() + texte.slice(1);
};

/** Nombre de mois saisi s'il est valide, sinon 0. */
const nombreMois = (valeur: unknown) => (Number.isInteger(Number(valeur)) && Number(valeur) > 0 ? Number(valeur) : 0);

/** Type non proposé aux étudiants en réadmission (contrôlé aussi par le serveur). */
const TYPE_CAUTION = 'caution';

/** Types proposés au résident choisi : sans caution en réadmission. */
const typesPourResident = (types: OptionTypePaiement[], resident: ResidentTrouve | null) =>
    resident?.readmission ? types.filter((type) => type.value !== TYPE_CAUTION) : types;

/** Nombre de mois maximum par type coché (même limite côté serveur). */
const MOIS_MAX = 12;

/** Mois proposés pour un loyer : de quoi couvrir les arriérés et le mois en cours ; 1 mois pour les autres types. */
const moisPropose = (code: string, resident: ResidentTrouve | null) =>
    code === TYPE_LOYER && resident && resident.loyer_mensuel > 0 ? Math.min(MOIS_MAX, Math.ceil(Number(resident.arriere || 0) / resident.loyer_mensuel) + 1) : 1;

const METHODES = [
    { value: 'orange_money', label: 'Orange Money' },
    { value: 'mtn_money', label: 'MTN Money' },
    { value: 'moov_money', label: 'Moov Money' },
    { value: 'especes', label: 'Espèces' },
    { value: 'autre', label: 'Autre' },
] as const;

/** Méthodes permises par Configuration › Paiements (espèces → cash, Mobile Money → mobile ; « Autre » toujours). */
const methodesAcceptees = () =>
    METHODES.filter((m) => m.value === 'autre' || systeme().modesPaiement.includes(m.value === 'especes' ? 'cash' : 'mobile'));

const schema = z.object({
    resident_id: z.string().min(1, 'Sélectionnez un résident dans la liste.'),
    chambre_id: z.string().min(1, 'Ce résident n’a pas de chambre attribuée.'),
    batiment_id: z.string().min(1, 'Le bâtiment de ce résident est introuvable.'),
    cite_id: z.string(),
    contrat_id: z.string(),
    // Un paiement par type coché, chacun avec un nombre de mois ; le montant (mois × loyer du type de chambre) est calculé par le serveur.
    lignes: z
        .array(
            z.object({
                type_paiement: z.string().min(1),
                mois: z.coerce
                    .number({ error: 'Indiquez le nombre de mois.' })
                    .int('Nombre entier de mois.')
                    .min(1, 'Au moins 1 mois.')
                    .max(MOIS_MAX, `${MOIS_MAX} mois au maximum.`),
            }),
        )
        .min(1, 'Cochez au moins un type de paiement.'),
    methode_paiement: z.enum(['orange_money', 'mtn_money', 'moov_money', 'especes', 'autre'], { error: 'Choisissez une méthode de paiement.' }),
    notes: z.string().trim().max(2000, '2 000 caractères au maximum.'),
});

type Saisie = z.input<typeof schema>;
type Valeurs = z.output<typeof schema>;

const ETAPES = ['Résident', 'Paiement', 'Vérification'];
const CHAMPS_ETAPE: (keyof Saisie)[][] = [['resident_id', 'chambre_id', 'batiment_id'], ['lignes', 'methode_paiement', 'notes'], []];
const LIBELLES: Record<string, string> = { resident_id: 'Résident', lignes: 'Type de paiement', methode_paiement: 'Méthode de paiement' };
const VIDE: Saisie = { resident_id: '', chambre_id: '', batiment_id: '', cite_id: '', contrat_id: '', lignes: [], methode_paiement: 'especes', notes: '' };

type Props = { open: boolean; onClose: () => void; urlStore: string; urlRecherche: string; types: OptionTypePaiement[] };

/**
 * Enregistrement d'un paiement en 3 étapes. Plusieurs types peuvent être cochés ; pour chacun, seul le nombre de mois se saisit :
 * le montant vaut mois × loyer mensuel du type de chambre (Paramétrage hébergement › Types de chambre), recalculé par le serveur,
 * qui crée un paiement par type (répartition et contrôles restent côté serveur).
 */
export function NouveauPaiementDialog({ open, onClose, urlStore, urlRecherche, types }: Props) {
    // Loyer coché par défaut s'il est actif, sinon le premier type actif.
    const typeParDefaut = types.find((type) => type.value === TYPE_LOYER)?.value ?? types[0]?.value ?? '';
    const [etape, setEtape] = useState(0);
    const [resident, setResident] = useState<ResidentTrouve | null>(null);
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const [erreurMoisConcernes, setErreurMoisConcernes] = useState<string | null>(null);
    const {
        register,
        control,
        handleSubmit,
        reset,
        setValue,
        getValues,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<Saisie, unknown, Valeurs>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: VIDE });
    const { fields, insert, remove } = useFieldArray({ control, name: 'lignes' });

    useEffect(() => {
        if (open) {
            reset({ ...VIDE, lignes: typeParDefaut ? [{ type_paiement: typeParDefaut, mois: 1 }] : [] });
            setResident(null);
            setEtape(0);
            setErreurServeur(null);
            setErreurMoisConcernes(null);
        }
    }, [open, reset, typeParDefaut]);

    const choisir = (choix: ResidentTrouve | null) => {
        setResident(choix);
        // Réadmission : une caution déjà cochée est retirée.
        if (choix?.readmission) {
            const indexCaution = getValues('lignes').findIndex((ligne) => ligne.type_paiement === TYPE_CAUTION);
            if (indexCaution >= 0) remove(indexCaution);
        }
        const options = { shouldValidate: choix !== null };
        setValue('resident_id', choix?.id_etudiant ?? '', options);
        setValue('chambre_id', choix?.chambre_id ?? '', options);
        setValue('batiment_id', choix?.batiment_id ?? '', options);
        setValue('cite_id', choix?.residence_id ?? '');
        setValue('contrat_id', choix?.contrat_id ?? '');
        // Mois proposés pour le loyer (s'il est coché), modifiables à l'étape suivante.
        const indexLoyer = getValues('lignes').findIndex((ligne) => ligne.type_paiement === TYPE_LOYER);
        if (indexLoyer >= 0) setValue(`lignes.${indexLoyer}.mois`, moisPropose(TYPE_LOYER, choix));
    };

    // Cocher ajoute une ligne (dans l'ordre du paramétrage), décocher la retire.
    const basculer = (code: string, coche: boolean) => {
        const lignes = getValues('lignes');
        const index = lignes.findIndex((ligne) => ligne.type_paiement === code);
        if (!coche) {
            if (index >= 0) remove(index);
            return;
        }
        if (index >= 0) return;
        const rang = (valeur: string) => types.findIndex((type) => type.value === valeur);
        const position = lignes.filter((ligne) => rang(ligne.type_paiement) < rang(code)).length;
        insert(position, { type_paiement: code, mois: moisPropose(code, resident) }, { shouldFocus: false });
    };

    // Navigation libre entre les étapes : la validation complète n'a lieu qu'à l'enregistrement.
    const allerA = (cible: number) => setEtape(cible);

    const onSubmit = (valeurs: Valeurs) => {
        setErreurServeur(null);
        setErreurMoisConcernes(null);
        return envoyer(
            urlStore,
            { ...valeurs, mois_concernes: moisConcernes },
            {
                onSuccess: onClose,
                onError: (erreurs) => {
                    let fautive = -1;
                    Object.entries(erreurs).forEach(([cle, message]) => {
                        if (cle === 'mois_concernes') {
                            setErreurMoisConcernes(message);
                            fautive = fautive < 0 ? 1 : Math.min(fautive, 1);
                            return;
                        }
                        const racine = cle.split('.')[0] as keyof Saisie;
                        const index = CHAMPS_ETAPE.findIndex((champs) => champs.includes(racine));
                        if (index < 0) {
                            setErreurServeur(message);
                            return;
                        }
                        setError(cle as Parameters<typeof setError>[0], { type: 'server', message });
                        fautive = fautive < 0 ? index : Math.min(fautive, index);
                    });
                    if (fautive >= 0) setEtape(fautive);
                },
            },
        );
    };

    const valeurs = useWatch({ control }) as Saisie;
    const lignes = valeurs.lignes ?? [];
    const libelleType = (code: string) => types.find((type) => type.value === code)?.label ?? code;
    const avecLoyer = lignes.some((ligne) => TYPES_MENSUELS.includes(ligne.type_paiement));
    const horsLoyer = lignes.filter((ligne) => !TYPES_MENSUELS.includes(ligne.type_paiement)).map((ligne) => libelleType(ligne.type_paiement));
    // Champ « Mois » : mois consécutifs à partir du mois qui suit le dernier mois payé ; le loyer prend les premiers, l'avance les suivants.
    const indexLoyer = lignes.findIndex((ligne) => ligne.type_paiement === TYPE_LOYER);
    const indexAvance = lignes.findIndex((ligne) => ligne.type_paiement === TYPE_AVANCE);
    const moisLoyer = indexLoyer >= 0 ? nombreMois(lignes[indexLoyer].mois) : 0;
    const moisAvance = indexAvance >= 0 ? nombreMois(lignes[indexAvance].mois) : 0;
    const totalMois = moisLoyer + moisAvance;
    const moisConcernes = resident ? moisSuivants(resident.prochain_mois, totalMois) : [];
    const moisAffiches = resident ? moisSuivants(resident.prochain_mois, Math.max(12, totalMois + 1)) : [];
    // Cocher un mois coche aussi les précédents ; décocher un mois décoche aussi les suivants. Les nombres de mois suivent :
    // le loyer garde ses mois tant que possible, l'avance prend le reste (au moins 1 mois chacun quand les deux sont cochés).
    const basculerMois = (position: number) => {
        const nouveau = position < totalMois ? position : position + 1;
        const options = { shouldValidate: true, shouldDirty: true };
        if (indexLoyer >= 0 && indexAvance >= 0) {
            if (nouveau < 2) return;
            const avance = Math.max(1, nouveau - moisLoyer);
            setValue(`lignes.${indexLoyer}.mois`, nouveau - avance, options);
            setValue(`lignes.${indexAvance}.mois`, avance, options);
        } else if (nouveau >= 1) {
            setValue(`lignes.${indexLoyer >= 0 ? indexLoyer : indexAvance}.mois`, nouveau, options);
        }
        setErreurMoisConcernes(null);
    };
    // Montants affichés à titre indicatif : le serveur les recalcule à partir du type de chambre.
    const loyerMensuel = Number(resident?.loyer_mensuel || 0);
    const montantLigne = (mois: unknown) => nombreMois(mois) * loyerMensuel;
    const total = lignes.reduce((somme, ligne) => somme + montantLigne(ligne.mois), 0);
    const sansLoyer = resident !== null && loyerMensuel <= 0;
    const typesProposes = typesPourResident(types, resident);
    const avisHorsLoyer =
        horsLoyer.length > 0
            ? `${horsLoyer.join(', ')} : ${horsLoyer.length > 1 ? 'ces paiements ne sont pas affectés' : 'ce paiement n’est pas affecté'} au loyer, les arriérés et le solde du résident ne changent pas.`
            : null;
    // Nombre de mois manquant d'une ligne : libellé « Nombre de mois – <type> ».
    const libelles = { ...LIBELLES, ...Object.fromEntries(lignes.map((ligne, i) => [`lignes.${i}.mois`, `Nombre de mois – ${libelleType(ligne.type_paiement)}`])) };
    // Sans résident, seul « Résident » est signalé (chambre et bâtiment en découlent).
    const manquants = champsManquants(schema, valeurs, CHAMPS_ETAPE, libelles).filter((item) => !(valeurs.resident_id === '' && ['chambre_id', 'batiment_id'].includes(item.cle)));
    const erreurLignes = errors.lignes?.message ?? errors.lignes?.root?.message;
    const erreurResident = errors.resident_id?.message ?? errors.chambre_id?.message ?? errors.batiment_id?.message;

    return (
        <Dialog open={open} onOpenChange={(ouvert) => !ouvert && !isSubmitting && onClose()}>
            <DialogContent className="max-w-2xl">
                <form
                    onSubmit={handleSubmit(onSubmit, (fautes) => setEtape(Math.max(0, CHAMPS_ETAPE.findIndex((champs) => champs.some((champ) => fautes[champ])))))}
                    noValidate
                    className="flex min-h-0 flex-col"
                >
                    <DialogHeader>
                        <DialogTitle>Nouveau paiement</DialogTitle>
                        <DialogDescription>
                            Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                        </DialogDescription>
                    </DialogHeader>
                    <Stepper steps={ETAPES} current={etape} reached={ETAPES.length - 1} onStepClick={allerA} />
                    <DialogBody className="space-y-4">
                        {erreurServeur && (
                            <p role="alert" className="flex items-center gap-2 rounded-brand border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                <CircleAlert className="size-4 shrink-0" aria-hidden />
                                {erreurServeur}
                            </p>
                        )}

                        {etape === 0 && (
                            <>
                                <p className="text-sm text-muted">Seuls les résidents ayant une attribution confirmée apparaissent dans la recherche.</p>
                                <RechercheResident url={urlRecherche} resident={resident} onChange={choisir} erreur={erreurResident} />
                                {resident && <FicheResident resident={resident} />}
                            </>
                        )}

                        {etape === 1 && (
                            <>
                                {/* Les montants dépendent du résident choisi à l'étape 1 (loyer mensuel du type de sa chambre). */}
                                {resident ? (
                                    <div className="flex flex-col gap-1 rounded-brand border bg-surface px-4 py-2.5 text-sm sm:flex-row sm:items-center sm:justify-between">
                                        <span className="min-w-0 truncate">
                                            <span className="font-medium text-gray-900">{resident.name}</span>
                                            <span className="text-muted">{resident.chambre ? ` · Chambre ${resident.chambre}` : ''}</span>
                                            {resident.readmission && (
                                                <Badge variant="neutral" className="ml-2" title="Réadmission : la caution ne s’applique pas.">
                                                    Réadmission
                                                </Badge>
                                            )}
                                        </span>
                                        <span className="shrink-0 text-muted">
                                            Loyer mensuel : <span className="font-medium text-gray-900 tabular-nums">{loyerMensuel > 0 ? fcfa(loyerMensuel) : 'non défini'}</span>
                                        </span>
                                    </div>
                                ) : (
                                    <p className="flex items-center justify-between gap-3 rounded-brand border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-800">
                                        <span>Choisissez d’abord le résident : les montants sont calculés à partir du loyer de sa chambre.</span>
                                        <Button variant="outline" size="sm" onClick={() => setEtape(0)}>
                                            <ArrowLeft className="size-4" aria-hidden /> Résident
                                        </Button>
                                    </p>
                                )}
                                <fieldset className="space-y-1.5" aria-describedby={erreurLignes ? 'np-types-error' : avisHorsLoyer ? 'np-types-hint' : undefined}>
                                    <legend className="mb-1.5 block text-sm font-medium text-gray-800">
                                        Types de paiement<span className="ml-0.5 text-red-600">*</span>
                                    </legend>
                                    <div className={cn('divide-y rounded-brand border', erreurLignes && 'border-red-300')}>
                                        {typesProposes.map((type) => {
                                            const index = fields.findIndex((ligne) => ligne.type_paiement === type.value);
                                            const coche = index >= 0;
                                            const erreurMois = coche ? errors.lignes?.[index]?.mois?.message : undefined;
                                            const montantCalcule = coche ? montantLigne(lignes[index]?.mois) : 0;
                                            return (
                                                <div
                                                    key={type.value}
                                                    className={cn('flex flex-col gap-2 px-3 py-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4', coche && 'bg-surface')}
                                                >
                                                    <label className="flex min-h-10 cursor-pointer items-center gap-2.5 text-sm text-gray-900">
                                                        <Checkbox checked={coche} onCheckedChange={(valeur) => basculer(type.value, valeur === true)} />
                                                        {type.label}
                                                    </label>
                                                    {coche && (
                                                        <div className="space-y-1 sm:w-80">
                                                            <div className="grid grid-cols-[7rem_1fr] items-end gap-3">
                                                                <div className="space-y-1">
                                                                    <label htmlFor={`np-mois-${type.value}`} className="block text-xs text-muted">
                                                                        Nombre de mois<span className="ml-0.5 text-red-600">*</span>
                                                                    </label>
                                                                    <Input
                                                                        key={fields[index].id}
                                                                        id={`np-mois-${type.value}`}
                                                                        type="number"
                                                                        min={1}
                                                                        max={MOIS_MAX}
                                                                        step={1}
                                                                        inputMode="numeric"
                                                                        className="h-10"
                                                                        invalid={!!erreurMois}
                                                                        {...register(`lignes.${index}.mois`)}
                                                                    />
                                                                </div>
                                                                <div className="space-y-1">
                                                                    <p className="text-xs text-muted">Montant calculé</p>
                                                                    <p
                                                                        className="flex h-10 items-center justify-end rounded-brand border border-dashed bg-white px-3 text-sm tabular-nums"
                                                                        aria-live="polite"
                                                                    >
                                                                        {montantCalcule > 0 ? (
                                                                            <span className="font-medium text-gray-900">{fcfa(montantCalcule)}</span>
                                                                        ) : (
                                                                            <span className="text-xs text-muted">—</span>
                                                                        )}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            {erreurMois ? (
                                                                <p role="alert" className="text-xs font-medium text-red-600">
                                                                    {erreurMois}
                                                                </p>
                                                            ) : (
                                                                montantCalcule > 0 && (
                                                                    <p className="text-right text-xs text-muted">
                                                                        {montantCalcule / loyerMensuel} mois × {fcfa(loyerMensuel)}
                                                                        {type.value === TYPE_LOYER && resident && resident.arriere > 0 && ` · arriérés : ${fcfa(resident.arriere)}`}
                                                                    </p>
                                                                )
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                        {typesProposes.length === 0 && <p className="px-3 py-4 text-sm text-muted">Aucun type de paiement actif (Paramétrage hébergement › Types de paiement).</p>}
                                    </div>
                                    {erreurLignes ? (
                                        <p id="np-types-error" role="alert" className="text-xs font-medium text-red-600">
                                            {erreurLignes}
                                        </p>
                                    ) : (
                                        avisHorsLoyer && (
                                            <p id="np-types-hint" className="text-xs text-muted">
                                                {avisHorsLoyer}
                                            </p>
                                        )
                                    )}
                                </fieldset>
                                {lignes.length > 0 && (
                                    <p className="flex items-center justify-between rounded-brand bg-soft px-4 py-2.5 text-sm">
                                        <span className="text-muted">
                                            Total à encaisser ({lignes.length} {lignes.length > 1 ? 'paiements' : 'paiement'})
                                        </span>
                                        <span className="font-medium text-primary tabular-nums">{fcfa(total)}</span>
                                    </p>
                                )}
                                {resident && totalMois > 0 && (
                                    <fieldset className="space-y-1.5" aria-describedby={erreurMoisConcernes ? 'np-mois-error' : 'np-mois-hint'}>
                                        <legend className="mb-1.5 block text-sm font-medium text-gray-800">
                                            Mois<span className="ml-0.5 text-red-600">*</span>
                                        </legend>
                                        <div className={cn('grid grid-cols-2 gap-2 sm:grid-cols-4', erreurMoisConcernes && 'rounded-brand ring-1 ring-red-300')}>
                                            {moisAffiches.map((code, position) => {
                                                const coche = position < totalMois;
                                                const pour = !coche ? null : position < moisLoyer ? 'Loyer' : 'Avance';
                                                return (
                                                    <label
                                                        key={code}
                                                        className={cn(
                                                            'flex cursor-pointer items-start gap-2 rounded-brand border px-3 py-2 text-sm transition-colors',
                                                            coche ? 'border-secondary bg-surface' : 'bg-white hover:bg-gray-50',
                                                        )}
                                                    >
                                                        <Checkbox className="mt-0.5" checked={coche} onCheckedChange={() => basculerMois(position)} />
                                                        <span className="min-w-0">
                                                            <span className="block text-gray-900">{libelleMois(code)}</span>
                                                            {pour && <span className="block text-xs text-muted">{pour}</span>}
                                                        </span>
                                                    </label>
                                                );
                                            })}
                                        </div>
                                        {erreurMoisConcernes ? (
                                            <p id="np-mois-error" role="alert" className="text-xs font-medium text-red-600">
                                                {erreurMoisConcernes}
                                            </p>
                                        ) : (
                                            <p id="np-mois-hint" className="text-xs text-muted">
                                                Mois consécutifs à partir de {libelleMois(resident.prochain_mois)} (mois qui suit le dernier paiement) : {totalMois} mois cochés
                                                {moisLoyer > 0 && moisAvance > 0 ? ` (${moisLoyer} de loyer, puis ${moisAvance} d’avance)` : ''}. Cocher un mois coche aussi les
                                                précédents.
                                            </p>
                                        )}
                                    </fieldset>
                                )}
                                <Field id="np-methode" label="Méthode de paiement" required error={errors.methode_paiement?.message}>
                                    <Controller
                                        control={control}
                                        name="methode_paiement"
                                        render={({ field }) => (
                                            <Select value={field.value} onValueChange={field.onChange}>
                                                <SelectTrigger id="np-methode" invalid={!!errors.methode_paiement}>
                                                    <SelectValue placeholder="Sélectionner une méthode" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {methodesAcceptees().map((methode) => (
                                                        <SelectItem key={methode.value} value={methode.value}>
                                                            {methode.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        )}
                                    />
                                </Field>
                                <Field id="np-notes" label="Notes" error={errors.notes?.message} hint="Facultatif.">
                                    <Textarea id="np-notes" rows={3} maxLength={2000} placeholder="Commentaires éventuels…" {...register('notes')} />
                                </Field>
                            </>
                        )}

                        {etape === 2 && (
                            <>
                                <p className="text-sm text-muted">
                                    Vérifiez les informations avant d’enregistrer.{' '}
                                    {avecLoyer && 'Le loyer et l’avance sont d’abord affectés aux arriérés, puis aux mois cochés, dans l’ordre.'} {avisHorsLoyer}
                                </p>
                                <ChampsManquants manquants={manquants} etapes={ETAPES} onAller={setEtape} action="enregistrer le paiement" />
                                <dl className="divide-y rounded-brand border text-sm">
                                    <Recap terme="Résident" valeur={resident ? `${resident.name}${resident.matricule ? ` (${resident.matricule})` : ''}` : null} />
                                    <Recap terme="Logement" valeur={[resident?.residence, resident?.batiment, resident?.chambre && `Chambre ${resident.chambre}`].filter(Boolean).join(' · ')} />
                                    <Recap terme="Méthode" valeur={METHODES.find((methode) => methode.value === valeurs.methode_paiement)?.label} />
                                    {lignes.length === 0 && <Recap terme="Type de paiement" valeur={null} />}
                                    {lignes.map((ligne) => (
                                        <Recap
                                            key={ligne.type_paiement}
                                            terme={libelleType(ligne.type_paiement)}
                                            valeur={montantLigne(ligne.mois) > 0 ? `${ligne.mois} mois × ${fcfa(loyerMensuel)} = ${fcfa(montantLigne(ligne.mois))}` : null}
                                            fort={lignes.length === 1}
                                        />
                                    ))}
                                    {moisLoyer > 0 && <Recap terme="Mois – Loyer" valeur={moisConcernes.slice(0, moisLoyer).map(libelleMois).join(', ')} />}
                                    {moisAvance > 0 && <Recap terme="Mois – Avance" valeur={moisConcernes.slice(moisLoyer).map(libelleMois).join(', ')} />}
                                    {lignes.length > 1 && <Recap terme="Total" valeur={fcfa(total)} fort />}
                                    {avecLoyer && resident && resident.arriere > 0 && <Recap terme="Arriérés du résident" valeur={fcfa(resident.arriere)} />}
                                    {valeurs.notes && <Recap terme="Notes" valeur={String(valeurs.notes)} />}
                                </dl>
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
                                <Button key="envoyer" type="submit" size="sm" className="h-10" disabled={isSubmitting || manquants.length > 0 || sansLoyer}>
                                    {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />}
                                    Enregistrer le paiement
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** Recherche dynamique (2 caractères minimum, 300 ms après la frappe) sur l'endpoint JSON existant. */
function RechercheResident({ url, resident, onChange, erreur }: { url: string; resident: ResidentTrouve | null; onChange: (resident: ResidentTrouve | null) => void; erreur?: string }) {
    const [terme, setTerme] = useState('');
    const [resultats, setResultats] = useState<ResidentTrouve[] | null>(null);
    const [chargement, setChargement] = useState(false);
    const [echec, setEchec] = useState(false);
    const conteneur = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const q = terme.trim();
        if (resident || q.length < 2) {
            setResultats(null);
            return;
        }
        const controleur = new AbortController();
        const minuteur = window.setTimeout(async () => {
            setChargement(true);
            setEchec(false);
            try {
                const reponse = await fetch(`${url}?q=${encodeURIComponent(q)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controleur.signal,
                });
                if (!reponse.ok) throw new Error(String(reponse.status));
                setResultats((await reponse.json()) as ResidentTrouve[]);
            } catch (erreurFetch) {
                if ((erreurFetch as Error).name !== 'AbortError') setEchec(true);
            } finally {
                setChargement(false);
            }
        }, 300);
        return () => {
            window.clearTimeout(minuteur);
            controleur.abort();
        };
    }, [terme, url, resident]);

    useEffect(() => {
        const fermer = (event: MouseEvent) => {
            if (conteneur.current && !conteneur.current.contains(event.target as Node)) setResultats(null);
        };
        document.addEventListener('mousedown', fermer);
        return () => document.removeEventListener('mousedown', fermer);
    }, []);

    if (resident) {
        return (
            <div className="flex items-center justify-between gap-3 rounded-brand border border-secondary bg-soft px-3 py-2.5">
                <div className="flex min-w-0 items-center gap-3">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-white text-xs font-medium text-primary" aria-hidden>
                        {initiales(resident.name)}
                    </span>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-medium text-gray-900">{resident.name}</p>
                        <p className="truncate text-xs text-muted">{[resident.matricule, resident.telephone].filter(Boolean).join(' · ') || '—'}</p>
                    </div>
                </div>
                <Button variant="ghost" size="sm" onClick={() => onChange(null)}>
                    <X className="size-4" aria-hidden /> Changer
                </Button>
            </div>
        );
    }

    return (
        <div ref={conteneur} className="relative">
            <Field id="np-resident" label="Résident" required error={erreur} hint="Nom, matricule, téléphone ou e-mail (2 caractères minimum).">
                <Input
                    id="np-resident"
                    type="search"
                    autoFocus
                    autoComplete="off"
                    value={terme}
                    onChange={(event) => setTerme(event.target.value)}
                    placeholder="Rechercher un résident…"
                    icon={<Search />}
                    trailing={chargement ? <Loader2 className="mr-2 size-4 animate-spin text-muted" aria-hidden /> : undefined}
                    invalid={!!erreur}
                />
            </Field>
            {(resultats !== null || echec) && (
                <ul role="listbox" aria-label="Résidents trouvés" className="absolute inset-x-0 top-[4.75rem] z-20 max-h-72 overflow-y-auto rounded-brand border bg-white p-1 shadow-md">
                    {echec ? (
                        <li className="px-3 py-6 text-center text-sm text-red-700">La recherche a échoué. Réessayez.</li>
                    ) : resultats && resultats.length === 0 ? (
                        <li className="px-3 py-6 text-center text-sm text-muted">Aucun résident trouvé.</li>
                    ) : (
                        resultats?.map((item) => (
                            <li key={item.id_etudiant} role="option" aria-selected={false}>
                                <button
                                    type="button"
                                    onClick={() => {
                                        onChange(item);
                                        setTerme('');
                                    }}
                                    className="flex w-full cursor-pointer items-start justify-between gap-3 rounded-[6px] px-3 py-2 text-left text-sm hover:bg-soft focus-visible:bg-soft focus-visible:outline-none"
                                >
                                    <span className="min-w-0">
                                        <span className="flex flex-wrap items-center gap-2">
                                            <span className="font-medium text-gray-900">{item.name}</span>
                                            {item.arriere > 0 ? <Badge variant="danger">Arriéré</Badge> : <Badge variant="success">En règle</Badge>}
                                        </span>
                                        <span className="block truncate text-xs text-muted">
                                            {[item.matricule, item.telephone, item.residence, item.batiment, item.chambre && `Chambre ${item.chambre}`].filter(Boolean).join(' · ')}
                                        </span>
                                    </span>
                                    <span className="shrink-0 text-xs font-medium text-primary tabular-nums">{item.loyer_mensuel ? fcfa(item.loyer_mensuel) : '—'}</span>
                                </button>
                            </li>
                        ))
                    )}
                </ul>
            )}
        </div>
    );
}

function FicheResident({ resident }: { resident: ResidentTrouve }) {
    const enRetard = resident.arriere > 0;
    return (
        <section className="space-y-3">
            <dl className="grid grid-cols-1 gap-x-6 gap-y-2.5 rounded-brand border bg-surface p-4 text-sm sm:grid-cols-2">
                <Info terme="Résidence" valeur={resident.residence} />
                <Info terme="Bâtiment" valeur={resident.batiment} />
                <Info terme="Chambre" valeur={resident.chambre} />
                <Info terme="Loyer mensuel (type de chambre)" valeur={resident.loyer_mensuel ? fcfa(resident.loyer_mensuel) : null} />
                <div className="flex flex-col gap-0.5">
                    <dt className="text-xs text-muted">Statut</dt>
                    <dd>{enRetard ? <Badge variant="danger">En retard</Badge> : <Badge variant="success">En règle</Badge>}</dd>
                </div>
            </dl>
            {enRetard && (
                <p className="flex items-start gap-2 rounded-brand border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <TriangleAlert className="mt-0.5 size-4 shrink-0" aria-hidden />
                    <span>
                        Arriéré total : <strong className="font-medium">{fcfa(resident.arriere)}</strong>. Montant recommandé : {fcfa(resident.loyer_mensuel + resident.arriere)}.
                    </span>
                </p>
            )}
            {resident.chambre_id && !(resident.loyer_mensuel > 0) && (
                <p className="flex items-start gap-2 rounded-brand border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <CircleAlert className="mt-0.5 size-4 shrink-0" aria-hidden />
                    Le type de la chambre de ce résident n’a pas de loyer mensuel (Paramétrage hébergement › Types de chambre) : le paiement ne peut pas être calculé.
                </p>
            )}
            {!resident.chambre_id && (
                <p className="flex items-start gap-2 rounded-brand border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <UserRound className="mt-0.5 size-4 shrink-0" aria-hidden />
                    Ce résident n’a pas de chambre attribuée : le paiement ne peut pas être enregistré.
                </p>
            )}
        </section>
    );
}

function Info({ terme, valeur }: { terme: string; valeur: string | null | undefined }) {
    return (
        <div className="flex flex-col gap-0.5">
            <dt className="text-xs text-muted">{terme}</dt>
            <dd className="font-medium text-gray-900">{valeur || '—'}</dd>
        </div>
    );
}

function Recap({ terme, valeur, fort = false }: { terme: string; valeur: string | null | undefined; fort?: boolean }) {
    return (
        <div className="flex flex-col gap-0.5 px-4 py-2.5 sm:flex-row sm:justify-between sm:gap-4">
            <dt className="text-muted">{terme}</dt>
            <dd className={cn('font-medium text-gray-900 sm:text-right', fort && 'text-primary')}>{valeur || '—'}</dd>
        </div>
    );
}
