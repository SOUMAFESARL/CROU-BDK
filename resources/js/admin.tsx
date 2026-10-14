import '../css/admin.css';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/components/ui/sonner';

const appName = 'CROU Bondoukou';

createInertiaApp({
    title: (title) => (title ? `${title} | ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
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
