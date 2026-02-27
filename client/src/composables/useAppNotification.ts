import { defineStore, storeToRefs } from 'pinia'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { getErrorMessage } from '@/utils/errors'

export interface AppNotification {
  id: number
  message: string
  color: string
  timeout: number
}

let nextId = 0

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref<AppNotification[]>([])

  function notify (options: { message: string, color?: string, timeout?: number }) {
    const id = nextId++
    const notification: AppNotification = {
      id,
      message: options.message,
      color: options.color ?? 'error',
      timeout: options.timeout ?? 5000,
    }
    notifications.value.push(notification)
    return id
  }

  function dismiss (id: number) {
    notifications.value = notifications.value.filter(n => n.id !== id)
  }

  return { notifications, notify, dismiss }
})

export function useAppNotification () {
  const store = useNotificationStore()
  const { notifications } = storeToRefs(store)
  const { t } = useI18n()

  function notifyError (error: unknown, fallback?: string) {
    const message = getErrorMessage(error, fallback ?? t('common.genericError'))
    return store.notify({ message, color: 'error' })
  }

  function notifySuccess (message: string) {
    return store.notify({ message, color: 'success' })
  }

  function notify (options: { message: string, color?: string, timeout?: number }) {
    return store.notify(options)
  }

  function dismiss (id: number) {
    store.dismiss(id)
  }

  return { notifications, notifyError, notifySuccess, notify, dismiss }
}
