import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'WhatsApp Sender Pro';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Set RTL direction and language based on locale
        const locale = props.initialPage.props.locale || 'en';
        const isRTL = locale === 'ar';

        document.documentElement.setAttribute('lang', locale);
        document.documentElement.setAttribute('dir', isRTL ? 'rtl' : 'ltr');

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#25D366',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
