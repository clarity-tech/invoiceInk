import { createInertiaApp } from '@inertiajs/vue3';
import { listenForInstallPrompt } from '@/composables/useInstallPrompt';
import { syncServiceWorker } from '@/lib/serviceWorker';

// Chromium fires the install prompt once, early; catch it before any page renders.
listenForInstallPrompt();

const appName = import.meta.env.VITE_APP_NAME || 'InvoiceInk';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#7c3aed',
    },
});

syncServiceWorker();
