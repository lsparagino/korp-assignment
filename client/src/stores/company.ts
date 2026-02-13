import type { Company } from '@/types'
import { defineStore } from 'pinia'
import api from '@/plugins/api'

export const useCompanyStore = defineStore('company', {
  state: () => ({
    companies: [] as Company[],
    currentCompany: null as Company | null,
  }),
  getters: {
    hasCompanies: state => state.companies.length > 0,
    companyLabel: state => state.currentCompany?.name ?? 'Select company',
  },
  actions: {
    async fetchCompanies () {
      try {
        const response = await api.get('/companies')
        this.companies = response.data.data

        if (this.companies.length > 0 && !this.currentCompany) {
          this.currentCompany = this.companies[0] ?? null
        }
      } catch {
        this.companies = []
      }
    },
    setCurrentCompany (company: Company) {
      this.currentCompany = company
    },
  },
})
