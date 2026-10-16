import { cn } from '@/lib/utils';

type ProgressProps = {
    /** Pourcentage de 0 à 100. */
    value: number;
    tone?: 'primary' | 'secondary' | 'accent' | 'danger';
    className?: string;
    label?: string;
};

const tones = {
    primary: 'bg-primary',
    secondary: 'bg-secondary',
    accent: 'bg-accent',
    danger: 'bg-red-600',
};

/** Barre de progression fine (stock, documents validés, taux d'occupation…). */
export function Progress({ value, tone = 'secondary', className, label }: ProgressProps) {
    const taux = Math.max(0, Math.min(100, Math.round(value)));

    return (
        <div
            role="progressbar"
            aria-valuenow={taux}
            aria-valuemin={0}
            aria-valuemax={100}
            aria-label={label}
            title={label}
            className={cn('h-1.5 w-full overflow-hidden rounded-full bg-gray-200', className)}
        >
            <span className={cn('block h-full rounded-full transition-[width]', tones[tone])} style={{ width: `${taux}%` }} />
        </div>
    );
}
