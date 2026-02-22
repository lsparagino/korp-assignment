import { ref } from 'vue'
import { getValidationErrors, isApiError } from '@/utils/errors'

interface FormSubmitOptions<T> {
  submitFn: (form: T) => Promise<unknown>
  onSuccess?: () => void
  onError?: (error: unknown) => void
  resetForm?: () => void
}

export function useFormSubmit<T> (options: FormSubmitOptions<T>) {
  const processing = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const recentlySuccessful = ref(false)

  async function submit (form: T) {
    processing.value = true
    errors.value = {}
    recentlySuccessful.value = false

    try {
      await options.submitFn(form)
      recentlySuccessful.value = true
      options.onSuccess?.()
      options.resetForm?.()
      setTimeout(() => (recentlySuccessful.value = false), 3000)
    } catch (error: unknown) {
      if (isApiError(error, 422)) {
        errors.value = getValidationErrors(error)
      } else {
        options.onError?.(error)
      }
    } finally {
      processing.value = false
    }
  }

  return {
    processing,
    errors,
    recentlySuccessful,
    submit,
  }
}
