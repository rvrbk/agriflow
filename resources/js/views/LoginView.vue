<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

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
            errorMessage.value = 'Invalid credentials.';
            return;
        }

        if (error?.response?.status === 419) {
            errorMessage.value = 'Session expired. Please try again.';
            return;
        }

        errorMessage.value = 'Unable to login right now.';
    }
}
</script>

<template>
    <main class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-6">
        <section class="w-full max-w-md border border-slate-800 bg-slate-900 rounded-xl p-6 space-y-5">
            <h1 class="text-2xl font-semibold">Login</h1>
            <p class="text-sm text-slate-300">Sign in with your Fortify credentials.</p>

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
                        autocomplete="current-password"
                        class="w-full rounded-md bg-slate-800 border border-slate-700 px-3 py-2"
                        required
                    >
                </label>

                <label class="inline-flex items-center gap-2 text-sm text-slate-300">
                    <input v-model="form.remember" type="checkbox">
                    Remember me
                </label>

                <p v-if="errorMessage" class="text-sm text-rose-300">{{ errorMessage }}</p>

                <button
                    type="submit"
                    class="w-full rounded-md bg-emerald-500 text-slate-950 px-4 py-2 font-medium disabled:opacity-60"
                    :disabled="auth.state.loading"
                >
                    {{ auth.state.loading ? 'Signing in...' : 'Sign in' }}
                </button>
            </form>
        </section>
    </main>
</template>
