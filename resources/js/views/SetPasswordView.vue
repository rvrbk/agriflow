<script setup>
import { computed, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import http from '../lib/http';
import { fetchCsrfCookie } from '../services/auth';

const route = useRoute();
const router = useRouter();

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
        errorMessage.value = 'Missing reset token, email, or password.';
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

        successMessage.value = 'Your password has been set. Redirecting to login...';

        window.setTimeout(() => {
            void router.push('/login');
        }, 1000);
    } catch (error) {
        if (error?.response?.status === 422) {
            errorMessage.value = 'Invalid or expired link. Please request a new invitation.';
            return;
        }

        if (error?.response?.status === 419) {
            errorMessage.value = 'Session expired. Please try again.';
            return;
        }

        errorMessage.value = 'Could not set password right now.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <main class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-6">
        <section class="w-full max-w-md border border-slate-800 bg-slate-900 rounded-xl p-6 space-y-5">
            <h1 class="text-2xl font-semibold">Set Password</h1>
            <p class="text-sm text-slate-300">Create your password to activate your account.</p>

            <form class="space-y-4" @submit.prevent="submit">
                <label class="block space-y-1">
                    <span class="text-sm text-slate-200">Email</span>
                    <input
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        class="w-full rounded-md bg-slate-800 border border-slate-700 px-3 py-2"
                        required
                    >
                </label>

                <label class="block space-y-1">
                    <span class="text-sm text-slate-200">Password</span>
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        class="w-full rounded-md bg-slate-800 border border-slate-700 px-3 py-2"
                        required
                    >
                </label>

                <label class="block space-y-1">
                    <span class="text-sm text-slate-200">Confirm Password</span>
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        class="w-full rounded-md bg-slate-800 border border-slate-700 px-3 py-2"
                        required
                    >
                </label>

                <p v-if="errorMessage" class="text-sm text-rose-300">{{ errorMessage }}</p>
                <p v-if="successMessage" class="text-sm text-emerald-300">{{ successMessage }}</p>

                <button
                    type="submit"
                    class="w-full rounded-md bg-emerald-500 text-slate-950 px-4 py-2 font-medium disabled:opacity-60"
                    :disabled="loading || !canSubmit"
                >
                    {{ loading ? 'Saving...' : 'Set Password' }}
                </button>
            </form>
        </section>
    </main>
</template>
