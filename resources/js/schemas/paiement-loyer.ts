import { z } from "zod";

/**
 * Paiement en ligne du loyer depuis « Mes paiements » (route etudiants.online-payments.store).
 * Confort de saisie uniquement : le serveur revérifie le loyer, le solde, le réseau et le numéro.
 */
export function paiementLoyerSchema(reseaux: readonly string[], solde: number) {
    return z
        .object({
            reseau: z
                .string()
                .refine(
                    (valeur) => reseaux.includes(valeur),
                    "Choisissez votre moyen de paiement.",
                ),
            montant: z
                .string()
                .trim()
                .regex(/^[0-9]+$/, "Indiquez un montant en francs CFA, sans décimales.")
                .refine((valeur) => Number(valeur) >= 1, "Le montant doit être supérieur à zéro.")
                .refine(
                    (valeur) => Number(valeur) <= solde,
                    "Le montant ne doit pas dépasser le solde restant.",
                ),
            /** 10 chiffres du numéro ivoirien (espaces permis) ; l'indicatif 225 est ajouté à l'envoi. */
            telephone: z
                .string()
                .regex(/^(\s*[0-9]){10}\s*$/, "Indiquez votre numéro à 10 chiffres (ex. : 07 00 00 00 00)."),
        })
        .superRefine(({ reseau, montant }, ctx) => {
            // CinetPay n'accepte que les multiples de 5 FCFA (même règle que OnlinePaymentService::start).
            if (reseau === "cinetpay" && Number(montant) % 5 !== 0) {
                ctx.addIssue({
                    code: "custom",
                    path: ["montant"],
                    message: "CinetPay accepte uniquement les montants multiples de 5 FCFA.",
                });
            }
        });
}

export type PaiementLoyer = z.infer<ReturnType<typeof paiementLoyerSchema>>;

/** Champs validés à chaque étape de l'assistant. */
export const CHAMPS_ETAPE: (keyof PaiementLoyer)[][] = [
    ["reseau"],
    ["montant", "telephone"],
    [],
];

/** Clés d'erreur du serveur → champ du formulaire. */
export const CHAMP_SERVEUR: Record<string, keyof PaiementLoyer> = {
    provider: "reseau",
    amount: "montant",
    phone: "telephone",
};
