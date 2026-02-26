import { describe, expect, it } from 'vitest'
import { getCurrencyColors, getRoleColors, getStatusColors, getTransactionTypeColors } from './colors'

function expectColorPair (result: { bg: string, text: string }) {
  expect(result.bg).toBeTruthy()
  expect(result.text).toBeTruthy()
}

describe('getCurrencyColors', () => {
  it.each(['USD', 'EUR', 'GBP'])('returns a color pair for %s', currency => {
    expectColorPair(getCurrencyColors(currency))
  })

  it('returns a fallback for unknown currencies', () => {
    expectColorPair(getCurrencyColors('UNKNOWN'))
  })
})

describe('getStatusColors', () => {
  it.each(['active', 'frozen'])('returns a color pair for %s', status => {
    expectColorPair(getStatusColors(status))
  })

  it('returns a fallback for unknown statuses', () => {
    expectColorPair(getStatusColors('unknown'))
  })
})

describe('getRoleColors', () => {
  it.each(['admin', 'manager', 'member'])('returns a color pair for %s', role => {
    expectColorPair(getRoleColors(role))
  })

  it('is case-insensitive', () => {
    expect(getRoleColors('Admin')).toEqual(getRoleColors('admin'))
  })

  it('returns a fallback for unknown roles', () => {
    expectColorPair(getRoleColors('unknown'))
  })
})

describe('getTransactionTypeColors', () => {
  it.each(['debit', 'credit', 'transfer'])('returns a color pair for %s', type => {
    expectColorPair(getTransactionTypeColors(type))
  })

  it('returns a fallback for unknown types', () => {
    expectColorPair(getTransactionTypeColors('unknown'))
  })
})
