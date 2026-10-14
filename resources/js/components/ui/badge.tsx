import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';
import { cn } from '@/lib/utils';

/** Badge shadcn/ui aux couleurs de la charte (statuts dans les tableaux). */
const badgeVariants = cva('inline-flex w-fit shrink-0 items-center gap-1.5 rounded px-2.5 py-0.5 text-xs font-medium whitespace-nowrap', {
    variants: {
        variant: {
            success: 'bg-soft text-primary',
            neutral: 'bg-gray-100 text-muted',
            warning: 'bg-amber-50 text-amber-800',
            danger: 'bg-red-50 text-red-700',
            info: 'bg-sky-50 text-sky-700',
        },
    },
    defaultVariants: { variant: 'neutral' },
});

function Badge({ className, variant, ...props }: React.ComponentProps<'span'> & VariantProps<typeof badgeVariants>) {
    return <span data-slot="badge" className={cn(badgeVariants({ variant }), className)} {...props} />;
}

export { Badge, badgeVariants };
