import { Switch as SwitchPrimitive } from 'radix-ui';
import * as React from 'react';
import { cn } from '@/lib/utils';

/** Interrupteur shadcn/ui aux couleurs de la charte (activé en vert #006400). */
function Switch({ className, ...props }: React.ComponentProps<typeof SwitchPrimitive.Root>) {
    return (
        <SwitchPrimitive.Root
            data-slot="switch"
            className={cn(
                'peer inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border border-transparent bg-gray-300 transition-colors',
                'focus-visible:ring-2 focus-visible:ring-secondary focus-visible:ring-offset-1 focus-visible:outline-none',
                'data-[state=checked]:bg-primary disabled:cursor-not-allowed disabled:opacity-60',
                className,
            )}
            {...props}
        >
            <SwitchPrimitive.Thumb
                data-slot="switch-thumb"
                className="pointer-events-none block size-4 translate-x-0.5 rounded-full bg-white shadow-sm transition-transform data-[state=checked]:translate-x-4"
            />
        </SwitchPrimitive.Root>
    );
}

export { Switch };
