import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/plugins/api'

export const useCompanyStore = defineStore('company', () => {
    const companies = ref<any[]>([])
    const currentCompany = ref<any>(null)
    const loading = ref(false)

    const hasCompanies = computed(() => companies.value.length > 0)

    async function fetchCompanies() {
        loading.value = true
        try {
            const response = await api.get('/companies')
            companies.value = response.data.data || response.data

            // Default to first company if none selected
            if (!currentCompany.value && companies.value.length > 0) {
                currentCompany.value = companies.value[0]
            }
        } catch (error) {
            console.error('Failed to fetch companies:', error)
        } finally {
            loading.value = false
        }
    }

    function setCompany(company: any) {
        currentCompany.value = company
    }

    return {
        companies,
        currentCompany,
        loading,
        hasCompanies,
        fetchCompanies,
        setCompany,
    }
})
