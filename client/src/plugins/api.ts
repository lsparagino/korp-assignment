import axios from 'axios'
import router from '@/router'
import { useAuthStore } from '@/stores/auth'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request Interceptor
api.interceptors.request.use(config => {
  const authStore = useAuthStore()
  if (authStore.token) {
    config.headers.Authorization = `Bearer ${authStore.token}`
  }
  return config
})

// Response Interceptor
api.interceptors.response.use(
  response => response,
  error => {
    const authStore = useAuthStore()
    if (error.response?.status === 401) {
      authStore.clearToken()
      router.push('/auth/login' as any)
    }
    return Promise.reject(error)
  },
)

export default api
