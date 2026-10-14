import { CheckIcon, MinusIcon } from 'lucide-react';
import { Checkbox as CheckboxPrimitive } from 'radix-ui';
import * as React from 'react';
import { cn } from '@/lib/utils';

/** Case à cocher shadcn/ui aux couleurs de la charte (cochée en vert #006400, état « partiel » géré). */
function Checkbox({ className, ...props }: React.ComponentProps<typeof CheckboxPrimitive.Root>) {
    return (
        <CheckboxPrimitive.Root
            data-slot="checkbox"
            className={cn(
                'peer inline-flex size-[18px] shrink-0 cursor-pointer items-center justify-center rounded-[5px] border border-gray-300 bg-white transition-colors',
                'focus-visible:ring-2 focus-visible:ring-secondary focus-visible:ring-offset-1 focus-visible:outline-none',
                'data-[state=checked]:border-primary data-[state=checked]:bg-primary data-[state=checked]:text-white',
                'data-[state=indeterminate]:border-primary data-[state=indeterminate]:bg-primary data-[state=indeterminate]:text-white',
                'disabled:cursor-not-allowed disabled:opacity-60',
                className,
            )}
            {...props}
        >
            <CheckboxPrimitive.Indicator data-slot="checkbox-indicator" className="flex items-center justify-center">
                {props.checked === 'indeterminate' ? <MinusIcon className="size-3.5" strokeWidth={2} /> : <CheckIcon className="size-3.5" strokeWidth={2} />}
            </CheckboxPrimitive.Indicator>
        </CheckboxPrimitive.Root>
    );
}

export { Checkbox };
