<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { index as invoicesIndex, show as invoicesShow, analyse as invoicesAnalyse } from '@/actions/App/Http/Controllers/InvoiceController';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Invoices', href: invoicesIndex.url() },
            { title: 'Invoice Details', href: '#' },
        ],
    },
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
}

const props = defineProps<{ invoice: Invoice }>();

const statusColors: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};

function formatBytes(bytes: number): string {
    if (bytes < 1024) { return bytes + ' B'; }
    if (bytes < 1048576) { return (bytes / 1024).toFixed(1) + ' KB'; }
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function formatDate(dateString: string | null): string {
    if (!dateString) { return '-'; }
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
    if (value === null || value === undefined) { return '-'; }
    if (typeof value === 'boolean') { return value ? 'Yes' : 'No'; }
    if (typeof value === 'object') { return JSON.stringify(value, null, 2); }
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
const isCompleted = computed(() => props.invoice.status === 'completed' && props.invoice.analysis !== null);
const canAnalyse = computed(() => props.invoice.status === 'pending' || props.invoice.status === 'failed');

/** Flat key-value pairs from json_data (non-array/object values) */
const analysisFields = computed<Array<{ key: string; value: unknown }>>(() => {
    if (!props.invoice.analysis?.json_data) { return []; }
    return Object.entries(props.invoice.analysis.json_data)
        .filter(([, v]) => isPrimitive(v))
        .map(([k, v]) => ({ key: k, value: v }));
});

/** Array fields from json_data (arrays of objects, e.g. line_items) */
const analysisArrayFields = computed<Array<{ key: string; rows: unknown[] }>>(() => {
    if (!props.invoice.analysis?.json_data) { return []; }
    return Object.entries(props.invoice.analysis.json_data)
        .filter(([, v]) => Array.isArray(v))
        .map(([k, v]) => ({ key: k, rows: v as unknown[] }));
});

/** Get keys from a row object for table headers */
function getRowKeys(row: unknown): string[] {
    if (!row || typeof row !== 'object') return [];
    return Object.keys(row as Record<string, unknown>);
}

/** Get value from a row object for a given column key */
function getRowValue(row: unknown, col: string): unknown {
    if (!row || typeof row !== 'object') return null;
    return (row as Record<string, unknown>)[col];
}
</script>

<template>
    <Head :title="invoice.original_file_name" />

    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

        <!-- Back link -->
        <div class="mb-6">
            <Link
                :href="invoicesIndex.url()"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Invoices
            </Link>
        </div>

        <!-- Invoice Info Card -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col sm:flex-row">

                <!-- Thumbnail -->
                <div class="flex-shrink-0 p-6 flex items-start justify-center sm:w-52">
                    <a :href="invoice.file_url" target="_blank" class="block">
                        <img
                            :src="invoice.file_url"
                            :alt="invoice.original_file_name"
                            class="h-40 w-40 rounded-lg object-cover shadow-md border border-gray-200 dark:border-gray-700 hover:opacity-90 transition-opacity"
                        />
                    </a>
                </div>

                <!-- Info -->
                <div class="flex-1 p-6 border-t sm:border-t-0 sm:border-l border-gray-100 dark:border-gray-800">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900 dark:text-white break-all">
                                {{ invoice.original_file_name }}
                            </h1>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ invoice.mime_type }}</p>
                        </div>
                        <span :class="['inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ring-gray-500/10 capitalize', statusColors[invoice.status] ?? statusColors.pending]">
                            {{ invoice.status }}
                        </span>
                    </div>

                    <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">File Size</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100 mt-0.5">{{ formatBytes(invoice.file_size) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Uploaded At</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100 mt-0.5">{{ formatDate(invoice.uploaded_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Created At</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100 mt-0.5">{{ formatDate(invoice.created_at) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Analysis Section -->
        <div class="mt-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">AI Analysis</h2>

            <!-- Processing skeleton -->
            <div
                v-if="isProcessing"
                class="rounded-xl border border-blue-100 bg-blue-50/50 dark:border-blue-900/40 dark:bg-blue-950/20 p-6 space-y-4"
            >
                <div class="flex items-center gap-3 mb-6">
                    <svg class="h-5 w-5 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Analysis in progress…</p>
                </div>
                <div class="animate-pulse space-y-3">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div v-for="i in 6" :key="i" class="space-y-1.5">
                            <div class="h-3 bg-blue-200/60 dark:bg-blue-800/40 rounded w-2/3" />
                            <div class="h-4 bg-blue-200/80 dark:bg-blue-800/60 rounded w-full" />
                        </div>
                    </div>
                    <div class="mt-4 h-32 bg-blue-200/60 dark:bg-blue-800/40 rounded-lg w-full" />
                </div>
            </div>

            <!-- Completed: render analysis data -->
            <div
                v-else-if="isCompleted"
                class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 shadow-sm overflow-hidden"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Extracted Data</span>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400 dark:text-gray-500">Model: <span class="font-mono text-gray-600 dark:text-gray-300">{{ invoice.analysis?.llm_name }}</span></p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(invoice.analysis?.created_at ?? null) }}</p>
                    </div>
                </div>

                <!-- Key-value fields -->
                <div v-if="analysisFields.length > 0" class="px-6 py-5">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5">
                        <div v-for="field in analysisFields" :key="field.key">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                {{ humanizeKey(field.key) }}
                            </dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white break-words">
                                {{ renderValue(field.value) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Array fields (e.g. line_items) -->
                <div v-for="arrayField in analysisArrayFields" :key="arrayField.key" class="border-t border-gray-100 dark:border-gray-800">
                    <div class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ humanizeKey(arrayField.key) }}
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table
                            v-if="arrayField.rows.length > 0 && typeof arrayField.rows[0] === 'object'"
                            class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm"
                        >
                            <thead class="bg-gray-50 dark:bg-gray-800/50">
                                <tr>
                                    <th
                                        v-for="col in getRowKeys(arrayField.rows[0])"
                                        :key="col"
                                        class="px-4 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide whitespace-nowrap"
                                    >
                                        {{ humanizeKey(col) }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                                <tr v-for="(row, rowIdx) in arrayField.rows" :key="rowIdx">
                                    <td
                                        v-for="col in getRowKeys(arrayField.rows[0])"
                                        :key="col"
                                        class="px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap"
                                    >
                                        {{ renderValue(getRowValue(row, col)) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- Fallback: simple list for primitive arrays -->
                        <ul v-else class="px-6 py-3 space-y-1">
                            <li v-for="(item, idx) in arrayField.rows" :key="idx" class="text-sm text-gray-700 dark:text-gray-300">
                                {{ renderValue(item) }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Pending / Failed: empty state with CTA -->
            <div
                v-else-if="canAnalyse"
                class="rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-10 text-center"
            >
                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.346A3.51 3.51 0 0114.5 20.1v.9H9.5v-.9a3.51 3.51 0 01-1.03-2.459l-.347-.346z" />
                </svg>
                <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">No analysis yet</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ invoice.status === 'failed' ? 'The previous analysis failed. You can try again.' : 'Run the AI agent to extract structured data from this invoice.' }}
                </p>
                <Link
                    :href="invoicesAnalyse.url(invoice.id)"
                    method="post"
                    as="button"
                    class="mt-6 inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    {{ invoice.status === 'failed' ? 'Retry Analysis' : 'Analyse Invoice' }}
                </Link>
            </div>
        </div>
    </div>
</template>
