import { describe, expect, it } from 'vitest'
import { getErrorMessage, getValidationErrors, isApiError } from './errors'

function createAxiosError (status: number, data: Record<string, unknown> = {}) {
  return {
    isAxiosError: true,
    response: {
      status,
      data,
    },
  }
}

describe('isApiError', () => {
  it('returns true for an axios error matching the status', () => {
    const error = createAxiosError(422)
    expect(isApiError(error, 422)).toBe(true)
  })

  it('returns false for a status mismatch', () => {
    const error = createAxiosError(500)
    expect(isApiError(error, 422)).toBe(false)
  })

  it('returns false for non-axios errors', () => {
    expect(isApiError(new Error('fail'), 422)).toBe(false)
  })
})

describe('getValidationErrors', () => {
  it('extracts validation errors from 422 response', () => {
    const error = createAxiosError(422, {
      errors: { email: ['Email is required'] },
    })
    const result = getValidationErrors(error)
    expect(result).toEqual({ email: ['Email is required'] })
  })

  it('returns empty object when no errors field', () => {
    const error = createAxiosError(422, {})
    const result = getValidationErrors(error)
    expect(result).toEqual({})
  })
})

describe('getErrorMessage', () => {
  it('extracts message from error response', () => {
    const error = createAxiosError(500, { message: 'Server error' })
    const result = getErrorMessage(error)
    expect(result).toBe('Server error')
  })

  it('returns fallback when no message in response', () => {
    const error = createAxiosError(500, {})
    const result = getErrorMessage(error, 'Fallback')
    expect(result).toBe('Fallback')
  })
})
