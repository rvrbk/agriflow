<template>
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_#eef5e2_0%,_#f6f6ef_42%)] text-[#1f2a1d]">
        <header class="no-print sticky top-0 z-40 flex h-[68px] items-center justify-between gap-4 border-b border-[#d8d8c9] bg-[#fffdf5]/95 px-5 backdrop-blur-[6px]">
            <div class="flex items-center gap-3">
                <div
                    class="grid h-[42px] w-[42px] place-items-center rounded-[11px] bg-[linear-gradient(140deg,_#214f34,_#317f4f)] text-sm font-extrabold tracking-[0.04em] text-[#f8fff8] shadow-[0_8px_16px_rgb(42_91_57_/_24%)]"
                    aria-hidden="true"
                >
                    AF
                </div>
                <div>
                    <p class="m-0 text-base font-bold tracking-[0.02em]">AgriFlow</p>
                    <p class="m-0 text-xs uppercase tracking-[0.09em] text-[#5d684f]">{{ t('header.subtitle') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <label class="sr-only" for="locale-selector">{{ t('language') }}</label>
                <select
                    id="locale-selector"
                    v-model="selectedLocale"
                    class="h-[38px] rounded-lg border border-[#bed8bf] bg-[#f8fdf6] px-2 text-sm"
                    @change="changeLocale"
                >
                    <option v-for="option in languageOptions" :key="option.code" :value="option.code">
                        {{ option.label }}
                    </option>
                </select>

                <button
                    v-if="showMenu"
                    type="button"
                    class="inline-flex h-[38px] w-[38px] cursor-pointer flex-col justify-center gap-1 rounded-lg border border-[#bed8bf] bg-[#f8fdf6]"
                    :aria-expanded="isMenuOpen"
                    :aria-label="t('header.toggle_menu')"
                    @click="toggleMenu"
                >
                    <span class="mx-auto h-0.5 w-[18px] rounded-full bg-[#2f6e4a]" />
                    <span class="mx-auto h-0.5 w-[18px] rounded-full bg-[#2f6e4a]" />
                    <span class="mx-auto h-0.5 w-[18px] rounded-full bg-[#2f6e4a]" />
                </button>

                <button
                    v-if="auth.state.user"
                    type="button"
                    class="inline-flex h-[38px] items-center gap-1.5 rounded-lg bg-[#d97706] px-3 py-2 text-sm font-medium text-white hover:bg-[#b45309]"
                    @click="handleLogout"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    {{ t('header.logout') }}
                </button>
            </div>
        </header>

        <nav
            v-if="showMenu"
            :aria-label="t('menu.primary_aria')"
            class="no-print fixed left-0 right-0 top-[68px] z-[35] overflow-hidden border-b border-[#d8d8c9] bg-[#fffdf5] shadow-[0_14px_28px_rgb(16_29_18_/_16%)] transition duration-200"
            :class="isMenuOpen ? 'translate-y-0 scale-y-100 opacity-100' : '-translate-y-2 scale-y-95 opacity-0 pointer-events-none'"
            @click="collapseOnMenuLinkClick"
        >
            <div class="flex flex-col gap-4 px-4 py-4 md:grid md:grid-cols-2 md:px-5 md:pb-5">
                <div class="rounded-xl border border-[#dde5d7] bg-[linear-gradient(180deg,_#fcfff8_0%,_#f4f8ed_100%)] p-3.5">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.08em] text-[#46633f]">{{ t('menu.main') }}</p>
                    <RouterLink to="/" class="mt-1.5 block w-full rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]">
                        {{ t('menu.dashboard') }}
                    </RouterLink>
                    <RouterLink to="/harvests" class="mt-1.5 block w-full rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]">
                        {{ t('menu.harvests') }}
                    </RouterLink>
                    <RouterLink to="/sales" class="mt-1.5 block w-full rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]">
                        {{ t('menu.sales') }}
                    </RouterLink>
                    <RouterLink to="/sales-history" class="mt-1.5 block w-full rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]">
                        {{ t('menu.sales_history') }}
                    </RouterLink>
                </div>

                <div class="rounded-xl border border-[#dde5d7] bg-[linear-gradient(180deg,_#fcfff8_0%,_#f4f8ed_100%)] p-3.5">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.08em] text-[#46633f]">{{ t('menu.management') }}</p>
                    <RouterLink to="/products" class="mt-1.5 block w-full rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]">
                        {{ t('menu.products') }}
                    </RouterLink>
                    <RouterLink to="/inventory" class="mt-1.5 block w-full rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]">
                        {{ t('menu.inventory') }}
                    </RouterLink>
                    <RouterLink to="/corporations" class="mt-1.5 block w-full rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]">
                        {{ t('menu.corporations') }}
                    </RouterLink>
                    <RouterLink to="/warehouses" class="mt-1.5 block w-full rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]">
                        {{ t('menu.warehouses') }}
                    </RouterLink>
                    <RouterLink to="/users" class="mt-1.5 block w-full rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]">
                        {{ t('menu.users') }}
                    </RouterLink>
                </div>
            </div>
        </nav>

        <main class="min-w-0 p-4 md:p-5">
            <RouterView />
        </main>
    </div>
</template>

<script setup>
import { RouterLink, useRouter } from 'vue-router';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { getSupportedLocales, setLocale } from './i18n';
import { useAuthStore } from './stores/auth';

const router = useRouter();
const isMenuOpen = ref(false);
const { t, locale } = useI18n();
const auth = useAuthStore();
const supportedLocales = getSupportedLocales();
const selectedLocale = ref(locale.value);

const showMenu = computed(() => {
    return auth.state.user && router.currentRoute.value.name !== 'login' && router.currentRoute.value.name !== 'set-password';
});

const languageOptions = computed(() => {
    return supportedLocales.map((code) => ({
        code,
        label: t(`languages.${code}`),
    }));
});

watch(locale, (value) => {
    selectedLocale.value = value;
});

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
};

const changeLocale = async () => {
    await setLocale(selectedLocale.value);
};

const handleLogout = async () => {
    await auth.signOut();
    await router.push({ name: 'login' });
};

const collapseOnMenuLinkClick = (event) => {
    if (event.target.closest('a')) {
        isMenuOpen.value = false;
    }
};
</script>