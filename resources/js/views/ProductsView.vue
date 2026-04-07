<script setup>
import { computed, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import http from '../lib/http';

const { t } = useI18n();

const products = ref([]);
const loading = ref(true);
const loadError = ref('');
const addFormOpen = ref(false);
const savingAdd = ref(false);
const addError = ref('');

const editStates = reactive({});
const editForms = reactive({});
const editSaving = reactive({});
const editError = reactive({});
const deleteSaving = reactive({});

const unitOptions = computed(() => [
    { value: 'kg', label: t('products.units.kg') },
    { value: 'g', label: t('products.units.g') },
    { value: 'l', label: t('products.units.l') },
    { value: 'ml', label: t('products.units.ml') },
    { value: 'pcs', label: t('products.units.pcs') },
]);
const codeTypeOptions = computed(() => [
    { value: 'none', label: t('products.code_types.none') },
    { value: 'barcode', label: t('products.code_types.barcode') },
    { value: 'qr', label: t('products.code_types.qr') },
]);

const newProduct = reactive({
    name: '',
    code: '',
    code_type: 'none',
    unit: 'pcs',
});

function normalizeApiProduct(product) {
    return {
        uuid: product.uuid,
        name: product.name ?? '',
        code: product.code ?? '',
        code_type: product.code_type ?? 'none',
        unit: product.unit ?? 'pcs',
    };
}

function resetNewForm() {
    newProduct.name = '';
    newProduct.code = '';
    newProduct.code_type = 'none';
    newProduct.unit = 'pcs';
}

function initEditForm(product) {
    editForms[product.uuid] = {
        name: product.name ?? '',
        code: product.code ?? '',
        code_type: product.code_type ?? 'none',
        unit: product.unit ?? 'pcs',
    };
}

async function loadProducts() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await http.get('/api/product');
        products.value = Array.isArray(response.data)
            ? response.data.map(normalizeApiProduct)
            : [];

        products.value.forEach((product) => {
            initEditForm(product);
            if (!(product.uuid in editStates)) {
                editStates[product.uuid] = false;
            }
        });
    } catch (error) {
        loadError.value = t('products.messages.load_error');
    } finally {
        loading.value = false;
    }
}

async function addProduct() {
    addError.value = '';
    savingAdd.value = true;

    try {
        await http.post('/api/product', [
            {
                name: newProduct.name,
                code: newProduct.code || null,
                code_type: newProduct.code_type === 'none' ? null : newProduct.code_type,
                unit: newProduct.unit,
                properties: [],
            },
        ]);

        resetNewForm();
        addFormOpen.value = false;
        await loadProducts();
    } catch (error) {
        addError.value = t('products.messages.add_error');
    } finally {
        savingAdd.value = false;
    }
}

function openEdit(product) {
    editError[product.uuid] = '';
    editStates[product.uuid] = true;
    initEditForm(product);
}

function cancelEdit(product) {
    editError[product.uuid] = '';
    editStates[product.uuid] = false;
    initEditForm(product);
}

async function saveEdit(product) {
    const form = editForms[product.uuid];

    if (!form) {
        return;
    }

    editError[product.uuid] = '';
    editSaving[product.uuid] = true;

    try {
        await http.post('/api/product', [
            {
                uuid: product.uuid,
                name: form.name,
                code: form.code || null,
                code_type: form.code_type === 'none' ? null : form.code_type,
                unit: form.unit,
                properties: [],
            },
        ]);

        editStates[product.uuid] = false;
        await loadProducts();
    } catch (error) {
        editError[product.uuid] = t('products.messages.save_error');
    } finally {
        editSaving[product.uuid] = false;
    }
}

async function deleteProduct(product) {
    const productName = product.name || t('products.unnamed');
    const confirmed = window.confirm(t('products.messages.delete_confirm', { name: productName }));

    if (!confirmed) {
        return;
    }

    deleteSaving[product.uuid] = true;

    try {
        await http.delete(`/api/product/${product.uuid}`);
        await loadProducts();
    } catch (error) {
        editError[product.uuid] = t('products.messages.delete_error');
    } finally {
        deleteSaving[product.uuid] = false;
    }
}

void loadProducts();
</script>

<template>
    <section class="mx-auto max-w-5xl rounded-xl border border-[#dde5d7] bg-white/70 p-6">
        <header class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-[#1f2a1d]">{{ t('products.title') }}</h1>
                <p class="text-sm text-[#4e5f4f]">{{ t('products.subtitle') }}</p>
            </div>

            <button
                type="button"
                class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f]"
                @click="addFormOpen = !addFormOpen"
            >
                {{ addFormOpen ? t('products.actions.close_add_form') : t('products.actions.add_product') }}
            </button>
        </header>

        <form v-if="addFormOpen" class="mb-6 rounded-lg border border-[#ccd8c7] bg-white p-4" @submit.prevent="addProduct">
            <h2 class="mb-3 text-lg font-semibold text-[#1f2a1d]">{{ t('products.actions.add_product') }}</h2>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('products.fields.name') }}</span>
                    <input
                        v-model="newProduct.name"
                        required
                        type="text"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('products.fields.code') }}</span>
                    <input
                        v-model="newProduct.code"
                        type="text"
                        class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('products.fields.code_type') }}</span>
                    <select v-model="newProduct.code_type" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        <option v-for="option in codeTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('products.fields.unit') }}</span>
                    <select v-model="newProduct.unit" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                        <option v-for="option in unitOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </label>
            </div>

            <div class="mt-3 flex items-center gap-3">
                <button
                    type="submit"
                    :disabled="savingAdd"
                    class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f] disabled:cursor-not-allowed disabled:opacity-70"
                >
                    {{ savingAdd ? t('products.actions.saving') : t('products.actions.save_product') }}
                </button>
                <p v-if="addError" class="text-sm text-red-700">{{ addError }}</p>
            </div>
        </form>

        <p v-if="loading" class="text-sm text-[#4e5f4f]">{{ t('products.messages.loading') }}</p>
        <p v-if="loadError" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ loadError }}</p>

        <div v-if="!loading" class="space-y-4">
            <article
                v-for="product in products"
                :key="product.uuid"
                class="rounded-lg border border-[#ccd8c7] bg-white p-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-[#1f2a1d]">{{ product.name || t('products.unnamed') }}</h3>
                        <p class="text-sm text-[#4e5f4f]">{{ t('products.fields.code') }}: {{ product.code || '-' }}</p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('products.fields.code_type') }}: {{ product.code_type || '-' }}</p>
                        <p class="text-sm text-[#4e5f4f]">{{ t('products.fields.unit') }}: {{ product.unit || '-' }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-1.5 text-sm hover:bg-[#f4f8ed]"
                            @click="openEdit(product)"
                        >
                            {{ t('products.actions.edit') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-70"
                            :disabled="deleteSaving[product.uuid]"
                            @click="deleteProduct(product)"
                        >
                            {{ deleteSaving[product.uuid] ? t('products.actions.deleting') : t('products.actions.delete') }}
                        </button>
                    </div>
                </div>

                <form
                    v-if="editStates[product.uuid]"
                    class="mt-4 rounded-lg border border-[#ccd8c7] bg-[#f9fcf8] p-4"
                    @submit.prevent="saveEdit(product)"
                >
                    <h4 class="mb-3 text-base font-semibold text-[#1f2a1d]">{{ t('products.actions.edit_product') }}</h4>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('products.fields.name') }}</span>
                            <input
                                v-model="editForms[product.uuid].name"
                                required
                                type="text"
                                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                            >
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('products.fields.code') }}</span>
                            <input
                                v-model="editForms[product.uuid].code"
                                type="text"
                                class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2"
                            >
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('products.fields.code_type') }}</span>
                            <select v-model="editForms[product.uuid].code_type" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                                <option v-for="option in codeTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-[#1f2a1d]">{{ t('products.fields.unit') }}</span>
                            <select v-model="editForms[product.uuid].unit" class="w-full rounded-lg border border-[#ccd8c7] bg-white px-3 py-2">
                                <option v-for="option in unitOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>
                    </div>

                    <div class="mt-3 flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="editSaving[product.uuid]"
                            class="rounded-lg bg-[#2f6e4a] px-4 py-2 font-medium text-white hover:bg-[#275d3f] disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            {{ editSaving[product.uuid] ? t('products.actions.saving') : t('products.actions.save_changes') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-[#ccd8c7] bg-white px-4 py-2 font-medium text-[#1f2a1d] hover:bg-[#f4f8ed]"
                            @click="cancelEdit(product)"
                        >
                            {{ t('products.actions.cancel') }}
                        </button>
                        <p v-if="editError[product.uuid]" class="text-sm text-red-700">{{ editError[product.uuid] }}</p>
                    </div>
                </form>
            </article>

            <p v-if="products.length === 0" class="rounded-lg border border-[#ccd8c7] bg-white px-3 py-2 text-sm text-[#4e5f4f]">
                {{ t('products.messages.empty') }}
            </p>
        </div>
    </section>
</template>
