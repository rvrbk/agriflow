<script setup>
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import http from '../lib/http';

const { t } = useI18n();

const rows = ref([]);
const loading = ref(true);
const loadError = ref('');
const searchTerm = ref('');

const filteredRows = computed(() => {
    const query = searchTerm.value.trim().toLowerCase();

    if (!query) {
        return rows.value;
    }

    return rows.value.filter((row) => {
        return [
            row.product_name,
            row.batch_uuid,
            row.warehouse_name,
            row.quality,
            row.location,
            row.harvested_on,
        ].some((value) => String(value || '').toLowerCase().includes(query));
    });
});

function getUnitLabel(unit) {
    if (['kg', 'g', 'l', 'ml', 'pcs'].includes(unit)) {
        return t(`products.units.${unit}`);
    }

    return (unit || '').toUpperCase();
}

async function loadInventory() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await http.get('/api/inventory');
        rows.value = Array.isArray(response.data) ? response.data : [];
    } catch {
        loadError.value = t('inventory.messages.load_error');
    } finally {
        loading.value = false;
    }
}

void loadInventory();
</script>

<template>
    <section class="mx-auto max-w-6xl rounded-xl border border-[#dde5d7] bg-white/70 p-6">
        <header class="mb-4">
            <h1 class="text-2xl font-bold text-[#1f2a1d]">{{ t('inventory.title') }}</h1>
            <p class="text-sm text-[#4e5f4f]">{{ t('inventory.subtitle') }}</p>
        </header>

        <p v-if="loading" class="text-sm text-[#4e5f4f]">{{ t('inventory.messages.loading') }}</p>
        <p v-if="loadError" class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ loadError }}</p>

        <label class="mb-4 block" v-if="!loading">
            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('inventory.messages.search_label') }}</span>
            <input
                v-model="searchTerm"
                type="text"
                :placeholder="t('inventory.messages.search_placeholder')"
                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
            >
        </label>

        <div v-if="!loading" class="space-y-4">
            <article v-for="row in filteredRows" :key="row.inventory_id" class="rounded-lg border border-[#ccd8c7] bg-white p-4">
                <div class="grid grid-cols-1 gap-2 text-sm text-[#4e5f4f] md:grid-cols-2 lg:grid-cols-3">
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('inventory.fields.product') }}:</span> {{ row.product_name || '-' }}</p>
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('inventory.fields.batch') }}:</span> {{ row.batch_uuid || '-' }}</p>
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('inventory.fields.harvested_on') }}:</span> {{ row.harvested_on || '-' }}</p>
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('inventory.fields.warehouse') }}:</span> {{ row.warehouse_name || '-' }}</p>
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('inventory.fields.quality') }}:</span> {{ row.quality || '-' }}</p>
                    <p>
                        <span class="font-semibold text-[#1f2a1d]">{{ t('inventory.fields.quantity') }}:</span>
                        {{ row.quantity }} {{ getUnitLabel(row.product_unit) }}
                    </p>
                </div>

            </article>

            <p v-if="filteredRows.length === 0" class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 text-sm text-[#4e5f4f]">
                {{ t('inventory.messages.empty') }}
            </p>
        </div>
    </section>
</template>
