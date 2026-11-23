import type { ZodType } from 'zod';

/** Champ obligatoire encore vide, avec l'étape (index) où le renseigner. */
export type ChampManquant = { cle: string; libelle: string; etape: number };

/**
 * Valeur considérée comme « non renseignée » : vide, espaces seuls (balises d'éditeur comprises), liste vide, aucun fichier,
 * ou case non cochée (seulement listée quand le schéma l'exige : conditions à accepter…).
 */
export const estVide = (valeur: unknown): boolean =>
    valeur === undefined ||
    valeur === null ||
    valeur === false ||
    (typeof valeur === 'string' && valeur.replace(/<[^>]*>|&nbsp;/g, '').trim() === '') ||
    (Array.isArray(valeur) && valeur.length === 0) ||
    (typeof FileList !== 'undefined' && valeur instanceof FileList && valeur.length === 0);

const lire = (objet: unknown, chemin: readonly PropertyKey[]): unknown =>
    chemin.reduce<unknown>((valeur, cle) => (valeur === null || typeof valeur !== 'object' ? undefined : (valeur as Record<PropertyKey, unknown>)[cle]), objet);

/**
 * Formulaires en étapes : la navigation est libre, mais l'enregistrement reste bloqué tant qu'un champ obligatoire est vide.
 * Les champs manquants sont déduits du schéma zod (règles conditionnelles comprises) : tout problème porté par un champ vide.
 * Les valeurs saisies mais invalides ne sont pas listées : elles sont signalées sous le champ à l'enregistrement.
 *
 * @param etapes  champs de chaque étape (premier segment du chemin, ex. `lignes` pour `lignes.0.nom`)
 * @param libelles libellé affiché par champ (à défaut : le message d'erreur du schéma)
 */
export function champsManquants(
    schema: ZodType,
    valeurs: unknown,
    etapes: readonly (readonly string[])[],
    libelles: Record<string, string> = {},
): ChampManquant[] {
    const resultat = schema.safeParse(valeurs);
    if (resultat.success) return [];
    const vus = new Set<string>();
    // Un même libellé dans une même étape (ex. l'état de plusieurs éléments d'une liste) n'est affiché qu'une fois, avec le nombre.
    const groupes = new Map<string, ChampManquant & { nombre: number }>();
    resultat.error.issues.forEach((probleme) => {
        if (probleme.path.length === 0 || !estVide(lire(valeurs, probleme.path))) return;
        const cle = probleme.path.map(String).join('.');
        const racine = String(probleme.path[0]);
        if (vus.has(cle)) return;
        vus.add(cle);
        const etape = Math.max(0, etapes.findIndex((champs) => champs.includes(cle) || champs.includes(racine)));
        const libelle = libelles[cle] ?? libelles[racine] ?? probleme.message;
        const groupe = groupes.get(`${etape}|${libelle}`);
        if (groupe) groupe.nombre += 1;
        else groupes.set(`${etape}|${libelle}`, { cle, libelle, etape, nombre: 1 });
    });
    return [...groupes.values()]
        .map(({ nombre, ...item }) => (nombre > 1 ? { ...item, libelle: `${item.libelle} (${nombre} champs)` } : item))
        .sort((a, b) => a.etape - b.etape);
}
