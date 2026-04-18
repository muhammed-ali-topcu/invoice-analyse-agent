<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { index as invoicesIndex } from '@/actions/App/Http/Controllers/InvoiceController';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Invoices', href: invoicesIndex.url() },
        ],
    },
});

interface Invoice {
    id: number;
    original_file_name: string;
    file_url: string;
    mime_type: string;
    file_size: number;
    status: string;
    uploaded_at: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedData {
    data: Invoice[];
    links: PaginationLink[];
    from: number;
    to: number;
    total: number;
}

const props = defineProps<{
    invoices: PaginatedData;
    filters: {
        search?: string;
        status?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

// Map status to badge color
const statusColors: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};

function formatBytes(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
}

function updateData() {
    router.get(
        invoicesIndex.url(),
        {
            search: search.value,
            status: status.value,
            sort: props.filters.sort,
            direction: props.filters.direction,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}

let timeoutId: ReturnType<typeof setTimeout> | null = null;
const debouncedSearch = () => {
    if (timeoutId) clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        updateData();
    }, 300);
};

watch(search, () => debouncedSearch());
watch(status, () => updateData());

function toggleSort(field: string) {
    let direction = 'asc';
    if (props.filters.sort === field && props.filters.direction === 'asc') {
        direction = 'desc';
    }
    router.get(
        invoicesIndex.url(),
        {
            ...props.filters,
            sort: field,
            direction: direction,
        },
        { preserveState: true, preserveScroll: true }
    );
}

function getSortIcon(field: string) {
    if (props.filters.sort !== field) return null;
    return props.filters.direction === 'asc' ? '↑' : '↓';
}
</script>

<template>
    <Head title="Invoices" />

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Invoices</h1>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">A list of all the uploaded invoices.</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <Link
                    href="/invoices/upload"
                    class="block rounded-md bg-blue-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                >
                    Upload Invoice
                </Link>
            </div>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div class="w-full sm:w-1/3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by file name..."
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 dark:bg-gray-800 dark:text-white dark:ring-gray-700 dark:placeholder:text-gray-500 dark:focus:ring-blue-500"
                />
            </div>
            <div class="w-full sm:w-1/4">
                <select
                    v-model="status"
                    class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm sm:leading-6 dark:bg-gray-800 dark:text-white dark:ring-gray-700 dark:focus:ring-blue-500"
                >
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
        </div>

        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg dark:ring-white/10">
                        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6 dark:text-white">
                                        Thumb
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 cursor-pointer hover:bg-gray-100 dark:text-white dark:hover:bg-gray-800" @click="toggleSort('original_file_name')">
                                        Name {{ getSortIcon('original_file_name') }}
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 cursor-pointer hover:bg-gray-100 dark:text-white dark:hover:bg-gray-800" @click="toggleSort('file_size')">
                                        Size {{ getSortIcon('file_size') }}
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 cursor-pointer hover:bg-gray-100 dark:text-white dark:hover:bg-gray-800" @click="toggleSort('uploaded_at')">
                                        Uploaded At {{ getSortIcon('uploaded_at') }}
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                <tr v-for="invoice in invoices.data" :key="invoice.id">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                                        <a :href="invoice.file_url" target="_blank">
                                            <img :src="invoice.file_url" :alt="invoice.original_file_name" class="h-10 w-10 rounded object-cover shadow border border-gray-200 dark:border-gray-700" />
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-200">
                                        {{ invoice.original_file_name }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatBytes(invoice.file_size) }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(invoice.uploaded_at) }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        <span :class="['inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ring-gray-500/10', statusColors[invoice.status] || statusColors.pending]">
                                            {{ invoice.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="invoices.data.length === 0">
                                    <td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No invoices found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="invoices.links.length > 3" class="mt-6 flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6 dark:border-gray-700">
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-400">
                        Showing <span class="font-medium">{{ invoices.from }}</span> to <span class="font-medium">{{ invoices.to }}</span> of <span class="font-medium">{{ invoices.total }}</span> results
                    </p>
                </div>
                <div>
                    <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                        <template v-for="(link, i) in invoices.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    link.active ? 'z-10 bg-blue-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:text-gray-300 dark:ring-gray-600 dark:hover:bg-gray-800',
                                    'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20',
                                    i === 0 ? 'rounded-l-md' : '',
                                    i === invoices.links.length - 1 ? 'rounded-r-md' : '',
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                :class="[
                                    'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-300 ring-1 ring-inset ring-gray-300 dark:text-gray-600 dark:ring-gray-700',
                                    i === 0 ? 'rounded-l-md' : '',
                                    i === invoices.links.length - 1 ? 'rounded-r-md' : '',
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>
