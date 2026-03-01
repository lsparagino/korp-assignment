<script lang="ts" setup>
  import type { PaginationMeta } from '@/composables/useUrlPagination'
  import Pagination from '@/components/ui/Pagination.vue'

  interface Props {
    loading?: boolean
    meta?: PaginationMeta
    title?: string
    refreshing?: boolean
  }

  withDefaults(defineProps<Props>(), {
    loading: false,
    meta: undefined,
    title: undefined,
    refreshing: false,
  })

  defineEmits(['update:page', 'update:per-page', 'refresh'])
</script>

<template>
  <v-card border flat :loading="loading" rounded="lg">
    <div
      v-if="title || $slots.toolbar"
      class="d-flex align-center justify-space-between px-4 py-2 border-b"
    >
      <div class="d-flex align-center ga-2">
        <span class="text-caption font-weight-bold text-grey-darken-2">
          {{ title }}
        </span>
        <span
          v-if="meta?.total != null"
          class="text-caption text-grey"
        >
          ({{ meta.total }})
        </span>
      </div>

      <div class="d-flex align-center ga-1">
        <slot name="toolbar" />
        <v-btn
          :aria-label="$t('common.refreshData')"
          color="grey-darken-1"
          data-testid="refresh-btn"
          density="comfortable"
          icon="mdi-refresh"
          :loading="refreshing"
          size="small"
          variant="text"
          @click="$emit('refresh')"
        />
      </div>
    </div>

    <div class="overflow-x-auto">
      <v-table data-testid="data-table" density="comfortable">
        <thead class="bg-grey-lighten-4">
          <tr>
            <slot name="columns" />
          </tr>
        </thead>
        <tbody>
          <slot name="body" />
        </tbody>
      </v-table>
    </div>

    <div v-if="meta" class="border-t">
      <Pagination
        :meta="meta"
        @update:page="$emit('update:page', $event)"
        @update:per-page="$emit('update:per-page', $event)"
      />
    </div>

    <slot name="footer" />
  </v-card>
</template>

<style scoped>
:deep(thead th) {
    color: rgb(var(--v-theme-grey-darken-1));
    text-transform: uppercase;
    font-size: 0.75rem;
    font-weight: 700;
    text-align: left;
}
</style>
