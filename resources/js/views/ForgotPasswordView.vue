<script setup>
import { reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import http from '../lib/http';

const { t } = useI18n();

const form = reactive({
    email: '',
});

const loading = ref(false);
const successMessageKey = ref('');
const errorMessageKey = ref('');

async function submit() {
    successMessageKey.value = '';
    errorMessageKey.value = '';
    loading.value = true;

    try {
        await http.get('/sanctum/csrf-cookie');
        await http.post('/forgot-password', {
            email: form.email,
        });

        successMessageKey.value = 'forgot_password.messages.success';
    } catch (error) {
        if (error?.response?.status === 422) {
            errorMessageKey.value = 'forgot_password.messages.validation_error';
        } else if (error?.response?.status === 429) {
            errorMessageKey.value = 'forgot_password.messages.throttled';
        } else {
            errorMessageKey.value = 'forgot_password.messages.unable_to_send';
        }
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
                <h1 class="mt-4 text-2xl font-bold text-[#1f2a1d]">{{ t('forgot_password.title') }}</h1>
                <p class="text-sm text-[#4e5f4f]">{{ t('forgot_password.subtitle') }}</p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <label class="block space-y-1">
                    <span class="text-sm font-medium text-[#1f2a1d]">{{ t('forgot_password.fields.email') }}</span>
                    <input
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        :placeholder="t('forgot_password.fields.email_placeholder')"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                        required
                    >
                </label>

                <p v-if="successMessageKey" class="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">{{ t(successMessageKey) }}</p>
                <p v-if="errorMessageKey" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ t(errorMessageKey) }}</p>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-[#2f6e4a] px-4 py-2 text-sm font-medium text-white hover:bg-[#275d3f] disabled:opacity-70"
                    :disabled="loading"
                >
                    {{ loading ? t('forgot_password.actions.sending') : t('forgot_password.actions.send_link') }}
                </button>
            </form>

            <div class="text-center">
                <RouterLink to="/login" class="text-sm font-medium text-[#2f6e4a] hover:text-[#275d3f]">
                    {{ t('forgot_password.actions.back_to_login') }}
                </RouterLink>
            </div>
        </section>
    </main>
</template>
