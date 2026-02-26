interface ColorPair {
    bg: string;
    text: string;
}

const currencyColorMap: Record<string, ColorPair> = {
    USD: { bg: 'blue-lighten-4', text: 'blue-darken-3' },
    EUR: { bg: 'orange-lighten-4', text: 'orange-darken-3' },
    GBP: { bg: 'indigo-lighten-4', text: 'indigo-darken-3' },
};

const statusColorMap: Record<string, ColorPair> = {
    active: { bg: 'green-lighten-4', text: 'green-darken-3' },
    frozen: { bg: 'red-lighten-4', text: 'red-darken-3' },
};

const roleColorMap: Record<string, ColorPair> = {
    admin: { bg: 'purple-darken-4', text: 'white' },
    manager: { bg: 'orange-lighten-2', text: 'black' },
    member: { bg: 'blue-lighten-4', text: 'blue-darken-3' },
};

const defaultColorPair: ColorPair = {
    bg: 'grey-lighten-3',
    text: 'grey-darken-3',
};

const transactionTypeColorMap: Record<string, ColorPair> = {
    debit: { bg: 'red-lighten-4', text: 'red-darken-3' },
    credit: { bg: 'green-lighten-4', text: 'green-darken-3' },
    transfer: { bg: 'grey-lighten-3', text: 'grey-darken-3' },
};

export function getTransactionTypeColors(type: string): ColorPair {
    return transactionTypeColorMap[type.toLowerCase()] || defaultColorPair;
}

export function getCurrencyColors(currency: string): ColorPair {
    return currencyColorMap[currency] || defaultColorPair;
}

export function getStatusColors(status: string): ColorPair {
    return statusColorMap[status] || defaultColorPair;
}

export function getRoleColors(role: string): ColorPair {
    return roleColorMap[role.toLowerCase()] || defaultColorPair;
}
