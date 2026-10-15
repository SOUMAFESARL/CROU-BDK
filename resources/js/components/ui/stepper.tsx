import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

type StepperProps = {
    steps: string[];
    current: number;
    /** Plus haute étape atteinte : les étapes au-delà ne sont pas cliquables. */
    reached: number;
    onStepClick: (index: number) => void;
    className?: string;
};

/** Indicateur d'étapes horizontal (assistants dans une fenêtre modale). */
export function Stepper({ steps, current, reached, onStepClick, className }: StepperProps) {
    return (
        <ol aria-label="Étapes" className={cn('flex items-center gap-2 border-b px-6 py-3', className)}>
            {steps.map((label, index) => {
                const fait = index < current;
                const actif = index === current;
                return (
                    <li key={label} className="flex min-w-0 flex-1 items-center gap-2">
                        <button
                            type="button"
                            disabled={index > reached}
                            onClick={() => onStepClick(index)}
                            aria-current={actif ? 'step' : undefined}
                            className="flex min-w-0 cursor-pointer items-center gap-2 rounded-brand text-left focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none disabled:cursor-default"
                        >
                            <span
                                className={cn(
                                    'flex size-6 shrink-0 items-center justify-center rounded-full border text-xs font-medium',
                                    actif && 'border-primary bg-primary text-white',
                                    fait && 'border-secondary bg-soft text-primary',
                                    !actif && !fait && 'border-gray-300 bg-white text-muted',
                                )}
                            >
                                {fait ? <Check className="size-3.5" aria-hidden /> : index + 1}
                            </span>
                            <span className={cn('truncate text-sm', actif ? 'font-medium text-gray-900' : 'text-muted')}>{label}</span>
                        </button>
                        {index < steps.length - 1 && <span aria-hidden className={cn('h-px flex-1', fait ? 'bg-secondary' : 'bg-gray-200')} />}
                    </li>
                );
            })}
        </ol>
    );
}
