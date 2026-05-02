<script setup lang="ts">
import { Head, Form } from '@inertiajs/vue3';
import { index as optionsIndex, edit as optionsEdit, update as optionsUpdate } from '@/actions/App/Http/Controllers/OptionsController';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface Option {
    id: number;
    key: string;
    value: string | null;
}

const props = defineProps<{
    option: Option;
}>();

defineOptions({
    layout: (props) => ({
        breadcrumbs: [
            { title: 'Options', href: optionsIndex.url() },
            { title: props.option.key, href: optionsEdit.url(props.option.id) },
        ],
    }),
});
</script>

<template>
    <Head :title="`Edit Option: ${option.key}`" />

    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Edit Option</h1>
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                Update the value for <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-sm dark:bg-gray-800">{{ option.key }}</code>.
            </p>
        </div>

        <div class="overflow-hidden rounded-lg shadow ring-1 ring-black ring-opacity-5 dark:ring-white/10">
            <div class="bg-white px-6 py-8 dark:bg-gray-900">
                <Form
                    v-bind="optionsUpdate.form.patch(option.id)"
                    set-defaults-on-success
                    #default="{ errors, processing, wasSuccessful }"
                >
                    <div class="space-y-6">
                        <!-- Key (read-only) -->
                        <div>
                            <Label>Key</Label>
                            <div class="mt-1.5 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                {{ option.key }}
                            </div>
                        </div>

                        <!-- Value -->
                        <div class="grid gap-2">
                            <Label for="value">Value</Label>
                            <Textarea
                                id="value"
                                name="value"
                                rows="8"
                                :default-value="option.value ?? ''"
                                placeholder="Enter a value…"
                                class="mt-1 block w-full"
                            />
                            <InputError class="mt-2" :message="errors.value" />
                        </div>

                        <!-- Success banner -->
                        <div
                            v-if="wasSuccessful"
                            class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-400"
                        >
                            Option updated successfully.
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a
                                :href="optionsIndex.url()"
                                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </a>
                            <button
                                type="submit"
                                :disabled="processing"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-gray-900"
                            >
                                {{ processing ? 'Saving…' : 'Save Changes' }}
                            </button>
                        </div>
                    </div>
                </Form>
            </div>
        </div>
    </div>
</template>
