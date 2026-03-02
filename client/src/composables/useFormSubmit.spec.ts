import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createDeferred } from '@/test/deferred'
import { mockI18nWithTranslations } from '@/test/i18n.mock'
import { setupPinia } from '@/test/pinia'
import { useNotificationStore } from './useAppNotification'
import { useFormSubmit } from './useFormSubmit'

mockI18nWithTranslations()

describe('useFormSubmit', () => {
  beforeEach(() => {
    setupPinia()
  })

  it('initializes with default state', () => {
    const { processing, errors, serverError, recentlySuccessful } = useFormSubmit({
      submitFn: async () => {},
    })

    expect(processing.value).toBe(false)
    expect(errors.value).toEqual({})
    expect(serverError.value).toBe('')
    expect(recentlySuccessful.value).toBe(false)
  })

  it('sets processing to true during submission', async () => {
    const { promise, resolve } = createDeferred()

    const { processing, submit } = useFormSubmit({
      submitFn: async () => promise,
    })

    const submitPromise = submit({ name: 'test' })
    expect(processing.value).toBe(true)

    resolve()
    await submitPromise
    expect(processing.value).toBe(false)
  })

  it('sets recentlySuccessful on success', async () => {
    const { recentlySuccessful, submit } = useFormSubmit({
      submitFn: async () => {},
    })

    await submit({ name: 'test' })
    expect(recentlySuccessful.value).toBe(true)
  })

  it('calls onSuccess callback', async () => {
    const onSuccess = vi.fn()
    const { submit } = useFormSubmit({
      submitFn: async () => {},
      onSuccess,
    })

    await submit({ name: 'test' })
    expect(onSuccess).toHaveBeenCalledOnce()
  })

  it('calls resetForm callback on success', async () => {
    const resetForm = vi.fn()
    const { submit } = useFormSubmit({
      submitFn: async () => {},
      resetForm,
    })

    await submit({ name: 'test' })
    expect(resetForm).toHaveBeenCalledOnce()
  })

  it('extracts validation errors on 422 response', async () => {
    const error422 = {
      isAxiosError: true,
      response: {
        status: 422,
        data: {
          errors: { email: ['Email is required'] },
        },
      },
    }

    const { errors, submit } = useFormSubmit({
      submitFn: async () => {
        throw error422
      },
    })

    await submit({ email: '' })
    expect(errors.value).toEqual({ email: ['Email is required'] })
  })

  it('does not set recentlySuccessful on error', async () => {
    const error422 = {
      isAxiosError: true,
      response: {
        status: 422,
        data: { errors: {} },
      },
    }

    const { recentlySuccessful, submit } = useFormSubmit({
      submitFn: async () => {
        throw error422
      },
    })

    await submit({})
    expect(recentlySuccessful.value).toBe(false)
  })

  it('resets processing on error', async () => {
    const { processing, submit } = useFormSubmit({
      submitFn: async () => {
        throw new Error('fail')
      },
    })

    await submit({})
    expect(processing.value).toBe(false)
  })

  it('populates serverError from API error response', async () => {
    const error500 = {
      isAxiosError: true,
      response: {
        status: 500,
        data: { message: 'Internal Server Error' },
      },
    }

    const { serverError, submit } = useFormSubmit({
      submitFn: async () => {
        throw error500
      },
    })

    await submit({})
    expect(serverError.value).toBe('Internal Server Error')
  })

  it('auto-notifies on non-422 errors when no onError callback', async () => {
    const error500 = {
      isAxiosError: true,
      response: {
        status: 500,
        data: { message: 'Server blew up' },
      },
    }

    const { submit } = useFormSubmit({
      submitFn: async () => {
        throw error500
      },
    })

    await submit({})
    const store = useNotificationStore()
    expect(store.notifications).toHaveLength(1)
    expect(store.notifications[0]!.message).toBe('Server blew up')
  })

  it('does not auto-notify on non-422 errors when onError is provided', async () => {
    const onError = vi.fn()
    const error500 = {
      isAxiosError: true,
      response: {
        status: 500,
        data: { message: 'Server error' },
      },
    }

    const { submit } = useFormSubmit({
      submitFn: async () => {
        throw error500
      },
      onError,
    })

    await submit({})
    expect(onError).toHaveBeenCalledOnce()
    const store = useNotificationStore()
    expect(store.notifications).toHaveLength(0)
  })

  it('does not auto-notify on 422 errors', async () => {
    const error422 = {
      isAxiosError: true,
      response: {
        status: 422,
        data: {
          message: 'Validation failed',
          errors: { email: ['Required'] },
        },
      },
    }

    const { submit } = useFormSubmit({
      submitFn: async () => {
        throw error422
      },
    })

    await submit({})
    const store = useNotificationStore()
    expect(store.notifications).toHaveLength(0)
  })

  it('clears serverError on new submission', async () => {
    const error500 = {
      isAxiosError: true,
      response: {
        status: 500,
        data: { message: 'Server error' },
      },
    }

    let shouldFail = true
    const { serverError, submit } = useFormSubmit({
      submitFn: async () => {
        if (shouldFail) {
          throw error500
        }
      },
    })

    await submit({})
    expect(serverError.value).toBe('Server error')

    shouldFail = false
    await submit({})
    expect(serverError.value).toBe('')
  })
})
