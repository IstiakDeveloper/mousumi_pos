import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { setThemeOnLoad } from './theme';
// Import layouts
import UserLayout from './Layouts/UserLayout.vue';

// Import global components
import ConfirmDialog from './Components/ConfirmDialog.vue';
import Alert from './Components/Alert.vue';
import LoadingSpinner from './Components/LoadingSpinner.vue';

// Import FontAwesome
import '@fortawesome/fontawesome-free/css/all.min.css';


const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        );



        return page;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Register global components
        app.component('ConfirmDialog', ConfirmDialog);
        app.component('Alert', Alert);
        app.component('LoadingSpinner', LoadingSpinner);

        // Register plugins
        app.use(plugin);
        app.use(ZiggyVue);

        // Global error handler
        app.config.errorHandler = (error) => {
            console.error('Global error:', error);
            // You can add more error handling logic here
        };

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
        showSpinner: true,
    },
});

setThemeOnLoad()
