<script setup>
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import { computed, reactive, ref } from 'vue';
import { nextTick, onBeforeUnmount, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import http from '../lib/http';

const { t } = useI18n();

const warehouses = ref([]);
const corporations = ref([]);
const loading = ref(true);
const loadError = ref('');
const addFormOpen = ref(false);
const savingAdd = ref(false);
const addError = ref('');
const searchTerm = ref('');
const addGeocodingError = ref('');
const editGeocodingError = reactive({});
const addMapContainer = ref(null);
const editMapContainer = ref(null);
const activeEditUuid = ref('');

const editStates = reactive({});
const editForms = reactive({});
const editSaving = reactive({});
const editError = reactive({});
const deleteSaving = reactive({});

const newWarehouse = reactive({
    name: '',
    corporation_uuid: '',
    capacity: null,
    address: '',
    city: '',
    state: '',
    country: '',
    locationLatitude: '',
    locationLongitude: '',
});

let addMapInstance = null;
let addMarker = null;
let editMapInstance = null;
let editMarker = null;

function resolveMapContainer(refValue) {
    const element = Array.isArray(refValue) ? refValue[0] : refValue;

    if (!element || typeof element.addEventListener !== 'function') {
        return null;
    }

    return element;
}

const filteredWarehouses = computed(() => {
    const query = searchTerm.value.trim().toLowerCase();

    if (!query) {
        return warehouses.value;
    }

    return warehouses.value.filter((warehouse) => {
        return [
            warehouse.name,
            warehouse.corporation_name,
            warehouse.address,
            warehouse.city,
            warehouse.state,
            warehouse.country,
            warehouse.uuid,
        ].some((value) => String(value || '').toLowerCase().includes(query));
    });
});

function normalizeWarehouse(warehouse) {
    const location = parseLocation(warehouse.location);

    return {
        uuid: warehouse.uuid,
        name: warehouse.name ?? '',
        corporation_uuid: warehouse.corporation_uuid ?? '',
        corporation_name: warehouse.corporation_name ?? '',
        capacity: warehouse.capacity ?? null,
        address: warehouse.address ?? '',
        city: warehouse.city ?? '',
        state: warehouse.state ?? '',
        country: warehouse.country ?? '',
        locationLatitude: location.latitude,
        locationLongitude: location.longitude,
    };
}

function parseLocation(location) {
    if (!location) {
        return { latitude: '', longitude: '' };
    }

    if (typeof location === 'string') {
        try {
            const parsed = JSON.parse(location);

            return {
                latitude: parsed?.latitude ?? parsed?.lat ?? '',
                longitude: parsed?.longitude ?? parsed?.lng ?? '',
            };
        } catch {
            return { latitude: '', longitude: '' };
        }
    }

    return {
        latitude: location?.latitude ?? location?.lat ?? '',
        longitude: location?.longitude ?? location?.lng ?? '',
    };
}

function buildLocationPayload(latitudeValue, longitudeValue) {
    const latitude = Number(latitudeValue);
    const longitude = Number(longitudeValue);

    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return null;
    }

    return {
        latitude,
        longitude,
    };
}

function initEditForm(warehouse) {
    editForms[warehouse.uuid] = {
        name: warehouse.name ?? '',
        corporation_uuid: warehouse.corporation_uuid ?? '',
        capacity: warehouse.capacity ?? null,
        address: warehouse.address ?? '',
        city: warehouse.city ?? '',
        state: warehouse.state ?? '',
        country: warehouse.country ?? '',
        locationLatitude: warehouse.locationLatitude ?? '',
        locationLongitude: warehouse.locationLongitude ?? '',
    };
}

function resetNewForm() {
    newWarehouse.name = '';
    newWarehouse.corporation_uuid = corporations.value[0]?.uuid ?? '';
    newWarehouse.capacity = null;
    newWarehouse.address = '';
    newWarehouse.city = '';
    newWarehouse.state = '';
    newWarehouse.country = '';
    newWarehouse.locationLatitude = '';
    newWarehouse.locationLongitude = '';
}

async function reverseGeocode(latitude, longitude) {
    const response = await http.get('/api/geocoding/reverse', {
        params: {
            lat: latitude,
            lng: longitude,
        },
    });

    return response.data ?? {};
}

function readCoordinates(latitudeValue, longitudeValue) {
    const latitude = Number(latitudeValue);
    const longitude = Number(longitudeValue);

    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return null;
    }

    return { latitude, longitude };
}

function updateAddMapSelection() {
    if (!addMapInstance) {
        return;
    }

    const coordinates = readCoordinates(newWarehouse.locationLatitude, newWarehouse.locationLongitude);

    if (!coordinates) {
        if (addMarker) {
            addMapInstance.removeLayer(addMarker);
            addMarker = null;
        }

        return;
    }

    const latLng = [coordinates.latitude, coordinates.longitude];

    if (!addMarker) {
        addMarker = L.circleMarker(latLng, {
            radius: 8,
            color: '#1d4d31',
            fillColor: '#2f6e4a',
            fillOpacity: 0.85,
            weight: 2,
        }).addTo(addMapInstance);
    } else {
        addMarker.setLatLng(latLng);
    }
}

async function setAddCoordinatesFromMapClick(latitude, longitude) {
    newWarehouse.locationLatitude = Number(latitude).toFixed(6);
    newWarehouse.locationLongitude = Number(longitude).toFixed(6);
    updateAddMapSelection();

    addGeocodingError.value = '';

    try {
        const geocoded = await reverseGeocode(latitude, longitude);

        newWarehouse.address = geocoded.address ?? '';
        newWarehouse.city = geocoded.city ?? '';
        newWarehouse.state = geocoded.state ?? '';
        newWarehouse.country = geocoded.country ?? '';
    } catch {
        addGeocodingError.value = t('warehouses.messages.geocoding_error');
    }
}

function initializeAddMap() {
    const container = resolveMapContainer(addMapContainer.value);

    if (!container) {
        return;
    }

    if (addMapInstance) {
        addMapInstance.invalidateSize();
        updateAddMapSelection();
        return;
    }

    const coordinates = readCoordinates(newWarehouse.locationLatitude, newWarehouse.locationLongitude);
    const mapCenter = coordinates ? [coordinates.latitude, coordinates.longitude] : [20, 0];
    const mapZoom = coordinates ? 12 : 2;

    addMapInstance = L.map(container, {
        zoomControl: true,
    }).setView(mapCenter, mapZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(addMapInstance);

    addMapInstance.on('click', (event) => {
        void setAddCoordinatesFromMapClick(event.latlng.lat, event.latlng.lng);
    });

    updateAddMapSelection();
    addMapInstance.invalidateSize();
}

function destroyAddMap() {
    if (!addMapInstance) {
        return;
    }

    addMapInstance.remove();
    addMapInstance = null;
    addMarker = null;
}

function updateEditMapSelection(warehouseUuid) {
    if (!editMapInstance || !editForms[warehouseUuid]) {
        return;
    }

    const form = editForms[warehouseUuid];
    const coordinates = readCoordinates(form.locationLatitude, form.locationLongitude);

    if (!coordinates) {
        if (editMarker) {
            editMapInstance.removeLayer(editMarker);
            editMarker = null;
        }

        return;
    }

    const latLng = [coordinates.latitude, coordinates.longitude];

    if (!editMarker) {
        editMarker = L.circleMarker(latLng, {
            radius: 8,
            color: '#1d4d31',
            fillColor: '#2f6e4a',
            fillOpacity: 0.85,
            weight: 2,
        }).addTo(editMapInstance);
    } else {
        editMarker.setLatLng(latLng);
    }
}

async function setEditCoordinatesFromMapClick(warehouseUuid, latitude, longitude) {
    const form = editForms[warehouseUuid];

    if (!form) {
        return;
    }

    form.locationLatitude = Number(latitude).toFixed(6);
    form.locationLongitude = Number(longitude).toFixed(6);
    updateEditMapSelection(warehouseUuid);

    editGeocodingError[warehouseUuid] = '';

    try {
        const geocoded = await reverseGeocode(latitude, longitude);

        form.address = geocoded.address ?? '';
        form.city = geocoded.city ?? '';
        form.state = geocoded.state ?? '';
        form.country = geocoded.country ?? '';
    } catch {
        editGeocodingError[warehouseUuid] = t('warehouses.messages.geocoding_error');
    }
}

function initializeEditMap(warehouseUuid) {
    const container = resolveMapContainer(editMapContainer.value);

    if (!container || !editForms[warehouseUuid]) {
        return;
    }

    if (editMapInstance) {
        editMapInstance.invalidateSize();
        updateEditMapSelection(warehouseUuid);
        return;
    }

    const form = editForms[warehouseUuid];
    const coordinates = readCoordinates(form.locationLatitude, form.locationLongitude);
    const mapCenter = coordinates ? [coordinates.latitude, coordinates.longitude] : [20, 0];
    const mapZoom = coordinates ? 12 : 2;

    editMapInstance = L.map(container, {
        zoomControl: true,
    }).setView(mapCenter, mapZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(editMapInstance);

    editMapInstance.on('click', (event) => {
        void setEditCoordinatesFromMapClick(warehouseUuid, event.latlng.lat, event.latlng.lng);
    });

    updateEditMapSelection(warehouseUuid);
    editMapInstance.invalidateSize();
}

function destroyEditMap() {
    if (!editMapInstance) {
        return;
    }

    editMapInstance.remove();
    editMapInstance = null;
    editMarker = null;
}

async function loadWarehouses() {
    loading.value = true;
    loadError.value = '';

    try {
        const [warehousesResponse, corporationsResponse] = await Promise.all([
            http.get('/api/warehouse'),
            http.get('/api/corporations'),
        ]);

        warehouses.value = Array.isArray(warehousesResponse.data)
            ? warehousesResponse.data.map(normalizeWarehouse)
            : [];

        corporations.value = Array.isArray(corporationsResponse.data)
            ? corporationsResponse.data
            : [];

        if (!newWarehouse.corporation_uuid && corporations.value.length > 0) {
            newWarehouse.corporation_uuid = corporations.value[0].uuid;
        }

        warehouses.value.forEach((warehouse) => {
            initEditForm(warehouse);
            if (!(warehouse.uuid in editStates)) {
                editStates[warehouse.uuid] = false;
            }
        });
    } catch {
        loadError.value = t('warehouses.messages.load_error');
    } finally {
        loading.value = false;
    }
}

async function addWarehouse() {
    addError.value = '';
    savingAdd.value = true;

    try {
        await http.post('/api/warehouse', [
            {
                name: newWarehouse.name,
                corporation_uuid: newWarehouse.corporation_uuid || null,
                capacity: newWarehouse.capacity,
                address: newWarehouse.address || null,
                city: newWarehouse.city || null,
                state: newWarehouse.state || null,
                country: newWarehouse.country || null,
                location: buildLocationPayload(newWarehouse.locationLatitude, newWarehouse.locationLongitude),
            },
        ]);

        resetNewForm();
        addFormOpen.value = false;
        await loadWarehouses();
    } catch {
        addError.value = t('warehouses.messages.add_error');
    } finally {
        savingAdd.value = false;
    }
}

function openEdit(warehouse) {
    Object.keys(editStates).forEach((key) => {
        editStates[key] = false;
    });

    editError[warehouse.uuid] = '';
    editGeocodingError[warehouse.uuid] = '';
    editStates[warehouse.uuid] = true;
    initEditForm(warehouse);
    activeEditUuid.value = warehouse.uuid;
}

function cancelEdit(warehouse) {
    editError[warehouse.uuid] = '';
    editGeocodingError[warehouse.uuid] = '';
    editStates[warehouse.uuid] = false;
    initEditForm(warehouse);

    if (activeEditUuid.value === warehouse.uuid) {
        activeEditUuid.value = '';
    }
}

async function saveEdit(warehouse) {
    const form = editForms[warehouse.uuid];

    if (!form) {
        return;
    }

    editError[warehouse.uuid] = '';
    editSaving[warehouse.uuid] = true;

    try {
        await http.post('/api/warehouse', [
            {
                uuid: warehouse.uuid,
                name: form.name,
                corporation_uuid: form.corporation_uuid || null,
                capacity: form.capacity,
                address: form.address || null,
                city: form.city || null,
                state: form.state || null,
                country: form.country || null,
                location: buildLocationPayload(form.locationLatitude, form.locationLongitude),
            },
        ]);

        editStates[warehouse.uuid] = false;
        activeEditUuid.value = '';
        await loadWarehouses();
    } catch {
        editError[warehouse.uuid] = t('warehouses.messages.save_error');
    } finally {
        editSaving[warehouse.uuid] = false;
    }
}

async function deleteWarehouse(warehouse) {
    const warehouseName = warehouse.name || t('warehouses.unnamed');
    const confirmed = window.confirm(t('warehouses.messages.delete_confirm', { name: warehouseName }));

    if (!confirmed) {
        return;
    }

    deleteSaving[warehouse.uuid] = true;

    try {
        await http.delete(`/api/warehouse/${warehouse.uuid}`);
        await loadWarehouses();
    } catch (error) {
        if (error?.response?.status === 409) {
            editError[warehouse.uuid] = t('warehouses.messages.delete_linked_error');
        } else {
            editError[warehouse.uuid] = t('warehouses.messages.delete_error');
        }
    } finally {
        deleteSaving[warehouse.uuid] = false;
    }
}

void loadWarehouses();

watch(addFormOpen, async (open) => {
    if (!open) {
        destroyAddMap();
        addGeocodingError.value = '';
        return;
    }

    await nextTick();
    initializeAddMap();
    requestAnimationFrame(() => {
        addMapInstance?.invalidateSize();
    });
});

watch(activeEditUuid, async (warehouseUuid) => {
    destroyEditMap();

    if (!warehouseUuid || !editStates[warehouseUuid]) {
        return;
    }

    await nextTick();
    initializeEditMap(warehouseUuid);
    requestAnimationFrame(() => {
        editMapInstance?.invalidateSize();
    });
});

onBeforeUnmount(() => {
    destroyAddMap();
    destroyEditMap();
});
</script>

<template>
    <section class="mx-auto max-w-5xl rounded-xl border border-[#dde5d7] bg-white/70 p-6">
        <header class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#1f2a1d]">{{ t('warehouses.title') }}</h1>
                <p class="text-sm text-[#4e5f4f]">{{ t('warehouses.subtitle') }}</p>
            </div>

            <button
                type="button"
                class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f]"
                @click="addFormOpen = !addFormOpen"
            >
                {{ addFormOpen ? t('warehouses.actions.close_add_form') : t('warehouses.actions.add_warehouse') }}
            </button>
        </header>

        <form v-if="addFormOpen" class="mb-6 rounded-lg border border-[#ccd8c7] bg-white p-4" @submit.prevent="addWarehouse">
            <h2 class="mb-3 text-lg font-semibold text-[#1f2a1d]">{{ t('warehouses.actions.add_warehouse') }}</h2>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.name') }}</span>
                    <input v-model="newWarehouse.name" required type="text" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.corporation') }}</span>
                    <select v-model="newWarehouse.corporation_uuid" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        <option value="">-</option>
                        <option v-for="corporation in corporations" :key="corporation.uuid" :value="corporation.uuid">{{ corporation.name }}</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.capacity') }}</span>
                    <input v-model.number="newWarehouse.capacity" min="0" step="0.01" type="number" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.address') }}</span>
                    <input v-model="newWarehouse.address" type="text" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.city') }}</span>
                    <input v-model="newWarehouse.city" type="text" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.state') }}</span>
                    <input v-model="newWarehouse.state" type="text" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.country') }}</span>
                    <input v-model="newWarehouse.country" type="text" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                </label>

                <div class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                    <p class="mb-1 text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.latitude') }}</p>
                    <p class="text-[#1f2a1d]">{{ newWarehouse.locationLatitude || '-' }}</p>
                </div>

                <div class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                    <p class="mb-1 text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.longitude') }}</p>
                    <p class="text-[#1f2a1d]">{{ newWarehouse.locationLongitude || '-' }}</p>
                </div>

                <div class="md:col-span-2">
                    <p class="mb-2 text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.map_label') }}</p>
                    <p class="mb-2 text-xs text-[#4e5f4f]">{{ t('warehouses.map_hint') }}</p>
                    <div ref="addMapContainer" class="h-72 w-full rounded-lg border border-[#ccd8c7]"></div>
                    <p v-if="addGeocodingError" class="mt-2 text-xs text-red-700">{{ addGeocodingError }}</p>
                </div>
            </div>

            <div class="mt-3 flex items-center gap-3">
                <button type="submit" :disabled="savingAdd" class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f] disabled:opacity-70">
                    {{ savingAdd ? t('warehouses.actions.saving') : t('warehouses.actions.save_warehouse') }}
                </button>
                <p v-if="addError" class="text-sm text-red-700">{{ addError }}</p>
            </div>
        </form>

        <p v-if="loading" class="text-sm text-[#4e5f4f]">{{ t('warehouses.messages.loading') }}</p>
        <p v-if="loadError" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ loadError }}</p>

        <label class="mb-4 block" v-if="!loading">
            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.messages.search_label') }}</span>
            <input
                v-model="searchTerm"
                type="text"
                :placeholder="t('warehouses.messages.search_placeholder')"
                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
            >
        </label>

        <div v-if="!loading" class="space-y-4">
            <article v-for="warehouse in filteredWarehouses" :key="warehouse.uuid" class="rounded-lg border border-[#ccd8c7] bg-white p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-[#1f2a1d]">{{ warehouse.name || t('warehouses.unnamed') }}</h3>
                        <p class="text-sm text-[#4e5f4f]">{{ t('warehouses.fields.corporation') }}: {{ warehouse.corporation_name || '-' }}</p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('warehouses.fields.capacity') }}: {{ warehouse.capacity ?? '-' }}</p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('warehouses.fields.address') }}: {{ warehouse.address || '-' }}</p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('warehouses.fields.city') }}: {{ warehouse.city || '-' }}</p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('warehouses.fields.state') }}: {{ warehouse.state || '-' }}</p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('warehouses.fields.country') }}: {{ warehouse.country || '-' }}</p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('warehouses.fields.latitude') }}: {{ warehouse.locationLatitude || '-' }}</p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('warehouses.fields.longitude') }}: {{ warehouse.locationLongitude || '-' }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-1.5 text-sm hover:bg-[#f4f8ed]" @click="openEdit(warehouse)">
                            {{ t('warehouses.actions.edit') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm text-red-700 hover:bg-red-100 disabled:opacity-70"
                            :disabled="deleteSaving[warehouse.uuid]"
                            @click="deleteWarehouse(warehouse)"
                        >
                            {{ deleteSaving[warehouse.uuid] ? t('warehouses.actions.deleting') : t('warehouses.actions.delete') }}
                        </button>
                    </div>
                </div>

                <form v-if="editStates[warehouse.uuid]" class="mt-4 rounded-lg border border-[#ccd8c7] bg-[#f9fcf8] p-4" @submit.prevent="saveEdit(warehouse)">
                    <h4 class="mb-3 text-base font-semibold text-[#1f2a1d]">{{ t('warehouses.actions.edit_warehouse') }}</h4>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.name') }}</span>
                            <input v-model="editForms[warehouse.uuid].name" required type="text" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.corporation') }}</span>
                            <select v-model="editForms[warehouse.uuid].corporation_uuid" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                                <option value="">-</option>
                                <option v-for="corporation in corporations" :key="corporation.uuid" :value="corporation.uuid">{{ corporation.name }}</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.capacity') }}</span>
                            <input v-model.number="editForms[warehouse.uuid].capacity" min="0" step="0.01" type="number" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.address') }}</span>
                            <input v-model="editForms[warehouse.uuid].address" type="text" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.city') }}</span>
                            <input v-model="editForms[warehouse.uuid].city" type="text" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.state') }}</span>
                            <input v-model="editForms[warehouse.uuid].state" type="text" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.country') }}</span>
                            <input v-model="editForms[warehouse.uuid].country" type="text" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        </label>

                        <div class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                            <p class="mb-1 text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.latitude') }}</p>
                            <p class="text-[#1f2a1d]">{{ editForms[warehouse.uuid].locationLatitude || '-' }}</p>
                        </div>

                        <div class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                            <p class="mb-1 text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.fields.longitude') }}</p>
                            <p class="text-[#1f2a1d]">{{ editForms[warehouse.uuid].locationLongitude || '-' }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <p class="mb-2 text-sm font-medium text-[#1f2a1d]">{{ t('warehouses.map_label') }}</p>
                            <p class="mb-2 text-xs text-[#4e5f4f]">{{ t('warehouses.map_hint') }}</p>
                            <div ref="editMapContainer" class="h-72 w-full rounded-lg border border-[#ccd8c7]"></div>
                            <p v-if="editGeocodingError[warehouse.uuid]" class="mt-2 text-xs text-red-700">{{ editGeocodingError[warehouse.uuid] }}</p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-3">
                        <button type="submit" :disabled="editSaving[warehouse.uuid]" class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f] disabled:opacity-70">
                            {{ editSaving[warehouse.uuid] ? t('warehouses.actions.saving') : t('warehouses.actions.save_changes') }}
                        </button>
                        <button type="button" class="rounded-lg border border-[#ccd8c7] bg-white px-4 py-2 font-medium text-[#1f2a1d] hover:bg-[#f4f8ed]" @click="cancelEdit(warehouse)">
                            {{ t('warehouses.actions.cancel') }}
                        </button>
                        <p v-if="editError[warehouse.uuid]" class="text-sm text-red-700">{{ editError[warehouse.uuid] }}</p>
                    </div>
                </form>
            </article>

            <p v-if="filteredWarehouses.length === 0" class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 text-sm text-[#4e5f4f]">
                {{ t('warehouses.messages.empty') }}
            </p>
        </div>
    </section>
</template>
