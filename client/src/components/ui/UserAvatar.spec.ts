import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '@/stores/auth'
import { mountWithPlugins } from '@/test/setup'

import UserAvatar from './UserAvatar.vue'

vi.mock('@/stores/auth', () => ({
  useAuthStore: vi.fn(),
}))

function mockAuthStore (overrides: Record<string, unknown> = {}) {
  ; (useAuthStore as unknown as ReturnType<typeof vi.fn>).mockReturnValue({
    user: {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
      role: 'member',
    },
    ...overrides,
  })
}

describe('UserAvatar.vue', () => {
  let wrapper: ReturnType<typeof mountWithPlugins>

  beforeEach(() => {
    mockAuthStore()
    wrapper = mountWithPlugins(UserAvatar, { attachTo: document.body })
  })

  afterEach(() => {
    wrapper?.unmount()
    document.body.innerHTML = ''
    vi.clearAllMocks()
  })

  it('renders a v-avatar', () => {
    expect(wrapper.findComponent({ name: 'v-avatar' }).exists()).toBe(true)
  })

  it('renders a mdi-account icon inside the avatar', () => {
    const icon = wrapper.findComponent({ name: 'v-icon' })
    expect(icon.exists()).toBe(true)
    expect(icon.props('icon')).toBe('mdi-account')
  })

  it('does not show user info by default', () => {
    expect(wrapper.text()).not.toContain('John Doe')
    expect(wrapper.text()).not.toContain('john@example.com')
  })

  it('shows user name, email, and role chip when showInfo is true', () => {
    wrapper.unmount()
    wrapper = mountWithPlugins(UserAvatar, {
      attachTo: document.body,
      props: { showInfo: true },
    })

    expect(wrapper.text()).toContain('John Doe')
    expect(wrapper.text()).toContain('john@example.com')
    expect(wrapper.text()).toContain('member')
  })

  it('displays admin role when user is admin', () => {
    wrapper.unmount()
    mockAuthStore({
      user: { id: 1, name: 'Admin User', email: 'admin@test.com', role: 'admin' },
    })
    wrapper = mountWithPlugins(UserAvatar, {
      attachTo: document.body,
      props: { showInfo: true },
    })

    expect(wrapper.text()).toContain('admin')
  })

  it('accepts a custom size prop', () => {
    wrapper.unmount()
    wrapper = mountWithPlugins(UserAvatar, {
      attachTo: document.body,
      props: { size: 64 },
    })

    const avatar = wrapper.findComponent({ name: 'v-avatar' })
    expect(avatar.props('size')).toBe(64)
  })

  it('hides info section when user is null', () => {
    wrapper.unmount()
    mockAuthStore({ user: null })
    wrapper = mountWithPlugins(UserAvatar, {
      attachTo: document.body,
      props: { showInfo: true },
    })

    // Should not crash, and should not show info content
    expect(wrapper.text()).not.toContain('John Doe')
  })
})
