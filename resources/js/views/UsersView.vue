<script setup>
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import http from '../lib/http';

const { t } = useI18n();

const users = ref([]);
const loading = ref(true);
const loadError = ref('');
const searchTerm = ref('');
const addFormOpen = ref(false);
const savingAdd = ref(false);
const addError = ref('');

const editStates = reactive({});
const editForms = reactive({});
const editSaving = reactive({});
const editError = reactive({});
const deleteSaving = reactive({});

const newUser = reactive({
    name: '',
    email: '',
});

const filteredUsers = computed(() => {
    const query = searchTerm.value.trim().toLowerCase();

    if (!query) {
        return users.value;
    }

    return users.value.filter((user) => {
        return [
            user.name,
            user.email,
        ].some((value) => String(value || '').toLowerCase().includes(query));
    });
});

function normalizeApiUser(user) {
    return {
        id: Number(user.id),
        name: user.name ?? '',
        email: user.email ?? '',
        is_current_user: Boolean(user.is_current_user),
    };
}

function resetNewForm() {
    newUser.name = '';
    newUser.email = '';
}

function initEditForm(user) {
    editForms[user.id] = {
        name: user.name ?? '',
        email: user.email ?? '',
    };
}

async function loadUsers() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await http.get('/api/users');
        users.value = Array.isArray(response.data)
            ? response.data.map(normalizeApiUser)
            : [];

        users.value.forEach((user) => {
            initEditForm(user);
            if (!(user.id in editStates)) {
                editStates[user.id] = false;
            }
        });
    } catch (error) {
        loadError.value = t('users.messages.load_error');
    } finally {
        loading.value = false;
    }
}

async function addUser() {
    addError.value = '';
    savingAdd.value = true;

    try {
        await http.post('/api/users', {
            name: newUser.name,
            email: newUser.email,
        });

        resetNewForm();
        addFormOpen.value = false;
        await loadUsers();
    } catch (error) {
        if (error?.response?.status === 422) {
            addError.value = t('users.messages.validation_error');
        } else {
            addError.value = t('users.messages.add_error');
        }
    } finally {
        savingAdd.value = false;
    }
}

function openEdit(user) {
    editError[user.id] = '';
    editStates[user.id] = true;
    initEditForm(user);
}

function cancelEdit(user) {
    editError[user.id] = '';
    editStates[user.id] = false;
    initEditForm(user);
}

async function saveEdit(user) {
    const form = editForms[user.id];

    if (!form) {
        return;
    }

    editError[user.id] = '';
    editSaving[user.id] = true;

    try {
        await http.post('/api/users', {
            id: user.id,
            name: form.name,
            email: form.email,
        });

        editStates[user.id] = false;
        await loadUsers();
    } catch (error) {
        if (error?.response?.status === 422) {
            editError[user.id] = t('users.messages.validation_error');
        } else {
            editError[user.id] = t('users.messages.save_error');
        }
    } finally {
        editSaving[user.id] = false;
    }
}

async function deleteUser(user) {
    if (user.is_current_user) {
        editError[user.id] = t('users.messages.delete_current_user_error');
        return;
    }

    const userName = user.name || user.email;
    const confirmed = window.confirm(t('users.messages.delete_confirm', { name: userName }));

    if (!confirmed) {
        return;
    }

    deleteSaving[user.id] = true;

    try {
        await http.delete(`/api/users/${user.id}`);
        await loadUsers();
    } catch (error) {
        if (error?.response?.status === 409) {
            editError[user.id] = t('users.messages.delete_current_user_error');
        } else {
            editError[user.id] = t('users.messages.delete_error');
        }
    } finally {
        deleteSaving[user.id] = false;
    }
}

void loadUsers();
</script>

<template>
    <section class="mx-auto max-w-5xl rounded-xl border border-[#dde5d7] bg-white/70 p-6">
        <header class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#1f2a1d]">{{ t('users.title') }}</h1>
                <p class="text-sm text-[#4e5f4f]">{{ t('users.subtitle') }}</p>
            </div>

            <button
                type="button"
                class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f]"
                @click="addFormOpen = !addFormOpen"
            >
                {{ addFormOpen ? t('users.actions.close_add_form') : t('users.actions.add_user') }}
            </button>
        </header>

        <form v-if="addFormOpen" class="mb-6 rounded-lg border border-[#ccd8c7] bg-white p-4" @submit.prevent="addUser">
            <h2 class="mb-3 text-lg font-semibold text-[#1f2a1d]">{{ t('users.actions.add_user') }}</h2>
            <p class="mb-3 text-sm text-[#4e5f4f]">{{ t('users.messages.welcome_email_hint') }}</p>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('users.fields.name') }}</span>
                    <input
                        v-model="newUser.name"
                        required
                        type="text"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('users.fields.email') }}</span>
                    <input
                        v-model="newUser.email"
                        required
                        type="email"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                    >
                </label>
            </div>

            <div class="mt-3 flex items-center gap-3">
                <button
                    type="submit"
                    :disabled="savingAdd"
                    class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f] disabled:cursor-not-allowed disabled:opacity-70"
                >
                    {{ savingAdd ? t('users.actions.saving') : t('users.actions.save_user') }}
                </button>
                <p v-if="addError" class="text-sm text-red-700">{{ addError }}</p>
            </div>
        </form>

        <p v-if="loading" class="text-sm text-[#4e5f4f]">{{ t('users.messages.loading') }}</p>
        <p v-if="loadError" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ loadError }}</p>

        <label class="mb-4 block" v-if="!loading">
            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('users.messages.search_label') }}</span>
            <input
                v-model="searchTerm"
                type="text"
                :placeholder="t('users.messages.search_placeholder')"
                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
            >
        </label>

        <div v-if="!loading" class="space-y-4">
            <article
                v-for="user in filteredUsers"
                :key="user.id"
                class="rounded-lg border border-[#ccd8c7] bg-white p-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-[#1f2a1d]">{{ user.name }}</h3>
                        <p class="text-sm text-[#4e5f4f]">{{ t('users.fields.email') }}: {{ user.email }}</p>
                        <p v-if="user.is_current_user" class="mt-1 inline-flex rounded-full bg-[#d6e9d6] px-2 py-0.5 text-xs text-[#1e3020]">
                            {{ t('users.messages.current_user_badge') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-1.5 text-sm hover:bg-[#f4f8ed]"
                            @click="openEdit(user)"
                        >
                            {{ t('users.actions.edit') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-70"
                            :disabled="deleteSaving[user.id] || user.is_current_user"
                            @click="deleteUser(user)"
                        >
                            {{
                                user.is_current_user
                                    ? t('users.actions.delete_blocked')
                                    : (deleteSaving[user.id] ? t('users.actions.deleting') : t('users.actions.delete'))
                            }}
                        </button>
                    </div>
                </div>

                <form
                    v-if="editStates[user.id]"
                    class="mt-4 rounded-lg border border-[#ccd8c7] bg-[#f9fcf8] p-4"
                    @submit.prevent="saveEdit(user)"
                >
                    <h4 class="mb-3 text-base font-semibold text-[#1f2a1d]">{{ t('users.actions.edit_user') }}</h4>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('users.fields.name') }}</span>
                            <input
                                v-model="editForms[user.id].name"
                                required
                                type="text"
                                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                            >
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('users.fields.email') }}</span>
                            <input
                                v-model="editForms[user.id].email"
                                required
                                type="email"
                                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                            >
                        </label>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <button
                            type="submit"
                            :disabled="editSaving[user.id]"
                            class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f] disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            {{ editSaving[user.id] ? t('users.actions.saving') : t('users.actions.save_changes') }}
                        </button>

                        <button
                            type="button"
                            class="rounded-lg border border-[#ccd8c7] bg-white px-4 py-2 text-sm hover:bg-[#f4f8ed]"
                            @click="cancelEdit(user)"
                        >
                            {{ t('users.actions.cancel') }}
                        </button>

                        <p v-if="editError[user.id]" class="text-sm text-red-700">{{ editError[user.id] }}</p>
                    </div>
                </form>
            </article>

            <p v-if="filteredUsers.length === 0" class="rounded-lg border border-[#ccd8c7] bg-white p-4 text-sm text-[#4e5f4f]">
                {{ t('users.messages.empty') }}
            </p>
        </div>
    </section>
</template>
