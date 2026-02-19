import type { Company } from '@/types'
import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { api } from '@/plugins/api'

export const useCompanyStore = defineStore('company', () => {
  const companies = ref<Company[]>([])
  const currentCompany = ref<Company | null>(null)

  const hasCompanies = computed(() => companies.value.length > 0)
  const companyLabel = computed(() => currentCompany.value?.name ?? 'Select company')

  async function fetchCompanies() {
    try {
      const response = await api.get('/companies')
      companies.value = response.data.data

      if (companies.value.length > 0 && !currentCompany.value) {
        currentCompany.value = companies.value[0] ?? null
      }
    } catch {
      companies.value = []
    }
  }

  function setCurrentCompany(company: Company) {
    currentCompany.value = company
  }

  return {
    companies,
    currentCompany,
    hasCompanies,
    companyLabel,
    fetchCompanies,
    setCurrentCompany,
  }
})
