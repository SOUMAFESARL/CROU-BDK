import { ChevronDown } from "lucide-react";
import { Accordion as AccordionPrimitive } from "radix-ui";
import * as React from "react";
import { cn } from "@/lib/utils";

/** Accordéon shadcn/ui (Radix) : chaque section est une carte blanche bordée, rayon 8px. */
function Accordion({
    className,
    ...props
}: React.ComponentProps<typeof AccordionPrimitive.Root>) {
    return (
        <AccordionPrimitive.Root
            data-slot="accordion"
            className={cn("space-y-3", className)}
            {...props}
        />
    );
}

function AccordionItem({
    className,
    ...props
}: React.ComponentProps<typeof AccordionPrimitive.Item>) {
    return (
        <AccordionPrimitive.Item
            data-slot="accordion-item"
            className={cn(
                "overflow-hidden rounded-brand border bg-white",
                className,
            )}
            {...props}
        />
    );
}

function AccordionTrigger({
    className,
    children,
    ...props
}: React.ComponentProps<typeof AccordionPrimitive.Trigger>) {
    return (
        <AccordionPrimitive.Header className="flex">
            <AccordionPrimitive.Trigger
                data-slot="accordion-trigger"
                className={cn(
                    "flex flex-1 items-center gap-2 px-4 py-3 text-left text-sm font-medium text-gray-900 outline-none transition-colors hover:bg-surface/60 focus-visible:ring-2 focus-visible:ring-primary/30 disabled:pointer-events-none disabled:opacity-50 [&[data-state=open]>svg:last-child]:rotate-180",
                    className,
                )}
                {...props}
            >
                {children}
                <ChevronDown
                    size={18}
                    strokeWidth={1.25}
                    className="ml-auto shrink-0 text-muted transition-transform duration-200"
                    aria-hidden
                />
            </AccordionPrimitive.Trigger>
        </AccordionPrimitive.Header>
    );
}

function AccordionContent({
    className,
    children,
    ...props
}: React.ComponentProps<typeof AccordionPrimitive.Content>) {
    return (
        <AccordionPrimitive.Content
            data-slot="accordion-content"
            className="overflow-hidden data-[state=closed]:animate-accordion-up data-[state=open]:animate-accordion-down"
            {...props}
        >
            <div className={cn("border-t", className)}>{children}</div>
        </AccordionPrimitive.Content>
    );
}

export { Accordion, AccordionContent, AccordionItem, AccordionTrigger };
