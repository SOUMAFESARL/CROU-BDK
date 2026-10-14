import { Tabs as TabsPrimitive } from 'radix-ui';
import * as React from 'react';
import { cn } from '@/lib/utils';

/** Onglets shadcn/ui, style « souligné » : onglet actif en vert #006400. */
function Tabs({ className, ...props }: React.ComponentProps<typeof TabsPrimitive.Root>) {
    return <TabsPrimitive.Root data-slot="tabs" className={cn('flex flex-col gap-5', className)} {...props} />;
}

function TabsList({ className, ...props }: React.ComponentProps<typeof TabsPrimitive.List>) {
    return (
        <TabsPrimitive.List
            data-slot="tabs-list"
            className={cn('flex w-full items-center gap-1 overflow-x-auto border-b', className)}
            {...props}
        />
    );
}

function TabsTrigger({ className, ...props }: React.ComponentProps<typeof TabsPrimitive.Trigger>) {
    return (
        <TabsPrimitive.Trigger
            data-slot="tabs-trigger"
            className={cn(
                '-mb-px inline-flex h-11 shrink-0 cursor-pointer items-center gap-2 border-b-2 border-transparent px-3 text-sm whitespace-nowrap text-muted transition-colors',
                'hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                'data-[state=active]:border-primary data-[state=active]:font-medium data-[state=active]:text-primary',
                className,
            )}
            {...props}
        />
    );
}

function TabsContent({ className, ...props }: React.ComponentProps<typeof TabsPrimitive.Content>) {
    return <TabsPrimitive.Content data-slot="tabs-content" className={cn('outline-none', className)} {...props} />;
}

export { Tabs, TabsList, TabsTrigger, TabsContent };
