import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

type RowActionProps = {
    label: string;
    icon: LucideIcon;
    /** Lien classique (écrans Blade non migrés : rechargement complet). */
    href?: string;
    /** Ouvre le lien dans un nouvel onglet (PDF…). */
    newTab?: boolean;
    onClick?: () => void;
    tone?: Tone;
};

/** Couleur de l'icône selon la nature de l'action (fond teinté au survol) ; « view » reprend la couleur primaire de la charte. */
type Tone = 'view' | 'edit' | 'success' | 'danger' | 'default';

const tones: Record<Tone, string> = {
    view: 'text-primary hover:bg-soft hover:text-primary-hover',
    edit: 'text-amber-600 hover:bg-amber-50 hover:text-amber-700',
    success: 'text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700',
    danger: 'text-red-600 hover:bg-red-50 hover:text-red-700',
    default: 'text-primary hover:bg-soft',
};

const base =
    'inline-flex size-8 cursor-pointer items-center justify-center rounded-brand transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-secondary';

/** Bouton d'action d'une ligne de tableau : icône seule + info-bulle (le libellé sert aussi d'aria-label). */
export function RowAction({ label, icon: Icon, href, newTab, onClick, tone = 'default' }: RowActionProps) {
    const className = cn(base, tones[tone]);
    const content = <Icon className="size-4.5" strokeWidth={1.5} aria-hidden />;

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                {href ? (
                    <a href={href} aria-label={label} className={className} {...(newTab && { target: '_blank', rel: 'noopener' })}>
                        {content}
                    </a>
                ) : (
                    <button type="button" aria-label={label} onClick={onClick} className={className}>
                        {content}
                    </button>
                )}
            </TooltipTrigger>
            <TooltipContent sideOffset={4}>{label}</TooltipContent>
        </Tooltip>
    );
}

export function RowActions({ children }: { children: ReactNode }) {
    return <div className="flex items-center justify-end gap-1">{children}</div>;
}
