import { describe, expect, it } from 'vitest'
import { useFormValidation } from './useFormValidation'

describe('useFormValidation', () => {
  it('initialises with formValid as false', () => {
    const { formValid } = useFormValidation()
    expect(formValid.value).toBe(false)
  })

  it('initialises formRef as null', () => {
    const { formRef } = useFormValidation()
    expect(formRef.value).toBeNull()
  })

  it('validate returns false when formRef is null', async () => {
    const { validate } = useFormValidation()
    const result = await validate()
    expect(result).toBe(false)
  })

  it('resetValidation does not throw when formRef is null', () => {
    const { resetValidation } = useFormValidation()
    expect(() => resetValidation()).not.toThrow()
  })
})
