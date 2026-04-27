<script setup>
import { ref, onMounted, computed, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, RouterLink } from 'vue-router';
import http from '../lib/http';

const { t } = useI18n();
const route = useRoute();

const sale = ref(null);
const loading = ref(true);
const error = ref(null);
const corporation = ref(null);

const saleUuid = computed(() => route.params.uuid || route.query.uuid);

function formatCurrency(amount, currencyCode = 'USD') {
    if (amount === null || amount === undefined) return '';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currencyCode,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount);
}

function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const batchUrl = computed(() => {
    if (!sale.value?.batch_uuid) return '';
    return `${window.location.origin}/harvest/public/${sale.value.batch_uuid}`;
});

const qrCodeUrl = computed(() => {
    if (!batchUrl.value) return '';
    return `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(batchUrl.value)}`;
});

async function loadSale() {
    if (!saleUuid.value) return;
    
    loading.value = true;
    error.value = null;

    try {
        const response = await http.get(`/api/sales/${saleUuid.value}`);
        sale.value = response.data;
    } catch (e) {
        // Fallback: try to find in all sales
        try {
            const allResponse = await http.get('/api/sales');
            const allSales = Array.isArray(allResponse.data) ? allResponse.data : [];
            sale.value = allSales.find(s => s.uuid === saleUuid.value) || null;
        } catch (e2) {
            error.value = t('sales.receipt.not_found');
        }
    }
    
    try {
        const corpResponse = await http.get('/api/corporation');
        corporation.value = corpResponse.data || null;
    } catch (e) {
        console.warn('Could not load corporation:', e);
    }
    
    loading.value = false;
}

onMounted(() => {
    loadSale();
});

// Print handler
function handlePrint() {
    if (loading.value || error.value || !sale.value) return;
    window.print();
}

// Auto-print when data is loaded
watch([loading, sale, error], ([isLoading, currentSale, currentError]) => {
    if (!isLoading && currentSale && !currentError) {
        // Use nextTick to ensure DOM is updated from the sale data
        nextTick(() => {
            setTimeout(() => {
                handlePrint();
            }, 250);
        });
    }
}, { immediate: false });
</script>

<template>
    <div class="min-h-screen bg-[#f7f9f7] p-4 md:p-8">
        <div class="receipt-container mx-auto max-w-2xl bg-white rounded-xl shadow-lg p-6 md:p-8">
            <!-- Header -->
            <div class="receipt-header mb-6 text-center border-b-2 border-[#2f6e4a] pb-4">
                <h1 class="text-2xl md:text-3xl font-bold text-[#2f6e4a]">AgriFlow</h1>
                <p class="text-sm text-[#6b826b]">{{ t('sales.receipt.subtitle') }}</p>
            </div>

            <!-- Corporation Info -->
            <div class="corporation-info mb-6 text-center" v-if="corporation?.name">
                <h2 class="text-xl font-semibold text-[#2a4d2a]">{{ corporation.name }}</h2>
            </div>

            <!-- Sale ID Section -->
            <div class="receipt-section mb-6">
                <h3 class="text-lg font-semibold text-[#2a4d2a] mb-4 border-b border-[#e6ece1] pb-2">
                    {{ t('sales.receipt.sale_id') }}
                </h3>
                <div class="grid grid-cols-1 gap-2 text-sm">
                    <div class="flex">
                        <span class="w-32 font-medium text-[#4e5f4f]">{{ t('sales.receipt.timestamp') }}:</span>
                        <span class="text-[#1f2a1d]">{{ formatDate(sale?.created_at) || '-' }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-32 font-medium text-[#4e5f4f]">{{ t('sales.receipt.sale_uuid') }}:</span>
                        <span class="text-[#1f2a1d]">{{ sale?.uuid || '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Batch Details Section -->
            <div class="receipt-section mb-6">
                <h3 class="text-lg font-semibold text-[#2a4d2a] mb-4 border-b border-[#e6ece1] pb-2">
                    {{ t('sales.receipt.batch_details') }}
                </h3>
                <div class="grid grid-cols-1 gap-2 text-sm">
                    <div class="flex">
                        <span class="w-32 font-medium text-[#4e5f4f]">{{ t('sales.fields.product') }}:</span>
                        <span class="text-[#1f2a1d]">{{ sale?.product_name || '-' }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-32 font-medium text-[#4e5f4f]">{{ t('sales.fields.batch') }}:</span>
                        <span class="text-[#1f2a1d]">{{ sale?.batch_uuid || '-' }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-32 font-medium text-[#4e5f4f]">{{ t('sales.fields.harvested_on') }}:</span>
                        <span class="text-[#1f2a1d]">{{ sale?.harvested_on ? new Date(sale.harvested_on).toLocaleDateString('en-US') : '-' }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-32 font-medium text-[#4e5f4f]">{{ t('sales.fields.warehouse') }}:</span>
                        <span class="text-[#1f2a1d]">{{ sale?.warehouse_name || '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Sale Details Section -->
            <div class="receipt-section mb-6">
                <h3 class="text-lg font-semibold text-[#2a4d2a] mb-4 border-b border-[#e6ece1] pb-2">
                    {{ t('sales.receipt.sale_details') }}
                </h3>
                <div class="grid grid-cols-1 gap-2 text-sm">
                    <div class="flex">
                        <span class="w-32 font-medium text-[#4e5f4f]">{{ t('sales.fields.amount') }}:</span>
                        <span class="text-[#1f2a1d]">{{ sale?.quantity || '-' }} {{ sale?.product_unit || '' }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-32 font-medium text-[#4e5f4f]">{{ t('sales.fields.price') }}:</span>
                        <span class="text-[#1f2a1d]">{{ formatCurrency(sale?.unit_price, sale?.currency) }} / {{ sale?.product_unit || 'unit' }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-32 font-medium text-[#4e5f4f]">{{ t('sales.fields.total_value') }}:</span>
                        <span class="text-[#1f2a1d] font-semibold text-[#2f6e4a]">{{ formatCurrency(sale?.total_value, sale?.currency) }}</span>
                    </div>
                    <div class="flex">
                        <span class="w-32 font-medium text-[#4e5f4f]">{{ t('sales.fields.currency') }}:</span>
                        <span class="text-[#1f2a1d]">{{ sale?.currency || 'USD' }}</span>
                    </div>
                    <div class="flex" v-if="sale?.notes">
                        <span class="w-32 font-medium text-[#4e5f4f]">{{ t('sales.receipt.notes') }}:</span>
                        <span class="text-[#1f2a1d]">{{ sale.notes }}</span>
                    </div>
                </div>
            </div>

            <!-- QR Code Section -->
            <div class="qr-section mb-6 text-center" v-if="qrCodeUrl">
                <p class="text-sm text-[#6b826b] mb-3">{{ t('sales.receipt.scan_qr') }}</p>
                <img :src="qrCodeUrl" alt="QR Code" class="mx-auto w-32 h-32 md:w-40 md:h-40">
                <p class="text-xs text-[#6b826b] mt-2 break-all">{{ batchUrl }}</p>
            </div>

            <!-- Footer -->
            <div class="receipt-footer text-center text-sm text-[#6b826b] border-t border-[#e6ece1] pt-4">
                <p>{{ t('sales.receipt.thank_you') }}</p>
                <p class="mt-1">{{ t('sales.receipt.powered_by') }}</p>
            </div>

            <!-- Print/Action Buttons - Hidden when printing -->
            <div class="no-print mt-6 flex justify-center gap-4" v-if="!loading && !error">
                <button
                    @click="handlePrint"
                    class="rounded-lg bg-[#2f6e4a] px-6 py-2 text-sm font-medium text-white hover:bg-[#275d3f]"
                >
                    {{ t('sales.receipt.print') }}
                </button>
                <RouterLink
                    to="/sales"
                    class="inline-block rounded-lg bg-[#6b826b] px-6 py-2 text-sm font-medium text-white hover:bg-[#5a6f58]"
                >
                    {{ t('sales.receipt.back_to_sales') }}
                </RouterLink>
            </div>
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="fixed inset-0 z-50 flex items-center justify-center bg-white/80">
            <p class="text-[#4e5f4f]">{{ t('sales.receipt.loading') }}</p>
        </div>

        <!-- Error state -->
        <div v-if="error && !loading" class="fixed inset-0 z-50 flex items-center justify-center bg-white/80">
            <div class="text-center text-red-600">
                <p class="text-xl font-semibold">{{ error }}</p>
                <p class="mt-2 text-sm text-gray-500">{{ t('sales.receipt.error_hint') }}</p>
                <RouterLink to="/sales" class="mt-4 inline-block text-[#2f6e4a] underline">
                    {{ t('sales.receipt.back_to_sales') }}
                </RouterLink>
            </div>
        </div>
    </div>
</template>
