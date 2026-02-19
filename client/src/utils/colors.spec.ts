import { describe, expect, it } from 'vitest'
import { getCurrencyColors, getStatusColors, getRoleColors } from './colors'

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
