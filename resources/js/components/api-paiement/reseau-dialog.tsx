import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Loader2, Power, Save, TriangleAlert } from 'lucide-react';
import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { Controller, useForm, type FieldPath } from 'react-hook-form';
import { z } from 'zod';
import { ToggleCard } from '@/components/parc-immobilier/form/inputs';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Stepper } from '@/components/ui/stepper';
import { envoyer } from '@/lib/inertia-form';
import { CLES_IDENTIFIANTS, ENVIRONNEMENTS, noteReseau, urlAttendue, type CleIdentifiant, type EnvironnementMtn, type Reseau } from './types';

const ETAPES = ['Connexion', 'Identifiants', 'Activation'];

const texte = z.string().max(4096, 'La valeur ne doit pas dépasser 4096 caractères.');

const base = z.object({
    environment: z.enum(['sandbox', 'mtnivorycoast']).nullable(),
    api_url: z.string().trim().max(2048, 'Le lien API ne doit pas dépasser 2048 caractères.'),
    is_active: z.boolean(),
    api_key: texte,
    merchant_key: texte,
    api_user: texte,
    subscription_key: texte,
    request_signing_secret: texte,
    webhook_secret: texte,
});

type Valeurs = z.infer<typeof base>;
type Champ = FieldPath<Valeurs>;

const CHAMPS_ETAPE: Champ[][] = [['environment', 'api_url'], [...CLES_IDENTIFIANTS], ['is_active']];

const urlHttp = (valeur: string) => {
    try {
        const url = new URL(valeur);
        return url.protocol === 'http:' || url.protocol === 'https:';
    } catch {
        return false;
    }
};

/** Règles de `PaymentProviderController::update()` (le serveur revérifie tout) ; ici, chaque champ est en plus obligatoire. */
const schemaPour = (reseau: Reseau) =>
    base.superRefine((v, ctx) => {
        if (v.api_url && !urlHttp(v.api_url)) {
            ctx.addIssue({
                code: 'custom',
                path: ['api_url'],
                message: 'Le lien API doit être une URL HTTP ou HTTPS valide.',
            });
            return;
        }
        // Un changement d'environnement MTN efface les identifiants enregistrés : il faut les ressaisir.
        const changement = reseau.code === 'mtn' && v.environment !== reseau.environment;
        if (!v.api_url)
            ctx.addIssue({
                code: 'custom',
                path: ['api_url'],
                message: 'Renseignez le lien API.',
            });
        reseau.credentials.forEach((identifiant) => {
            if (!v[identifiant.field].trim() && (changement || !identifiant.saved)) {
                ctx.addIssue({
                    code: 'custom',
                    path: [identifiant.field],
                    message: `Renseignez « ${identifiant.label} ».`,
                });
            }
        });
        const attendue = urlAttendue(reseau, v.environment);
        if (v.api_url && attendue && v.api_url.replace(/\/+$/, '') !== attendue) {
            ctx.addIssue({
                code: 'custom',
                path: ['api_url'],
                message: `Pour ce connecteur, utilisez ${attendue}.`,
            });
        }
    });

const valeursInitiales = (reseau: Reseau): Valeurs => ({
    // Seul l'environnement Côte d’Ivoire est proposé : un réseau resté en sandbox y bascule (identifiants à ressaisir).
    environment: reseau.code === 'mtn' ? 'mtnivorycoast' : reseau.environment,
    api_url: reseau.api_url,
    is_active: reseau.is_active,
    api_key: '',
    merchant_key: '',
    api_user: '',
    subscription_key: '',
    request_signing_secret: '',
    webhook_secret: '',
});

type Props = { reseau: Reseau | null; onClose: () => void };

/** Configuration d'un réseau de paiement en 3 étapes (action `payment-providers.update` inchangée). */
export function ReseauDialog({ reseau, onClose }: Props) {
    return (
        <Dialog open={reseau !== null} onOpenChange={(ouvert) => !ouvert && onClose()}>
            <DialogContent className="max-w-2xl">{reseau && <Formulaire key={reseau.id} reseau={reseau} onClose={onClose} />}</DialogContent>
        </Dialog>
    );
}

function Formulaire({ reseau, onClose }: { reseau: Reseau; onClose: () => void }) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const [erreurGenerale, setErreurGenerale] = useState<string | null>(null);
    const schema = useMemo(() => schemaPour(reseau), [reseau]);
    const {
        register,
        control,
        trigger,
        watch,
        setValue,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<Valeurs>({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues: valeursInitiales(reseau),
    });

    useEffect(() => setErreurGenerale(null), [etape]);

    const aller = (index: number) => {
        setEtape(index);
        setAtteinte((max) => Math.max(max, index));
    };

    /** Avancer impose de valider chaque étape intermédiaire. */
    const allerA = async (cible: number) => {
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPE[index], { shouldFocus: true }))) {
                aller(index);
                return;
            }
        }
        aller(cible);
    };

    const valeurs = watch();
    const changementEnvironnement = reseau.code === 'mtn' && valeurs.environment !== reseau.environment;
    const attendue = urlAttendue(reseau, valeurs.environment);
    const complet = schema.safeParse(valeurs).success;

    const enregistrer = (v: Valeurs) =>
        envoyer<Valeurs>(
            reseau.update_url!,
            {
                api_url: v.api_url,
                is_active: v.is_active ? 1 : 0,
                ...(reseau.code === 'mtn' ? { environment: v.environment } : {}),
                ...Object.fromEntries(reseau.credentials.map((identifiant) => [identifiant.field, v[identifiant.field]])),
            },
            {
                method: 'put',
                errorBag: `provider_${reseau.id}`,
                onSuccess: onClose,
                onError: (erreurs) => {
                    let premiere = ETAPES.length - 1;
                    const autres: string[] = [];
                    Object.entries(erreurs).forEach(([cle, message]) => {
                        const index = CHAMPS_ETAPE.findIndex((champs) => champs.includes(cle as Champ));
                        if (index >= 0) {
                            setError(cle as Champ, { type: 'server', message });
                            premiere = Math.min(premiere, index);
                        } else {
                            autres.push(message);
                        }
                    });
                    aller(premiere);
                    if (autres.length) setErreurGenerale(autres.join(' '));
                },
            },
        );

    /** Dernière étape : tout est revérifié, une erreur ramène sur l'étape concernée. */
    const soumettre = async () => {
        for (let index = 0; index < ETAPES.length; index++) {
            if (!(await trigger(CHAMPS_ETAPE[index], { shouldFocus: true }))) {
                aller(index);
                return;
            }
        }
        await handleSubmit(enregistrer)();
    };

    const etatIdentifiant = (field: CleIdentifiant, saved: boolean) => {
        if (valeurs[field].trim()) return <Badge variant="info">Nouvelle valeur</Badge>;
        if (saved && !changementEnvironnement) return <Badge variant="success">Conservée</Badge>;
        return <Badge variant="neutral">Non renseignée</Badge>;
    };

    const note = noteReseau(reseau);

    return (
        <form
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                if (etape < ETAPES.length - 1) void allerA(etape + 1);
                else void soumettre();
            }}
            className="flex min-h-0 flex-col"
            autoComplete="off"
        >
            <DialogHeader>
                <DialogTitle>Configurer {reseau.name}</DialogTitle>
                <DialogDescription>
                    Étape {etape + 1} sur {ETAPES.length} · Les clés sont chiffrées ; laissez un champ de clé vide pour conserver sa valeur actuelle.
                </DialogDescription>
            </DialogHeader>
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={(index) => void allerA(index)} />
            <DialogBody className="space-y-4">
                {erreurGenerale && (
                    <p role="alert" className="rounded-brand border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {erreurGenerale}
                    </p>
                )}
                {note && (
                    <p className="flex items-start gap-2 rounded-brand border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <TriangleAlert className="mt-0.5 size-4 shrink-0" aria-hidden />
                        <span>{note}</span>
                    </p>
                )}

                {etape === 0 && (
                    <>
                        {reseau.code === 'mtn' && (
                            <Field id="rs-environment" label="Environnement MTN" required error={errors.environment?.message}>
                                <Controller
                                    control={control}
                                    name="environment"
                                    render={({ field }) => (
                                        <Select
                                            value={field.value ?? 'mtnivorycoast'}
                                            onValueChange={(valeur) => {
                                                field.onChange(valeur);
                                                // Comme l'ancien formulaire : le lien API suit l'environnement choisi.
                                                const url = urlAttendue(reseau, valeur as EnvironnementMtn);
                                                if (url)
                                                    setValue('api_url', url, {
                                                        shouldValidate: true,
                                                        shouldDirty: true,
                                                    });
                                            }}
                                        >
                                            <SelectTrigger id="rs-environment" invalid={!!errors.environment}>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {ENVIRONNEMENTS.map((environnement) => (
                                                    <SelectItem key={environnement.value} value={environnement.value}>
                                                        {environnement.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                />
                            </Field>
                        )}
                        <Field
                            id="rs-api-url"
                            label="Lien API"
                            required
                            error={errors.api_url?.message}
                        >
                            <Input
                                id="rs-api-url"
                                type="url"
                                maxLength={2048}
                                placeholder="https://..."
                                invalid={!!errors.api_url}
                                trailing={
                                    attendue && valeurs.api_url !== attendue ? (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="h-9"
                                            onClick={() =>
                                                setValue('api_url', attendue, {
                                                    shouldValidate: true,
                                                    shouldDirty: true,
                                                })
                                            }
                                        >
                                            Utiliser
                                        </Button>
                                    ) : undefined
                                }
                                className={attendue && valeurs.api_url !== attendue ? 'pr-24' : undefined}
                                {...register('api_url')}
                            />
                        </Field>
                        {reseau.webhook_url && (
                            <div className="space-y-1.5">
                                <p className="text-sm font-medium text-gray-800">Adresse de notification à déclarer chez Wave</p>
                                <code className="block rounded-brand border bg-surface px-3 py-2 font-mono text-xs break-all text-gray-900">
                                    {reseau.webhook_url}
                                </code>
                            </div>
                        )}
                    </>
                )}

                {etape === 1 && (
                    <>
                        {reseau.credentials.map((identifiant) => (
                            <Field
                                key={identifiant.field}
                                id={`rs-${identifiant.field}`}
                                label={identifiant.label}
                                required
                                error={errors[identifiant.field]?.message}
                            >
                                <div className="flex items-center gap-2">
                                    <div className="min-w-0 flex-1">
                                        <Input
                                            id={`rs-${identifiant.field}`}
                                            type="password"
                                            maxLength={4096}
                                            autoComplete="new-password"
                                            placeholder={
                                                identifiant.saved && !changementEnvironnement
                                                    ? 'Valeur enregistrée — laisser vide pour conserver'
                                                    : 'À renseigner'
                                            }
                                            invalid={!!errors[identifiant.field]}
                                            {...register(identifiant.field)}
                                        />
                                    </div>
                                    {etatIdentifiant(identifiant.field, identifiant.saved)}
                                </div>
                            </Field>
                        ))}
                    </>
                )}

                {etape === 2 && (
                    <>
                        <Controller
                            control={control}
                            name="is_active"
                            render={({ field }) => (
                                <ToggleCard
                                    checked={field.value}
                                    onChange={field.onChange}
                                    label={field.value ? 'Désactiver' : 'Activer le réseau'}
                                    icon={<Power />}
                                    description={
                                        field.value
                                            ? undefined
                                            : reseau.connecte
                                              ? 'Le réseau est proposé aux étudiants dès que sa configuration est complète.'
                                              : 'Connecteur provisoire : le réseau sera affiché mais pas encore utilisable pour payer.'
                                    }
                                />
                            )}
                        />
                        <dl className="divide-y rounded-brand border text-sm">
                            {reseau.code === 'mtn' && (
                                <Ligne
                                    label="Environnement"
                                    valeur={ENVIRONNEMENTS.find((environnement) => environnement.value === valeurs.environment)?.label ?? '—'}
                                />
                            )}
                            <Ligne label="Lien API" valeur={valeurs.api_url || '—'} />
                            {reseau.credentials.map((identifiant) => (
                                <Ligne key={identifiant.field} label={identifiant.label} valeur={etatIdentifiant(identifiant.field, identifiant.saved)} />
                            ))}
                            <Ligne
                                label="État après enregistrement"
                                valeur={valeurs.is_active ? <Badge variant="success">Activé</Badge> : <Badge variant="neutral">Inactif · masqué</Badge>}
                            />
                        </dl>
                        {Object.keys(errors).length > 0 && (
                            <p role="alert" className="text-sm font-medium text-red-600">
                                Certaines informations sont incomplètes : revenez aux étapes précédentes pour les corriger.
                            </p>
                        )}
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
                        <Button type="submit" size="sm" className="h-10">
                            Suivant <ArrowRight className="size-4" aria-hidden />
                        </Button>
                    ) : (
                        <Button type="submit" size="sm" className="h-10" disabled={isSubmitting || !reseau.update_url || !complet}>
                            {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Save className="size-4" aria-hidden />}
                            Enregistrer
                        </Button>
                    )}
                </div>
            </DialogFooter>
        </form>
    );
}

function Ligne({ label, valeur }: { label: string; valeur: ReactNode }) {
    return (
        <div className="flex flex-col gap-0.5 px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <dt className="text-muted">{label}</dt>
            <dd className="min-w-0 font-medium break-all text-gray-900">{valeur}</dd>
        </div>
    );
}
