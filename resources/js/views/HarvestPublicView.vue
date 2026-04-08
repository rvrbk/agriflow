<script setup>
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import http from '../lib/http';

const route = useRoute();
const { t } = useI18n();

const loading = ref(true);
const loadError = ref('');
const harvest = ref(null);

const items = computed(() => {
    if (!harvest.value) {
        return [];
    }

    return [
        { key: 'product', value: harvest.value.product_name || '-' },
        { key: 'product_uuid', value: harvest.value.product_uuid || '-' },
        { key: 'warehouse', value: harvest.value.warehouse_name || '-' },
        { key: 'warehouse_uuid', value: harvest.value.warehouse_uuid || '-' },
        { key: 'corporation', value: harvest.value.corporation_name || '-' },
        { key: 'corporation_uuid', value: harvest.value.corporation_uuid || '-' },
        { key: 'quantity', value: harvest.value.quantity ?? '-' },
        { key: 'location', value: harvest.value.location || '-' },
        { key: 'available_on', value: harvest.value.available_on || '-' },
        { key: 'harvested_on', value: harvest.value.harvested_on || '-' },
        { key: 'expires_on', value: harvest.value.expires_on || '-' },
        { key: 'quality', value: harvest.value.quality || '-' },
        { key: 'qr_code', value: harvest.value.qr_code || '-' },
        { key: 'qr_payload', value: harvest.value.qr_payload || '-' },
    ];
});

async function loadHarvest() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await http.get(`/api/harvest/public/${route.params.batchUuid}`);
        harvest.value = response.data;
    } catch {
        loadError.value = t('harvest_public.not_found');
    } finally {
        loading.value = false;
    }
}

void loadHarvest();
</script>

<template>
    <main class="min-h-screen bg-[radial-gradient(circle_at_top_right,_#eef5e2_0%,_#f6f8f2_42%)] p-4 text-[#1f2a1d] md:p-6">
        <article class="mx-auto max-w-5xl rounded-xl border border-[#ccd8c7] bg-white/90 p-5 shadow-sm md:p-6">
            <header class="mb-5 border-b border-[#e0e7db] pb-4">
                <h1 class="text-2xl font-bold">{{ t('harvest_public.title') }}</h1>
                <p class="mt-1 text-sm text-[#4e5f4f]">
                    {{ t('harvest_public.batch', { uuid: route.params.batchUuid }) }}
                </p>
            </header>

            <p v-if="loading" class="text-sm text-[#4e5f4f]">{{ t('harvests.messages.loading') }}</p>
            <p v-else-if="loadError" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ loadError }}</p>

            <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="item in items"
                    :key="item.key"
                    :class="[
                        'rounded-lg border border-[#dbe3d5] bg-[#fcfffa] p-3',
                        item.key === 'qr_payload' ? 'md:col-span-2 lg:col-span-2' : '',
                    ]"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#4e5f4f]">
                        {{ t(`harvest_public.fields.${item.key}`) }}
                    </p>
                    <p class="mt-1 break-words text-sm">{{ item.value }}</p>
                </div>
            </div>
        </article>
    </main>
</template>
