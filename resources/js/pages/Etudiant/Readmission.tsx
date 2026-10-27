import { zodResolver } from '@hookform/resolvers/zod';
import { ChevronLeft, ChevronRight, Info, Loader2, Send } from 'lucide-react';
import { useState } from 'react';
import { Controller, useForm, useWatch } from 'react-hook-form';
import { toast } from 'sonner';
import { Notice, Panneau } from '@/components/etudiant/panneau';
import { ICON } from '@/components/layout/app-sidebar';
import { EtudiantLayout } from '@/components/layout/etudiant-layout';
import { Button, buttonVariants } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Stepper } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { cn } from '@/lib/utils';
import { CHAMPS_ETAPE, MAX_SITUATION, readmissionSchema, type Readmission as ReadmissionForm } from '@/schemas/readmission';

type Option = { id: string; libelle: string };

type ReadmissionProps = {
    campagnes: (Option & { dateLimite: string | null })[];
    niveaux: Option[];
    liens: { envoyer: string; demandes: string };
};

const ETAPES = ['Campagne', 'Résultats', 'Situation'];

/** Page « Réadmission » : nouvelle demande de logement avec le compte existant, en trois étapes. */
export default function Readmission({ campagnes, niveaux, liens }: ReadmissionProps) {
    return (
        <EtudiantLayout title="Réadmission">
            <header className="mb-5">
                <h1 className="page-title text-xl font-medium text-gray-900">Demande de réadmission</h1>
                <p className="mt-1 text-sm text-muted">Votre compte et vos documents sont conservés. Votre demande sera examinée selon les règles de la campagne.</p>
            </header>

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5">
                {campagnes.length ? (
                    <Formulaire campagnes={campagnes} niveaux={niveaux} liens={liens} />
                ) : (
                    <Panneau surtitre="Réadmission" titre="Aucune campagne ouverte">
                        <Notice icone={Info}>Aucune campagne n’est active pour le moment. Revenez lorsque le service Logement aura ouvert les demandes.</Notice>
                        <a href={liens.demandes} className={cn(buttonVariants({ variant: 'secondary' }), 'mt-4 w-full font-medium sm:w-fit')}>
                            Suivre mes demandes
                            <ChevronRight {...ICON} />
                        </a>
                    </Panneau>
                )}
            </div>
        </EtudiantLayout>
    );
}

function Formulaire({ campagnes, niveaux, liens }: ReadmissionProps) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const {
        register,
        control,
        handleSubmit,
        trigger,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<ReadmissionForm>({
        resolver: zodResolver(readmissionSchema),
        defaultValues: { campagne_id: campagnes.length === 1 ? campagnes[0].id : '', niveau: '', moyenne: '', credit_valider: '', boursier: false, cas_social: '' },
        mode: 'onTouched',
    });
    const valeurs = useWatch({ control });

    const allerA = async (cible: number) => {
        // Avancer valide les étapes intermédiaires ; reculer est toujours permis.
        for (let index = etape; index < cible; index++) {
            if (!(await trigger(CHAMPS_ETAPE[index]))) {
                setEtape(index);
                return;
            }
        }
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const soumettre = handleSubmit(async (donnees) => {
        await envoyer(
            liens.envoyer,
            {
                ...donnees,
                moyenne: donnees.moyenne.replace(',', '.'),
                credit_valider: donnees.credit_valider.replace(',', '.'),
                boursier: donnees.boursier ? 1 : 0,
            },
            {
                onError: (erreurs) => {
                    // Erreurs serveur sous le champ concerné, puis retour à l'étape du premier champ fautif.
                    let fautive = -1;
                    Object.entries(erreurs).forEach(([cle, message]) => {
                        const index = CHAMPS_ETAPE.findIndex((champs) => (champs as string[]).includes(cle));
                        if (index < 0) {
                            toast.error(message);
                            return;
                        }
                        setError(cle as keyof ReadmissionForm, { type: 'server', message });
                        if (fautive < 0 || index < fautive) fautive = index;
                    });
                    if (fautive >= 0) setEtape(fautive);
                },
            },
        );
    });

    const campagne = campagnes.find((item) => item.id === valeurs.campagne_id);
    const niveau = niveaux.find((item) => item.id === valeurs.niveau);
    const longueur = (valeurs.cas_social ?? '').length;

    return (
        <form onSubmit={soumettre} noValidate className="rounded-brand border bg-white">
            <div className="px-4 pt-4 md:px-5 md:pt-5">
                <p className="text-xs text-muted">Réadmission</p>
                <h2 className="text-base font-medium text-gray-900">Nouvelle demande de logement</h2>
                <p className="mt-1 text-xs text-muted">
                    Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                </p>
            </div>
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} className="mt-3 px-4 md:px-5" />

            <div className="flex flex-col gap-4 p-4 md:p-5">
                {etape === 0 && (
                    <>
                        <Field id="rd-campagne" label="Campagne" required error={errors.campagne_id?.message} hint={campagne?.dateLimite ? `Date limite de dépôt : ${campagne.dateLimite}` : undefined}>
                            <Controller
                                control={control}
                                name="campagne_id"
                                render={({ field }) => (
                                    <Select value={field.value} onValueChange={field.onChange}>
                                        <SelectTrigger id="rd-campagne" invalid={!!errors.campagne_id} onBlur={field.onBlur}>
                                            <SelectValue placeholder="Choisir une campagne" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {campagnes.map((item) => (
                                                <SelectItem key={item.id} value={item.id}>
                                                    {item.libelle}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                        </Field>
                        <Field id="rd-niveau" label="Niveau d’étude actuel" required error={errors.niveau?.message}>
                            <Controller
                                control={control}
                                name="niveau"
                                render={({ field }) => (
                                    <Select value={field.value} onValueChange={field.onChange}>
                                        <SelectTrigger id="rd-niveau" invalid={!!errors.niveau} onBlur={field.onBlur}>
                                            <SelectValue placeholder="Choisir un niveau" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {niveaux.map((item) => (
                                                <SelectItem key={item.id} value={item.id}>
                                                    {item.libelle}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                        </Field>
                    </>
                )}

                {etape === 1 && (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field id="rd-moyenne" label="Moyenne sur 20" error={errors.moyenne?.message} hint="Moyenne de l’année écoulée.">
                                <Input id="rd-moyenne" inputMode="decimal" placeholder="Ex. : 12,50" invalid={!!errors.moyenne} {...register('moyenne')} />
                            </Field>
                            <Field id="rd-credits" label="Crédits validés" error={errors.credit_valider?.message} hint="Crédits de l’année écoulée, sur 60.">
                                <Input id="rd-credits" inputMode="numeric" placeholder="Ex. : 60" invalid={!!errors.credit_valider} {...register('credit_valider')} />
                            </Field>
                        </div>
                        <label htmlFor="rd-boursier" className="flex cursor-pointer items-center gap-3 rounded-brand border border-gray-300 p-3 text-sm text-gray-900 hover:bg-surface">
                            <Controller
                                control={control}
                                name="boursier"
                                render={({ field }) => <Checkbox id="rd-boursier" checked={field.value} onCheckedChange={(v) => field.onChange(v === true)} />}
                            />
                            Je suis boursier
                        </label>
                    </>
                )}

                {etape === 2 && (
                    <>
                        <Field
                            id="rd-situation"
                            label="Situation sociale ou observations"
                            error={errors.cas_social?.message}
                            hint={
                                <span className={cn(longueur > MAX_SITUATION - 100 && 'text-red-700')}>
                                    Facultatif · {longueur} / {MAX_SITUATION}
                                </span>
                            }
                        >
                            <Textarea id="rd-situation" rows={4} maxLength={MAX_SITUATION} placeholder="Précisez toute situation utile à l’examen de votre demande." invalid={!!errors.cas_social} {...register('cas_social')} />
                        </Field>
                        <div className="rounded-brand bg-surface p-3">
                            <p className="mb-2 text-xs text-muted">Récapitulatif</p>
                            <dl className="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                                <Recap libelle="Campagne" valeur={campagne?.libelle} />
                                <Recap libelle="Niveau" valeur={niveau?.libelle} />
                                <Recap libelle="Moyenne" valeur={valeurs.moyenne ? `${valeurs.moyenne} / 20` : null} />
                                <Recap libelle="Crédits validés" valeur={valeurs.credit_valider} />
                                <Recap libelle="Boursier" valeur={valeurs.boursier ? 'Oui' : 'Non'} />
                            </dl>
                        </div>
                    </>
                )}
            </div>

            <div className="flex flex-col-reverse gap-2 border-t p-4 sm:flex-row sm:justify-between md:px-5">
                {etape === 0 ? (
                    <a href={liens.demandes} className={cn(buttonVariants({ variant: 'outline' }), 'font-medium')}>
                        Mes demandes
                    </a>
                ) : (
                    <Button variant="outline" onClick={() => setEtape(etape - 1)} disabled={isSubmitting} className="font-medium">
                        <ChevronLeft {...ICON} />
                        Précédent
                    </Button>
                )}
                {etape < ETAPES.length - 1 ? (
                    <Button onClick={() => allerA(etape + 1)} className="font-medium">
                        Suivant
                        <ChevronRight {...ICON} />
                    </Button>
                ) : (
                    <Button type="submit" disabled={isSubmitting} className="font-medium">
                        {isSubmitting ? <Loader2 {...ICON} className="animate-spin" /> : <Send {...ICON} />}
                        Soumettre ma demande
                    </Button>
                )}
            </div>
        </form>
    );
}

function Recap({ libelle, valeur }: { libelle: string; valeur: string | null | undefined }) {
    return (
        <div className="min-w-0">
            <dt className="text-xs text-muted">{libelle}</dt>
            <dd className={cn('truncate', valeur ? 'text-gray-900' : 'text-gray-400')}>{valeur || 'Non renseigné'}</dd>
        </div>
    );
}

