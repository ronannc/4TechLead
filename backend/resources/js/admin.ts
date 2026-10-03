import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, type DefineComponent } from 'vue';
import { configureAdminHttpErrors } from './admin/core/http/adminHttpErrors';

configureAdminHttpErrors();

createInertiaApp({
    title: (title) => (title ? `${title} · ForTechLead Admin` : 'ForTechLead Admin'),
    resolve: (name) =>
        resolvePageComponent(
            `./admin/features/${name}.vue`,
            import.meta.glob('./admin/features/**/*.vue'),
        ) as Promise<DefineComponent>,
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
