import { useState } from 'react';

/** Saisies comparées sans casse, espaces, tirets, points ni barres (comme la recherche du serveur). */
export const normaliserSaisie = (valeur: string) => valeur.replace(/[\s\-./\\]+/g, '').toUpperCase();

function lire(cle: string, max: number): string[] {
    try {
        const valeurs: unknown = JSON.parse(window.localStorage.getItem(cle) ?? '[]');
        return Array.isArray(valeurs) ? valeurs.filter((valeur): valeur is string => typeof valeur === 'string' && valeur.trim() !== '').slice(0, max) : [];
    } catch {
        return []; // stockage indisponible ou illisible : aucune proposition
    }
}

/** Dernières saisies d'un champ, gardées dans le navigateur (la plus récente d'abord, sans doublon). */
export function useSaisiesMemorisees(cle: string, max = 5) {
    const [saisies, setSaisies] = useState<string[]>(() => lire(cle, max));

    const enregistrer = (suivantes: string[]) => {
        setSaisies(suivantes);
        try {
            if (suivantes.length > 0) window.localStorage.setItem(cle, JSON.stringify(suivantes));
            else window.localStorage.removeItem(cle);
        } catch {
            /* stockage indisponible */
        }
    };

    // Relues avant chaque modification : un autre onglet a pu les changer.
    const sans = (valeur: string) => lire(cle, max).filter((saisie) => normaliserSaisie(saisie) !== normaliserSaisie(valeur));

    return {
        saisies,
        memoriser: (valeur: string) => {
            const saisie = valeur.trim().toUpperCase();
            if (saisie) enregistrer([saisie, ...sans(saisie)].slice(0, max));
        },
        oublier: (valeur: string) => enregistrer(sans(valeur)),
    };
}
