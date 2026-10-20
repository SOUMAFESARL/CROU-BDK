import { Head, usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { SharedProps } from '@/types/global';

type AuthLayoutProps = {
    title: string;
    subtitle: ReactNode;
    logoUrl: string;
    backgroundUrl: string;
    year: number;
    children: ReactNode;
};

/** Cadre commun des pages d'authentification : photo plein écran, message de marque et carte du formulaire. */
export function AuthLayout({ title, subtitle, logoUrl, backgroundUrl, year, children }: AuthLayoutProps) {
    // Nom court et nom complet de Configuration › Général.
    const { systeme } = usePage<SharedProps>().props;

    return (
        <>
            <Head title={title} />
            <div className="relative isolate min-h-dvh overflow-hidden bg-primary">
                {/* Image de fond plein écran */}
                <img src={backgroundUrl} alt="" aria-hidden className="absolute inset-0 -z-20 size-full object-cover" />
                <div aria-hidden className="absolute inset-0 -z-10 bg-linear-to-br from-primary/35 via-primary/10 to-secondary/30" />
                {/* Dégradé localisé derrière le texte de marque : lisibilité sans assombrir le reste de la photo */}
                <div aria-hidden className="absolute inset-y-0 left-0 -z-10 hidden w-3/5 bg-linear-to-r from-black/65 via-black/35 to-transparent lg:block" />

                <div className="mx-auto flex min-h-dvh w-full max-w-7xl items-center justify-center px-4 py-10 sm:px-6 lg:justify-between lg:px-12">
                    {/* Message de marque, visible sur grand écran uniquement */}
                    <section className="hidden max-w-lg text-white lg:block">
                        <span className="mb-5 block h-1 w-12 rounded-full bg-accent" />
                        <h1 className="text-4xl leading-tight font-bold drop-shadow-lg xl:text-5xl">{systeme.nomComplet}</h1>
                        <p className="mt-4 text-lg text-white/90 drop-shadow-md">Plateforme intégrée de gestion des services universitaires.</p>
                        <p className="mt-10 text-sm text-white/70">© {year} {systeme.nom}</p>
                    </section>

                    <main className="login-card-enter w-full max-w-md">
                        <div className="rounded-brand border border-white/60 bg-surface/95 p-6 shadow-2xl ring-1 shadow-black/30 ring-black/5 backdrop-blur-md sm:p-8">
                            <div className="flex flex-col items-center text-center">
                                <img src={logoUrl} alt={`Logo ${systeme.nom}`} className="size-20 rounded-full bg-white object-contain p-1.5 shadow-md ring-2 ring-soft" />
                                <h2 className="mt-4 text-2xl font-bold text-gray-900">{title}</h2>
                                <p className="mt-1 text-sm text-muted">{subtitle}</p>
                                <span aria-hidden className="mt-5 block h-px w-24 bg-linear-to-r from-transparent via-secondary to-transparent" />
                            </div>

                            {children}
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}

/** Bandeau de message affiché en haut du formulaire (erreur générale ou confirmation). */
export function AuthAlert({ variant, children }: { variant: 'error' | 'success'; children: ReactNode }) {
    const Icon = variant === 'error' ? AlertCircle : CheckCircle2;
    return (
        <div
            role={variant === 'error' ? 'alert' : 'status'}
            className={cn(
                'mt-6 flex gap-2 rounded-brand border p-3 text-sm',
                variant === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-secondary/40 bg-soft text-primary',
            )}
        >
            <Icon className="mt-0.5 size-4 shrink-0" />
            <span>{children}</span>
        </div>
    );
}
