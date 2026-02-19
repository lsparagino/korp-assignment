import { describe, expect, it, vi } from 'vitest'
import { useFormSubmit } from './useFormSubmit'

describe('useFormSubmit', () => {
    it('initializes with default state', () => {
        const { processing, errors, recentlySuccessful } = useFormSubmit({
            submitFn: async () => { },
        })

        expect(processing.value).toBe(false)
        expect(errors.value).toEqual({})
        expect(recentlySuccessful.value).toBe(false)
    })

    it('sets processing to true during submission', async () => {
        let resolvePromise: () => void
        const promise = new Promise<void>(resolve => { resolvePromise = resolve })

        const { processing, submit } = useFormSubmit({
            submitFn: async () => promise,
        })

        const submitPromise = submit({ name: 'test' })
        expect(processing.value).toBe(true)

        resolvePromise!()
        await submitPromise
        expect(processing.value).toBe(false)
    })

    it('sets recentlySuccessful on success', async () => {
        const { recentlySuccessful, submit } = useFormSubmit({
            submitFn: async () => { },
        })

        await submit({ name: 'test' })
        expect(recentlySuccessful.value).toBe(true)
    })

    it('calls onSuccess callback', async () => {
        const onSuccess = vi.fn()
        const { submit } = useFormSubmit({
            submitFn: async () => { },
            onSuccess,
        })

        await submit({ name: 'test' })
        expect(onSuccess).toHaveBeenCalledOnce()
    })

    it('calls resetForm callback on success', async () => {
        const resetForm = vi.fn()
        const { submit } = useFormSubmit({
            submitFn: async () => { },
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
            submitFn: async () => { throw error422 },
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
            submitFn: async () => { throw error422 },
        })

        await submit({})
        expect(recentlySuccessful.value).toBe(false)
    })

    it('resets processing on error', async () => {
        const { processing, submit } = useFormSubmit({
            submitFn: async () => { throw new Error('fail') },
        })

        await submit({})
        expect(processing.value).toBe(false)
    })
})
