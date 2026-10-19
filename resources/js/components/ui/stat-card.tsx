import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * Fonds des KPI : une teinte par indicateur, complémentaire de la charte (vert, orange) avec un bleu et un prune.
 * Contrastes texte/fond vérifiés WCAG AAA (≥ 7:1) :
 * #0B3A6E / #E3EEFA ≈ 9,7:1 · #004D00 / #DDF0C8 ≈ 8,4:1 · #6B3F00 / #FEEBC0 ≈ 7,6:1 · #6E1F4E / #F8E4EF ≈ 8,8:1
 * · #1F2328 / #FAAB04 ≈ 8,2:1 · #8A1C14 / #FDECEA ≈ 8,6:1 · #54595F / #F8F8F8 ≈ 6,6:1 (AA).
 */
export const KPI_TONES = {
    blue: {
        card: 'border-[#C9DCF3] bg-[#E3EEFA] text-[#0B3A6E]',
        badge: 'bg-white text-[#0B3A6E]',
    },
    green: {
        card: 'border-[#C6E3A6] bg-[#DDF0C8] text-[#004D00]',
        badge: 'bg-white text-[#004D00]',
    },
    amber: {
        card: 'border-[#F9D98A] bg-[#FEEBC0] text-[#6B3F00]',
        badge: 'bg-white text-[#6B3F00]',
    },
    plum: {
        card: 'border-[#EDC8DC] bg-[#F8E4EF] text-[#6E1F4E]',
        badge: 'bg-white text-[#6E1F4E]',
    },
    red: {
        card: 'border-[#F5C2C0] bg-[#FDECEA] text-[#8A1C14]',
        badge: 'bg-white text-[#8A1C14]',
    },
    neutral: {
        card: 'border-[#E6E9E2] bg-[#F8F8F8] text-[#54595F]',
        badge: 'bg-white text-[#54595F]',
    },
} as const;

export type KpiTone = keyof typeof KPI_TONES;

type StatCardProps = {
    label: string;
    value: ReactNode;
    tone: KpiTone;
    /** Pastille à droite de la valeur (ex. « 0.9% d’occupation »). */
    extra?: string;
    /** Point d’attention : la pastille passe en orange plein de la charte. */
    highlight?: boolean;
    /** Contenu complémentaire sous la valeur (barre de progression…). */
    children?: ReactNode;
    className?: string;
    /** Valeur en texte réduit (dates longues, ex. tableau de bord étudiant). */
    compact?: boolean;
};

/** Carte KPI compacte et teintée, utilisée en tête des pages d’administration. */
export function StatCard({ label, value, tone, extra, highlight = false, children, className, compact = false }: StatCardProps) {
    const colors = KPI_TONES[tone];

    return (
        <article className={cn('flex items-center rounded-md border p-3.5', colors.card, className)}>
            <div className="flex w-full min-w-0 flex-col gap-2">
                <small className="block truncate text-xs leading-4 font-medium">{label}</small>
                <div className="flex items-center gap-2">
                    <strong className={cn('tabular-nums', compact ? 'text-sm leading-5 font-medium' : 'text-lg leading-5 font-bold')}>{value}</strong>
                    {extra && (
                        <span
                            className={cn(
                                'truncate rounded px-1.5 py-0.5 text-xs leading-4 font-semibold',
                                highlight ? 'bg-[#FAAB04] text-[#1F2328]' : colors.badge,
                            )}
                        >
                            {extra}
                        </span>
                    )}
                </div>
                {children}
            </div>
        </article>
    );
}
