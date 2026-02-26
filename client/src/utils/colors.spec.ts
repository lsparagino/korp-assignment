import { describe, expect, it } from 'vitest'
import { getCurrencyColors, getRoleColors, getStatusColors, getTransactionTypeColors } from './colors'

describe('getCurrencyColors', () => {
  it('returns a color pair for USD', () => {
    const result = getCurrencyColors('USD')
    expect(result).toHaveProperty('bg')
    expect(result).toHaveProperty('text')
  })

  it('returns a color pair for EUR', () => {
    const result = getCurrencyColors('EUR')
    expect(result).toHaveProperty('bg')
    expect(result).toHaveProperty('text')
  })

  it('returns a default color for unknown currencies', () => {
    const result = getCurrencyColors('UNKNOWN')
    expect(result).toHaveProperty('bg')
    expect(result).toHaveProperty('text')
  })
})

describe('getStatusColors', () => {
  it('returns green tones for active status', () => {
    const result = getStatusColors('active')
    expect(result.bg).toBeTruthy()
    expect(result.text).toBeTruthy()
  })

  it('returns a color pair for frozen status', () => {
    const result = getStatusColors('frozen')
    expect(result.bg).toBeTruthy()
    expect(result.text).toBeTruthy()
  })
})

describe('getRoleColors', () => {
  it('returns a color pair for owner role', () => {
    const result = getRoleColors('owner')
    expect(result).toHaveProperty('bg')
    expect(result).toHaveProperty('text')
  })

  it('returns a color pair for admin role', () => {
    const result = getRoleColors('admin')
    expect(result).toHaveProperty('bg')
    expect(result).toHaveProperty('text')
  })

  it('returns a default for unknown roles', () => {
    const result = getRoleColors('unknown')
    expect(result).toHaveProperty('bg')
    expect(result).toHaveProperty('text')
  })
})

describe('getTransactionTypeColors', () => {
  it('returns red tones for debit', () => {
    const result = getTransactionTypeColors('debit')
    expect(result.bg).toContain('red')
    expect(result.text).toContain('red')
  })

  it('returns green tones for credit', () => {
    const result = getTransactionTypeColors('credit')
    expect(result.bg).toContain('green')
    expect(result.text).toContain('green')
  })

  it('returns blue tones for transfer', () => {
    const result = getTransactionTypeColors('transfer')
    expect(result.bg).toContain('blue')
    expect(result.text).toContain('blue')
  })

  it('returns a default for unknown types', () => {
    const result = getTransactionTypeColors('unknown')
    expect(result).toHaveProperty('bg')
    expect(result).toHaveProperty('text')
  })
})
