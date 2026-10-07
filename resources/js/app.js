import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import axios from 'axios';

// Add default CSRF header
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Add redirect handler for 401 Unauthorized
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Check if we're already on the login page to avoid infinite redirect loop
            if (!window.location.pathname.includes('/login')) {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

createInertiaApp({
    title: (title) => title ? `${title} - KCC Fleet` : 'KCC Corporate Fleet Management',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page = pages[`./Pages/${name}.vue`];
        if (!page) {
            throw new Error(`Page component "./Pages/${name}.vue" not found.`);
        }
        return page.default;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#2563eb', // Blue-600 Corporate Brand
        showSpinner: true,
    },
});
