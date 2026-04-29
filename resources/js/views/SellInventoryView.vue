<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import http from '../lib/http';
import { readCachedList, writeCachedList } from '../services/offlineDataCache';

const { t } = useI18n();

const rows = ref([]);
const loading = ref(true);
const loadError = ref('');
const searchTerm = ref('');
const actionError = ref('');
const actionSuccess = ref('');
const sellAmounts = reactive({});
const sellPrices = reactive({});
const sellCurrencies = reactive({});
const selling = reactive({});
const lastSaleUuid = ref(null);
const buyerName = ref('');
const INVENTORY_CACHE_KEY = 'inventory';
const SALES_CACHE_KEY = 'sales';

const currencyOptions = [
    { code: 'USD', symbol: '$', name: 'US Dollar' },
    { code: 'UGX', symbol: 'USh', name: 'Ugandan Shilling' },
];

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
        ].some((value) => String(value || '').toLowerCase().includes(query));
    });
});

function getUnitLabel(unit) {
    if (['kg', 'g', 'l', 'ml', 'pcs'].includes(unit)) {
        return t(`products.units.${unit}`);
    }

    return (unit || '').toUpperCase();
}

function getStep(unit) {
    return unit === 'pcs' ? 1 : 0.01;
}

function initializeSellCurrenciesForRows() {
    rows.value.forEach((row) => {
        if (!sellCurrencies[row.batch_uuid]) {
            sellCurrencies[row.batch_uuid] = 'UGX';
        }
    });
}

async function loadInventory() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await http.get('/api/inventory');
        rows.value = Array.isArray(response.data) ? response.data : [];
        writeCachedList(INVENTORY_CACHE_KEY, rows.value);
        initializeSellCurrenciesForRows();
    } catch {
        const cachedRows = readCachedList(INVENTORY_CACHE_KEY);

        if (cachedRows.length > 0) {
            rows.value = cachedRows;
            initializeSellCurrenciesForRows();
            loadError.value = '';
        } else {
            loadError.value = t('sales.messages.load_error');
        }
    } finally {
        loading.value = false;
    }
}

async function sell(row) {
    const amount = Number(sellAmounts[row.batch_uuid]);
    const price = Number(sellPrices[row.batch_uuid]);
    const currency = sellCurrencies[row.batch_uuid] || 'UGX';

    if (!Number.isFinite(amount) || amount <= 0) {
        actionError.value = t('sales.messages.invalid_amount');
        actionSuccess.value = '';
        return;
    }

    if (amount > row.quantity) {
        actionError.value = t('sales.messages.insufficient_quantity');
        actionSuccess.value = '';
        return;
    }

    if (!Number.isFinite(price) || price < 0) {
        actionError.value = t('sales.messages.invalid_price');
        actionSuccess.value = '';
        return;
    }

    actionError.value = '';
    actionSuccess.value = '';
    selling[row.batch_uuid] = true;

    try {
        const response = await http.post('/api/inventory/sell', {
            batch_uuid: row.batch_uuid,
            amount,
            price,
            currency,
            buyer_name: buyerName.value || null,
        });

        if (response?.data?.queued || !navigator.onLine) {
            const pendingSaleUuid = `pending-${Date.now()}`;

            rows.value = rows.value.map((item) => {
                if (item.batch_uuid !== row.batch_uuid) {
                    return item;
                }

                return {
                    ...item,
                    quantity: Math.max(0, Number(item.quantity || 0) - amount),
                };
            });
            writeCachedList(INVENTORY_CACHE_KEY, rows.value);

            const cachedSales = readCachedList(SALES_CACHE_KEY);
            cachedSales.unshift({
                uuid: pendingSaleUuid,
                product_name: row.product_name,
                product_unit: row.product_unit,
                batch_uuid: row.batch_uuid,
                harvested_on: row.harvested_on,
                warehouse_name: row.warehouse_name,
                quantity: amount,
                unit_price: price,
                currency,
                total_value: price * amount,
                buyer_name: buyerName.value || null,
                created_at: new Date().toISOString(),
                pending_sync: true,
            });
            writeCachedList(SALES_CACHE_KEY, cachedSales);

            lastSaleUuid.value = pendingSaleUuid;
            actionSuccess.value = t('products.messages.add_queued');
            sellAmounts[row.batch_uuid] = '';
            sellPrices[row.batch_uuid] = '';
            sellCurrencies[row.batch_uuid] = 'UGX';
            buyerName.value = '';
            return;
        }

        const saleData = response.data;
        lastSaleUuid.value = saleData.uuid;

        actionSuccess.value = t('sales.messages.sell_success');
        await loadInventory();
        sellAmounts[row.batch_uuid] = '';
        sellPrices[row.batch_uuid] = '';
        sellCurrencies[row.batch_uuid] = 'UGX';
        buyerName.value = '';
    } catch (error) {
        if (error?.response?.status === 422) {
            actionError.value = t('sales.messages.insufficient_quantity');
        } else {
            actionError.value = t('sales.messages.sell_error');
        }
        lastSaleUuid.value = null;
    } finally {
        selling[row.batch_uuid] = false;
    }
}

async function handleQueueSynced(event) {
    const url = String(event?.detail?.url ?? '');

    if (!url.startsWith('/api/inventory/sell') && !url.startsWith('/api/sales')) {
        return;
    }

    await loadInventory();
}

function handleQueueDropped(event) {
    const url = String(event?.detail?.url ?? '');

    if (!url.startsWith('/api/inventory/sell') && !url.startsWith('/api/sales')) {
        return;
    }

    actionError.value = t('sales.messages.sell_error');
}

onMounted(() => {
    window.addEventListener('offline-queue:synced', handleQueueSynced);
    window.addEventListener('offline-queue:dropped', handleQueueDropped);
});

onBeforeUnmount(() => {
    window.removeEventListener('offline-queue:synced', handleQueueSynced);
    window.removeEventListener('offline-queue:dropped', handleQueueDropped);
});

void loadInventory();
</script>

<template>
    <section class="mx-auto max-w-6xl rounded-xl border border-[#dde5d7] bg-white/70 p-6">
        <header class="mb-4">
            <h1 class="text-2xl font-bold text-[#1f2a1d]">{{ t('sales.title') }}</h1>
            <p class="text-sm text-[#4e5f4f]">{{ t('sales.subtitle') }}</p>
        </header>

        <p v-if="loading" class="text-sm text-[#4e5f4f]">{{ t('sales.messages.loading') }}</p>
        <p v-if="loadError" class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ loadError }}</p>
        <p v-if="actionError" class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ actionError }}</p>
        <p v-if="actionSuccess" class="mb-3 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">
            {{ actionSuccess }}
        </p>

        <!-- Buyer Name Input -->
        <label class="mb-4 block" v-if="!loading">
            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('sales.fields.buyer_name') }}</span>
            <input
                v-model="buyerName"
                type="text"
                :placeholder="t('sales.fields.buyer_placeholder')"
                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
            >
        </label>

        <!-- Print Receipt Button (shown after successful sale) -->
        <div class="mb-4 flex gap-3" v-if="lastSaleUuid">
            <RouterLink
                :to="`/receipt/${lastSaleUuid}`"
                class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-[#2f6e4a] px-4 py-2 text-sm font-medium text-white hover:bg-[#275d3f]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ t('sales.actions.print_receipt') }}
            </RouterLink>
        </div>

        <label class="mb-4 block" v-if="!loading">
            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('sales.messages.search_label') }}</span>
            <input
                v-model="searchTerm"
                type="text"
                :placeholder="t('sales.messages.search_placeholder')"
                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
            >
        </label>

        <div v-if="!loading" class="space-y-4">
            <article v-for="row in filteredRows" :key="row.inventory_id" class="rounded-lg border border-[#ccd8c7] bg-white p-4">
                <div class="grid grid-cols-1 gap-2 text-sm text-[#4e5f4f] md:grid-cols-2 lg:grid-cols-3">
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('sales.fields.product') }}:</span> {{ row.product_name || '-' }}</p>
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('sales.fields.batch') }}:</span> {{ row.batch_uuid || '-' }}</p>
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('sales.fields.harvested_on') }}:</span> {{ row.harvested_on || '-' }}</p>
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('sales.fields.warehouse') }}:</span> {{ row.warehouse_name || '-' }}</p>
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('sales.fields.quality') }}:</span> {{ row.quality || '-' }}</p>
                    <p>
                        <span class="font-semibold text-[#1f2a1d]">{{ t('sales.fields.quantity') }}:</span>
                        {{ row.quantity }} {{ getUnitLabel(row.product_unit) }}
                    </p>
                </div>

                <div class="mt-3 space-y-3">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('sales.fields.amount') }}</span>
                        <input
                            v-model.number="sellAmounts[row.batch_uuid]"
                            type="number"
                            :step="getStep(row.product_unit)"
                            min="0"
                            :placeholder="t('sales.fields.amount_placeholder')"
                            class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                        >
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('sales.fields.price') }}</span>
                        <div class="flex items-center gap-2">
                            <select
                                v-model="sellCurrencies[row.batch_uuid]"
                                class="h-[42px] rounded-lg border border-[#ccd8c7] bg-white px-2 text-sm"
                            >
                                <option v-for="curr in currencyOptions" :key="curr.code" :value="curr.code">
                                    {{ curr.code }}
                                </option>
                            </select>
                            <input
                                v-model.number="sellPrices[row.batch_uuid]"
                                type="number"
                                min="0"
                                step="0.01"
                                :placeholder="t('sales.fields.price_placeholder')"
                                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                            >
                        </div>
                    </label>

                    <button
                        type="button"
                        class="w-full rounded-lg bg-[#d97706] px-4 py-2 text-sm font-medium text-white hover:bg-[#b45309] disabled:opacity-70"
                        :disabled="selling[row.batch_uuid]"
                        @click="sell(row)"
                    >
                        {{ selling[row.batch_uuid] ? t('sales.actions.selling') : t('sales.actions.sell') }}
                    </button>
                </div>
            </article>

            <p v-if="filteredRows.length === 0" class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 text-sm text-[#4e5f4f]">
                {{ t('sales.messages.empty') }}
            </p>
        </div>
    </section>
</template>
