import { flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useRoute } from 'vue-router'
import en from '@/locales/en.json'
import { makeEmptyAuthState, mountWithPlugins } from '@/test/setup'
import AcceptInvitationPage from './[token].vue'

vi.mock('@/api/auth', () => ({
    verifyInvitation: vi.fn(),
    acceptInvitation: vi.fn(),
}))

beforeEach(() => {
    vi.mocked(useRoute).mockReturnValue({
        params: { token: 'test-token-123' },
    } as any)
})

describe('[token].vue (accept-invitation)', () => {
    it('shows loading spinner while verifying token', async () => {
        const { verifyInvitation } = await import('@/api/auth')
        vi.mocked(verifyInvitation).mockReturnValue(new Promise(() => { }))

        const wrapper = mountWithPlugins(AcceptInvitationPage, {
            piniaOptions: { initialState: makeEmptyAuthState() },
        })

        expect(wrapper.find('.v-progress-circular').exists()).toBe(true)
        expect(wrapper.text()).toContain(en.auth.invitation.verifying)
    })

    it('shows invalid token message on verification failure', async () => {
        const { verifyInvitation } = await import('@/api/auth')
        vi.mocked(verifyInvitation).mockRejectedValue(new Error('Invalid'))

        const wrapper = mountWithPlugins(AcceptInvitationPage, {
            piniaOptions: { initialState: makeEmptyAuthState() },
        })
        await flushPromises()

        expect(wrapper.text()).toContain(en.auth.invitation.invalidTitle)
        expect(wrapper.text()).toContain(en.auth.invitation.backToLogin)
    })

    it('shows password form after successful verification', async () => {
        const { verifyInvitation } = await import('@/api/auth')
        vi.mocked(verifyInvitation).mockResolvedValue({
            data: { email: 'invited@example.com' },
        } as any)

        const wrapper = mountWithPlugins(AcceptInvitationPage, {
            piniaOptions: { initialState: makeEmptyAuthState() },
        })
        await flushPromises()

        expect(wrapper.text()).toContain('invited@example.com')
        expect(wrapper.findAll('input[type="password"]')).toHaveLength(2)
        expect(wrapper.text()).toContain(en.auth.invitation.activateAccount)
    })

    it('calls acceptInvitation API on submit', async () => {
        const { verifyInvitation, acceptInvitation } = await import('@/api/auth')
        vi.mocked(verifyInvitation).mockResolvedValue({
            data: { email: 'invited@example.com' },
        } as any)
        vi.mocked(acceptInvitation).mockResolvedValue({
            data: {
                access_token: 'jwt-token',
                user: { id: 1, name: 'Invited', email: 'invited@example.com', email_verified_at: '2024-01-01', role: 'member' },
            },
        } as any)

        const wrapper = mountWithPlugins(AcceptInvitationPage, {
            piniaOptions: { initialState: makeEmptyAuthState() },
        })
        await flushPromises()

        const inputs = wrapper.findAll('input[type="password"]')
        await inputs[0]!.setValue('newpassword123')
        await inputs[1]!.setValue('newpassword123')
        await wrapper.find('form').trigger('submit.prevent')
        await flushPromises()

        expect(acceptInvitation).toHaveBeenCalledWith('test-token-123', {
            password: 'newpassword123',
            password_confirmation: 'newpassword123',
        })
    })
})
