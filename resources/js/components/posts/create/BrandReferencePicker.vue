<script setup lang="ts">
import { IconCheck } from '@tabler/icons-vue';
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import type { MediaItem } from '@/types/media';

const props = defineProps<{
    references: MediaItem[];
}>();

const selectedIds = defineModel<string[]>('selectedIds', { default: () => [] });

const allSelected = computed(
    () =>
        props.references.length > 0 &&
        selectedIds.value.length === props.references.length,
);

const isSelected = (id: string) => selectedIds.value.includes(id);

const toggle = (id: string) => {
    selectedIds.value = isSelected(id)
        ? selectedIds.value.filter((selected) => selected !== id)
        : [...selectedIds.value, id];
};

const selectAll = () => {
    selectedIds.value = props.references.map((reference) => reference.id);
};

const clear = () => {
    selectedIds.value = [];
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-end gap-2">
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :disabled="allSelected"
                @click="selectAll"
            >
                {{ $t('posts.wizard.brand_references_select_all') }}
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :disabled="selectedIds.length === 0"
                @click="clear"
            >
                {{ $t('posts.wizard.brand_references_clear') }}
            </Button>
        </div>
        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
            <button
                v-for="reference in references"
                :key="reference.id"
                type="button"
                class="group relative cursor-pointer overflow-hidden rounded-xl border-2 border-foreground bg-card text-left shadow-2xs transition-all hover:-translate-y-0.5 hover:shadow-md"
                :class="{
                    '!bg-violet-100 shadow-md': isSelected(reference.id),
                }"
                @click="toggle(reference.id)"
            >
                <div class="aspect-square">
                    <img
                        :src="reference.url"
                        :alt="
                            reference.meta?.label || reference.original_filename
                        "
                        class="size-full object-cover"
                        loading="lazy"
                    />
                </div>
                <div
                    v-if="isSelected(reference.id)"
                    class="absolute top-1.5 right-1.5 inline-flex size-6 items-center justify-center rounded-full border-2 border-foreground bg-primary text-primary-foreground shadow-2xs"
                >
                    <IconCheck class="size-3.5" stroke-width="3" />
                </div>
                <p
                    v-if="reference.meta?.label"
                    class="truncate px-1.5 py-1 text-[11px] font-semibold text-foreground"
                >
                    {{ reference.meta.label }}
                </p>
            </button>
        </div>
    </div>
</template>
