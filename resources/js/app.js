import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
    title: (title) => `${title} - Nova Commerce`,

    resolve: async (name) => {
        const pages = import.meta.glob('./pages/**/*.vue');
        const page = await pages[`./pages/${name}.vue`]();
        return page.default;
    },

    setup({ el, App, props, plugin }) {
        createApp({
            render: () => h(App, props),
        })
            .use(plugin)
            .mount(el);
    },

    progress: {
        color: '#0f766e',
        showSpinner: true,
    },
});
