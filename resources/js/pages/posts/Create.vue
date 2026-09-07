<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { IconPencil, IconSparkles } from '@tabler/icons-vue';
import { computed, ref } from 'vue';

import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { store as storePost } from '@/routes/app/posts';

interface Props {
    date?: string | null;
    brandReferenceCount?: number;
}

const props = withDefaults(defineProps<Props>(), {
    date: null,
    brandReferenceCount: 0,
});

const submitting = ref(false);

const startFromScratch = (): void => {
    if (submitting.value) return;

    submitting.value = true;

    router.post(
        storePost.url(),
        props.date ? { date: props.date } : {},
        { onFinish: () => (submitting.value = false) },
    );
};

const hasConnectedAccounts = computed(() => true);
</script>

<template>
    <Head :title="$t('posts.wizard.title')" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col p-4">
            <div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
                <PageHeader
                    :title="$t('posts.wizard.title')"
                    :description="$t('posts.wizard.description')"
                />

                <div class="grid gap-4 sm:grid-cols-2">
                    <button
                        type="button"
                        class="group flex flex-col items-start gap-4 rounded-2xl border-2 border-foreground bg-card p-5 text-left shadow-2xs transition-all hover:-translate-y-0.5 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="submitting"
                        @click="startFromScratch"
                    >
                        <div
                            class="inline-flex size-12 -rotate-2 items-center justify-center rounded-2xl border-2 border-foreground bg-violet-200 shadow-2xs transition-transform group-hover:rotate-0"
                        >
                            <IconPencil
                                class="size-6 text-foreground"
                                stroke-width="2"
                            />
                        </div>
                        <div class="space-y-1">
                            <p class="text-base font-bold text-foreground">
                                {{ $t('posts.wizard.scratch_title') }}
                            </p>
                            <p
                                class="text-xs leading-relaxed text-foreground/70"
                            >
                                {{ $t('posts.wizard.scratch_description') }}
                            </p>
                        </div>
                    </button>

                    <button
                        type="button"
                        class="group flex flex-col items-start gap-4 rounded-2xl border-2 border-foreground bg-card p-5 text-left shadow-2xs transition-all hover:-translate-y-0.5 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                        disabled
                    >
                        <div
                            class="inline-flex size-12 rotate-1 items-center justify-center rounded-2xl border-2 border-foreground bg-amber-200 shadow-2xs transition-transform group-hover:rotate-0"
                        >
                            <IconSparkles
                                class="size-6 text-foreground"
                                stroke-width="2"
                            />
                        </div>
                        <div class="space-y-1">
                            <p class="text-base font-bold text-foreground">
                                {{ $t('posts.wizard.ai_title') }}
                            </p>
                            <p
                                class="text-xs leading-relaxed text-foreground/70"
                            >
                                {{ $t('posts.wizard.ai_description') }}
                            </p>
                        </div>
                    </button>
                </div>

                <p
                    v-if="!hasConnectedAccounts"
                    class="text-sm text-muted-foreground"
                >
                    {{ $t('posts.wizard.connect_first') }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>
