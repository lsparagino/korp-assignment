<script lang="ts" setup>
  import { useCompanyStore } from '@/stores/company'

  defineProps<{
    title: string
  }>()

  const companyStore = useCompanyStore()
</script>

<template>
  <div
    class="d-flex flex-column flex-sm-row align-start align-sm-center justify-space-between ga-4 mb-8"
  >
    <h1 class="text-h5 font-weight-bold text-grey-darken-2">
      {{ title }}
      <span
        v-if="companyStore.currentCompany"
        class="text-grey-darken-1"
      >- {{ companyStore.currentCompany.name }}</span>
    </h1>
    <slot />
  </div>
</template>
