<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index as optionsIndex } from '@/actions/App/Http/Controllers/OptionsController';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Options', href: optionsIndex.url() }],
    },
});

interface Option {
    id: number;
    key: string;
    value: string | null;
}

defineProps<{
    options: Option[];
}>();
</script>

<template>
    <Head title="Options" />

    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Options</h1>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Application configuration options.</p>
            </div>
        </div>

        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg dark:ring-white/10">
                        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th
                                        scope="col"
                                        class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6 dark:text-white"
                                    >
                                        Key
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        Value
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                <tr v-for="option in options" :key="option.id">
                                    <td
                                        class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 dark:text-gray-200"
                                    >
                                        {{ option.key }}
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ option.value ?? '—' }}
                                    </td>
                                </tr>
                                <tr v-if="options.length === 0">
                                    <td
                                        colspan="2"
                                        class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        No options found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
