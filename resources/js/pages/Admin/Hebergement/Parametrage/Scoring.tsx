import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { Accessibility, History, Info, Power, Star } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Controller, useForm, type Path, type Resolver } from 'react-hook-form';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { ChoixCampagne, ErreurGenerale, Note, PiedAssistant, useAssistant, Vide, type CampagneOption } from '@/components/parametrage-hebergement/shared';
import { ToggleCard } from '@/components/parc-immobilier/form/inputs';
import { Badge } from '@/components/ui/badge';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { Progress } from '@/components/ui/progress';
import { Stepper } from '@/components/ui/stepper';
import { envoyer } from '@/lib/inertia-form';
import { nombre } from '@/lib/text';
import { cn } from '@/lib/utils';
import { arrondi, enNombre, scoringSchema, SEUILS_DISTANCE_DEFAUT, type ScoringValeurs } from '@/schemas/parametrage-hebergement';

type Scoring = {
    weights: Record<string, number>;
    points: Record<string, Record<string, number>>;
    /** Début (km) des tranches « proche », « loin » et « très loin ». */
    distance_thresholds: Record<string, number>;
    minimum_score: number;
    pmr_first: boolean;
    active: boolean;
};

type Version = { id: number; date: string | null; active: boolean; minimum_score: number; auteur: string | null };

type Props = {
    campagnes: CampagneOption[];
    campagneId: string | null;
    scoring: Scoring | null;
    history: Version[];
    groups: Record<string, string>;
    fields: Record<string, Record<string, string>>;
    urls: { index: string; parametrage: string; store: string | null };
};

/** Valeurs d'exemple de l'ancien formulaire, proposées tant qu'aucun barème n'est enregistré. */
const EXEMPLE: Scoring = {
    weights: { social: 40, academique: 30, geographique: 20, specifique: 10 },
    points: {
        social: { boursier: 100, non_boursier: 0 },
        academique: { tb: 100, bien: 80, ab: 60, passable: 40, insuffisant: 0 },
        geographique: { local: 0, proche: 35, loin: 65, tres_loin: 100 },
        specifique: { pmr: 100, autre: 0 },
    },
    distance_thresholds: { ...SEUILS_DISTANCE_DEFAUT },
    minimum_score: 0,
    pmr_first: false,
    active: false,
};

const ETAPES = ['Pondérations', 'Points par critère', 'Seuil et activation', 'Vérification'];
const CHAMPS = [['weights'], ['points', 'distance_thresholds'], ['minimum_score', 'pmr_first', 'active'], []] as unknown as Path<ScoringValeurs>[][];
const CLES_SEUILS = Object.keys(SEUILS_DISTANCE_DEFAUT) as (keyof typeof SEUILS_DISTANCE_DEFAUT)[];

const texte = (n: number | undefined) => (n === undefined || n === null ? '' : String(n));

/** Libellé d'une tranche de distance d'après les seuils saisis (« 50 ≤ distance < 100 km »). */
function libelleDistance(cle: string, seuils: Record<string, string>) {
    const km = (k: string) => nombre(enNombre(seuils[k] ?? '') ?? 0, 2);
    switch (cle) {
        case 'local':
            return `Distance < ${km('proche')} km`;
        case 'proche':
            return `${km('proche')} ≤ distance < ${km('loin')} km`;
        case 'loin':
            return `${km('loin')} ≤ distance < ${km('tres_loin')} km`;
        case 'tres_loin':
            return `Distance ≥ ${km('tres_loin')} km`;
        default:
            return cle;
    }
}

/** Barème de classement par campagne (remplace `parametrage-herb/scoring-campagne/index.blade.php`) ; action `scoring-campagne.store` inchangée. */
export default function Scoring({ campagnes, campagneId, scoring, history, groups, fields, urls }: Props) {
    return (
        <AdminLayout title="Scoring">
            <PageHeader
                title="Barème de classement"
                description="Le score final (/100) est la somme des sous-scores multipliés par leur pondération ; il ordonne les dossiers lors de l’attribution automatique."
            />

            {campagnes.length === 0 ? (
                <Vide icone={<Star />}>Aucune campagne n’est enregistrée : créez d’abord une campagne.</Vide>
            ) : (
                <>
                    <ChoixCampagne campagnes={campagnes} valeur={campagneId} url={urls.index} />

                    {!scoring && (
                        <div className="mb-4">
                            <Note icone={<Info />} ton="attention">
                                <p>Aucun barème enregistré pour cette campagne. Les valeurs proposées sont des exemples : elles ne s’appliquent qu’après enregistrement et activation.</p>
                            </Note>
                        </div>
                    )}

                    <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
                        <Formulaire
                            key={campagneId ?? 'aucune'}
                            campagneId={campagneId}
                            scoring={scoring ?? EXEMPLE}
                            groups={groups}
                            fields={fields}
                            url={urls.store}
                            urlRetour={urls.parametrage}
                        />
                        <Historique versions={history} />
                    </div>
                </>
            )}
        </AdminLayout>
    );
}

function Formulaire({
    campagneId,
    scoring,
    groups,
    fields,
    url,
    urlRetour,
}: {
    campagneId: string | null;
    scoring: Scoring;
    groups: Record<string, string>;
    fields: Record<string, Record<string, string>>;
    url: string | null;
    urlRetour: string;
}) {
    const schema = useMemo(() => scoringSchema(fields), [fields]);
    const valeursInitiales = useMemo<ScoringValeurs>(
        () => ({
            weights: Object.fromEntries(Object.keys(groups).map((g) => [g, texte(scoring.weights[g] ?? EXEMPLE.weights[g])])),
            points: Object.fromEntries(
                Object.entries(fields).map(([g, champs]) => [g, Object.fromEntries(Object.keys(champs).map((k) => [k, texte(scoring.points[g]?.[k] ?? EXEMPLE.points[g]?.[k] ?? 0)]))]),
            ),
            distance_thresholds: Object.fromEntries(CLES_SEUILS.map((k) => [k, texte(scoring.distance_thresholds?.[k] ?? SEUILS_DISTANCE_DEFAUT[k])])),
            minimum_score: texte(scoring.minimum_score),
            pmr_first: scoring.pmr_first,
            active: scoring.active,
        }),
        [scoring, groups, fields],
    );
    const [erreur, setErreur] = useState<string | null>(null);
    const {
        register,
        control,
        trigger,
        watch,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<ScoringValeurs>({ resolver: zodResolver(schema) as unknown as Resolver<ScoringValeurs>, mode: 'onTouched', defaultValues: valeursInitiales });
    const { etape, atteinte, aller, allerA, toutValider, derniere } = useAssistant<ScoringValeurs>(CHAMPS, trigger, true);
    const v = watch();
    const total = arrondi(Object.values(v.weights).reduce((somme, n) => somme + (enNombre(n) ?? 0), 0));

    const enregistrer = (valeurs: ScoringValeurs) =>
        envoyer<ScoringValeurs>(
            url!,
            {
                campagne_id: campagneId,
                weights: Object.fromEntries(Object.entries(valeurs.weights).map(([g, n]) => [g, enNombre(n)])),
                points: Object.fromEntries(Object.entries(valeurs.points).map(([g, champs]) => [g, Object.fromEntries(Object.entries(champs).map(([k, n]) => [k, enNombre(n)]))])),
                distance_thresholds: Object.fromEntries(Object.entries(valeurs.distance_thresholds).map(([k, n]) => [k, enNombre(n)])),
                minimum_score: enNombre(valeurs.minimum_score),
                pmr_first: valeurs.pmr_first ? 1 : 0,
                active: valeurs.active ? 1 : 0,
            },
            {
                onError: (erreurs) => {
                    let fautive = -1;
                    const autres: string[] = [];
                    Object.entries(erreurs).forEach(([cle, message]) => {
                        const index = cle.startsWith('weights') ? 0 : cle.startsWith('points') || cle.startsWith('distance_thresholds') ? 1 : ['minimum_score', 'pmr_first', 'active'].includes(cle) ? 2 : -1;
                        if (index < 0) {
                            autres.push(message);
                            return;
                        }
                        setError(cle as Path<ScoringValeurs>, { type: 'server', message });
                        fautive = fautive < 0 ? index : Math.min(fautive, index);
                    });
                    if (fautive >= 0) aller(fautive);
                    setErreur(autres.length ? autres.join(' ') : null);
                },
            },
        );

    const erreurPoids = (g: string) => (errors.weights as Record<string, { message?: string }> | undefined)?.[g]?.message;
    // Total différent de 100 % : erreur portée par l'objet `weights` (schéma Zod ou `ValidationException` du serveur).
    const messageTotal = (errors.weights as { message?: unknown } | undefined)?.message;
    const erreurTotal = typeof messageTotal === 'string' ? messageTotal : null;
    const erreurPoint = (g: string, k: string) => (errors.points as Record<string, Record<string, { message?: string }>> | undefined)?.[g]?.[k]?.message;
    const erreurSeuil = (k: string) => (errors.distance_thresholds as Record<string, { message?: string }> | undefined)?.[k]?.message;
    // Libellé affiché d'un critère : les tranches de distance suivent les seuils saisis.
    const libelle = (g: string, k: string, label: string) => (g === 'geographique' ? libelleDistance(k, v.distance_thresholds) : label);

    return (
        <form
            noValidate
            className="flex min-w-0 flex-col overflow-hidden rounded-brand border bg-white"
            onSubmit={async (event) => {
                event.preventDefault();
                setErreur(null);
                if (!derniere) await allerA(etape + 1);
                else if (await toutValider()) await handleSubmit(enregistrer)();
            }}
        >
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={(index) => void allerA(index)} className="overflow-x-auto" />
            <div className="space-y-4 px-6 py-5">
                <ErreurGenerale message={erreur} />

                {etape === 0 && (
                    <>
                        <p className="text-sm text-muted">Part de chaque famille de critères dans le score final. Le total doit faire exactement 100 %.</p>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {Object.entries(groups).map(([g, label]) => (
                                <Field key={g} id={`sc-w-${g}`} label={`${label} (%)`} required error={erreurPoids(g)}>
                                    <Input id={`sc-w-${g}`} inputMode="decimal" placeholder="Ex. 25" invalid={!!erreurPoids(g)} {...register(`weights.${g}` as Path<ScoringValeurs>)} />
                                </Field>
                            ))}
                        </div>
                        <div className="space-y-1.5 rounded-brand border bg-surface px-4 py-3">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted">Total des pondérations</span>
                                <span className={cn('font-medium tabular-nums', total === 100 ? 'text-primary' : 'text-amber-700')}>{nombre(total, 2)} %</span>
                            </div>
                            <Progress value={Math.min(total, 100)} tone={total === 100 ? 'primary' : 'accent'} label={`Total des pondérations : ${nombre(total, 2)} %`} />
                        </div>
                        {erreurTotal && (
                            <p role="alert" className="text-xs font-medium text-red-600">
                                {erreurTotal}
                            </p>
                        )}
                    </>
                )}

                {etape === 1 && (
                    <>
                        <p className="text-sm text-muted">Points attribués (sur 100) selon la situation du dossier, pour chaque famille de critères.</p>
                        <div className="grid gap-4 lg:grid-cols-2">
                            {Object.entries(fields).map(([g, champs]) => (
                                <fieldset key={g} className="space-y-3 rounded-brand border p-4">
                                    <legend className="px-1 text-sm font-medium text-gray-900">
                                        {groups[g]} <span className="font-normal text-muted">· {nombre(enNombre(v.weights[g]) ?? 0, 2)} %</span>
                                    </legend>
                                    {g === 'geographique' && (
                                        <div className="space-y-2 rounded-brand bg-surface p-3">
                                            <p className="text-xs text-muted">Limites des tranches (km), de la plus proche à la plus éloignée.</p>
                                            <div className="grid grid-cols-3 gap-2">
                                                {CLES_SEUILS.map((k, index) => (
                                                    <div key={k} className="space-y-1">
                                                        <label htmlFor={`sc-d-${k}`} className="text-xs font-medium text-gray-800">
                                                            Limite {index + 1}
                                                        </label>
                                                        <Input
                                                            id={`sc-d-${k}`}
                                                            inputMode="numeric"
                                                            placeholder={`Ex. ${SEUILS_DISTANCE_DEFAUT[k]}`}
                                                            invalid={!!erreurSeuil(k)}
                                                            trailing={<span className="pr-2 text-xs text-muted">km</span>}
                                                            {...register(`distance_thresholds.${k}` as Path<ScoringValeurs>)}
                                                        />
                                                        {erreurSeuil(k) && (
                                                            <p role="alert" className="text-xs font-medium text-red-600">
                                                                {erreurSeuil(k)}
                                                            </p>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                    {Object.entries(champs).map(([k, label]) => (
                                        <div key={k} className="grid grid-cols-[minmax(0,1fr)_96px] items-start gap-3">
                                            <label htmlFor={`sc-p-${g}-${k}`} className="pt-3 text-sm text-gray-800">
                                                {libelle(g, k, label)}
                                            </label>
                                            <div className="space-y-1">
                                                <Input
                                                    id={`sc-p-${g}-${k}`}
                                                    inputMode="decimal"
                                                    placeholder="0 à 100"
                                                    invalid={!!erreurPoint(g, k)}
                                                    {...register(`points.${g}.${k}` as Path<ScoringValeurs>)}
                                                />
                                                {erreurPoint(g, k) && (
                                                    <p role="alert" className="text-xs font-medium text-red-600">
                                                        {erreurPoint(g, k)}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </fieldset>
                            ))}
                        </div>
                    </>
                )}

                {etape === 2 && (
                    <>
                        <div className="max-w-xs">
                            <Field
                                id="sc-min"
                                label="Score minimum pour l’attribution automatique (/100)"
                                required
                                error={errors.minimum_score?.message}
                                hint="Sous ce seuil, le dossier reste sans attribution automatique."
                            >
                                <Input id="sc-min" inputMode="decimal" placeholder="Ex. 40" invalid={!!errors.minimum_score} {...register('minimum_score')} />
                            </Field>
                        </div>
                        <Controller
                            control={control}
                            name="pmr_first"
                            render={({ field }) => (
                                <ToggleCard
                                    checked={field.value}
                                    onChange={field.onChange}
                                    label="Priorité absolue aux étudiants PMR confirmés"
                                    icon={<Accessibility />}
                                    description="Ils passent avant tous les autres dossiers, quel que soit leur score."
                                />
                            )}
                        />
                        <Controller
                            control={control}
                            name="active"
                            render={({ field }) => (
                                <ToggleCard
                                    checked={field.value}
                                    onChange={field.onChange}
                                    label="Activer ce barème pour la campagne"
                                    icon={<Power />}
                                    description="Désactivé, il est enregistré mais n’est pas utilisé par le classement automatique."
                                />
                            )}
                        />
                        <p className="text-xs text-muted">
                            À priorité et score égaux, le dossier le plus ancien passe en premier. Une donnée manquante pour un critère pondéré bloque le classement et doit
                            être complétée.
                        </p>
                    </>
                )}

                {etape === 3 && (
                    <div className="space-y-4">
                        <div className="overflow-x-auto rounded-brand border">
                            <table className="w-full text-sm">
                                <thead className="bg-surface text-left text-xs text-muted">
                                    <tr>
                                        <th className="px-4 py-2.5 font-medium">Critère</th>
                                        <th className="px-4 py-2.5 font-medium">Pondération</th>
                                        <th className="px-4 py-2.5 font-medium">Points</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {Object.entries(fields).map(([g, champs]) => (
                                        <tr key={g} className="align-top">
                                            <td className="px-4 py-3 font-medium text-gray-900">{groups[g]}</td>
                                            <td className="px-4 py-3 tabular-nums">{nombre(enNombre(v.weights[g]) ?? 0, 2)} %</td>
                                            <td className="px-4 py-3">
                                                <ul className="space-y-0.5 text-muted">
                                                    {Object.entries(champs).map(([k, label]) => (
                                                        <li key={k}>
                                                            {libelle(g, k, label)} : <span className="text-gray-900 tabular-nums">{nombre(enNombre(v.points[g]?.[k] ?? '') ?? 0, 2)}</span>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="flex flex-wrap gap-2 text-sm">
                            <Badge variant="info">Seuil : {nombre(enNombre(v.minimum_score) ?? 0, 2)} / 100</Badge>
                            <Badge variant={v.pmr_first ? 'warning' : 'neutral'}>{v.pmr_first ? 'Priorité PMR' : 'Sans priorité PMR'}</Badge>
                            <Badge variant={v.active ? 'success' : 'neutral'}>{v.active ? 'Barème activé' : 'Barème désactivé'}</Badge>
                        </div>
                        <p className="text-xs text-muted">Chaque enregistrement crée une nouvelle version. Elle s’applique aux prochains lancements ; les attributions existantes sont conservées.</p>
                        {!url && <p className="text-sm text-muted">Vous pouvez consulter ce barème mais pas le modifier.</p>}
                    </div>
                )}
            </div>
            <PiedAssistant
                etape={etape}
                derniere={derniere}
                envoi={isSubmitting}
                desactive={!url || !campagneId}
                onAnnuler={() => router.visit(urlRetour)}
                onPrecedent={() => aller(etape - 1)}
                libelle="Enregistrer le barème"
            />
        </form>
    );
}

function Historique({ versions }: { versions: Version[] }) {
    return (
        <aside className="h-fit rounded-brand border bg-white">
            <h2 className="flex items-center gap-2 border-b px-4 py-3 text-sm font-medium text-gray-900">
                <History {...ICON} className="text-primary" aria-hidden /> Historique des versions
            </h2>
            {versions.length === 0 ? (
                <p className="px-4 py-6 text-sm text-muted">Aucune version enregistrée.</p>
            ) : (
                <ol className="divide-y">
                    {versions.map((version, index) => (
                        <li key={version.id} className="space-y-1 px-4 py-3 text-sm">
                            <div className="flex items-center justify-between gap-2">
                                <span className="font-medium text-gray-900">Version n° {version.id}</span>
                                {index === 0 ? (
                                    <Badge variant={version.active ? 'success' : 'neutral'}>{version.active ? 'En vigueur' : 'Désactivée'}</Badge>
                                ) : (
                                    <Badge variant="neutral">{version.active ? 'Activée' : 'Désactivée'}</Badge>
                                )}
                            </div>
                            <p className="text-xs text-muted">
                                {version.date ?? '—'}
                                {version.auteur && ` · ${version.auteur}`} · seuil {nombre(version.minimum_score, 2)}
                            </p>
                        </li>
                    ))}
                </ol>
            )}
        </aside>
    );
}
