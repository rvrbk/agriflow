import { reactive } from 'vue';
import { getCurrentUser, login as loginRequest, logout as logoutRequest } from '../services/auth';

const AUTH_USER_STORAGE_KEY = 'agriflow.authUser';

function readCachedUser() {
    try {
        const raw = window.localStorage.getItem(AUTH_USER_STORAGE_KEY);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch {
        return null;
    }
}

function writeCachedUser(user) {
    if (!user) {
        window.localStorage.removeItem(AUTH_USER_STORAGE_KEY);
        return;
    }

    window.localStorage.setItem(AUTH_USER_STORAGE_KEY, JSON.stringify(user));
}

const state = reactive({
    user: readCachedUser(),
    initialized: false,
    loading: false,
});

async function refreshUser() {
    try {
        state.user = await getCurrentUser();
        writeCachedUser(state.user);
        return state.user;
    } catch (error) {
        if (error?.response?.status === 401 || error?.response?.status === 419 || error?.response?.status === 404) {
            state.user = null;
            writeCachedUser(null);
            return null;
        }

        // On offline/network failures keep the last known authenticated user.
        if (state.user) {
            return state.user;
        }

        const cachedUser = readCachedUser();
        state.user = cachedUser;
        return cachedUser;
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
        writeCachedUser(null);
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
