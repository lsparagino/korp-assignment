import { createTestingPinia } from '@pinia/testing'
import { setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import en from '@/locales/en.json'
import { useAppNotification, useNotificationStore } from './useAppNotification'

// Provide i18n globally so useI18n() works outside components
const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { en },
})

// Inject the i18n instance globally
vi.mock('vue-i18n', async importOriginal => {
    const actual = await importOriginal()
    return {
        ...(actual as Record<string, unknown>),
        useI18n: () => (actual as any).createI18n({
            legacy: false,
            locale: 'en',
            messages: { en },
        }).global,
    }
})

describe('useNotificationStore', () => {
    beforeEach(() => {
        setActivePinia(createTestingPinia({
            createSpy: vi.fn,
            stubActions: false,
        }))
    })

    it('starts with an empty notifications list', () => {
        const store = useNotificationStore()
        expect(store.notifications).toEqual([])
    })

    it('adds a notification via notify', () => {
        const store = useNotificationStore()
        store.notify({ message: 'Test error' })

        expect(store.notifications).toHaveLength(1)
        expect(store.notifications[0].message).toBe('Test error')
        expect(store.notifications[0].color).toBe('error')
        expect(store.notifications[0].timeout).toBe(5000)
    })

    it('allows custom color and timeout', () => {
        const store = useNotificationStore()
        store.notify({ message: 'Success', color: 'success', timeout: 3000 })

        expect(store.notifications[0].color).toBe('success')
        expect(store.notifications[0].timeout).toBe(3000)
    })

    it('dismisses a notification by id', () => {
        const store = useNotificationStore()
        const id = store.notify({ message: 'Error 1' })
        store.notify({ message: 'Error 2' })

        expect(store.notifications).toHaveLength(2)
        store.dismiss(id)
        expect(store.notifications).toHaveLength(1)
        expect(store.notifications[0].message).toBe('Error 2')
    })

    it('assigns unique ids to notifications', () => {
        const store = useNotificationStore()
        const id1 = store.notify({ message: 'A' })
        const id2 = store.notify({ message: 'B' })

        expect(id1).not.toBe(id2)
    })
})

describe('useAppNotification', () => {
    beforeEach(() => {
        setActivePinia(createTestingPinia({
            createSpy: vi.fn,
            stubActions: false,
        }))
    })

    it('notifyError extracts message from API error', () => {
        const { notifyError, notifications } = useAppNotification()

        const apiError = {
            isAxiosError: true,
            response: {
                status: 500,
                data: { message: 'Internal Server Error' },
            },
        }

        notifyError(apiError)
        expect(notifications.value).toHaveLength(1)
        expect(notifications.value[0].message).toBe('Internal Server Error')
        expect(notifications.value[0].color).toBe('error')
    })

    it('notifyError uses fallback when no API message', () => {
        const { notifyError, notifications } = useAppNotification()

        notifyError(new Error('network'), 'Custom fallback')
        expect(notifications.value[0].message).toBe('Custom fallback')
    })

    it('notifySuccess creates a success notification', () => {
        const { notifySuccess, notifications } = useAppNotification()

        notifySuccess('Done!')
        expect(notifications.value[0].message).toBe('Done!')
        expect(notifications.value[0].color).toBe('success')
    })
})
