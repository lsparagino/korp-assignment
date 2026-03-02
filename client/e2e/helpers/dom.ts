import type { Page } from '@playwright/test'

/**
 * Set a native textarea value and trigger 'input' event for Vue v-model.
 *
 * Playwright's `fill()` sometimes doesn't trigger Vue's v-model on Vuetify
 * textareas — this helper sets the value via the native property descriptor
 * and dispatches a bubbling input event.
 */
export async function setNativeTextareaValue (page: Page, testId: string, value: string) {
  const textarea = page.getByTestId(testId).locator('textarea').first()
  await textarea.click()
  await textarea.evaluate((el, val) => {
    const t = el as HTMLTextAreaElement
    const setter = Object.getOwnPropertyDescriptor(
      globalThis.HTMLTextAreaElement.prototype, 'value',
    )!.set!
    setter.call(t, val)
    t.dispatchEvent(new Event('input', { bubbles: true }))
  }, value)
}
