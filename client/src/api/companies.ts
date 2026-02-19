import { api } from '@/plugins/api'

export function fetchCompanies() {
    return api.get('/companies')
}
