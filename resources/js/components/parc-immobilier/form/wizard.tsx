import { Check, ChevronLeft, ChevronRight, Loader2, Save, type LucideIcon } from 'lucide-react';
import { useCallback, useState, type ReactNode } from 'react';
import { ICON } from '@/components/layout/app-sidebar';
import { Button, buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type WizardStep = { label: string; description: string; icon: LucideIcon };

/**
 * Étape courante + plus haute étape atteinte (on ne saute jamais une étape non validée).
 * `toutesAccessibles` (modification d'un élément existant) : toutes les étapes sont cliquables d'emblée ;
 * le formulaire valide quand même les étapes intermédiaires avant d'avancer (`allerA`).
 */
export function useWizard(count: number, { toutesAccessibles = false }: { toutesAccessibles?: boolean } = {}) {
    const [current, setCurrent] = useState(0);
    const [reached, setReached] = useState(toutesAccessibles ? count - 1 : 0);

    const goTo = useCallback(
        (index: number) => {
            const cible = Math.max(0, Math.min(index, count - 1));
            setCurrent(cible);
            setReached((max) => Math.max(max, cible));
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        [count],
    );

    return {
        current,
        reached,
        isFirst: current === 0,
        isLast: current === count - 1,
        goTo,
        next: () => goTo(current + 1),
        previous: () => goTo(current - 1),
    };
}

type WizardProps = {
    steps: WizardStep[];
    current: number;
    reached: number;
    onStepClick: (index: number) => void;
    onPrevious: () => void;
    onNext: () => void;
    cancelUrl: string;
    submitting?: boolean;
    submitLabel: string;
    /** Désactive le bouton final (ex. droit manquant). */
    submitDisabled?: boolean;
    children: ReactNode;
};

/**
 * Formulaire en plusieurs étapes : liste des étapes à gauche (écran large) ou barre de progression (mobile),
 * contenu de l'étape, puis Précédent / Suivant. « Suivant » valide l'étape avant d'avancer (`onNext`).
 */
export function Wizard({
    steps,
    current,
    reached,
    onStepClick,
    onPrevious,
    onNext,
    cancelUrl,
    submitting = false,
    submitLabel,
    submitDisabled = false,
    children,
}: WizardProps) {
    const step = steps[current];
    const isLast = current === steps.length - 1;
    const progression = Math.round(((current + 1) / steps.length) * 100);

    return (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-[240px_minmax(0,1fr)]">
            {/* Écran large : étapes cliquables (seulement celles déjà atteintes). */}
            <nav aria-label="Étapes du formulaire" className="hidden lg:block">
                <ol className="sticky top-24 space-y-1">
                    {steps.map((item, index) => {
                        // Étape validée : déjà dépassée au moins une fois.
                        const done = index !== current && index < reached;
                        const active = index === current;
                        const accessible = index <= reached && !submitting;
                        const Icon = item.icon;

                        return (
                            <li key={item.label}>
                                <button
                                    type="button"
                                    onClick={() => accessible && onStepClick(index)}
                                    disabled={!accessible}
                                    aria-current={active ? 'step' : undefined}
                                    className={cn(
                                        'flex w-full items-start gap-3 rounded-brand px-3 py-2.5 text-left transition-colors',
                                        active ? 'bg-white shadow-sm ring-1 ring-gray-200' : accessible ? 'cursor-pointer hover:bg-white/70' : 'cursor-not-allowed',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full text-xs transition-colors',
                                            active ? 'bg-primary text-white' : done ? 'bg-soft text-primary' : 'bg-gray-100 text-muted',
                                        )}
                                    >
                                        {done ? <Check className="size-4" aria-hidden /> : <Icon {...ICON} className="size-4" aria-hidden />}
                                    </span>
                                    <span className="min-w-0">
                                        <span className={cn('block text-sm', active ? 'font-medium text-gray-900' : accessible ? 'text-gray-700' : 'text-muted')}>
                                            {item.label}
                                        </span>
                                        <span className="block text-xs text-muted">{item.description}</span>
                                    </span>
                                </button>
                            </li>
                        );
                    })}
                </ol>
            </nav>

            <div className="min-w-0 rounded-brand border bg-white">
                {/* Mobile / tablette : étape courante + barre de progression. */}
                <div className="border-b px-4 py-3 lg:hidden">
                    <div className="flex items-center justify-between gap-3 text-xs text-muted">
                        <span>
                            Étape {current + 1} sur {steps.length}
                        </span>
                        <span>{progression} %</span>
                    </div>
                    {/* Segments cliquables : on passe directement à une étape déjà atteinte. */}
                    <ol aria-label="Étapes du formulaire" className="mt-2 flex gap-1">
                        {steps.map((item, index) => {
                            const accessible = index <= reached && !submitting;
                            return (
                                <li key={item.label} className="flex-1">
                                    <button
                                        type="button"
                                        onClick={() => accessible && onStepClick(index)}
                                        disabled={!accessible}
                                        aria-label={`Étape ${index + 1} : ${item.label}`}
                                        aria-current={index === current ? 'step' : undefined}
                                        className="flex h-5 w-full items-center disabled:cursor-not-allowed"
                                    >
                                        <span
                                            className={cn(
                                                'h-1.5 w-full rounded-full transition-colors',
                                                index === current ? 'bg-primary' : index <= reached ? 'bg-secondary' : 'bg-gray-100',
                                            )}
                                        />
                                    </button>
                                </li>
                            );
                        })}
                    </ol>
                </div>

                <div className="flex items-start gap-3 border-b px-4 py-4 md:px-6">
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-brand bg-soft text-primary">
                        <step.icon {...ICON} aria-hidden />
                    </span>
                    <div className="min-w-0">
                        <h2 className="text-base font-medium text-gray-900">{step.label}</h2>
                        <p className="text-sm text-muted">{step.description}</p>
                    </div>
                </div>

                <div className="space-y-6 p-4 md:p-6">{children}</div>

                <div className="flex flex-col-reverse gap-2 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between md:px-6">
                    <a href={cancelUrl} className={cn(buttonVariants({ variant: 'ghost' }), 'h-10')}>
                        Annuler
                    </a>
                    <div className="flex flex-col-reverse gap-2 sm:flex-row">
                        {current > 0 && (
                            <Button variant="outline" className="h-10" onClick={onPrevious} disabled={submitting}>
                                <ChevronLeft className="size-4" aria-hidden />
                                Précédent
                            </Button>
                        )}
                        <Button className="h-10" onClick={onNext} disabled={submitting || (isLast && submitDisabled)}>
                            {isLast ? (
                                <>
                                    {submitting ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Save className="size-4" aria-hidden />}
                                    {submitting ? 'Enregistrement…' : submitLabel}
                                </>
                            ) : (
                                <>
                                    Suivant
                                    <ChevronRight className="size-4" aria-hidden />
                                </>
                            )}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}

/** Bandeau d'erreur générale (erreur serveur sans champ associé, total de chambres…). */
export function FormAlert({ children }: { children: ReactNode }) {
    return (
        <p role="alert" className="rounded-brand border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {children}
        </p>
    );
}

/** Sous-titre de section à l'intérieur d'une étape. */
export function SectionTitle({ children, hint }: { children: ReactNode; hint?: ReactNode }) {
    return (
        <div className="space-y-0.5">
            <h3 className="text-sm font-medium text-gray-900">{children}</h3>
            {hint && <p className="text-xs text-muted">{hint}</p>}
        </div>
    );
}
