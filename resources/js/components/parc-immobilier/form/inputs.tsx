import { FileText, Minus, Pencil, Plus, Trash2, UploadCloud, X } from 'lucide-react';
import { useEffect, useId, useMemo, useRef, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';

type CounterProps = {
    id: string;
    value: string;
    onChange: (value: string) => void;
    onBlur?: () => void;
    min?: number;
    max?: number;
    invalid?: boolean;
    describedBy?: string;
    label: string;
};

/** Champ numérique entier avec boutons − / + (saisie directe possible). */
export function Counter({ id, value, onChange, onBlur, min = 0, max = 9999, invalid, describedBy, label }: CounterProps) {
    const nombre = Number.parseInt(value, 10);
    const courant = Number.isFinite(nombre) ? nombre : min;
    const changer = (delta: number) => onChange(String(Math.min(max, Math.max(min, courant + delta))));

    return (
        <div
            className={cn(
                'flex h-11 w-full items-stretch overflow-hidden rounded-brand border bg-white focus-within:border-primary focus-within:ring-2 focus-within:ring-soft',
                invalid ? 'border-red-500' : 'border-gray-300',
            )}
        >
            <button
                type="button"
                onClick={() => changer(-1)}
                disabled={courant <= min}
                aria-label={`Diminuer : ${label}`}
                className="flex w-11 shrink-0 cursor-pointer items-center justify-center text-muted transition-colors hover:bg-soft hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
            >
                <Minus className="size-4" aria-hidden />
            </button>
            <input
                id={id}
                inputMode="numeric"
                value={value}
                onChange={(event) => onChange(event.target.value.replace(/[^\d]/g, '').slice(0, 5))}
                onBlur={onBlur}
                aria-invalid={invalid || undefined}
                aria-describedby={describedBy}
                className="min-w-0 flex-1 border-x border-gray-200 bg-transparent text-center text-sm text-gray-900 tabular-nums outline-none"
            />
            <button
                type="button"
                onClick={() => changer(1)}
                disabled={courant >= max}
                aria-label={`Augmenter : ${label}`}
                className="flex w-11 shrink-0 cursor-pointer items-center justify-center text-muted transition-colors hover:bg-soft hover:text-primary disabled:cursor-not-allowed disabled:opacity-40"
            >
                <Plus className="size-4" aria-hidden />
            </button>
        </div>
    );
}

type ToggleCardProps = {
    checked: boolean;
    onChange: (checked: boolean) => void;
    label: string;
    icon?: ReactNode;
    description?: string;
};

/** Case à cocher présentée en carte (services disponibles). */
export function ToggleCard({ checked, onChange, label, icon, description }: ToggleCardProps) {
    const id = useId();

    return (
        <label
            htmlFor={id}
            className={cn(
                'flex cursor-pointer items-center gap-3 rounded-brand border px-3 py-3 transition-colors',
                checked ? 'border-primary bg-soft' : 'border-gray-200 bg-white hover:border-gray-300',
            )}
        >
            <Checkbox id={id} checked={checked} onCheckedChange={(value) => onChange(value === true)} />
            {icon && <span className={cn('[&_svg]:size-[18px]', checked ? 'text-primary' : 'text-muted')}>{icon}</span>}
            <span className="min-w-0">
                <span className="block text-sm text-gray-900">{label}</span>
                {description && <span className="block text-xs text-muted">{description}</span>}
            </span>
        </label>
    );
}

type ChoiceCardProps = {
    selected: boolean;
    onSelect: () => void;
    title: string;
    description: string;
    icon: ReactNode;
    name: string;
    value: string;
};

/** Choix exclusif présenté en carte (bouton radio). */
export function ChoiceCard({ selected, onSelect, title, description, icon, name, value }: ChoiceCardProps) {
    const id = useId();

    return (
        <label
            htmlFor={id}
            className={cn(
                'flex cursor-pointer items-start gap-3 rounded-brand border p-4 transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-secondary',
                selected ? 'border-primary bg-soft' : 'border-gray-200 bg-white hover:border-gray-300',
            )}
        >
            <input id={id} type="radio" name={name} value={value} checked={selected} onChange={onSelect} className="sr-only" />
            <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-brand', selected ? 'bg-primary text-white' : 'bg-gray-100 text-muted')}>
                {icon}
            </span>
            <span className="min-w-0">
                <span className={cn('block text-sm font-medium', selected ? 'text-primary' : 'text-gray-900')}>{title}</span>
                <span className="block text-xs text-muted">{description}</span>
            </span>
        </label>
    );
}

type PlanUploadProps = {
    id: string;
    file: File | null;
    onFileChange: (file: File | null) => void;
    /** Plan déjà enregistré (modification). */
    existingUrl?: string | null;
    removeExisting?: boolean;
    onRemoveExistingChange?: (remove: boolean) => void;
    maxMo: number;
    error?: string;
};

export const PLAN_ACCEPT = '.pdf,.png,.jpg,.jpeg';
const PLAN_TYPES = ['application/pdf', 'image/png', 'image/jpeg'];

/** Contrôle client du plan : format (PDF, PNG, JPEG) et taille ; null si valide. */
export function erreurPlan(file: File | null, maxMo: number): string | null {
    if (!file) return null;
    const extension = file.name.split('.').pop()?.toLowerCase() ?? '';
    if (!PLAN_TYPES.includes(file.type) && !['pdf', 'png', 'jpg', 'jpeg'].includes(extension)) {
        return 'Format non accepté : PDF, PNG ou JPEG uniquement.';
    }
    if (file.size > maxMo * 1024 * 1024) {
        return `Le fichier dépasse ${maxMo} Mo.`;
    }
    return null;
}

/** Zone de dépôt du plan (PDF ou image) avec aperçu, et gestion du plan existant. */
export function PlanUpload({ id, file, onFileChange, existingUrl, removeExisting = false, onRemoveExistingChange, maxMo, error }: PlanUploadProps) {
    const input = useRef<HTMLInputElement | null>(null);
    const apercu = useMemo(() => (file ? URL.createObjectURL(file) : null), [file]);
    useEffect(() => () => {
        if (apercu) URL.revokeObjectURL(apercu);
    }, [apercu]);
    const estImage = (nom: string) => /\.(png|jpe?g|gif|webp)$/i.test(nom);

    return (
        <div className="space-y-3">
            {existingUrl && !file && (
                <div className={cn('flex flex-wrap items-center gap-3 rounded-brand border p-3', removeExisting && 'border-dashed opacity-60')}>
                    {estImage(existingUrl) ? (
                        <img src={existingUrl} alt="Plan actuel" className="h-16 w-24 rounded-[6px] border object-cover" />
                    ) : (
                        <span className="flex size-12 items-center justify-center rounded-[6px] bg-soft text-primary">
                            <FileText className="size-5" aria-hidden />
                        </span>
                    )}
                    <div className="min-w-0 flex-1 text-sm">
                        <a href={existingUrl} target="_blank" rel="noopener" className="text-primary hover:underline">
                            Voir le plan actuel
                        </a>
                        <p className="text-xs text-muted">{removeExisting ? 'Sera supprimé à l’enregistrement.' : 'Déposez un fichier pour le remplacer.'}</p>
                    </div>
                    {onRemoveExistingChange && (
                        <Button variant="outline" size="sm" onClick={() => onRemoveExistingChange(!removeExisting)}>
                            {removeExisting ? <X className="size-4" aria-hidden /> : <Trash2 className="size-4" aria-hidden />}
                            {removeExisting ? 'Conserver' : 'Supprimer'}
                        </Button>
                    )}
                </div>
            )}

            {file ? (
                <div className="space-y-3 rounded-brand border p-3">
                    <div className="flex items-center gap-3">
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-[6px] bg-soft text-primary">
                            <FileText className="size-5" aria-hidden />
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm text-gray-900">{file.name}</p>
                            <p className="text-xs text-muted">{(file.size / 1024 / 1024).toFixed(2)} Mo</p>
                        </div>
                        <Button variant="ghost" size="icon" onClick={() => input.current?.click()} aria-label="Changer de fichier">
                            <Pencil className="size-4" aria-hidden />
                        </Button>
                        <Button variant="ghost" size="icon" onClick={() => onFileChange(null)} aria-label="Retirer le fichier">
                            <X className="size-4" aria-hidden />
                        </Button>
                    </div>
                    {apercu &&
                        (file.type === 'application/pdf' ? (
                            <iframe src={apercu} title="Aperçu du plan" className="h-72 w-full rounded-[6px] border" />
                        ) : (
                            <img src={apercu} alt="Aperçu du plan" className="max-h-72 w-full rounded-[6px] border object-contain" />
                        ))}
                </div>
            ) : (
                <button
                    type="button"
                    onClick={() => input.current?.click()}
                    onDragOver={(event) => event.preventDefault()}
                    onDrop={(event) => {
                        event.preventDefault();
                        const depose = event.dataTransfer.files?.[0];
                        if (depose) onFileChange(depose);
                    }}
                    aria-describedby={error ? `${id}-error` : undefined}
                    className={cn(
                        'flex w-full cursor-pointer flex-col items-center justify-center gap-2 rounded-brand border-2 border-dashed px-4 py-8 text-center transition-colors hover:border-primary hover:bg-soft/50 focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                        error ? 'border-red-400' : 'border-gray-300',
                    )}
                >
                    <UploadCloud className="size-7 text-primary" strokeWidth={1.25} aria-hidden />
                    <span className="text-sm text-gray-900">Cliquez ou déposez un fichier</span>
                    <span className="text-xs text-muted">PDF, PNG ou JPEG — {maxMo} Mo maximum</span>
                </button>
            )}
            <input
                ref={input}
                id={id}
                type="file"
                accept={PLAN_ACCEPT}
                className="sr-only"
                tabIndex={-1}
                onChange={(event) => {
                    onFileChange(event.target.files?.[0] ?? null);
                    event.target.value = '';
                }}
            />
            {error && (
                <p id={`${id}-error`} role="alert" className="text-xs font-medium text-red-600">
                    {error}
                </p>
            )}
        </div>
    );
}

type RecapProps = {
    title: string;
    onEdit: () => void;
    rows: [string, ReactNode][];
};

/** Bloc du récapitulatif final, avec retour direct sur l'étape à corriger. */
export function RecapSection({ title, onEdit, rows }: RecapProps) {
    return (
        <section className="rounded-brand border">
            <div className="flex items-center justify-between gap-3 border-b bg-surface/60 px-4 py-2.5">
                <h3 className="text-sm font-medium text-gray-900">{title}</h3>
                <Button variant="ghost" size="sm" className="h-8" onClick={onEdit}>
                    <Pencil className="size-3.5" aria-hidden />
                    Modifier
                </Button>
            </div>
            <dl className="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-3 sm:grid-cols-2">
                {rows.map(([label, valeur]) => (
                    <div key={label} className="min-w-0">
                        <dt className="text-xs text-muted">{label}</dt>
                        <dd className="text-sm break-words text-gray-900">{valeur === null || valeur === undefined || valeur === '' ? '—' : valeur}</dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}
