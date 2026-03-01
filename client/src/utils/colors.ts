interface ColorPair {
  bg: string
  text: string
}

const defaultColorPair: ColorPair = {
  bg: 'grey-lighten-3',
  text: 'grey-darken-3',
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
  admin: { bg: 'purple-darken-4', text: 'white' },
  manager: { bg: 'orange-lighten-2', text: 'black' },
  member: defaultColorPair,
}

const transactionTypeColorMap: Record<string, ColorPair> = {
  debit: { bg: 'red-lighten-4', text: 'red-darken-3' },
  credit: { bg: 'green-lighten-4', text: 'green-darken-3' },
  transfer: defaultColorPair,
}

const transactionStatusColorMap: Record<string, ColorPair> = {
  pending_approval: { bg: 'amber-lighten-4', text: 'amber-darken-4' },
  completed: { bg: 'green-lighten-4', text: 'green-darken-3' },
  rejected: { bg: 'red-lighten-4', text: 'red-darken-3' },
  cancelled: { bg: 'grey-lighten-2', text: 'grey-darken-2' },
}

export function getTransactionTypeColors(type: string): ColorPair {
  return transactionTypeColorMap[type.toLowerCase()] || defaultColorPair
}

export function getTransactionStatusColors(status: string): ColorPair {
  return transactionStatusColorMap[status.toLowerCase()] || defaultColorPair
}

export function getCurrencyColors(currency: string): ColorPair {
  return currencyColorMap[currency] || defaultColorPair
}

export function getStatusColors(status: string): ColorPair {
  return statusColorMap[status] || defaultColorPair
}

export function getRoleColors(role: string): ColorPair {
  return roleColorMap[role.toLowerCase()] || defaultColorPair
}
