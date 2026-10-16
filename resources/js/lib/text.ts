/** Texte en minuscules sans accents, pour les recherches locales (« Élevé » trouve « eleve »). */
export const sansAccents = (texte: string | null | undefined) =>
    (texte ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase();

/** Nombre au format français (espaces entre les milliers). */
export const nombre = (valeur: number | string | null | undefined, decimales = 0) =>
    new Intl.NumberFormat('fr-FR', { maximumFractionDigits: decimales }).format(Number(valeur ?? 0));

/** Date `aaaa-mm-jj` (ou ISO) → jj/mm/aaaa ; « — » si vide. */
export const dateFr = (valeur: string | null | undefined) => {
    if (!valeur) return '—';
    const [annee, mois, jour] = valeur.slice(0, 10).split('-');
    return jour ? `${jour}/${mois}/${annee}` : valeur;
};

/** Initiales d'un nom (avatar). */
export const initiales = (nom: string | null | undefined) =>
    (nom ?? '')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((mot) => Array.from(mot)[0] ?? '')
        .join('')
        .toUpperCase() || '?';
