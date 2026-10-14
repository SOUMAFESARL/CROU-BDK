import { Link, router, usePage } from '@inertiajs/react';
import { zodResolver } from '@hookform/resolvers/zod';
import { CircleAlert, Eye, EyeOff, Loader2, Lock, Mail, RotateCcw, Save, User, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { Button, buttonVariants } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { SharedProps } from '@/types/global';

export type Option = { id: string; name: string };

export type PasswordPolicy = {
    min: number;
    mixedCase: boolean;
    numbers: boolean;
    symbols: boolean;
};

export type UserFormValues = {
    genre_id: string;
    name: string;
    telephone_principal: string;
    email: string;
    role_id: string;
    password: string;
};

type UserFormProps = {
    /** « edit » : mot de passe facultatif (vide = inchangé) et envoi en PUT. */
    mode: 'create' | 'edit';
    genres: Option[];
    roles: Option[];
    canAssignRole: boolean;
    telephonePrefixes: string[];
    passwordPolicy: PasswordPolicy;
    submitUrl: string;
    cancelUrl: string;
    initialValues?: Partial<UserFormValues>;
};

const FIELDS = ['genre_id', 'name', 'telephone_principal', 'email', 'role_id', 'password'] as const;

/** Garde 10 chiffres au plus et les affiche par paires : « 0700000000 » → « 07 00 00 00 00 ». */
export function formatTelephoneCI(value: string) {
    return (value.replace(/\D/g, '').slice(0, 10).match(/.{1,2}/g) ?? []).join(' ');
}

const passwordRules = (policy: PasswordPolicy) =>
    z
        .string()
        .min(policy.min, `Le mot de passe doit contenir au moins ${policy.min} caractères.`)
        .refine((value) => !policy.mixedCase || (/[a-z]/.test(value) && /[A-Z]/.test(value)), 'Le mot de passe doit contenir une majuscule et une minuscule.')
        .refine((value) => !policy.numbers || /\d/.test(value), 'Le mot de passe doit contenir au moins un chiffre.')
        .refine((value) => !policy.symbols || /[^A-Za-z0-9]/.test(value), 'Le mot de passe doit contenir au moins un caractère spécial.');

const buildSchema = (prefixes: string[], policy: PasswordPolicy, passwordRequired: boolean) =>
    z.object({
        genre_id: z.string().min(1, 'La civilité est obligatoire.'),
        name: z.string().trim().min(1, 'Le nom est obligatoire.').max(255, 'Le nom ne doit pas dépasser 255 caractères.'),
        telephone_principal: z
            .string()
            .transform((value) => value.replace(/\D/g, ''))
            .pipe(
                z
                    .string()
                    .min(1, 'Le téléphone principal est obligatoire.')
                    .length(10, 'Le téléphone doit contenir exactement 10 chiffres.')
                    .refine(
                        (digits) => prefixes.includes(digits.slice(0, 2)),
                        `Le téléphone doit être un numéro ivoirien commençant par ${prefixes.join(', ')}.`,
                    ),
            ),
        email: z.string().trim().min(1, "L'adresse email est obligatoire.").pipe(z.email("L'adresse email n'est pas valide.")),
        role_id: z.string().min(1, 'Le rôle est obligatoire.'),
        password: passwordRequired
            ? z.string().min(1, 'Le mot de passe est obligatoire.').pipe(passwordRules(policy))
            : z.union([z.literal(''), passwordRules(policy)]),
    });

type FormInput = z.input<ReturnType<typeof buildSchema>>;
type FormOutput = z.output<ReturnType<typeof buildSchema>>;

const DEFAULTS: FormInput = { genre_id: '', name: '', telephone_principal: '', email: '', role_id: '', password: '' };

function passwordHint(policy: PasswordPolicy, optional: boolean) {
    const rules = [`${policy.min} caractères minimum`];
    if (policy.mixedCase) rules.push('une majuscule et une minuscule');
    if (policy.numbers) rules.push('un chiffre');
    if (policy.symbols) rules.push('un caractère spécial');
    const hint = `${rules.join(', ')}.`;
    return optional ? `Laissez vide pour conserver le mot de passe actuel. Sinon : ${hint}` : hint;
}

export function UserForm({ mode, genres, roles, canAssignRole, telephonePrefixes, passwordPolicy, submitUrl, cancelUrl, initialValues }: UserFormProps) {
    const { errors: serverErrors } = usePage<SharedProps>().props;
    const [showPassword, setShowPassword] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const isEdit = mode === 'edit';

    const defaultValues = useMemo<FormInput>(
        () => ({
            ...DEFAULTS,
            ...initialValues,
            telephone_principal: formatTelephoneCI(initialValues?.telephone_principal ?? ''),
            password: '',
        }),
        [initialValues],
    );
    const schema = useMemo(() => buildSchema(telephonePrefixes, passwordPolicy, !isEdit), [telephonePrefixes, passwordPolicy, isEdit]);
    const {
        register,
        control,
        handleSubmit,
        setError,
        reset,
        formState: { errors, isDirty },
    } = useForm<FormInput, unknown, FormOutput>({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues,
    });

    // Erreurs renvoyées par la validation Laravel (UserRequest), ex. e-mail déjà utilisé.
    useEffect(() => {
        FIELDS.forEach((field) => {
            if (serverErrors[field]) setError(field, { message: serverErrors[field] });
        });
    }, [serverErrors, setError]);

    const onSubmit = (values: FormOutput) => {
        const options = {
            preserveScroll: true,
            onStart: () => setSubmitting(true),
            onFinish: () => setSubmitting(false),
        };
        if (isEdit) {
            router.put(submitUrl, values, options);
        } else {
            router.post(submitUrl, values, options);
        }
    };

    const describedBy = (field: (typeof FIELDS)[number], withHint = false) =>
        errors[field] ? `${field}-error` : withHint ? `${field}-hint` : undefined;

    const unknownErrors = Object.entries(serverErrors).filter(([key]) => !(FIELDS as readonly string[]).includes(key));
    // Création : attribuer un rôle est obligatoire. Édition : le rôle actuel peut être conservé sans ce droit.
    const blocked = !canAssignRole && !isEdit;

    return (
        <form onSubmit={handleSubmit(onSubmit)} noValidate className="rounded-brand border bg-white">
            <div className="space-y-6 p-4 md:p-6">
                {!canAssignRole && (
                    <p role="alert" className="flex items-center gap-2 rounded-brand border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <CircleAlert className="size-4 shrink-0" aria-hidden />
                        {isEdit
                            ? "Vous n'avez pas le droit d'attribuer un rôle : le rôle actuel sera conservé."
                            : "Vous n'avez pas le droit d'attribuer un rôle : la création d'un compte vous est donc impossible."}
                    </p>
                )}
                {unknownErrors.length > 0 && (
                    <p role="alert" className="flex items-center gap-2 rounded-brand border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <CircleAlert className="size-4 shrink-0" aria-hidden />
                        {unknownErrors[0][1]}
                    </p>
                )}

                <div className="grid grid-cols-1 gap-x-6 gap-y-5 lg:grid-cols-2">
                    <Field id="genre_id" label="Civilité" required error={errors.genre_id?.message}>
                        <Controller
                            control={control}
                            name="genre_id"
                            render={({ field }) => (
                                <Select name={field.name} value={field.value} onValueChange={field.onChange} onOpenChange={(open) => !open && field.onBlur()}>
                                    <SelectTrigger id="genre_id" ref={field.ref} invalid={!!errors.genre_id} aria-describedby={describedBy('genre_id')}>
                                        <SelectValue placeholder="Sélectionnez une civilité" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {genres.map((genre) => (
                                            <SelectItem key={genre.id} value={genre.id}>
                                                {genre.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                        />
                    </Field>

                    <Field id="name" label="Nom complet" required error={errors.name?.message}>
                        <Input
                            id="name"
                            autoComplete="name"
                            placeholder="Ex : Kouassi Jean"
                            icon={<User />}
                            invalid={!!errors.name}
                            aria-describedby={describedBy('name')}
                            {...register('name')}
                        />
                    </Field>

                    <Field id="telephone_principal" label="Téléphone" required error={errors.telephone_principal?.message}>
                        <Controller
                            control={control}
                            name="telephone_principal"
                            render={({ field }) => (
                                <Input
                                    id="telephone_principal"
                                    ref={field.ref}
                                    name={field.name}
                                    value={field.value}
                                    onBlur={field.onBlur}
                                    onChange={(event) => field.onChange(formatTelephoneCI(event.target.value))}
                                    type="tel"
                                    inputMode="numeric"
                                    autoComplete="tel-national"
                                    maxLength={14}
                                    placeholder="07 00 00 00 00"
                                    icon={<span className="text-sm font-medium text-gray-700">+225</span>}
                                    className="pl-14 tracking-wide tabular-nums"
                                    invalid={!!errors.telephone_principal}
                                    aria-describedby={describedBy('telephone_principal')}
                                />
                            )}
                        />
                    </Field>

                    <Field id="email" label="Adresse e-mail" required error={errors.email?.message}>
                        <Input
                            id="email"
                            type="email"
                            autoComplete="email"
                            placeholder="Ex : jean.kouassi@exemple.ci"
                            icon={<Mail />}
                            invalid={!!errors.email}
                            aria-describedby={describedBy('email')}
                            {...register('email')}
                        />
                    </Field>

                    <Field id="role_id" label="Rôle" required error={errors.role_id?.message}>
                        <Controller
                            control={control}
                            name="role_id"
                            render={({ field }) => (
                                <Select
                                    name={field.name}
                                    value={field.value}
                                    onValueChange={field.onChange}
                                    onOpenChange={(open) => !open && field.onBlur()}
                                    disabled={!canAssignRole}
                                >
                                    <SelectTrigger id="role_id" ref={field.ref} invalid={!!errors.role_id} aria-describedby={describedBy('role_id')}>
                                        <SelectValue placeholder="Sélectionnez un rôle" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((role) => (
                                            <SelectItem key={role.id} value={role.id}>
                                                {role.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                        />
                    </Field>

                    <Field
                        id="password"
                        label={isEdit ? 'Nouveau mot de passe' : 'Mot de passe'}
                        required={!isEdit}
                        error={errors.password?.message}
                        hint={passwordHint(passwordPolicy, isEdit)}
                    >
                        <Input
                            id="password"
                            type={showPassword ? 'text' : 'password'}
                            autoComplete="new-password"
                            icon={<Lock />}
                            invalid={!!errors.password}
                            aria-describedby={describedBy('password', true)}
                            trailing={
                                <button
                                    type="button"
                                    onClick={() => setShowPassword((value) => !value)}
                                    aria-label={showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
                                    className="inline-flex size-9 cursor-pointer items-center justify-center rounded-brand text-muted transition-colors hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                                >
                                    {showPassword ? <EyeOff className="size-4" aria-hidden /> : <Eye className="size-4" aria-hidden />}
                                </button>
                            }
                            {...register('password')}
                        />
                    </Field>
                </div>
            </div>

            <div className="flex flex-col-reverse gap-2 border-t px-4 py-4 sm:flex-row sm:justify-end md:px-6">
                <Link href={cancelUrl} className={cn(buttonVariants({ variant: 'outline' }), 'h-10')}>
                    <X className="size-4" aria-hidden />
                    Annuler
                </Link>
                {isEdit && (
                    <Button type="button" variant="outline" className="h-10" disabled={!isDirty || submitting} onClick={() => reset(defaultValues)}>
                        <RotateCcw className="size-4" aria-hidden />
                        Réinitialiser
                    </Button>
                )}
                <Button type="submit" className="h-10" disabled={submitting || blocked}>
                    {submitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Save className="size-4" aria-hidden />}
                    {submitting ? 'Enregistrement…' : isEdit ? 'Enregistrer les modifications' : 'Enregistrer'}
                </Button>
            </div>
        </form>
    );
}
