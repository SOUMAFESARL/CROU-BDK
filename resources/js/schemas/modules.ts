import { z } from 'zod';

/** Schéma du formulaire de module (`ModuleController::validated()` reste la source de vérité). */

const texte = (max: number, label: string) => z.string().trim().max(max, `${label} : ${max} caractères au maximum.`);

export const moduleSchema = z.object({
    parent_id: z.string(),
    name: texte(120, 'Le nom').min(1, 'Le nom du module est obligatoire.'),
    slug: texte(160, 'L’identifiant')
        .min(1, 'L’identifiant technique est obligatoire.')
        .regex(/^[a-z0-9.-]+$/, 'Lettres minuscules, chiffres, points et tirets uniquement (ex. hebergement.sanctions).'),
    description: texte(1000, 'La description'),
    icon: texte(100, 'L’icône'),
    route_name: texte(180, 'Le nom de la route'),
    menu_key: texte(80, 'La clé du menu'),
    submenu_key: texte(80, 'La clé du sous-menu'),
    position: z
        .string()
        .trim()
        .min(1, 'La position est obligatoire.')
        .regex(/^\d+$/, 'Saisissez un nombre entier positif.')
        .refine((v) => Number(v) <= 9999, 'La position ne doit pas dépasser 9999.'),
    actions: z.array(z.string()).min(1, 'Cochez au moins une action : sans action, aucune permission n’est générée.'),
    is_active: z.boolean(),
    show_in_menu: z.boolean(),
});

export type ModuleValeurs = z.infer<typeof moduleSchema>;

/** « Gestion des sanctions » → « gestion-des-sanctions ». */
export const enSlug = (valeur: string) =>
    valeur
        .toLocaleLowerCase('fr')
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
