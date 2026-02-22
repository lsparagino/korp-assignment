<script lang="ts" setup>
  import type { PaginationMeta } from '@/composables/useUrlPagination'
  import Pagination from '@/components/ui/Pagination.vue'

  interface Props {
    loading?: boolean
    meta?: PaginationMeta
  }

  withDefaults(defineProps<Props>(), {
    loading: false,
    meta: undefined,
  })

  defineEmits(['update:page', 'update:per-page'])
</script>

<template>
  <v-card border flat :loading="loading" rounded="lg">
    <div class="overflow-x-auto">
      <v-table density="comfortable">
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
