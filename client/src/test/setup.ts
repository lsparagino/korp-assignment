import { createTestingPinia } from '@pinia/testing'
import { mount } from '@vue/test-utils'
import { vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import en from '@/locales/en.json'

vi.mock('vue-router', async importOriginal => {
  const actual = await importOriginal()
  return {
    ...(actual as Record<string, unknown>),
    useRoute: vi.fn(() => ({ query: {} })),
    useRouter: vi.fn(() => ({ push: vi.fn(), replace: vi.fn() })),
  }
})

const vuetify = createVuetify({
  components,
  directives,
})

const i18n = createI18n({
  legacy: false,
  locale: 'en',
  messages: {
    en,
  },
})

export function mountWithPlugins (component: any, options: any = {}) {
  const { piniaOptions, ...mountOptions } = options

  return mount(component, {
    ...mountOptions,
    global: {
      ...mountOptions.global,
      plugins: [
        vuetify,
        i18n,
        createTestingPinia({
          createSpy: vi.fn,
          stubActions: false,
          ...piniaOptions,
        }),
        ...(mountOptions.global?.plugins || []),
      ],
      stubs: {
        RouterLink: true,
        ...mountOptions.global?.stubs,
      },
    },
  })
}
