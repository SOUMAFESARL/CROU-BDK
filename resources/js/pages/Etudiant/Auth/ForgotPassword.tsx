import { usePage } from '@inertiajs/react';
import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, Loader2, Mail, Send } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { AuthAlert, AuthLayout } from '@/components/auth/auth-layout';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import type { SharedProps } from '@/types/global';

type ForgotPasswordProps = {
    submitUrl: string;
    loginUrl: string;
    csrfToken: string;
    oldEmail: string | null;
    status: string | null;
    logoUrl: string;
    backgroundUrl: string;
    year: number;
};

const schema = z.object({
    email: z.string().trim().min(1, "L'adresse e-mail est obligatoire.").pipe(z.email('Adresse e-mail invalide.')),
});

type ForgotValues = z.infer<typeof schema>;

export default function ForgotPassword({ submitUrl, loginUrl, csrfToken, oldEmail, status, logoUrl, backgroundUrl, year }: ForgotPasswordProps) {
    const { errors: serverErrors } = usePage<SharedProps>().props;
    const formRef = useRef<HTMLFormElement>(null);
    const [submitting, setSubmitting] = useState(false);

    const {
        register,
        handleSubmit,
        setError,
        setFocus,
        formState: { errors },
    } = useForm<ForgotValues>({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues: { email: oldEmail ?? '' },
    });

    useEffect(() => {
        if (serverErrors.email) setError('email', { message: serverErrors.email });
    }, [serverErrors, setError]);

    useEffect(() => {
        setFocus('email');
    }, [setFocus]);

    // Soumission HTML classique : le contrôleur redirige avec le message de confirmation en session.
    const submitNative = () => {
        setSubmitting(true);
        formRef.current?.submit();
    };

    return (
        <AuthLayout
            title="Mot de passe oublié"
            subtitle="Saisissez l'adresse e-mail de votre compte : nous vous enverrons un lien pour choisir un nouveau mot de passe."
            logoUrl={logoUrl}
            backgroundUrl={backgroundUrl}
            year={year}
        >
            {status && <AuthAlert variant="success">{status}</AuthAlert>}

            <form
                ref={formRef}
                method="POST"
                action={submitUrl}
                onSubmit={(event) => void handleSubmit(submitNative, () => setSubmitting(false))(event)}
                noValidate
                className="mt-6 space-y-5"
            >
                <input type="hidden" name="_token" value={csrfToken} />

                <Field id="email" label="Adresse e-mail" error={errors.email?.message} required>
                    <Input
                        id="email"
                        type="email"
                        autoComplete="email"
                        placeholder="etudiant@exemple.com"
                        icon={<Mail />}
                        invalid={!!errors.email}
                        aria-describedby={errors.email ? 'email-error' : undefined}
                        {...register('email')}
                    />
                </Field>

                <Button type="submit" variant="accent" className="w-full" disabled={submitting}>
                    {submitting ? <Loader2 className="size-4 animate-spin" /> : <Send className="size-4" />}
                    {submitting ? 'Envoi…' : 'Envoyer le lien'}
                </Button>
            </form>

            <div className="mt-6 text-center text-sm">
                <a href={loginUrl} className="inline-flex items-center gap-1.5 font-semibold text-primary hover:underline">
                    <ArrowLeft className="size-4" />
                    Retour à la connexion
                </a>
            </div>
        </AuthLayout>
    );
}
