<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import http from '../lib/http';

const { t } = useI18n();

const loading = ref(true);
const loadError = ref('');
const searchTerm = ref('');
const warehouseCards = ref([]);
const salesSummary = ref(null);
const currencies = ref([]);
const displayCurrency = ref('UGX');

function asNumber(value) {
    const parsed = Number(value);

    if (!Number.isFinite(parsed)) {
        return 0;
    }

    return parsed;
}

function formatCurrency(amount, currencyCode = 'USD') {
    if (amount === null || amount === undefined) {
        return '';
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currencyCode,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount);
}

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

function getExchangeRate(fromCode, toCode) {
    const from = currencies.value.find(c => c.code === fromCode);
    const to = currencies.value.find(c => c.code === toCode);
    if (!from || !to) return 1;
    return to.exchange_rate / from.exchange_rate;
}

function convertAmount(amount, fromCurrency, toCurrency) {
    if (amount === null || amount === undefined) return 0;
    return amount * getExchangeRate(fromCurrency, toCurrency);
}

const filteredCards = computed(() => {
    const query = searchTerm.value.trim().toLowerCase();

    if (!query) {
        return warehouseCards.value;
    }

    return warehouseCards.value.filter((warehouse) => {
        const productText = warehouse.products
            .map((product) => `${product.name} ${product.quantity} ${product.unit}`)
            .join(' ')
            .toLowerCase();

        return `${warehouse.name} ${warehouse.productCount} ${warehouse.totalQuantity} ${productText}`
            .toLowerCase()
            .includes(query);
    });
});

async function loadDashboardOverview() {
    loading.value = true;
    loadError.value = '';

    try {
        const [warehousesResponse, inventoryResponse, currenciesResponse, salesResponse] = await Promise.all([
            http.get('/api/warehouse'),
            http.get('/api/inventory'),
            http.get('/api/currencies'),
            http.get('/api/sales'),
        ]);

        // Load currencies for exchange rate calculations
        currencies.value = currenciesResponse.data || [];

        const salesData = Array.isArray(salesResponse.data) ? salesResponse.data : [];

        // Calculate sales summary from all available sales.
        if (salesData.length > 0) {
            salesSummary.value = {
                totalSales: salesData.length,
                totalRevenueByCurrency: {},
                totalRevenueUSD: 0,
            };

            salesData.forEach(sale => {
                const currency = sale.currency || 'USD';
                const amount = Number(sale.total_value) || 0;

                if (!salesSummary.value.totalRevenueByCurrency[currency]) {
                    salesSummary.value.totalRevenueByCurrency[currency] = 0;
                }
                salesSummary.value.totalRevenueByCurrency[currency] += amount;

                // Convert to USD for aggregate total
                if (currency === 'USD') {
                    salesSummary.value.totalRevenueUSD += amount;
                } else {
                    // Find the currency and convert via exchange rate
                    const curr = currencies.value.find(c => c.code === currency);
                    if (curr) {
                        // currency exchange_rate is relative to USD, so: amount / rate = USD value
                        salesSummary.value.totalRevenueUSD += amount / curr.exchange_rate;
                    } else {
                        salesSummary.value.totalRevenueUSD += amount;
                    }
                }
            });
        } else {
            salesSummary.value = {
                totalSales: 0,
                totalRevenueByCurrency: {},
                totalRevenueUSD: 0,
            };
        }

        // Load warehouse and inventory data
        const warehouses = Array.isArray(warehousesResponse.data) ? warehousesResponse.data : [];
        const inventoryRows = Array.isArray(inventoryResponse.data) ? inventoryResponse.data : [];

        const inventoryByWarehouse = new Map();

        inventoryRows.forEach((row) => {
            const warehouseName = String(row.warehouse_name || '').trim();

            if (!warehouseName) {
                return;
            }

            if (!inventoryByWarehouse.has(warehouseName)) {
                inventoryByWarehouse.set(warehouseName, new Map());
            }

            const productKey = `${row.product_uuid || row.product_name}|${row.product_unit || ''}`;
            const groupedProducts = inventoryByWarehouse.get(warehouseName);
            const current = groupedProducts.get(productKey);

            if (current) {
                current.quantity += asNumber(row.quantity);
                return;
            }

            groupedProducts.set(productKey, {
                name: row.product_name || t('dashboard.unknown_product'),
                unit: row.product_unit || '',
                quantity: asNumber(row.quantity),
            });
        });

        warehouseCards.value = warehouses
            .map((warehouse) => {
                const warehouseName = warehouse.name || t('warehouses.unnamed');
                const groupedProducts = inventoryByWarehouse.get(warehouseName) ?? new Map();
                const products = Array.from(groupedProducts.values())
                    .sort((a, b) => a.name.localeCompare(b.name));

                const totalQuantity = products.reduce((sum, product) => sum + product.quantity, 0);

                return {
                    uuid: warehouse.uuid,
                    name: warehouseName,
                    productCount: products.length,
                    totalQuantity,
                    products,
                };
            })
            .sort((a, b) => a.name.localeCompare(b.name));
    } catch {
        loadError.value = t('dashboard.messages.load_error');
    } finally {
        loading.value = false;
    }
}

void loadDashboardOverview();
</script>

<template>
    <section class="mx-auto max-w-6xl rounded-xl border border-[#dde5d7] bg-white/70 p-6">
        <header class="mb-4">
            <h1 class="text-2xl font-bold text-[#1f2a1d]">{{ t('dashboard.title') }}</h1>
            <p class="text-sm text-[#4e5f4f]">{{ t('dashboard.subtitle') }}</p>
        </header>

        <div class="mb-4 flex flex-wrap gap-3">
            <RouterLink to="/sales" class="flex shrink-0 items-center gap-1.5 rounded-lg bg-[#2f6e4a] px-3 py-2 text-sm font-medium text-white hover:bg-[#275d3f]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ t('dashboard.actions.sell_inventory') }}
            </RouterLink>
            <RouterLink to="/sales-history" class="flex shrink-0 items-center gap-1.5 rounded-lg bg-[#5d7c69] px-3 py-2 text-sm font-medium text-white hover:bg-[#4a6856]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ t('dashboard.actions.sales_history') }}
            </RouterLink>
        </div>

        <div v-if="!loading && salesSummary" class="mb-6 rounded-lg border border-[#ccd8c7] bg-[#f7f9f7] p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-[#4e5f4f] mt-1">{{ t('dashboard.revenue.title') }}</p>
                    <p class="text-2xl font-bold text-[#2a4d2a]">
                        {{ formatCurrency(salesSummary.totalRevenueUSD * getExchangeRate('USD', displayCurrency), displayCurrency) }}
                        <span class="text-sm font-normal text-[#4e5f4f]">{{ displayCurrency }}</span>
                    </p>
                    <p class="text-sm text-[#6b826b]">
                        {{ salesSummary.totalSales }} {{ t('dashboard.revenue.sales') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-[#4e5f4f]">{{ t('dashboard.revenue.view_in') }}:</span>
                    <select
                        v-model="displayCurrency"
                        class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-1 text-sm"
                    >
                        <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                            {{ currency.code }}
                        </option>
                    </select>
                    <RouterLink to="/sales-history" class="rounded-lg bg-[#5d7c69] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#4a6856]">
                        {{ t('dashboard.actions.view_details') }}
                    </RouterLink>
                </div>
            </div>
            <div v-if="Object.keys(salesSummary.totalRevenueByCurrency).length > 0" class="mt-3 grid grid-cols-1 gap-2 text-sm md:grid-cols-2 lg:grid-cols-3">
                <p v-for="(amount, currency) in salesSummary.totalRevenueByCurrency" :key="currency" class="flex items-center gap-2">
                    <span class="font-medium text-[#2a4d2a]">{{ currency }}:</span>
                    <span>{{ formatCurrency(amount, currency) }}</span>
                </p>
            </div>
        </div>

        <label class="mb-4 block">
            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('dashboard.messages.search_label') }}</span>
            <input
                v-model="searchTerm"
                type="text"
                :placeholder="t('dashboard.messages.search_placeholder')"
                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
            >
        </label>

        <p v-if="loading" class="text-sm text-[#4e5f4f]">{{ t('dashboard.messages.loading') }}</p>
        <p v-if="loadError" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ loadError }}</p>

        <div v-if="!loading">
            <h2 class="mb-4 text-xl font-bold text-[#1f2a1d]">{{ t('warehouses.title') }}</h2>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <article
                v-for="warehouse in filteredCards"
                :key="warehouse.uuid"
                class="rounded-lg border border-[#ccd8c7] bg-white p-4"
            >
                <div class="mb-2 flex items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold text-[#1f2a1d]">{{ warehouse.name }}</h2>
                    <span class="rounded-full bg-[#d6e9d6] px-2 py-0.5 text-xs font-medium text-[#1e3020]">
                        {{ t('dashboard.fields.products') }}: {{ warehouse.productCount }}
                    </span>
                </div>

                <p class="mb-3 text-sm text-[#4e5f4f]">
                    {{ t('dashboard.fields.total_quantity') }}: {{ warehouse.totalQuantity.toFixed(2) }}
                </p>

                <div v-if="warehouse.products.length > 0" class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-[#e6ece1] text-[#4e5f4f]">
                                <th class="px-2 py-1 font-medium">{{ t('dashboard.fields.product') }}</th>
                                <th class="px-2 py-1 font-medium">{{ t('dashboard.fields.quantity') }}</th>
                                <th class="px-2 py-1 font-medium">{{ t('dashboard.fields.unit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="product in warehouse.products"
                                :key="`${warehouse.uuid}-${product.name}-${product.unit}`"
                                class="border-b border-[#f0f3eb] last:border-b-0"
                            >
                                <td class="px-2 py-1 text-[#1f2a1d]">{{ product.name }}</td>
                                <td class="px-2 py-1 text-[#1f2a1d]">{{ product.quantity.toFixed(2) }}</td>
                                <td class="px-2 py-1 text-[#1f2a1d]">{{ product.unit || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-else class="text-sm text-[#4e5f4f]">{{ t('dashboard.messages.empty_warehouse') }}</p>
            </article>

            <p v-if="filteredCards.length === 0" class="rounded-lg border border-[#ccd8c7] bg-white p-4 text-sm text-[#4e5f4f] lg:col-span-2">
                {{ t('dashboard.messages.empty') }}
            </p>
            </div>
        </div>
    </section>
</template>
