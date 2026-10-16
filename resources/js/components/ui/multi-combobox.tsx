import { Check, ChevronDown, Search, X } from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import type { ComboboxOption } from '@/components/ui/combobox';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { sansAccents } from '@/lib/text';
import { cn } from '@/lib/utils';

type MultiComboboxProps = {
    id?: string;
    value: string[];
    onChange: (value: string[]) => void;
    options: ComboboxOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    emptyMessage?: ReactNode;
    invalid?: boolean;
    onBlur?: () => void;
};

/**
 * Sélection multiple avec recherche sans accents (remplace select2 « multiple ») :
 * les éléments choisis s'affichent en étiquettes retirables sous le champ.
 */
export function MultiCombobox({
    id,
    value,
    onChange,
    options,
    placeholder = 'Sélectionner…',
    searchPlaceholder = 'Rechercher…',
    emptyMessage = 'Aucun résultat.',
    invalid,
    onBlur,
}: MultiComboboxProps) {
    const [open, setOpen] = useState(false);
    const [recherche, setRecherche] = useState('');
    const choisis = useMemo(() => options.filter((option) => value.includes(option.value)), [options, value]);

    const filtrees = useMemo(() => {
        const terme = sansAccents(recherche.trim());
        const liste = terme ? options.filter((option) => sansAccents(`${option.label} ${option.description ?? ''}`).includes(terme)) : options;
        return liste.slice(0, 200);
    }, [options, recherche]);

    const basculer = (option: ComboboxOption) =>
        onChange(value.includes(option.value) ? value.filter((v) => v !== option.value) : [...value, option.value]);

    return (
        <div className="space-y-2">
            <Popover
                open={open}
                onOpenChange={(ouvert) => {
                    setOpen(ouvert);
                    if (ouvert) setRecherche('');
                    else onBlur?.();
                }}
            >
                <PopoverTrigger asChild>
                    <button
                        type="button"
                        id={id}
                        role="combobox"
                        aria-expanded={open}
                        aria-invalid={invalid || undefined}
                        className={cn(
                            'flex h-11 w-full cursor-pointer items-center justify-between gap-2 rounded-brand border border-gray-300 bg-white px-3 text-left text-sm transition-colors',
                            'focus:border-primary focus:ring-2 focus:ring-soft focus:outline-none',
                            'aria-invalid:border-red-500 aria-invalid:focus:ring-red-100',
                        )}
                    >
                        <span className={cn('truncate', choisis.length ? 'text-gray-900' : 'text-muted')}>
                            {choisis.length ? `${choisis.length} sélectionné(s)` : placeholder}
                        </span>
                        <ChevronDown className="size-4 shrink-0 text-muted" aria-hidden />
                    </button>
                </PopoverTrigger>
                <PopoverContent align="start" className="w-(--radix-popover-trigger-width) min-w-64 p-0">
                    <div className="relative border-b">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted" aria-hidden />
                        <input
                            autoFocus
                            value={recherche}
                            onChange={(event) => setRecherche(event.target.value)}
                            placeholder={searchPlaceholder}
                            aria-label={searchPlaceholder}
                            className="h-10 w-full bg-transparent pr-3 pl-9 text-sm outline-none placeholder:text-gray-400"
                        />
                    </div>
                    <ul role="listbox" aria-multiselectable className="max-h-64 overflow-y-auto p-1">
                        {filtrees.length === 0 ? (
                            <li className="px-3 py-6 text-center text-sm text-muted">{emptyMessage}</li>
                        ) : (
                            filtrees.map((option) => {
                                const coche = value.includes(option.value);
                                return (
                                    <li
                                        key={option.value}
                                        role="option"
                                        aria-selected={coche}
                                        onClick={() => basculer(option)}
                                        className={cn('flex cursor-pointer items-center gap-2.5 rounded-[6px] px-3 py-2 text-sm select-none hover:bg-soft', coche && 'text-primary')}
                                    >
                                        <span
                                            aria-hidden
                                            className={cn('flex size-4 shrink-0 items-center justify-center rounded border', coche ? 'border-primary bg-primary text-white' : 'border-gray-300 bg-white')}
                                        >
                                            {coche && <Check className="size-3" />}
                                        </span>
                                        <span className="flex min-w-0 flex-col">
                                            <span className="truncate">{option.label}</span>
                                            {option.description && <span className="truncate text-xs text-muted">{option.description}</span>}
                                        </span>
                                    </li>
                                );
                            })
                        )}
                    </ul>
                </PopoverContent>
            </Popover>
            {choisis.length > 0 && (
                <ul className="flex flex-wrap gap-1.5" aria-label="Éléments sélectionnés">
                    {choisis.map((option) => (
                        <li key={option.value} className="inline-flex items-center gap-1 rounded bg-soft py-0.5 pr-1 pl-2 text-xs text-primary">
                            {option.label}
                            <button
                                type="button"
                                onClick={() => basculer(option)}
                                aria-label={`Retirer ${option.label}`}
                                className="inline-flex size-4 cursor-pointer items-center justify-center rounded hover:bg-white"
                            >
                                <X className="size-3" aria-hidden />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
