<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { store } from '@/actions/App/Http/Controllers/InvoiceController';
import { index as invoicesUpload } from '@/actions/App/Http/Controllers/InvoiceController';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Invoices', href: invoicesUpload.url() },
            { title: 'Upload', href: invoicesUpload.url() },
        ],
    },
});

interface UploadedInvoice {
    id: number;
    original_file_name: string;
    file_url: string;
    mime_type: string;
    file_size: number;
    status: string;
    uploaded_at: string | null;
}

const isDragging = ref(false);
const isUploading = ref(false);
const uploadProgress = ref(0);
const uploadError = ref<string | null>(null);
const validationErrors = ref<Record<string, string[]>>({});
const uploadedInvoices = ref<UploadedInvoice[]>([]);

interface SelectedFile {
    file: File;
    previewUrl: string;
}

const selectedFiles = ref<SelectedFile[]>([]);

const hasFiles = computed(() => selectedFiles.value.length > 0);
const hasUploaded = computed(() => uploadedInvoices.value.length > 0);

function formatBytes(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function addFiles(files: FileList | File[]) {
    const allowed = ['image/jpeg', 'image/jpg', 'image/png'];
    Array.from(files).forEach((file) => {
        if (!allowed.includes(file.type)) {
            uploadError.value = `"${file.name}" is not a supported image type (jpeg, jpg, png).`;
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            uploadError.value = `"${file.name}" exceeds the 10 MB size limit.`;
            return;
        }
        selectedFiles.value.push({
            file,
            previewUrl: URL.createObjectURL(file),
        });
    });
    uploadError.value = null;
    validationErrors.value = {};
}

function onFileInputChange(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        addFiles(input.files);
    }
    input.value = '';
}

function onDrop(event: DragEvent) {
    isDragging.value = false;
    if (event.dataTransfer?.files) {
        addFiles(event.dataTransfer.files);
    }
}

function removeFile(index: number) {
    URL.revokeObjectURL(selectedFiles.value[index].previewUrl);
    selectedFiles.value.splice(index, 1);
}

function openFilePicker() {
    document.getElementById('invoice-file-input')?.click();
}

function submit() {
    if (!hasFiles.value || isUploading.value) return;

    const formData = new FormData();
    selectedFiles.value.forEach(({ file }) => {
        formData.append('invoices[]', file);
    });

    isUploading.value = true;
    uploadError.value = null;
    validationErrors.value = {};

    router.post(store.url(), formData, {
        forceFormData: true,
        onProgress: (progress) => {
            uploadProgress.value = progress?.percentage ?? 0;
        },
        onSuccess: (page) => {
            const data = (page as any)?.props?.invoices ?? [];
            uploadedInvoices.value = data;
            selectedFiles.value.forEach(({ previewUrl }) =>
                URL.revokeObjectURL(previewUrl),
            );
            selectedFiles.value = [];
        },
        onError: (errors) => {
            validationErrors.value = errors as Record<string, string[]>;
            uploadError.value = 'Please fix the errors below and try again.';
        },
        onFinish: () => {
            isUploading.value = false;
            uploadProgress.value = 0;
        },
    });
}
</script>

<template>
    <Head title="Upload Invoices" />

    <div class="mx-auto max-w-3xl px-4 py-10">
        <!-- Header -->
        <div class="mb-8">
            <h1
                class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white"
            >
                Upload Invoices
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Select one or more invoice images (JPEG or PNG, max 10 MB each).
            </p>
        </div>

        <!-- Drop Zone -->
        <div
            class="relative flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-8 py-14 transition-colors"
            :class="[
                isDragging
                    ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-950/30'
                    : 'border-gray-300 bg-gray-50 hover:border-blue-400 hover:bg-blue-50/50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-blue-500 dark:hover:bg-blue-950/20',
            ]"
            @click="openFilePicker"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
        >
            <input
                id="invoice-file-input"
                type="file"
                accept="image/jpeg,image/jpg,image/png"
                multiple
                class="hidden"
                @change="onFileInputChange"
            />

            <div
                class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-7 w-7 text-blue-600 dark:text-blue-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"
                    />
                </svg>
            </div>

            <p
                class="mt-4 text-sm font-medium text-gray-700 dark:text-gray-300"
            >
                <span class="text-blue-600 dark:text-blue-400"
                    >Click to browse</span
                >
                or drag &amp; drop files here
            </p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                JPEG, JPG, PNG — up to 10 MB each
            </p>
        </div>

        <!-- Error Banner -->
        <div
            v-if="uploadError"
            class="mt-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/30 dark:text-red-400"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="mt-0.5 h-4 w-4 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                />
            </svg>
            <span>{{ uploadError }}</span>
        </div>

        <!-- Validation Errors -->
        <div
            v-if="Object.keys(validationErrors).length"
            class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 dark:border-red-800 dark:bg-red-950/30"
        >
            <ul class="space-y-1">
                <li
                    v-for="(messages, field) in validationErrors"
                    :key="field"
                    class="text-sm text-red-700 dark:text-red-400"
                >
                    {{ messages[0] }}
                </li>
            </ul>
        </div>

        <!-- Selected Files List -->
        <div v-if="hasFiles" class="mt-6">
            <h2
                class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300"
            >
                Selected ({{ selectedFiles.length }})
            </h2>
            <ul class="space-y-3">
                <li
                    v-for="(item, index) in selectedFiles"
                    :key="index"
                    class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                >
                    <img
                        :src="item.previewUrl"
                        :alt="item.file.name"
                        class="h-12 w-12 rounded-lg object-cover"
                    />
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm font-medium text-gray-800 dark:text-gray-200"
                        >
                            {{ item.file.name }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ formatBytes(item.file.size) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-red-100 hover:text-red-500 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                        @click.stop="removeFile(index)"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </li>
            </ul>
        </div>

        <!-- Upload Progress -->
        <div v-if="isUploading" class="mt-6">
            <div
                class="mb-1 flex items-center justify-between text-sm text-gray-600 dark:text-gray-400"
            >
                <span>Uploading…</span>
                <span>{{ uploadProgress }}%</span>
            </div>
            <div
                class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"
            >
                <div
                    class="h-2 rounded-full bg-blue-500 transition-all duration-300"
                    :style="{ width: uploadProgress + '%' }"
                />
            </div>
        </div>

        <!-- Submit Button -->
        <div v-if="hasFiles" class="mt-6">
            <button
                id="invoice-upload-btn"
                type="button"
                :disabled="isUploading"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60 dark:bg-blue-500 dark:hover:bg-blue-600"
                @click="submit"
            >
                <svg
                    v-if="isUploading"
                    class="h-4 w-4 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v8z"
                    />
                </svg>
                {{
                    isUploading
                        ? 'Uploading…'
                        : `Upload ${selectedFiles.length} invoice${selectedFiles.length > 1 ? 's' : ''}`
                }}
            </button>
        </div>

        <!-- Uploaded Results -->
        <div v-if="hasUploaded" class="mt-10">
            <h2
                class="mb-4 text-sm font-medium text-gray-700 dark:text-gray-300"
            >
                ✓ Uploaded successfully
            </h2>
            <ul class="space-y-3">
                <li
                    v-for="invoice in uploadedInvoices"
                    :key="invoice.id"
                    class="flex items-center gap-4 rounded-xl border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950/30"
                >
                    <a
                        :href="invoice.file_url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <img
                            :src="invoice.file_url"
                            :alt="invoice.original_file_name"
                            class="h-12 w-12 rounded-lg object-cover ring-1 ring-green-300"
                        />
                    </a>
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm font-medium text-gray-800 dark:text-gray-100"
                        >
                            {{ invoice.original_file_name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ formatBytes(invoice.file_size) }} ·
                            {{ invoice.mime_type }}
                        </p>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                    >
                        {{ invoice.status }}
                    </span>
                </li>
            </ul>
        </div>
    </div>
</template>
