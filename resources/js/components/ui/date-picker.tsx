import { CalendarDays } from 'lucide-react';
import { useState } from 'react';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type DatePickerProps = {
    /** Date au format `aaaa-mm-jj` (valeur échangée avec Laravel), chaîne vide si aucune. */
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    /** Bornes au format `aaaa-mm-jj`. */
    min?: string;
    max?: string;
    invalid?: boolean;
    className?: string;
    'aria-label'?: string;
};

/** `aaaa-mm-jj` → Date locale (sans décalage de fuseau). */
const parse = (value?: string) => {
    if (!value) return undefined;
    const [year, month, day] = value.split('-').map(Number);
    return year && month && day ? new Date(year, month - 1, day) : undefined;
};

const pad = (n: number) => String(n).padStart(2, '0');
const toIso = (date: Date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
const toDisplay = (date: Date) => `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()}`;

/** Sélecteur de date shadcn/ui (Popover + Calendar), affiché au format jj/mm/aaaa. */
export function DatePicker({ value, onChange, placeholder = 'jj/mm/aaaa', min, max, invalid, className, ...props }: DatePickerProps) {
    const [open, setOpen] = useState(false);
    const selected = parse(value);
    const fromDate = parse(min);
    const toDate = parse(max);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <button
                    type="button"
                    aria-label={props['aria-label']}
                    aria-invalid={invalid || undefined}
                    className={cn(
                        'flex h-11 w-full cursor-pointer items-center gap-2 rounded-brand border border-gray-300 bg-white px-3 text-left text-sm whitespace-nowrap transition-colors',
                        'focus:border-primary focus:ring-2 focus:ring-soft focus:outline-none',
                        'aria-invalid:border-red-500 aria-invalid:focus:ring-red-100',
                        selected ? 'text-gray-900' : 'text-gray-400',
                        className,
                    )}
                >
                    <CalendarDays className="size-4 shrink-0 text-muted" aria-hidden />
                    <span className="truncate">{selected ? toDisplay(selected) : placeholder}</span>
                </button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    selected={selected}
                    defaultMonth={selected ?? toDate ?? fromDate}
                    disabled={[...(fromDate ? [{ before: fromDate }] : []), ...(toDate ? [{ after: toDate }] : [])]}
                    onSelect={(date) => {
                        onChange(date ? toIso(date) : '');
                        setOpen(false);
                    }}
                />
            </PopoverContent>
        </Popover>
    );
}
