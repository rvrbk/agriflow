import './bootstrap';
import './lib/http';
import { createApp } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import router from './router';
import App from './App.vue';
import { i18n, initializeLocale } from './i18n';
import { initializeOfflineQueue } from './services/offlineQueue';

const isLocalDevHost = window.location.hostname === 'localhost'
	|| window.location.hostname === '127.0.0.1';

const isViteDev = import.meta.env.DEV;

const shouldForceServiceWorker = window.__AGRIFLOW_FORCE_SW__ === true;

if (isViteDev && isLocalDevHost && !shouldForceServiceWorker) {
	if ('serviceWorker' in navigator) {
		void navigator.serviceWorker.getRegistrations().then((registrations) => {
			registrations.forEach((registration) => {
				void registration.unregister();
			});
		});
	}
} else {
	if ('serviceWorker' in navigator) {
		void navigator.serviceWorker.getRegistrations().then((registrations) => {
			registrations
				.filter((registration) => registration.scope.includes('/build/'))
				.forEach((registration) => {
					void registration.unregister();
				});

			void navigator.serviceWorker.register('/sw.js', { scope: '/' });
		});
	}
}

initializeOfflineQueue();

const app = createApp(App);

app
	.use(router)
	.use(i18n)
	.use(ZiggyVue);

await initializeLocale();

app.mount('#app');
