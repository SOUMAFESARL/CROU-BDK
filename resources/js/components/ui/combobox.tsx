import { CheckIcon, ChevronDown, Search } from 'lucide-react';
import { useMemo, useRef, useState, type ReactNode } from 'react';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { sansAccents } from '@/lib/text';
import { cn } from '@/lib/utils';

export type ComboboxOption = {
    value: string;
    label: string;
    /** Texte secondaire (matricule, places libres…), aussi pris en compte par la recherche. */
    description?: string;
    disabled?: boolean;
};

type ComboboxProps = {
    id?: string;
    value: string;
    onChange: (value: string) => void;
    options: ComboboxOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    emptyMessage?: ReactNode;
    disabled?: boolean;
    invalid?: boolean;
    className?: string;
    onBlur?: () => void;
};

/**
 * Liste déroulante avec recherche sans accents (remplace `ca-select.js` et select2),
 * au style des champs de la charte : option survolée en vert clair, option choisie en vert + coche.
 */
export function Combobox({
    id,
    value,
    onChange,
    options,
    placeholder = 'Sélectionner…',
    searchPlaceholder = 'Rechercher…',
    emptyMessage = 'Aucun résultat.',
    disabled,
    invalid,
    className,
    onBlur,
}: ComboboxProps) {
    const [open, setOpen] = useState(false);
    const [recherche, setRecherche] = useState('');
    const [actif, setActif] = useState(0);
    const liste = useRef<HTMLUListElement>(null);
    const choisie = options.find((option) => option.value === value);

    const filtrees = useMemo(() => {
        const terme = sansAccents(recherche.trim());
        return terme ? options.filter((option) => sansAccents(`${option.label} ${option.description ?? ''}`).includes(terme)) : options;
    }, [options, recherche]);

    const choisir = (option: ComboboxOption) => {
        if (option.disabled) return;
        onChange(option.value);
        setOpen(false);
    };

    const deplacer = (pas: number) => {
        if (filtrees.length === 0) return;
        const suivant = (actif + pas + filtrees.length) % filtrees.length;
        setActif(suivant);
        liste.current?.children[suivant]?.scrollIntoView({ block: 'nearest' });
    };

    return (
        <Popover
            open={open}
            onOpenChange={(ouvert) => {
                setOpen(ouvert);
                if (ouvert) {
                    setRecherche('');
                    setActif(Math.max(0, options.findIndex((option) => option.value === value)));
                } else {
                    onBlur?.();
                }
            }}
        >
            <PopoverTrigger asChild>
                <button
                    type="button"
                    id={id}
                    role="combobox"
                    aria-expanded={open}
                    aria-invalid={invalid || undefined}
                    disabled={disabled}
                    className={cn(
                        'flex h-11 w-full cursor-pointer items-center justify-between gap-2 rounded-brand border border-gray-300 bg-white px-3 text-left text-sm transition-colors',
                        'focus:border-primary focus:ring-2 focus:ring-soft focus:outline-none',
                        'disabled:cursor-not-allowed disabled:bg-surface disabled:text-muted',
                        'aria-invalid:border-red-500 aria-invalid:focus:ring-red-100',
                        className,
                    )}
                >
                    <span className={cn('truncate', choisie ? 'text-gray-900' : 'text-muted')}>{choisie?.label ?? placeholder}</span>
                    <ChevronDown className="size-4 shrink-0 text-muted" aria-hidden />
                </button>
            </PopoverTrigger>
            <PopoverContent align="start" className="w-(--radix-popover-trigger-width) min-w-64 p-0">
                <div className="relative border-b">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted" aria-hidden />
                    <input
                        autoFocus
                        value={recherche}
                        onChange={(event) => {
                            setRecherche(event.target.value);
                            setActif(0);
                        }}
                        onKeyDown={(event) => {
                            if (event.key === 'ArrowDown') {
                                event.preventDefault();
                                deplacer(1);
                            } else if (event.key === 'ArrowUp') {
                                event.preventDefault();
                                deplacer(-1);
                            } else if (event.key === 'Enter') {
                                event.preventDefault();
                                const option = filtrees[actif];
                                if (option) choisir(option);
                            }
                        }}
                        placeholder={searchPlaceholder}
                        aria-label={searchPlaceholder}
                        className="h-10 w-full bg-transparent pr-3 pl-9 text-sm outline-none placeholder:text-gray-400"
                    />
                </div>
                <ul ref={liste} role="listbox" className="max-h-64 overflow-y-auto p-1">
                    {filtrees.length === 0 ? (
                        <li className="px-3 py-6 text-center text-sm text-muted">{emptyMessage}</li>
                    ) : (
                        filtrees.map((option, index) => {
                            const selectionnee = option.value === value;
                            return (
                                <li
                                    key={option.value}
                                    role="option"
                                    aria-selected={selectionnee}
                                    aria-disabled={option.disabled || undefined}
                                    onMouseEnter={() => setActif(index)}
                                    onClick={() => choisir(option)}
                                    className={cn(
                                        'relative flex cursor-pointer flex-col rounded-[6px] py-2 pr-8 pl-3 text-sm select-none',
                                        index === actif && 'bg-soft text-primary',
                                        selectionnee && 'font-medium text-primary',
                                        option.disabled && 'cursor-not-allowed opacity-50',
                                    )}
                                >
                                    <span className="truncate">{option.label}</span>
                                    {option.description && <span className="truncate text-xs font-normal text-muted">{option.description}</span>}
                                    {selectionnee && <CheckIcon className="absolute top-2.5 right-2 size-4" aria-hidden />}
                                </li>
                            );
                        })
                    )}
                </ul>
            </PopoverContent>
        </Popover>
    );
}
