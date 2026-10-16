import { Tabs as TabsPrimitive } from 'radix-ui';
import * as React from 'react';
import { cn } from '@/lib/utils';

/** Onglets shadcn/ui, style « souligné » : onglet actif en vert #006400. */
function Tabs({ className, ...props }: React.ComponentProps<typeof TabsPrimitive.Root>) {
    return <TabsPrimitive.Root data-slot="tabs" className={cn('flex flex-col gap-5', className)} {...props} />;
}

type TabsListProps = React.ComponentProps<typeof TabsPrimitive.List> & {
    /** Soulignement animé qui glisse vers l'onglet actif (désactivable pour les onglets encadrés). */
    indicator?: boolean;
};

function TabsList({ className, indicator = true, children, ...props }: TabsListProps) {
    const listRef = React.useRef<HTMLDivElement>(null);
    const [position, setPosition] = React.useState<{ left: number; width: number } | null>(null);

    React.useLayoutEffect(() => {
        const list = listRef.current;
        if (!indicator || !list) return;

        const update = () => {
            const active = list.querySelector<HTMLElement>('[data-slot="tabs-trigger"][data-state="active"]');
            setPosition(active ? { left: active.offsetLeft, width: active.offsetWidth } : null);
        };

        update();
        const mutations = new MutationObserver(update);
        mutations.observe(list, { attributes: true, attributeFilter: ['data-state'], subtree: true, childList: true });
        const resize = new ResizeObserver(update);
        resize.observe(list);

        return () => {
            mutations.disconnect();
            resize.disconnect();
        };
    }, [indicator]);

    return (
        <TabsPrimitive.List
            ref={listRef}
            data-slot="tabs-list"
            className={cn('relative flex w-full items-center gap-1 overflow-x-auto overflow-y-hidden border-b', className)}
            {...props}
        >
            {children}
            {indicator && position && (
                <span
                    aria-hidden
                    className="pointer-events-none absolute bottom-0 left-0 h-0.5 bg-primary transition-[transform,width] duration-300 ease-out motion-reduce:transition-none"
                    style={{ width: position.width, transform: `translateX(${position.left}px)` }}
                />
            )}
        </TabsPrimitive.List>
    );
}

function TabsTrigger({ className, ...props }: React.ComponentProps<typeof TabsPrimitive.Trigger>) {
    return (
        <TabsPrimitive.Trigger
            data-slot="tabs-trigger"
            className={cn(
                'inline-flex h-11 shrink-0 cursor-pointer items-center gap-2 px-3 text-sm whitespace-nowrap text-muted transition-colors duration-300',
                'hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none focus-visible:ring-inset',
                'data-[state=active]:font-medium data-[state=active]:text-primary',
                className,
            )}
            {...props}
        />
    );
}

function TabsContent({ className, ...props }: React.ComponentProps<typeof TabsPrimitive.Content>) {
    return (
        <TabsPrimitive.Content
            data-slot="tabs-content"
            className={cn('outline-none data-[state=active]:animate-in data-[state=active]:fade-in-0 data-[state=active]:slide-in-from-bottom-1 data-[state=active]:duration-300 motion-reduce:animate-none', className)}
            {...props}
        />
    );
}

export { Tabs, TabsList, TabsTrigger, TabsContent };
