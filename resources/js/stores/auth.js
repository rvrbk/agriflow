import { reactive } from 'vue';
import { getCurrentUser, login as loginRequest, logout as logoutRequest } from '../services/auth';

const state = reactive({
    user: null,
    initialized: false,
    loading: false,
});

async function refreshUser() {
    try {
        state.user = await getCurrentUser();
        return state.user;
    } catch (error) {
        if (error?.response?.status === 401 || error?.response?.status === 419 || error?.response?.status === 404) {
            state.user = null;
            return null;
        }

        state.user = null;
        return null;
    } finally {
        state.initialized = true;
    }
}

async function ensureInitialized() {
    if (state.initialized) {
        return;
    }

    await refreshUser();
}

async function signIn(credentials) {
    state.loading = true;

    try {
        await loginRequest(credentials);
        await refreshUser();
    } finally {
        state.loading = false;
    }
}

async function signOut() {
    try {
        await logoutRequest();
    } finally {
        state.user = null;
        state.initialized = true;
    }
}

export function useAuthStore() {
    return {
        state,
        ensureInitialized,
        refreshUser,
        signIn,
        signOut,
    };
}
