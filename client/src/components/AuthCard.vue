<script lang="ts" setup>
  import type { VAlert } from 'vuetify/components'

  defineProps<{
    status?: string
    alertType?: VAlert['type']
  }>()
</script>

<template>
  <v-card border class="pa-6 pa-sm-10" flat rounded="xl">
    <v-alert
      v-if="status"
      class="mb-6"
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
