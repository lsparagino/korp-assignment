import type { App } from 'vue'
import { PiniaColada } from '@pinia/colada'
import { router } from '../router'
import { pinia } from '../stores'
import { i18n } from './i18n'
import { vuetify } from './vuetify'

export function registerPlugins(app: App) {
  app.use(i18n).use(vuetify).use(router).use(pinia).use(PiniaColada)
}
