import { PiniaColada } from '@pinia/colada'
import { createPinia, setActivePinia } from 'pinia'
import { createApp } from 'vue'

/**
 * Bootstrap a fresh Pinia + PiniaColada instance for composable / store tests.
 * Optionally seed initial state.
 */
export function setupPinia (initialState: Record<string, any> = {}) {
  const app = createApp({})
  const pinia = createPinia()
  if (Object.keys(initialState).length > 0) {
    pinia.state.value = initialState
  }
  app.use(pinia)
  app.use(PiniaColada)
  setActivePinia(pinia)
  return pinia
}
