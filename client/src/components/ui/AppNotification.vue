<script lang="ts" setup>
  import { useAppNotification } from '@/composables/useAppNotification'

  const { notifications, dismiss } = useAppNotification()
</script>

<template>
  <div class="app-notifications">
    <v-snackbar
      v-for="n in notifications"
      :key="n.id"
      :color="n.color"
      data-testid="app-notification"
      location="bottom right"
      :model-value="true"
      :timeout="n.timeout"
      variant="flat"
      @update:model-value="dismiss(n.id)"
    >
      {{ n.message }}
      <template #actions>
        <v-btn
          density="comfortable"
          icon="mdi-close"
          size="small"
          variant="text"
          @click="dismiss(n.id)"
        />
      </template>
    </v-snackbar>
  </div>
</template>
