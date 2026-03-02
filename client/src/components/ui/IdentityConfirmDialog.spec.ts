import { afterEach, describe, expect, it } from 'vitest'
import { findByTestId } from '@/test/helpers'
import { mountWithPlugins } from '@/test/setup'
import IdentityConfirmDialog from './IdentityConfirmDialog.vue'

describe('IdentityConfirmDialog.vue', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  afterEach(() => {
    wrapper?.unmount()
    document.body.innerHTML = ''
  })

  function mountDialog (props: Record<string, unknown> = {}) {
    return mountWithPlugins(IdentityConfirmDialog, {
      props: {
        modelValue: true,
        credential: '',
        error: '',
        isSubmitting: false,
        hasTwoFactor: false,
        ...props,
      },
      attachTo: document.body,
    })
  }

  it('renders with password label when no 2FA', async () => {
    wrapper = mountDialog()
    await wrapper.vm.$nextTick()

    const input = findByTestId('identity-confirm-input')
    expect(input).not.toBeNull()
    expect(document.body.textContent).toContain('Password')
  })

  it('renders with auth code label when 2FA is enabled', async () => {
    wrapper = mountDialog({ hasTwoFactor: true })
    await wrapper.vm.$nextTick()

    expect(document.body.textContent).toContain('Authentication Code')
  })

  it('shows error message when error prop is set', async () => {
    wrapper = mountDialog({ error: 'Wrong password' })
    await wrapper.vm.$nextTick()

    expect(document.body.textContent).toContain('Wrong password')
  })

  it('emits confirm on confirm button click', async () => {
    wrapper = mountDialog()
    await wrapper.vm.$nextTick()

    const confirmBtn = findByTestId('identity-confirm-submit')
    expect(confirmBtn).not.toBeNull()
    await confirmBtn!.trigger('click')

    expect(wrapper.emitted('confirm')).toBeTruthy()
  })

  it('emits cancel on cancel button click', async () => {
    wrapper = mountDialog()
    await wrapper.vm.$nextTick()

    const cancelBtn = findByTestId('identity-confirm-cancel')
    expect(cancelBtn).not.toBeNull()
    await cancelBtn!.trigger('click')

    expect(wrapper.emitted('cancel')).toBeTruthy()
  })

  it('shows loading state on submit button when isSubmitting is true', async () => {
    wrapper = mountDialog({ isSubmitting: true })
    await wrapper.vm.$nextTick()

    const submitBtn = findByTestId('identity-confirm-submit')
    expect(submitBtn).not.toBeNull()
    // Vuetify loading button gets aria-busy or the loading class
    const el = submitBtn!.element as HTMLElement
    expect(
      el.classList.contains('v-btn--loading')
      || el.getAttribute('aria-busy') === 'true',
    ).toBe(true)
  })
})
