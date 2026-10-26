import { z } from 'zod';
import { telephoneFacultatif, telephoneObligatoire } from '@/lib/telephone';

/** Coordonnées modifiables par l'étudiant (`dashboardController::updateProfile`, qui revérifie tout). */
export const coordonneesSchema = z.object({
    telephone1: telephoneObligatoire('Le numéro de téléphone principal est obligatoire.'),
    telephone2: telephoneFacultatif(),
    email2: z
        .string()
        .trim()
        .max(255, 'L’adresse e-mail ne doit pas dépasser 255 caractères.')
        .refine((valeur) => valeur === '' || z.email().safeParse(valeur).success, 'L’adresse e-mail secondaire n’est pas valide.'),
});

export type Coordonnees = z.infer<typeof coordonneesSchema>;

/** Règles de `SystemSecurity::passwordRule()` (paramétrage système). */
export type ReglesMotDePasse = { min: number; mixed: boolean; number: boolean; symbol: boolean };

export type RegleCle = keyof ReglesMotDePasse;

/** Libellé et test de chaque règle active ; mêmes classes de caractères que Illuminate\Validation\Rules\Password. */
export function reglesActives(regles: ReglesMotDePasse): { cle: RegleCle; libelle: string; test: (v: string) => boolean }[] {
    return [
        { cle: 'min' as const, libelle: `${regles.min} caractères minimum`, test: (v: string) => v.length >= regles.min, actif: true },
        { cle: 'mixed' as const, libelle: 'Une majuscule et une minuscule', test: (v: string) => /\p{Ll}/u.test(v) && /\p{Lu}/u.test(v), actif: regles.mixed },
        { cle: 'number' as const, libelle: 'Au moins un chiffre', test: (v: string) => /\p{N}/u.test(v), actif: regles.number },
        { cle: 'symbol' as const, libelle: 'Au moins un caractère spécial', test: (v: string) => /[\p{Z}\p{S}\p{P}]/u.test(v), actif: regles.symbol },
    ]
        .filter((regle) => regle.actif)
        .map(({ cle, libelle, test }) => ({ cle, libelle, test }));
}

/** Nouveau mot de passe (`dashboardController::updatePassword`, sac d'erreurs `passwordChange`). */
export function motDePasseSchema(regles: ReglesMotDePasse) {
    const actives = reglesActives(regles);

    return z
        .object({
            password: z
                .string()
                .min(1, 'Renseignez le nouveau mot de passe.')
                .refine((v) => actives.every((regle) => regle.test(v)), 'Le mot de passe ne respecte pas toutes les règles ci-dessous.'),
            password_confirmation: z.string().min(1, 'Confirmez le nouveau mot de passe.'),
        })
        .refine((v) => v.password === v.password_confirmation, {
            path: ['password_confirmation'],
            message: 'La confirmation ne correspond pas au nouveau mot de passe.',
        });
}

export type MotDePasse = z.infer<ReturnType<typeof motDePasseSchema>>;
