<script setup lang="ts">
import VuetifyLayout from '@/layouts/VuetifyLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

</script>

<template>
    <VuetifyLayout :breadcrumbs="breadcrumbs">
        <slot />
    </VuetifyLayout>
</template>
