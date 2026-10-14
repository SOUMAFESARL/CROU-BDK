import { usePage } from '@inertiajs/react';
import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowLeft, Eye, EyeOff, KeyRound, Loader2, Lock, Mail } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { AuthLayout } from '@/components/auth/auth-layout';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import type { SharedProps } from '@/types/global';

type ResetPasswordProps = {
    submitUrl: string;
    loginUrl: string;
    csrfToken: string;
    token: string;
    email: string;
    passwordHint: string;
    logoUrl: string;
    backgroundUrl: string;
    year: number;
};

// Les règles de robustesse (longueur, majuscules, chiffres…) sont vérifiées par le serveur selon le paramétrage.
const schema = z
    .object({
        email: z.string().trim().min(1, "L'adresse e-mail est obligatoire.").pipe(z.email('Adresse e-mail invalide.')),
        password: z.string().min(1, 'Renseignez le nouveau mot de passe.'),
        password_confirmation: z.string().min(1, 'Confirmez le nouveau mot de passe.'),
    })
    .refine((values) => values.password === values.password_confirmation, {
        path: ['password_confirmation'],
        message: 'La confirmation ne correspond pas au nouveau mot de passe.',
    });

type ResetValues = z.infer<typeof schema>;

export default function ResetPassword({ submitUrl, loginUrl, csrfToken, token, email, passwordHint, logoUrl, backgroundUrl, year }: ResetPasswordProps) {
    const { errors: serverErrors } = usePage<SharedProps>().props;
    const formRef = useRef<HTMLFormElement>(null);
    const [showPassword, setShowPassword] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const {
        register,
        handleSubmit,
        setError,
        setFocus,
        formState: { errors },
    } = useForm<ResetValues>({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues: { email, password: '', password_confirmation: '' },
    });

    useEffect(() => {
        if (serverErrors.email) setError('email', { message: serverErrors.email });
        if (serverErrors.password) setError('password', { message: serverErrors.password });
    }, [serverErrors, setError]);

    useEffect(() => {
        setFocus(email ? 'password' : 'email');
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const submitNative = () => {
        setSubmitting(true);
        formRef.current?.submit();
    };

    const toggle = (
        <Button
            variant="ghost"
            size="icon"
            onClick={() => setShowPassword((v) => !v)}
            aria-label={showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
        >
            {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
        </Button>
    );

    return (
        <AuthLayout
            title="Nouveau mot de passe"
            subtitle="Choisissez le nouveau mot de passe de votre espace étudiant."
            logoUrl={logoUrl}
            backgroundUrl={backgroundUrl}
            year={year}
        >
            <form
                ref={formRef}
                method="POST"
                action={submitUrl}
                onSubmit={(event) => void handleSubmit(submitNative, () => setSubmitting(false))(event)}
                noValidate
                className="mt-6 space-y-5"
            >
                <input type="hidden" name="_token" value={csrfToken} />
                <input type="hidden" name="token" value={token} />

                <Field id="email" label="Adresse e-mail" error={errors.email?.message} required>
                    <Input
                        id="email"
                        type="email"
                        autoComplete="username"
                        icon={<Mail />}
                        invalid={!!errors.email}
                        aria-describedby={errors.email ? 'email-error' : undefined}
                        {...register('email')}
                    />
                </Field>

                <Field id="password" label="Nouveau mot de passe" error={errors.password?.message} hint={passwordHint} required>
                    <Input
                        id="password"
                        type={showPassword ? 'text' : 'password'}
                        autoComplete="new-password"
                        icon={<Lock />}
                        invalid={!!errors.password}
                        aria-describedby={errors.password ? 'password-error' : 'password-hint'}
                        trailing={toggle}
                        {...register('password')}
                    />
                </Field>

                <Field id="password_confirmation" label="Confirmer le mot de passe" error={errors.password_confirmation?.message} required>
                    <Input
                        id="password_confirmation"
                        type={showPassword ? 'text' : 'password'}
                        autoComplete="new-password"
                        icon={<Lock />}
                        invalid={!!errors.password_confirmation}
                        aria-describedby={errors.password_confirmation ? 'password_confirmation-error' : undefined}
                        {...register('password_confirmation')}
                    />
                </Field>

                <Button type="submit" variant="accent" className="w-full" disabled={submitting}>
                    {submitting ? <Loader2 className="size-4 animate-spin" /> : <KeyRound className="size-4" />}
                    {submitting ? 'Enregistrement…' : 'Enregistrer le mot de passe'}
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
