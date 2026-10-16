import { cva, type VariantProps } from 'class-variance-authority';
import type { ButtonHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 rounded-brand text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-secondary focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-60 cursor-pointer',
    {
        variants: {
            variant: {
                primary: 'bg-primary text-white hover:bg-primary-hover',
                accent: 'bg-accent text-gray-900 shadow-md shadow-accent/40 hover:bg-accent-hover hover:shadow-lg hover:shadow-accent/50 focus-visible:ring-accent',
                // Orange de la charte en version atténuée (fond pâle, texte ambré), sans ombre.
                'soft-accent': 'border border-accent/40 bg-accent/15 text-amber-800 hover:bg-accent/25 focus-visible:ring-accent',
                secondary:'border border-primary bg-white text-primary hover:bg-soft hover:text-primary-hover focus-visible:bg-soft focus-visible:ring-0 focus-visible:ring-offset-0 data-[state=open]:bg-soft',
                outline:'border border-gray-300 bg-white text-muted hover:bg-surface',
                ghost: 'text-muted hover:bg-soft hover:text-primary',
            },
            size: {
                default: 'h-11 px-5',
                sm: 'h-9 px-3',
                icon: 'h-9 w-9',
            },
        },
        defaultVariants: { variant: 'primary', size: 'default' },
    },
);

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & VariantProps<typeof buttonVariants>;

export function Button({ className, variant, size, type = 'button', ...props }: ButtonProps) {
    return <button type={type} className={cn(buttonVariants({ variant, size }), className)} {...props} />;
}
