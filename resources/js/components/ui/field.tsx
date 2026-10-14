import type { ReactNode } from 'react';

type FieldProps = {
    id: string;
    label: string;
    error?: string;
    /** Aide affichée sous le champ (masquée quand une erreur est présente). */
    hint?: ReactNode;
    required?: boolean;
    children: ReactNode;
};

export function Field({ id, label, error, hint, required, children }: FieldProps) {
    return (
        <div className="space-y-1.5">
            <label htmlFor={id} className="block text-sm font-medium text-gray-800">
                {label}
                {required && <span className="ml-0.5 text-red-600">*</span>}
            </label>
            {children}
            {error ? (
                <p id={`${id}-error`} role="alert" className="text-xs font-medium text-red-600">
                    {error}
                </p>
            ) : (
                hint && (
                    <p id={`${id}-hint`} className="text-xs text-muted">
                        {hint}
                    </p>
                )
            )}
        </div>
    );
}
