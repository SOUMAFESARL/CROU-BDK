import { zodResolver } from '@hookform/resolvers/zod';
import { Check, Circle, CircleCheck, Eye, EyeOff, IdCard, Info, KeyRound, Loader2, Lock, Phone, ShieldCheck } from 'lucide-react';
import { useMemo, useState, type ComponentProps, type ReactNode } from 'react';
import { useForm } from 'react-hook-form';
import { ICON } from '@/components/layout/app-sidebar';
import { EtudiantLayout } from '@/components/layout/etudiant-layout';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { envoyer } from '@/lib/inertia-form';
import { cn } from '@/lib/utils';
import {
    coordonneesSchema,
    motDePasseSchema,
    reglesActives,
    type Coordonnees,
    type MotDePasse,
    type ReglesMotDePasse,
} from '@/schemas/etudiant-profil';

type Onglet = 'identite' | 'coordonnees' | 'securite';

type ProfilProps = {
    onglet: Onglet;
    etudiant: {
        nom: string | null;
        initiales: string;
        matricule: string | null;
        numeroEtudiant: string | null;
        genre: string | null;
        dateNaissance: string | null;
        lieuNaissance: string | null;
        nationalite: string | null;
        faculte: string | null;
        email1: string | null;
    };
    coordonnees: Coordonnees;
    reglesMotDePasse: ReglesMotDePasse;
    urls: { coordonnees: string; motDePasse: string };
};

const ONGLETS: { cle: Onglet; libelle: string; icone: typeof IdCard }[] = [
    { cle: 'identite', libelle: 'Identité', icone: IdCard },
    { cle: 'coordonnees', libelle: 'Coordonnées', icone: Phone },
    { cle: 'securite', libelle: 'Sécurité', icone: Lock },
];

/** Page « Mon profil » : identité en lecture seule, coordonnées et mot de passe modifiables. */
export default function Profil({ onglet, etudiant, coordonnees, reglesMotDePasse, urls }: ProfilProps) {
    const [actif, setActif] = useState<Onglet>(onglet);

    // L'onglet ouvert reste dans l'URL (?onglet=…) pour le partage et le retour arrière.
    const changerOnglet = (valeur: string) => {
        setActif(valeur as Onglet);
        const url = new URL(window.location.href);
        url.searchParams.set('onglet', valeur);
        window.history.replaceState(window.history.state, '', url);
    };

    return (
        <EtudiantLayout title="Mon profil">
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5">
                <header className="flex items-center gap-4 rounded-brand border bg-white p-4 md:p-5">
                    <span aria-hidden className="grid size-14 shrink-0 place-items-center rounded-full bg-primary text-lg font-medium text-white">
                        {etudiant.initiales}
                    </span>
                    <div className="min-w-0">
                        <p className="text-xs text-muted">Compte étudiant</p>
                        <h1 className="truncate text-lg font-medium text-gray-900">{etudiant.nom ?? 'Étudiant'}</h1>
                        <p className="flex flex-wrap gap-x-3 text-sm text-muted">
                            {etudiant.matricule && <span>{etudiant.matricule}</span>}
                            {etudiant.email1 && <span className="truncate">{etudiant.email1}</span>}
                        </p>
                    </div>
                </header>

                <Tabs value={actif} onValueChange={changerOnglet}>
                    <TabsList aria-label="Rubriques du profil" className="bg-white px-1">
                        {ONGLETS.map(({ cle, libelle, icone: Icone }) => (
                            <TabsTrigger key={cle} value={cle}>
                                <Icone {...ICON} />
                                {libelle}
                            </TabsTrigger>
                        ))}
                    </TabsList>

                    <TabsContent value="identite">
                        <Panneau surtitre="Identité" titre="Informations personnelles">
                            <Identite etudiant={etudiant} />
                        </Panneau>
                    </TabsContent>
                    <TabsContent value="coordonnees">
                        <Panneau surtitre="Coordonnées" titre="Comment vous joindre">
                            <FormulaireCoordonnees valeurs={coordonnees} email1={etudiant.email1} url={urls.coordonnees} />
                        </Panneau>
                    </TabsContent>
                    <TabsContent value="securite">
                        <Panneau surtitre="Sécurité" titre="Modifier mon mot de passe">
                            <FormulaireMotDePasse regles={reglesMotDePasse} url={urls.motDePasse} />
                        </Panneau>
                    </TabsContent>
                </Tabs>
            </div>
        </EtudiantLayout>
    );
}

function Panneau({ surtitre, titre, children }: { surtitre: string; titre: string; children: ReactNode }) {
    return (
        <section className="rounded-brand border bg-white p-4 md:p-6">
            <p className="text-xs text-muted">{surtitre}</p>
            <h2 className="mb-5 text-base font-medium text-gray-900">{titre}</h2>
            {children}
        </section>
    );
}

function Notice({ icone, children }: { icone: ReactNode; children: ReactNode }) {
    return (
        <div className="flex items-start gap-2.5 rounded-brand bg-soft p-3 text-sm text-primary [&_svg]:mt-0.5 [&_svg]:shrink-0">
            {icone}
            <span>{children}</span>
        </div>
    );
}

function Identite({ etudiant }: { etudiant: ProfilProps['etudiant'] }) {
    const lignes: [string, string | null][] = [
        ['Nom complet', etudiant.nom],
        ['Matricule', etudiant.matricule],
        ['Numéro étudiant', etudiant.numeroEtudiant],
        ['Genre', etudiant.genre],
        ['Date de naissance', etudiant.dateNaissance],
        ['Lieu de naissance', etudiant.lieuNaissance],
        ['Nationalité', etudiant.nationalite],
        ['Faculté', etudiant.faculte],
        ['E-mail principal', etudiant.email1],
    ];

    return (
        <div className="flex flex-col gap-5">
            <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                {lignes.map(([libelle, valeur]) => (
                    <div key={libelle} className="min-w-0 border-b pb-3">
                        <dt className="text-xs text-muted">{libelle}</dt>
                        <dd className={cn('mt-0.5 text-sm break-words', valeur ? 'text-gray-900' : 'text-gray-400')}>{valeur || 'Non renseigné'}</dd>
                    </div>
                ))}
            </dl>
            <Notice icone={<Info {...ICON} />}>Une information est inexacte ? Rapprochez-vous de l’administration du CROU pour la faire corriger.</Notice>
        </div>
    );
}

const CHAMPS_COORDONNEES = ['telephone1', 'telephone2', 'email2'] as const;

function FormulaireCoordonnees({ valeurs, email1, url }: { valeurs: Coordonnees; email1: string | null; url: string }) {
    const {
        register,
        handleSubmit,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<Coordonnees>({ resolver: zodResolver(coordonneesSchema), defaultValues: valeurs, mode: 'onTouched' });

    const envoyerCoordonnees = handleSubmit((donnees) =>
        envoyer<Coordonnees>(url, donnees, { method: 'patch', setError, champs: CHAMPS_COORDONNEES }),
    );

    return (
        <form onSubmit={envoyerCoordonnees} noValidate className="flex flex-col gap-4">
            <Field id="telephone1" label="Téléphone principal" required error={errors.telephone1?.message}>
                <Input
                    id="telephone1"
                    type="tel"
                    inputMode="tel"
                    autoComplete="tel"
                    maxLength={30}
                    placeholder="Ex. 07 00 00 00 00"
                    invalid={!!errors.telephone1}
                    aria-describedby={errors.telephone1 ? 'telephone1-error' : undefined}
                    {...register('telephone1')}
                />
            </Field>
            <Field id="telephone2" label="Téléphone secondaire" error={errors.telephone2?.message}>
                <Input
                    id="telephone2"
                    type="tel"
                    inputMode="tel"
                    autoComplete="tel"
                    maxLength={30}
                    placeholder="Facultatif"
                    invalid={!!errors.telephone2}
                    aria-describedby={errors.telephone2 ? 'telephone2-error' : undefined}
                    {...register('telephone2')}
                />
            </Field>
            <Field
                id="email2"
                label="E-mail secondaire"
                error={errors.email2?.message}
                hint={`L’e-mail principal (${email1 || 'non renseigné'}) sert à la connexion et ne peut pas être modifié ici.`}
            >
                <Input
                    id="email2"
                    type="email"
                    inputMode="email"
                    autoComplete="email"
                    maxLength={255}
                    placeholder="exemple@email.com"
                    invalid={!!errors.email2}
                    aria-describedby={errors.email2 ? 'email2-error' : 'email2-hint'}
                    {...register('email2')}
                />
            </Field>
            <div className="flex justify-end pt-1">
                <Button type="submit" disabled={isSubmitting} className="w-full font-medium sm:w-auto">
                    {isSubmitting ? <Loader2 {...ICON} className="animate-spin" /> : <Check {...ICON} />}
                    Enregistrer mes coordonnées
                </Button>
            </div>
        </form>
    );
}

const CHAMPS_MOT_DE_PASSE = ['password', 'password_confirmation'] as const;

function FormulaireMotDePasse({ regles, url }: { regles: ReglesMotDePasse; url: string }) {
    const schema = useMemo(() => motDePasseSchema(regles), [regles]);
    const actives = useMemo(() => reglesActives(regles), [regles]);
    const {
        register,
        handleSubmit,
        setError,
        reset,
        watch,
        formState: { errors, isSubmitting },
    } = useForm<MotDePasse>({ resolver: zodResolver(schema), defaultValues: { password: '', password_confirmation: '' }, mode: 'onTouched' });
    const saisie = watch('password');

    const envoyerMotDePasse = handleSubmit((donnees) =>
        envoyer<MotDePasse>(url, donnees, {
            method: 'put',
            setError,
            champs: CHAMPS_MOT_DE_PASSE,
            errorBag: 'passwordChange',
            onSuccess: () => reset(),
        }),
    );

    return (
        <form onSubmit={envoyerMotDePasse} noValidate className="flex flex-col gap-4">
            <Field id="password" label="Nouveau mot de passe" required error={errors.password?.message}>
                <ChampMotDePasse id="password" invalid={!!errors.password} describedBy="password-rules" {...register('password')} />
            </Field>
            <ul id="password-rules" className="-mt-2 grid gap-1.5 text-sm" aria-label="Règles du mot de passe">
                {actives.map((regle) => {
                    const ok = regle.test(saisie);

                    return (
                        <li key={regle.cle} className={cn('flex items-center gap-2', ok ? 'text-primary' : 'text-muted')}>
                            {ok ? <CircleCheck {...ICON} size={16} /> : <Circle {...ICON} size={16} />}
                            {regle.libelle}
                            <span className="sr-only">{ok ? ' : respectée' : ' : non respectée'}</span>
                        </li>
                    );
                })}
            </ul>
            <Field id="password_confirmation" label="Confirmer le nouveau mot de passe" required error={errors.password_confirmation?.message}>
                <ChampMotDePasse id="password_confirmation" invalid={!!errors.password_confirmation} {...register('password_confirmation')} />
            </Field>
            <Notice icone={<ShieldCheck {...ICON} />}>Choisissez un mot de passe que vous n’utilisez sur aucun autre site et ne le communiquez à personne.</Notice>
            <div className="flex justify-end pt-1">
                <Button type="submit" disabled={isSubmitting} className="w-full font-medium sm:w-auto">
                    {isSubmitting ? <Loader2 {...ICON} className="animate-spin" /> : <KeyRound {...ICON} />}
                    Modifier mon mot de passe
                </Button>
            </div>
        </form>
    );
}

type ChampMotDePasseProps = Omit<ComponentProps<typeof Input>, 'type'> & { describedBy?: string };

/** Champ mot de passe avec bouton afficher / masquer. */
function ChampMotDePasse({ id, invalid, describedBy, ...props }: ChampMotDePasseProps) {
    const [visible, setVisible] = useState(false);
    const decrit = [invalid ? `${id}-error` : null, describedBy].filter(Boolean).join(' ') || undefined;

    return (
        <Input
            id={id}
            type={visible ? 'text' : 'password'}
            autoComplete="new-password"
            invalid={invalid}
            aria-describedby={decrit}
            trailing={
                <button
                    type="button"
                    onClick={() => setVisible((v) => !v)}
                    aria-label={visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
                    aria-pressed={visible}
                    className="grid size-9 cursor-pointer place-items-center rounded-brand text-muted hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                >
                    {visible ? <EyeOff {...ICON} /> : <Eye {...ICON} />}
                </button>
            }
            {...props}
        />
    );
}
