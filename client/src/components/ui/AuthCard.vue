<script lang="ts" setup>
  import type { VAlert } from 'vuetify/components'

  defineProps<{
    status?: string
    error?: string
    alertType?: VAlert['type']
  }>()
</script>

<template>
  <v-card border class="pa-6 pa-sm-10" flat rounded="xl">
    <v-alert
      v-if="error"
      class="mb-6"
      data-testid="error-alert"
      density="compact"
      type="error"
      variant="tonal"
    >
      {{ error }}
    </v-alert>

    <v-alert
      v-if="status"
      class="mb-6"
      data-testid="status-alert"
      density="compact"
      :type="alertType || 'success'"
      variant="tonal"
    >
      {{ status }}
    </v-alert>

    <slot />

    <div v-if="$slots.footer" class="mt-6 text-center">
      <slot name="footer" />
    </div>
  </v-card>
</template>
