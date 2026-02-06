<script lang="ts" setup>
  import { computed } from 'vue'

  const props = defineProps<{
    meta: {
      current_page: number
      last_page: number
      per_page: number
      total: number
      from: number | null
      to: number | null
    }
    perPageOptions?: number[]
  }>()

  const emit = defineEmits(['update:page', 'update:perPage'])

  const page = computed({
    get: () => props.meta.current_page,
    set: value => emit('update:page', value),
  })

  const perPageModel = computed({
    get: () => props.meta.per_page,
    set: value => emit('update:perPage', value),
  })

  const itemsPerPageOptions = props.perPageOptions || [
    5, 10, 25, 50, 100, 250, 500,
  ]
</script>

<template>
  <div
    class="d-flex flex-column flex-sm-row align-center justify-space-between py-4 ga-4 px-4"
  >
    <div class="d-flex align-center text-caption text-grey-darken-1">
      Showing {{ meta.from || 0 }} to {{ meta.to || 0 }} of
      {{ meta.total }} entries
    </div>

    <div class="d-flex flex-column flex-sm-row align-center ga-4 w-100 w-sm-auto justify-end">
      <div class="d-flex align-center order-1 order-sm-0">
        <span class="text-caption text-grey-darken-1 me-2 text-no-wrap">Per page:</span>
        <v-select
          v-model="perPageModel"
          class="page-size-selector"
          density="compact"
          hide-details
          :items="itemsPerPageOptions"
          variant="outlined"
        />
      </div>

      <v-pagination
        v-model="page"
        active-color="primary"
        class="order-0 order-sm-1"
        density="compact"
        :length="meta.last_page"
        :total-visible="5"
      />
    </div>
  </div>
</template>

<style scoped>
.page-size-selector {
    min-width: 6rem;
}
</style>
