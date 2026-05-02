<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { index as invoicesIndex, show as invoicesShow, analyse as invoicesAnalyse } from '@/actions/App/Http/Controllers/InvoiceController';

defineOptions({
    layout: () => ({
        breadcrumbs: [
            { title: 'Invoices', href: invoicesIndex.url() },
            { title: 'Invoice Details', href: '#' },
        ],
    }),
});

interface Analysis {
    id: number;
    invoice_id: number;
    json_data: Record<string, unknown>;
    llm_name: string;
    prompt_text: string;
    created_at: string;
}

interface Invoice {
    id: number;
    original_file_name: string;
    file_url: string;
    mime_type: string;
    file_size: number;
    status: string;
    uploaded_at: string | null;
    created_at: string | null;
    analysis: Analysis | null;
    analyses: Analysis[];
}

const props = defineProps<{ invoice: Invoice }>();

// Use a ref to allow manual switching between analyses in history
const currentAnalysis = ref<Analysis | null>(props.invoice.analysis ?? null);

// Keep currentAnalysis in sync if the invoice prop changes (e.g. after analysis)
watch(() => props.invoice.analysis, (newAnalysis) => {
    currentAnalysis.value = newAnalysis ?? null;
}, { immediate: true });

const statusColors: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
 return bytes + ' B'; 
}

    if (bytes < 1048576) {
 return (bytes / 1024).toFixed(1) + ' KB'; 
}

    return (bytes / 1048576).toFixed(1) + ' MB';
}

function formatDate(dateString: string | null): string {
    if (!dateString) {
 return '-'; 
}

    return new Date(dateString).toLocaleString();
}

/**
 * Check whether a json_data value is a primitive (not array/object).
 */
function isPrimitive(value: unknown): boolean {
    return value !== null && typeof value !== 'object';
}

/**
 * Render a json_data value as a display string.
 */
function renderValue(value: unknown): string {
    if (value === null || value === undefined) {
 return '-'; 
}

    if (typeof value === 'boolean') {
 return value ? 'Yes' : 'No'; 
}

    if (typeof value === 'object') {
 return JSON.stringify(value, null, 2); 
}

    return String(value);
}

/**
 * Convert a snake_case / camelCase key to a readable label.
 */
function humanizeKey(key: string): string {
    return key
        .replace(/_/g, ' ')
        .replace(/([A-Z])/g, ' $1')
        .replace(/^\w/, (c) => c.toUpperCase())
        .trim();
}

const isProcessing = computed(() => props.invoice.status === 'processing');
const isCompleted = computed(() => props.invoice.status === 'completed' && currentAnalysis.value !== null);
const canAnalyse = computed(() => props.invoice.status === 'pending' || props.invoice.status === 'failed');

/** Flat key-value pairs from json_data (non-array/object values) */
const analysisFields = computed<Array<{ key: string; value: unknown }>>(() => {
    if (!currentAnalysis.value?.json_data) {
 return []; 
}

    return Object.entries(currentAnalysis.value.json_data)
        .filter(([, v]) => isPrimitive(v))
        .map(([k, v]) => ({ key: k, value: v }));
});

/** Array fields from json_data (arrays of objects, e.g. line_items) */
const analysisArrayFields = computed<Array<{ key: string; rows: unknown[] }>>(() => {
    if (!currentAnalysis.value?.json_data) {
 return []; 
}

    return Object.entries(currentAnalysis.value.json_data)
        .filter(([, v]) => Array.isArray(v))
        .map(([k, v]) => ({ key: k, rows: v as unknown[] }));
});

/** Get keys from a row object for table headers */
function getRowKeys(row: unknown): string[] {
    if (!row || typeof row !== 'object') {
return [];
}

    return Object.keys(row as Record<string, unknown>);
}

/** Get value from a row object for a given column key */
function getRowValue(row: unknown, col: string): unknown {
    if (!row || typeof row !== 'object') {
return null;
}

    return (row as Record<string, unknown>)[col];
}
</script>

<template>
    <Head :title="invoice.original_file_name" />

    <div class="mx-auto max-w-[90rem] px-4 py-8 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <Link
                :href="invoicesIndex.url()"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Invoices
            </Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left Column: Sticky Image -->
            <div class="lg:col-span-5 xl:col-span-5 flex flex-col gap-6">
                <div class="sticky top-8">
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 p-2 shadow-sm dark:border-gray-800 dark:bg-gray-900/50 flex items-center justify-center min-h-[300px]">
                        <a :href="invoice.file_url" target="_blank" class="block w-full h-full relative group">
                            <img
                                :src="invoice.file_url"
                                :alt="invoice.original_file_name"
                                class="w-full h-auto max-h-[85vh] object-contain rounded-xl shadow-sm border border-gray-200/50 dark:border-gray-700/50 group-hover:opacity-90 transition-opacity"
                            />
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/5 rounded-xl">
                                <span class="bg-black/75 text-white text-xs px-3 py-1.5 rounded-full backdrop-blur-sm shadow-lg font-medium flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z" />
                                    </svg>
                                    Open Original
                                </span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Column: Info & Analysis -->
            <div class="lg:col-span-7 xl:col-span-7 flex flex-col gap-8">
                
                <!-- Invoice Info Card -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white break-all leading-tight">
                                {{ invoice.original_file_name }}
                            </h1>
                            <div class="mt-2 flex items-center gap-3 flex-wrap text-sm text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z" /></svg>
                                    {{ invoice.mime_type }}
                                </span>
                                <span>&bull;</span>
                                <span class="flex items-center gap-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                                    {{ formatBytes(invoice.file_size) }}
                                </span>
                            </div>
                        </div>
                        <span :class="['shrink-0 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ring-gray-500/10 capitalize', statusColors[invoice.status] ?? statusColors.pending]">
                            <svg v-if="invoice.status === 'completed'" class="mr-1.5 h-3 w-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                            <svg v-else-if="invoice.status === 'processing'" class="mr-1.5 h-3 w-3 animate-pulse" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                            <svg v-else class="mr-1.5 h-3 w-3 opacity-50" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                            {{ invoice.status }}
                        </span>
                    </div>

                    <div class="mt-6 border-t border-gray-100 dark:border-gray-800 pt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-500">Uploaded</p>
                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-200">{{ formatDate(invoice.uploaded_at) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-500">Created</p>
                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-200">{{ formatDate(invoice.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Analysis Section -->
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                            AI Analysis
                        </h2>
                    </div>

                    <!-- Tabs -->
                    <div v-if="invoice.analyses && invoice.analyses.length > 1" class="border-b border-gray-200 dark:border-gray-800">
                        <nav class="-mb-px flex space-x-6 overflow-x-auto scrollbar-hide" aria-label="Tabs">
                            <button
                                v-for="(analysis, index) in invoice.analyses"
                                :key="analysis.id"
                                @click="currentAnalysis = analysis"
                                :class="[
                                    analysis.id === currentAnalysis?.id
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600',
                                    'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors flex items-center gap-2'
                                ]"
                            >
                                <span v-if="analysis.id === invoice.analysis?.id" class="flex h-2 w-2 relative">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                                </span>
                                {{ formatDate(analysis.created_at) }}
                                <span class="text-xs font-mono font-normal opacity-70 border border-current rounded px-1.5 py-0.5 ml-1">{{ analysis.llm_name }}</span>
                            </button>
                        </nav>
                    </div>

                    <!-- Processing State -->
                    <div
                        v-if="isProcessing"
                        class="rounded-2xl border border-blue-100 bg-blue-50/50 dark:border-blue-900/20 dark:bg-blue-900/10 p-8 space-y-6"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-6 w-6 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            <p class="text-base font-medium text-blue-700 dark:text-blue-400">Analysis in progress…</p>
                        </div>
                        <div class="animate-pulse space-y-4">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-6">
                                <div v-for="i in 6" :key="i" class="space-y-2">
                                    <div class="h-3 bg-blue-200/60 dark:bg-blue-800/40 rounded w-1/2" />
                                    <div class="h-5 bg-blue-200/80 dark:bg-blue-800/60 rounded w-full" />
                                </div>
                            </div>
                            <div class="mt-6 h-40 bg-blue-200/60 dark:bg-blue-800/40 rounded-xl w-full" />
                        </div>
                    </div>

                    <!-- Completed State -->
                    <div
                        v-else-if="isCompleted"
                        class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-sm overflow-hidden"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800/60 bg-gray-50/50 dark:bg-gray-800/20">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                                    <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Extracted Data</span>
                            </div>
                            <div class="text-right flex flex-col items-end gap-0.5">
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                                    {{ currentAnalysis?.llm_name }}
                                </span>
                            </div>
                        </div>

                        <!-- Key-value fields -->
                        <div v-if="analysisFields.length > 0" class="px-6 py-6">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-6">
                                <div v-for="field in analysisFields" :key="field.key" class="bg-gray-50/50 dark:bg-gray-800/20 p-3 rounded-lg border border-gray-100 dark:border-gray-800/50">
                                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        {{ humanizeKey(field.key) }}
                                    </dt>
                                    <dd class="mt-1.5 text-sm font-medium text-gray-900 dark:text-gray-100 break-words">
                                        {{ renderValue(field.value) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Array fields (e.g. line_items) -->
                        <div v-for="arrayField in analysisArrayFields" :key="arrayField.key" class="border-t border-gray-100 dark:border-gray-800/60">
                            <div class="px-6 py-4 bg-gray-50/80 dark:bg-gray-800/40 border-b border-gray-100 dark:border-gray-800/60">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                                    {{ humanizeKey(arrayField.key) }}
                                </h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table
                                    v-if="arrayField.rows.length > 0 && typeof arrayField.rows[0] === 'object'"
                                    class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm"
                                >
                                    <thead class="bg-white dark:bg-gray-900">
                                        <tr>
                                            <th
                                                v-for="col in getRowKeys(arrayField.rows[0])"
                                                :key="col"
                                                class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap"
                                            >
                                                {{ humanizeKey(col) }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-900">
                                        <tr v-for="(row, rowIdx) in arrayField.rows" :key="rowIdx" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20 transition-colors">
                                            <td
                                                v-for="col in getRowKeys(arrayField.rows[0])"
                                                :key="col"
                                                class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap"
                                            >
                                                {{ renderValue(getRowValue(row, col)) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <!-- Fallback: simple list for primitive arrays -->
                                <ul v-else class="px-6 py-4 space-y-2 bg-white dark:bg-gray-900">
                                    <li v-for="(item, idx) in arrayField.rows" :key="idx" class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                        {{ renderValue(item) }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Pending / Failed State -->
                    <div
                        v-else-if="canAnalyse"
                        class="rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/20 p-12 text-center"
                    >
                        <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 mb-6">
                            <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">No analysis available</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                            {{ invoice.status === 'failed' ? 'The previous analysis failed. You can try again to extract structured data.' : 'Run the AI agent to extract structured data from this invoice.' }}
                        </p>
                        <Link
                            :href="invoicesAnalyse.url(invoice.id)"
                            method="post"
                            as="button"
                            class="mt-8 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-all hover:scale-105 active:scale-95"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            {{ invoice.status === 'failed' ? 'Retry Analysis' : 'Analyse Invoice Now' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
