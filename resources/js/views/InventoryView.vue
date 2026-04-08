<script setup>
import { reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import http from '../lib/http';

const { t } = useI18n();

const rows = ref([]);
const loading = ref(true);
const loadError = ref('');
const actionError = ref('');
const amountByBatch = reactive({});
const adjusting = reactive({});

function getUnitLabel(unit) {
    if (['kg', 'g', 'l', 'ml', 'pcs'].includes(unit)) {
        return t(`products.units.${unit}`);
    }

    return (unit || '').toUpperCase();
}

function getStep(unit) {
    return unit === 'pcs' ? 1 : 0.01;
}

async function loadInventory() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await http.get('/api/inventory');
        rows.value = Array.isArray(response.data) ? response.data : [];

        rows.value.forEach((row) => {
            if (!(row.batch_uuid in amountByBatch)) {
                amountByBatch[row.batch_uuid] = getStep(row.product_unit);
            }
        });
    } catch {
        loadError.value = t('inventory.messages.load_error');
    } finally {
        loading.value = false;
    }
}

async function adjust(row, direction) {
    const amount = Number(amountByBatch[row.batch_uuid]);

    if (!Number.isFinite(amount) || amount <= 0) {
        actionError.value = t('inventory.messages.invalid_amount');
        return;
    }

    actionError.value = '';
    adjusting[row.batch_uuid] = true;

    try {
        await http.post('/api/inventory/adjust', {
            batch_uuid: row.batch_uuid,
            amount,
            direction,
        });

        await loadInventory();
    } catch (error) {
        if (error?.response?.status === 422) {
            actionError.value = t('inventory.messages.insufficient_quantity');
        } else {
            actionError.value = t('inventory.messages.adjust_error');
        }
    } finally {
        adjusting[row.batch_uuid] = false;
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
        <p v-if="actionError" class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ actionError }}</p>

        <div v-if="!loading" class="space-y-4">
            <article v-for="row in rows" :key="row.inventory_id" class="rounded-lg border border-[#ccd8c7] bg-white p-4">
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

                <div class="mt-3 flex flex-col gap-2 md:flex-row md:items-center">
                    <input
                        v-model.number="amountByBatch[row.batch_uuid]"
                        type="number"
                        :step="getStep(row.product_unit)"
                        min="0"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 md:w-44"
                    >
                    <button
                        type="button"
                        class="rounded-lg bg-[#2f6e4a] px-4 py-2 text-sm font-medium text-white hover:bg-[#275d3f] disabled:opacity-70"
                        :disabled="adjusting[row.batch_uuid]"
                        @click="adjust(row, 'add')"
                    >
                        {{ t('inventory.actions.add') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100 disabled:opacity-70"
                        :disabled="adjusting[row.batch_uuid]"
                        @click="adjust(row, 'subtract')"
                    >
                        {{ t('inventory.actions.subtract') }}
                    </button>
                </div>
            </article>

            <p v-if="rows.length === 0" class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 text-sm text-[#4e5f4f]">
                {{ t('inventory.messages.empty') }}
            </p>
        </div>
    </section>
</template>
