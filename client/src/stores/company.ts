import type { Company } from '@/api/companies'
import { useQueryCache } from '@pinia/colada'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { i18n } from '@/plugins/i18n'
import { companiesQuery, COMPANY_QUERY_KEYS } from '@/queries/companies'

export const useCompanyStore = defineStore('company', () => {
  const currentCompany = ref<Company | null>(null)
  const companiesList = ref<Company[]>([])
  const queryCache = useQueryCache()

  const companies = computed<Company[]>(() => companiesList.value)
  const hasCompanies = computed(() => companies.value.length > 0)
  const companyLabel = computed(() => currentCompany.value?.name ?? i18n.global.t('company.selectCompany'))

  function setCurrentCompany(company: Company) {
    currentCompany.value = company
  }

  // Imperative fetch for router guards — leverages query cache
  async function fetchCompanies() {
    try {
      const entry = queryCache.ensure(companiesQuery)
      await queryCache.fetch(entry)
      const data = entry.state.value.data
      if (data) {
        companiesList.value = data
        // Auto-select first company if none selected yet
        if (!currentCompany.value && data.length > 0) {
          currentCompany.value = data[0] ?? null
        }
      }
    } catch {
      // Handled gracefully — keep existing data
    }
  }

  async function invalidateQueries() {
    await queryCache.invalidateQueries({ key: COMPANY_QUERY_KEYS.root })
  }

  return {
    companies,
    currentCompany,
    hasCompanies,
    companyLabel,
    fetchCompanies,
    setCurrentCompany,
    invalidateQueries,
  }
})
