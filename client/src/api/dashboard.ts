import { api } from '@/plugins/api'

export function fetchDashboard () {
  return api.get('/dashboard')
}
