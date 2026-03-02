import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

// Mock vue-router before importing anything that uses it
vi.mock('vue-router', () => ({
  createRouter: vi.fn(() => ({
    push: vi.fn(),
    replace: vi.fn(),
    isReady: vi.fn(() => Promise.resolve()),
    beforeEach: vi.fn(),
    afterEach: vi.fn(),
    onError: vi.fn(),
    install: vi.fn(),
    currentRoute: { value: { query: {}, params: {}, path: '/' } },
  })),
  createWebHistory: vi.fn(),
  useRoute: vi.fn(() => ({ query: {}, params: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn(), replace: vi.fn() })),
}))

const mockRouterPush = vi.fn()
vi.mock('@/router', () => ({
  router: {
    push: mockRouterPush,
    replace: vi.fn(),
    isReady: vi.fn(() => Promise.resolve()),
    beforeEach: vi.fn(),
    afterEach: vi.fn(),
    onError: vi.fn(),
    install: vi.fn(),
    currentRoute: { value: { query: {}, params: {}, path: '/' } },
  },
}))

describe('plugins/api — Axios interceptors', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('attaches Authorization header when token exists', async () => {
    const { useAuthStore } = await import('@/stores/auth')
    const authStore = useAuthStore()
    authStore.token = 'test-jwt-token'

    const { api } = await import('./api')

    // Simulate a request interceptor by running config through it
    const interceptor = (api.interceptors.request as any).handlers[0]
    const config = { headers: {} as Record<string, string>, params: {}, method: 'get' }
    const result = interceptor.fulfilled(config)

    expect(result.headers.Authorization).toBe('Bearer test-jwt-token')
  })

  it('does not attach Authorization header when no token', async () => {
    const { useAuthStore } = await import('@/stores/auth')
    const authStore = useAuthStore()
    authStore.token = ''

    const { api } = await import('./api')

    const interceptor = (api.interceptors.request as any).handlers[0]
    const config = { headers: {} as Record<string, string>, params: {}, method: 'get' }
    const result = interceptor.fulfilled(config)

    expect(result.headers.Authorization).toBeUndefined()
  })

  it('appends company_id param when currentCompany is set', async () => {
    const { useCompanyStore } = await import('@/stores/company')
    const companyStore = useCompanyStore()
    companyStore.currentCompany = { id: 42, name: 'Test Corp' } as any

    const { api } = await import('./api')

    const interceptor = (api.interceptors.request as any).handlers[0]
    const config = { headers: {} as Record<string, string>, params: {}, method: 'get' }
    const result = interceptor.fulfilled(config)

    expect(result.params.company_id).toBe(42)
  })

  it('adds Idempotency-Key header for POST requests', async () => {
    const { api } = await import('./api')

    const interceptor = (api.interceptors.request as any).handlers[0]
    const config = {
      headers: {} as Record<string, string>,
      params: {},
      method: 'post',
    }
    const result = interceptor.fulfilled(config)

    expect(result.headers['Idempotency-Key']).toBeDefined()
    expect(typeof result.headers['Idempotency-Key']).toBe('string')
  })

  it('does not add Idempotency-Key header for GET requests', async () => {
    const { api } = await import('./api')

    const interceptor = (api.interceptors.request as any).handlers[0]
    const config = { headers: {} as Record<string, string>, params: {}, method: 'get' }
    const result = interceptor.fulfilled(config)

    expect(result.headers['Idempotency-Key']).toBeUndefined()
  })

  it('clears token and redirects to login on 401 response', async () => {
    const { useAuthStore } = await import('@/stores/auth')
    const authStore = useAuthStore()
    authStore.token = 'existing-token'

    const { api } = await import('./api')

    const responseInterceptor = (api.interceptors.response as any).handlers[0]
    const error = { response: { status: 401 } }

    await expect(responseInterceptor.rejected(error)).rejects.toEqual(error)
    expect(authStore.token).toBeNull()
    expect(mockRouterPush).toHaveBeenCalledWith('/auth/login')
  })

  it('does not clear token on non-401 errors', async () => {
    const { useAuthStore } = await import('@/stores/auth')
    const authStore = useAuthStore()
    authStore.token = 'existing-token'

    const { api } = await import('./api')

    const responseInterceptor = (api.interceptors.response as any).handlers[0]
    const error = { response: { status: 500 } }

    await expect(responseInterceptor.rejected(error)).rejects.toEqual(error)
    expect(authStore.token).toBe('existing-token')
    expect(mockRouterPush).not.toHaveBeenCalledWith('/auth/login')
  })
})
