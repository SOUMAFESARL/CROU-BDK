import { nombre } from '@/lib/text';

/** Colonne « Utilisation » des référentiels géographiques : étudiants + autres fiches (campus, comptes, configuration). */
export function Utilisation({ etudiants, autres }: { etudiants: number; autres: number }) {
    if (etudiants + autres === 0) return <span className="text-muted">Inutilisée</span>;

    return (
        <span className="text-gray-900">
            {nombre(etudiants)} étudiant(s)
            {autres > 0 && (
                <span className="text-muted" title="Campus, comptes administrateurs ou configuration de l’application">
                    {' '}
                    · {nombre(autres)} autre(s)
                </span>
            )}
        </span>
    );
}
