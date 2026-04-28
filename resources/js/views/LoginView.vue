<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const form = reactive({
    email: '',
    password: '',
    remember: false,
});

const errorMessage = ref('');

async function submit() {
    errorMessage.value = '';

    try {
        await auth.signIn(form);

        const redirectTo = typeof route.query.redirect === 'string' ? route.query.redirect : '/';
        await router.push(redirectTo);
    } catch (error) {
        if (error?.response?.status === 422) {
            errorMessage.value = t('login.messages.invalid_credentials');
            return;
        }

        if (error?.response?.status === 419) {
            errorMessage.value = t('login.messages.session_expired');
            return;
        }

        errorMessage.value = t('login.messages.unable_to_login');
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
                <h1 class="mt-4 text-2xl font-bold text-[#1f2a1d]">{{ t('login.title') }}</h1>
                <p class="text-sm text-[#4e5f4f]">{{ t('login.subtitle') }}</p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <label class="block space-y-1">
                    <span class="text-sm font-medium text-[#1f2a1d]">{{ t('login.fields.email') }}</span>
                    <input
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        :placeholder="t('login.fields.email_placeholder')"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                        required
                    >
                </label>

                <label class="block space-y-1">
                    <span class="text-sm font-medium text-[#1f2a1d]">{{ t('login.fields.password') }}</span>
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        :placeholder="t('login.fields.password_placeholder')"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                        required
                    >
                </label>

                <label class="inline-flex items-center gap-2 text-sm text-[#4e5f4f]">
                    <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-[#2f6e4a] focus:ring-[#2f6e4a]">
                    {{ t('login.fields.remember_me') }}
                </label>

                <div class="text-right">
                    <RouterLink to="/forgot-password" class="text-sm font-medium text-[#2f6e4a] hover:text-[#275d3f]">
                        {{ t('login.actions.forgot_password') }}
                    </RouterLink>
                </div>

                <p v-if="errorMessage" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ errorMessage }}</p>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-[#2f6e4a] px-4 py-2 text-sm font-medium text-white hover:bg-[#275d3f] disabled:opacity-70"
                    :disabled="auth.state.loading"
                >
                    {{ auth.state.loading ? t('login.actions.signing_in') : t('login.actions.sign_in') }}
                </button>
            </form>
        </section>
    </main>
</template>
