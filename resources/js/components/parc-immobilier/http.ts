import axios, { isAxiosError } from 'axios';

/** Appels JSON vers les actions existantes du parc (`toggleStatus`, `updateChambre`) avec le jeton CSRF de la page. */
export const parcHttp = axios.create({
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

parcHttp.interceptors.request.use((config) => {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
    if (token) {
        config.headers.set('X-CSRF-TOKEN', token);
    }
    return config;
});

/** Message lisible d'une réponse d'erreur `{ success: false, message, errors? }`. */
export function messageErreur(error: unknown, defaut: string): string {
    if (isAxiosError(error)) {
        const data = error.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
        const premiere = data?.errors ? Object.values(data.errors)[0]?.[0] : undefined;
        return premiere ?? data?.message ?? defaut;
    }
    return defaut;
}
