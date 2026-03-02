import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import en from '@/locales/en.json'
import { findByTestId } from '@/test/helpers'
import { mountWithPlugins } from '@/test/setup'
import DeleteUser from './DeleteUser.vue'

vi.mock('@/api/settings', () => ({
  deleteAccount: vi.fn().mockResolvedValue({}),
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    user: { id: 1, name: 'Test User', email: 'test@example.com', role: 'member' },
    clearToken: vi.fn(),
  }),
}))

describe('DeleteUser.vue', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  afterEach(() => {
    wrapper?.unmount()
    document.body.innerHTML = ''
  })

  async function mount () {
    wrapper = mountWithPlugins(DeleteUser, { attachTo: document.body })
    await flushPromises()
    return wrapper
  }

  it('renders the delete account trigger button', async () => {
    await mount()

    const btn = findByTestId('delete-user-trigger-btn')
    expect(btn).not.toBeNull()
    expect(btn!.text()).toContain(en.deleteUser.button)
  })

  it('opens dialog when trigger button is clicked', async () => {
    await mount()

    const triggerBtn = findByTestId('delete-user-trigger-btn')
    await triggerBtn!.trigger('click')
    await flushPromises()

    const dialog = findByTestId('delete-user-dialog')
    expect(dialog).not.toBeNull()
    expect(dialog!.text()).toContain(en.deleteUser.dialogTitle)
  })

  it('closes dialog when cancel button is clicked', async () => {
    await mount()

    // Open the dialog
    const triggerBtn = findByTestId('delete-user-trigger-btn')
    await triggerBtn!.trigger('click')
    await flushPromises()

    // Click cancel
    const cancelBtn = findByTestId('delete-user-cancel-btn')
    expect(cancelBtn).not.toBeNull()
    await cancelBtn!.trigger('click')
    await flushPromises()
  })

  it('has submit button disabled when password is empty', async () => {
    await mount()

    // Open the dialog
    const triggerBtn = findByTestId('delete-user-trigger-btn')
    await triggerBtn!.trigger('click')
    await flushPromises()

    const submitBtn = findByTestId('delete-user-submit-btn')
    expect(submitBtn).not.toBeNull()
    expect((submitBtn!.element as HTMLButtonElement).disabled).toBe(true)
  })
})
