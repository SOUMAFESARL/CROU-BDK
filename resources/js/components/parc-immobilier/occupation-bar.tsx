import { cn } from '@/lib/utils';
import { nombre } from './labels';
import type { Resume } from './types';

const SEGMENTS = [
    { key: 'occupees', label: 'Occupées', className: 'bg-primary' },
    { key: 'reservees', label: 'Réservées', className: 'bg-accent' },
    { key: 'maintenance', label: 'Maintenance', className: 'bg-red-400' },
    { key: 'disponibles', label: 'Disponibles', className: 'bg-secondary/45' },
] as const;

/** Taux d'occupation des places (affectations actives / capacité) : une chambre double à moitié occupée compte pour 50 %. */
export const tauxOccupation = (resume: Pick<Resume, 'places' | 'occupants'>) =>
    resume.places > 0 ? Math.round((resume.occupants / resume.places) * 100) : 0;

/** Barre empilée de la répartition des chambres par état, avec légende facultative. */
export function OccupationBar({ resume, legend = false, className }: { resume: Resume; legend?: boolean; className?: string }) {
    const total = resume.chambres;

    return (
        <div className={cn('space-y-2', className)}>
            <div
                className="flex h-2 w-full overflow-hidden rounded-full bg-gray-100"
                role="img"
                aria-label={`Chambres : ${resume.occupees} occupées, ${resume.reservees} réservées, ${resume.maintenance} en maintenance, ${resume.disponibles} disponibles`}
            >
                {total > 0 &&
                    SEGMENTS.map((segment) =>
                        resume[segment.key] > 0 ? (
                            <span
                                key={segment.key}
                                className={cn('h-full first:rounded-l-full last:rounded-r-full', segment.className)}
                                style={{ width: `${(resume[segment.key] / total) * 100}%` }}
                            />
                        ) : null,
                    )}
            </div>
            {legend && (
                <ul className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted">
                    {SEGMENTS.map((segment) => (
                        <li key={segment.key} className="flex items-center gap-1.5">
                            <span className={cn('size-2 rounded-full', segment.className)} aria-hidden />
                            {segment.label}
                            <span className="font-medium text-gray-900">{nombre.format(resume[segment.key])}</span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
