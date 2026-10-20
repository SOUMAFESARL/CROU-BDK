import { zodResolver } from '@hookform/resolvers/zod';
import { Link } from '@inertiajs/react';
import { Accessibility, ArrowLeft, Check, CircleCheck, CircleX, DoorOpen, Info, Loader2, Route, TriangleAlert, UserRound } from 'lucide-react';
import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { lienContour, type CampagneAllocation } from '@/components/allocations/shared';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { Field } from '@/components/ui/field';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { envoyer } from '@/lib/inertia-form';
import { montant } from '@/lib/systeme';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';

/** Sexe requis par la chambre (`AttributionRules::sexeRequis()`) : m, f, mixte, conflit ou incomplet. */
type SexeRequis = 'm' | 'f' | 'mixte' | 'conflit' | 'incomplet';

type EtudiantOption = {
    id: string;
    name: string;
    matricule: string | null;
    email: string | null;
    niveau: string | null;
    pmr: boolean;
    sexe: 'm' | 'f' | null;
    priorites: string;
};

type ChambreOption = {
    id: string;
    numero: string;
    type: string;
    batiment: string | null;
    lieu: string;
    capacite: number;
    occupes: number;
    libres: number;
    prix: number;
    pmr: boolean;
    sexe: SexeRequis;
    lits: number[];
};

type Props = {
    campagne: CampagneAllocation;
    etudiants: EtudiantOption[];
    chambres: ChambreOption[];
    etudiantInitial: string;
    urls: { show: string | null; allocate: string | null; pmr: string | null };
};

const schema = z
    .object({
        etudiant_id: z.string().min(1, 'Choisissez un étudiant.'),
        allocation_type: z.enum(['automatique', 'manuelle']),
        chambre_id: z.string(),
        numero_lit: z.string(),
    })
    .refine((valeurs) => valeurs.allocation_type === 'automatique' || valeurs.chambre_id !== '', {
        path: ['chambre_id'],
        message: 'Choisissez une chambre pour une attribution manuelle.',
    });

type Valeurs = z.infer<typeof schema>;
type Controle = ['ok' | 'ko' | 'warn' | 'idle', string];

const SEXES = { m: 'hommes', f: 'femmes' };
const PREMIER_LIT = '__premier__';

/** Pré-contrôle affiché à l'écran (mêmes règles que l'ancienne page) ; le serveur refait la vérification complète. */
function controles(etudiant: EtudiantOption | undefined, chambre: ChambreOption | undefined, mode: Valeurs['allocation_type']): Controle[] {
    if (!etudiant) return [['idle', 'Sélectionnez un étudiant.']];
    const lignes: Controle[] = [];

    if (mode === 'automatique') {
        lignes.push(['ok', `Recherche automatique parmi les chambres ${etudiant.priorites || 'compatibles'}.`]);
        if (etudiant.pmr) lignes.push(['ok', 'Statut PMR confirmé : les chambres adaptées sont aussi proposées.']);
    } else if (!chambre) {
        return [['idle', 'Choisissez une chambre.']];
    } else {
        if (chambre.pmr && !etudiant.pmr) lignes.push(['ko', 'Chambre adaptée PMR : le statut PMR de l’étudiant n’est pas confirmé.']);
        else lignes.push(['ok', chambre.pmr ? 'Chambre PMR et statut PMR confirmé.' : 'Pas de restriction PMR.']);

        const requis = chambre.sexe;
        if (requis === 'incomplet') lignes.push(['ko', 'Type d’occupation non renseigné pour la chambre, le bâtiment ou la cité.']);
        else if (requis === 'conflit') lignes.push(['ko', 'Types d’occupation contradictoires entre la chambre, le bâtiment et la cité.']);
        else if (requis === 'mixte') lignes.push(['ok', 'Occupation mixte.']);
        else if (!etudiant.sexe) lignes.push(['warn', `Genre de l’étudiant non renseigné : chambre réservée aux ${SEXES[requis]}.`]);
        else if (etudiant.sexe !== requis) lignes.push(['ko', `Chambre réservée aux ${SEXES[requis]}.`]);
        else lignes.push(['ok', `Chambre réservée aux ${SEXES[requis]} : compatible.`]);

        lignes.push(chambre.libres > 0 ? ['ok', `${chambre.libres} place(s) libre(s).`] : ['ko', 'Plus aucune place libre.']);
    }
    lignes.push(['idle', 'Sanctions, campagne et places sont revérifiées à l’enregistrement.']);
    return lignes;
}

/** Attribution d'une chambre à un étudiant (remplace `allocation_form.blade.php` et `allocation-chambres.js`). */
export default function AllocationAllocate({ campagne, etudiants, chambres, etudiantInitial, urls }: Props) {
    const initial = etudiants.some((etudiant) => etudiant.id === etudiantInitial) ? etudiantInitial : '';
    const {
        control,
        handleSubmit,
        setValue,
        setError,
        clearErrors,
        formState: { errors, isSubmitting },
    } = useForm<Valeurs>({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues: { etudiant_id: initial, allocation_type: 'automatique', chambre_id: '', numero_lit: '' },
    });
    const [etudiantId, mode, chambreId, numeroLit] = useWatch({ control, name: ['etudiant_id', 'allocation_type', 'chambre_id', 'numero_lit'] });
    const etudiant = etudiants.find((item) => item.id === etudiantId);
    const chambre = mode === 'manuelle' ? chambres.find((item) => item.id === chambreId) : undefined;
    const lignes = useMemo(() => controles(etudiant, chambre, mode), [etudiant, chambre, mode]);

    const onSubmit = (valeurs: Valeurs) =>
        envoyer<Valeurs>(
            urls.allocate!,
            {
                etudiant_id: valeurs.etudiant_id,
                allocation_type: valeurs.allocation_type,
                ...(valeurs.allocation_type === 'manuelle' ? { chambre_id: valeurs.chambre_id, numero_lit: valeurs.numero_lit } : {}),
            },
            { setError, champs: ['etudiant_id', 'allocation_type', 'chambre_id', 'numero_lit'] },
        );

    return (
        <AdminLayout title="Attribuer une chambre">
            <PageHeader
                title="Attribuer une chambre"
                description={
                    <>
                        Campagne <strong className="font-medium text-gray-900">{campagne.annee}</strong> · La chambre est d’abord <strong className="font-medium text-gray-900">proposée</strong> à
                        l’étudiant ; elle devient définitive après son accord et la validation d’un agent.
                    </>
                }
                actions={
                    urls.show && (
                        <Link href={urls.show} className={lienContour}>
                            <ArrowLeft {...ICON} aria-hidden /> Suivi de la campagne
                        </Link>
                    )
                }
            />

            <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                {urls.allocate ? (
                    <form onSubmit={handleSubmit(onSubmit)} noValidate className="overflow-hidden rounded-brand border bg-white">
                        <div className="divide-y">
                            <Section icone={UserRound} titre="1. Étudiant" description="Dossiers éligibles de la campagne qui n’ont encore aucune chambre proposée ou occupée.">
                                <Field id="al-etudiant" label="Étudiant" required error={errors.etudiant_id?.message}>
                                    <Controller
                                        control={control}
                                        name="etudiant_id"
                                        render={({ field }) => (
                                            <Combobox
                                                id="al-etudiant"
                                                value={field.value}
                                                onChange={field.onChange}
                                                onBlur={field.onBlur}
                                                invalid={!!errors.etudiant_id}
                                                disabled={etudiants.length === 0}
                                                placeholder={etudiants.length === 0 ? 'Aucun dossier éligible en attente' : 'Choisir un étudiant'}
                                                searchPlaceholder="Nom ou matricule…"
                                                options={etudiants.map((item) => ({
                                                    value: item.id,
                                                    label: `${item.name} — ${item.matricule ?? ''}`,
                                                    description: item.niveau ?? undefined,
                                                }))}
                                            />
                                        )}
                                    />
                                </Field>
                                {etudiant && (
                                    <Infos
                                        lignes={[
                                            ['Matricule', etudiant.matricule ?? '—'],
                                            ['Niveau', etudiant.niveau ?? 'Non renseigné'],
                                            ['E-mail', etudiant.email || '—'],
                                            ['Statut PMR', etudiant.pmr ? 'Confirmé' : 'Non confirmé'],
                                        ]}
                                    />
                                )}
                            </Section>

                            <Section icone={Route} titre="2. Mode d’attribution" description="Le choix automatique applique les mêmes règles que l’attribution de masse.">
                                <Controller
                                    control={control}
                                    name="allocation_type"
                                    render={({ field }) => (
                                        <div role="radiogroup" aria-label="Mode d’attribution" className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            {(
                                                [
                                                    ['automatique', 'Automatique', 'Première chambre compatible, selon le niveau d’études de l’étudiant.'],
                                                    ['manuelle', 'Manuelle', 'Vous choisissez la chambre et, si besoin, le lit.'],
                                                ] as const
                                            ).map(([valeur, titre, description]) => (
                                                <label
                                                    key={valeur}
                                                    className={cn(
                                                        'flex cursor-pointer items-start gap-2.5 rounded-brand border p-3 transition-colors hover:border-primary',
                                                        field.value === valeur && 'border-primary bg-soft',
                                                    )}
                                                >
                                                    <input
                                                        type="radio"
                                                        name="allocation_type"
                                                        value={valeur}
                                                        checked={field.value === valeur}
                                                        onChange={() => {
                                                            field.onChange(valeur);
                                                            if (valeur === 'automatique') {
                                                                setValue('chambre_id', '');
                                                                setValue('numero_lit', '');
                                                                clearErrors('chambre_id');
                                                            }
                                                        }}
                                                        className="mt-0.5 size-4 accent-primary"
                                                    />
                                                    <span className="flex flex-col">
                                                        <span className="text-sm font-medium text-gray-900">{titre}</span>
                                                        <span className="text-xs text-muted">{description}</span>
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                    )}
                                />
                            </Section>

                            {mode === 'manuelle' && (
                                <Section icone={DoorOpen} titre="3. Chambre et lit" description="Seules les chambres actives des résidences de la campagne, avec au moins une place libre, sont proposées.">
                                    <Field id="al-chambre" label="Chambre" required error={errors.chambre_id?.message}>
                                        <Controller
                                            control={control}
                                            name="chambre_id"
                                            render={({ field }) => (
                                                <Combobox
                                                    id="al-chambre"
                                                    value={field.value}
                                                    onChange={(valeur) => {
                                                        field.onChange(valeur);
                                                        setValue('numero_lit', '');
                                                    }}
                                                    onBlur={field.onBlur}
                                                    invalid={!!errors.chambre_id}
                                                    placeholder={chambres.length === 0 ? 'Aucune chambre disponible' : 'Choisir une chambre'}
                                                    searchPlaceholder="Numéro, type, bâtiment…"
                                                    options={chambres.map((item) => ({
                                                        value: item.id,
                                                        label: `${item.numero} — ${item.type}${item.batiment ? ` · ${item.batiment}` : ''}`,
                                                        description: `${item.libres} place(s) libre(s) sur ${item.capacite}${item.pmr ? ' · PMR' : ''}`,
                                                        disabled: item.libres <= 0,
                                                    }))}
                                                />
                                            )}
                                        />
                                    </Field>
                                    <div className="sm:w-1/2">
                                        <Field id="al-lit" label="Lit" error={errors.numero_lit?.message} hint="Laissez « Premier lit disponible » pour un choix automatique.">
                                            <Controller
                                                control={control}
                                                name="numero_lit"
                                                render={({ field }) => (
                                                    <Select value={field.value || PREMIER_LIT} onValueChange={(valeur) => field.onChange(valeur === PREMIER_LIT ? '' : valeur)} disabled={!chambre}>
                                                        <SelectTrigger id="al-lit">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value={PREMIER_LIT}>Premier lit disponible</SelectItem>
                                                            {chambre?.lits.map((lit) => (
                                                                <SelectItem key={lit} value={String(lit)}>
                                                                    Lit {lit}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                )}
                                            />
                                        </Field>
                                    </div>
                                    {chambre && (
                                        <Infos
                                            lignes={[
                                                ['Type', chambre.type],
                                                ['Emplacement', chambre.lieu || '—'],
                                                ['Places libres', `${chambre.libres} / ${chambre.capacite}`],
                                                ['Loyer mensuel', montant(chambre.prix)],
                                            ]}
                                        >
                                            <Progress value={chambre.capacite ? (chambre.occupes / chambre.capacite) * 100 : 0} label="Occupation de la chambre" className="mt-3" />
                                        </Infos>
                                    )}
                                </Section>
                            )}
                        </div>
                        <div className="flex flex-col-reverse gap-2 border-t bg-surface/50 px-5 py-4 sm:flex-row sm:justify-end">
                            {urls.show && (
                                <Link href={urls.show} className={lienContour}>
                                    Annuler
                                </Link>
                            )}
                            <Button type="submit" size="sm" className="h-10 px-4" disabled={isSubmitting || etudiants.length === 0}>
                                {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />}
                                Proposer la chambre
                            </Button>
                        </div>
                    </form>
                ) : (
                    <p className="rounded-brand border bg-white p-6 text-sm text-muted">Vous n’avez pas l’autorisation d’attribuer une chambre.</p>
                )}

                <aside className="flex flex-col gap-6 xl:sticky xl:top-20">
                    {urls.pmr && <VerificationPmr url={urls.pmr} etudiants={etudiants} etudiantCourant={etudiantId} />}

                    <section className="overflow-hidden rounded-brand border bg-white">
                        <h2 className="border-b px-4 py-3 text-base font-medium text-gray-900">Résumé</h2>
                        <div className="divide-y px-4">
                            <Resume terme="Étudiant" valeur={etudiant?.name} vide="Non sélectionné" detail={etudiant && `${etudiant.matricule ?? ''} · ${etudiant.niveau ?? 'Non renseigné'}`} />
                            <Resume
                                terme="Chambre"
                                valeur={mode === 'automatique' ? 'Choix automatique' : chambre && `Chambre ${chambre.numero}`}
                                vide="Non sélectionnée"
                                detail={mode === 'automatique' ? 'Première chambre compatible' : chambre && `${chambre.type} · ${numeroLit ? `Lit ${numeroLit}` : 'premier lit disponible'}`}
                            />
                            <div className="py-3">
                                <p className="mb-2 text-xs font-medium text-muted">Pré-contrôle</p>
                                <ul className="flex flex-col gap-2 text-sm" aria-live="polite">
                                    {lignes.map(([niveau, texte]) => (
                                        <LigneControle key={texte} niveau={niveau}>
                                            {texte}
                                        </LigneControle>
                                    ))}
                                </ul>
                            </div>
                        </div>
                        <div className="flex justify-between gap-2 border-t bg-surface/50 px-4 py-3 text-xs text-muted">
                            <span>{nombre(etudiants.length)} étudiant(s) sans chambre</span>
                            <span>{nombre(chambres.length)} chambre(s) disponible(s)</span>
                        </div>
                    </section>
                </aside>
            </div>
        </AdminLayout>
    );
}

const pmrSchema = z.object({
    etudiant_id: z.string().min(1, 'Choisissez un étudiant.'),
    pmr_confirme: z.enum(['1', '0'], { error: 'Indiquez le résultat de la vérification.' }),
});

/** Enregistrement de la vérification PMR (action `pmr` inchangée), présélectionnée sur l'étudiant en cours. */
function VerificationPmr({ url, etudiants, etudiantCourant }: { url: string; etudiants: EtudiantOption[]; etudiantCourant: string }) {
    const {
        control,
        handleSubmit,
        setValue,
        getValues,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<z.infer<typeof pmrSchema>>({ resolver: zodResolver(pmrSchema), defaultValues: { etudiant_id: '', pmr_confirme: undefined } });

    useEffect(() => {
        if (etudiantCourant && !getValues('etudiant_id')) setValue('etudiant_id', etudiantCourant);
    }, [etudiantCourant, getValues, setValue]);

    const onSubmit = (valeurs: z.infer<typeof pmrSchema>) => envoyer(url, valeurs, { setError, champs: ['etudiant_id', 'pmr_confirme'] });

    return (
        <section className="overflow-hidden rounded-brand border bg-white">
            <h2 className="flex items-center gap-2 border-b px-4 py-3 text-base font-medium text-gray-900">
                <span className="flex size-8 items-center justify-center rounded-brand bg-[#F8E4EF] text-[#6E1F4E]" aria-hidden>
                    <Accessibility size={18} strokeWidth={1.25} />
                </span>
                Statut PMR
            </h2>
            <form onSubmit={handleSubmit(onSubmit)} noValidate className="flex flex-col gap-4 p-4">
                <p className="text-sm text-muted">Enregistrez le résultat de la vérification du dossier avant de proposer une chambre adaptée.</p>
                <Field id="al-pmr-etudiant" label="Étudiant" required error={errors.etudiant_id?.message}>
                    <Controller
                        control={control}
                        name="etudiant_id"
                        render={({ field }) => (
                            <Combobox
                                id="al-pmr-etudiant"
                                value={field.value}
                                onChange={field.onChange}
                                invalid={!!errors.etudiant_id}
                                placeholder="Choisir un étudiant"
                                searchPlaceholder="Nom ou matricule…"
                                options={etudiants.map((item) => ({
                                    value: item.id,
                                    label: `${item.name} — ${item.matricule ?? ''}`,
                                    description: item.pmr ? 'PMR confirmé' : 'non confirmé',
                                }))}
                            />
                        )}
                    />
                </Field>
                <fieldset>
                    <legend className="mb-1.5 text-sm font-medium text-gray-800">
                        Résultat de la vérification <span className="text-red-600">*</span>
                    </legend>
                    <Controller
                        control={control}
                        name="pmr_confirme"
                        render={({ field }) => (
                            <div role="radiogroup" className="flex flex-col gap-2">
                                {(
                                    [
                                        ['1', 'PMR confirmé'],
                                        ['0', 'PMR non confirmé'],
                                    ] as const
                                ).map(([valeur, libelle]) => (
                                    <label
                                        key={valeur}
                                        className={cn('flex cursor-pointer items-center gap-2.5 rounded-brand border px-3 py-2.5 text-sm transition-colors hover:border-primary', field.value === valeur && 'border-primary bg-soft')}
                                    >
                                        <input type="radio" name="pmr_confirme" value={valeur} checked={field.value === valeur} onChange={() => field.onChange(valeur)} className="size-4 accent-primary" />
                                        {libelle}
                                    </label>
                                ))}
                            </div>
                        )}
                    />
                    {errors.pmr_confirme && <p className="mt-1.5 text-xs font-medium text-red-600">{errors.pmr_confirme.message}</p>}
                </fieldset>
                <Button type="submit" variant="secondary" size="sm" className="h-10" disabled={isSubmitting}>
                    {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Check className="size-4" aria-hidden />}
                    Enregistrer la vérification
                </Button>
            </form>
        </section>
    );
}

function Section({ icone: Icone, titre, description, children }: { icone: typeof UserRound; titre: string; description: string; children: ReactNode }) {
    return (
        <section className="flex flex-col gap-4 p-5">
            <div className="flex items-start gap-3">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                    <Icone {...ICON} />
                </span>
                <div>
                    <h2 className="text-base font-medium text-gray-900">{titre}</h2>
                    <p className="text-sm text-muted">{description}</p>
                </div>
            </div>
            {children}
        </section>
    );
}

function Infos({ lignes, children }: { lignes: [string, string][]; children?: ReactNode }) {
    return (
        <div className="rounded-brand bg-surface p-4">
            <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                {lignes.map(([terme, valeur]) => (
                    <div key={terme} className="min-w-0">
                        <dt className="text-xs text-muted">{terme}</dt>
                        <dd className="truncate text-sm font-medium text-gray-900">{valeur}</dd>
                    </div>
                ))}
            </dl>
            {children}
        </div>
    );
}

function Resume({ terme, valeur, vide, detail }: { terme: string; valeur: string | undefined; vide: string; detail?: string | false }) {
    return (
        <div className="flex flex-col py-3">
            <span className="text-xs font-medium text-muted">{terme}</span>
            <span className={cn('text-sm font-medium', valeur ? 'text-gray-900' : 'text-gray-400')}>{valeur ?? vide}</span>
            {detail && <span className="text-xs text-muted">{detail}</span>}
        </div>
    );
}

const ICONES_CONTROLE = {
    ok: { icone: CircleCheck, classe: 'text-primary' },
    ko: { icone: CircleX, classe: 'text-red-600' },
    warn: { icone: TriangleAlert, classe: 'text-amber-700' },
    idle: { icone: Info, classe: 'text-muted' },
};

function LigneControle({ niveau, children }: { niveau: Controle[0]; children: ReactNode }) {
    const { icone: Icone, classe } = ICONES_CONTROLE[niveau];
    return (
        <li className={cn('flex items-start gap-2', classe)}>
            <Icone className="mt-0.5 size-4 shrink-0" strokeWidth={1.5} aria-hidden />
            <span className={niveau === 'idle' ? 'text-muted' : 'text-gray-900'}>{children}</span>
        </li>
    );
}
