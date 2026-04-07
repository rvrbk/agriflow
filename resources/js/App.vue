<template>
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_#eef5e2_0%,_#f6f6ef_42%)] text-[#1f2a1d]">
        <header class="sticky top-0 z-40 flex h-[68px] items-center justify-between gap-4 border-b border-[#d8d8c9] bg-[#fffdf5]/95 px-5 backdrop-blur-[6px]">
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
            </div>
        </header>

        <nav
            :aria-label="t('menu.primary_aria')"
            class="fixed left-0 right-0 top-[68px] z-[35] overflow-hidden border-b border-[#d8d8c9] bg-[#fffdf5] shadow-[0_14px_28px_rgb(16_29_18_/_16%)] transition duration-200"
            :class="isMenuOpen ? 'translate-y-0 scale-y-100 opacity-100' : '-translate-y-2 scale-y-95 opacity-0 pointer-events-none'"
            @click="collapseOnMenuLinkClick"
        >
            <div class="grid grid-cols-1 gap-4 px-4 py-4 md:grid-cols-2 md:px-5 md:pb-5">
                <div class="rounded-xl border border-[#dde5d7] bg-[linear-gradient(180deg,_#fcfff8_0%,_#f4f8ed_100%)] p-3.5">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.08em] text-[#46633f]">{{ t('menu.main') }}</p>
                    <button class="mt-1.5 block w-full cursor-pointer rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]" type="button">
                        {{ t('menu.dashboard') }}
                    </button>
                    <button class="mt-1.5 block w-full cursor-pointer rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]" type="button">
                        {{ t('menu.inventory') }}
                    </button>
                    <RouterLink to="/harvests" class="mt-1.5 block w-full rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]">
                        {{ t('menu.harvests') }}
                    </RouterLink>
                </div>

                <div class="rounded-xl border border-[#dde5d7] bg-[linear-gradient(180deg,_#fcfff8_0%,_#f4f8ed_100%)] p-3.5">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.08em] text-[#46633f]">{{ t('menu.management') }}</p>
                    <RouterLink to="/products" class="mt-1.5 block w-full rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]">
                        {{ t('menu.products') }}
                    </RouterLink>
                    <RouterLink to="/corporations" class="mt-1.5 block w-full rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]">
                        {{ t('menu.corporations') }}
                    </RouterLink>
                    <button class="mt-1.5 block w-full cursor-pointer rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]" type="button">
                        {{ t('menu.warehouses') }}
                    </button>
                    <button class="mt-1.5 block w-full cursor-pointer rounded-lg bg-[#d6e9d6] px-2.5 py-2 text-left text-[#1e3020] hover:bg-[#c8e0c9]" type="button">
                        {{ t('menu.users') }}
                    </button>
                </div>
            </div>
        </nav>

        <main class="min-w-0 p-4 md:p-5">
            <RouterView />
        </main>
    </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { getSupportedLocales, setLocale } from './i18n';

const isMenuOpen = ref(true);
const { t, locale } = useI18n();
const supportedLocales = getSupportedLocales();
const selectedLocale = ref(locale.value);

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

const collapseOnMenuLinkClick = (event) => {
    if (event.target.closest('a')) {
        isMenuOpen.value = false;
    }
};
</script>