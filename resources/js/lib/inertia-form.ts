import type { RequestPayload } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import type { FieldValues, Path, UseFormSetError } from 'react-hook-form';
import { toast } from 'sonner';

type EnvoiOptions<T extends FieldValues> = {
    method?: 'post' | 'put' | 'patch' | 'delete';
    /** Erreurs Laravel à afficher sous les champs du formulaire (les autres partent en toast). */
    setError?: UseFormSetError<T>;
    champs?: readonly Path<T>[];
    /** Envoi multipart (fichiers) : PUT/PATCH passent alors en POST + `_method`. */
    forceFormData?: boolean;
    preserveScroll?: boolean;
    only?: string[];
    onSuccess?: () => void;
    /** Erreurs restantes après répartition dans les champs. */
    onError?: (erreurs: Record<string, string>) => void;
};

/**
 * Envoie un formulaire vers une action Laravel existante avec Inertia et résout la promesse à la fin.
 * Les actions répondent par `back()->with('success'|'error', …)` ou `withErrors(...)` :
 * le message de succès est affiché par `AdminLayout`, les erreurs de champ vont dans le formulaire.
 */
export function envoyer<T extends FieldValues>(url: string, data: RequestPayload, options: EnvoiOptions<T> = {}): Promise<boolean> {
    const { method = 'post', setError, champs = [], forceFormData = false, preserveScroll = true, only, onSuccess, onError } = options;
    const methode = forceFormData && (method === 'put' || method === 'patch') ? 'post' : method;
    const donnees = forceFormData && methode !== method && data && typeof data === 'object' && !(data instanceof FormData)
        ? { ...(data as Record<string, unknown>), _method: method }
        : data;

    return new Promise((resolve) => {
        router.visit(url, {
            method: methode,
            data: donnees as RequestPayload,
            forceFormData,
            preserveScroll,
            preserveState: true,
            only,
            onSuccess: (page) => {
                // Un `back()->with('error', …)` n'est pas une erreur de validation : on le considère comme un échec.
                const flash = (page.props as { flash?: { error?: string | null } }).flash;
                if (flash?.error) {
                    resolve(false);
                    return;
                }
                onSuccess?.();
                resolve(true);
            },
            onError: (erreurs) => {
                const restantes: Record<string, string> = {};
                Object.entries(erreurs).forEach(([cle, message]) => {
                    if (setError && (champs as readonly string[]).includes(cle)) {
                        setError(cle as Path<T>, { type: 'server', message });
                    } else {
                        restantes[cle] = message;
                    }
                });
                if (onError) onError(restantes);
                else Object.values(restantes).forEach((message) => toast.error(message));
                resolve(false);
            },
        });
    });
}
