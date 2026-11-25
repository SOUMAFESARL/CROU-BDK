import { History, X } from 'lucide-react';
import { useState, type ComponentProps, type KeyboardEvent } from 'react';
import { Input } from '@/components/ui/input';
import { normaliserSaisie } from '@/hooks/use-saisies-memorisees';
import { cn } from '@/lib/utils';

type InputSuggestionsProps = ComponentProps<typeof Input> & {
    id: string;
    /** Texte saisi : filtre les propositions. */
    valeur: string;
    suggestions: string[];
    /** Nom de la liste pour les lecteurs d'écran. */
    libelleListe: string;
    onChoisir: (valeur: string) => void;
    onOublier: (valeur: string) => void;
};

/** Champ texte proposant les saisies précédentes : clic ou flèches + Entrée pour choisir, croix ou Maj + Suppr pour retirer. */
export function InputSuggestions({ id, valeur, suggestions, libelleListe, onChoisir, onOublier, onFocus, onBlur, onChange, ...props }: InputSuggestionsProps) {
    const [ouvert, setOuvert] = useState(false);
    const [actif, setActif] = useState(-1);

    const terme = normaliserSaisie(valeur);
    // Un numéro déjà saisi en entier n'est plus proposé.
    const proposees = suggestions.filter((suggestion) => normaliserSaisie(suggestion).includes(terme) && normaliserSaisie(suggestion) !== terme);
    const visible = ouvert && proposees.length > 0;
    const courante = visible ? proposees[actif] : undefined;

    const ouvrir = () => {
        setOuvert(true);
        setActif(-1);
    };
    const choisir = (suggestion: string) => {
        onChoisir(suggestion);
        setOuvert(false);
    };
    const oublier = (suggestion: string) => {
        onOublier(suggestion);
        setActif(-1);
    };

    const auClavier = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            if (proposees.length === 0) return;
            event.preventDefault();
            const bas = event.key === 'ArrowDown';
            if (!visible) {
                setOuvert(true);
                setActif(bas ? 0 : proposees.length - 1);
                return;
            }
            // -1 : retour au texte saisi, aucune proposition choisie.
            if (bas) setActif(actif + 1 >= proposees.length ? -1 : actif + 1);
            else setActif(actif < 0 ? proposees.length - 1 : actif - 1);
        } else if (event.key === 'Escape' && visible) {
            event.preventDefault();
            setOuvert(false);
        } else if (event.key === 'Enter') {
            if (courante) {
                event.preventDefault();
                choisir(courante);
            } else {
                setOuvert(false);
            }
        } else if (event.key === 'Delete' && event.shiftKey && courante) {
            event.preventDefault();
            oublier(courante);
        }
    };

    return (
        <div className="relative">
            <Input
                {...props}
                id={id}
                autoComplete="off"
                role="combobox"
                aria-autocomplete="list"
                aria-expanded={visible}
                aria-controls={visible ? `${id}-suggestions` : undefined}
                aria-activedescendant={courante ? `${id}-suggestion-${actif}` : undefined}
                onFocus={(event) => {
                    ouvrir();
                    onFocus?.(event);
                }}
                onClick={() => {
                    if (!ouvert) ouvrir();
                }}
                onBlur={(event) => {
                    setOuvert(false);
                    onBlur?.(event);
                }}
                onChange={(event) => {
                    ouvrir();
                    onChange?.(event);
                }}
                onKeyDown={auClavier}
            />
            {visible && (
                <ul
                    id={`${id}-suggestions`}
                    role="listbox"
                    aria-label={libelleListe}
                    // Le champ garde le focus : la liste ne se ferme pas avant le choix.
                    onMouseDown={(event) => event.preventDefault()}
                    className="absolute inset-x-0 top-full z-20 mt-1 max-h-48 overflow-y-auto rounded-brand border bg-white p-1 shadow-md"
                >
                    {proposees.map((suggestion, index) => (
                        <li key={suggestion} role="presentation" className={cn('flex items-center rounded-brand hover:bg-soft', index === actif && 'bg-soft')}>
                            <div
                                id={`${id}-suggestion-${index}`}
                                role="option"
                                aria-selected={index === actif}
                                onClick={() => choisir(suggestion)}
                                className="flex h-9 min-w-0 flex-1 cursor-pointer items-center gap-2 px-2 text-sm text-gray-900"
                            >
                                <History size={18} strokeWidth={1.25} className="shrink-0 text-muted" aria-hidden />
                                <span className="truncate">{suggestion}</span>
                            </div>
                            <button
                                type="button"
                                tabIndex={-1}
                                aria-label={`Retirer ${suggestion} des propositions`}
                                title="Retirer"
                                onClick={() => oublier(suggestion)}
                                className="flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-brand text-muted hover:text-red-700"
                            >
                                <X size={18} strokeWidth={1.25} aria-hidden />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
