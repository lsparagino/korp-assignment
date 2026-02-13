interface ColorPair {
  bg: string
  text: string
}

const currencyColorMap: Record<string, ColorPair> = {
  USD: { bg: 'blue-lighten-4', text: 'blue-darken-3' },
  EUR: { bg: 'orange-lighten-4', text: 'orange-darken-3' },
  GBP: { bg: 'indigo-lighten-4', text: 'indigo-darken-3' },
}

const statusColorMap: Record<string, ColorPair> = {
  active: { bg: 'green-lighten-4', text: 'green-darken-3' },
  frozen: { bg: 'red-lighten-4', text: 'red-darken-3' },
}

const roleColorMap: Record<string, ColorPair> = {
  admin: { bg: 'grey-lighten-2', text: 'grey-darken-3' },
  member: { bg: 'blue-lighten-4', text: 'blue-darken-3' },
}

const defaultColorPair: ColorPair = {
  bg: 'grey-lighten-3',
  text: 'grey-darken-3',
}

/**
 * Get background and text colors for a currency code.
 */
export function getCurrencyColors (currency: string): ColorPair {
  return currencyColorMap[currency] || defaultColorPair
}

/**
 * Get background and text colors for a wallet status.
 */
export function getStatusColors (status: string): ColorPair {
  return statusColorMap[status] || defaultColorPair
}

/**
 * Get background and text colors for a user role.
 */
export function getRoleColors (role: string): ColorPair {
  return roleColorMap[role.toLowerCase()] || defaultColorPair
}
