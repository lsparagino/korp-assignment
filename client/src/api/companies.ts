import { api } from '@/plugins/api'

export interface Company {
    id: number
    name: string
}

export function fetchCompanies() {
    return api.get('/companies')
}
