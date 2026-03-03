import { PiniaColada } from '@pinia/colada'
import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import en from '@/locales/en.json'

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
  const pinia = createPinia()

  // Seed initial state if provided (replaces createTestingPinia's initialState)
  if (piniaOptions?.initialState) {
    pinia.state.value = piniaOptions.initialState
  }

  return mount(component, {
    ...mountOptions,
    global: {
      ...mountOptions.global,
      plugins: [
        pinia,
        PiniaColada,
        vuetify,
        i18n,
        ...(mountOptions.global?.plugins || []),
      ],
      stubs: {
        RouterLink: true,
        ...mountOptions.global?.stubs,
      },
    },
  })
}

export function makeEmptyAuthState () {
  return {
    auth: {
      user: null,
      token: null,
    },
    company: {
      currentCompany: null,
      companies: [],
    },
  }
}
