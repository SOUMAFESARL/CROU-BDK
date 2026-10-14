import { CheckIcon, ChevronDown } from 'lucide-react';
import { Select as SelectPrimitive } from 'radix-ui';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * Liste déroulante (Radix Select) aux couleurs de la charte : remplace le `<select>` natif,
 * dont l'option survolée reste bleue (couleur du système) et ne peut pas être stylée.
 */
function Select(props: ComponentProps<typeof SelectPrimitive.Root>) {
    return <SelectPrimitive.Root data-slot="select" {...props} />;
}

function SelectValue(props: ComponentProps<typeof SelectPrimitive.Value>) {
    return <SelectPrimitive.Value data-slot="select-value" {...props} />;
}

type SelectTriggerProps = ComponentProps<typeof SelectPrimitive.Trigger> & { invalid?: boolean };

/** Déclencheur au style des champs `Input` (hauteur 44px, rayon 8px, focus vert). */
function SelectTrigger({ className, invalid, children, ...props }: SelectTriggerProps) {
    return (
        <SelectPrimitive.Trigger
            data-slot="select-trigger"
            aria-invalid={invalid || undefined}
            className={cn(
                'flex h-11 w-full cursor-pointer items-center justify-between gap-2 rounded-brand border border-gray-300 bg-white px-3 text-left text-sm whitespace-nowrap text-gray-900 transition-colors',
                'focus:border-primary focus:ring-2 focus:ring-soft focus:outline-none data-[placeholder]:text-muted',
                'disabled:cursor-not-allowed disabled:bg-surface disabled:text-muted',
                'aria-invalid:border-red-500 aria-invalid:focus:ring-red-100',
                '[&>span]:truncate',
                className,
            )}
            {...props}
        >
            {children}
            <SelectPrimitive.Icon asChild>
                <ChevronDown className="size-4 shrink-0 text-muted" aria-hidden />
            </SelectPrimitive.Icon>
        </SelectPrimitive.Trigger>
    );
}

function SelectContent({ className, children, position = 'popper', ...props }: ComponentProps<typeof SelectPrimitive.Content>) {
    return (
        <SelectPrimitive.Portal>
            <SelectPrimitive.Content
                data-slot="select-content"
                position={position}
                sideOffset={4}
                className={cn(
                    'relative z-50 max-h-(--radix-select-content-available-height) min-w-[8rem] overflow-hidden rounded-brand border bg-white text-gray-900 shadow-md',
                    'data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95',
                    position === 'popper' && 'w-full min-w-(--radix-select-trigger-width)',
                    className,
                )}
                {...props}
            >
                <SelectPrimitive.Viewport className="p-1">{children}</SelectPrimitive.Viewport>
            </SelectPrimitive.Content>
        </SelectPrimitive.Portal>
    );
}

/** Option : survol/focus en vert clair de la charte, option choisie en vert principal avec coche. */
function SelectItem({ className, children, ...props }: ComponentProps<typeof SelectPrimitive.Item>) {
    return (
        <SelectPrimitive.Item
            data-slot="select-item"
            className={cn(
                'relative flex w-full cursor-pointer items-center rounded-[6px] py-2 pr-8 pl-3 text-sm outline-none select-none',
                'data-[highlighted]:bg-soft data-[highlighted]:text-primary data-[state=checked]:font-medium data-[state=checked]:text-primary',
                'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                className,
            )}
            {...props}
        >
            <span className="absolute right-2 flex size-4 items-center justify-center">
                <SelectPrimitive.ItemIndicator>
                    <CheckIcon className="size-4" aria-hidden />
                </SelectPrimitive.ItemIndicator>
            </span>
            <SelectPrimitive.ItemText>{children}</SelectPrimitive.ItemText>
        </SelectPrimitive.Item>
    );
}

export { Select, SelectContent, SelectItem, SelectTrigger, SelectValue };
