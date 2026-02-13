import { DateTime } from 'luxon'

/**
 * Format a numeric amount as a currency string.
 */
export function formatCurrency (amount: number, currency: string): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency,
  }).format(amount)
}

/**
 * Format a date string to dd/MM/yy HH:mm.
 */
export function formatDate (dateString: string): string {
  if (!dateString) {
    return ''
  }
  const dt = DateTime.fromISO(dateString)
  if (!dt.isValid) {
    return 'Invalid Date'
  }
  return dt.toFormat('dd/MM/yy HH:mm')
}

/**
 * Return a Vuetify text color class based on numeric amount.
 */
export function getAmountColor (amount: number): string {
  if (amount > 0) {
    return 'text-green-darken-1'
  }
  if (amount < 0) {
    return 'text-red-darken-1'
  }
  return 'text-grey-darken-1'
}
