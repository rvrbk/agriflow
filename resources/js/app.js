import './bootstrap';
import './lib/http';
import { createApp } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { registerSW } from 'virtual:pwa-register';
import router from './router';
import App from './App.vue';
import { i18n, initializeLocale } from './i18n';

registerSW({ immediate: true });

const app = createApp(App);

app
	.use(router)
	.use(i18n)
	.use(ZiggyVue);

await initializeLocale();

app.mount('#app');
