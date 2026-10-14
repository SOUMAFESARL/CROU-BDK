import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { ComponentProps } from 'react';
import { DayPicker, getDefaultClassNames } from 'react-day-picker';
import { fr } from 'react-day-picker/locale';
import { cn } from '@/lib/utils';

/**
 * Calendrier shadcn/ui (react-day-picker v9), en français, aux couleurs de la charte :
 * jour sélectionné en primaire, aujourd'hui en fond doux.
 */
function Calendar({ className, classNames, showOutsideDays = true, ...props }: ComponentProps<typeof DayPicker>) {
    const defaults = getDefaultClassNames();

    return (
        <DayPicker
            locale={fr}
            showOutsideDays={showOutsideDays}
            className={cn('p-3', className)}
            classNames={{
                root: cn('w-fit', defaults.root),
                months: cn('relative flex flex-col gap-4', defaults.months),
                month: cn('flex w-full flex-col gap-4', defaults.month),
                nav: cn('absolute inset-x-0 top-0 flex w-full items-center justify-between gap-1', defaults.nav),
                button_previous: cn(
                    'inline-flex size-8 cursor-pointer items-center justify-center rounded-brand text-muted hover:bg-soft hover:text-primary disabled:opacity-40',
                    defaults.button_previous,
                ),
                button_next: cn(
                    'inline-flex size-8 cursor-pointer items-center justify-center rounded-brand text-muted hover:bg-soft hover:text-primary disabled:opacity-40',
                    defaults.button_next,
                ),
                month_caption: cn('flex h-8 w-full items-center justify-center px-8', defaults.month_caption),
                caption_label: cn('text-sm font-semibold text-gray-900 capitalize', defaults.caption_label),
                weekdays: cn('flex', defaults.weekdays),
                weekday: cn('w-9 text-[0.75rem] font-normal text-muted capitalize', defaults.weekday),
                week: cn('mt-1 flex w-full', defaults.week),
                day: cn('size-9 p-0 text-center text-sm', defaults.day),
                day_button: cn(
                    'inline-flex size-9 cursor-pointer items-center justify-center rounded-brand text-gray-900 transition-colors hover:bg-soft hover:text-primary',
                    'focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                    defaults.day_button,
                ),
                selected: '[&>button]:bg-primary [&>button]:text-white [&>button]:hover:bg-primary-hover [&>button]:hover:text-white',
                today: '[&>button]:bg-soft [&>button]:font-semibold [&>button]:text-primary',
                outside: cn('[&>button]:text-gray-400', defaults.outside),
                disabled: cn('[&>button]:pointer-events-none [&>button]:text-gray-300', defaults.disabled),
                hidden: cn('invisible', defaults.hidden),
                ...classNames,
            }}
            components={{
                Chevron: ({ orientation, className: chevronClass }) =>
                    orientation === 'left' ? (
                        <ChevronLeft className={cn('size-4', chevronClass)} aria-hidden />
                    ) : (
                        <ChevronRight className={cn('size-4', chevronClass)} aria-hidden />
                    ),
            }}
            {...props}
        />
    );
}

export { Calendar };
