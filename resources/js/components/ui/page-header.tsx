import type { ReactNode } from 'react';
import { Separator } from '@/components/ui/separator';

type PageHeaderProps = {
    title: string;
    description?: ReactNode;
    /** Boutons d'action, alignés à droite sous un séparateur (masqués s'il n'y en a aucun). */
    actions?: ReactNode;
};

/** En-tête commun des pages admin : titre (barre orange), description, séparateur puis actions. */
export function PageHeader({ title, description, actions }: PageHeaderProps) {
    return (
        <div className="mb-6 flex flex-col gap-4">
            <div className="flex flex-col gap-1">
                <h1 className="page-title text-xl font-medium text-gray-900">{title}</h1>
                {description && <div className="text-sm text-muted">{description}</div>}
            </div>
            {actions && (
                <>
                    <Separator />
                    <div className="flex flex-wrap items-center justify-end gap-2">{actions}</div>
                </>
            )}
        </div>
    );
}
