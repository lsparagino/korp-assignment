export interface User {
    id: number
    name: string
    email: string
    role: string
    two_factor_confirmed_at: string | null
}

export interface Company {
    id: number
    name: string
}

export interface Wallet {
    id: number
    name: string
    address: string
    balance: number
    currency: string
    status: 'active' | 'frozen'
    can_delete: boolean
}

export interface Transaction {
    id: number
    type: string
    amount: number
    currency: string
    reference: string | null
    from_wallet_id: number | null
    to_wallet_id: number | null
    from_wallet: { name: string, address: string } | null
    to_wallet: { name: string, address: string } | null
    created_at: string
}

export interface TeamMember {
    id: number
    name: string
    email: string
    role: string
    wallet_access: string
    is_current: boolean
    is_pending: boolean
    assigned_wallets: number[]
}

export interface RecoveryCode {
    code: string
}

export type ValidationErrors = Record<string, string[]>
