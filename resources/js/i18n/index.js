import { createI18n } from 'vue-i18n';
import { route } from 'ziggy-js';
import http from '../lib/http';

const SUPPORTED_LOCALES = ['en', 'lg', 'sw'];
const DEFAULT_LOCALE = 'en';
const STORAGE_KEY = 'agriflow.locale';
const MESSAGE_CACHE_PREFIX = 'agriflow.localeMessages.';

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

function readCachedMessages(locale) {
    try {
        const raw = window.localStorage.getItem(`${MESSAGE_CACHE_PREFIX}${locale}`);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch {
        return null;
    }
}

function writeCachedMessages(locale, messages) {
    if (!messages || typeof messages !== 'object') {
        return;
    }

    window.localStorage.setItem(`${MESSAGE_CACHE_PREFIX}${locale}`, JSON.stringify(messages));
}

export async function setLocale(locale) {
    const targetLocale = SUPPORTED_LOCALES.includes(locale) ? locale : DEFAULT_LOCALE;
    const existingMessages = i18n.global.getLocaleMessage(targetLocale);
    const hasLoadedMessages = Object.keys(existingMessages || {}).length > 0;

    if (!hasLoadedMessages) {
        try {
            const messages = await fetchLocaleMessages(targetLocale);
            i18n.global.setLocaleMessage(targetLocale, messages);
            writeCachedMessages(targetLocale, messages);
        } catch (error) {
            const cachedMessages = readCachedMessages(targetLocale);

            if (cachedMessages && Object.keys(cachedMessages).length > 0) {
                i18n.global.setLocaleMessage(targetLocale, cachedMessages);
                i18n.global.locale.value = targetLocale;
                window.localStorage.setItem(STORAGE_KEY, targetLocale);
                return;
            }

            if (targetLocale !== DEFAULT_LOCALE) {
                return setLocale(DEFAULT_LOCALE);
            }

            const fallbackCachedMessages = readCachedMessages(DEFAULT_LOCALE);
            i18n.global.setLocaleMessage(DEFAULT_LOCALE, fallbackCachedMessages ?? {});
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
