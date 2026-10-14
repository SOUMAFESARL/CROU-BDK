import { usePage } from '@inertiajs/react';
import { zodResolver } from '@hookform/resolvers/zod';
import { Eye, EyeOff, KeyRound, Loader2, Lock, LogIn, Mail, RefreshCw } from 'lucide-react';
import { type ReactNode, useEffect, useMemo, useRef, useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';
import { AuthAlert, AuthLayout } from '@/components/auth/auth-layout';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { SharedProps } from '@/types/global';

export type LoginPageProps = {
    loginUrl: string;
    forgotUrl?: string | null;
    csrfToken: string;
    oldEmail: string | null;
    logoUrl: string;
    backgroundUrl: string;
    year: number;
};

type LoginPageOptions = LoginPageProps & {
    /** Phrase sous le titre « Connexion ». */
    subtitle: string;
    emailPlaceholder: string;
    /** Clé sessionStorage où l'e-mail saisi est conservé entre deux tentatives. */
    storageKey: string;
    buttonVariant?: 'primary' | 'accent';
    /** Contenu affiché sous le bouton de connexion (ex. lien d'inscription). */
    footer?: ReactNode;
};

const CAPTCHA_CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';

function generateCaptcha(length = 6) {
    return Array.from({ length }, () => CAPTCHA_CHARS[Math.floor(Math.random() * CAPTCHA_CHARS.length)]).join('');
}

function readStoredEmail(storageKey: string) {
    try {
        return sessionStorage.getItem(storageKey) ?? '';
    } catch {
        return '';
    }
}

const buildSchema = (captcha: string) =>
    z.object({
        email: z.string().trim().min(1, "L'adresse e-mail est obligatoire.").pipe(z.email('Adresse e-mail invalide.')),
        password: z.string().min(1, 'Le mot de passe est obligatoire.'),
        captcha: z
            .string()
            .min(1, 'Recopiez le code de vérification.')
            .refine((value) => value === captcha, 'Le code ne correspond pas. Respectez les majuscules.'),
    });

type LoginValues = z.infer<ReturnType<typeof buildSchema>>;

export function LoginPage({
    loginUrl,
    forgotUrl,
    csrfToken,
    oldEmail,
    logoUrl,
    backgroundUrl,
    year,
    subtitle,
    emailPlaceholder,
    storageKey,
    buttonVariant = 'primary',
    footer,
}: LoginPageOptions) {
    const { errors: serverErrors, flash } = usePage<SharedProps>().props;
    const formRef = useRef<HTMLFormElement>(null);
    const [captcha, setCaptcha] = useState(generateCaptcha);
    const [showPassword, setShowPassword] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const initialEmail = oldEmail || readStoredEmail(storageKey);

    const schema = useMemo(() => buildSchema(captcha), [captcha]);
    const {
        register,
        control,
        handleSubmit,
        setError,
        setFocus,
        resetField,
        getValues,
        formState: { errors },
    } = useForm<LoginValues>({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues: { email: initialEmail, password: '', captcha: '' },
    });

    // Le captcha n'apparaît qu'une fois l'e-mail et le mot de passe renseignés.
    const [email, password] = useWatch({
        control,
        name: ['email', 'password'],
    });
    const captchaVisible = email.trim() !== '' && password !== '';

    useEffect(() => {
        if (serverErrors.email) setError('email', { message: serverErrors.email });
        if (serverErrors.password) setError('password', { message: serverErrors.password });
    }, [serverErrors, setError]);

    useEffect(() => {
        // Après un échec, l'e-mail est déjà connu : on place le curseur sur le mot de passe.
        setFocus(initialEmail && !serverErrors.email ? 'password' : 'email');
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const refreshCaptcha = () => {
        setCaptcha(generateCaptcha());
        resetField('captcha');
    };

    // Soumission HTML classique : le contrôleur et ses redirections (2FA, tableau de bord Blade) restent inchangés.
    const submitNative = () => {
        setSubmitting(true);
        try {
            sessionStorage.setItem(storageKey, getValues('email').trim());
        } catch {
            /* stockage indisponible : sans conséquence */
        }
        formRef.current?.submit();
    };

    const generalError = serverErrors.error;

    return (
        <AuthLayout title="Connexion" subtitle={subtitle} logoUrl={logoUrl} backgroundUrl={backgroundUrl} year={year}>
            {generalError && <AuthAlert variant="error">{generalError}</AuthAlert>}
            {!generalError && flash.success && <AuthAlert variant="success">{flash.success}</AuthAlert>}

            <form
                ref={formRef}
                method="POST"
                action={loginUrl}
                onSubmit={(event) => void handleSubmit(submitNative, () => setSubmitting(false))(event)}
                noValidate
                className="mt-6 space-y-5"
            >
                <input type="hidden" name="_token" value={csrfToken} />

                <Field id="email" label="Adresse e-mail" error={errors.email?.message} required>
                    <Input
                        id="email"
                        type="email"
                        autoComplete="username"
                        placeholder={emailPlaceholder}
                        icon={<Mail />}
                        invalid={!!errors.email}
                        aria-describedby={errors.email ? 'email-error' : undefined}
                        {...register('email')}
                    />
                </Field>

                <Field id="password" label="Mot de passe" error={errors.password?.message} required>
                    <Input
                        id="password"
                        type={showPassword ? 'text' : 'password'}
                        autoComplete="current-password"
                        placeholder="Entrez votre mot de passe"
                        icon={<Lock />}
                        invalid={!!errors.password}
                        aria-describedby={errors.password ? 'password-error' : undefined}
                        trailing={
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => setShowPassword((v) => !v)}
                                aria-label={showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
                            >
                                {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                            </Button>
                        }
                        {...register('password')}
                    />
                </Field>
                {/* Hors du Field : le message d'erreur reste collé au champ, au-dessus du lien */}
                {forgotUrl && (
                    <div className="-mt-3.5 flex justify-end">
                        <a href={forgotUrl} className="text-xs font-medium text-primary hover:underline">
                            Mot de passe oublié ?
                        </a>
                    </div>
                )}

                {/* Captcha : déplié avec un zoom doux dès que les deux champs sont remplis */}
                <div
                    inert={!captchaVisible}
                    aria-hidden={!captchaVisible}
                    className={cn(
                        'grid transition-all duration-500 ease-out motion-reduce:transition-none',
                        captchaVisible ? 'grid-rows-[1fr] opacity-100' : '-mt-5 grid-rows-[0fr] opacity-0',
                    )}
                >
                    <div className="overflow-hidden">
                        <div
                            className={cn(
                                'origin-top p-0.5 transition-transform duration-500 ease-out motion-reduce:transition-none',
                                captchaVisible ? 'scale-100' : 'scale-90',
                            )}
                        >
                            <Field id="captcha" label="Code de vérification" error={errors.captcha?.message} required>
                                <div className="flex gap-2">
                                    <div
                                        aria-label={`Code à recopier : ${captcha.split('').join(' ')}`}
                                        className="flex h-11 flex-1 items-center justify-center rounded-brand border border-dashed border-secondary bg-soft font-mono text-lg font-bold tracking-[0.35em] text-primary select-none"
                                    >
                                        {captcha}
                                    </div>
                                    <Button variant="outline" className="h-11 w-11 px-0" onClick={refreshCaptcha} aria-label="Générer un nouveau code">
                                        <RefreshCw className="size-4" />
                                    </Button>
                                </div>
                                <Input
                                    id="captcha"
                                    autoComplete="off"
                                    placeholder="Recopiez le code ci-dessus"
                                    icon={<KeyRound />}
                                    invalid={!!errors.captcha}
                                    aria-describedby={errors.captcha ? 'captcha-error' : undefined}
                                    className="mt-2"
                                    {...register('captcha')}
                                />
                            </Field>
                        </div>
                    </div>
                </div>

                <Button type="submit" variant={buttonVariant} className="w-full" disabled={submitting}>
                    {submitting ? <Loader2 className="size-4 animate-spin" /> : <LogIn className="size-4" />}
                    {submitting ? 'Connexion…' : 'Se connecter'}
                </Button>
            </form>

            {footer && <div className="mt-6 text-center text-sm text-muted">{footer}</div>}
        </AuthLayout>
    );
}
