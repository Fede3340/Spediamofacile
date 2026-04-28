import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import AppLayout from '@/Layouts/AppLayout.vue';

createInertiaApp({
	title: (title) => title ? `${title} | SpediamoFacile` : 'SpediamoFacile',
	resolve: (name) => {
		const page = resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'));
		page.then((module) => {
			module.default.layout = module.default.layout || AppLayout;
		});
		return page;
	},
	setup({ el, App, props, plugin }) {
		createApp({ render: () => h(App, props) })
			.use(plugin)
			.mount(el);
	},
	progress: {
		color: '#E44203',
		showSpinner: false,
	},
});
