import { usePage } from '@inertiajs/react';
import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, ChevronLeft, ChevronRight, Hash, Loader2, Send, User } from 'lucide-react';
import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { toast } from 'sonner';
import { AuthAlert, AuthLayout } from '@/components/auth/auth-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { envoyer } from '@/lib/inertia-form';
import { dateFr } from '@/lib/text';
import { CHAMPS_ETAPE_RECLAMATION, reclamationSchema, type SaisieReclamation } from '@/schemas/reclamation';
import type { SharedProps } from '@/types/global';

type ReclamationProps = {
    /** Période ouverte ; null si les réclamations sont fermées. */
    periode: { campagne: string; dateFin: string } | null;
    /** Types de demande ouverts par la campagne. */
    types: { value: string; label: string }[];
    liens: { envoyer: string; connexion: string };
    logoUrl: string;
    backgroundUrl: string;
    year: number;
};

/** Page publique « Faire une réclamation », ouverte depuis la page de connexion pendant une période de réclamation. */
export default function Reclamation({ periode, types, liens, logoUrl, backgroundUrl, year }: ReclamationProps) {
    const { flash } = usePage<SharedProps>().props;

    return (
        <AuthLayout
            title="Faire une réclamation"
            subtitle={periode ? [periode.campagne, `jusqu’au ${dateFr(periode.dateFin)}`].filter(Boolean).join(' · ') : 'Réclamation sur une demande d’hébergement'}
            logoUrl={logoUrl}
            backgroundUrl={backgroundUrl}
            year={year}
        >
            {flash.success ? (
                <AuthAlert variant="success">{flash.success}</AuthAlert>
            ) : !periode ? (
                <AuthAlert variant="error">Les réclamations ne sont pas ouvertes pour le moment.</AuthAlert>
            ) : (
                <>
                    {flash.error && <AuthAlert variant="error">{flash.error}</AuthAlert>}
                    <Formulaire types={types} url={liens.envoyer} />
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

function Formulaire({ types, url }: { types: ReclamationProps['types']; url: string }) {
    const [etape, setEtape] = useState(0);
    const {
        register,
        control,
        handleSubmit,
        trigger,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<SaisieReclamation>({
        resolver: zodResolver(reclamationSchema),
        mode: 'onTouched',
        // Un seul type ouvert par la campagne : il est déjà choisi.
        defaultValues: { nom_prenoms: '', num_bac_matricule: '', type_demande: types.length === 1 ? types[0].value : '', motif: '' },
    });

    const suivant = async () => {
        if (!(await trigger(CHAMPS_ETAPE_RECLAMATION[0]))) return;
        setEtape(1);
    };

    const envoi = handleSubmit(
        async (valeurs) => {
            await envoyer<SaisieReclamation>(url, valeurs, {
                onError: (erreurs) => {
                    // Erreurs serveur sous le champ concerné, puis retour à l'étape du premier champ fautif.
                    let fautive = -1;
                    Object.entries(erreurs).forEach(([cle, message]) => {
                        const index = CHAMPS_ETAPE_RECLAMATION.findIndex((champs) => (champs as string[]).includes(cle));
                        if (index < 0) {
                            toast.error(message);
                            return;
                        }
                        setError(cle as keyof SaisieReclamation, { type: 'server', message });
                        fautive = fautive < 0 ? index : Math.min(fautive, index);
                    });
                    if (fautive >= 0) setEtape(fautive);
                },
            });
        },
        (erreurs) => setEtape(CHAMPS_ETAPE_RECLAMATION.findIndex((champs) => champs.some((champ) => erreurs[champ]))),
    );

    return (
        <form
            onSubmit={(event) => {
                // Entrée à la première étape : étape suivante, pas d'envoi.
                if (etape === 0) {
                    event.preventDefault();
                    void suivant();
                    return;
                }
                void envoi(event);
            }}
            noValidate
            className="mt-5 space-y-5"
        >
            {etape === 0 ? (
                <>
                    <Field id="nom_prenoms" label="Nom et prénoms" required error={errors.nom_prenoms?.message} hint="Comme dans votre demande d’hébergement.">
                        <Input
                            id="nom_prenoms"
                            autoComplete="name"
                            maxLength={150}
                            placeholder="Ex. : KONAN Aya Marie"
                            icon={<User />}
                            invalid={!!errors.nom_prenoms}
                            aria-describedby={errors.nom_prenoms ? 'nom_prenoms-error' : undefined}
                            {...register('nom_prenoms')}
                        />
                    </Field>
                    <Field
                        id="num_bac_matricule"
                        label="Numéro de table au bac / matricule"
                        required
                        error={errors.num_bac_matricule?.message}
                        hint="L’un ou l’autre, comme dans votre demande d’hébergement."
                    >
                        <Input
                            id="num_bac_matricule"
                            autoComplete="off"
                            maxLength={50}
                            placeholder="Ex. : 2501234567"
                            className="uppercase"
                            icon={<Hash />}
                            invalid={!!errors.num_bac_matricule}
                            aria-describedby={errors.num_bac_matricule ? 'num_bac_matricule-error' : undefined}
                            {...register('num_bac_matricule')}
                        />
                    </Field>
                    <Button type="submit" variant="accent" className="w-full">
                        Suivant
                        <ChevronRight {...ICON} aria-hidden />
                    </Button>
                </>
            ) : (
                <>
                    <Field id="type_demande" label="Type de demande" required error={errors.type_demande?.message}>
                        <Controller
                            control={control}
                            name="type_demande"
                            render={({ field }) => (
                                <Select value={field.value} onValueChange={field.onChange}>
                                    <SelectTrigger id="type_demande" aria-invalid={!!errors.type_demande || undefined}>
                                        <SelectValue placeholder="Choisir le type de demande" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {types.map((type) => (
                                            <SelectItem key={type.value} value={type.value}>
                                                {type.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                        />
                    </Field>
                    <Field id="motif" label="Motif" required error={errors.motif?.message} hint="2 000 caractères maximum.">
                        <Textarea
                            id="motif"
                            rows={5}
                            maxLength={2000}
                            placeholder="Expliquez votre réclamation"
                            invalid={!!errors.motif}
                            aria-describedby={errors.motif ? 'motif-error' : undefined}
                            {...register('motif')}
                        />
                    </Field>
                    <div className="flex gap-2">
                        <Button variant="outline" className="h-11" onClick={() => setEtape(0)} disabled={isSubmitting}>
                            <ChevronLeft {...ICON} aria-hidden />
                            Précédent
                        </Button>
                        <Button type="submit" variant="accent" className="flex-1" disabled={isSubmitting}>
                            {isSubmitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Send className="size-4" aria-hidden />}
                            {isSubmitting ? 'Envoi…' : 'Envoyer'}
                        </Button>
                    </div>
                </>
            )}
        </form>
    );
}
