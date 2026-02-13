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
 * Format a date string to MM/DD/YY HH:mm.
 */
export function formatDate (dateString: string): string {
  if (!dateString) {
    return ''
  }
  const date = new Date(dateString)
  if (isNaN(date.getTime())) {
    return 'Invalid Date'
  }

  const pad = (n: number) => n.toString().padStart(2, '0')
  const m = pad(date.getMonth() + 1)
  const d = pad(date.getDate())
  const y = date.getFullYear().toString().slice(-2)
  const h = pad(date.getHours())
  const min = pad(date.getMinutes())

  return `${m}/${d}/${y} ${h}:${min}`
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
