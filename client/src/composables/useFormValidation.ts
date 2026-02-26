import type { VForm } from 'vuetify/components'
import { ref } from 'vue'

export function useFormValidation () {
  const formRef = ref<InstanceType<typeof VForm> | null>(null)
  const formValid = ref(false)

  async function validate () {
    const result = await formRef.value?.validate()
    return result?.valid ?? false
  }

  function resetValidation () {
    formRef.value?.resetValidation()
  }

  return { formRef, formValid, validate, resetValidation }
}
