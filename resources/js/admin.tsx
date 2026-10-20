import '../css/admin.css';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/components/ui/sonner';
import { definirSysteme, systeme, type Systeme } from '@/lib/systeme';

// Nom du CROU et format des montants (Configuration) : prop partagée `systeme`, relue à chaque visite.
router.on('navigate', (event) => definirSysteme(event.detail.page.props.systeme as Systeme | undefined));

// Les routes protégées par `ValidateCsrfToken` (transition de dossier, PMR, paramétrage des entrées…) attendent
// le jeton de session : aucun cookie XSRF-TOKEN n'étant émis, on le joint depuis la balise meta à chaque envoi.
router.on('before', (event) => {
    const visite = event.detail.visit;
    const jeton = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
    if (jeton && visite.method !== 'get') visite.headers = { ...visite.headers, 'X-CSRF-TOKEN': jeton };
});

createInertiaApp({
    title: (title) => (title ? `${title} | ${systeme().nom}` : systeme().nom),
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        definirSysteme(props.initialPage.props.systeme as Systeme | undefined);
        // Toaster avant <App> : il s'abonne aux toasts avant que la page n'émette ceux des messages flash.
        createRoot(el).render(
            <>
                <Toaster />
                <App {...props} />
            </>,
        );
    },
    progress: { color: '#86BB46' },
});
