<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { index as invoicesIndex, analyse as invoicesAnalyse } from '@/actions/App/Http/Controllers/InvoiceController';

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
    json_data: {
        vendor_name: string;
        vendor_address: string;
        invoice_number: string;
        invoice_date: string;
        due_date: string;
        line_items: Array<{
            description: string;
            quantity: number;
            discount: number | null;
            presents: number | null;
            unit_price: number;
            total: number;
        }>;
        subtotal: number;
        discount_total: number | null;
        present_total: number | null;
        total_amount: number;
        paid_amount: number | null;
        dept_amount: number | null;
        currency: string;
        notes: string;
        [key: string]: any;
    };
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
const showPrompt = ref(false);

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
    if (key === 'dept_amount') {
 return 'Debt Amount'; 
}

    return key
        .replace(/_/g, ' ')
        .replace(/([A-Z])/g, ' $1')
        .replace(/^\w/, (c) => c.toUpperCase())
        .trim();
}

/**
 * Format a number as currency with the given ISO code.
 */
function formatCurrency(amount: unknown, currency: string = 'USD'): string {
    if (typeof amount !== 'number') {
 return '-'; 
}

    try {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency || 'USD',
        }).format(amount);
    } catch (e) {
        return `${amount.toFixed(2)} ${currency}`;
    }
}

const isProcessing = computed(() => props.invoice.status === 'processing');
const hasAnalyses = computed(() => props.invoice.analyses && props.invoice.analyses.length > 0);
const canAnalyse = computed(() => props.invoice.status === 'pending' || props.invoice.status === 'failed');
const canAnalyseAgain = computed(() => props.invoice.status === 'completed' && !isProcessing.value);

/**
 * Extract summary fields that should be displayed at the top.
 */
const summaryData = computed(() => {
    const data = currentAnalysis.value?.json_data;
    if (!data) {
 return null; 
}

    return {
        vendor: data.vendor_name,
        address: data.vendor_address,
        number: data.invoice_number,
        date: data.invoice_date,
        dueDate: data.due_date,
        currency: data.currency,
        total: data.total_amount,
    };
});

/**
 * Extract financial totals for the footer.
 */
const financialTotals = computed(() => {
    const data = currentAnalysis.value?.json_data;
    if (!data) {
 return []; 
}

    return [
        { key: 'subtotal', value: data.subtotal },
        { key: 'discount_total', value: data.discount_total },
        { key: 'present_total', value: data.present_total },
        { key: 'total_amount', value: data.total_amount, highlight: true },
        { key: 'paid_amount', value: data.paid_amount },
        { key: 'dept_amount', value: data.dept_amount },
    ].filter(f => f.value !== undefined);
});

/** Other fields from json_data that aren't part of the structured sections */
const otherFields = computed<Array<{ key: string; value: unknown }>>(() => {
    if (!currentAnalysis.value?.json_data) {
 return []; 
}

    const structuredKeys = [
        'vendor_name', 'vendor_address', 'invoice_number', 'invoice_date', 'due_date',
        'subtotal', 'discount_total', 'present_total', 'total_amount', 'paid_amount', 'dept_amount',
        'currency', 'line_items', 'notes'
    ];

    return Object.entries(currentAnalysis.value.json_data)
        .filter(([k, v]) => isPrimitive(v) && !structuredKeys.includes(k))
        .map(([k, v]) => ({ key: k, value: v }));
});

/** The main line items table data */
const lineItems = computed(() => {
    return currentAnalysis.value?.json_data?.line_items || [];
});

/** Fixed keys for line items to ensure consistent columns */
const lineItemKeys = ['description', 'quantity', 'unit_price', 'discount', 'presents', 'total'];

// Magnifier Logic
const rotation = ref(0);
const containerRef = ref<HTMLDivElement | null>(null);
const imageRef = ref<HTMLImageElement | null>(null);
const isHovering = ref(false);
const magnifierPos = ref({ x: 0, y: 0 });
const magnifierSize = 400;
const zoomScale = 2.5;

const rotateLeft = () => {
    rotation.value = (rotation.value - 90 + 360) % 360;
};

const rotateRight = () => {
    rotation.value = (rotation.value + 90) % 360;
};

const magnifierStyle = computed(() => {
    if (!imageRef.value || !isHovering.value) {
        return { display: 'none' };
    }

    const imgRect = imageRef.value.getBoundingClientRect();

    // Position of the mouse relative to the actual image content
    const x = magnifierPos.value.x - imgRect.left;
    const y = magnifierPos.value.y - imgRect.top;

    // Calculate background position based on rotation
    let bgX = 0;
    let bgY = 0;

    const w = imgRect.width;
    const h = imgRect.height;

    if (rotation.value === 0) {
        bgX = (x / w) * 100;
        bgY = (y / h) * 100;
    } else if (rotation.value === 90) {
        bgX = (y / h) * 100;
        bgY = (1 - x / w) * 100;
    } else if (rotation.value === 180) {
        bgX = (1 - x / w) * 100;
        bgY = (1 - y / h) * 100;
    } else if (rotation.value === 270) {
        bgX = (1 - y / h) * 100;
        bgY = (x / w) * 100;
    }

    return {
        display: 'block',
        position: 'fixed',
        top: `${magnifierPos.value.y - magnifierSize / 2}px`,
        left: `${magnifierPos.value.x - magnifierSize / 2}px`,
        width: `${magnifierSize}px`,
        height: `${magnifierSize}px`,
        backgroundImage: `url(${props.invoice.file_url})`,
        backgroundSize: (rotation.value === 90 || rotation.value === 270)
            ? `${h * zoomScale}px ${w * zoomScale}px`
            : `${w * zoomScale}px ${h * zoomScale}px`,
        backgroundPosition: `${bgX}% ${bgY}%`,
        transform: `rotate(${rotation.value}deg)`,
        pointerEvents: 'none',
        zIndex: 9999,
        transition: 'opacity 0.2s ease-in-out',
    };
});

function handleMouseMove(e: MouseEvent) {
    magnifierPos.value = { x: e.clientX, y: e.clientY };
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
                        <div 
                            ref="containerRef"
                            class="relative w-full h-full cursor-zoom-in overflow-hidden rounded-xl group"
                            @mouseenter="isHovering = true"
                            @mouseleave="isHovering = false"
                            @mousemove="handleMouseMove"
                        >
                            <img
                                ref="imageRef"
                                :src="invoice.file_url"
                                :alt="invoice.original_file_name"
                                class="w-full h-auto max-h-[85vh] object-contain rounded-xl shadow-sm border border-gray-200/50 dark:border-gray-700/50 transition-all duration-300"
                                :class="{ 'opacity-50': isHovering }"
                                :style="{ transform: `rotate(${rotation}deg)` }"
                            />
                            


                            <!-- Magnifier glass -->
                            <Teleport to="body">
                                <div
                                    v-if="isHovering"
                                    :style="magnifierStyle"
                                    class="rounded-2xl border-4 border-white shadow-[0_0_30px_rgba(0,0,0,0.4)] pointer-events-none ring-1 ring-black/10"
                                ></div>
                            </Teleport>

                            <div v-if="!isHovering" class="absolute inset-0 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity bg-black/5 rounded-xl">
                                <a :href="invoice.file_url" target="_blank" class="bg-black/75 text-white text-xs px-3 py-1.5 rounded-full backdrop-blur-sm shadow-lg font-medium flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z" />
                                    </svg>
                                    Open Original
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Rotation & Controls Toolbar (Moved below) -->
                    <div class="flex items-center justify-center gap-3 py-2">
                        <button 
                            @click="rotateLeft" 
                            class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition-all active:scale-95 text-sm font-medium"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-6 6m0 0l-6-6m6 6V9a6 6 0 0112 0v3" />
                            </svg>
                            Rotate Left
                        </button>
                        <button 
                            @click="rotateRight" 
                            class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition-all active:scale-95 text-sm font-medium"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15l6 6m0 0l6-6m-6 6V9a6 6 0 00-12 0v3" />
                            </svg>
                            Rotate Right
                        </button>
                        <a 
                            :href="invoice.file_url" 
                            target="_blank" 
                            class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition-all active:scale-95 text-sm font-medium"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z" />
                            </svg>
                            View Original
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
                        <Link
                            v-if="canAnalyseAgain"
                            :href="invoicesAnalyse.url(invoice.id)"
                            method="post"
                            as="button"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600/10 px-4 py-2 text-sm font-semibold text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white transition-all active:scale-95 border border-blue-600/20"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Analyse Again
                        </Link>
                    </div>

                    <!-- Tabs -->
                    <div v-if="hasAnalyses" class="border-b border-gray-200 dark:border-gray-800">
                        <nav class="-mb-px flex space-x-6 overflow-x-auto scrollbar-hide" aria-label="Tabs">
                            <button
                                v-for="analysis in invoice.analyses"
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
                            
                            <!-- Processing Tab -->
                            <div v-if="isProcessing" class="border-b-2 border-blue-500/50 py-3 px-1 text-sm font-medium flex items-center gap-2 text-blue-500 animate-pulse">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                New analysis…
                            </div>
                        </nav>
                    </div>

                    <!-- Processing State (No previous analysis) -->
                    <div
                        v-if="isProcessing && !hasAnalyses"
                        class="rounded-2xl border border-blue-100 bg-blue-50/50 dark:border-blue-900/20 dark:bg-blue-900/10 p-8 space-y-6"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-6 w-6 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            <p class="text-base font-medium text-blue-700 dark:text-blue-400">Initial analysis in progress…</p>
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

                    <!-- Completed State / Existing Analysis while Processing -->
                    <div
                        v-else-if="currentAnalysis"
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

                        <!-- Prompt Toggle -->
                        <div v-if="currentAnalysis?.prompt_text" class="px-6 py-3 border-b border-gray-100 dark:border-gray-800/40 bg-gray-50/30 dark:bg-gray-800/10">
                            <button 
                                @click="showPrompt = !showPrompt" 
                                class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-blue-500 dark:text-gray-500 dark:hover:text-blue-400 transition-colors"
                            >
                                <svg :class="['h-3.5 w-3.5 transition-transform duration-200', showPrompt ? 'rotate-90' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                                AI Instructions & Prompt
                            </button>
                            <div v-if="showPrompt" class="mt-4 animate-in fade-in slide-in-from-top-2 duration-300">
                                <div class="relative group">
                                    <pre class="text-[11px] font-mono leading-relaxed text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-950 p-4 rounded-xl border border-gray-200 dark:border-gray-800 overflow-x-auto whitespace-pre-wrap max-h-60 scrollbar-thin">{{ currentAnalysis.prompt_text }}</pre>
                                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="bg-gray-100 dark:bg-gray-800 text-[10px] px-2 py-1 rounded border border-gray-200 dark:border-gray-700 text-gray-500">System Prompt</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Structured Information -->
                        <div v-if="summaryData" class="px-6 py-6 border-b border-gray-100 dark:border-gray-800/40">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                <!-- Vendor Info -->
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Vendor</label>
                                        <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white leading-tight">{{ summaryData.vendor }}</p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 whitespace-pre-wrap">{{ summaryData.address }}</p>
                                    </div>
                                </div>
                                
                                <!-- Invoice Details -->
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Invoice #</label>
                                            <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ summaryData.number }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Currency</label>
                                            <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ summaryData.currency }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Date</label>
                                            <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">{{ summaryData.date }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Due Date</label>
                                            <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">{{ summaryData.dueDate }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Big Total -->
                                <div class="flex flex-col items-start lg:items-end justify-center">
                                    <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 lg:text-right">Total Amount</label>
                                    <p class="mt-1 text-3xl font-black text-blue-600 dark:text-blue-400">
                                        {{ formatCurrency(summaryData.total, summaryData.currency) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Line Items Table -->
                        <div v-if="lineItems.length > 0" class="border-b border-gray-100 dark:border-gray-800/60">
                            <div class="px-6 py-3 bg-gray-50/50 dark:bg-gray-800/20 border-b border-gray-100 dark:border-gray-800/60 flex items-center justify-between">
                                <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">Line Items</h3>
                                <span class="text-[10px] font-medium text-gray-400">{{ lineItems.length }} items detected</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                    <thead class="bg-white dark:bg-gray-900">
                                        <tr>
                                            <th v-for="col in lineItemKeys" :key="col" class="px-6 py-3 text-left text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                                {{ humanizeKey(col) }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-900">
                                        <tr v-for="(item, idx) in lineItems" :key="idx" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20 transition-colors">
                                            <td v-for="col in lineItemKeys" :key="col" class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                <template v-if="['unit_price', 'discount', 'presents', 'total'].includes(col)">
                                                    {{ formatCurrency(item[col], summaryData?.currency) }}
                                                </template>
                                                <template v-else>
                                                    {{ renderValue(item[col]) }}
                                                </template>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Totals & Notes -->
                        <div class="px-6 py-8 bg-gray-50/30 dark:bg-gray-800/10">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                                <!-- Notes -->
                                <div>
                                    <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Notes & Comments</label>
                                    <div class="mt-2 p-4 rounded-xl bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-sm text-gray-600 dark:text-gray-400 min-h-[100px] whitespace-pre-wrap">
                                        {{ currentAnalysis.json_data.notes || 'No additional notes provided.' }}
                                    </div>
                                </div>

                                <!-- Financial Totals -->
                                <div class="space-y-3">
                                    <div 
                                        v-for="total in financialTotals" 
                                        :key="total.key"
                                        class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800/50 last:border-0"
                                        :class="{ 'mt-4 pt-4 border-t-2 border-gray-200 dark:border-gray-700': total.highlight }"
                                    >
                                        <span :class="[total.highlight ? 'text-sm font-bold text-gray-900 dark:text-white' : 'text-xs font-medium text-gray-500 dark:text-gray-400']">
                                            {{ humanizeKey(total.key) }}
                                        </span>
                                        <span :class="[total.highlight ? 'text-lg font-black text-blue-600 dark:text-blue-400' : 'text-sm font-bold text-gray-900 dark:text-gray-200']">
                                            {{ formatCurrency(total.value, summaryData?.currency) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Other/Unstructured Fields -->
                        <div v-if="otherFields.length > 0" class="px-6 py-6 border-t border-gray-100 dark:border-gray-800/40">
                            <h4 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-4">Additional Metadata</h4>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div v-for="field in otherFields" :key="field.key" class="bg-gray-50/50 dark:bg-gray-800/20 p-3 rounded-lg border border-gray-100 dark:border-gray-800/50">
                                    <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                        {{ humanizeKey(field.key) }}
                                    </dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100 break-words">
                                        {{ renderValue(field.value) }}
                                    </dd>
                                </div>
                            </dl>
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
