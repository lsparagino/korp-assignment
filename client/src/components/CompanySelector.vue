<script lang="ts" setup>
  import { useCompanyStore } from '@/stores/company'

  defineProps<{
    block?: boolean
  }>()

  const companyStore = useCompanyStore()
</script>

<template>
  <v-menu v-if="companyStore.hasCompanies" offset-y>
    <template #activator="{ props }">
      <v-btn
        :block="block"
        class="text-none font-weight-bold"
        :color="block ? 'primary' : undefined"
        :variant="block ? 'tonal' : 'text'"
        v-bind="props"
      >
        {{ companyStore.companyLabel }}
        <v-icon end size="small">mdi-chevron-down</v-icon>
      </v-btn>
    </template>
    <v-list density="compact" nav>
      <v-list-item
        v-for="company in companyStore.companies"
        :key="company.id"
        :active="companyStore.currentCompany?.id === company.id"
        color="primary"
        @click="companyStore.setCurrentCompany(company)"
      >
        <v-list-item-title>{{ company.name }}</v-list-item-title>
      </v-list-item>
    </v-list>
  </v-menu>
</template>
