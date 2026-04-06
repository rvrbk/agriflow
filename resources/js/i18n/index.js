import { createI18n } from 'vue-i18n';
import { route } from 'ziggy-js';
import http from '../lib/http';

const SUPPORTED_LOCALES = ['en', 'lg', 'sw'];
const DEFAULT_LOCALE = 'en';
const STORAGE_KEY = 'agriflow.locale';

export const i18n = createI18n({
    legacy: false,
    locale: DEFAULT_LOCALE,
    fallbackLocale: DEFAULT_LOCALE,
    messages: {},
});

async function fetchLocaleMessages(locale) {
    const response = await http.get(route('translations.show', { locale }));

    return response.data;
}

export async function setLocale(locale) {
    const targetLocale = SUPPORTED_LOCALES.includes(locale) ? locale : DEFAULT_LOCALE;
    const existingMessages = i18n.global.getLocaleMessage(targetLocale);
    const hasLoadedMessages = Object.keys(existingMessages || {}).length > 0;

    if (!hasLoadedMessages) {
        try {
            const messages = await fetchLocaleMessages(targetLocale);
            i18n.global.setLocaleMessage(targetLocale, messages);
        } catch (error) {
            if (targetLocale !== DEFAULT_LOCALE) {
                return setLocale(DEFAULT_LOCALE);
            }

            i18n.global.setLocaleMessage(DEFAULT_LOCALE, {});
        }
    }

    i18n.global.locale.value = targetLocale;
    window.localStorage.setItem(STORAGE_KEY, targetLocale);
}

export async function initializeLocale() {
    const storedLocale = window.localStorage.getItem(STORAGE_KEY);
    const initialLocale = SUPPORTED_LOCALES.includes(storedLocale) ? storedLocale : DEFAULT_LOCALE;

    await setLocale(initialLocale);
}

export function getSupportedLocales() {
    return [...SUPPORTED_LOCALES];
}
