import { z } from 'zod';

/** Approbation du contrat (`ContractApprovalController::store`, règle `consent: accepted` revérifiée par le serveur). */
export const approbationSchema = z.object({
    lu: z.boolean().refine((valeur) => valeur, 'Confirmez avoir lu le contrat avant de continuer.'),
    consent: z.boolean().refine((valeur) => valeur, 'Cochez la case pour approuver votre contrat.'),
});

export type Approbation = z.infer<typeof approbationSchema>;
