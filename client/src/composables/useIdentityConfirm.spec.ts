import { PiniaColada } from '@pinia/colada'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createApp } from 'vue'
import { useIdentityConfirm } from './useIdentityConfirm'

vi.mock('@/utils/errors', () => ({
  isApiError: (err: unknown, status: number) =>
    typeof err === 'object' && err !== null && 'response' in err
    && (err as any).response?.status === status,
  getValidationErrors: (err: unknown) => (err as any).response?.data?.errors ?? {},
}))

function setupPinia (initialState: Record<string, any> = {}) {
  const app = createApp({})
  const pinia = createPinia()
  app.use(pinia)
  app.use(PiniaColada)
  setActivePinia(pinia)
  pinia.state.value = initialState
}

describe('useIdentityConfirm', () => {
  beforeEach(() => {
    setupPinia({
      auth: {
        user: { id: 1, name: 'Test', email: 'test@test.com', two_factor_confirmed_at: null },
      },
    })
  })

  it('starts with dialog hidden and no error', () => {
    const { showDialog, error, isSubmitting, credential } = useIdentityConfirm()

    expect(showDialog.value).toBe(false)
    expect(error.value).toBe('')
    expect(isSubmitting.value).toBe(false)
    expect(credential.value).toBe('')
  })

  it('requireConfirmation opens the dialog and resets state', () => {
    const { showDialog, requireConfirmation } = useIdentityConfirm()

    requireConfirmation(async () => {})
    expect(showDialog.value).toBe(true)
  })

  it('confirm sets error when credential is empty', async () => {
    const { error, requireConfirmation, confirm } = useIdentityConfirm()

    requireConfirmation(async () => {})
    await confirm()

    expect(error.value).toBe('This field is required')
  })

  it('confirm calls pending action with password payload when no 2FA', async () => {
    const action = vi.fn().mockResolvedValue(undefined)
    const {
      credential, showDialog, requireConfirmation, confirm,
    } = useIdentityConfirm()

    const promise = requireConfirmation(action)
    credential.value = 'my-password'
    await confirm()
    await promise

    expect(action).toHaveBeenCalledWith({ password: 'my-password' })
    expect(showDialog.value).toBe(false)
  })

  it('confirm calls pending action with code payload when 2FA is enabled', async () => {
    setupPinia({
      auth: {
        user: { id: 1, name: 'Test', email: 'test@test.com', two_factor_confirmed_at: '2024-01-01' },
      },
    })

    const action = vi.fn().mockResolvedValue(undefined)
    const { credential, hasTwoFactor, requireConfirmation, confirm } = useIdentityConfirm()

    expect(hasTwoFactor.value).toBe(true)
    const promise = requireConfirmation(action)
    credential.value = '123456'
    await confirm()
    await promise

    expect(action).toHaveBeenCalledWith({ code: '123456' })
  })

  it('confirm shows validation error for password field', async () => {
    const action = vi.fn().mockRejectedValue({
      response: { status: 422, data: { errors: { password: ['Wrong password'] } } },
    })
    const { credential, error, requireConfirmation, confirm } = useIdentityConfirm()

    requireConfirmation(action)
    credential.value = 'wrong'
    await confirm()

    expect(error.value).toBe('Wrong password')
  })

  it('confirm shows validation error for code field', async () => {
    const action = vi.fn().mockRejectedValue({
      response: { status: 422, data: { errors: { code: ['Invalid code'] } } },
    })
    const { credential, error, requireConfirmation, confirm } = useIdentityConfirm()

    requireConfirmation(action)
    credential.value = '000000'
    await confirm()

    expect(error.value).toBe('Invalid code')
  })

  it('cancel closes dialog and rejects the promise', async () => {
    const { showDialog, requireConfirmation, cancel } = useIdentityConfirm()

    const promise = requireConfirmation(async () => {})
    cancel()

    expect(showDialog.value).toBe(false)
    await expect(promise).rejects.toThrow('cancelled')
  })
})
