<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

import { start as startRoute } from '@/actions/App/Http/Controllers/App/PostCreateController';
import BrandReferencePicker from '@/components/posts/create/BrandReferencePicker.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { usePostCreation } from '@/composables/echo/usePostCreation';
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
    accounts: Array<{ id: string; label: string; username: string | null }>;
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

const MAX_IMAGES = 10;
const PROMPT_MIN = 3;

const prompt = ref('');
const format = ref<string | null>(null);
const accountId = ref<string | null>(null);
const style = ref('image_card');
const imageCount = ref(1);
const useBrandColors = ref(props.catalog.applies_brand_visuals_default);
const useBrandReferences = ref(true);
const selectedReferenceIds = ref<string[]>(
    props.brandReferences.map((reference) => reference.id),
);
const languageCode = ref<string | null>(props.catalog.content_language);

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
        useBrandColors: useBrandColors.value,
        useBrandReferences: useBrandReferences.value,
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
        if (typeof state.useBrandColors === 'boolean')
            useBrandColors.value = state.useBrandColors;
        if (typeof state.useBrandReferences === 'boolean')
            useBrandReferences.value = state.useBrandReferences;
        if (Array.isArray(state.selectedReferenceIds))
            selectedReferenceIds.value = state.selectedReferenceIds.filter(
                (id: string) =>
                    props.brandReferences.some((ref) => ref.id === id),
            );
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
        useBrandColors,
        useBrandReferences,
        selectedReferenceIds,
        languageCode,
    ],
    saveState,
    { deep: true },
);

restoreState();

/** One card per format, with every platform's accounts merged into it. */
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

const showReferences = computed(
    () => props.brandReferences.length > 0 && imageCount.value > 0,
);

const showLanguage = computed(() => languages.value.length > 1);

const canContinue = computed(() => {
    return (
        format.value !== null &&
        accountId.value !== null &&
        style.value !== null &&
        prompt.value.trim().length >= PROMPT_MIN
    );
});

const selectFormat = (value: string): void => {
    format.value = value;

    const accounts =
        formats.value.find((entry) => entry.value === value)?.accounts ?? [];
    accountId.value = accounts.length === 1 ? accounts[0].id : null;
};

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
    if (submitting.value) return;

    submitting.value = true;
    failed.value = null;
    detached.value = false;

    router.post(
        startRoute.url(),
        {
            prompt: prompt.value.trim(),
            format: format.value,
            style: style.value,
            image_count: imageCount.value,
            social_account_id: accountId.value,
            date: props.date,
            apply_brand_visuals: useBrandColors.value,
            use_brand_references:
                useBrandReferences.value && showReferences.value,
            reference_media_ids:
                useBrandReferences.value && showReferences.value
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

        // Still in progress — keep the detached message but allow another check.
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
    <div class="space-y-6">
        <div class="space-y-2">
            <Label class="text-sm font-bold">{{
                $t('posts.wizard.format_label')
            }}</Label>
            <div class="grid gap-2 sm:grid-cols-2">
                <button
                    v-for="entry in formats"
                    :key="entry.value"
                    type="button"
                    class="rounded-xl border-2 bg-card p-3 text-left text-sm transition-colors"
                    :class="
                        format === entry.value
                            ? 'border-foreground'
                            : 'border-foreground/20 hover:border-foreground/50'
                    "
                    @click="selectFormat(entry.value)"
                >
                    {{ entry.label }}
                </button>
            </div>
        </div>

        <div v-if="accountsForFormat.length > 1" class="space-y-2">
            <Label class="text-sm font-bold">{{
                $t('posts.wizard.account_label')
            }}</Label>
            <div class="grid gap-2">
                <button
                    v-for="account in accountsForFormat"
                    :key="account.id"
                    type="button"
                    class="rounded-xl border-2 bg-card p-3 text-left text-sm transition-colors"
                    :class="
                        accountId === account.id
                            ? 'border-foreground'
                            : 'border-foreground/20 hover:border-foreground/50'
                    "
                    @click="accountId = account.id"
                >
                    {{ account.label }}
                    <span
                        v-if="account.username"
                        class="text-xs text-foreground/60"
                        >@{{ account.username }}</span
                    >
                </button>
            </div>
        </div>

        <div class="space-y-2">
            <Label class="text-sm font-bold">{{
                $t('posts.wizard.style_label')
            }}</Label>
            <div class="grid gap-2 sm:grid-cols-2">
                <button
                    v-for="entry in catalog.styles"
                    :key="entry.key"
                    type="button"
                    class="rounded-xl border-2 bg-card p-3 text-left transition-colors"
                    :class="
                        style === entry.key
                            ? 'border-foreground'
                            : 'border-foreground/20 hover:border-foreground/50'
                    "
                    @click="style = entry.key"
                >
                    <p class="text-sm font-semibold">{{ entry.name }}</p>
                    <p class="text-xs text-foreground/60">
                        {{ entry.description }}
                    </p>
                </button>
            </div>
        </div>

        <div class="space-y-2">
            <Label class="text-sm font-bold">{{
                $t('posts.wizard.images_label')
            }}</Label>
            <input
                v-model.number="imageCount"
                type="range"
                min="0"
                :max="MAX_IMAGES"
                class="w-full"
            />
            <p class="text-xs text-foreground/60">{{ imageCount }}</p>
        </div>

        <div
            class="flex items-center justify-between rounded-xl border-2 border-foreground/20 bg-card p-3"
        >
            <Label>{{ $t('posts.wizard.brand_colors_label') }}</Label>
            <Switch v-model:checked="useBrandColors" />
        </div>

        <div v-if="showReferences" class="space-y-3">
            <div
                class="flex items-center justify-between rounded-xl border-2 border-foreground/20 bg-card p-3"
            >
                <Label>{{ $t('posts.wizard.brand_references_label') }}</Label>
                <Switch v-model:checked="useBrandReferences" />
            </div>
            <BrandReferencePicker
                v-if="useBrandReferences"
                v-model:selected-ids="selectedReferenceIds"
                :references="brandReferences"
            />
        </div>

        <div v-if="showLanguage" class="space-y-2">
            <Label class="text-sm font-bold">{{
                $t('posts.wizard.language_label')
            }}</Label>
            <div class="grid gap-2 sm:grid-cols-2">
                <button
                    v-for="entry in languages"
                    :key="entry.language_code"
                    type="button"
                    class="rounded-xl border-2 bg-card p-3 text-left text-sm transition-colors"
                    :class="
                        languageCode === entry.language_code
                            ? 'border-foreground'
                            : 'border-foreground/20 hover:border-foreground/50'
                    "
                    @click="languageCode = entry.language_code"
                >
                    {{ entry.label }}
                </button>
            </div>
        </div>

        <div class="space-y-2">
            <Label class="text-sm font-bold">{{
                $t('posts.wizard.prompt_label')
            }}</Label>
            <Textarea
                v-model="prompt"
                rows="5"
                :placeholder="$t('posts.wizard.prompt_placeholder')"
            />
        </div>

        <p v-if="failed" class="text-sm text-destructive">{{ failed }}</p>
        <div v-if="detached" class="space-y-2">
            <p class="text-sm text-muted-foreground">
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

        <p v-if="credits && !credits.allowed" class="text-sm text-destructive">
            {{ credits.message ?? $t('posts.wizard.credits_exhausted') }}
        </p>

        <div class="flex items-center justify-between gap-3">
            <Button variant="ghost" @click="emit('cancel')">
                {{ $t('common.back') }}
            </Button>

            <Button
                :disabled="
                    !canContinue ||
                    submitting ||
                    (credits !== null && !credits.allowed)
                "
                @click="generate"
            >
                {{ $t('posts.wizard.generate') }}
            </Button>
        </div>

        <p v-if="submitting" class="text-sm text-muted-foreground">
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
        </p>
    </div>
</template>
