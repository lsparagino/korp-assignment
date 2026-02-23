import { describe, expect, it, vi } from 'vitest'
import { useRefreshData } from './useRefreshData'

describe('useRefreshData', () => {
    it('starts with refreshing false', () => {
        const { refreshing } = useRefreshData(async () => { })
        expect(refreshing.value).toBe(false)
    })

    it('sets refreshing to true during execution and false after', async () => {
        let resolvePromise: () => void
        const promise = new Promise<void>(resolve => {
            resolvePromise = resolve
        })

        const { refreshing, refresh } = useRefreshData(async () => promise)

        const refreshPromise = refresh()
        expect(refreshing.value).toBe(true)

        resolvePromise!()
        await refreshPromise
        expect(refreshing.value).toBe(false)
    })

    it('resets refreshing to false on error', async () => {
        const { refreshing, refresh } = useRefreshData(async () => {
            throw new Error('Test error')
        })

        await expect(refresh()).rejects.toThrow('Test error')
        expect(refreshing.value).toBe(false)
    })

    it('prevents concurrent calls', async () => {
        const fn = vi.fn(async () => {
            await new Promise(resolve => setTimeout(resolve, 10))
        })

        const { refresh } = useRefreshData(fn)

        // Call twice simultaneously
        const p1 = refresh()
        const p2 = refresh()

        await Promise.all([p1, p2])

        // Should only be called once because the second call returns early
        expect(fn).toHaveBeenCalledOnce()
    })
})
