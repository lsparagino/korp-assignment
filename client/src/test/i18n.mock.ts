import { vi } from 'vitest'
import en from '@/locales/en.json'

/**
 * Full i18n mock — use when composable needs real translations.
 * Call at module scope (outside describe blocks).
 */
export function mockI18nWithTranslations () {
  vi.mock('vue-i18n', async importOriginal => {
    const actual = await importOriginal()
    return {
      ...(actual as Record<string, unknown>),
      useI18n: () => (actual as any).createI18n({
        legacy: false,
        locale: 'en',
        messages: { en },
      }).global,
    }
  })
}

/**
 * Simple i18n mock — returns keys as-is. Use when translations don't matter.
 * Call at module scope (outside describe blocks).
 */
export function mockI18nPassthrough () {
  vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
  }))
}
