<script setup>
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import http from '../lib/http';

const { t } = useI18n();

const form = reactive({
    uuid: '',
    name: '',
    address: '',
    city: '',
    state: '',
    country: '',
    locationLatitude: '',
    locationLongitude: '',
});

const loading = ref(true);
const saving = ref(false);
const loadError = ref('');
const saveMessage = ref('');
const saveError = ref('');
const isEditing = ref(false);
const mapContainer = ref(null);
const countries = ref([]);
const countriesLoading = ref(false);
const countriesError = ref('');
const geocodingError = ref('');

let mapInstance = null;
let selectedPointLayer = null;

const emptySnapshot = {
    uuid: '',
    name: '',
    address: '',
    city: '',
    state: '',
    country: '',
    locationLatitude: '',
    locationLongitude: '',
};

let formSnapshot = { ...emptySnapshot };

const countryByCode = computed(() => {
    return countries.value.reduce((accumulator, item) => {
        accumulator[item.code] = item.name;
        return accumulator;
    }, {});
});

const selectedCountryName = computed(() => {
    if (!form.country) {
        return t('corporation.empty');
    }

    return countryByCode.value[form.country] ?? form.country;
});

function updateSnapshot() {
    formSnapshot = {
        uuid: form.uuid,
        name: form.name,
        address: form.address,
        city: form.city,
        state: form.state,
        country: form.country,
        locationLatitude: form.locationLatitude,
        locationLongitude: form.locationLongitude,
    };
}

function restoreSnapshot() {
    form.uuid = formSnapshot.uuid;
    form.name = formSnapshot.name;
    form.address = formSnapshot.address;
    form.city = formSnapshot.city;
    form.state = formSnapshot.state;
    form.country = formSnapshot.country;
    form.locationLatitude = formSnapshot.locationLatitude;
    form.locationLongitude = formSnapshot.locationLongitude;
}

function setFormFromCorporation(corporation) {
    form.uuid = corporation?.uuid ?? '';
    form.name = corporation?.name ?? '';
    form.address = corporation?.address ?? '';
    form.city = corporation?.city ?? '';
    form.state = corporation?.state ?? '';
    form.country = corporation?.country ?? '';

    const location = corporation?.location;

    if (!location) {
        form.locationLatitude = '';
        form.locationLongitude = '';
        updateSnapshot();
        return;
    }

    if (typeof location === 'string') {
        try {
            const parsed = JSON.parse(location);
            form.locationLatitude = parsed?.latitude ?? parsed?.lat ?? '';
            form.locationLongitude = parsed?.longitude ?? parsed?.lng ?? '';
            updateSnapshot();
            return;
        } catch {
            form.locationLatitude = '';
            form.locationLongitude = '';
            updateSnapshot();
            return;
        }
    }

    form.locationLatitude = location?.latitude ?? location?.lat ?? '';
    form.locationLongitude = location?.longitude ?? location?.lng ?? '';

    updateSnapshot();
}

function readCoordinatesFromForm() {
    const latitude = Number(form.locationLatitude);
    const longitude = Number(form.locationLongitude);

    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return null;
    }

    return { latitude, longitude };
}

function updateMapSelection() {
    if (!mapInstance) {
        return;
    }

    const coordinates = readCoordinatesFromForm();

    if (!coordinates) {
        if (selectedPointLayer) {
            mapInstance.removeLayer(selectedPointLayer);
            selectedPointLayer = null;
        }

        return;
    }

    const latLng = [coordinates.latitude, coordinates.longitude];

    if (!selectedPointLayer) {
        selectedPointLayer = L.circleMarker(latLng, {
            radius: 8,
            color: '#1d4d31',
            fillColor: '#2f6e4a',
            fillOpacity: 0.85,
            weight: 2,
        }).addTo(mapInstance);
    } else {
        selectedPointLayer.setLatLng(latLng);
    }
}

async function setCoordinatesFromMapClick(latitude, longitude) {
    form.locationLatitude = Number(latitude).toFixed(6);
    form.locationLongitude = Number(longitude).toFixed(6);
    updateMapSelection();

    geocodingError.value = '';

    try {
        const response = await http.get('/api/geocoding/reverse', {
            params: {
                lat: latitude,
                lng: longitude,
            },
        });

        const geocoded = response.data ?? {};

        form.address = geocoded.address ?? '';
        form.city = geocoded.city ?? '';
        form.state = geocoded.state ?? '';
        form.country = geocoded.country ?? '';
    } catch (error) {
        geocodingError.value = 'Could not fetch address details for this location.';
    }
}

function initializeMap() {
    if (!mapContainer.value) {
        return;
    }

    if (mapInstance) {
        mapInstance.invalidateSize();
        updateMapSelection();
        return;
    }

    const coordinates = readCoordinatesFromForm();
    const mapCenter = coordinates
        ? [coordinates.latitude, coordinates.longitude]
        : [20, 0];
    const mapZoom = coordinates ? 12 : 2;

    mapInstance = L.map(mapContainer.value, {
        zoomControl: true,
    }).setView(mapCenter, mapZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(mapInstance);

    mapInstance.on('click', (event) => {
        void setCoordinatesFromMapClick(event.latlng.lat, event.latlng.lng);
    });

    updateMapSelection();
    mapInstance.invalidateSize();
}

function destroyMap() {
    if (!mapInstance) {
        return;
    }

    mapInstance.remove();
    mapInstance = null;
    selectedPointLayer = null;
}

async function loadCorporation() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await http.get('/api/corporation');
        setFormFromCorporation(response.data);
    } catch (error) {
        loadError.value = t('corporation.load_error');
    } finally {
        loading.value = false;
    }
}

async function submitCorporation() {
    saving.value = true;
    saveMessage.value = '';
    saveError.value = '';

    const hasLat = String(form.locationLatitude).trim() !== '';
    const hasLng = String(form.locationLongitude).trim() !== '';

    const payload = {
        uuid: form.uuid || undefined,
        name: form.name,
        address: form.address || null,
        city: form.city || null,
        state: form.state || null,
        country: form.country || null,
        location: hasLat || hasLng
            ? {
                latitude: hasLat ? Number(form.locationLatitude) : null,
                longitude: hasLng ? Number(form.locationLongitude) : null,
            }
            : null,
    };

    try {
        await http.post('/api/corporation', [payload]);
        saveMessage.value = t('corporation.save_success');
        await loadCorporation();
        isEditing.value = false;
    } catch (error) {
        saveError.value = t('corporation.save_error');
    } finally {
        saving.value = false;
    }
}

function startEditing() {
    saveMessage.value = '';
    saveError.value = '';
    isEditing.value = true;
}

function cancelEditing() {
    restoreSnapshot();
    saveMessage.value = '';
    saveError.value = '';
    isEditing.value = false;
}

async function loadCountries() {
    countriesLoading.value = true;
    countriesError.value = '';

    try {
        const response = await http.get('/api/countries');
        countries.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        countriesError.value = t('corporation.countries_error');
        countries.value = [];
    } finally {
        countriesLoading.value = false;
    }
}

onMounted(async () => {
    await Promise.all([loadCountries(), loadCorporation()]);
});

watch(isEditing, async (editing) => {
    if (!editing) {
        destroyMap();
        return;
    }

    await nextTick();
    initializeMap();
    requestAnimationFrame(() => {
        mapInstance?.invalidateSize();
    });
});

onBeforeUnmount(() => {
    destroyMap();
});
</script>

<template>
    <section class="mx-auto max-w-4xl rounded-xl border border-[#dde5d7] bg-white/70 p-6">
        <header class="mb-6">
            <h1 class="text-2xl font-bold text-[#1f2a1d]">{{ t('corporation.title') }}</h1>
            <p class="mt-1 text-sm text-[#4e5f4f]">{{ t('corporation.subtitle') }}</p>
        </header>

        <p v-if="loading" class="mb-4 text-sm text-[#4e5f4f]">{{ t('corporation.loading') }}</p>
        <p v-if="loadError" class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ loadError }}</p>

        <div v-if="!loading && !isEditing" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                    <p class="text-xs font-medium text-[#4e5f4f]">{{ t('corporation.fields.name') }}</p>
                    <p class="text-[#1f2a1d]">{{ form.name || t('corporation.empty') }}</p>
                </div>
                <div class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                    <p class="text-xs font-medium text-[#4e5f4f]">{{ t('corporation.fields.address') }}</p>
                    <p class="text-[#1f2a1d]">{{ form.address || t('corporation.empty') }}</p>
                </div>
                <div class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                    <p class="text-xs font-medium text-[#4e5f4f]">{{ t('corporation.fields.city') }}</p>
                    <p class="text-[#1f2a1d]">{{ form.city || t('corporation.empty') }}</p>
                </div>
                <div class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                    <p class="text-xs font-medium text-[#4e5f4f]">{{ t('corporation.fields.state') }}</p>
                    <p class="text-[#1f2a1d]">{{ form.state || t('corporation.empty') }}</p>
                </div>
                <div class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                    <p class="text-xs font-medium text-[#4e5f4f]">{{ t('corporation.fields.country') }}</p>
                    <p class="text-[#1f2a1d]">{{ selectedCountryName }}</p>
                    <p v-if="form.country" class="text-xs text-[#4e5f4f]">{{ t('corporation.code_prefix') }}: {{ form.country }}</p>
                </div>
                <div class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                    <p class="text-xs font-medium text-[#4e5f4f]">{{ t('corporation.fields.latitude') }}</p>
                    <p class="text-[#1f2a1d]">{{ form.locationLatitude || t('corporation.empty') }}</p>
                </div>
                <div class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 md:col-span-2">
                    <p class="text-xs font-medium text-[#4e5f4f]">{{ t('corporation.fields.longitude') }}</p>
                    <p class="text-[#1f2a1d]">{{ form.locationLongitude || t('corporation.empty') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button
                    type="button"
                    class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f]"
                    @click="startEditing"
                >
                    {{ t('corporation.edit') }}
                </button>
            </div>
        </div>

        <form v-if="!loading && isEditing" class="space-y-4" @submit.prevent="submitCorporation">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('corporation.fields.name') }}</span>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 text-[#1f2a1d] outline-none focus:border-[#7aa67f]"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('corporation.fields.address') }}</span>
                    <input
                        v-model="form.address"
                        type="text"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 text-[#1f2a1d] outline-none focus:border-[#7aa67f]"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('corporation.fields.city') }}</span>
                    <input
                        v-model="form.city"
                        type="text"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 text-[#1f2a1d] outline-none focus:border-[#7aa67f]"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('corporation.fields.state') }}</span>
                    <input
                        v-model="form.state"
                        type="text"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 text-[#1f2a1d] outline-none focus:border-[#7aa67f]"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('corporation.fields.country') }}</span>
                    <select
                        v-model="form.country"
                        :disabled="countriesLoading"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 text-[#1f2a1d] outline-none focus:border-[#7aa67f]"
                    >
                        <option value="">{{ t('corporation.select_country') }}</option>
                        <option
                            v-for="country in countries"
                            :key="country.code"
                            :value="country.code"
                        >
                            {{ country.name }}
                        </option>
                    </select>
                    <p v-if="countriesError" class="mt-1 text-xs text-red-700">{{ countriesError }}</p>
                    <p v-else class="mt-1 text-xs text-[#4e5f4f]">{{ t('corporation.country_hint') }}</p>
                </label>

                <div class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                    <p class="mb-1 text-sm font-medium text-[#1f2a1d]">{{ t('corporation.fields.latitude') }}</p>
                    <p class="text-[#1f2a1d]">{{ form.locationLatitude || t('corporation.empty') }}</p>
                </div>

                <div class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                    <p class="mb-1 text-sm font-medium text-[#1f2a1d]">{{ t('corporation.fields.longitude') }}</p>
                    <p class="text-[#1f2a1d]">{{ form.locationLongitude || t('corporation.empty') }}</p>
                </div>

                <div class="md:col-span-2">
                    <p class="mb-2 text-sm font-medium text-[#1f2a1d]">{{ t('corporation.map_label') }}</p>
                    <p class="mb-2 text-xs text-[#4e5f4f]">{{ t('corporation.map_hint') }}</p>
                    <div ref="mapContainer" class="h-72 w-full rounded-lg border border-[#ccd8c7]"></div>
                    <p v-if="geocodingError" class="mt-2 text-xs text-red-700">{{ geocodingError }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="saving"
                    class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f] disabled:cursor-not-allowed disabled:opacity-70"
                >
                    {{ saving ? t('corporation.saving') : t('corporation.save') }}
                </button>
                <button
                    type="button"
                    :disabled="saving"
                    class="rounded-lg border border-[#ccd8c7] bg-white px-4 py-2 font-medium text-[#1f2a1d] hover:bg-[#f4f8ed] disabled:cursor-not-allowed disabled:opacity-70"
                    @click="cancelEditing"
                >
                    {{ t('corporation.cancel') }}
                </button>

                <p v-if="saveMessage" class="text-sm text-green-700">{{ saveMessage }}</p>
                <p v-if="saveError" class="text-sm text-red-700">{{ saveError }}</p>
            </div>
        </form>
    </section>
</template>
