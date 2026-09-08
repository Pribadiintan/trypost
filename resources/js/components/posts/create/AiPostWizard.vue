<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { IconArrowLeft, IconCheck, IconSparkles } from '@tabler/icons-vue';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

import { start as startRoute } from '@/actions/App/Http/Controllers/App/PostCreateController';
import BrandReferencePicker from '@/components/posts/create/BrandReferencePicker.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { usePostCreation } from '@/composables/echo/usePostCreation';
import {
    getPlatformLabel,
    getPlatformLogo,
} from '@/composables/usePlatformLogo';
import { edit as editPost } from '@/routes/app/posts';
import {
    credits as creditsRoute,
    status as statusRoute,
} from '@/routes/app/posts/ai';
import type { MediaItem } from '@/types/media';

interface CatalogFormat {
    value: string;
    platform: string;
    label: string;
    accounts: Array<{
        id: string;
        label: string;
        username: string | null;
        platform: string;
    }>;
}

interface CatalogStyle {
    key: string;
    name: string;
    description: string;
    preview: string;
    needs_account: boolean;
    supported_formats: string[];
    applies_brand_visuals: boolean;
}

interface Props {
    catalog: {
        formats: CatalogFormat[];
        styles: CatalogStyle[];
        applies_brand_visuals_default: boolean;
        content_language: string | null;
        languages: Array<{ language_code: string; label: string }>;
        brand_reference_count: number;
    };
    date?: string | null;
    brandReferences?: MediaItem[];
    canManageBrandReferences?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    date: null,
    brandReferences: () => [],
    canManageBrandReferences: false,
});

const emit = defineEmits<{ cancel: [] }>();

const PROMPT_MIN = 3;
const PROMPT_MAX = 2000;

const prompt = ref('');
const format = ref<string | null>(null);
const accountId = ref<string | null>(null);
const style = ref('image_card');
const imageCount = ref(1);
const languageCode = ref<string | null>(props.catalog.content_language);

const localReferences = ref<MediaItem[]>([...props.brandReferences]);
const selectedReferenceIds = ref<string[]>(
    props.brandReferences.map((reference) => reference.id),
);

const submitting = ref(false);
const failed = ref<string | null>(null);
const detached = ref(false);
const progress = ref<{ done: number; total: number } | null>(null);
const phase = ref<string | null>(null);
const readyPostId = ref<string | null>(null);
const creationId = ref<string | null>(null);
const checkingStatus = ref(false);
const credits = ref<{
    allowed: boolean;
    remaining?: number;
    limit?: number;
    message?: string;
} | null>(null);
const checkingCredits = ref(false);

const STORAGE_KEY = 'trypost:wizard:state';

const saveState = () => {
    const state = {
        prompt: prompt.value,
        format: format.value,
        accountId: accountId.value,
        style: style.value,
        imageCount: imageCount.value,
        selectedReferenceIds: selectedReferenceIds.value,
        languageCode: languageCode.value,
    };
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
};

const restoreState = () => {
    const raw = sessionStorage.getItem(STORAGE_KEY);
    if (!raw) return;
    try {
        const state = JSON.parse(raw);
        if (state.prompt) prompt.value = state.prompt;
        if (state.format) format.value = state.format;
        if (state.accountId) accountId.value = state.accountId;
        if (state.style) style.value = state.style;
        if (typeof state.imageCount === 'number')
            imageCount.value = state.imageCount;
        if (Array.isArray(state.selectedReferenceIds)) {
            selectedReferenceIds.value = state.selectedReferenceIds.filter(
                (id: string) =>
                    localReferences.value.some((ref) => ref.id === id),
            );
        }
        if (state.languageCode) languageCode.value = state.languageCode;
    } catch {
        sessionStorage.removeItem(STORAGE_KEY);
    }
};

const clearState = () => sessionStorage.removeItem(STORAGE_KEY);

watch(
    [
        prompt,
        format,
        accountId,
        style,
        imageCount,
        selectedReferenceIds,
        languageCode,
    ],
    saveState,
    { deep: true },
);

restoreState();

/** Deduplicate formats while preserving all linked accounts. */
const formats = computed(() => {
    const byValue = new Map<string, CatalogFormat>();

    for (const entry of props.catalog.formats) {
        const existing = byValue.get(entry.value);

        if (existing) {
            existing.accounts.push(...entry.accounts);
            continue;
        }

        byValue.set(entry.value, {
            value: entry.value,
            platform: entry.platform,
            label: entry.label,
            accounts: [...entry.accounts],
        });
    }

    return [...byValue.values()];
});

const accountsForFormat = computed(
    () =>
        formats.value.find((entry) => entry.value === format.value)?.accounts ??
        [],
);

const languages = computed(() => props.catalog.languages);

const isCarousel = computed(() => format.value === 'instagram_carousel');

const selectFormat = (value: string): void => {
    format.value = value;

    if (value === 'instagram_carousel') {
        imageCount.value = 5;
    } else if (
        imageCount.value === 0 &&
        (value === 'instagram_story' || value === 'pinterest_pin')
    ) {
        imageCount.value = 1;
    }

    const accounts =
        formats.value.find((entry) => entry.value === value)?.accounts ?? [];
    accountId.value = accounts.length === 1 ? accounts[0].id : null;
};

const onReferenceAdded = (newItem: MediaItem) => {
    localReferences.value = [newItem, ...localReferences.value];
    if (!selectedReferenceIds.value.includes(newItem.id)) {
        selectedReferenceIds.value = [
            ...selectedReferenceIds.value,
            newItem.id,
        ];
    }
};

const promptLength = computed(() => [...prompt.value.trim()].length);

const canContinue = computed(() => {
    return (
        format.value !== null &&
        accountId.value !== null &&
        style.value !== null &&
        promptLength.value >= PROMPT_MIN &&
        promptLength.value <= PROMPT_MAX
    );
});

const { watchCreation } = usePostCreation({
    onReady: (postId: string) => {
        readyPostId.value = postId;
        clearState();
        router.visit(editPost(postId).url);
    },
    onProgress: (event) => {
        phase.value = event.phase ?? null;
        if (event.image_expected) {
            progress.value = {
                done: event.image_done ?? 0,
                total: event.image_expected,
            };
        }
    },
    onFailed: (message) => {
        failed.value = message ?? trans('posts.wizard.failed');
        submitting.value = false;
        clearState();
    },
    onDetached: () => {
        detached.value = true;
        submitting.value = false;
        clearState();
    },
});

const generate = (): void => {
    if (submitting.value || !canContinue.value) return;

    submitting.value = true;
    failed.value = null;
    detached.value = false;

    const hasReferences =
        imageCount.value > 0 && selectedReferenceIds.value.length > 0;

    router.post(
        startRoute.url(),
        {
            prompt: prompt.value.trim(),
            format: format.value,
            style: style.value,
            image_count: imageCount.value,
            social_account_id: accountId.value,
            date: props.date,
            apply_brand_visuals: true, // Brand colors are always applied
            use_brand_references: hasReferences,
            reference_media_ids: hasReferences
                ? selectedReferenceIds.value
                : [],
            language_code: languageCode.value,
        },
        {
            onSuccess: (page) => {
                const payload = (
                    page as unknown as {
                        props: { creation_id?: string; channel?: string };
                    }
                ).props;

                if (payload.creation_id) {
                    creationId.value = payload.creation_id;
                }

                if (payload.channel) {
                    void watchCreation(payload.channel);
                    return;
                }

                submitting.value = false;
            },
            onError: () => {
                failed.value = trans('posts.wizard.failed');
                submitting.value = false;
            },
        },
    );
};

const checkStatus = async (): Promise<void> => {
    if (!creationId.value || checkingStatus.value) return;

    checkingStatus.value = true;

    try {
        const response = await fetch(statusRoute.url(creationId.value), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            failed.value = trans('posts.wizard.status_check_failed');
            checkingStatus.value = false;
            return;
        }

        const data = (await response.json()) as {
            status?: string;
            post_id?: string | null;
            error?: string | null;
        };

        if (data.post_id) {
            clearState();
            router.visit(editPost(data.post_id).url);
            return;
        }

        if (data.error) {
            failed.value = data.error;
            detached.value = false;
            checkingStatus.value = false;
            clearState();
            return;
        }

        checkingStatus.value = false;
    } catch {
        failed.value = trans('posts.wizard.status_check_failed');
        checkingStatus.value = false;
    }
};

const checkCredits = async (): Promise<void> => {
    if (checkingCredits.value) return;

    checkingCredits.value = true;

    try {
        const response = await fetch(creditsRoute.url(), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            credits.value = { allowed: true };
            checkingCredits.value = false;
            return;
        }

        credits.value = (await response.json()) as {
            allowed: boolean;
            remaining?: number;
            limit?: number;
            message?: string;
        };
    } catch {
        credits.value = { allowed: true };
    }

    checkingCredits.value = false;
};

// Pre-flight credit check on mount.
void checkCredits();
</script>

<template>
    <div class="space-y-8">
        <!-- Header Back Action -->
        <div>
            <button
                type="button"
                class="group inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-foreground/70 transition-colors hover:text-foreground"
                @click="emit('cancel')"
            >
                <span
                    class="inline-flex size-7 items-center justify-center rounded-lg border-2 border-foreground bg-card shadow-2xs transition-transform group-hover:-translate-x-0.5"
                >
                    <IconArrowLeft
                        class="size-3.5 text-foreground"
                        stroke-width="2.5"
                    />
                </span>
                {{ $t('common.back') }}
            </button>
        </div>

        <!-- 1. Format Selection with Social Media Icons -->
        <div class="space-y-3">
            <Label class="text-sm font-bold">
                {{ $t('posts.wizard.format_label') }}
            </Label>
            <div class="grid gap-2.5 sm:grid-cols-2">
                <button
                    v-for="entry in formats"
                    :key="entry.value"
                    type="button"
                    class="group flex cursor-pointer items-center gap-3 rounded-xl border-2 border-foreground bg-card p-3.5 text-left text-sm shadow-2xs transition-all hover:-translate-y-0.5 hover:shadow-md"
                    :class="{
                        '!bg-violet-100 shadow-md ring-2 ring-foreground':
                            format === entry.value,
                    }"
                    @click="selectFormat(entry.value)"
                >
                    <span
                        class="inline-flex size-7 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-foreground bg-card shadow-2xs"
                    >
                        <img
                            :src="getPlatformLogo(entry.platform)"
                            :alt="getPlatformLabel(entry.platform)"
                            class="size-full object-cover"
                            loading="lazy"
                        />
                    </span>
                    <span class="flex-1 font-semibold text-foreground">
                        {{ entry.label }}
                    </span>
                    <IconCheck
                        v-if="format === entry.value"
                        class="size-4 shrink-0 text-foreground"
                        stroke-width="3"
                    />
                </button>
            </div>
        </div>

        <!-- 1b. Account Selection (when multiple accounts match format) -->
        <div v-if="accountsForFormat.length > 1" class="space-y-3">
            <Label class="text-sm font-bold">
                {{ $t('posts.wizard.account_label') }}
            </Label>
            <div class="grid gap-2 sm:grid-cols-2">
                <button
                    v-for="account in accountsForFormat"
                    :key="account.id"
                    type="button"
                    class="group flex cursor-pointer items-center gap-3 rounded-xl border-2 border-foreground bg-card p-3 text-left text-sm shadow-2xs transition-all hover:-translate-y-0.5 hover:shadow-md"
                    :class="{
                        '!bg-violet-100 shadow-md ring-2 ring-foreground':
                            accountId === account.id,
                    }"
                    @click="accountId = account.id"
                >
                    <span
                        class="inline-flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-foreground bg-card shadow-2xs"
                    >
                        <img
                            :src="getPlatformLogo(account.platform)"
                            :alt="account.platform"
                            class="size-full object-cover"
                            loading="lazy"
                        />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-xs leading-tight font-bold text-foreground"
                        >
                            {{ account.label }}
                        </p>
                        <p
                            v-if="account.username"
                            class="truncate text-xs font-medium text-foreground/60"
                        >
                            @{{ account.username }}
                        </p>
                    </div>
                    <IconCheck
                        v-if="accountId === account.id"
                        class="size-4 shrink-0 text-foreground"
                        stroke-width="3"
                    />
                </button>
            </div>
        </div>

        <!-- 2. Visual Style (Image Cards Preview) -->
        <div class="space-y-3">
            <Label class="text-sm font-bold">
                {{ $t('posts.wizard.style_label') }}
            </Label>
            <div class="grid gap-3 sm:grid-cols-3">
                <button
                    v-for="entry in catalog.styles"
                    :key="entry.key"
                    type="button"
                    class="group relative flex cursor-pointer flex-col overflow-hidden rounded-xl border-2 border-foreground bg-card text-left shadow-2xs transition-all hover:-translate-y-0.5 hover:shadow-md"
                    :class="{
                        '!bg-violet-100 shadow-md ring-2 ring-foreground':
                            style === entry.key,
                    }"
                    @click="style = entry.key"
                >
                    <div class="aspect-video w-full overflow-hidden bg-muted">
                        <img
                            :src="entry.preview"
                            :alt="entry.name"
                            class="size-full object-cover transition-transform group-hover:scale-102"
                            loading="lazy"
                        />
                    </div>
                    <div class="flex items-start gap-2 p-3">
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-bold text-foreground"
                            >
                                {{ entry.name }}
                            </p>
                            <p
                                v-if="entry.description"
                                class="mt-0.5 text-xs leading-snug text-foreground/60"
                            >
                                {{ entry.description }}
                            </p>
                        </div>
                        <IconCheck
                            v-if="style === entry.key"
                            class="mt-0.5 size-4 shrink-0 text-foreground"
                            stroke-width="3"
                        />
                    </div>
                </button>
            </div>
        </div>

        <!-- 3. Media / Image Count (Ergonomic Pill Buttons) -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <Label class="text-sm font-bold">
                    {{ $t('posts.wizard.images_label') }}
                </Label>
                <span class="text-xs font-semibold text-foreground/70">
                    {{
                        imageCount === 0
                            ? $t('posts.wizard.media_none')
                            : `${imageCount} ${$t('posts.wizard.media_images')}`
                    }}
                </span>
            </div>

            <!-- Carousel pills (2 to 10) -->
            <div v-if="isCarousel" class="flex flex-wrap gap-2">
                <Button
                    v-for="n in [2, 3, 4, 5, 6, 7, 8, 9, 10]"
                    :key="n"
                    type="button"
                    size="sm"
                    class="h-9 min-w-9 font-bold"
                    :variant="imageCount === n ? 'default' : 'outline'"
                    @click="imageCount = n"
                >
                    {{ n }}
                </Button>
            </div>

            <!-- Standard feed pills (None, 1 to 4) -->
            <div v-else class="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    size="sm"
                    class="h-9 font-semibold"
                    :variant="imageCount === 0 ? 'default' : 'outline'"
                    @click="imageCount = 0"
                >
                    {{ $t('posts.wizard.media_none') }}
                </Button>
                <Button
                    v-for="n in [1, 2, 3, 4]"
                    :key="n"
                    type="button"
                    size="sm"
                    class="h-9 min-w-9 font-bold"
                    :variant="imageCount === n ? 'default' : 'outline'"
                    @click="imageCount = n"
                >
                    {{ n }}
                </Button>
            </div>
        </div>

        <!-- 4. Language Variant Selection -->
        <div class="space-y-3">
            <div class="space-y-0.5">
                <Label class="text-sm font-bold">
                    {{ $t('posts.wizard.language_variant_label') }}
                </Label>
                <p class="text-xs text-foreground/60">
                    {{ $t('posts.wizard.language_variant_description') }}
                </p>
            </div>

            <!-- If multiple language variants exist -->
            <div v-if="languages.length > 1" class="grid gap-2 sm:grid-cols-2">
                <button
                    v-for="entry in languages"
                    :key="entry.language_code"
                    type="button"
                    class="flex cursor-pointer items-center justify-between rounded-xl border-2 border-foreground bg-card p-3 text-left text-sm shadow-2xs transition-all hover:-translate-y-0.5 hover:shadow-md"
                    :class="{
                        '!bg-violet-100 shadow-md ring-2 ring-foreground':
                            languageCode === entry.language_code,
                    }"
                    @click="languageCode = entry.language_code"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex size-6 items-center justify-center rounded-md border border-foreground/30 bg-muted text-[11px] font-bold text-foreground uppercase"
                        >
                            {{ entry.language_code }}
                        </span>
                        <span class="font-semibold text-foreground">
                            {{ entry.label }}
                        </span>
                    </div>
                    <IconCheck
                        v-if="languageCode === entry.language_code"
                        class="size-4 shrink-0 text-foreground"
                        stroke-width="3"
                    />
                </button>
            </div>

            <!-- Single default variant badge -->
            <div
                v-else-if="languages.length === 1"
                class="flex items-center justify-between rounded-xl border-2 border-foreground/20 bg-card p-3.5"
            >
                <div class="flex items-center gap-2.5">
                    <span
                        class="inline-flex size-7 items-center justify-center rounded-lg border border-foreground/30 bg-muted text-xs font-bold text-foreground uppercase"
                    >
                        {{ languages[0].language_code }}
                    </span>
                    <div>
                        <p class="text-sm font-bold text-foreground">
                            {{ languages[0].label }}
                        </p>
                        <p class="text-xs text-foreground/60">
                            {{ $t('posts.wizard.language_variant_default') }}
                        </p>
                    </div>
                </div>
                <span
                    class="rounded-md border border-foreground/20 bg-muted/60 px-2 py-1 text-[11px] font-semibold text-foreground/70"
                >
                    {{ $t('posts.wizard.language_variant_default') }}
                </span>
            </div>
        </div>

        <!-- 5. Brand References (shown when image count > 0) -->
        <div v-if="imageCount > 0" class="space-y-3">
            <Label class="text-sm font-bold">
                {{ $t('posts.wizard.brand_references_title') }}
            </Label>
            <BrandReferencePicker
                v-model:selected-ids="selectedReferenceIds"
                :references="localReferences"
                :can-manage="props.canManageBrandReferences"
                @reference-added="onReferenceAdded"
            />
        </div>

        <!-- 6. Prompt Input -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <Label for="wizard-prompt" class="text-sm font-bold">
                    {{ $t('posts.wizard.prompt_label') }}
                </Label>
                <span
                    class="text-xs tabular-nums"
                    :class="
                        promptLength > PROMPT_MAX
                            ? 'font-bold text-destructive'
                            : 'text-muted-foreground'
                    "
                >
                    {{ promptLength }}/{{ PROMPT_MAX }}
                </span>
            </div>
            <Textarea
                id="wizard-prompt"
                v-model="prompt"
                rows="4"
                :placeholder="$t('posts.wizard.prompt_placeholder')"
                class="resize-none"
            />
        </div>

        <!-- Error & Detached Status Alerts -->
        <p v-if="failed" class="text-sm font-medium text-destructive">
            {{ failed }}
        </p>

        <div
            v-if="detached"
            class="space-y-2 rounded-xl border border-foreground/20 bg-muted/40 p-4"
        >
            <p class="text-sm text-foreground/80">
                {{ $t('posts.wizard.detached') }}
            </p>
            <Button
                variant="outline"
                size="sm"
                :disabled="checkingStatus"
                @click="checkStatus"
            >
                {{ $t('posts.wizard.check_status') }}
            </Button>
        </div>

        <p
            v-if="credits && !credits.allowed"
            class="text-sm font-medium text-destructive"
        >
            {{ credits.message ?? $t('posts.wizard.credits_exhausted') }}
        </p>

        <!-- Generation Actions -->
        <div class="flex items-center justify-between gap-3 pt-2">
            <Button variant="ghost" @click="emit('cancel')">
                {{ $t('common.back') }}
            </Button>

            <Button
                size="lg"
                class="gap-2 font-bold"
                :disabled="
                    !canContinue ||
                    submitting ||
                    (credits !== null && !credits.allowed)
                "
                @click="generate"
            >
                <IconSparkles class="size-4" />
                {{ $t('posts.wizard.generate') }}
            </Button>
        </div>

        <!-- Realtime Progress Indicator -->
        <div
            v-if="submitting"
            class="flex items-center gap-2 rounded-xl border border-foreground/20 bg-card p-3.5 text-sm text-foreground/80 shadow-2xs"
        >
            <div
                class="size-4 animate-spin rounded-full border-2 border-foreground border-t-transparent"
            />
            <span class="font-medium">
                <template v-if="phase === 'pending_text'">
                    {{ $t('posts.wizard.generating_text') }}
                </template>
                <template v-else-if="phase === 'text_ready'">
                    {{ $t('posts.wizard.text_ready') }}
                </template>
                <template v-else-if="progress">
                    {{
                        $t('chat.post_generation.result_images_progress', {
                            done: String(progress.done),
                            total: String(progress.total),
                        })
                    }}
                </template>
                <template v-else>
                    {{ $t('posts.wizard.submitting') }}
                </template>
            </span>
        </div>
    </div>
</template>
