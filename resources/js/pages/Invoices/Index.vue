<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Calendar, 
    Eye, 
    FileText, 
    FilterX, 
    HardDrive, 
    Inbox, 
    Play, 
    Plus, 
    Search 
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { index as invoicesIndex, show as invoicesShow } from '@/actions/App/Http/Controllers/InvoiceController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

defineOptions({
    layout: () => ({
        breadcrumbs: [{ title: 'Invoices', href: invoicesIndex.url() }],
    }),
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
    meta: {
        links: PaginationLink[];
        from: number;
        to: number;
        total: number;
    };
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
const status = ref(props.filters.status || 'all');

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'completed':
            return 'secondary';
        case 'failed':
            return 'destructive';
        case 'processing':
            return 'default';
        default:
            return 'outline';
    }
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

    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function updateData() {
    router.get(
        invoicesIndex.url(),
        {
            search: search.value,
            status: status.value === 'all' ? '' : status.value,
            sort: props.filters.sort,
            direction: props.filters.direction,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}

let timeoutId: ReturnType<typeof setTimeout> | null = null;
const debouncedSearch = () => {
    if (timeoutId) {
clearTimeout(timeoutId);
}

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

function resetFilters() {
    search.value = '';
    status.value = '';
}
</script>

<template>
    <Head title="Invoices" />

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-8">
            <Heading title="Invoices" description="Manage and analyze your uploaded invoices." />
            
            <Link href="/invoices/upload">
                <Button class="w-full sm:w-auto shadow-lg shadow-primary/20">
                    <Plus class="mr-2 h-4 w-4" />
                    Upload Invoice
                </Button>
            </Link>
        </div>

        <!-- Filters Section -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center mb-6">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    v-model="search"
                    placeholder="Search by file name..."
                    class="pl-9 bg-background/50 backdrop-blur-sm"
                />
            </div>
            
            <div class="flex gap-2">
                <Select v-model="status">
                    <SelectTrigger class="w-[160px] bg-background/50 backdrop-blur-sm">
                        <SelectValue placeholder="All Statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Statuses</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="processing">Processing</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                        <SelectItem value="failed">Failed</SelectItem>
                    </SelectContent>
                </Select>

                <Button 
                    v-if="search || status" 
                    variant="ghost" 
                    size="icon" 
                    @click="resetFilters"
                    title="Reset filters"
                >
                    <FilterX class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <!-- Invoices List -->
        <div v-if="invoices.data.length > 0" class="grid gap-4">
            <!-- Table Header (Desktop) -->
            <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">
                <div class="col-span-1">Preview</div>
                <div class="col-span-4 cursor-pointer hover:text-foreground transition-colors flex items-center gap-1" @click="toggleSort('original_file_name')">
                    Name
                    <ArrowUpDown v-if="props.filters.sort === 'original_file_name'" class="h-3 w-3" />
                </div>
                <div class="col-span-2 cursor-pointer hover:text-foreground transition-colors flex items-center gap-1" @click="toggleSort('file_size')">
                    Size
                    <ArrowUpDown v-if="props.filters.sort === 'file_size'" class="h-3 w-3" />
                </div>
                <div class="col-span-2 cursor-pointer hover:text-foreground transition-colors flex items-center gap-1" @click="toggleSort('uploaded_at')">
                    Date
                    <ArrowUpDown v-if="props.filters.sort === 'uploaded_at'" class="h-3 w-3" />
                </div>
                <div class="col-span-1">Status</div>
                <div class="col-span-2 text-right">Actions</div>
            </div>

            <!-- Invoice Rows -->
            <Card 
                v-for="invoice in invoices.data" 
                :key="invoice.id" 
                class="overflow-hidden hover:shadow-md hover:border-primary/20 transition-all duration-200 group"
            >
                <CardContent class="p-0">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center p-4 md:px-6">
                        <!-- Preview -->
                        <div class="col-span-1">
                            <a :href="invoice.file_url" target="_blank" class="block w-12 h-12 rounded-lg overflow-hidden border bg-muted group-hover:border-primary/30 transition-colors">
                                <img 
                                    v-if="invoice.mime_type.startsWith('image/')" 
                                    :src="invoice.file_url" 
                                    class="w-full h-full object-cover" 
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground">
                                    <FileText class="h-6 w-6" />
                                </div>
                            </a>
                        </div>

                        <!-- Name & Metadata (Mobile) -->
                        <div class="col-span-4 flex flex-col gap-0.5">
                            <span class="text-sm font-medium truncate" :title="invoice.original_file_name">
                                {{ invoice.original_file_name }}
                            </span>
                            <div class="flex items-center gap-2 md:hidden">
                                <Badge :variant="getStatusVariant(invoice.status)" class="capitalize text-[10px] px-1.5 py-0">
                                    {{ invoice.status }}
                                </Badge>
                                <span class="text-xs text-muted-foreground">{{ formatBytes(invoice.file_size) }}</span>
                            </div>
                        </div>

                        <!-- Size (Desktop) -->
                        <div class="hidden md:flex col-span-2 items-center text-sm text-muted-foreground">
                            <HardDrive class="mr-2 h-3 w-3 opacity-50" />
                            {{ formatBytes(invoice.file_size) }}
                        </div>

                        <!-- Date (Desktop) -->
                        <div class="hidden md:flex col-span-2 items-center text-sm text-muted-foreground">
                            <Calendar class="mr-2 h-3 w-3 opacity-50" />
                            {{ formatDate(invoice.uploaded_at) }}
                        </div>

                        <!-- Status (Desktop) -->
                        <div class="hidden md:block col-span-1">
                            <Badge :variant="getStatusVariant(invoice.status)" class="capitalize">
                                {{ invoice.status }}
                            </Badge>
                        </div>

                        <!-- Actions -->
                        <div class="col-span-2 flex items-center justify-end gap-2">
                            <Link :href="invoicesShow.url(invoice.id)">
                                <Button variant="ghost" size="sm" class="h-8 px-2 lg:px-3">
                                    <Eye class="h-4 w-4 lg:mr-2" />
                                    <span class="hidden lg:inline">View</span>
                                </Button>
                            </Link>
                            
                            <Link 
                                v-if="invoice.status === 'pending' || invoice.status === 'failed'"
                                :href="`/invoices/${invoice.id}/analyse`" 
                                method="post" 
                                as="button"
                            >
                                <Button variant="outline" size="sm" class="h-8 px-2 lg:px-3 border-primary/20 hover:bg-primary/5 text-primary">
                                    <Play class="h-4 w-4 lg:mr-2" />
                                    <span class="hidden lg:inline">Analyse</span>
                                </Button>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="invoices.meta.links.length > 3" class="mt-8 flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Showing {{ invoices.meta.from }} to {{ invoices.meta.to }} of {{ invoices.meta.total }}
                </p>
                
                <div class="flex gap-1">
                    <template v-for="(link, i) in invoices.meta.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors h-9 px-4',
                                link.active 
                                    ? 'bg-primary text-primary-foreground shadow' 
                                    : 'hover:bg-accent hover:text-accent-foreground border'
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                        <span
                            v-else
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium h-9 px-4 opacity-50 cursor-not-allowed border"
                        >
                            <span v-html="link.label" />
                        </span>
                    </template>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="flex flex-col items-center justify-center py-20 px-4 text-center">
            <div class="bg-muted/50 p-6 rounded-full mb-6 relative">
                <Inbox class="h-12 w-12 text-muted-foreground/60" />
                <div class="absolute -right-1 -top-1 bg-primary h-4 w-4 rounded-full border-2 border-background animate-pulse"></div>
            </div>
            <h3 class="text-xl font-semibold mb-2">No invoices found</h3>
            <p class="text-muted-foreground max-w-xs mb-8">
                {{ search || status 
                    ? "We couldn't find any invoices matching your filters." 
                    : "Upload your first invoice to start the AI-powered analysis process." 
                }}
            </p>
            <div class="flex gap-3">
                <Button v-if="search || status" variant="outline" @click="resetFilters">
                    Clear Filters
                </Button>
                <Link href="/invoices/upload">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Upload Invoice
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>

