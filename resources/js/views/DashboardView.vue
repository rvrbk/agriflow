<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import http from '../lib/http';

const { t } = useI18n();

const loading = ref(true);
const loadError = ref('');
const searchTerm = ref('');
const warehouseCards = ref([]);

function asNumber(value) {
    const parsed = Number(value);

    if (!Number.isFinite(parsed)) {
        return 0;
    }

    return parsed;
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
        const [warehousesResponse, inventoryResponse] = await Promise.all([
            http.get('/api/warehouse'),
            http.get('/api/inventory'),
        ]);

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

        <div v-if="!loading" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
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
    </section>
</template>
