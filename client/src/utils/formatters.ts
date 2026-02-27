import { DateTime } from 'luxon'
import { usePreferencesStore } from '@/stores/preferences'

export function formatCurrency(amount: number, currency: string): string {
  const store = usePreferencesStore()
  return new Intl.NumberFormat(store.numberLocale, {
    style: 'currency',
    currency: currency || 'USD',
  }).format(amount)
}

export function formatNumber(value: number): string {
  const store = usePreferencesStore()
  return new Intl.NumberFormat(store.numberLocale).format(value)
}

export function formatDate(dateString: string): string {
  if (!dateString) {
    return ''
  }
  const dt = DateTime.fromISO(dateString)
  if (!dt.isValid) {
    return 'Invalid Date'
  }
  const store = usePreferencesStore()
  return dt.toLocaleString(DateTime.DATETIME_SHORT, { locale: store.dateLocale })
}

export function getAmountColor(amount: number): string {
  if (amount > 0) {
    return 'text-green-darken-1'
  }
  if (amount < 0) {
    return 'text-red-darken-1'
  }
  return 'text-grey-darken-1'
}

const CURRENCY_ICONS: Record<string, string> = {
  USD: 'mdi-currency-usd',
  EUR: 'mdi-currency-eur',
  GBP: 'mdi-currency-gbp',
}

export function getCurrencyIcon(currency: string): string {
  return CURRENCY_ICONS[currency] ?? 'mdi-cash'
}

const CURRENCY_SYMBOLS: Record<string, string> = {
  USD: '$',
  EUR: '€',
  GBP: '£',
}

export function getCurrencySymbol(currency: string): string {
  return CURRENCY_SYMBOLS[currency] ?? currency
}
