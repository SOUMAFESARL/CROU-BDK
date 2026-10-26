import type { ChangeEvent } from 'react';
import { z } from 'zod';

/** Longueur d'un numéro (format ivoirien), alignée sur `App\Support\Telephone`. */
export const CHIFFRES_TELEPHONE = 10;

export const MESSAGE_TELEPHONE = 'Le numéro de téléphone doit contenir exactement 10 chiffres.';

/**
 * Garde 10 chiffres au plus et les affiche par paires : « 0700000000 » → « 07 00 00 00 00 ».
 * Un numéro enregistré avec l'indicatif (« +225 07… », 13 chiffres) perd son indicatif.
 */
export function formatTelephone(valeur: string | null | undefined) {
    let chiffres = (valeur ?? '').replace(/\D/g, '');
    if (chiffres.length === 13 && chiffres.startsWith('225')) chiffres = chiffres.slice(3);
    return (chiffres.slice(0, CHIFFRES_TELEPHONE).match(/.{1,2}/g) ?? []).join(' ');
}

/** `onChange` d'un champ enregistré (`register`) : reformate la saisie avant de la transmettre au formulaire. */
export const avecFormatTelephone =
    <T extends { onChange: (event: ChangeEvent<HTMLInputElement>) => unknown }>(champ: T): T => ({
        ...champ,
        onChange: (event: ChangeEvent<HTMLInputElement>) => {
            event.target.value = formatTelephone(event.target.value);
            return champ.onChange(event);
        },
    });

/** Attributs communs des champs téléphone (10 chiffres affichés par paires, soit 14 caractères). */
export const PROPS_TELEPHONE = { type: 'tel', inputMode: 'numeric', autoComplete: 'tel-national', maxLength: 14, placeholder: '07 00 00 00 00' } as const;

const estValide = (valeur: string) => valeur.replace(/\D/g, '').length === CHIFFRES_TELEPHONE;

/** Téléphone obligatoire : la saisie reste formatée, le serveur retire les espaces. */
export const telephoneObligatoire = (messageRequis = 'Le numéro de téléphone est obligatoire.') =>
    z.string().trim().min(1, messageRequis).refine(estValide, MESSAGE_TELEPHONE);

/** Téléphone facultatif : vide accepté, sinon 10 chiffres. */
export const telephoneFacultatif = () =>
    z
        .string()
        .trim()
        .refine((valeur) => valeur === '' || estValide(valeur), MESSAGE_TELEPHONE);
