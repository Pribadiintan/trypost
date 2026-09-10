<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { IconFileText, IconUpload } from '@tabler/icons-vue';
import { trans } from 'laravel-vue-i18n';
import { computed, onBeforeUnmount, ref } from 'vue';

import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import postImports from '@/routes/app/post-imports';
import { index as postsIndex } from '@/routes/app/posts';

interface ImportRow {
    id: string;
    row_number: number;
    status: string;
    error: string | null;
    language_code: string | null;
    mapped_content: string | null;
    post_id: string | null;
}

interface ImportState {
    id: string;
    original_filename: string;
    status: string;
    total_rows: number;
    valid_rows: number;
    invalid_rows: number;
    processed_rows: number;
    created_count: number;
    error: string | null;
    rows?: ImportRow[];
}

defineProps<{ maxRows: number }>();

const fileInput = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const processing = ref(false);
const uploadError = ref<string | null>(null);
const state = ref<ImportState | null>(null);
let poll: ReturnType<typeof setTimeout> | null = null;

const stopPolling = (): void => {
    if (poll) {
        clearTimeout(poll);
        poll = null;
    }
};

onBeforeUnmount(stopPolling);

const csrf = (): string =>
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';

const fetchState = async (path: string): Promise<ImportState> => {
    const response = await fetch(path, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(String(response.status));
    }

    return (await response.json()) as ImportState;
};

const pickFile = (): void => fileInput.value?.click();

const onFileChange = async (event: Event): Promise<void> => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    uploadError.value = null;
    uploading.value = true;
    stopPolling();

    try {
        const form = new FormData();
        form.append('file', file);

        const response = await fetch(postImports.store.url(), {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: form,
        });

        if (!response.ok) {
            const body = (await response.json().catch(() => ({}))) as {
                message?: string;
            };
            uploadError.value = body.message ?? trans('post_import.errors.upload');

            return;
        }

        state.value = (await response.json()) as ImportState;
        pollParse();
    } catch {
        uploadError.value = trans('post_import.errors.upload');
    } finally {
        uploading.value = false;
        input.value = '';
    }
};

const pollParse = (): void => {
    stopPolling();

    poll = setTimeout(async () => {
        if (!state.value) {
            return;
        }

        try {
            const next = await fetchState(postImports.show.url(state.value.id));
            state.value = next;

            if (next.status === 'preview_ready') {
                await loadPreview();

                return;
            }

            if (next.status === 'failed') {
                return;
            }

            pollParse();
        } catch {
            pollParse();
        }
    }, 1500);
};

const loadPreview = async (): Promise<void> => {
    if (!state.value) {
        return;
    }

    state.value = await fetchState(postImports.preview.url(state.value.id));
};

const startProcessing = async (): Promise<void> => {
    if (!state.value || processing.value) {
        return;
    }

    processing.value = true;

    try {
        const response = await fetch(postImports.process.url(state.value.id), {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        });

        if (!response.ok) {
            uploadError.value = trans('post_import.errors.process');

            return;
        }

        state.value = (await response.json()) as ImportState;
        pollProcess();
    } catch {
        uploadError.value = trans('post_import.errors.process');
    } finally {
        processing.value = false;
    }
};

const pollProcess = (): void => {
    stopPolling();

    poll = setTimeout(async () => {
        if (!state.value) {
            return;
        }

        try {
            const next = await fetchState(postImports.show.url(state.value.id));
            state.value = next;

            if (next.status === 'completed' || next.status === 'failed') {
                return;
            }

            pollProcess();
        } catch {
            pollProcess();
        }
    }, 2000);
};

const reset = (): void => {
    stopPolling();
    state.value = null;
    uploadError.value = null;
};

const goToDrafts = (): void => router.visit(postsIndex.url('draft'));

const status = computed(() => state.value?.status ?? null);
const rows = computed(() => state.value?.rows ?? []);

const rowVariant = (
    rowStatus: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (rowStatus === 'invalid' || rowStatus === 'failed') {
        return 'destructive';
    }

    if (rowStatus === 'needs_review') {
        return 'secondary';
    }

    return 'outline';
};
</script>

<template>
    <Head :title="$t('post_import.title')" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-6 px-6 py-8">
            <PageHeader :title="$t('post_import.title')" />

            <p class="max-w-2xl text-sm text-muted-foreground">
                {{ $t('post_import.description', { max: String(maxRows) }) }}
            </p>

            <div v-if="uploadError" class="text-sm text-destructive">
                {{ uploadError }}
            </div>

            <EmptyState
                v-if="!state"
                :icon="IconFileText"
                :title="$t('post_import.empty.title')"
                :description="$t('post_import.empty.description')"
            >
                <template #action>
                    <Button :loading="uploading" @click="pickFile">
                        <IconUpload class="size-4" />
                        {{ $t('post_import.upload') }}
                    </Button>
                </template>
            </EmptyState>

            <template v-else>
                <div
                    class="flex flex-wrap items-center gap-3 rounded-xl border border-foreground/15 bg-background p-4"
                >
                    <IconFileText class="size-5 text-muted-foreground" />
                    <span class="text-sm font-medium">{{
                        state.original_filename
                    }}</span>
                    <Badge variant="outline">{{
                        $t(`post_import.status.${state.status}`)
                    }}</Badge>

                    <span
                        v-if="status === 'preview_ready'"
                        class="text-sm text-muted-foreground"
                    >
                        {{
                            $t('post_import.summary', {
                                valid: String(state.valid_rows),
                                invalid: String(state.invalid_rows),
                            })
                        }}
                    </span>

                    <span
                        v-else-if="status === 'processing'"
                        class="text-sm text-muted-foreground"
                    >
                        {{
                            $t('post_import.progress', {
                                processed: String(state.processed_rows),
                                total: String(state.valid_rows),
                            })
                        }}
                    </span>

                    <div class="ms-auto flex gap-2">
                        <Button
                            v-if="status === 'preview_ready'"
                            :loading="processing"
                            @click="startProcessing"
                        >
                            {{
                                $t('post_import.process', {
                                    count: String(state.valid_rows),
                                })
                            }}
                        </Button>
                        <Button
                            v-if="status === 'completed'"
                            @click="goToDrafts"
                        >
                            {{ $t('post_import.view_drafts') }}
                        </Button>
                        <Button variant="outline" @click="reset">
                            {{ $t('post_import.new_import') }}
                        </Button>
                    </div>
                </div>

                <p
                    v-if="status === 'completed'"
                    class="text-sm text-muted-foreground"
                >
                    {{
                        $t('post_import.completed', {
                            count: String(state.created_count),
                        })
                    }}
                </p>

                <div v-if="rows.length" class="overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-16">#</TableHead>
                                <TableHead>{{
                                    $t('post_import.table.content')
                                }}</TableHead>
                                <TableHead class="w-32">{{
                                    $t('post_import.table.status')
                                }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in rows" :key="row.id">
                                <TableCell class="text-muted-foreground">{{
                                    row.row_number
                                }}</TableCell>
                                <TableCell class="max-w-md">
                                    <p class="line-clamp-2 text-sm">
                                        {{
                                            row.mapped_content ||
                                            $t('post_import.table.no_content')
                                        }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="rowVariant(row.status)">
                                        {{
                                            $t(
                                                `post_import.row_status.${row.status}`,
                                            )
                                        }}
                                    </Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </template>

            <input
                ref="fileInput"
                type="file"
                accept=".csv,text/csv"
                class="hidden"
                @change="onFileChange"
            />
        </div>
    </AppLayout>
</template>
