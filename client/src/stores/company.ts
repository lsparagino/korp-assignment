import type { Company } from '@/api/companies'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { fetchCompanies as apiFetchCompanies } from '@/api/companies'
import { i18n } from '@/plugins/i18n'

export const useCompanyStore = defineStore('company', () => {
  const companies = ref<Company[]>([])
  const currentCompany = ref<Company | null>(null)

  const hasCompanies = computed(() => companies.value.length > 0)
  const companyLabel = computed(() => currentCompany.value?.name ?? i18n.global.t('company.selectCompany'))

  async function fetchCompanies () {
    try {
      const response = await apiFetchCompanies()
      companies.value = response.data.data

      if (companies.value.length > 0 && !currentCompany.value) {
        currentCompany.value = companies.value[0] ?? null
      }
    } catch {
      companies.value = []
    }
  }

  function setCurrentCompany (company: Company) {
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
