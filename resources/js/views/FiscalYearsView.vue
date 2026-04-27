<script setup>
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import http from '../lib/http';

const { t } = useI18n();

const fiscalYears = ref([]);
const loading = ref(true);
const loadError = ref('');
const searchTerm = ref('');
const addFormOpen = ref(false);
const savingAdd = ref(false);
const addError = ref('');
const hasCorporation = ref(null);

const editStates = reactive({});
const editForms = reactive({});
const editSaving = reactive({});
const editError = reactive({});
const closing = reactive({});
const closeError = reactive({});

const currentFiscalYear = ref(null);

const filteredFiscalYears = computed(() => {
    const query = searchTerm.value.trim().toLowerCase();

    if (!query) {
        return fiscalYears.value;
    }

    return fiscalYears.value.filter((fy) => {
        return [
            fy.name,
            fy.start_date,
            fy.end_date,
            fy.status,
        ].some((value) => String(value || '').toLowerCase().includes(query));
    });
});

function formatDate(dateString) {
    if (!dateString) {
        return '';
    }
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatCurrency(amount, currency = 'UGX') {
    if (amount === null || amount === undefined) {
        return '';
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

function getStatusLabel(fy) {
    if (fy.is_closed) {
        return t('fiscal_years.statuses.closed');
    }
    if (fy.is_active) {
        return t('fiscal_years.statuses.active');
    }
    return t('fiscal_years.statuses.draft');
}

function getStatusColor(fy) {
    if (fy.is_closed) {
        return 'bg-red-50 text-red-700 border-red-200';
    }
    if (fy.is_active) {
        return 'bg-green-50 text-green-700 border-green-200';
    }
    return 'bg-gray-50 text-gray-700 border-gray-200';
}

function normalizeApiFiscalYear(fy) {
    return {
        uuid: fy.uuid,
        name: fy.name ?? '',
        start_date: fy.start_date ?? '',
        end_date: fy.end_date ?? '',
        is_active: fy.is_active ?? false,
        is_closed: fy.is_closed ?? false,
        closed_at: fy.closed_at ?? null,
        closed_by: fy.closed_by ?? null,
        total_revenue: fy.total_revenue ?? 0,
        formatted_start: formatDate(fy.start_date),
        formatted_end: formatDate(fy.end_date),
        formatted_closed: fy.closed_at ? formatDate(fy.closed_at) : null,
        formatted_revenue: formatCurrency(fy.total_revenue, 'UGX'),
        status: getStatusLabel(fy),
        status_color: getStatusColor(fy),
    };
}

const newFiscalYear = reactive({
    name: '',
    start_date: '',
    end_date: '',
});

function resetNewForm() {
    newFiscalYear.name = '';
    newFiscalYear.start_date = '';
    newFiscalYear.end_date = '';
}

function initEditForm(fy) {
    editForms[fy.uuid] = {
        name: fy.name ?? '',
        start_date: fy.start_date ?? '',
        end_date: fy.end_date ?? '',
    };
}

async function loadFiscalYears() {
    loading.value = true;
    loadError.value = '';

    try {
        // Check if user has a corporation
        const userResponse = await http.get('/api/user');
        hasCorporation.value = userResponse.data?.corporation_id !== null && userResponse.data?.corporation_id !== undefined;

        const [listResponse, currentResponse] = await Promise.all([
            http.get('/api/fiscal-years'),
            http.get('/api/fiscal-years/current'),
        ]);

        fiscalYears.value = Array.isArray(listResponse.data)
            ? listResponse.data.map(normalizeApiFiscalYear)
            : [];

        fiscalYears.value.forEach((fy) => {
            initEditForm(fy);
            if (!(fy.uuid in editStates)) {
                editStates[fy.uuid] = false;
            }
        });

        // Load current fiscal year separately
        if (currentResponse.data && currentResponse.data.uuid) {
            currentFiscalYear.value = normalizeApiFiscalYear(currentResponse.data);
        } else {
            currentFiscalYear.value = null;
        }

    } catch (error) {
        loadError.value = t('fiscal_years.messages.load_error');
    } finally {
        loading.value = false;
    }
}

async function addFiscalYear() {
    addError.value = '';
    savingAdd.value = true;

    try {
        await http.post('/api/fiscal-years', {
            name: newFiscalYear.name,
            start_date: newFiscalYear.start_date,
            end_date: newFiscalYear.end_date,
        });

        resetNewForm();
        addFormOpen.value = false;
        await loadFiscalYears();
    } catch (error) {
        addError.value = t('fiscal_years.messages.add_error');
    } finally {
        savingAdd.value = false;
    }
}

function openEdit(fy) {
    editError[fy.uuid] = '';
    editStates[fy.uuid] = true;
    initEditForm(fy);
}

function cancelEdit(fy) {
    editError[fy.uuid] = '';
    editStates[fy.uuid] = false;
    initEditForm(fy);
}

async function saveEdit(fy) {
    const form = editForms[fy.uuid];

    if (!form) {
        return;
    }

    editError[fy.uuid] = '';
    editSaving[fy.uuid] = true;

    try {
        // Note: Fiscal years don't have a PUT endpoint yet in controller
        // For now, we'd need to add it to FiscalYearController
        editStates[fy.uuid] = false;
        await loadFiscalYears();
    } catch (error) {
        editError[fy.uuid] = t('fiscal_years.messages.save_error');
    } finally {
        editSaving[fy.uuid] = false;
    }
}

async function closeFiscalYear(fy) {
    const fyName = fy.name || t('fiscal_years.fields.unknown');
    const confirmed = window.confirm(t('fiscal_years.messages.close_confirm', { name: fyName }));

    if (!confirmed) {
        return;
    }

    closing[fy.uuid] = true;
    closeError[fy.uuid] = '';

    try {
        await http.post(`/api/fiscal-years/${fy.uuid}/close`);
        await loadFiscalYears();
    } catch (error) {
        closeError[fy.uuid] = t('fiscal_years.messages.close_error');
    } finally {
        closing[fy.uuid] = false;
    }
}

async function viewReport(fy) {
    try {
        const response = await http.get(`/api/fiscal-years/${fy.uuid}/report`);
        // Could display in a modal or navigate to a report view
        alert(t('fiscal_years.messages.report_ready', { name: fy.name }));
        console.log('Report:', response.data);
    } catch (error) {
        alert(t('fiscal_years.messages.load_error'));
    }
}

// Auto-load on page mount
void loadFiscalYears();
</script>

<template>
    <section class="mx-auto max-w-6xl rounded-xl border border-[#dde5d7] bg-white/70 p-6">
        <header class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#1f2a1d]">{{ t('fiscal_years.title') }}</h1>
                <p class="text-sm text-[#4e5f4f]">{{ t('fiscal_years.subtitle') }}</p>
            </div>

            <button
                type="button"
                class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f] disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="!hasCorporation"
                @click="addFormOpen = !addFormOpen"
            >
                {{ addFormOpen ? t('fiscal_years.actions.cancel') : t('fiscal_years.actions.add_fiscal_year') }}
            </button>
        </header>

        <!-- No Corporation Warning -->
        <div v-if="!loading && hasCorporation === false" class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <p class="font-semibold text-amber-800">{{ t('fiscal_years.messages.no_corporation_title') }}</p>
                    <p class="text-sm text-amber-700">{{ t('fiscal_years.messages.no_corporation_hint') }}</p>
                </div>
            </div>
        </div>

        <!-- Current Fiscal Year Alert -->
        <div v-if="currentFiscalYear && !loading && hasCorporation" class="mb-6 rounded-lg border border-[#ccd8c7] bg-[#f0f7f0] p-4">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2a4d2a]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="font-semibold text-[#1f2a1d]">{{ t('fiscal_years.messages.active_fiscal_year') }}</p>
                    <p class="text-sm text-[#4e5f4f]">
                        {{ currentFiscalYear.name }}: {{ currentFiscalYear.formatted_start }} → {{ currentFiscalYear.formatted_end }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Add Fiscal Year Form -->
        <form v-if="addFormOpen" class="mb-6 rounded-lg border border-[#ccd8c7] bg-white p-4" @submit.prevent="addFiscalYear">
            <h2 class="mb-3 text-lg font-semibold text-[#1f2a1d]">{{ t('fiscal_years.actions.add_fiscal_year') }}</h2>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('fiscal_years.fields.name') }}</span>
                    <input
                        v-model="newFiscalYear.name"
                        required
                        type="text"
                        :placeholder="t('fiscal_years.fields.name')"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('fiscal_years.fields.start_date') }}</span>
                    <input
                        v-model="newFiscalYear.start_date"
                        required
                        type="date"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('fiscal_years.fields.end_date') }}</span>
                    <input
                        v-model="newFiscalYear.end_date"
                        required
                        type="date"
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
                    {{ savingAdd ? t('fiscal_years.actions.saving') : t('fiscal_years.actions.save_changes') }}
                </button>
                <p v-if="addError" class="text-sm text-red-700">{{ addError }}</p>
            </div>
        </form>

        <p v-if="loading" class="text-sm text-[#4e5f4f]">{{ t('fiscal_years.messages.loading') }}</p>
        <p v-if="loadError" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ loadError }}</p>

        <label class="mb-4 block" v-if="!loading">
            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('fiscal_years.messages.search_label') }}</span>
            <input
                v-model="searchTerm"
                type="text"
                :placeholder="t('fiscal_years.messages.search_placeholder')"
                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
            >
        </label>

        <div v-if="!loading" class="space-y-4">
            <article
                v-for="fy in filteredFiscalYears"
                :key="fy.uuid"
                class="rounded-lg border border-[#ccd8c7] bg-white p-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-semibold text-[#1f2a1d]">{{ fy.name }}</h3>
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="fy.status_color"
                            >
                                {{ fy.status }}
                            </span>
                        </div>

                        <div class="mt-2 grid grid-cols-1 gap-2 text-sm text-[#4e5f4f] md:grid-cols-2 lg:grid-cols-3">
                            <p>
                                <span class="font-medium text-[#1f2a1d]">{{ t('fiscal_years.fields.start_date') }}:</span>
                                {{ fy.formatted_start }}
                            </p>
                            <p>
                                <span class="font-medium text-[#1f2a1d]">{{ t('fiscal_years.fields.end_date') }}:</span>
                                {{ fy.formatted_end }}
                            </p>
                            <p v-if="fy.is_closed && fy.formatted_closed">
                                <span class="font-medium text-[#1f2a1d]">{{ t('fiscal_years.fields.closed_at') }}:</span>
                                {{ fy.formatted_closed }}
                            </p>
                        </div>

                        <!-- Financial Summary -->
                        <div class="mt-3 grid grid-cols-1 gap-2 text-sm">
                            <p>
                                <span class="font-medium text-[#1f2a1d]">{{ t('fiscal_years.fields.total_revenue') }}:</span>
                                <span class="font-semibold text-[#2a4d2a]">{{ fy.formatted_revenue }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="!fy.is_closed"
                            type="button"
                            class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-1.5 text-sm hover:bg-[#f4f8ed]"
                            @click="openEdit(fy)"
                        >
                            {{ t('fiscal_years.actions.edit') }}
                        </button>

                        <button
                            v-if="!fy.is_closed && fy.is_active"
                            type="button"
                            class="rounded-lg bg-[#d97706] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#b45309]"
                            :disabled="closing[fy.uuid]"
                            @click="closeFiscalYear(fy)"
                        >
                            {{ closing[fy.uuid] ? t('fiscal_years.actions.closing') : t('fiscal_years.actions.close_fiscal_year') }}
                        </button>

                        <button
                            type="button"
                            class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-1.5 text-sm hover:bg-[#f4f8ed]"
                            @click="viewReport(fy)"
                        >
                            {{ t('fiscal_years.actions.view_report') }}
                        </button>


                    </div>
                </div>

                <form
                    v-if="editStates[fy.uuid]"
                    class="mt-4 rounded-lg border border-[#ccd8c7] bg-[#f9fcf8] p-4"
                    @submit.prevent="saveEdit(fy)"
                >
                    <h4 class="mb-3 text-base font-semibold text-[#1f2a1d]">{{ t('fiscal_years.actions.edit') }} {{ fy.name }}</h4>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('fiscal_years.fields.name') }}</span>
                            <input
                                v-model="editForms[fy.uuid].name"
                                required
                                type="text"
                                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                            >
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('fiscal_years.fields.start_date') }}</span>
                            <input
                                v-model="editForms[fy.uuid].start_date"
                                required
                                type="date"
                                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                            >
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('fiscal_years.actions.end_date') }}</span>
                            <input
                                v-model="editForms[fy.uuid].end_date"
                                required
                                type="date"
                                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                            >
                        </label>
                    </div>

                    <div class="mt-3 flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="editSaving[fy.uuid]"
                            class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f] disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            {{ editSaving[fy.uuid] ? t('fiscal_years.actions.saving') : t('fiscal_years.actions.save_changes') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-[#ccd8c7] bg-white px-4 py-2 font-medium text-[#1f2a1d] hover:bg-[#f4f8ed]"
                            @click="cancelEdit(fy)"
                        >
                            {{ t('fiscal_years.actions.cancel') }}
                        </button>
                        <p v-if="editError[fy.uuid]" class="text-sm text-red-700">{{ editError[fy.uuid] }}</p>
                    </div>
                </form>

                <p v-if="closeError[fy.uuid]" class="mt-2 text-sm text-red-700">{{ closeError[fy.uuid] }}</p>
            </article>

            <p v-if="filteredFiscalYears.length === 0" class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 text-sm text-[#4e5f4f]">
                {{ t('fiscal_years.messages.empty') }}
            </p>
        </div>

        <!-- No Active Fiscal Year Warning -->
        <div v-if="!loading && !currentFiscalYear" class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <p class="font-semibold text-amber-800">{{ t('fiscal_years.messages.no_active') }}</p>
                    <p class="text-sm text-amber-700">{{ t('fiscal_years.messages.create_first') }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
