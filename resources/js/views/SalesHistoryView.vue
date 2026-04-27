<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import http from '../lib/http';

const { t } = useI18n();

const sales = ref([]);
const loading = ref(true);
const loadError = ref('');
const searchTerm = ref('');
const currencies = ref([]);
const displayCurrency = ref('UGX');

// Load exchange rates
async function loadCurrencies() {
    try {
        const response = await http.get('/api/currencies');
        currencies.value = response.data || [];
        // Set default display currency if not already set
        if (!currencies.value.some(c => c.code === displayCurrency.value)) {
            displayCurrency.value = currencies.value[0]?.code || 'USD';
        }
    } catch (e) {
        console.error('Failed to load currencies:', e);
    }
}

// Get exchange rate from one currency to another
function getExchangeRate(fromCode, toCode) {
    const from = currencies.value.find(c => c.code === fromCode);
    const to = currencies.value.find(c => c.code === toCode);
    if (!from || !to) return 1;
    // Both rates are relative to USD, so: to率 / from率
    return to.exchange_rate / from.exchange_rate;
}

// Convert amount from sale currency to display currency
function asNumber(value) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
}

function convertToDisplayCurrency(amount, saleCurrency) {
    if (amount === null || amount === undefined) return 0;
    const numericAmount = asNumber(amount);
    const rate = getExchangeRate(saleCurrency, displayCurrency.value);
    return numericAmount * rate;
}

// Total revenue in original currencies
const totalRevenue = computed(() => {
    return sales.value.reduce((sum, sale) => {
        return sum + asNumber(sale.total_value);
    }, 0);
});

// Total revenue converted to display currency
const displayTotalRevenue = computed(() => {
    // Reference displayCurrency to make this computed reactive to currency changes
    const _ = displayCurrency.value;
    let total = 0;
    sales.value.forEach(sale => {
        total += convertToDisplayCurrency(sale.total_value, sale.currency);
    });
    return total;
});

// Filtered total in display currency
const filteredDisplayTotal = computed(() => {
    // Reference displayCurrency to make this computed reactive to currency changes
    const _ = displayCurrency.value;
    let total = 0;
    filteredSales.value.forEach(sale => {
        total += convertToDisplayCurrency(sale.total_value, sale.currency);
    });
    return total;
});

// Summary by original currency
const summaryByCurrency = computed(() => {
    const summary = {};
    filteredSales.value.forEach(sale => {
        const currency = sale.currency || 'USD';
        if (!summary[currency]) {
            summary[currency] = { total: 0, count: 0 };
        }
        summary[currency].total += asNumber(sale.total_value);
        summary[currency].count += 1;
    });
    return summary;
});

const filteredSales = computed(() => {
    const query = searchTerm.value.trim().toLowerCase();

    if (!query) {
        return sales.value;
    }

    return sales.value.filter((sale) => {
        return [
            sale.product_name,
            sale.batch_uuid,
            sale.warehouse_name,
            sale.formatted_date,
        ].some((value) => String(value || '').toLowerCase().includes(query));
    });
});

function formatCurrency(amount, currency = 'USD') {
    const numericAmount = asNumber(amount);
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(numericAmount);
}

function getCurrencySymbol(currency) {
    const symbols = {
        USD: '$',
        UGX: 'USh',
    };
    return symbols[currency] || currency;
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
        hour: '2-digit',
        minute: '2-digit',
    });
}

// Format amount in display currency
function formatDisplayCurrency(amount, saleCurrency) {
    const converted = convertToDisplayCurrency(amount, saleCurrency);
    return formatCurrency(converted, displayCurrency.value);
}

async function loadSales() {
    loading.value = true;
    loadError.value = '';

    try {
        const [salesResponse, currenciesResponse] = await Promise.all([
            http.get('/api/sales'),
            http.get('/api/currencies'),
        ]);
        
        currencies.value = currenciesResponse.data || [];
        
        // Default to UGX if available, otherwise USD
        const ugxExists = currencies.value.some(c => c.code === 'UGX');
        if (ugxExists && displayCurrency.value !== 'UGX') {
            displayCurrency.value = 'UGX';
        } else if (!currencies.value.some(c => c.code === displayCurrency.value)) {
            displayCurrency.value = currencies.value[0]?.code || 'USD';
        }
        
        sales.value = Array.isArray(salesResponse.data) ? salesResponse.data.map((sale) => {
            const numericTotal = asNumber(sale.total_value);
            const numericPrice = asNumber(sale.unit_price);
            return {
                ...sale,
                unit_price: numericPrice,
                total_value: numericTotal,
                formatted_date: formatDate(sale.created_at),
                formatted_price: formatCurrency(numericPrice, sale.currency),
                formatted_total: formatCurrency(numericTotal, sale.currency),
                formatted_price_display: formatDisplayCurrency(numericPrice, sale.currency),
                formatted_total_display: formatDisplayCurrency(numericTotal, sale.currency),
            };
        }) : [];
    } catch {
        loadError.value = t('sales_history.messages.load_error');
    } finally {
        loading.value = false;
    }
}

void loadSales();
</script>

<template>
    <section class="mx-auto max-w-6xl rounded-xl border border-[#dde5d7] bg-white/70 p-6">
        <header class="mb-4">
            <h1 class="text-2xl font-bold text-[#1f2a1d]">{{ t('sales_history.title') }}</h1>
            <p class="text-sm text-[#4e5f4f]">{{ t('sales_history.subtitle') }}</p>
        </header>

        <p v-if="loading" class="text-sm text-[#4e5f4f]">{{ t('sales_history.messages.loading') }}</p>
        <p v-if="loadError" class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ loadError }}</p>

        <!-- Currency Toggle & Total Revenue Summary -->
        <div v-if="!loading && sales.length > 0" class="mb-6 rounded-lg border border-[#ccd8c7] bg-[#f7f9f7] p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#4e5f4f]">{{ t('sales_history.totals.total_revenue') }}</p>
                    <p class="text-2xl font-bold text-[#2a4d2a]">
                        {{ formatCurrency(filteredDisplayTotal, displayCurrency) }}
                        <span class="text-sm font-normal text-[#4e5f4f]">{{ displayCurrency }}</span>
                    </p>
                    <p v-if="displayCurrency !== 'USD'" class="text-xs text-[#6b826b]">
                        ~{{ formatCurrency(filteredDisplayTotal / getExchangeRate(displayCurrency, 'USD'), 'USD') }} USD
                    </p>
                    <p v-if="displayCurrency !== 'UGX' && currencies.some(c => c.code === 'UGX')" class="text-xs text-[#6b826b]">
                        ~{{ formatCurrency(filteredDisplayTotal * getExchangeRate('USD', 'UGX'), 'UGX') }} UGX
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-[#4e5f4f]">{{ t('sales_history.totals.display_currency') }}:</span>
                    <select
                        v-model="displayCurrency"
                        class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-1 text-sm"
                    >
                        <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                            {{ currency.code }} - {{ currency.name }}
                        </option>
                    </select>
                </div>
            </div>
            <p class="mt-2 text-xs text-[#6b826b]">
                {{ t('sales_history.totals.exchange_note') }}: 
                <span v-for="(currency, index) in currencies" :key="currency.code">
                    <span v-if="index > 0">, </span>
                    1 {{ currency.code }} = {{ formatCurrency(currency.exchange_rate, 'USD') }} USD
                </span>
            </p>
        </div>

        <label class="mb-4 block" v-if="!loading">
            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('sales_history.messages.search_label') }}</span>
            <input
                v-model="searchTerm"
                type="text"
                :placeholder="t('sales_history.messages.search_placeholder')"
                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
            >
        </label>

        <div v-if="!loading" class="space-y-4">
            <article v-for="sale in filteredSales" :key="sale.uuid" class="rounded-lg border border-[#ccd8c7] bg-white p-4">
                <div class="grid grid-cols-1 gap-2 text-sm text-[#4e5f4f] md:grid-cols-2 lg:grid-cols-3">
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('sales_history.fields.product') }}:</span> {{ sale.product_name || t('sales_history.fields.unknown_product') }}</p>
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('sales_history.fields.batch') }}:</span> {{ sale.batch_uuid || '-' }}</p>
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('sales_history.fields.date') }}:</span> {{ sale.formatted_date || '-' }}</p>
                    <p><span class="font-semibold text-[#1f2a1d]">{{ t('sales_history.fields.warehouse') }}:</span> {{ sale.warehouse_name || '-' }}</p>
                    <p>
                        <span class="font-semibold text-[#1f2a1d]">{{ t('sales_history.fields.quantity') }}:</span>
                        {{ sale.quantity }} {{ sale.product_unit || '' }}
                    </p>
                    <p>
                        <span class="font-semibold text-[#1f2a1d]">{{ t('sales_history.fields.unit_price') }}:</span>
                        {{ sale.formatted_price || '-' }}
                    </p>
                    <p>
                        <span class="font-semibold text-[#1f2a1d]">{{ t('sales_history.fields.total_value') }}:</span>
                        {{ sale.formatted_total || '-' }}
                    </p>
                </div>
                <div v-if="sale.currency !== displayCurrency" class="mt-2 text-xs text-[#6b826b]">
                    ≈ {{ formatDisplayCurrency(sale.total_value, sale.currency) }} {{ displayCurrency }}
                </div>
                <div class="mt-3 flex gap-2">
                    <RouterLink
                        :to="`/receipt/${sale.uuid}`"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-[#2f6e4a] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#275d3f]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ t('sales.actions.print_receipt') }}
                    </RouterLink>
                </div>
            </article>

            <!-- Summary by original currency -->
            <div v-if="filteredSales.length > 0" class="rounded-lg border border-[#ccd8c7] bg-[#f7f9f7] p-4">
                <h3 class="mb-3 font-semibold text-[#1f2a1d]">{{ t('sales_history.totals.by_currency') }}</h3>
                <div class="grid grid-cols-1 gap-2 text-sm md:grid-cols-2 lg:grid-cols-3">
                    <p v-for="(currencyGroup, code) in summaryByCurrency" :key="code" class="flex items-center gap-2">
                        <span class="font-medium text-[#2a4d2a]">{{ code }}:</span>
                        <span>{{ formatCurrency(currencyGroup.total, code) }}</span>
                        <span class="text-xs text-[#6b826b]">
                            ({{ currencyGroup.count }} {{ t('sales_history.totals.sales') }})
                        </span>
                    </p>
                </div>
            </div>

            <p v-if="filteredSales.length === 0" class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 text-sm text-[#4e5f4f]">
                {{ t('sales_history.messages.empty') }}
            </p>
        </div>
    </section>
</template>
=======
