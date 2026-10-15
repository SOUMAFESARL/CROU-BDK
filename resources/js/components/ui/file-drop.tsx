import { Upload } from 'lucide-react';
import { useEffect, useId, useState } from 'react';
import { cn } from '@/lib/utils';

type FileDropProps = {
    id?: string;
    file: File | null;
    onChange: (file: File | null) => void;
    accept?: string;
    title?: string;
    hint?: string;
    /** Image actuelle (modification), montrée tant qu'aucun nouveau fichier n'est choisi. */
    currentUrl?: string | null;
    invalid?: boolean;
};

/** Zone de dépôt d'un fichier (clic ou glisser-déposer) avec aperçu des images. */
export function FileDrop({ id, file, onChange, accept, title = 'Choisir un fichier', hint, currentUrl, invalid }: FileDropProps) {
    const autoId = useId();
    const inputId = id ?? autoId;
    const [preview, setPreview] = useState<string | null>(null);
    const [survol, setSurvol] = useState(false);

    useEffect(() => {
        if (file && file.type.startsWith('image/')) {
            const url = URL.createObjectURL(file);
            setPreview(url);
            return () => URL.revokeObjectURL(url);
        }
        setPreview(null);
    }, [file]);

    const image = preview ?? (file ? null : currentUrl);

    return (
        <label
            htmlFor={inputId}
            onDragEnter={() => setSurvol(true)}
            onDragOver={(event) => {
                event.preventDefault();
                setSurvol(true);
            }}
            onDragLeave={() => setSurvol(false)}
            onDrop={(event) => {
                event.preventDefault();
                setSurvol(false);
                onChange(event.dataTransfer.files[0] ?? null);
            }}
            className={cn(
                'flex cursor-pointer items-center gap-3 rounded-brand border border-dashed border-gray-300 bg-surface px-4 py-3 text-sm transition-colors hover:border-primary focus-within:border-primary focus-within:ring-2 focus-within:ring-soft',
                survol && 'border-primary bg-soft',
                invalid && 'border-red-500',
            )}
        >
            <input id={inputId} type="file" accept={accept} className="sr-only" onChange={(event) => onChange(event.target.files?.[0] ?? null)} />
            {image ? (
                <img src={image} alt="" className="size-14 shrink-0 rounded-brand border object-cover" />
            ) : (
                <span className="flex size-10 shrink-0 items-center justify-center rounded-brand bg-white text-primary" aria-hidden>
                    <Upload size={18} strokeWidth={1.25} />
                </span>
            )}
            <span className="flex min-w-0 flex-col gap-0.5">
                <span className="text-gray-900">
                    <strong className="font-medium text-primary">{title}</strong> ou le glisser ici
                </span>
                {file ? <span className="truncate text-xs text-gray-900">{file.name}</span> : hint && <span className="text-xs text-muted">{hint}</span>}
            </span>
        </label>
    );
}
