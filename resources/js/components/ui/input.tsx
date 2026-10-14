import type { InputHTMLAttributes, ReactNode, Ref } from 'react';
import { cn } from '@/lib/utils';

type InputProps = InputHTMLAttributes<HTMLInputElement> & {
    icon?: ReactNode;
    trailing?: ReactNode;
    invalid?: boolean;
    ref?: Ref<HTMLInputElement>;
};

export function Input({ className, icon, trailing, invalid, ...props }: InputProps) {
    return (
        <div className="relative">
            {icon && <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted [&_svg]:size-4">{icon}</span>}
            <input
                aria-invalid={invalid || undefined}
                className={cn(
                    'h-11 w-full rounded-brand border border-gray-300 bg-white px-3 text-sm text-gray-900 placeholder:text-gray-400 transition-colors',
                    'focus:border-primary focus:ring-2 focus:ring-soft focus:outline-none',
                    invalid && 'border-red-500 focus:border-red-500 focus:ring-red-100',
                    icon && 'pl-10',
                    trailing && 'pr-11',
                    className,
                )}
                {...props}
            />
            {trailing && <span className="absolute top-1/2 right-1 -translate-y-1/2">{trailing}</span>}
        </div>
    );
}
