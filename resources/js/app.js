import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

// Global error handler para capturar errores de renderizado
window.addEventListener('error', (e) => {
    console.error('[GLOBAL ERROR]', e.message, e.filename, e.lineno);
    document.body.innerHTML = `<pre style="padding:20px;background:#fee;color:#c00;font-size:14px;">
        <strong>ERROR:</strong> ${e.message}
        <br><em>${e.filename}:${e.lineno}</em>
    </pre>`;
});

window.addEventListener('unhandledrejection', (e) => {
    console.error('[UNHANDLED REJECTION]', e.reason);
});

createInertiaApp({
    title: (title) => `${title} - ${import.meta.env.VITE_APP_NAME || 'Vendex'}`,

    resolve: async (name) => {
        const pages = import.meta.glob('./pages/**/*.vue');
        const page = await pages[`./pages/${name}.vue`]();
        return page.default;
    },

    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () => h(App, props),
        });

        app.config.errorHandler = (err, instance, info) => {
            console.error('[VUE ERROR]', err, info);
        };

        app.use(plugin);
        app.mount(el);
    },

    progress: {
        color: '#0f766e',
        showSpinner: true,
    },
});
