<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import QRCode from 'qrcode';
import http from '../lib/http';
import { readCachedList, removeCachedRow, upsertCachedRow, writeCachedList } from '../services/offlineDataCache';

const { t } = useI18n();

const harvests = ref([]);
const products = ref([]);
const warehouses = ref([]);
const corporations = ref([]);

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
const qrImages = reactive({});
const CACHE_KEY = 'harvests';

const qualityOptions = computed(() => [
    { value: 'high', label: t('harvests.quality.high') },
    { value: 'medium', label: t('harvests.quality.medium') },
    { value: 'low', label: t('harvests.quality.low') },
]);

const filteredHarvests = computed(() => {
    const query = searchTerm.value.trim().toLowerCase();

    if (!query) {
        return harvests.value;
    }

    return harvests.value.filter((harvest) => {
        return [
            harvest.product_name,
            harvest.warehouse_name,
            harvest.corporation_name,
            harvest.batch_uuid,
            harvest.qr_code,
            harvest.quality,
        ].some((value) => String(value || '').toLowerCase().includes(query));
    });
});

const newHarvest = reactive({
    product_uuid: '',
    warehouse_uuid: '',
    corporation_uuid: '',
    quantity: 0,
    location: '',
    harvested_on: toDateTimeLocal(new Date().toISOString()),
    expires_on: '',
    quality: 'medium',
});

function getProductByUuid(productUuid) {
    return products.value.find((product) => product.uuid === productUuid) || null;
}

function getProductUnit(productUuid) {
    return getProductByUuid(productUuid)?.unit || 'pcs';
}

function getUnitLabelForProduct(productUuid) {
    const unit = getProductUnit(productUuid);

    if (['kg', 'g', 'l', 'ml', 'pcs'].includes(unit)) {
        return t(`products.units.${unit}`);
    }

    return String(unit).toUpperCase();
}

function getQuantityStepForProduct(productUuid) {
    const unit = getProductUnit(productUuid);

    if (unit === 'pcs') {
        return 1;
    }

    return 0.01;
}

function toDateTimeLocal(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function toApiDate(value) {
    if (!value) {
        return null;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return date.toISOString();
}

function normalizeHarvest(harvest) {
    return {
        batch_uuid: harvest.batch_uuid,
        product_uuid: harvest.product_uuid ?? '',
        product_name: harvest.product_name ?? '',
        warehouse_uuid: harvest.warehouse_uuid ?? '',
        warehouse_name: harvest.warehouse_name ?? '',
        corporation_uuid: harvest.corporation_uuid ?? '',
        corporation_name: harvest.corporation_name ?? '',
        quantity: Number(harvest.quantity ?? 0),
        location: harvest.location ?? '',
        harvested_on: harvest.harvested_on ?? '',
        expires_on: harvest.expires_on ?? '',
        quality: harvest.quality ?? 'medium',
        qr_code: harvest.qr_code ?? '',
        qr_payload: harvest.qr_payload ?? '',
        qr_url: harvest.qr_url ?? `${window.location.origin}/harvest/${harvest.batch_uuid}`,
    };
}

function resetNewForm() {
    newHarvest.product_uuid = products.value[0]?.uuid ?? '';
    newHarvest.warehouse_uuid = warehouses.value[0]?.uuid ?? '';
    newHarvest.corporation_uuid = corporations.value[0]?.uuid ?? '';
    newHarvest.quantity = 0;
    newHarvest.location = '';
    newHarvest.harvested_on = toDateTimeLocal(new Date().toISOString());
    newHarvest.expires_on = '';
    newHarvest.quality = 'medium';
}

function initEditForm(harvest) {
    editForms[harvest.batch_uuid] = {
        batch_uuid: harvest.batch_uuid,
        product_uuid: harvest.product_uuid,
        warehouse_uuid: harvest.warehouse_uuid,
        corporation_uuid: harvest.corporation_uuid,
        quantity: harvest.quantity,
        location: harvest.location,
        harvested_on: toDateTimeLocal(harvest.harvested_on),
        expires_on: toDateTimeLocal(harvest.expires_on),
        quality: harvest.quality,
        regenerate_qr: false,
    };
}

async function renderQrImage(harvest) {
    const qrSource = harvest.qr_url || harvest.qr_payload || harvest.qr_code;

    if (!qrSource) {
        qrImages[harvest.batch_uuid] = '';
        return;
    }

    try {
        qrImages[harvest.batch_uuid] = await QRCode.toDataURL(qrSource, {
            width: 180,
            margin: 1,
        });
    } catch {
        qrImages[harvest.batch_uuid] = '';
    }
}

function printQr(harvest) {
    const dataUrl = qrImages[harvest.batch_uuid];

    if (!dataUrl) {
        return;
    }

    const label = harvest.qr_code || harvest.batch_uuid || 'harvest-qr';
    const printWindow = window.open('', '_blank', 'width=500,height=700');

    if (!printWindow) {
        return;
    }

    printWindow.document.write(`
        <!doctype html>
        <html>
            <head>
                <title>${label}</title>
                <style>
                    body {
                        margin: 0;
                        min-height: 100vh;
                        display: grid;
                        place-items: center;
                        background: #ffffff;
                        font-family: Arial, sans-serif;
                    }
                    .qr-wrap {
                        text-align: center;
                    }
                    img {
                        width: 280px;
                        height: 280px;
                    }
                    p {
                        margin-top: 12px;
                        font-size: 14px;
                        color: #1f2a1d;
                    }
                </style>
            </head>
            <body>
                <div class="qr-wrap">
                    <img src="${dataUrl}" alt="QR" />
                    <p>${label}</p>
                </div>
                <script>
                    window.onload = function () {
                        window.print();
                        window.close();
                    };
                <\/script>
            </body>
        </html>
    `);
    printWindow.document.close();
}

async function loadLookups() {
    try {
        const [productsResponse, warehousesResponse, corporationsResponse] = await Promise.all([
            http.get('/api/product'),
            http.get('/api/warehouse'),
            http.get('/api/corporations'),
        ]);

        products.value = Array.isArray(productsResponse.data) ? productsResponse.data : [];
        warehouses.value = Array.isArray(warehousesResponse.data) ? warehousesResponse.data : [];
        corporations.value = Array.isArray(corporationsResponse.data) ? corporationsResponse.data : [];

        writeCachedList('products', products.value);
        writeCachedList('warehouses', warehouses.value);
        writeCachedList('corporations', corporations.value);
    } catch {
        products.value = readCachedList('products');
        warehouses.value = readCachedList('warehouses');
        corporations.value = readCachedList('corporations');
    }

    if (!newHarvest.product_uuid && products.value.length > 0) {
        newHarvest.product_uuid = products.value[0].uuid;
    }

    if (!newHarvest.warehouse_uuid && warehouses.value.length > 0) {
        newHarvest.warehouse_uuid = warehouses.value[0].uuid;
    }

    if (!newHarvest.corporation_uuid && corporations.value.length > 0) {
        newHarvest.corporation_uuid = corporations.value[0].uuid;
    }
}

async function loadHarvests() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await http.get('/api/harvest');

        harvests.value = Array.isArray(response.data)
            ? response.data.map(normalizeHarvest)
            : [];
        writeCachedList(CACHE_KEY, harvests.value);

        harvests.value.forEach((harvest) => {
            initEditForm(harvest);
            if (!(harvest.batch_uuid in editStates)) {
                editStates[harvest.batch_uuid] = false;
            }
        });

        await Promise.all(harvests.value.map(renderQrImage));
    } catch {
        const cachedHarvests = readCachedList(CACHE_KEY).map(normalizeHarvest);

        if (cachedHarvests.length > 0) {
            harvests.value = cachedHarvests;
            harvests.value.forEach((harvest) => {
                initEditForm(harvest);
                if (!(harvest.batch_uuid in editStates)) {
                    editStates[harvest.batch_uuid] = false;
                }
            });
            await Promise.all(harvests.value.map(renderQrImage));
            loadError.value = '';
        } else {
            loadError.value = t('harvests.messages.load_error');
        }
    } finally {
        loading.value = false;
    }
}

async function addHarvest() {
    addError.value = '';
    savingAdd.value = true;

    try {
        const response = await http.post('/api/harvest', [
            {
                product_uuid: newHarvest.product_uuid,
                warehouse_uuid: newHarvest.warehouse_uuid,
                corporation_uuid: newHarvest.corporation_uuid,
                quantity: Number(newHarvest.quantity),
                location: newHarvest.location || null,
                harvested_on: toApiDate(newHarvest.harvested_on),
                expires_on: toApiDate(newHarvest.expires_on),
                quality: newHarvest.quality,
                replace_quantity: true,
            },
        ]);

        if (response?.data?.queued || !navigator.onLine) {
            const optimisticHarvest = normalizeHarvest({
                batch_uuid: `pending-${Date.now()}`,
                product_uuid: newHarvest.product_uuid,
                product_name: getProductByUuid(newHarvest.product_uuid)?.name ?? '',
                warehouse_uuid: newHarvest.warehouse_uuid,
                warehouse_name: warehouses.value.find((w) => w.uuid === newHarvest.warehouse_uuid)?.name ?? '',
                corporation_uuid: newHarvest.corporation_uuid,
                corporation_name: corporations.value.find((c) => c.uuid === newHarvest.corporation_uuid)?.name ?? '',
                quantity: Number(newHarvest.quantity),
                location: newHarvest.location,
                harvested_on: toApiDate(newHarvest.harvested_on),
                expires_on: toApiDate(newHarvest.expires_on),
                quality: newHarvest.quality,
                qr_code: '',
                qr_payload: '',
            });

            harvests.value = [optimisticHarvest, ...harvests.value];
            upsertCachedRow(CACHE_KEY, optimisticHarvest, 'batch_uuid');
            await renderQrImage(optimisticHarvest);
            resetNewForm();
            addFormOpen.value = false;
            return;
        }

        resetNewForm();
        addFormOpen.value = false;
        await loadHarvests();
    } catch {
        addError.value = t('harvests.messages.add_error');
    } finally {
        savingAdd.value = false;
    }
}

function openEdit(harvest) {
    editError[harvest.batch_uuid] = '';
    editStates[harvest.batch_uuid] = true;
    initEditForm(harvest);
}

function cancelEdit(harvest) {
    editError[harvest.batch_uuid] = '';
    editStates[harvest.batch_uuid] = false;
    initEditForm(harvest);
}

async function saveEdit(harvest) {
    const form = editForms[harvest.batch_uuid];

    if (!form) {
        return;
    }

    editError[harvest.batch_uuid] = '';
    editSaving[harvest.batch_uuid] = true;

    try {
        const response = await http.post('/api/harvest', [
            {
                batch_uuid: harvest.batch_uuid,
                product_uuid: form.product_uuid,
                warehouse_uuid: form.warehouse_uuid,
                corporation_uuid: form.corporation_uuid,
                quantity: Number(form.quantity),
                location: form.location || null,
                harvested_on: toApiDate(form.harvested_on),
                expires_on: toApiDate(form.expires_on),
                quality: form.quality,
                replace_quantity: true,
                regenerate_qr: form.regenerate_qr,
            },
        ]);

        if (response?.data?.queued || !navigator.onLine) {
            const optimisticHarvest = normalizeHarvest({
                ...harvest,
                product_uuid: form.product_uuid,
                product_name: getProductByUuid(form.product_uuid)?.name ?? harvest.product_name,
                warehouse_uuid: form.warehouse_uuid,
                warehouse_name: warehouses.value.find((w) => w.uuid === form.warehouse_uuid)?.name ?? harvest.warehouse_name,
                corporation_uuid: form.corporation_uuid,
                corporation_name: corporations.value.find((c) => c.uuid === form.corporation_uuid)?.name ?? harvest.corporation_name,
                quantity: Number(form.quantity),
                location: form.location,
                harvested_on: toApiDate(form.harvested_on),
                expires_on: toApiDate(form.expires_on),
                quality: form.quality,
            });

            harvests.value = harvests.value.map((item) => item.batch_uuid === harvest.batch_uuid ? optimisticHarvest : item);
            upsertCachedRow(CACHE_KEY, optimisticHarvest, 'batch_uuid');
            await renderQrImage(optimisticHarvest);
            editStates[harvest.batch_uuid] = false;
            return;
        }

        editStates[harvest.batch_uuid] = false;
        await loadHarvests();
    } catch {
        editError[harvest.batch_uuid] = t('harvests.messages.save_error');
    } finally {
        editSaving[harvest.batch_uuid] = false;
    }
}

async function deleteHarvest(harvest) {
    const label = harvest.product_name || t('harvests.unnamed');
    const confirmed = window.confirm(t('harvests.messages.delete_confirm', { name: label }));

    if (!confirmed) {
        return;
    }

    deleteSaving[harvest.batch_uuid] = true;

    try {
        const response = await http.delete(`/api/harvest/${harvest.batch_uuid}`);

        if (response?.data?.queued || !navigator.onLine) {
            harvests.value = harvests.value.filter((item) => item.batch_uuid !== harvest.batch_uuid);
            removeCachedRow(CACHE_KEY, harvest.batch_uuid, 'batch_uuid');
            return;
        }

        await loadHarvests();
    } catch {
        editError[harvest.batch_uuid] = t('harvests.messages.delete_error');
    } finally {
        deleteSaving[harvest.batch_uuid] = false;
    }
}

async function initializePage() {
    loading.value = true;

    try {
        await loadLookups();
        await loadHarvests();
    } catch {
        loadError.value = t('harvests.messages.load_error');
        loading.value = false;
    }
}

async function handleQueueSynced(event) {
    const url = String(event?.detail?.url ?? '');

    if (!url.startsWith('/api/harvest')) {
        return;
    }

    await loadHarvests();
}

function handleQueueDropped(event) {
    const url = String(event?.detail?.url ?? '');

    if (!url.startsWith('/api/harvest')) {
        return;
    }

    loadError.value = t('harvests.messages.save_error');
}

onMounted(() => {
    window.addEventListener('offline-queue:synced', handleQueueSynced);
    window.addEventListener('offline-queue:dropped', handleQueueDropped);
});

onBeforeUnmount(() => {
    window.removeEventListener('offline-queue:synced', handleQueueSynced);
    window.removeEventListener('offline-queue:dropped', handleQueueDropped);
});

void initializePage();
</script>

<template>
    <section class="mx-auto max-w-5xl rounded-xl border border-[#dde5d7] bg-white/70 p-6">
        <header class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#1f2a1d]">{{ t('harvests.title') }}</h1>
                <p class="text-sm text-[#4e5f4f]">{{ t('harvests.subtitle') }}</p>
            </div>

            <button
                type="button"
                class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f]"
                @click="addFormOpen = !addFormOpen"
            >
                {{ addFormOpen ? t('harvests.actions.close_add_form') : t('harvests.actions.add_harvest') }}
            </button>
        </header>

        <form v-if="addFormOpen" class="mb-6 rounded-lg border border-[#ccd8c7] bg-white p-4" @submit.prevent="addHarvest">
            <h2 class="mb-3 text-lg font-semibold text-[#1f2a1d]">{{ t('harvests.actions.add_harvest') }}</h2>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.product') }}</span>
                    <select v-model="newHarvest.product_uuid" required class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        <option v-for="product in products" :key="product.uuid" :value="product.uuid">
                            {{ product.name || t('harvests.unnamed') }}
                        </option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.warehouse') }}</span>
                    <select v-model="newHarvest.warehouse_uuid" required class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        <option v-for="warehouse in warehouses" :key="warehouse.uuid" :value="warehouse.uuid">
                            {{ warehouse.name }}
                        </option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.corporation') }}</span>
                    <select v-model="newHarvest.corporation_uuid" required class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        <option v-for="corporation in corporations" :key="corporation.uuid" :value="corporation.uuid">
                            {{ corporation.name }}
                        </option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">
                        {{ t('harvests.fields.quantity') }} ({{ getUnitLabelForProduct(newHarvest.product_uuid) }})
                    </span>
                    <input
                        v-model.number="newHarvest.quantity"
                        required
                        min="0"
                        :step="getQuantityStepForProduct(newHarvest.product_uuid)"
                        type="number"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.quality') }}</span>
                    <select v-model="newHarvest.quality" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        <option v-for="option in qualityOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.location') }}</span>
                    <input v-model="newHarvest.location" type="text" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.harvested_on') }}</span>
                    <input v-model="newHarvest.harvested_on" required type="datetime-local" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.expires_on') }}</span>
                    <input v-model="newHarvest.expires_on" type="datetime-local" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                </label>
            </div>

            <div class="mt-3 flex items-center gap-3">
                <button
                    type="submit"
                    :disabled="savingAdd"
                    class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f] disabled:cursor-not-allowed disabled:opacity-70"
                >
                    {{ savingAdd ? t('harvests.actions.saving') : t('harvests.actions.save_harvest') }}
                </button>
                <p v-if="addError" class="text-sm text-red-700">{{ addError }}</p>
            </div>
        </form>

        <p v-if="loading" class="text-sm text-[#4e5f4f]">{{ t('harvests.messages.loading') }}</p>
        <p v-if="loadError" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ loadError }}</p>

        <label class="mb-4 block" v-if="!loading">
            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.messages.search_label') }}</span>
            <input
                v-model="searchTerm"
                type="text"
                :placeholder="t('harvests.messages.search_placeholder')"
                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
            >
        </label>

        <div v-if="!loading" class="space-y-4">
            <article
                v-for="harvest in filteredHarvests"
                :key="harvest.batch_uuid"
                class="rounded-lg border border-[#ccd8c7] bg-white p-4"
            >
                <div class="flex flex-col justify-between gap-3 md:flex-row md:items-start">
                    <div class="space-y-1">
                        <h3 class="text-lg font-semibold text-[#1f2a1d]">{{ harvest.product_name || t('harvests.unnamed') }}</h3>
                        <p class="text-sm text-[#4e5f4f]">
                            {{ t('harvests.fields.quantity') }}: {{ harvest.quantity }} {{ getUnitLabelForProduct(harvest.product_uuid) }}
                        </p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('harvests.fields.warehouse') }}: {{ harvest.warehouse_name || '-' }}</p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('harvests.fields.corporation') }}: {{ harvest.corporation_name || '-' }}</p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('harvests.fields.harvested_on') }}: {{ harvest.harvested_on || '-' }}</p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('harvests.fields.quality') }}: {{ harvest.quality || '-' }}</p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('harvests.fields.qr_code') }}: {{ harvest.qr_code || '-' }}</p>
                    </div>

                    <div class="space-y-2">
                        <img v-if="qrImages[harvest.batch_uuid]" :src="qrImages[harvest.batch_uuid]" :alt="t('harvests.fields.qr_code')" class="h-[120px] w-[120px] rounded border border-[#ccd8c7] bg-white p-1">
                        <button
                            v-if="qrImages[harvest.batch_uuid]"
                            type="button"
                            class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-1.5 text-sm hover:bg-[#f4f8ed]"
                            @click="printQr(harvest)"
                        >
                            {{ t('harvests.actions.print_qr') }}
                        </button>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-1.5 text-sm hover:bg-[#f4f8ed]"
                                @click="openEdit(harvest)"
                            >
                                {{ t('harvests.actions.edit') }}
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-70"
                                :disabled="deleteSaving[harvest.batch_uuid]"
                                @click="deleteHarvest(harvest)"
                            >
                                {{ deleteSaving[harvest.batch_uuid] ? t('harvests.actions.deleting') : t('harvests.actions.delete') }}
                            </button>
                        </div>
                    </div>
                </div>

                <form
                    v-if="editStates[harvest.batch_uuid]"
                    class="mt-4 rounded-lg border border-[#ccd8c7] bg-[#f9fcf8] p-4"
                    @submit.prevent="saveEdit(harvest)"
                >
                    <h4 class="mb-3 text-base font-semibold text-[#1f2a1d]">{{ t('harvests.actions.edit_harvest') }}</h4>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.product') }}</span>
                            <select v-model="editForms[harvest.batch_uuid].product_uuid" required class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                                <option v-for="product in products" :key="product.uuid" :value="product.uuid">
                                    {{ product.name || t('harvests.unnamed') }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.warehouse') }}</span>
                            <select v-model="editForms[harvest.batch_uuid].warehouse_uuid" required class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                                <option v-for="warehouse in warehouses" :key="warehouse.uuid" :value="warehouse.uuid">
                                    {{ warehouse.name }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.corporation') }}</span>
                            <select v-model="editForms[harvest.batch_uuid].corporation_uuid" required class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                                <option v-for="corporation in corporations" :key="corporation.uuid" :value="corporation.uuid">
                                    {{ corporation.name }}
                                </option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">
                                {{ t('harvests.fields.quantity') }} ({{ getUnitLabelForProduct(editForms[harvest.batch_uuid].product_uuid) }})
                            </span>
                            <input
                                v-model.number="editForms[harvest.batch_uuid].quantity"
                                required
                                min="0"
                                :step="getQuantityStepForProduct(editForms[harvest.batch_uuid].product_uuid)"
                                type="number"
                                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                            >
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.quality') }}</span>
                            <select v-model="editForms[harvest.batch_uuid].quality" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                                <option v-for="option in qualityOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.location') }}</span>
                            <input v-model="editForms[harvest.batch_uuid].location" type="text" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.harvested_on') }}</span>
                            <input v-model="editForms[harvest.batch_uuid].harvested_on" required type="datetime-local" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('harvests.fields.expires_on') }}</span>
                            <input v-model="editForms[harvest.batch_uuid].expires_on" type="datetime-local" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        </label>
                    </div>

                    <label class="mt-3 inline-flex cursor-pointer items-center gap-2 text-sm text-[#1f2a1d]">
                        <input v-model="editForms[harvest.batch_uuid].regenerate_qr" type="checkbox" class="h-4 w-4 rounded border-[#ccd8c7]">
                        {{ t('harvests.actions.regenerate_qr') }}
                    </label>

                    <div class="mt-3 flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="editSaving[harvest.batch_uuid]"
                            class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f] disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            {{ editSaving[harvest.batch_uuid] ? t('harvests.actions.saving') : t('harvests.actions.save_changes') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-[#ccd8c7] bg-white px-4 py-2 font-medium text-[#1f2a1d] hover:bg-[#f4f8ed]"
                            @click="cancelEdit(harvest)"
                        >
                            {{ t('harvests.actions.cancel') }}
                        </button>
                        <p v-if="editError[harvest.batch_uuid]" class="text-sm text-red-700">{{ editError[harvest.batch_uuid] }}</p>
                    </div>
                </form>
            </article>

            <p v-if="filteredHarvests.length === 0" class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 text-sm text-[#4e5f4f]">
                {{ t('harvests.messages.empty') }}
            </p>
        </div>
    </section>
</template>
