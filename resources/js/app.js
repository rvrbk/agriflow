import './bootstrap';
import './lib/http';
import { createApp } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { registerSW } from 'virtual:pwa-register';
import router from './router';
import App from './App.vue';
import { i18n, initializeLocale } from './i18n';
import { initializeOfflineQueue } from './services/offlineQueue';

const isLocalHost = window.location.hostname === 'localhost'
	|| window.location.hostname === '127.0.0.1'
	|| window.location.hostname.endsWith('.test');

if (isLocalHost) {
	if ('serviceWorker' in navigator) {
		void navigator.serviceWorker.getRegistrations().then((registrations) => {
			registrations.forEach((registration) => {
				void registration.unregister();
			});
		});
	}
} else {
	registerSW({ immediate: true });
}

initializeOfflineQueue();

const app = createApp(App);

app
	.use(router)
	.use(i18n)
	.use(ZiggyVue);

await initializeLocale();

app.mount('#app');
