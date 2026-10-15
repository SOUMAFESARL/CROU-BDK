import type { Ref, TextareaHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

type TextareaProps = TextareaHTMLAttributes<HTMLTextAreaElement> & { invalid?: boolean; ref?: Ref<HTMLTextAreaElement> };

/** Zone de texte au style des champs `Input`. */
export function Textarea({ className, invalid, ...props }: TextareaProps) {
    return (
        <textarea
            aria-invalid={invalid || undefined}
            className={cn(
                'min-h-20 w-full rounded-brand border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors',
                'focus:border-primary focus:ring-2 focus:ring-soft focus:outline-none',
                invalid && 'border-red-500 focus:border-red-500 focus:ring-red-100',
                className,
            )}
            {...props}
        />
    );
}
