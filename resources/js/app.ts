import { createInertiaApp } from '@inertiajs/vue3';
import { listenForInstallPrompt } from '@/composables/useInstallPrompt';
import { syncServiceWorker } from '@/lib/serviceWorker';

// Chromium fires the install prompt once, early; catch it before any page renders.
listenForInstallPrompt();

const appName = import.meta.env.VITE_APP_NAME || 'InvoiceInk';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    // Head tags a page sends in its `head` prop (the description and canonical
    // of the sign-in pages search engines index). app.blade.php prints them for
    // the first load; this keeps them in step on client-side visits.
    serverHead: true,
    progress: {
        color: '#7c3aed',
    },
});

syncServiceWorker();
