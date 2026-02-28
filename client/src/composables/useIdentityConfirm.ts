import { computed, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { getValidationErrors, isApiError } from '@/utils/errors'

export function useIdentityConfirm () {
  const authStore = useAuthStore()

  const showDialog = ref(false)
  const credential = ref('')
  const error = ref('')
  const isSubmitting = ref(false)

  const hasTwoFactor = computed(() => !!authStore.user?.two_factor_confirmed_at)

  let pendingAction: ((cred: Record<string, string>) => Promise<void>) | null = null
  let resolvePromise: (() => void) | null = null
  let rejectPromise: ((err: unknown) => void) | null = null

  function requireConfirmation (action: (cred: Record<string, string>) => Promise<void>): Promise<void> {
    return new Promise<void>((resolve, reject) => {
      pendingAction = action
      resolvePromise = resolve
      rejectPromise = reject
      credential.value = ''
      error.value = ''
      isSubmitting.value = false
      showDialog.value = true
    })
  }

  function getCredentialPayload (): Record<string, string> {
    return hasTwoFactor.value
      ? { code: credential.value }
      : { password: credential.value }
  }

  async function confirm () {
    if (!credential.value) {
      error.value = 'This field is required'
      return
    }

    if (!pendingAction) {
      return
    }

    isSubmitting.value = true
    error.value = ''

    try {
      await pendingAction(getCredentialPayload())
      showDialog.value = false
      resolvePromise?.()
    } catch (error_: unknown) {
      if (isApiError(error_, 422)) {
        const validationErrors = getValidationErrors(error_)
        if (validationErrors.password) {
          error.value = validationErrors.password[0]
        } else if (validationErrors.code) {
          error.value = validationErrors.code[0]
        } else {
          showDialog.value = false
          rejectPromise?.(error_)
        }
      } else {
        showDialog.value = false
        rejectPromise?.(error_)
      }
    } finally {
      isSubmitting.value = false
    }
  }

  function cancel () {
    showDialog.value = false
    rejectPromise?.(new Error('cancelled'))
  }

  return {
    showDialog,
    credential,
    error,
    isSubmitting,
    hasTwoFactor,
    requireConfirmation,
    confirm,
    cancel,
  }
}
