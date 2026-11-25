import { Link, usePage } from '@inertiajs/react';
import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, Hash, Loader2, MessageSquareWarning, Search } from 'lucide-react';
import { useForm, useWatch } from 'react-hook-form';
import { AuthAlert, AuthLayout } from '@/components/auth/auth-layout';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { InputSuggestions } from '@/components/ui/input-suggestions';
import { useSaisiesMemorisees } from '@/hooks/use-saisies-memorisees';
import { envoyer } from '@/lib/inertia-form';
import { cn } from '@/lib/utils';
import { resultatSchema, type SaisieResultat } from '@/schemas/resultat-admission';
import type { SharedProps } from '@/types/global';

type TypeResultat = 'admis' | 'recevable_non_admis' | 'non_recevable';

type ResultatProps = {
    /** Campagne de la dernière publication ; null tant qu'aucun résultat n'est publié. */
    campagne: string | null;
    /** Résultat de la demande recherchée (`ResultatsAdmission::consulter`) ; null : formulaire. */
    resultat: { type: TypeResultat; nom: string; campagne: string; volet: string; titre: string; message: string } | null;
    /** `reclamation` : présent pour une demande non admise, pendant une période de réclamation ouverte. */
    liens: { consulter: string; formulaire: string; connexion: string; reclamation: string | null };
    logoUrl: string;
    backgroundUrl: string;
    year: number;
};

const STYLES: Record<TypeResultat, string> = {
    admis: 'border-secondary/40 bg-soft text-primary',
    recevable_non_admis: 'border-accent/50 bg-accent/10 text-amber-800',
    non_recevable: 'border-red-200 bg-red-50 text-red-700',
};

/** Page publique « Résultats d'admission » : consultation par numéro de table au bac ou matricule. */
export default function Resultat({ campagne, resultat, liens, logoUrl, backgroundUrl, year }: ResultatProps) {
    const { flash } = usePage<SharedProps>().props;

    return (
        <AuthLayout
            title="Résultats d’admission"
            subtitle={resultat?.campagne || campagne || 'Demandes d’hébergement'}
            logoUrl={logoUrl}
            backgroundUrl={backgroundUrl}
            year={year}
        >
            {resultat ? (
                <FicheResultat resultat={resultat} liens={liens} />
            ) : !campagne ? (
                <AuthAlert variant="error">Aucun résultat n’est publié pour le moment.</AuthAlert>
            ) : (
                <>
                    {flash.error && <AuthAlert variant="error">{flash.error}</AuthAlert>}
                    <Formulaire url={liens.consulter} />
                </>
            )}

            <div className="mt-6 text-center text-sm">
                <a href={liens.connexion} className="inline-flex items-center gap-1.5 font-semibold text-primary hover:underline">
                    <ArrowLeft className="size-4" />
                    Retour à la connexion
                </a>
            </div>
        </AuthLayout>
    );
}

/** Numéros déjà consultés depuis ce navigateur, proposés à la saisie. */
const CLE_NUMEROS = 'resultats.numeros';

function Formulaire({ url }: { url: string }) {
    const {
        register,
        handleSubmit,
        setError,
        setValue,
        control,
        formState: { errors, isSubmitting },
    } = useForm<SaisieResultat>({ resolver: zodResolver(resultatSchema), mode: 'onTouched', defaultValues: { numero: '' } });
    const numero = useWatch({ control, name: 'numero' });
    const { saisies, memoriser, oublier } = useSaisiesMemorisees(CLE_NUMEROS);

    const consulter = handleSubmit(async (valeurs) => {
        memoriser(valeurs.numero);
        await envoyer<SaisieResultat>(url, valeurs, { setError, champs: ['numero'] });
    });

    return (
        <form onSubmit={(event) => void consulter(event)} noValidate className="mt-5 space-y-5">
            <Field
                id="numero"
                label="Numéro de table au bac / matricule"
                required
                error={errors.numero?.message}
                hint="L’un ou l’autre, comme dans votre demande d’hébergement."
            >
                <InputSuggestions
                    id="numero"
                    valeur={numero}
                    suggestions={saisies}
                    libelleListe="Numéros déjà consultés"
                    onChoisir={(valeur) => setValue('numero', valeur, { shouldDirty: true, shouldValidate: true })}
                    onOublier={oublier}
                    maxLength={50}
                    placeholder="Ex. : 2501234567"
                    className="uppercase placeholder:normal-case"
                    icon={<Hash />}
                    invalid={!!errors.numero}
                    aria-describedby={errors.numero ? 'numero-error' : undefined}
                    {...register('numero')}
                />
            </Field>
            <Button type="submit" variant="accent" className="w-full" disabled={isSubmitting}>
                {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Search className="size-4" aria-hidden />}
                {isSubmitting ? 'Recherche…' : 'Consulter mon résultat'}
            </Button>
        </form>
    );
}

function FicheResultat({ resultat, liens }: { resultat: NonNullable<ResultatProps['resultat']>; liens: ResultatProps['liens'] }) {
    const lignes: [string, string][] = [
        ['Nom et prénoms', resultat.nom],
        ['Type de demande', resultat.volet],
    ];

    return (
        <div className="mt-5 space-y-5">
            <dl className="divide-y rounded-brand border bg-white">
                {lignes.map(([libelle, valeur]) => (
                    <div key={libelle} className="flex flex-wrap justify-between gap-x-4 gap-y-0.5 px-3 py-2.5 text-sm">
                        <dt className="text-muted">{libelle}</dt>
                        <dd className="font-medium text-gray-900">{valeur || '—'}</dd>
                    </div>
                ))}
            </dl>

            <div role="status" className={cn('rounded-brand border p-4', STYLES[resultat.type])}>
                <p className="font-semibold">{resultat.titre}</p>
                <p className="mt-1 text-sm">{resultat.message}</p>
            </div>

            {liens.reclamation && (
                <div className="rounded-brand border border-dashed border-secondary bg-soft px-4 py-3 text-center text-sm">
                    <p className="text-muted">Vous contestez ce résultat ?</p>
                    <a href={liens.reclamation} className="mt-1 inline-flex items-center gap-1.5 font-semibold text-primary hover:underline">
                        <MessageSquareWarning className="size-4" aria-hidden />
                        Faire une réclamation
                    </a>
                </div>
            )}

            <Link
                href={liens.formulaire}
                className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-brand border bg-white text-sm font-medium text-gray-900 hover:bg-surface focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
            >
                <Search className="size-4" aria-hidden />
                Consulter un autre résultat
            </Link>
        </div>
    );
}
