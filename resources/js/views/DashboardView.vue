<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import http from '../lib/http';

const auth = useAuthStore();
const router = useRouter();

const protectedResponse = ref('');
const apiError = ref('');

const userName = computed(() => auth.state.user?.name ?? auth.state.user?.email ?? 'User');

async function callProtectedEndpoint() {
    protectedResponse.value = '';
    apiError.value = '';

    try {
        await http.post('/api/product', {
            name: 'Test Product',
        });

        protectedResponse.value = 'Protected API call succeeded with Sanctum session.';
    } catch (error) {
        if (error?.response?.status === 401 || error?.response?.status === 419) {
            await auth.signOut();
            await router.push({ name: 'login' });
            return;
        }

        apiError.value = 'Protected API request failed. Check payload requirements in your ProductController.';
    }
}

async function logout() {
    await auth.signOut();
    await router.push({ name: 'login' });
}
</script>

<template>
    <main class="min-h-screen bg-slate-950 text-slate-100 p-6">
        <section class="mx-auto max-w-3xl space-y-4">
            <header class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Welcome, {{ userName }}</h1>
                <button
                    type="button"
                    class="rounded-md bg-slate-800 border border-slate-700 px-4 py-2"
                    @click="logout"
                >
                    Logout
                </button>
            </header>

            <p class="text-slate-300">
                This route is guarded by Vue middleware and requires an active Sanctum session.
            </p>

            <div class="flex gap-3">
                <button
                    type="button"
                    class="rounded-md bg-emerald-500 text-slate-950 px-4 py-2 font-medium"
                    @click="callProtectedEndpoint"
                >
                    Call Protected API
                </button>
            </div>

            <p v-if="protectedResponse" class="text-emerald-300">{{ protectedResponse }}</p>
            <p v-if="apiError" class="text-rose-300">{{ apiError }}</p>
        </section>
    </main>
</template>
