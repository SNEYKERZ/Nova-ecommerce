import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { useToast } from './composables/useToast';

// Nombre de la tienda actual (multi-tenant) para el <title>
let storeName = import.meta.env.VITE_APP_NAME || 'Vendex';

const syncSharedProps = (pageProps) => {
    if (pageProps?.app?.name) storeName = pageProps.app.name;
    // El token CSRF cambia tras login/logout: mantener la meta al día para fetch()
    if (pageProps?.csrf_token) {
        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', pageProps.csrf_token);
    }
};

if (import.meta.env.DEV) {
    window.addEventListener('unhandledrejection', (e) => console.error('[unhandledrejection]', e.reason));
}

createInertiaApp({
    title: (title) => (title ? `${title} · ${storeName}` : storeName),

    resolve: async (name) => {
        const pages = import.meta.glob('./pages/**/*.vue');
        const loader = pages[`./pages/${name}.vue`];
        if (!loader) throw new Error(`Página Inertia no encontrada: ${name}`);
        const page = await loader();
        return page.default;
    },

    setup({ el, App, props, plugin }) {
        syncSharedProps(props.initialPage?.props);
        router.on('navigate', (event) => syncSharedProps(event.detail.page.props));
        // 419 (token CSRF caducado) fuera del login: el servidor vuelve a la página con errors.csrf
        router.on('error', (event) => {
            if (event.detail.errors?.csrf) useToast().error(event.detail.errors.csrf);
        });

        const app = createApp({ render: () => h(App, props) });

        app.config.errorHandler = (err, instance, info) => {
            console.error('[Vue]', err, info);
        };

        app.use(plugin);
        app.mount(el);
    },

    progress: {
        color: '#c8102e',
        delay: 200,
        showSpinner: false,
    },
});
