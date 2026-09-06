<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { IconEye, IconEyeOff } from '@tabler/icons-vue';
import { ref } from 'vue';

import PreviewTab from '@/components/posts/editor/PreviewTab.vue';
import { chatPreview } from '@/routes/app/posts';
import type { MediaItem } from '@/types/media';

interface PreviewSocialAccount {
    id: string;
    platform: string;
    display_name: string | null;
    username: string | null;
    display_label: string;
    handle_label: string;
    avatar_url: string | null;
}

interface PreviewPlatform {
    id: string;
    platform: string;
    platform_name: string | null;
    platform_avatar: string | null;
    content_type: string | null;
    enabled: boolean;
    social_account: PreviewSocialAccount | null;
}

interface ChatPostPreviewData {
    content: string;
    media: MediaItem[];
    platforms: PreviewPlatform[];
    platform_content_types: Record<string, string>;
    platform_meta: Record<string, Record<string, unknown>>;
    contents: Record<string, string>;
}

const props = defineProps<{
    postId: string;
}>();

const open = ref(false);
const loading = ref(false);
const failed = ref(false);
const data = ref<ChatPostPreviewData | null>(null);

const http = useHttp<Record<string, never>, ChatPostPreviewData>({});

const toggle = async (): Promise<void> => {
    open.value = !open.value;

    if (!open.value || data.value !== null || loading.value) {
        return;
    }

    loading.value = true;
    failed.value = false;

    try {
        data.value = await http.get(chatPreview.url({ post: props.postId }));
    } catch {
        failed.value = true;
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div data-testid="chat-post-preview">
        <button
            type="button"
            class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline"
            data-testid="chat-post-preview-toggle"
            @click="toggle"
        >
            <component :is="open ? IconEyeOff : IconEye" class="size-3.5" />
            {{
                open
                    ? $t('chat.post_generation.preview_hide')
                    : $t('chat.post_generation.preview_show')
            }}
        </button>

        <div v-if="open" class="mt-2">
            <div
                v-if="loading"
                class="space-y-2 rounded-xl border border-foreground/15 bg-background p-3"
                data-testid="chat-post-preview-loading"
            >
                <div class="h-4 w-2/3 animate-pulse rounded bg-accent" />
                <div
                    class="mx-auto h-64 w-full max-w-75 animate-pulse rounded-3xl bg-accent"
                />
            </div>

            <p
                v-else-if="failed || data === null"
                class="text-xs text-muted-foreground"
                data-testid="chat-post-preview-error"
            >
                {{ $t('chat.post_generation.preview_error') }}
            </p>

            <div
                v-else
                class="overflow-hidden rounded-xl border border-foreground/15 bg-background"
            >
                <PreviewTab
                    :platforms="data.platforms"
                    :content="data.content"
                    :media="data.media"
                    :platform-content-types="data.platform_content_types"
                    :platform-meta="data.platform_meta"
                    :platform-contents="data.contents"
                    show-disabled-badge
                />
            </div>
        </div>
    </div>
</template>
