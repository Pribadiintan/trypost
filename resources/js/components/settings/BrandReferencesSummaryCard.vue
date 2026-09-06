<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { IconArrowRight, IconPhotoPlus } from '@tabler/icons-vue';
import { computed } from 'vue';

import { AspectRatio } from '@/components/ui/aspect-ratio';
import { Button } from '@/components/ui/button';
import { BRAND_REFERENCE_LIMIT } from '@/lib/brandReferences';
import { index as assetsIndex } from '@/routes/app/assets';
import type { MediaItem } from '@/types/media';

const props = defineProps<{
    references: MediaItem[];
}>();

const preview = computed(() => props.references.slice(0, 3));
</script>

<template>
    <section class="grid gap-4 border-t-2 border-foreground pt-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid gap-1">
                <h2 class="text-lg font-bold">
                    {{ $t('settings.brand.reference_photos_title') }}
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ $t('settings.brand.reference_photos_description') }}
                </p>
            </div>

            <Button type="button" size="sm" as-child>
                <Link :href="assetsIndex.url({ query: { tab: 'references' } })">
                    {{ $t('settings.brand.reference_photos_manage') }}
                    <IconArrowRight class="ml-2 size-4" />
                </Link>
            </Button>
        </div>

        <div
            v-if="references.length === 0"
            class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-foreground/30 p-8 text-center"
        >
            <div class="rounded-full bg-muted p-3">
                <IconPhotoPlus class="size-6 text-muted-foreground" />
            </div>
            <p class="mt-3 max-w-sm text-xs text-muted-foreground">
                {{ $t('settings.brand.reference_photos_empty') }}
            </p>
        </div>

        <div v-else class="flex items-center gap-4">
            <div class="grid grid-cols-3 gap-2">
                <div
                    v-for="photo in preview"
                    :key="photo.id"
                    class="w-20 overflow-hidden rounded-lg border bg-card sm:w-24"
                >
                    <AspectRatio :ratio="1">
                        <img
                            :src="photo.url"
                            :alt="
                                photo.meta?.label ||
                                photo.original_filename ||
                                'Reference photo'
                            "
                            class="size-full object-cover"
                        />
                    </AspectRatio>
                </div>
            </div>
            <p class="text-sm font-medium text-muted-foreground">
                {{
                    $t('settings.brand.reference_photos_count', {
                        count: String(references.length),
                        max: String(BRAND_REFERENCE_LIMIT),
                    })
                }}
            </p>
        </div>
    </section>
</template>
