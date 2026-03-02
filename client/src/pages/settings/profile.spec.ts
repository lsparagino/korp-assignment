import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { mountWithPlugins } from '@/test/setup'
import ProfilePage from './profile.vue'

vi.mock('@/api/settings', () => ({
    updateProfile: vi.fn(),
    deleteAccount: vi.fn(),
    cancelPendingEmail: vi.fn(),
}))

function makeAuthState(overrides: Record<string, unknown> = {}) {
    return {
        auth: {
            user: {
                id: 1,
                name: 'Test User',
                email: 'test@example.com',
                email_verified_at: '2024-01-01',
                role: 'admin',
                pending_email: null,
                ...overrides,
            },
            token: 'test-token',
        },
        company: {
            currentCompany: { id: 1, name: 'Test Corp' },
            companies: [{ id: 1, name: 'Test Corp' }],
        },
    }
}

describe('settings/profile.vue', () => {
    it('renders profile form with user data', () => {
        const wrapper = mountWithPlugins(ProfilePage, {
            piniaOptions: { initialState: makeAuthState() },
        })

        const nameInput = wrapper.find('[data-testid="profile-name-input"] input')
        expect(nameInput.exists()).toBe(true)
        expect((nameInput.element as HTMLInputElement).value).toBe('Test User')
    })

    it('save button is disabled when form is not dirty', () => {
        const wrapper = mountWithPlugins(ProfilePage, {
            piniaOptions: { initialState: makeAuthState() },
        })

        const btn = wrapper.find('[data-testid="profile-save-btn"]')
        expect(btn.attributes('disabled')).toBeDefined()
    })

    it('save button becomes enabled when form is modified', async () => {
        const wrapper = mountWithPlugins(ProfilePage, {
            piniaOptions: { initialState: makeAuthState() },
        })

        await wrapper.find('[data-testid="profile-name-input"] input').setValue('New Name')
        await flushPromises()

        const btn = wrapper.find('[data-testid="profile-save-btn"]')
        expect(btn.attributes('disabled')).toBeUndefined()
    })

    it('calls updateProfile API on form submit', async () => {
        const { updateProfile } = await import('@/api/settings')
        vi.mocked(updateProfile).mockResolvedValue({
            data: { user: { id: 1, name: 'New Name', email: 'test@example.com', email_verified_at: '2024-01-01', role: 'admin', pending_email: null } },
        } as any)

        const wrapper = mountWithPlugins(ProfilePage, {
            piniaOptions: { initialState: makeAuthState() },
        })

        await wrapper.find('[data-testid="profile-name-input"] input').setValue('New Name')
        await wrapper.find('form').trigger('submit.prevent')
        await flushPromises()

        expect(updateProfile).toHaveBeenCalledWith(
            expect.objectContaining({ name: 'New Name', email: 'test@example.com' }),
        )
    })

    it('renders delete account button', () => {
        const wrapper = mountWithPlugins(ProfilePage, {
            piniaOptions: { initialState: makeAuthState() },
        })

        expect(wrapper.find('[data-testid="delete-account-btn"]').exists()).toBe(true)
    })

    it('shows pending email warning when user has pending email', () => {
        const wrapper = mountWithPlugins(ProfilePage, {
            piniaOptions: { initialState: makeAuthState({ pending_email: 'new@example.com' }) },
        })

        expect(wrapper.text()).toContain('new@example.com')
    })
})
