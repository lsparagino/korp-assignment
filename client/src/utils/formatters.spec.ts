import { describe, expect, it } from 'vitest'
import { formatCurrency, formatDate, getAmountColor } from './formatters'

describe('formatCurrency', () => {
    it('formats a positive USD amount', () => {
        const result = formatCurrency(1234.56, 'USD')
        expect(result).toContain('1,234.56')
    })

    it('formats zero', () => {
        const result = formatCurrency(0, 'EUR')
        expect(result).toContain('0.00')
    })

    it('formats a negative amount', () => {
        const result = formatCurrency(-500, 'USD')
        expect(result).toContain('500')
    })
})

describe('formatDate', () => {
    it('formats a valid ISO date string', () => {
        const result = formatDate('2025-06-15T10:30:00.000Z')
        expect(result).toBeTruthy()
        expect(typeof result).toBe('string')
    })

    it('returns empty string for null', () => {
        const result = formatDate(null as unknown as string)
        expect(result).toBe('')
    })
})

describe('getAmountColor', () => {
    it('returns green for positive amounts', () => {
        expect(getAmountColor(100)).toBe('text-green-darken-1')
    })

    it('returns red for negative amounts', () => {
        expect(getAmountColor(-50)).toBe('text-red-darken-1')
    })

    it('returns grey for zero', () => {
        expect(getAmountColor(0)).toBe('text-grey-darken-1')
    })
})
