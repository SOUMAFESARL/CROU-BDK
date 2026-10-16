import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ArrowRight, Check, CircleAlert, Loader2, Search, TriangleAlert, UserRound, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { fcfa } from '@/components/paiements/shared';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
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
};

const TYPES = [
    { value: 'loyer', label: 'Loyer' },
    { value: 'caution', label: 'Caution' },
    { value: 'penalite', label: 'Pénalité' },
    { value: 'autre', label: 'Autre' },
] as const;

const METHODES = [
    { value: 'orange_money', label: 'Orange Money' },
    { value: 'mtn_money', label: 'MTN Money' },
    { value: 'moov_money', label: 'Moov Money' },
    { value: 'especes', label: 'Espèces' },
    { value: 'autre', label: 'Autre' },
] as const;

const schema = z.object({
    resident_id: z.string().min(1, 'Sélectionnez un résident dans la liste.'),
    chambre_id: z.string().min(1, 'Ce résident n’a pas de chambre attribuée.'),
    batiment_id: z.string().min(1, 'Le bâtiment de ce résident est introuvable.'),
    cite_id: z.string(),
    contrat_id: z.string(),
    type_paiement: z.enum(['loyer', 'caution', 'penalite', 'autre'], { error: 'Choisissez le type de paiement.' }),
    montant: z.coerce.number({ error: 'Indiquez un montant.' }).min(100, 'Montant minimum : 100 FCFA.'),
    methode_paiement: z.enum(['orange_money', 'mtn_money', 'moov_money', 'especes', 'autre'], { error: 'Choisissez une méthode de paiement.' }),
    notes: z.string().trim().max(2000, '2 000 caractères au maximum.'),
});

type Saisie = z.input<typeof schema>;
type Valeurs = z.output<typeof schema>;

const ETAPES = ['Résident', 'Paiement', 'Vérification'];
const CHAMPS_ETAPE: (keyof Saisie)[][] = [['resident_id', 'chambre_id', 'batiment_id'], ['type_paiement', 'montant', 'methode_paiement', 'notes'], []];
const VIDE: Saisie = { resident_id: '', chambre_id: '', batiment_id: '', cite_id: '', contrat_id: '', type_paiement: 'loyer', montant: '', methode_paiement: 'especes', notes: '' };

type Props = { open: boolean; onClose: () => void; urlStore: string; urlRecherche: string };

/** Enregistrement d'un paiement en 3 étapes (action `store` inchangée : la répartition et les contrôles restent côté serveur). */
export function NouveauPaiementDialog({ open, onClose, urlStore, urlRecherche }: Props) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const [resident, setResident] = useState<ResidentTrouve | null>(null);
    const [erreurServeur, setErreurServeur] = useState<string | null>(null);
    const {
        register,
        control,
        handleSubmit,
        reset,
        setValue,
        trigger,
        setError,
        getValues,
        formState: { errors, isSubmitting },
    } = useForm<Saisie, unknown, Valeurs>({ resolver: zodResolver(schema), mode: 'onTouched', defaultValues: VIDE });

    useEffect(() => {
        if (open) {
            reset(VIDE);
            setResident(null);
            setEtape(0);
            setAtteinte(0);
            setErreurServeur(null);
        }
    }, [open, reset]);

    const choisir = (choix: ResidentTrouve | null) => {
        setResident(choix);
        const options = { shouldValidate: choix !== null };
        setValue('resident_id', choix?.id_etudiant ?? '', options);
        setValue('chambre_id', choix?.chambre_id ?? '', options);
        setValue('batiment_id', choix?.batiment_id ?? '', options);
        setValue('cite_id', choix?.residence_id ?? '');
        setValue('contrat_id', choix?.contrat_id ?? '');
        // Montant proposé : loyer mensuel + arriérés (comme l'ancien écran), modifiable à l'étape suivante.
        setValue('montant', choix ? Number(choix.loyer_mensuel || 0) + Number(choix.arriere || 0) || '' : '');
    };

    const allerA = async (cible: number) => {
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPE[index]))) {
                setEtape(index);
                return;
            }
        }
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const onSubmit = (valeurs: Valeurs) => {
        setErreurServeur(null);
        return envoyer(
            urlStore,
            { ...valeurs, devise: 'FCFA', montant_du: resident?.loyer_mensuel ?? 0 },
            {
                onSuccess: onClose,
                onError: (erreurs) => {
                    let fautive = -1;
                    Object.entries(erreurs).forEach(([cle, message]) => {
                        const index = CHAMPS_ETAPE.findIndex((champs) => champs.includes(cle as keyof Saisie));
                        if (index < 0) {
                            setErreurServeur(message);
                            return;
                        }
                        setError(cle as keyof Saisie, { type: 'server', message });
                        fautive = fautive < 0 ? index : Math.min(fautive, index);
                    });
                    if (fautive >= 0) setEtape(fautive);
                },
            },
        );
    };

    const montant = useWatch({ control, name: 'montant' });
    const valeurs = getValues();
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
                    <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} />
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
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field id="np-type" label="Type de paiement" required error={errors.type_paiement?.message}>
                                        <Controller
                                            control={control}
                                            name="type_paiement"
                                            render={({ field }) => (
                                                <Select value={field.value} onValueChange={field.onChange}>
                                                    <SelectTrigger id="np-type" invalid={!!errors.type_paiement}>
                                                        <SelectValue placeholder="Sélectionner un type" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {TYPES.map((type) => (
                                                            <SelectItem key={type.value} value={type.value}>
                                                                {type.label}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            )}
                                        />
                                    </Field>
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
                                                        {METHODES.map((methode) => (
                                                            <SelectItem key={methode.value} value={methode.value}>
                                                                {methode.label}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            )}
                                        />
                                    </Field>
                                </div>
                                <Field
                                    id="np-montant"
                                    label="Montant (FCFA)"
                                    required
                                    error={errors.montant?.message}
                                    hint={resident && resident.arriere > 0 ? `Montant recommandé : ${fcfa(resident.loyer_mensuel + resident.arriere)} (loyer + arriérés).` : 'Minimum 100 FCFA.'}
                                >
                                    <Input id="np-montant" type="number" min={100} step={100} inputMode="numeric" placeholder="Ex. 50000" invalid={!!errors.montant} {...register('montant')} />
                                </Field>
                                <Field id="np-notes" label="Notes" error={errors.notes?.message} hint="Facultatif.">
                                    <Textarea id="np-notes" rows={3} maxLength={2000} placeholder="Commentaires éventuels…" {...register('notes')} />
                                </Field>
                            </>
                        )}

                        {etape === 2 && (
                            <>
                                <p className="text-sm text-muted">
                                    Vérifiez les informations avant d’enregistrer. Le montant est d’abord affecté aux arriérés, puis au mois en cours ; le surplus devient une avance.
                                </p>
                                <dl className="divide-y rounded-brand border text-sm">
                                    <Recap terme="Résident" valeur={resident ? `${resident.name}${resident.matricule ? ` (${resident.matricule})` : ''}` : null} />
                                    <Recap terme="Logement" valeur={[resident?.residence, resident?.batiment, resident?.chambre && `Chambre ${resident.chambre}`].filter(Boolean).join(' · ')} />
                                    <Recap terme="Type" valeur={TYPES.find((type) => type.value === valeurs.type_paiement)?.label} />
                                    <Recap terme="Méthode" valeur={METHODES.find((methode) => methode.value === valeurs.methode_paiement)?.label} />
                                    <Recap terme="Montant" valeur={montant ? fcfa(Number(montant)) : null} fort />
                                    {resident && resident.arriere > 0 && <Recap terme="Arriérés du résident" valeur={fcfa(resident.arriere)} />}
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
                                <Button size="sm" className="h-10" onClick={() => allerA(etape + 1)}>
                                    Suivant <ArrowRight className="size-4" aria-hidden />
                                </Button>
                            ) : (
                                <Button type="submit" size="sm" className="h-10" disabled={isSubmitting}>
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
                <Info terme="Loyer mensuel" valeur={resident.loyer_mensuel ? fcfa(resident.loyer_mensuel) : null} />
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
