<script setup>
import { computed, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import http from '../lib/http';
import { fetchCsrfCookie } from '../services/auth';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const form = reactive({
    email: typeof route.query.email === 'string' ? route.query.email : '',
    token: typeof route.query.token === 'string' ? route.query.token : '',
    password: '',
    password_confirmation: '',
});

const loading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const canSubmit = computed(() => {
    return Boolean(form.email && form.token && form.password && form.password_confirmation);
});

async function submit() {
    successMessage.value = '';
    errorMessage.value = '';

    if (!canSubmit.value) {
        errorMessage.value = t('set_password.messages.missing_info');
        return;
    }

    loading.value = true;

    try {
        await fetchCsrfCookie();
        await http.post('/reset-password', {
            token: form.token,
            email: form.email,
            password: form.password,
            password_confirmation: form.password_confirmation,
        });

        successMessage.value = t('set_password.messages.success');

        window.setTimeout(() => {
            void router.push('/login');
        }, 1000);
    } catch (error) {
        if (error?.response?.status === 422) {
            errorMessage.value = t('set_password.messages.invalid_link');
            return;
        }

        if (error?.response?.status === 419) {
            errorMessage.value = t('set_password.messages.session_expired');
            return;
        }

        errorMessage.value = t('set_password.messages.unable_to_set');
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <main class="min-h-screen bg-[radial-gradient(circle_at_top_right,_#eef5e2_0%,_#f6f6ef_42%)] text-[#1f2a1d] flex items-center justify-center p-4 md:p-6">
        <section class="w-full max-w-md rounded-xl border border-[#dde5d7] bg-white/70 p-6 space-y-6 shadow-[0_14px_28px_rgb(16_29_18_/_16%)]">
            <div class="text-center">
                <div
                    class="mx-auto grid h-[42px] w-[42px] place-items-center rounded-[11px] bg-[linear-gradient(140deg,_#214f34,_#317f4f)] text-sm font-extrabold tracking-[0.04em] text-[#f8fff8] shadow-[0_8px_16px_rgb(42_91_57_/_24%)]"
                    aria-hidden="true"
                >
                    AF
                </div>
                <h1 class="mt-4 text-2xl font-bold text-[#1f2a1d]">{{ t('set_password.title') }}</h1>
                <p class="text-sm text-[#4e5f4f]">{{ t('set_password.subtitle') }}</p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <label class="block space-y-1">
                    <span class="text-sm font-medium text-[#1f2a1d]">{{ t('set_password.fields.email') }}</span>
                    <input
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        :placeholder="t('set_password.fields.email_placeholder')"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                        :disabled="Boolean(form.email)"
                    >
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium text-[#1f2a1d]">{{ t('set_password.fields.password') }}</span>
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        :placeholder="t('set_password.fields.password_placeholder')"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                        required
                    >
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium text-[#1f2a1d]">{{ t('set_password.fields.confirm_password') }}</span>
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        :placeholder="t('set_password.fields.confirm_password_placeholder')"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                        required
                    >
                </label>

                <p v-if="errorMessage" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ errorMessage }}</p>
                <p v-if="successMessage" class="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">{{ successMessage }}</p>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-[#2f6e4a] px-4 py-2 text-sm font-medium text-white hover:bg-[#275d3f] disabled:opacity-70"
                    :disabled="loading || !canSubmit"
                >
                    {{ loading ? t('set_password.actions.saving') : t('set_password.actions.set_password') }}
                </button>
            </form>
        </section>
    </main>
</template>
