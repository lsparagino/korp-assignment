import { DOMWrapper, flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { useAppNotification } from '@/composables/useAppNotification'
import { mountWithPlugins } from '@/test/setup'

import AppNotification from './AppNotification.vue'

const mockNotifications = vi.fn((): Array<{ id: number, message: string, color: string, timeout: number }> => [])
const mockDismiss = vi.fn()

vi.mock('@/composables/useAppNotification', () => ({
  useAppNotification: vi.fn(),
}))

function mockComposable(notifications: Array<{ id: number, message: string, color: string, timeout: number }> = []) {
  mockNotifications.mockReturnValue(notifications)
    ; (useAppNotification as unknown as ReturnType<typeof vi.fn>).mockReturnValue({
      notifications: mockNotifications(),
      dismiss: mockDismiss,
    })
}

describe('AppNotification.vue', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  afterEach(() => {
    wrapper?.unmount()
    document.body.innerHTML = ''
    vi.clearAllMocks()
  })

  function findAllByTestId(testId: string) {
    return Array.from(document.body.querySelectorAll(`[data-testid="${testId}"]`))
      .map(el => new DOMWrapper(el as HTMLElement))
  }

  it('renders nothing when there are no notifications', () => {
    mockComposable([])
    wrapper = mountWithPlugins(AppNotification, { attachTo: document.body })

    expect(findAllByTestId('app-notification')).toHaveLength(0)
  })

  it('renders a snackbar for each notification', async () => {
    mockComposable([
      { id: 1, message: 'Success!', color: 'success', timeout: 3000 },
      { id: 2, message: 'Error!', color: 'error', timeout: 5000 },
    ])
    wrapper = mountWithPlugins(AppNotification, { attachTo: document.body })
    await flushPromises()

    const snackbars = findAllByTestId('app-notification')
    expect(snackbars.length).toBe(2)
  })

  it('displays the notification message', async () => {
    mockComposable([
      { id: 1, message: 'Item saved', color: 'success', timeout: 3000 },
    ])
    wrapper = mountWithPlugins(AppNotification, { attachTo: document.body })
    await flushPromises()

    const snackbar = findAllByTestId('app-notification')[0]!
    expect(snackbar.text()).toContain('Item saved')
  })

  it('calls dismiss with notification id when close button is clicked', async () => {
    mockComposable([
      { id: 42, message: 'Alert', color: 'warning', timeout: 3000 },
    ])
    wrapper = mountWithPlugins(AppNotification, { attachTo: document.body })
    await flushPromises()

    const closeBtn = document.body.querySelector('.v-snackbar .v-btn')
    expect(closeBtn).not.toBeNull()
    await new DOMWrapper(closeBtn as HTMLElement).trigger('click')
    await flushPromises()

    expect(mockDismiss).toHaveBeenCalledWith(42)
  })
})
