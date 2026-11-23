import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

// This Blade page can be built independently of the main Inertia application.
export default defineConfig({
    publicDir: false,
    plugins: [react()],
    build: {
        outDir: 'public/build/admission-sms',
        emptyOutDir: true,
        rollupOptions: {
            input: 'resources/js/admission-sms-editor.jsx',
            output: { entryFileNames: 'editor.js' },
        },
    },
});
