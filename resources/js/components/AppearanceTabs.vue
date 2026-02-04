<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', icon: 'mdi-white-balance-sunny', label: 'Light' },
    { value: 'dark', icon: 'mdi-moon-waning-crescent', label: 'Dark' },
    { value: 'system', icon: 'mdi-monitor', label: 'System' },
] as const;
</script>

<template>
    <v-btn-toggle
        v-model="appearance"
        mandatory
        variant="tonal"
        color="primary"
        density="comfortable"
        rounded="lg"
        @update:model-value="(val) => updateAppearance(val as 'light' | 'dark' | 'system')"
    >
        <v-btn
            v-for="{ value, icon, label } in tabs"
            :key="value"
            :value="value"
            class="text-none"
        >
            <v-icon start :icon="icon"></v-icon>
            {{ label }}
        </v-btn>
    </v-btn-toggle>
</template>
