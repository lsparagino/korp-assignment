/**
 * Global vue-router mock for all test files.
 *
 * Registered as a vitest setupFile so the mock applies to ALL test files.
 *
 * Per https://test-utils.vuejs.org/guide/advanced/vue-router
 *
 * We mock @/router (the project's router module) because it contains a
 * top-level `await router.isReady()` that never resolves in jsdom.
 * vue-router itself is mocked to stub useRoute/useRouter for Composition API.
 */
import { vi } from 'vitest'

vi.mock('vue-router', () => ({
  createRouter: vi.fn(() => ({
    push: vi.fn(),
    replace: vi.fn(),
    go: vi.fn(),
    back: vi.fn(),
    forward: vi.fn(),
    isReady: vi.fn(() => Promise.resolve()),
    beforeEach: vi.fn(),
    afterEach: vi.fn(),
    onError: vi.fn(),
    install: vi.fn(),
    currentRoute: { value: { query: {}, params: {}, path: '/' } },
  })),
  createMemoryHistory: vi.fn(),
  createWebHistory: vi.fn(),
  useRoute: vi.fn(() => ({ query: {}, params: {}, path: '/' })),
  useRouter: vi.fn(() => ({ push: vi.fn(), replace: vi.fn() })),
  RouterLink: { template: '<a><slot /></a>' },
}))

// Mock the project's router module to prevent top-level await hang
vi.mock('@/router', () => ({
  router: {
    push: vi.fn(),
    replace: vi.fn(),
    isReady: vi.fn(() => Promise.resolve()),
    beforeEach: vi.fn(),
    afterEach: vi.fn(),
    onError: vi.fn(),
    install: vi.fn(),
    currentRoute: { value: { query: {}, params: {}, path: '/' } },
  },
}))
